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
 * @param int $maestro_id ID del Maestro.
 * @param int $dia_semana Día de la semana (1-7).
 * @param string $hora_inicio_str Hora de inicio (ej. '09:00').
 * @param string $hora_fin_str Hora de fin (ej. '10:00').
 * @param string $estado Estado del bloque ('Vacío', 'Asignado', 'Lleno', 'Mismo', 'Traslado', etc.).
 * @param array $programas_admisibles IDs generales admisibles para este bloque específico.
 * @param array $sedes_admisibles IDs generales admisibles para este bloque específico.
 * @param array $rangos_admisibles IDs generales admisibles para este bloque específico.
 * @param int $vacantes Número de vacantes (si aplica, default 0).
 * @param int $programa_asignado ID del programa asignado (si aplica, default 0).
 * @param int $sede_asignada ID de la sede específica asignada a ESTE bloque (si aplica).
 * @param int $rango_asignado ID del rango de edad asignado (si aplica, default 0).
 * @param int $buffer_antes Buffer 'antes' original de la asignación (si aplica, default 0).
 * @param int $buffer_despues Buffer 'después' original de la asignación (si aplica, default 0).
 * @param int $sede_adyacente_id ID de la sede de la clase ASIGNADA adyacente (para buffers).
 * @return array Estructura del bloque lista para ser procesada.
 */
function mph_crear_sub_bloque( $maestro_id, $dia_semana, $hora_inicio_str, $hora_fin_str, $estado,
                               $programas_admisibles, $sedes_admisibles, $rangos_admisibles,
                               $vacantes = 0, $programa_asignado = 0, $sede_asignada = 0, $rango_asignado = 0,
                               $buffer_antes = 0, $buffer_despues = 0, $sede_adyacente_id = 0 ) {

    $log_prefix = "mph_crear_sub_bloque:";
    error_log("$log_prefix Creando bloque - Maestro: $maestro_id, Dia: $dia_semana, Hora: $hora_inicio_str-$hora_fin_str, Estado: $estado, SedeAsig: $sede_asignada, SedeAdy: $sede_adyacente_id");

    $programas_admisibles = !empty($programas_admisibles) ? array_map('intval', (array)$programas_admisibles) : array();
    $sedes_admisibles = !empty($sedes_admisibles) ? array_map('intval', (array)$sedes_admisibles) : array();
    $rangos_admisibles = !empty($rangos_admisibles) ? array_map('intval', (array)$rangos_admisibles) : array();

    $titulo_bloque = sprintf("Maestro %d - Día %d - %s-%s - %s", $maestro_id, $dia_semana, $hora_inicio_str, $hora_fin_str, $estado);
    if (($estado === 'Asignado' || $estado === 'Lleno') && $programa_asignado > 0) {
         $term_prog = get_term($programa_asignado, 'programa');
         if ($term_prog && !is_wp_error($term_prog)) {
              $titulo_bloque .= " (" . $term_prog->name . ")";
         }
    }

    $meta_input_data = array(
        'maestro_id'                 => $maestro_id,
        'mph_dia_semana'             => $dia_semana,
        'mph_hora_inicio'            => $hora_inicio_str,
        'mph_hora_fin'               => $hora_fin_str,
        'mph_estado'                 => $estado,
        'mph_programas_admisibles'   => implode(',', $programas_admisibles),
        'mph_sedes_admisibles'       => implode(',', $sedes_admisibles),
        'mph_rangos_admisibles'      => implode(',', $rangos_admisibles),
        'mph_vacantes'               => intval($vacantes),
        'mph_programa_asignado'      => intval($programa_asignado),
        'mph_sede_asignada'          => intval($sede_asignada),
        'mph_rango_de_edad_asignado' => intval($rango_asignado),
        'mph_buffer_antes'           => intval($buffer_antes),
        'mph_buffer_despues'         => intval($buffer_despues),
        'mph_sede_adyacente'         => intval($sede_adyacente_id),
    );
    // error_log("$log_prefix Meta Input preparado: " . print_r($meta_input_data, true));

    return array(
        'dia_semana'  => $dia_semana,
        'hora_inicio' => $hora_inicio_str,
        'hora_fin'    => $hora_fin_str,
        'estado'      => $estado,
        'meta_input'  => $meta_input_data,
        'post_title'  => $titulo_bloque
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

/* Nueva función para filtrar sedes por hora de cierre */
/**
 * Filtra una lista de IDs de sedes, devolviendo solo aquellas que están abiertas
 * a una hora específica, más todas las sedes marcadas como comunes.
 *
 * @param array  $sede_ids_originales Array de IDs de términos de la taxonomía 'sede'.
 * @param string $hora_actual_str Hora del bloque actual (HH:MM) para la comparación.
 * @return array Array de IDs de sedes filtradas.
 */
function mph_get_filtered_admisibles_sedes( $sede_ids_originales, $hora_actual_str ) {
    $log_prefix = "mph_get_filtered_admisibles_sedes:";
    if ( empty($sede_ids_originales) ) {
        return array();
    }
    error_log("$log_prefix Filtrando sedes para la hora: $hora_actual_str. Originales: " . print_r($sede_ids_originales, true));

    $sedes_filtradas_ids = array();
    $base_date = '1970-01-01 ';
    $dt_hora_actual = null;

    try {
        $dt_hora_actual = new DateTime($base_date . $hora_actual_str);
    } catch (Exception $e) {
        error_log("$log_prefix Error creando DateTime para hora_actual_str ($hora_actual_str): " . $e->getMessage());
        return $sede_ids_originales; // Devolver originales si la hora es inválida
    }

    foreach ( (array) $sede_ids_originales as $sede_id ) {
        $sede_id = intval($sede_id);
        if ($sede_id <= 0) continue;

        $es_comun_raw = get_term_meta( $sede_id, 'sede_comun', true );
        $es_sede_comun = !empty($es_comun_raw) && $es_comun_raw === '1';

        if ( $es_sede_comun ) {
            $sedes_filtradas_ids[] = $sede_id; // Las sedes comunes siempre se incluyen
            continue;
        }

        // Para sedes no comunes, verificar hora de cierre
        $hora_cierre_raw = get_term_meta( $sede_id, 'hora_cierre', true );
        if ( $hora_cierre_raw && preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $hora_cierre_raw) ) {
            try {
                $dt_hora_cierre = new DateTime($base_date . $hora_cierre_raw);
                // La sede está abierta si la hora actual es ANTES de la hora de cierre
                if ( $dt_hora_actual < $dt_hora_cierre ) {
                    $sedes_filtradas_ids[] = $sede_id;
                } else {
                     error_log("$log_prefix Sede ID $sede_id (cierra $hora_cierre_raw) está cerrada a las $hora_actual_str y fue excluida.");
                }
            } catch (Exception $e) {
                error_log("$log_prefix Error creando DateTime para hora_cierre de Sede ID $sede_id: " . $e->getMessage());
                // Si hay error con la hora de cierre, ¿incluirla por defecto o excluirla? Por seguridad, excluir.
            }
        } else {
            // No tiene hora de cierre definida, se asume siempre abierta
            $sedes_filtradas_ids[] = $sede_id;
        }
    }
    $result = array_unique($sedes_filtradas_ids);
    error_log("$log_prefix Sedes filtradas resultantes: " . print_r($result, true));
    return $result;
}

?>