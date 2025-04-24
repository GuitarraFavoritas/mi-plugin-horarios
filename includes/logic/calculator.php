<?php
/**
 * Lógica Principal para Calcular y Dividir Bloques de Horario.
 *
 * Contiene la función que recibe los datos de entrada y orquesta
 * la división del tiempo y la determinación inicial de estados.
 *
 * @package MiPluginHorarios/Includes/Logic
 * @version 1.0.0
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Salir si se accede directamente.
}

/**
 * Calcula los bloques de horario resultantes de una entrada de disponibilidad/asignación.
 *
 * Esta es la función principal que recibe los datos del formulario (vía AJAX handler)
 * y devuelve un array estructurado con la información de cada post 'Horario'
 * que necesita ser creado o actualizado. NO guarda los posts directamente.
 *
 * @param int   $maestro_id ID del CPT Maestro.
 * @param array $data Datos recibidos del formulario modal. Espera claves como:
 *                    'dia_semana', 'hora_inicio_general', 'hora_fin_general',
 *                    'programa_admisibles' (array de IDs), 'sede_admisibles' (array de IDs), 'rango_de_edad_admisibles' (array de IDs),
 *                    'hora_inicio_asignada' (opcional), 'hora_fin_asignada' (opcional),
 *                    'programa_asignado' (opcional ID), 'sede_asignada' (opcional ID), 'rango_de_edad_asignado' (opcional ID),
 *                    'vacantes' (opcional), 'buffer_minutos_antes' (opcional), 'buffer_minutos_despues' (opcional).
 *
 * @return array|WP_Error Un array de arrays, donde cada subarray representa un bloque 'Horario'
 *                        a crear/actualizar. O un WP_Error en caso de fallo.
 */
