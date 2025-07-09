<?php
/**
 * Manejadores para las Peticiones AJAX del Plugin.
 *
 * Define las funciones callback que se ejecutan cuando JavaScript
 * envía datos al servidor (ej. al guardar un horario).
 *
 * @package MiPluginHorarios/Includes
 * @version 1.0.0
 */

// Salida de seguridad
if (!defined("ABSPATH")) {
    exit();
}

/**
 * Registra las acciones AJAX de WordPress.
 *
 * Engancha nuestras funciones PHP a los hooks AJAX correspondientes.
 * 'wp_ajax_{action}' para usuarios logueados.
 * 'wp_ajax_nopriv_{action}' para usuarios no logueados (si fuera necesario).
 */
function mph_register_ajax_actions()
{
    add_action(
        "wp_ajax_mph_guardar_horario_maestro",
        "mph_ajax_guardar_horario_maestro_callback"
    );
    add_action(
        "wp_ajax_mph_eliminar_horario",
        "mph_ajax_eliminar_horario_callback"
    );
    add_action(
        "wp_ajax_mph_actualizar_vacantes",
        "mph_ajax_actualizar_vacantes_callback"
    );
    add_action(
        "wp_ajax_mph_vaciar_horario",
        "mph_ajax_vaciar_horario_callback"
    );
}
add_action("init", "mph_register_ajax_actions"); // Registrar las acciones al inicio

/**
 * Callback para la acción AJAX 'mph_guardar_horario_maestro'.
 *
 * Procesa los datos enviados desde el modal de gestión de horarios,
 * calcula los bloques resultantes, crea/actualiza los posts CPT 'Horario',
 * y devuelve una respuesta JSON.
 */

