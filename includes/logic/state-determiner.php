<?php
/**
 * Lógica para Determinar el Estado de los Bloques de Horario.
 *
 * Contiene la función principal y posibles auxiliares para calcular
 * estados como 'Mismo', 'Traslado', 'No Disponible', etc.
 *
 * @package MiPluginHorarios/Includes/Logic
 * @version 1.0.0
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Determina el estado específico de un bloque de buffer ('Mismo', 'Traslado', 'No Disponible', 'Mismo o Traslado').
 * Consulta otros horarios del maestro en el mismo día y datos de la sede adyacente.
 *
 * @param int $maestro_id ID del Maestro.
 * @param int $dia_semana Día de la semana (1-7).
 * @param string $hora_inicio_str Hora inicio del buffer (HH:MM).
 * @param string $hora_fin_str Hora fin del buffer (HH:MM).
 * @param string $posicion 'antes' o 'despues' de la clase asignada.
 * @param int $sede_asignada_adyacente ID de la sede de la clase adyacente a este buffer.
 * @return string El estado calculado ('Mismo', 'Traslado', 'No Disponible', 'Mismo o Traslado').
 */
function mph_determinar_estado_buffer( $maestro_id, $dia_semana, $hora_inicio_str, $hora_fin_str, $posicion, $sede_asignada_adyacente ) {
    $log_prefix = "mph_determinar_estado_buffer:";
    error_log("$log_prefix Iniciando - Maestro: $maestro_id, Dia: $dia_semana, Buffer: $hora_inicio_str-$hora_fin_str, Pos: $posicion, Sede Ady: $sede_asignada_adyacente");

    // --- Obtener datos de la Sede Adyacente ---
    $hora_cierre_sede_adyacente = null;
    $es_sede_adyacente_comun = false;
    $nombre_sede_adyacente = '';
    if ( $sede_asignada_adyacente > 0 ) {
        $hora_cierre_raw = get_term_meta( $sede_asignada_adyacente, 'hora_cierre', true );
        if ( $hora_cierre_raw && preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $hora_cierre_raw) ) {
             $hora_cierre_sede_adyacente = $hora_cierre_raw;
        }
        $es_comun_raw = get_term_meta( $sede_asignada_adyacente, 'sede_comun', true );
        $es_sede_adyacente_comun = !empty($es_comun_raw) && $es_comun_raw === '1';
        $term_sede = get_term($sede_asignada_adyacente, 'sede');
        if ($term_sede && !is_wp_error($term_sede)) { $nombre_sede_adyacente = $term_sede->name; }
        error_log("$log_prefix Sede Adyacente ID: $sede_asignada_adyacente ($nombre_sede_adyacente) - Hora Cierre: " . ($hora_cierre_sede_adyacente ?? 'N/A') . " - Es Común: " . ($es_sede_adyacente_comun ? 'Sí' : 'No'));
    } else {
         error_log("$log_prefix Advertencia: No se proporcionó ID de sede adyacente.");
    }

    // --- Lógica de Estado: Prioridad 1: No Disponible por Cierre ---
    if ( $posicion === 'despues' && !$es_sede_adyacente_comun && $hora_cierre_sede_adyacente ) {
        try {
            $base_date = '1970-01-01 ';
            $dt_inicio_buffer = new DateTime($base_date . $hora_inicio_str);
            $dt_hora_cierre = new DateTime($base_date . $hora_cierre_sede_adyacente);
            if ($dt_inicio_buffer >= $dt_hora_cierre) {
                error_log("$log_prefix Estado = No Disponible (Inicio buffer >= Hora Cierre)");
                return 'No Disponible';
            }
        } catch (Exception $e) {
            error_log("$log_prefix Error comparando hora cierre: " . $e->getMessage());
        }
    }

    // --- Obtener todos los horarios del maestro para ese día ---
    // Nota: Considerar optimización pasando esto como parámetro si hay muchas llamadas.
    $horarios_del_dia = mph_get_horarios_existentes_dia( $maestro_id, $dia_semana );

    // Necesitamos comparar las horas como objetos DateTime
    $dt_inicio_buffer_actual = null;
    $dt_fin_buffer_actual = null;
     try {
          $base_date = '1970-01-01 ';
          $dt_inicio_buffer_actual = new DateTime($base_date . $hora_inicio_str);
          $dt_fin_buffer_actual = new DateTime($base_date . $hora_fin_str);
     } catch (Exception $e) {
         error_log("$log_prefix Error creando DateTime para buffer actual: " . $e->getMessage());
         error_log("$log_prefix Devolviendo estado por defecto 'Mismo o Traslado' debido a error de fecha.");
         return 'Mismo o Traslado'; // No se puede determinar contexto sin fechas válidas
     }


    // --- Lógica de Estado: Prioridad 2: Mismo (Inicio/Fin de Jornada) ---
    $es_inicio_jornada = true;
    $es_fin_jornada = true;

    if ( !empty($horarios_del_dia) ) {
         $primera_hora_dia = null;
         $ultima_hora_dia = null;

         foreach ($horarios_del_dia as $horario_existente) {
             $inicio_existente_str = get_post_meta($horario_existente->ID, 'mph_hora_inicio', true);
             $fin_existente_str = get_post_meta($horario_existente->ID, 'mph_hora_fin', true);

             if ($inicio_existente_str && $fin_existente_str) {
                 try {
                     $dt_inicio_existente = new DateTime($base_date . $inicio_existente_str);
                     $dt_fin_existente = new DateTime($base_date . $fin_existente_str);

                     // Encontrar la hora más temprana y la más tardía del día
                     if ($primera_hora_dia === null || $dt_inicio_existente < $primera_hora_dia) {
                         $primera_hora_dia = $dt_inicio_existente;
                     }
                     if ($ultima_hora_dia === null || $dt_fin_existente > $ultima_hora_dia) {
                         $ultima_hora_dia = $dt_fin_existente;
                     }

                 } catch (Exception $e) {
                      error_log("$log_prefix Error procesando fecha de horario existente ($horario_existente->ID): " . $e->getMessage());
                      continue;
                 }
             }
         } // Fin foreach para encontrar rango horario

         // Determinar si el buffer actual está al inicio o al final del rango encontrado
         if ($primera_hora_dia !== null && $dt_inicio_buffer_actual > $primera_hora_dia) {
              $es_inicio_jornada = false;
         }
         if ($ultima_hora_dia !== null && $dt_fin_buffer_actual < $ultima_hora_dia) {
             $es_fin_jornada = false;
         }

          // Aplicar estado "Mismo" si corresponde
          if ( ($posicion === 'antes' && $es_inicio_jornada) || ($posicion === 'despues' && $es_fin_jornada) ) {
               error_log("$log_prefix Estado = Mismo (Posicion: $posicion, InicioJornada: $es_inicio_jornada, FinJornada: $es_fin_jornada)");
               return 'Mismo';
          }

    } else {
         // Si no hay otros horarios, este buffer ES el inicio Y el fin de la jornada
         error_log("$log_prefix No hay otros horarios. Estado = Mismo (Inicio y Fin de Jornada)");
          return 'Mismo';
    }


    // --- Lógica de Estado: Prioridad 3: Traslado (Entre clases en Sedes Diferentes) ---
    $otra_clase_adyacente_post = null;
    $sede_otra_clase_adyacente = 0;

    if (!empty($horarios_del_dia)) {
         $clase_anterior = null;
         $clase_posterior = null;
         $diff_anterior = null;
         $diff_posterior = null;

         foreach ($horarios_del_dia as $horario_existente) {
             $estado_existente = get_post_meta($horario_existente->ID, 'mph_estado', true);
             if ($estado_existente === 'Asignado' || $estado_existente === 'Lleno') {
                 $inicio_existente_str = get_post_meta($horario_existente->ID, 'mph_hora_inicio', true);
                 $fin_existente_str = get_post_meta($horario_existente->ID, 'mph_hora_fin', true);
                 if ($inicio_existente_str && $fin_existente_str) {
                     try {
                         $dt_inicio_existente = new DateTime($base_date . $inicio_existente_str);
                         $dt_fin_existente = new DateTime($base_date . $fin_existente_str);

                         // Buscar clase POSTERIOR (si buffer es 'despues')
                         if ($posicion === 'despues') {
                             if ($dt_inicio_existente >= $dt_fin_buffer_actual) { // Empieza cuando/después termina el buffer
                                 $current_diff = $dt_inicio_existente->getTimestamp() - $dt_fin_buffer_actual->getTimestamp();
                                 if ($clase_posterior === null || $current_diff < $diff_posterior) {
                                     $diff_posterior = $current_diff;
                                     $clase_posterior = $horario_existente;
                                 }
                             }
                         }
                         // Buscar clase ANTERIOR (si buffer es 'antes')
                         elseif ($posicion === 'antes') {
                             if ($dt_fin_existente <= $dt_inicio_buffer_actual) { // Termina cuando/antes empieza el buffer
                                $current_diff = $dt_inicio_buffer_actual->getTimestamp() - $dt_fin_existente->getTimestamp();
                                 if ($clase_anterior === null || $current_diff < $diff_anterior) {
                                     $diff_anterior = $current_diff;
                                     $clase_anterior = $horario_existente;
                                 }
                             }
                         }

                     } catch (Exception $e) { continue; }
                 }
             }
         } // Fin foreach para buscar adyacentes

         // Determinar si hay que trasladarse
         if ($posicion === 'despues' && $clase_posterior) {
             $sede_otra_clase_adyacente = (int) get_post_meta($clase_posterior->ID, 'mph_sede_asignada', true);
             if ($sede_otra_clase_adyacente > 0 && $sede_asignada_adyacente > 0 && $sede_otra_clase_adyacente !== $sede_asignada_adyacente) {
                 error_log("$log_prefix Estado = Traslado (Buffer Despues entre sedes $sede_asignada_adyacente y $sede_otra_clase_adyacente)");
                 return 'Traslado';
             }
         } elseif ($posicion === 'antes' && $clase_anterior) {
             $sede_otra_clase_adyacente = (int) get_post_meta($clase_anterior->ID, 'mph_sede_asignada', true);
              if ($sede_otra_clase_adyacente > 0 && $sede_asignada_adyacente > 0 && $sede_otra_clase_adyacente !== $sede_asignada_adyacente) {
                 error_log("$log_prefix Estado = Traslado (Buffer Antes entre sedes $sede_otra_clase_adyacente y $sede_asignada_adyacente)");
                 return 'Traslado';
             }
         }
    } // Fin if !empty horarios_del_dia

    // --- Lógica de Estado: Default ---
    error_log("$log_prefix No se aplicó estado específico ('No Disponible', 'Mismo', 'Traslado'). Devolviendo estado por defecto 'Mismo o Traslado'.");
    return 'Mismo o Traslado';

} // Fin mph_determinar_estado_buffer

?>