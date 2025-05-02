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
                $meta = get_post_meta($horario_id);

                $hora_inicio = isset($meta['mph_hora_inicio'][0]) ? $meta['mph_hora_inicio'][0] : 'N/A';
                $hora_fin = isset($meta['mph_hora_fin'][0]) ? $meta['mph_hora_fin'][0] : 'N/A';
                $estado = isset($meta['mph_estado'][0]) ? $meta['mph_estado'][0] : 'Desconocido';
                $vacantes = isset($meta['mph_vacantes'][0]) ? intval($meta['mph_vacantes'][0]) : 0;
                /* Inicia Modificación: Leer sede adyacente */
                $sede_adyacente_id = isset($meta['mph_sede_adyacente'][0]) ? intval($meta['mph_sede_adyacente'][0]) : 0;
                /* Finaliza Modificación */

                $horario_col = esc_html($nombre_dia) . ' ' . esc_html($hora_inicio) . ' - ' . esc_html($hora_fin);

                // Inicializar columnas
                $programa_col = 'N/A';
                $sede_col = 'N/A';
                $rango_col = 'N/A';
                $disponibilidad_col = esc_html($estado); // Default

                /* Inicia Modificación: Lógica de visualización por estado */
                // Obtener IDs admisibles generales (siempre los leemos, los necesitamos para varios estados)
                $prog_admisibles_ids = isset($meta['mph_programas_admisibles'][0]) ? explode(',', $meta['mph_programas_admisibles'][0]) : array();
                $sede_admisibles_ids = isset($meta['mph_sedes_admisibles'][0]) ? explode(',', $meta['mph_sedes_admisibles'][0]) : array();
                $rango_admisibles_ids = isset($meta['mph_rangos_admisibles'][0]) ? explode(',', $meta['mph_rangos_admisibles'][0]) : array();


                switch ($estado) {
                    case 'Asignado':
                    case 'Lleno':
                        // Mostrar asignados específicos
                        $prog_id = isset($meta['mph_programa_asignado'][0]) ? intval($meta['mph_programa_asignado'][0]) : 0;
                        $sede_id = isset($meta['mph_sede_asignada'][0]) ? intval($meta['mph_sede_asignada'][0]) : 0;
                        $rango_id = isset($meta['mph_rango_de_edad_asignado'][0]) ? intval($meta['mph_rango_de_edad_asignado'][0]) : 0; // Ojo nombre meta

                        $programa_col = mph_get_term_list_string($prog_id, 'programa');
                        $sede_col = mph_get_term_list_string($sede_id, 'sede');
                        $rango_col = mph_get_term_list_string($rango_id, 'rango_edad'); // Ojo slug tax

                        $disponibilidad_col = ($estado === 'Lleno')
                            ? '<span style="color:red;">' . esc_html__('Lleno', 'mi-plugin-horarios') . '</span>'
                            : sprintf(esc_html__('%d vacantes', 'mi-plugin-horarios'), $vacantes);
                        break;

                    case 'Vacío':
                        // Mostrar admisibles generales
                        $programa_col = mph_get_term_list_string($prog_admisibles_ids, 'programa');
                        $sede_col = mph_get_term_list_string($sede_admisibles_ids, 'sede'); // Muestra TODOS los admisibles
                        $rango_col = mph_get_term_list_string($rango_admisibles_ids, 'rango_edad');
                        $disponibilidad_col = '<em>' . esc_html__('Vacío', 'mi-plugin-horarios') . '</em>';
                        break;

                    case 'Mismo':
                    case 'Mismo o Traslado':
                        // Mostrar admisibles generales para Programa y Rango
                        $programa_col = mph_get_term_list_string($prog_admisibles_ids, 'programa');
                        $rango_col = mph_get_term_list_string($rango_admisibles_ids, 'rango_edad');
                        // Mostrar Sede Adyacente + Sedes Comunes
                        $sedes_a_mostrar_ids = array();
                        if ($sede_adyacente_id > 0) {
                            $sedes_a_mostrar_ids[] = $sede_adyacente_id;
                        }
                        // Añadir IDs de sedes comunes (necesitamos obtenerlos)
                        $sedes_comunes_ids = mph_get_common_term_ids('sede'); // Necesitamos crear esta función auxiliar
                        $sedes_a_mostrar_ids = array_unique(array_merge($sedes_a_mostrar_ids, $sedes_comunes_ids));

                        $sede_col = mph_get_term_list_string($sedes_a_mostrar_ids, 'sede');
                        $disponibilidad_col = '<strong>' . esc_html(str_replace('_', ' ', $estado)) . '</strong>';
                        break;

                    case 'Traslado':
                    case 'No Disponible': // <-- Añadir este case aquí
                        $no_disp_text = '<em>' . esc_html__('No Disponible', 'mi-plugin-horarios') . '</em>';
                        $programa_col = $no_disp_text;
                        $sede_col = $no_disp_text;
                        $rango_col = $no_disp_text;

                        // Diferenciar texto de estado
                        if ($estado === 'No Disponible') {
                            $disponibilidad_col = '<strong>' . esc_html__('No Disponible (Cierre Sede)', 'mi-plugin-horarios') . '</strong>';
                        } else { // Es Traslado
                            $disponibilidad_col = '<strong>' . esc_html__('Traslado', 'mi-plugin-horarios') . '</strong>';
                        }
                        break;

                    default:
                         // Para estados desconocidos, intentar mostrar admisibles generales
                         $programa_col = mph_get_term_list_string($prog_admisibles_ids, 'programa');
                         $sede_col = mph_get_term_list_string($sede_admisibles_ids, 'sede');
                         $rango_col = mph_get_term_list_string($rango_admisibles_ids, 'rango_edad');
                         $disponibilidad_col = '<strong>' . esc_html($estado) . '</strong>';
                }
                /* Finaliza Modificación */


                // Formatear columna Acciones (Añadir Editar para Vacío/Mismo/Mismo o Traslado)
                $acciones_col = '';
                // Botón Eliminar (siempre)
                $acciones_col .= '<button type="button" class="button button-link mph-accion-horario mph-accion-eliminar" data-horario-id="' . esc_attr($horario_id) . '" data-nonce="' . esc_attr(wp_create_nonce('mph_eliminar_horario_' . $horario_id)) . '">' . esc_html__('Eliminar', 'mi-plugin-horarios') . '</button>';

                /* Inicia Modificación: Lógica botones Editar/Asignar/Vaciar */
                // Botón Asignar (para Vacío, Mismo o Traslado, Mismo)
                if (in_array($estado, ['Vacío', 'Mismo o Traslado', 'Mismo'])) {
                    $data_asignar = htmlspecialchars(json_encode(array(
                        'horario_id' => $horario_id,
                        'dia' => $num_dia,
                        'inicio' => $hora_inicio,
                        'fin' => $hora_fin,
                        'programas_admisibles' => $prog_admisibles_ids, // Pasar IDs limpios
                        'sedes_admisibles' => $sede_admisibles_ids,
                        'rangos_admisibles' => $rango_admisibles_ids,
                     )), ENT_QUOTES, 'UTF-8');
                    $acciones_col .= ' | <button type="button" class="button button-link mph-accion-horario mph-accion-asignar" data-horario-info="' . $data_asignar . '">' . esc_html__('Asignar', 'mi-plugin-horarios') . '</button>';
                }

                // Botón Editar (para Asignado, Lleno) -> Solo Vacantes
                if (in_array($estado, ['Asignado', 'Lleno'])) {


                    // Pasar todos los datos guardados para pre-llenar el modal de edición
                     $prog_admisibles_ids = isset($meta['mph_programas_admisibles'][0]) ? explode(',', $meta['mph_programas_admisibles'][0]) : array();
                     $sede_admisibles_ids = isset($meta['mph_sedes_admisibles'][0]) ? explode(',', $meta['mph_sedes_admisibles'][0]) : array();
                     $rango_admisibles_ids = isset($meta['mph_rangos_admisibles'][0]) ? explode(',', $meta['mph_rangos_admisibles'][0]) : array(); // Ojo slug tax




                     $data_editar_vacantes = array(
                        // ¿ESTÁ $horario_id AQUÍ? ($horario_id se define al principio del bucle foreach)
                        'horario_id' => $horario_id, // <-- ASEGÚRATE DE QUE ESTA LÍNEA EXISTE Y ES CORRECTA
                        'dia' => $num_dia,
                        //'inicio_gen' => '', // No las necesitamos para editar vacantes
                        //'fin_gen' => '',
                        'prog_admisibles' => array_map('intval', $prog_admisibles_ids), // Las pasamos por si la info adicional las usa
                        'sede_admisibles' => array_map('intval', $sede_admisibles_ids),
                        'rango_admisibles' => array_map('intval', $rango_admisibles_ids),
                        'inicio_asig' => $hora_inicio, // Para mostrar info
                        'fin_asig' => $hora_fin,       // Para mostrar info
                        'prog_asig' => isset($meta['mph_programa_asignado'][0]) ? intval($meta['mph_programa_asignado'][0]) : 0, // Para mostrar info
                        'sede_asig' => isset($meta['mph_sede_asignada'][0]) ? intval($meta['mph_sede_asignada'][0]) : 0,       // Para mostrar info
                        'rango_asig' => isset($meta['mph_rango_de_edad_asignado'][0]) ? intval($meta['mph_rango_de_edad_asignado'][0]) : 0, // Para mostrar info
                        'vacantes' => $vacantes, // El valor a editar
                        'buffer_antes' => isset($meta['mph_buffer_antes'][0]) ? intval($meta['mph_buffer_antes'][0]) : 0, // Para info o lógica futura
                        'buffer_despues' => isset($meta['mph_buffer_despues'][0]) ? intval($meta['mph_buffer_despues'][0]) : 0, // Para info o lógica futura
                    );


                     
                     $data_editar_vacantes_json = htmlspecialchars(json_encode($data_editar_vacantes), ENT_QUOTES, 'UTF-8');
                     $acciones_col .= ' | <button type="button" class="button button-link mph-accion-horario mph-accion-editar-vacantes" data-horario-info="' . $data_editar_vacantes_json . '">' . esc_html__('Editar Vacantes', 'mi-plugin-horarios') . '</button>'; // Cambiar clase y texto
                }

                 // Botón Editar (para Vacío, Mismo, Mismo o Traslado) -> Editar Disponibilidad General
                 if (in_array($estado, ['Vacío', 'Mismo o Traslado', 'Mismo'])) {
                     $data_editar_disp = htmlspecialchars(json_encode(array(
                         'horario_id' => $horario_id,
                         'dia' => $num_dia,
                         'inicio_gen' => $hora_inicio, // Usar horas del bloque como base
                         'fin_gen' => $hora_fin,
                         'prog_admisibles' => $prog_admisibles_ids,
                         'sede_admisibles' => $sede_admisibles_ids,
                         'rango_admisibles' => $rango_admisibles_ids,
                         // No hay datos de asignación aquí
                     )), ENT_QUOTES, 'UTF-8');
                     $acciones_col .= ' | <button type="button" class="button button-link mph-accion-horario mph-accion-editar-disp" data-horario-info="' . $data_editar_disp . '">' . esc_html__('Editar Disp.', 'mi-plugin-horarios') . '</button>'; // Nueva clase y texto
                }

                // Botón Editar (para Traslado, No Disponible) -> Solo Info (si se implementa)
                if (in_array($estado, ['Traslado', 'No Disponible'])) {
                     // $acciones_col .= ' | <button type="button" class="button button-link mph-accion-horario mph-accion-editar-info">Info</button>';
                }


                 // Botón Vaciar (para Asignado, Lleno)
                 if (in_array($estado, ['Asignado', 'Lleno'])) {
                     $nonce_vaciar = wp_create_nonce('mph_vaciar_horario_' . $horario_id);
                     $acciones_col .= ' | <button type="button" class="button button-link-delete mph-accion-horario mph-accion-vaciar" data-horario-id="' . esc_attr($horario_id) . '" data-nonce="' . esc_attr($nonce_vaciar) . '">' . esc_html__('Vaciar', 'mi-plugin-horarios') . '</button>';
                 }
                /* Finaliza Modificación */


                // Construir la fila ... (sin cambios)
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