function mph_ajax_guardar_horario_maestro_callback() { // Línea 53
    global $wpdb;
    $log_prefix = "AJAX mph_guardar_horario_maestro (Inteligente V3.1 Corregida):"; // Nueva versión
    error_log("$log_prefix Petición AJAX recibida.");

    // --- 1. Seguridad y Sanitización ---
    $nonce_action = 'mph_guardar_horario_action';
    $nonce_name = 'mph_nonce_guardar';
    if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( sanitize_key($_POST[$nonce_name]), $nonce_action ) ) {
        error_log("$log_prefix Error: Falló Nonce. Recibido: " . sanitize_key($_POST[$nonce_name] ?? 'No recibido'));
        wp_send_json_error( array( 'message' => __( 'Error de seguridad (Nonce).', 'mi-plugin-horarios' ) ), 403 ); return;
    }
    error_log("$log_prefix Nonce verificado.");
    if ( ! current_user_can( 'edit_others_posts' ) ) { error_log("$log_prefix Error: Permisos."); wp_send_json_error( array( 'message' => __( 'Sin permisos.', 'mi-plugin-horarios' ) ), 403 ); return; }
    error_log("$log_prefix Permisos verificados.");

    // --- 2. Obtención y Sanitización de Datos POST ---
    $sanitized_data = array(); // Array para datos limpios
    $sanitized_data['maestro_id'] = isset($_POST['maestro_id']) ? intval($_POST['maestro_id']) : 0;
    $sanitized_data['dia_semana'] = isset($_POST['dia_semana']) ? intval($_POST['dia_semana']) : 0;
    $sanitized_data['hora_inicio_general'] = isset($_POST['hora_inicio_general']) ? sanitize_text_field($_POST['hora_inicio_general']) : '';
    $sanitized_data['hora_fin_general'] = isset($_POST['hora_fin_general']) ? sanitize_text_field($_POST['hora_fin_general']) : '';
    $sanitized_data['hora_inicio_asignada'] = isset($_POST['hora_inicio_asignada']) ? sanitize_text_field($_POST['hora_inicio_asignada']) : '';
    $sanitized_data['hora_fin_asignada'] = isset($_POST['hora_fin_asignada']) ? sanitize_text_field($_POST['hora_fin_asignada']) : '';
    $sanitized_data['programa_admisibles'] = isset($_POST['programa_admisibles']) ? array_map('intval', (array)$_POST['programa_admisibles']) : array();
    $sanitized_data['sede_admisibles'] = isset($_POST['sede_admisibles']) ? array_map('intval', (array)$_POST['sede_admisibles']) : array();
    $sanitized_data['rango_de_edad_admisibles'] = isset($_POST['rango_edad_admisibles']) ? array_map('intval', (array)$_POST['rango_edad_admisibles']) : array();
    $sanitized_data['programa_asignado'] = isset($_POST['programa_asignado']) ? intval($_POST['programa_asignado']) : 0;
    $sanitized_data['sede_asignada'] = isset($_POST['sede_asignada']) ? intval($_POST['sede_asignada']) : 0;
    $sanitized_data['rango_de_edad_asignado'] = isset($_POST['rango_edad_asignado']) ? intval($_POST['rango_edad_asignado']) : 0;
    $sanitized_data['vacantes'] = isset($_POST['vacantes']) ? intval($_POST['vacantes']) : 0;
    $sanitized_data['buffer_minutos_antes'] = isset($_POST['buffer_minutos_antes']) ? intval($_POST['buffer_minutos_antes']) : 0;
    $sanitized_data['buffer_minutos_despues'] = isset($_POST['buffer_minutos_despues']) ? intval($_POST['buffer_minutos_despues']) : 0;
    $id_bloque_original_si_edita = isset($_POST['horario_id']) ? intval($_POST['horario_id']) : 0;
    // error_log("$log_prefix Datos POST sanitizados. ID original: $id_bloque_original_si_edita");
    // El ID del bloque que se está editando/reemplazando (si aplica)
    $id_bloque_original_operacion = isset($_POST['horario_id']) ? intval($_POST['horario_id']) : 0;
    // error_log("$log_prefix Datos POST sanitizados. ID original para operación: $id_bloque_original_operacion");

    // --- 3. Calcular Bloques Nuevos/Ideales ---
    $bloques_calculados_nuevos = mph_calcular_bloques_horario( $sanitized_data['maestro_id'], $sanitized_data );
    if ( is_wp_error( $bloques_calculados_nuevos ) ) { wp_send_json_error( array( 'message' => $bloques_calculados_nuevos->get_error_message() ), 400 ); return; }
    error_log("$log_prefix " . count($bloques_calculados_nuevos) . " bloques 'nuevos' calculados (estado ideal).");

    $wpdb->query('START TRANSACTION');
    $errores_operacion = array();
    $posts_creados_ids = array();
    $posts_actualizados_ids = array();
    $posts_borrados_ids = array();
    $base_date = '1970-01-01 '; // Para comparaciones de DateTime si es necesario

    // --- 4. Obtener Horarios Existentes y Crear Línea de Tiempo ---
    $horarios_existentes_db_dia = mph_get_horarios_existentes_dia( $sanitized_data['maestro_id'], $sanitized_data['dia_semana'] );
    error_log("$log_prefix Encontrados " . count($horarios_existentes_db_dia) . " horarios existentes en BD para el día.");

    $puntos_de_tiempo_str = array();
    foreach ($horarios_existentes_db_dia as $h_existente) { // <-- USAR VARIABLE CORRECTA
        $puntos_de_tiempo_str[] = get_post_meta($h_existente->ID, 'mph_hora_inicio', true);
        $puntos_de_tiempo_str[] = get_post_meta($h_existente->ID, 'mph_hora_fin', true);
    }
    foreach ($bloques_calculados_nuevos as $b_nuevo) {
        $puntos_de_tiempo_str[] = $b_nuevo['hora_inicio'];
        $puntos_de_tiempo_str[] = $b_nuevo['hora_fin'];
    }
    $puntos_de_tiempo_str = array_filter(array_unique($puntos_de_tiempo_str));
    usort($puntos_de_tiempo_str, 'strcmp');
    error_log("$log_prefix Puntos de tiempo: " . print_r($puntos_de_tiempo_str, true));


    // --- 5. Decidir Operaciones por Intervalo Atómico ---
    $operaciones = array('crear' => array(), 'actualizar' => array(), 'borrar' => array());

    for ($i = 0; $i < count($puntos_de_tiempo_str) - 1; $i++) {
        $intervalo_inicio = $puntos_de_tiempo_str[$i];
        $intervalo_fin = $puntos_de_tiempo_str[$i+1];
        if ($intervalo_inicio >= $intervalo_fin) continue;

        $intervalo_key = "$intervalo_inicio-$intervalo_fin";
        error_log("$log_prefix Procesando intervalo: $intervalo_key");

        $bloque_existente_en_intervalo = null;
        foreach($horarios_existentes_db_dia as $h) { if(get_post_meta($h->ID, 'mph_hora_inicio', true) <= $intervalo_inicio && get_post_meta($h->ID, 'mph_hora_fin', true) >= $intervalo_fin) {$bloque_existente_en_intervalo = $h; break;} }
        $bloque_nuevo_para_intervalo = null;
        foreach($bloques_calculados_nuevos as $b_n) { if($b_n['hora_inicio'] <= $intervalo_inicio && $b_n['hora_fin'] >= $intervalo_fin) {$bloque_nuevo_para_intervalo = $b_n; break;} }

        if ($bloque_nuevo_para_intervalo && !$bloque_existente_en_intervalo) {
            $operaciones['crear'][$intervalo_key] = $bloque_nuevo_para_intervalo;
            error_log("$log_prefix Acción para $intervalo_key: CREAR.");
        }
        elseif (!$bloque_nuevo_para_intervalo && $bloque_existente_en_intervalo) {
            $operaciones['borrar'][$bloque_existente_en_intervalo->ID] = true;
            error_log("$log_prefix Acción para $intervalo_key: BORRAR ID {$bloque_existente_en_intervalo->ID}.");
        }
        elseif ($bloque_nuevo_para_intervalo && $bloque_existente_en_intervalo) {
            $mismas_horas = (get_post_meta($bloque_existente_en_intervalo->ID, 'mph_hora_inicio', true) === $bloque_nuevo_para_intervalo['hora_inicio'] &&
                             get_post_meta($bloque_existente_en_intervalo->ID, 'mph_hora_fin', true) === $bloque_nuevo_para_intervalo['hora_fin']);
            if ($mismas_horas) {
                if (mph_bloques_son_diferentes_en_meta($bloque_existente_en_intervalo->ID, $bloque_nuevo_para_intervalo['meta_input'])) {
                    $operaciones['actualizar'][$bloque_existente_en_intervalo->ID] = $bloque_nuevo_para_intervalo;
                    error_log("$log_prefix Acción para $intervalo_key: ACTUALIZAR ID {$bloque_existente_en_intervalo->ID}.");
                } else {
                     error_log("$log_prefix Acción para $intervalo_key: NADA (idénticos).");
                }
            } else {
                 $operaciones['borrar'][$bloque_existente_en_intervalo->ID] = true;
                 $operaciones['crear'][$intervalo_key] = $bloque_nuevo_para_intervalo;
                 error_log("$log_prefix Acción para $intervalo_key: BORRAR ID {$bloque_existente_en_intervalo->ID} y CREAR nuevo (horas difieren).");
            }
        }
    }

    // --- 6. Ejecutar Operaciones de BD ---
    // BORRAR
    error_log("$log_prefix Ejecutando " . count($operaciones['borrar']) . " operaciones de BORRADO.");
    foreach (array_keys($operaciones['borrar']) as $id_a_borrar) {
        if (!wp_delete_post($id_a_borrar, true)) {
            $errores_operacion[] = "Fallo al borrar post ID $id_a_borrar.";
        } else {
             $posts_borrados_ids[] = $id_a_borrar;
        }
    }

    // ACTUALIZAR
    error_log("$log_prefix Ejecutando " . count($operaciones['actualizar']) . " operaciones de ACTUALIZACIÓN.");
    foreach ($operaciones['actualizar'] as $id_a_actualizar => $b_nuevo_data) {
        $update_args = array(
            'ID'         => $id_a_actualizar,
            'post_title' => sanitize_text_field($b_nuevo_data['post_title']),
            'meta_input' => $b_nuevo_data['meta_input']
        );
        $update_result = wp_update_post($update_args, true);
        if (is_wp_error($update_result)) {
            $errores_operacion[] = "Fallo al actualizar post ID $id_a_actualizar: " . $update_result->get_error_message();
        } else {
            $posts_actualizados_ids[] = $id_a_actualizar;
        }
    }

    // CREAR
    error_log("$log_prefix Ejecutando " . count($operaciones['crear']) . " operaciones de CREACIÓN.");
    foreach ($operaciones['crear'] as $b_nuevo) {
        $post_data = array(
             'post_type'    => 'horario',
             'post_title'   => sanitize_text_field( $b_nuevo['post_title'] ),
             'post_status'  => 'publish',
             'post_author'  => get_current_user_id(),
             'meta_input'   => $b_nuevo['meta_input']
         );
        $new_post_id = wp_insert_post($post_data, true);
         if ( is_wp_error( $new_post_id ) ) {
            $errores_operacion[] = "Fallo al crear nuevo bloque '{$b_nuevo['post_title']}': " . $new_post_id->get_error_message();
        } else {
             $posts_creados_ids[] = $new_post_id;
        }
    }

    // --- 7. Finalizar Transacción y Enviar Respuesta ---
    if (empty($errores_operacion)) {
        $wpdb->query('COMMIT');
        error_log("$log_prefix Éxito. Creados:".count($posts_creados_ids).", Actualizados:".count($posts_actualizados_ids).", Borrados:".count($posts_borrados_ids));
        $html_tabla = '';
        if ( function_exists( 'mph_get_horarios_table_html' ) ) {
             $html_tabla = mph_get_horarios_table_html( $sanitized_data['maestro_id'] );
        }
        wp_send_json_success( array( 'message' => __( 'Horario guardado con éxito.', 'mi-plugin-horarios' ), 'html_tabla' => $html_tabla ) );
    } else {
        $wpdb->query('ROLLBACK');
        $error_string = implode( '; ', $errores_operacion );
        error_log("$log_prefix Fallo con errores: $error_string. Transacción revertida.");
        wp_send_json_error( array( 'message' => __( 'Errores al guardar: ', 'mi-plugin-horarios' ) . $error_string ), 500 );
    }

} // Fin de mph_ajax_guardar_horario_maestro_callback // Línea 258


