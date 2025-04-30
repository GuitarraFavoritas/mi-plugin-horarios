<?php
/**
 * Funciones Auxiliares para la Lógica de Horarios.
 *
 * Contiene funciones de ayuda reutilizables para el cálculo y consulta de horarios.
 *
 * @package MiPluginHorarios/Includes/Logic
 * @version 1.0.0
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Función auxiliar para crear la estructura de un sub-bloque de horario.
 * Prepara un array con los datos necesarios para crear o actualizar un post 'horario'.
 *
* @param int $maestro_id
* @param int $dia_semana
* @param string $hora_inicio_str
* @param string $hora_fin_str
* @param string $estado
* @param array $programas_admisibles IDs generales admisibles.
* @param array $sedes_admisibles IDs generales admisibles.
* @param array $rangos_admisibles IDs generales admisibles.
* @param int $vacantes
* @param int $programa_asignado
* @param int $sede_asignada ID de la sede específica asignada a ESTE bloque (si aplica).
* @param int $rango_asignado
* @param int $buffer_antes
* @param int $buffer_despues
* @param int $sede_adyacente_id ID de la sede de la clase ASIGNADA adyacente (para buffers). <-- NUEVO PARÁMETRO
* @return array Estructura del bloque lista para ser procesada.
 */
function mph_crear_sub_bloque( $maestro_id, $dia_semana, $hora_inicio_str, $hora_fin_str, $estado,
                               $programas_admisibles, $sedes_admisibles, $rangos_admisibles,
                               $vacantes = 0, $programa_asignado = 0, $sede_asignada = 0, $rango_asignado = 0,
                               $buffer_antes = 0, $buffer_despues = 0, $sede_adyacente_id = 0 ) {

    // Log detallado de la creación del bloque
    $log_prefix = "mph_crear_sub_bloque:";
    error_log("$log_prefix Creando bloque - Maestro: $maestro_id, Dia: $dia_semana, Hora: $hora_inicio_str-$hora_fin_str, Estado: $estado, Vacantes: $vacantes, ProgAsig: $programa_asignado, SedeAsig: $sede_asignada, RangoAsig: $rango_asignado, SedeAdy: $sede_adyacente_id");

    // Asegurarse de que los arrays de admisibles sean arrays y contengan enteros
    $programas_admisibles = !empty($programas_admisibles) ? array_map('intval', (array)$programas_admisibles) : array();
    $sedes_admisibles = !empty($sedes_admisibles) ? array_map('intval', (array)$sedes_admisibles) : array();
    $rangos_admisibles = !empty($rangos_admisibles) ? array_map('intval', (array)$rangos_admisibles) : array();


    // Generar título descriptivo
    // TODO: Considerar obtener nombres de programa/sede para el título si es asignado o lleno.
    $titulo_bloque = sprintf("Maestro %d - Día %d - %s-%s - %s", $maestro_id, $dia_semana, $hora_inicio_str, $hora_fin_str, $estado);
    if (($estado === 'Asignado' || $estado === 'Lleno') && $programa_asignado > 0) {
         $term_prog = get_term($programa_asignado, 'programa');
         if ($term_prog && !is_wp_error($term_prog)) {
              $titulo_bloque .= " (" . $term_prog->name . ")";
         }
    }

    // Preparar array para meta_input (usado en wp_insert_post/wp_update_post)
    $meta_input_data = array(
        'maestro_id'                 => $maestro_id, // Guardamos el ID del maestro relacionado
        'mph_dia_semana'             => $dia_semana,
        'mph_hora_inicio'            => $hora_inicio_str,
        'mph_hora_fin'               => $hora_fin_str,
        'mph_estado'                 => $estado,
        // Guardar IDs como string separado por comas (fácil de guardar/consultar como meta simple)
        'mph_programas_admisibles'   => implode(',', $programas_admisibles),
        'mph_sedes_admisibles'       => implode(',', $sedes_admisibles),
        'mph_rangos_admisibles'      => implode(',', $rangos_admisibles),
        // Guardar datos específicos de la asignación (si existen)
        'mph_vacantes'               => intval($vacantes),
        'mph_programa_asignado'      => intval($programa_asignado),
        'mph_sede_asignada'          => intval($sede_asignada),
        'mph_rango_de_edad_asignado' => intval($rango_asignado),
        'mph_buffer_antes'           => intval($buffer_antes), // Buffer original que generó este bloque (si aplica)
        'mph_buffer_despues'         => intval($buffer_despues), // Buffer original que generó este bloque (si aplica)
        'mph_sede_adyacente'         => intval($sede_adyacente_id),
    );
    error_log("$log_prefix Meta Input preparado: " . print_r($meta_input_data, true));

    return array(
        'dia_semana'  => $dia_semana, // Para posible uso interno antes de guardar
        'hora_inicio' => $hora_inicio_str,
        'hora_fin'    => $hora_fin_str,
        'estado'      => $estado,
        'meta_input'  => $meta_input_data,
        'post_title'  => $titulo_bloque // Título para el post CPT 'horario'
    );
}

/**
 * Obtiene los bloques de horario (posts CPT 'horario') existentes para un maestro en un día específico.
 * Utilizado para determinar contextos como inicio/fin de jornada o clases adyacentes.
 * Ordena por hora de inicio.
 *
 * @param int $maestro_id ID del maestro.
 * @param int $dia_semana Día de la semana (1=Lunes, 7=Domingo).
 * @return array Array de objetos WP_Post para los horarios encontrados, ordenados por hora de inicio. Array vacío si no hay o error.
 */
function mph_get_horarios_existentes_dia( $maestro_id, $dia_semana ) {
    $log_prefix = "mph_get_horarios_existentes_dia:";
    error_log("$log_prefix Buscando horarios para Maestro ID $maestro_id, Día $dia_semana");

    if ( empty( $maestro_id ) || empty( $dia_semana ) ) {
        error_log("$log_prefix Error: Maestro ID o Día inválido.");
        return array();
    }

    $args = array(
        'post_type'      => 'horario',
        'post_status'    => 'publish', // Considerar solo los publicados
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'maestro_id',
                'value'   => $maestro_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
            array(
                'key'     => 'mph_dia_semana',
                'value'   => $dia_semana,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
            // Cláusula para ordenar por meta 'mph_hora_inicio'
            'orderby_hora_inicio' => array(
                 'key'     => 'mph_hora_inicio',
                 'compare' => 'EXISTS', // Asegura que la meta exista para ordenar
            ),
        ),
        // Especificar el ordenamiento por la clave meta definida arriba
        'orderby' => array(
            'orderby_hora_inicio' => 'ASC',
        ),
    );

    $query = new WP_Query( $args );

    $posts_encontrados = $query->posts;
    $count = count($posts_encontrados);
    error_log("$log_prefix WP_Query completada. Encontrados $count horarios.");

    return $posts_encontrados;
}

?>