function mph_calcular_bloques_horario( $maestro_id, $data ) {
  $log_prefix = "mph_calcular_bloques_horario:";
  error_log("$log_prefix Iniciando - Maestro ID: $maestro_id, Datos: " . print_r($data, true));

  // --- 1. Validación Inicial y Limpieza de Datos Esenciales ---
  if ( empty( $maestro_id ) || !isset( $data['dia_semana'] ) || empty( $data['hora_inicio_general'] ) || empty( $data['hora_fin_general'] ) ) {
    error_log("$log_prefix Error - Datos insuficientes.");
    return new WP_Error( 'datos_insuficientes', __( 'Faltan datos esenciales para calcular los horarios.', 'mi-plugin-horarios' ) );
  }
  // Validar formato hora HH:MM (más estricto que solo 'empty')
  $time_regex = "/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/";
  if ( !preg_match($time_regex, $data['hora_inicio_general']) || !preg_match($time_regex, $data['hora_fin_general']) ) {
    error_log("$log_prefix Error - Formato de hora general inválido.");
    return new WP_Error( 'formato_hora_invalido', __( 'El formato de hora general es inválido.', 'mi-plugin-horarios' ) );
  }
  if ($data['hora_fin_general'] <= $data['hora_inicio_general']) {
     error_log("$log_prefix Error - Hora general fin <= inicio.");
     return new WP_Error( 'hora_fin_menor_inicio', __( 'La hora de fin general debe ser posterior a la hora de inicio.', 'mi-plugin-horarios' ) );
  }

  $dia_semana = intval( $data['dia_semana'] );
  $inicio_general_str = $data['hora_inicio_general'];
  $fin_general_str = $data['hora_fin_general'];

  // Obtener y asegurar que los admisibles sean arrays de enteros
  $programas_admisibles = isset($data['programa_admisibles']) ? array_map('intval', (array) $data['programa_admisibles']) : array();
  $sedes_admisibles = isset($data['sede_admisibles']) ? array_map('intval', (array) $data['sede_admisibles']) : array();
  $rangos_admisibles = isset($data['rango_de_edad_admisibles']) ? array_map('intval', (array) $data['rango_de_edad_admisibles']) : array();

  // Validar que al menos una opción admisible fue seleccionada por tipo
  if (empty($programas_admisibles) || empty($sedes_admisibles) || empty($rangos_admisibles)) {
     error_log("$log_prefix Error - Faltan selecciones de admisibilidad.");
     return new WP_Error('admisibles_faltantes', __('Debe seleccionar al menos una opción admisible para Programas, Sedes y Rangos de Edad.', 'mi-plugin-horarios'));
  }


  // --- 2. Determinar si hay una Asignación Específica ---
  $hay_asignacion = ! empty( $data['hora_inicio_asignada'] ) && ! empty( $data['hora_fin_asignada'] ) &&
            ! empty( $data['programa_asignado'] ) && ! empty( $data['sede_asignada'] ) && ! empty( $data['rango_de_edad_asignado'] ) &&
            preg_match($time_regex, $data['hora_inicio_asignada']) && preg_match($time_regex, $data['hora_fin_asignada']) && // Validar formato hora asignada
            $data['hora_fin_asignada'] > $data['hora_inicio_asignada']; // Validar fin > inicio asignada

  $bloques_resultantes = array();

  // --- 3. Lógica de División de Tiempo ---

  if ( ! $hay_asignacion ) {
    // --- 3.a. Caso: Solo Disponibilidad General (Bloque único 'Vacío') ---
    error_log("$log_prefix Calculando bloque único 'Vacío'.");
    $bloque_vacio = mph_crear_sub_bloque(
      $maestro_id, $dia_semana,
      $inicio_general_str, $fin_general_str,
      'Vacío',
      $programas_admisibles, $sedes_admisibles, $rangos_admisibles
    );
    $bloques_resultantes[] = $bloque_vacio;

  } else {
    // --- 3.b. Caso: Hay Asignación Específica (Dividir en Antes, Asignado, Después) ---
    error_log("$log_prefix Calculando división por asignación específica.");

    // Validar y limpiar datos de asignación
    $inicio_asignado_str = $data['hora_inicio_asignada'];
    $fin_asignado_str = $data['hora_fin_asignada'];
    $programa_asignado = intval($data['programa_asignado']);
    $sede_asignada = intval($data['sede_asignada']);
    $rango_asignado = intval($data['rango_de_edad_asignado']);
    $vacantes = isset($data['vacantes']) ? max(0, intval($data['vacantes'])) : 0; // Asegurar >= 0
    $buffer_antes_min = isset($data['buffer_minutos_antes']) ? max(0, intval($data['buffer_minutos_antes'])) : 0; // Asegurar >= 0
    $buffer_despues_min = isset($data['buffer_minutos_despues']) ? max(0, intval($data['buffer_minutos_despues'])) : 0; // Asegurar >= 0

     try {
       // Convertir horas a objetos DateTime
       $base_date = '1970-01-01 ';
       $dt_inicio_general = new DateTime($base_date . $inicio_general_str);
       $dt_fin_general = new DateTime($base_date . $fin_general_str);
       $dt_inicio_asignado = new DateTime($base_date . $inicio_asignado_str);
       $dt_fin_asignado = new DateTime($base_date . $fin_asignado_str);

       // Validaciones de coherencia de tiempo (asignado dentro de general)
       if ($dt_inicio_asignado < $dt_inicio_general || $dt_fin_asignado > $dt_fin_general) {
         error_log("$log_prefix Error - Horas asignadas fuera del rango general.");
         return new WP_Error('horas_fuera_rango', __('Las horas asignadas deben estar dentro del rango general.', 'mi-plugin-horarios'));
       }

       // Calcular puntos de tiempo clave para los buffers
       $dt_inicio_buffer_antes = clone $dt_inicio_asignado;
       if ($buffer_antes_min > 0) { $dt_inicio_buffer_antes->sub(new DateInterval("PT{$buffer_antes_min}M")); }

       $dt_fin_buffer_despues = clone $dt_fin_asignado;
       if ($buffer_despues_min > 0) { $dt_fin_buffer_despues->add(new DateInterval("PT{$buffer_despues_min}M")); }

       // Ajustar buffers para que no se salgan del rango general
       $dt_inicio_buffer_antes = max($dt_inicio_buffer_antes, $dt_inicio_general);
       $dt_fin_buffer_despues = min($dt_fin_buffer_despues, $dt_fin_general);


       // --- Generar Bloque(s) Vacío ANTES del buffer (si aplica) ---
       if ($dt_inicio_buffer_antes > $dt_inicio_general) {
         error_log("$log_prefix Creando bloque 'Vacío' ANTES.");
         $bloques_resultantes[] = mph_crear_sub_bloque(
           $maestro_id, $dia_semana,
           $dt_inicio_general->format('H:i'),
           $dt_inicio_buffer_antes->format('H:i'),
           'Vacío',
           $programas_admisibles, $sedes_admisibles, $rangos_admisibles
         );
       }

        // --- Generar Bloque Buffer ANTES (si aplica) ---
        // Solo si el buffer tiene duración (inicio buffer < inicio asignado)
        if ($dt_inicio_buffer_antes < $dt_inicio_asignado) {
           error_log("$log_prefix Determinando estado para 'Buffer ANTES'.");
           $estado_buffer_antes = mph_determinar_estado_buffer(
            $maestro_id, $dia_semana,
            $dt_inicio_buffer_antes->format('H:i'),
            $dt_inicio_asignado->format('H:i'),
            'antes',
            $sede_asignada
          );
           error_log("$log_prefix Estado para 'Buffer ANTES' determinado como: $estado_buffer_antes");

           // Admisibles para buffer: ¿Los generales o los de la clase asignada?
           // Por ahora, usaremos los de la clase asignada, ya que el estado depende de ella.
           $bloques_resultantes[] = mph_crear_sub_bloque(
             $maestro_id, $dia_semana,
             $dt_inicio_buffer_antes->format('H:i'),
             $dt_inicio_asignado->format('H:i'),
             $estado_buffer_antes,
             array($programa_asignado), array($sede_asignada), array($rango_asignado), // Usar asignados como admisibles
             0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min // Guardar ref a asignación original
           );
        }


       // --- Generar Bloque ASIGNADO ---
       $estado_asignado = ($vacantes > 0) ? 'Asignado' : 'Lleno';
       error_log("$log_prefix Creando bloque '$estado_asignado'.");
       $bloques_resultantes[] = mph_crear_sub_bloque(
         $maestro_id, $dia_semana,
         $dt_inicio_asignado->format('H:i'),
         $dt_fin_asignado->format('H:i'),
         $estado_asignado,
         array($programa_asignado), array($sede_asignada), array($rango_asignado), // Admisibles = Asignado
         $vacantes,
         $programa_asignado, $sede_asignada, $rango_asignado,
         $buffer_antes_min, $buffer_despues_min
       );


       // --- Generar Bloque Buffer DESPUÉS (si aplica) ---
       // Solo si el buffer tiene duración (fin asignado < fin buffer)
       if ($dt_fin_asignado < $dt_fin_buffer_despues) {
          error_log("$log_prefix Determinando estado para 'Buffer DESPUES'.");
          $estado_buffer_despues = mph_determinar_estado_buffer(
            $maestro_id, $dia_semana,
            $dt_fin_asignado->format('H:i'),
            $dt_fin_buffer_despues->format('H:i'),
            'despues',
            $sede_asignada
          );
          error_log("$log_prefix Estado para 'Buffer DESPUES' determinado como: $estado_buffer_despues");

          // Admisibles para buffer: Usar asignados como base
          $bloques_resultantes[] = mph_crear_sub_bloque(
            $maestro_id, $dia_semana,
            $dt_fin_asignado->format('H:i'),
            $dt_fin_buffer_despues->format('H:i'),
            $estado_buffer_despues,
            array($programa_asignado), array($sede_asignada), array($rango_asignado),
            0, $programa_asignado, $sede_asignada, $rango_asignado, $buffer_antes_min, $buffer_despues_min
          );
       }


       // --- Generar Bloque(s) Vacío DESPUÉS del buffer (si aplica) ---
       if ($dt_fin_buffer_despues < $dt_fin_general) {
         error_log("$log_prefix Creando bloque 'Vacío' DESPUES.");
         $bloques_resultantes[] = mph_crear_sub_bloque(
           $maestro_id, $dia_semana,
           $dt_fin_buffer_despues->format('H:i'),
           $dt_fin_general->format('H:i'),
           'Vacío',
           $programas_admisibles, $sedes_admisibles, $rangos_admisibles // Hereda generales
         );
       }

     } catch (Exception $e) {
       error_log("$log_prefix EXCEPCION al procesar fechas: " . $e->getMessage());
       return new WP_Error('fecha_invalida', __('Error al procesar las horas proporcionadas.', 'mi-plugin-horarios'));
     }
  } // Fin else $hay_asignacion

  error_log("$log_prefix Finalizando - Bloques resultantes: " . count($bloques_resultantes));
  return $bloques_resultantes;

} // Fin de mph_calcular_bloques_horario

?>