/**
 * Callback para la acción AJAX 'mph_eliminar_horario'.
 *
 * Elimina un post CPT 'Horario' específico.
 */
function mph_ajax_eliminar_horario_callback()
{
    $log_prefix = "AJAX mph_eliminar_horario:";
    error_log("$log_prefix Petición AJAX recibida.");

    // --- 1. Obtener y Validar Datos ---
    $horario_id = isset($_POST["horario_id"])
        ? intval($_POST["horario_id"])
        : 0;
    $nonce = isset($_POST["nonce"]) ? sanitize_key($_POST["nonce"]) : "";

    if (empty($horario_id) || empty($nonce)) {
        error_log("$log_prefix Error: Faltan horario_id o nonce.");
        wp_send_json_error(
            [
                "message" => __(
                    "Datos insuficientes para eliminar.",
                    "mi-plugin-horarios"
                ),
            ],
            400
        );
        return;
    }
    error_log("$log_prefix Intentando eliminar Horario ID: $horario_id");

    // --- 2. Verificación de Seguridad ---
    // Verificar Nonce específico para este horario
    if (!wp_verify_nonce($nonce, "mph_eliminar_horario_" . $horario_id)) {
        error_log(
            "$log_prefix Error: Falló la verificación del Nonce específico."
        );
        wp_send_json_error(
            [
                "message" => __(
                    "Error de seguridad (Nonce inválido). Por favor, recarga la página.",
                    "mi-plugin-horarios"
                ),
            ],
            403
        );
        return;
    }

    // Verificar Permisos (usar la misma capacidad que para guardar)
    if (!current_user_can("edit_others_posts")) {
        error_log("$log_prefix Error: Permisos insuficientes.");
        wp_send_json_error(
            [
                "message" => __(
                    "No tienes permisos suficientes para eliminar horarios.",
                    "mi-plugin-horarios"
                ),
            ],
            403
        );
        return;
    }

    // --- 3. Comprobar si el Post Existe y es del Tipo Correcto (Opcional pero recomendado) ---
    $post_a_borrar = get_post($horario_id);
    if (!$post_a_borrar || $post_a_borrar->post_type !== "horario") {
        error_log(
            "$log_prefix Error: El post ID $horario_id no existe o no es un 'horario'."
        );
        wp_send_json_error(
            [
                "message" => __(
                    "El horario a eliminar no es válido.",
                    "mi-plugin-horarios"
                ),
            ],
            404
        ); // 404 Not Found
        return;
    }

    // --- 4. Ejecutar Borrado ---
    // El segundo parámetro 'true' fuerza el borrado permanente (sin pasar por la papelera)
    $delete_result = wp_delete_post($horario_id, true);

    if ($delete_result !== false && $delete_result !== null) {
        // Éxito (wp_delete_post devuelve el objeto WP_Post borrado en éxito, o false/null/WP_Error en fallo)
        error_log("$log_prefix Éxito: Horario ID $horario_id eliminado.");
        wp_send_json_success([
            "message" => __(
                "Horario eliminado con éxito.",
                "mi-plugin-horarios"
            ),
        ]);
    } else {
        // Fallo
        error_log(
            "$log_prefix Error: wp_delete_post falló para el ID $horario_id."
        );
        wp_send_json_error(
            [
                "message" => __(
                    "No se pudo eliminar el horario.",
                    "mi-plugin-horarios"
                ),
            ],
            500
        );
    }

    // wp_send_json_* termina la ejecución
}

