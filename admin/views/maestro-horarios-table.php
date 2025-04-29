<?php
/**
 * Funciones para genera el HTML de la tabla de horarios para un maestro específico.
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}



/**
 *  Realiza una WP_Query para obtener los posts 'Horario' asociados
 * y los formatea en una tabla HTML.
 *
 * @param int $maestro_id ID del maestro.
 * @return string HTML de la tabla o mensaje si no hay horarios.
 */
function mph_get_horarios_table_html( $maestro_id ) {
    $log_prefix = "mph_get_horarios_table_html:";
    error_log("$log_prefix Generando tabla para Maestro ID: $maestro_id");

    // Usar la función auxiliar que ya creamos para obtener horarios por día
    // Necesitamos iterar por cada día de la semana para mostrar todo ordenado
    $output = '';
    $dias_semana = array(
        1 => __('Lunes', 'mi-plugin-horarios'),
        2 => __('Martes', 'mi-plugin-horarios'),
        3 => __('Miércoles', 'mi-plugin-horarios'),
        4 => __('Jueves', 'mi-plugin-horarios'),
        5 => __('Viernes', 'mi-plugin-horarios'),
        6 => __('Sábado', 'mi-plugin-horarios'),
        7 => __('Domingo', 'mi-plugin-horarios'),
    );

    $horarios_encontrados_total = false;

    $output .= '<table class="wp-list-table widefat fixed striped mph-tabla-horarios">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th>' . esc_html__('Horario', 'mi-plugin-horarios') . '</th>';
    $output .= '<th>' . esc_html__('Programa(s)', 'mi-plugin-horarios') . '</th>';
    $output .= '<th>' . esc_html__('Sede(s)', 'mi-plugin-horarios') . '</th>';
    $output .= '<th>' . esc_html__('Rango(s) Edad', 'mi-plugin-horarios') . '</th>';
    $output .= '<th>' . esc_html__('Disponibilidad', 'mi-plugin-horarios') . '</th>';
    $output .= '<th>' . esc_html__('Acciones', 'mi-plugin-horarios') . '</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    foreach ($dias_semana as $num_dia => $nombre_dia) {
        $horarios_dia = mph_get_horarios_existentes_dia( $maestro_id, $num_dia );

        if ( !empty($horarios_dia) ) {
            $horarios_encontrados_total = true;
             error_log("$log_prefix Encontrados " . count($horarios_dia) . " horarios para el día $num_dia.");

            // Opcional: Añadir fila separadora para el día
            // $output .= '<tr class="mph-dia-separador"><td colspan="6"><strong>' . esc_html($nombre_dia) . '</strong></td></tr>';

            foreach ($horarios_dia as $horario_post) {
                $horario_id = $horario_post->ID;
                // Obtener metadatos
                $meta = get_post_meta($horario_id); // Obtener todos los meta para eficiencia

                $hora_inicio = isset($meta['mph_hora_inicio'][0]) ? $meta['mph_hora_inicio'][0] : 'N/A';
                $hora_fin = isset($meta['mph_hora_fin'][0]) ? $meta['mph_hora_fin'][0] : 'N/A';
                $estado = isset($meta['mph_estado'][0]) ? $meta['mph_estado'][0] : 'Desconocido';
                $vacantes = isset($meta['mph_vacantes'][0]) ? intval($meta['mph_vacantes'][0]) : 0;

                // Formatear la columna Horario
                $horario_col = esc_html($nombre_dia) . ' ' . esc_html($hora_inicio) . ' - ' . esc_html($hora_fin);

                // Formatear columnas de taxonomías (admisibles o asignadas)
                $programa_col = '';
                $sede_col = '';
                $rango_col = '';

                if ($estado === 'Asignado' || $estado === 'Lleno' || $estado === 'Mismo' || $estado === 'Traslado' || $estado === 'Mismo o Traslado' || $estado === 'No Disponible') {
                     // Mostrar asignados (si el estado implica una asignación base)
                     $prog_id = isset($meta['mph_programa_asignado'][0]) ? intval($meta['mph_programa_asignado'][0]) : 0;
                     $sede_id = isset($meta['mph_sede_asignada'][0]) ? intval($meta['mph_sede_asignada'][0]) : 0;
                     $rango_id = isset($meta['mph_rango_de_edad_asignado'][0]) ? intval($meta['mph_rango_de_edad_asignado'][0]) : 0;

                     if ($prog_id > 0) $programa_col = mph_get_term_list_string($prog_id, 'programa');
                     if ($sede_id > 0) $sede_col = mph_get_term_list_string($sede_id, 'sede');
                     if ($rango_id > 0) $rango_col = mph_get_term_list_string($rango_id, 'rango_edad');

                } else { // Para estado 'Vacío'
                     // Mostrar admisibles
                     $prog_ids_str = isset($meta['mph_programas_admisibles'][0]) ? $meta['mph_programas_admisibles'][0] : '';
                     $sede_ids_str = isset($meta['mph_sedes_admisibles'][0]) ? $meta['mph_sedes_admisibles'][0] : '';
                     $rango_ids_str = isset($meta['mph_rangos_admisibles'][0]) ? $meta['mph_rangos_admisibles'][0] : '';

                     $programa_col = mph_get_term_list_string(explode(',', $prog_ids_str), 'programa');
                     $sede_col = mph_get_term_list_string(explode(',', $sede_ids_str), 'sede');
                     $rango_col = mph_get_term_list_string(explode(',', $rango_ids_str), 'rango_edad');
                }


                 // Formatear columna Disponibilidad
                 $disponibilidad_col = '';
                 switch ($estado) {
                    case 'Asignado':
                        $disponibilidad_col = sprintf(esc_html__('%d vacantes', 'mi-plugin-horarios'), $vacantes);
                        break;
                    case 'Lleno':
                         $disponibilidad_col = '<span style="color:red;">' . esc_html__('Lleno', 'mi-plugin-horarios') . '</span>';
                         break;
                    case 'Vacío':
                         $disponibilidad_col = '<em>' . esc_html__('Vacío', 'mi-plugin-horarios') . '</em>';
                         break;
                     case 'Mismo':
                     case 'Traslado':
                     case 'Mismo o Traslado':
                     case 'No Disponible':
                         $disponibilidad_col = '<strong>' . esc_html(str_replace('_', ' ', $estado)) . '</strong>'; // Muestra el estado directamente
                         break;
                    default:
                         $disponibilidad_col = esc_html($estado);
                 }

                 // Formatear columna Acciones (con data-attributes para JS)
                 $acciones_col = '';
                 // Botón Eliminar (para todos)
                 $acciones_col .= '<button type="button" class="button button-link mph-accion-horario mph-accion-eliminar" data-horario-id="' . esc_attr($horario_id) . '" data-nonce="' . esc_attr(wp_create_nonce('mph_eliminar_horario_' . $horario_id)) . '">' . esc_html__('Eliminar', 'mi-plugin-horarios') . '</button>';

                // Botón Asignar (para Vacío, Mismo o Traslado, Mismo, Traslado)
                if (in_array($estado, ['Vacío', 'Mismo o Traslado', 'Mismo', 'Traslado'])) {
                     // Pasar datos necesarios para pre-llenar el modal de asignación
                     $data_asignar = htmlspecialchars(json_encode(array(
                         'horario_id' => $horario_id, // ID del bloque a reemplazar/dividir
                         'dia' => $num_dia,
                         'inicio' => $hora_inicio,
                         'fin' => $hora_fin,
                         'programas_admisibles' => isset($meta['mph_programas_admisibles'][0]) ? explode(',', $meta['mph_programas_admisibles'][0]) : array(),
                         'sedes_admisibles' => isset($meta['mph_sedes_admisibles'][0]) ? explode(',', $meta['mph_sedes_admisibles'][0]) : array(),
                         'rangos_admisibles' => isset($meta['mph_rangos_admisibles'][0]) ? explode(',', $meta['mph_rangos_admisibles'][0]) : array(),
                     )), ENT_QUOTES, 'UTF-8');
                     $acciones_col .= ' | <button type="button" class="button button-link mph-accion-horario mph-accion-asignar" data-horario-info="' . $data_asignar . '">' . esc_html__('Asignar', 'mi-plugin-horarios') . '</button>';
                }

                 // Botón Editar (para Asignado, Lleno)
                     if (in_array($estado, ['Asignado', 'Lleno'])) {
                          // Pasar todos los datos guardados para pre-llenar el modal de edición
                          /* Inicia Modificación: Incluir IDs admisibles en data_editar */
                          $prog_admisibles_ids = isset($meta['mph_programas_admisibles'][0]) ? explode(',', $meta['mph_programas_admisibles'][0]) : array();
                          $sede_admisibles_ids = isset($meta['mph_sedes_admisibles'][0]) ? explode(',', $meta['mph_sedes_admisibles'][0]) : array();
                          $rango_admisibles_ids = isset($meta['mph_rangos_admisibles'][0]) ? explode(',', $meta['mph_rangos_admisibles'][0]) : array();

                          $data_editar = array(
                             'horario_id' => $horario_id,
                             'dia' => $num_dia,
                             // 'inicio_gen' => '', // Seguimos sin tenerlas fácilmente
                             // 'fin_gen' => '',
                             'prog_admisibles' => array_map('intval', $prog_admisibles_ids), // Pasar IDs admisibles
                             'sede_admisibles' => array_map('intval', $sede_admisibles_ids), // Pasar IDs admisibles
                             'rango_admisibles' => array_map('intval', $rango_admisibles_ids), // Pasar IDs admisibles (OJO slug tax)
                             'inicio_asig' => $hora_inicio,
                             'fin_asig' => $hora_fin,
                             'prog_asig' => isset($meta['mph_programa_asignado'][0]) ? intval($meta['mph_programa_asignado'][0]) : 0,
                             'sede_asig' => isset($meta['mph_sede_asignada'][0]) ? intval($meta['mph_sede_asignada'][0]) : 0,
                             'rango_asig' => isset($meta['mph_rango_de_edad_asignado'][0]) ? intval($meta['mph_rango_de_edad_asignado'][0]) : 0, // OJO nombre meta
                             'vacantes' => $vacantes,
                             'buffer_antes' => isset($meta['mph_buffer_antes'][0]) ? intval($meta['mph_buffer_antes'][0]) : 0,
                             'buffer_despues' => isset($meta['mph_buffer_despues'][0]) ? intval($meta['mph_buffer_despues'][0]) : 0,
                         );
                          /* Finaliza Modificación */
                         $data_editar_json = htmlspecialchars(json_encode($data_editar), ENT_QUOTES, 'UTF-8');
                         $acciones_col .= ' | <button type="button" class="button button-link mph-accion-horario mph-accion-editar" data-horario-info="' . $data_editar_json . '">' . esc_html__('Editar', 'mi-plugin-horarios') . '</button>';
                    }


                // Construir la fila de la tabla
                $output .= '<tr>';
                $output .= '<td>' . $horario_col . '</td>';
                $output .= '<td>' . $programa_col . '</td>';
                $output .= '<td>' . $sede_col . '</td>';
                $output .= '<td>' . $rango_col . '</td>';
                $output .= '<td>' . $disponibilidad_col . '</td>';
                $output .= '<td>' . $acciones_col . '</td>';
                $output .= '</tr>';
            } // Fin foreach $horarios_dia
        } else {
             // Opcional: Mostrar mensaje si no hay horarios para este día
             // error_log("$log_prefix No se encontraron horarios para el día $num_dia.");
        }
    } // Fin foreach $dias_semana

    $output .= '</tbody>';
    $output .= '</table>';

    if (!$horarios_encontrados_total) {
         error_log("$log_prefix No se encontró ningún horario para el Maestro ID: $maestro_id");
         return '<p>' . esc_html__('Aún no se han registrado horarios para este maestro.', 'mi-plugin-horarios') . '</p>';
    }

    return $output;

} // Fin mph_get_horarios_table_html

/**
 * Función auxiliar para obtener una lista legible de nombres de términos a partir de IDs.
 *
 * @param int|array $term_ids ID único o array de IDs de términos.
 * @param string $taxonomy Slug de la taxonomía.
 * @return string String HTML con los nombres de los términos separados por comas o 'N/A'.
 */
function mph_get_term_list_string( $term_ids, $taxonomy ) {
    if ( empty($term_ids) ) return 'N/A';

    // Asegurarse de que sea un array de enteros no vacíos
    $term_ids = array_map('intval', (array) $term_ids);
    $term_ids = array_filter($term_ids); // Eliminar ceros o valores vacíos

    if ( empty($term_ids) ) return 'N/A';

    $term_names = array();
    // Obtener los términos eficientemente
    $terms = get_terms( array(
        'taxonomy' => $taxonomy,
        'include' => $term_ids,
        'hide_empty' => false,
        'fields' => 'names' // Obtener solo los nombres
    ));

    if ( !is_wp_error($terms) && !empty($terms) ) {
        return implode(', ', $terms); // Unir nombres con coma
    }

    return 'Error al obtener términos'; // O devolver N/A si prefieres
}