// --- NECESITAMOS ESTA NUEVA FUNCIÓN AUXILIAR ---
/* Inicia Modificación: Añadir función para obtener sedes comunes */
/**
 * Obtiene los IDs de los términos comunes para una taxonomía dada.
 * Cachea el resultado para evitar consultas repetidas en la misma carga.
 *
 * @param string $taxonomy_slug Slug de la taxonomía (ej. 'sede').
 * @return array Array de IDs de términos comunes.
 */
function mph_get_common_term_ids( $taxonomy_slug ) {
    // Usar caché estática simple para esta petición
    static $common_ids_cache = array();

    if ( isset($common_ids_cache[$taxonomy_slug]) ) {
        return $common_ids_cache[$taxonomy_slug];
    }

    $meta_key = $taxonomy_slug . '_comun';
     // Ajuste específico para rango_edad si la meta key es diferente
     if ($taxonomy_slug === 'rango_edad') {
         $meta_key = 'rango_edad_comun';
     }

    $common_terms_query_args = array(
        'taxonomy'   => $taxonomy_slug,
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'     => $meta_key,
                'value'   => '1',
                'compare' => '=',
            ),
        ),
        'fields' => 'ids', // Obtener solo IDs
        'update_term_meta_cache' => false, // Optimización menor
    );
    $common_term_ids = get_terms( $common_terms_query_args );

    if ( is_wp_error( $common_term_ids ) ) {
         error_log("mph_get_common_term_ids: Error al obtener términos comunes para $taxonomy_slug: " . $common_term_ids->get_error_message());
        $common_term_ids = array();
    }

    $common_ids_cache[$taxonomy_slug] = $common_term_ids; // Guardar en caché
    return $common_term_ids;
}


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