/* Reescribir callback para Vaciar */
/**
 * Callback para la acción AJAX 'mph_vaciar_horario'.
 *
 * Convierte un post 'Horario' asignado/lleno en un estado 'Vacío'.
 * Utiliza una estrategia de "Actualización Inteligente" para recalcular y fusionar
 * el nuevo bloque 'Vacío' con cualquier bloque 'Vacío' adyacente.
 */
function mph_ajax_vaciar_horario_callback() {
    global $wpdb;
    $log_prefix = "AJAX mph_vaciar_horario (Inteligente):";
    error_log("$log_prefix Petición AJAX recibida.");

    // --- 1. Verificación de Seguridad y Permisos ---
    $horario_id = isset($_POST['horario_id']) ? intval($_POST['horario_id']) : 0;
    $nonce = isset($_POST['nonce']) ? sanitize_key($_POST['nonce']) : '';
    if ( empty($horario_id) || empty($nonce) ) { wp_send_json_error( array( 'message' => __('Datos insuficientes.') ), 400 ); return; }
    if ( ! wp_verify_nonce( $nonce, 'mph_vaciar_horario_' . $horario_id ) ) { wp_send_json_error( array( 'message' => __('Error de seguridad.') ), 403 ); return; }
    if ( ! current_user_can( 'edit_others_posts' ) ) { wp_send_json_error( array( 'message' => __('Sin permisos.') ), 403 ); return; }
    error_log("$log_prefix Verificaciones OK para Horario ID: $horario_id");

    // --- 2. Obtener Datos del Bloque a Vaciar ---
    $post_a_vaciar = get_post($horario_id);
    if (!$post_a_vaciar || $post_a_vaciar->post_type !== 'horario') { wp_send_json_error( array( 'message' => __('Horario a vaciar no válido.') ), 404 ); return; }

    $meta = get_post_meta($horario_id);
    $maestro_id = isset($meta['maestro_id'][0]) ? intval($meta['maestro_id'][0]) : 0;
    $dia_semana = isset($meta['mph_dia_semana'][0]) ? intval($meta['mph_dia_semana'][0]) : 0;
    // Obtener los admisibles ORIGINALES que se guardaron con el bloque asignado
    $programas_admisibles = isset($meta['mph_programas_admisibles'][0]) ? $meta['mph_programas_admisibles'][0] : '';
    $sedes_admisibles = isset($meta['mph_sedes_admisibles'][0]) ? $meta['mph_sedes_admisibles'][0] : '';
    $rangos_admisibles = isset($meta['mph_rangos_admisibles'][0]) ? $meta['mph_rangos_admisibles'][0] : '';

    if (empty($maestro_id) || empty($dia_semana)) { wp_send_json_error( array( 'message' => __('Datos del horario original incompletos.') ), 500 ); return; }


    // --- 3. Buscar Bloques Adyacentes para Fusión ---
    $horarios_existentes_db = mph_get_horarios_existentes_dia( $maestro_id, $dia_semana );
    $base_date = '1970-01-01 ';
    $dt_inicio_actual = new DateTime($base_date . get_post_meta($horario_id, 'mph_hora_inicio', true));
    $dt_fin_actual = new DateTime($base_date . get_post_meta($horario_id, 'mph_hora_fin', true));

    $ids_a_borrar_para_fusion = array($horario_id);
    $rango_fusionado_inicio = clone $dt_inicio_actual;
    $rango_fusionado_fin = clone $dt_fin_actual;

    // Buscar hacia atrás
    $fusion_continua = true;
    while ($fusion_continua) {
        $fusion_continua = false; // Asumir que no habrá más fusiones en esta pasada
        // Buscar hacia atrás
        foreach ($horarios_existentes_db as $key => $h_vecino) {
            if (in_array($h_vecino->ID, $ids_a_borrar_para_fusion)) continue; // Ya está en la lista

            $estado_vecino = get_post_meta($h_vecino->ID, 'mph_estado', true);
            if ($estado_vecino === 'Vacío' || $estado_vecino === 'Mismo' || $estado_vecino === 'Mismo o Traslado') {
                try {
                    $dt_fin_vecino = new DateTime($base_date . get_post_meta($h_vecino->ID, 'mph_hora_fin', true));
                    if ($dt_fin_vecino == $rango_fusionado_inicio) {
                        $rango_fusionado_inicio = new DateTime($base_date . get_post_meta($h_vecino->ID, 'mph_hora_inicio', true));
                        $ids_a_borrar_para_fusion[] = $h_vecino->ID;
                        error_log("$log_prefix Fusionando con bloque ANTERIOR ID: {$h_vecino->ID}. Nuevo inicio: " . $rango_fusionado_inicio->format('H:i'));
                        $fusion_continua = true; // Encontramos uno, así que repetimos el bucle while
                        unset($horarios_existentes_db[$key]); // Optimización: no re-evaluar este vecino
                        break; // Salir del foreach y reiniciar el while
                    }
                } catch (Exception $e) { continue; }
            }
        }
    } // Fin while para buscar hacia atrás
    
    // Buscar hacia adelante
     $fusion_continua = true;
    while ($fusion_continua) {
        $fusion_continua = false;
        // Buscar hacia adelante
        foreach ($horarios_existentes_db as $key => $h_vecino) {
            if (in_array($h_vecino->ID, $ids_a_borrar_para_fusion)) continue;

            $estado_vecino = get_post_meta($h_vecino->ID, 'mph_estado', true);
            if ($estado_vecino === 'Vacío' || $estado_vecino === 'Mismo' || $estado_vecino === 'Mismo o Traslado') {
                try {
                    $dt_inicio_vecino = new DateTime($base_date . get_post_meta($h_vecino->ID, 'mph_hora_inicio', true));
                    if ($dt_inicio_vecino == $rango_fusionado_fin) {
                        $rango_fusionado_fin = new DateTime($base_date . get_post_meta($h_vecino->ID, 'mph_hora_fin', true));
                        $ids_a_borrar_para_fusion[] = $h_vecino->ID;
                        error_log("$log_prefix Fusionando con bloque POSTERIOR ID: {$h_vecino->ID}. Nuevo fin: " . $rango_fusionado_fin->format('H:i'));
                        $fusion_continua = true;
                        unset($horarios_existentes_db[$key]);
                        break;
                    }
                } catch (Exception $e) { continue; }
            }
        }
    } // Fin while para buscar hacia adelante

    // --- 4. Ejecutar Operaciones de BD ---
    $wpdb->query('START TRANSACTION');
    $errores_operacion = array();

    // Borrar todos los bloques que se van a fusionar
    $posts_borrados_ids = array();
    foreach (array_unique($ids_a_borrar_para_fusion) as $id_a_borrar) {
        $delete_result = wp_delete_post($id_a_borrar, true);
        if ($delete_result) $posts_borrados_ids[] = $id_a_borrar;
        else $errores_operacion[] = "Error borrando ID $id_a_borrar para fusión.";
    }
     error_log("$log_prefix Borrados " . count($posts_borrados_ids) . " bloques para fusión.");


    // Crear el nuevo bloque 'Vacío' fusionado
    $hora_inicio_fusionada = $rango_fusionado_inicio->format('H:i');
    $hora_fin_fusionada = $rango_fusionado_fin->format('H:i');
    $titulo_nuevo = sprintf("Maestro %d - Día %d - %s-%s - %s", $maestro_id, $dia_semana, $hora_inicio_fusionada, $hora_fin_fusionada, 'Vacío');

    $post_data = array(
        'post_type'    => 'horario',
        'post_title'   => sanitize_text_field($titulo_nuevo),
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
        'meta_input'   => array(
            'maestro_id'                 => $maestro_id,
            'mph_dia_semana'             => $dia_semana,
            'mph_hora_inicio'            => $hora_inicio_fusionada,
            'mph_hora_fin'               => $hora_fin_fusionada,
            'mph_estado'                 => 'Vacío',
            'mph_programas_admisibles'   => $programas_admisibles,
            'mph_sedes_admisibles'       => $sedes_admisibles,
            'mph_rangos_admisibles'      => $rangos_admisibles,
            'mph_vacantes'               => 0, 'mph_programa_asignado' => 0, 'mph_sede_asignada' => 0,
            'mph_rango_de_edad_asignado' => 0, 'mph_buffer_antes' => 0, 'mph_buffer_despues' => 0, 'mph_sede_adyacente' => 0,
        ),
    );
    $new_post_id = wp_insert_post( $post_data, true );
    if ( is_wp_error( $new_post_id ) ) {
        $errores_operacion[] = "Error creando nuevo bloque Vacío: " . $new_post_id->get_error_message();
    }
     error_log("$log_prefix Creado nuevo bloque Vacío fusionado ID: " . (is_wp_error($new_post_id) ? 'ERROR' : $new_post_id));

    // --- 5. Enviar Respuesta ---
    if (empty($errores_operacion)) {
        $wpdb->query('COMMIT');
        error_log("$log_prefix Éxito: Horario vaciado y fusionado.");
        $html_tabla = '';
        if ( $maestro_id && function_exists( 'mph_get_horarios_table_html' ) ) {
             $html_tabla = mph_get_horarios_table_html( $maestro_id );
        }
        wp_send_json_success( array( 'message' => __('Horario vaciado con éxito.', 'mi-plugin-horarios'), 'html_tabla' => $html_tabla ));
    } else {
         $wpdb->query('ROLLBACK');
         error_log("$log_prefix Fallo al vaciar/fusionar: " . implode('; ', $errores_operacion));
         wp_send_json_error( array( 'message' => __('Error al vaciar el horario.', 'mi-plugin-horarios') ), 500 );
    }
}

/* Inicia Modificación: AÑADIR ESTA FUNCIÓN COMPLETA */
/**
 * Callback para la acción AJAX 'mph_actualizar_vacantes'.
 * Actualiza únicamente el número de vacantes de un post 'Horario' existente.
 */
function mph_ajax_actualizar_vacantes_callback()
{
    $log_prefix = "AJAX mph_actualizar_vacantes:";
    error_log("$log_prefix Petición AJAX recibida.");
    error_log("$log_prefix Datos POST recibidos: " . print_r($_POST, true));

    // 1. Verificación Nonce
    $nonce_action = "mph_actualizar_vacantes_action"; // Acción específica para este callback
    $nonce_name = "mph_nonce_actualizar_vacantes"; // Nombre del campo nonce específico
    if (
        !isset($_POST[$nonce_name]) ||
        !wp_verify_nonce(sanitize_key($_POST[$nonce_name]), $nonce_action)
    ) {
        error_log(
            "$log_prefix Error: Falló Nonce (Acción: $nonce_action). Recibido: " .
                sanitize_key($_POST[$nonce_name] ?? "No recibido")
        );
        wp_send_json_error(
            ["message" => __("Error de seguridad.", "mi-plugin-horarios")],
            403
        );
        return;
    }
    error_log("$log_prefix Nonce verificado.");

    // 2. Permisos
    if (!current_user_can("edit_others_posts")) {
        error_log("$log_prefix Error: Permisos insuficientes.");
        wp_send_json_error(/*...*/ 403);
        return;
    }
    error_log("$log_prefix Permisos verificados.");

    // 3. Obtener y Sanitizar Datos
    $horario_id = isset($_POST["horario_id"])
        ? intval($_POST["horario_id"])
        : 0;
    $vacantes = isset($_POST["vacantes"]) ? intval($_POST["vacantes"]) : -1; // Permitir 0
    error_log(
        "$log_prefix Datos recibidos - Horario ID: $horario_id, Vacantes: $vacantes"
    );

    if (empty($horario_id) || $vacantes < 0) {
        // Vacantes debe ser >= 0
        error_log(
            "$log_prefix Error: Validación fallida - Faltan horario_id o vacantes inválidas ($vacantes)."
        );
        wp_send_json_error(
            [
                "message" => __(
                    "Datos insuficientes o inválidos.",
                    "mi-plugin-horarios"
                ),
            ],
            400
        );
        return;
    }

    // 4. Comprobar Post
    $post_a_actualizar = get_post($horario_id);
    if (!$post_a_actualizar || $post_a_actualizar->post_type !== "horario") {
        error_log("$log_prefix Error: Post ID $horario_id no válido.");
        wp_send_json_error(
            [
                "message" => __(
                    "El horario a actualizar no es válido.",
                    "mi-plugin-horarios"
                ),
            ],
            404
        );
        return;
    }
    error_log("$log_prefix Post $horario_id encontrado y válido.");
    $estado_actual = get_post_meta($horario_id, "mph_estado", true);
    error_log("$log_prefix Estado actual: $estado_actual");

    // 5. Actualizar Meta y Estado
    error_log("$log_prefix Actualizando meta 'mph_vacantes' a $vacantes...");
    $meta_update_result = update_post_meta(
        $horario_id,
        "mph_vacantes",
        $vacantes
    );
    error_log(
        "$log_prefix Resultado update_post_meta(mph_vacantes): " .
            ($meta_update_result !== false ? "Éxito/Igual" : "Fallo")
    ); // Comparar con false

    $nuevo_estado = $estado_actual;
    $estado_update_result = true;
    if ($vacantes === 0 && $estado_actual !== "Lleno") {
        $nuevo_estado = "Lleno";
        error_log("$log_prefix Actualizando meta 'mph_estado' a 'Lleno'...");
        $estado_update_result = update_post_meta(
            $horario_id,
            "mph_estado",
            "Lleno"
        );
        error_log(
            "$log_prefix Resultado update_post_meta(mph_estado): " .
                ($estado_update_result !== false ? "Éxito/Igual" : "Fallo")
        );
    } elseif ($vacantes > 0 && $estado_actual === "Lleno") {
        $nuevo_estado = "Asignado";
        error_log("$log_prefix Actualizando meta 'mph_estado' a 'Asignado'...");
        $estado_update_result = update_post_meta(
            $horario_id,
            "mph_estado",
            "Asignado"
        );
        error_log(
            "$log_prefix Resultado update_post_meta(mph_estado): " .
                ($estado_update_result !== false ? "Éxito/Igual" : "Fallo")
        );
    }

    // 6. Enviar Respuesta
    if ($meta_update_result !== false && $estado_update_result !== false) {
        error_log(
            "$log_prefix Éxito final: Vacantes actualizadas para Horario ID: $horario_id"
        );
        $html_tabla = "";
        $maestro_id = get_post_meta($horario_id, "maestro_id", true);
        if ($maestro_id && function_exists("mph_get_horarios_table_html")) {
            $html_tabla = mph_get_horarios_table_html($maestro_id);
        }
        wp_send_json_success([
            "message" => __("Vacantes actualizadas.", "mi-plugin-horarios"),
            "html_tabla" => $html_tabla,
        ]);
    } else {
        error_log(
            "$log_prefix Fallo final: No se pudieron actualizar los metas para Horario ID: $horario_id"
        );
        wp_send_json_error(
            [
                "message" => __(
                    "Error al actualizar vacantes/estado.",
                    "mi-plugin-horarios"
                ),
            ],
            500
        );
    }
}

/* Nueva función auxiliar para comparar metas */
/**
 * Compara los metadatos esenciales de un post existente con un array de nuevos metadatos.
 *
 * @param int   $post_id      ID del post existente.
 * @param array $nuevas_metas Array de metadatos ['meta_key' => 'valor'] del bloque nuevo.
 * @return bool True si son diferentes, false si son iguales.
 */
function mph_bloques_son_diferentes_en_meta($post_id, $nuevas_metas) {
    // Comparar estado
    if (get_post_meta($post_id, 'mph_estado', true) !== $nuevas_metas['mph_estado']) return true;
    // Comparar admisibles (son strings CSV)
    if (get_post_meta($post_id, 'mph_programas_admisibles', true) !== $nuevas_metas['mph_programas_admisibles']) return true;
    if (get_post_meta($post_id, 'mph_sedes_admisibles', true) !== $nuevas_metas['mph_sedes_admisibles']) return true;
    if (get_post_meta($post_id, 'mph_rangos_admisibles', true) !== $nuevas_metas['mph_rangos_admisibles']) return true;
    // Comparar asignados
    if ((int)get_post_meta($post_id, 'mph_programa_asignado', true) !== $nuevas_metas['mph_programa_asignado']) return true;
    if ((int)get_post_meta($post_id, 'mph_sede_asignada', true) !== $nuevas_metas['mph_sede_asignada']) return true;
    if ((int)get_post_meta($post_id, 'mph_rango_de_edad_asignado', true) !== $nuevas_metas['mph_rango_de_edad_asignado']) return true;
    if ((int)get_post_meta($post_id, 'mph_vacantes', true) !== $nuevas_metas['mph_vacantes']) return true;
    // Añadir mph_sede_adyacente y buffers si son relevantes para la "identidad" de un bloque no asignado
    if ((int)get_post_meta($post_id, 'mph_sede_adyacente', true) !== $nuevas_metas['mph_sede_adyacente']) return true;

    return false; // Son iguales
}