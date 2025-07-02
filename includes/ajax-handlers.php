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

function mph_ajax_guardar_horario_maestro_callback() {
    global $wpdb;
    $log_prefix = "AJAX mph_guardar_horario_maestro (Inteligente V3):"; // Actualizar versión de log
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

    $sanitized_data = array(); // Array para datos limpios
    // Llenar $sanitized_data con todos los campos POST sanitizados...
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

    // --- 2. Calcular Bloques Nuevos/Ideales ---
    $bloques_calculados_nuevos = mph_calcular_bloques_horario( $sanitized_data['maestro_id'], $sanitized_data );
    if ( is_wp_error( $bloques_calculados_nuevos ) ) { wp_send_json_error( array( 'message' => $bloques_calculados_nuevos->get_error_message() ), 400 ); return; }
    error_log("$log_prefix " . count($bloques_calculados_nuevos) . " bloques 'nuevos' calculados (estado ideal).");

    $wpdb->query('START TRANSACTION');
    $errores_operacion = array();
    $posts_creados_ids = array();
    $posts_actualizados_ids = array();
    $posts_borrados_ids = array();
    $base_date = '1970-01-01 '; // Para comparaciones de DateTime si es necesario

    // --- 3. Obtener TODOS los horarios existentes del maestro para ESE DÍA ---
    $horarios_existentes_db_dia = mph_get_horarios_existentes_dia( $sanitized_data['maestro_id'], $sanitized_data['dia_semana'] );
    error_log("$log_prefix Encontrados " . count($horarios_existentes_db_dia) . " horarios existentes en BD para el día.");

    /* Lógica de Actualización Inteligente V3 */

    // A. Mapear Nuevos Bloques Calculados por "INICIO-FIN" para búsqueda eficiente
    $mapa_nuevos_por_intervalo = array();
    foreach ($bloques_calculados_nuevos as $b_nuevo) {
        $mapa_nuevos_por_intervalo[$b_nuevo['hora_inicio'] . '-' . $b_nuevo['hora_fin']] = $b_nuevo;
    }

    // B. Iterar sobre Bloques Existentes: Decidir si Actualizar o Borrar
    $ids_existentes_que_se_mantienen_o_actualizan = array();

    foreach ($horarios_existentes_db_dia as $h_existente) {
        $id_existente = $h_existente->ID;
        $inicio_existente_str = get_post_meta($id_existente, 'mph_hora_inicio', true);
        $fin_existente_str = get_post_meta($id_existente, 'mph_hora_fin', true);
        if (!$inicio_existente_str || !$fin_existente_str) continue;

        $intervalo_existente_key = $inicio_existente_str . '-' . $fin_existente_str;

        if (isset($mapa_nuevos_por_intervalo[$intervalo_existente_key])) {
            // Hay un NUEVO bloque calculado que tiene EXACTAMENTE las mismas horas.
            $b_nuevo_coincidente = $mapa_nuevos_por_intervalo[$intervalo_existente_key];
            error_log("$log_prefix Existente ID $id_existente ($intervalo_existente_key) tiene un nuevo bloque coincidente en tiempo.");

            if (mph_bloques_son_diferentes_en_meta($id_existente, $b_nuevo_coincidente['meta_input'])) {
                error_log("$log_prefix -> Metas diferentes. ACTUALIZANDO post ID $id_existente. Nuevo estado: {$b_nuevo_coincidente['estado']}.");
                $update_args = array(
                    'ID' => $id_existente,
                    'post_title' => sanitize_text_field($b_nuevo_coincidente['post_title']),
                    'meta_input' => $b_nuevo_coincidente['meta_input'], // Contiene todos los metas mph_*
                );
                $update_result = wp_update_post($update_args, true); // true para WP_Error
                if (is_wp_error($update_result)) {
                    $errores_operacion[] = "Error actualizando ID $id_existente: " . $update_result->get_error_message();
                } else {
                    $posts_actualizados_ids[] = $id_existente;
                }
            } else {
                error_log("$log_prefix -> Metas idénticas. Bloque existente ID $id_existente se MANTIENE sin cambios.");
            }
            $ids_existentes_que_se_mantienen_o_actualizan[$id_existente] = true;
            // Marcar este nuevo como "manejado" para no insertarlo después
            $mapa_nuevos_por_intervalo[$intervalo_existente_key]['manejado_inteligentemente'] = true;
        }
        // Si no hay un nuevo bloque con el mismo tiempo exacto, el existente será borrado en el siguiente paso si no se marcó para mantener.
    }

    // C. Borrar Bloques Existentes que NO fueron Mantenidos ni Actualizados
    // (Es decir, aquellos que no encontraron un nuevo bloque con su mismo horario exacto,
    // o aquellos que se solapan con el rango de la operación de forma que deben ser reemplazados)
    // La lógica actual de $mapa_nuevos_por_intervalo y $ids_existentes_que_se_mantienen_o_actualizan
    // ya identifica qué actualizar/mantener. Los que no están en $ids_existentes_que_se_mantienen_o_actualizan
    // y SÍ ESTÁN EN EL RANGO DE LA OPERACIÓN son candidatos a borrado.
    // Para simplificar: Borraremos los que NO están en $ids_existentes_que_se_mantienen_o_actualizan
    // Y que además se solapen con el rango general de la operación.
    // Esta es la parte más delicada de la "actualización inteligente" sin una comparación intervalo a intervalo.

    // Si $id_bloque_original_si_edita se pasó, y no está en $ids_existentes_que_se_mantienen_o_actualizan,
    // significa que su tiempo cambió tanto que se considera un borrado y creación.
    if ($id_bloque_original_si_edita > 0 && !isset($ids_existentes_que_se_mantienen_o_actualizan[$id_bloque_original_si_edita])) {
         error_log("$log_prefix ID Original $id_bloque_original_si_edita no fue mantenido/actualizado (tiempo cambió drásticamente). BORRANDO.");
         if (get_post_status($id_bloque_original_si_edita)) { // Comprobar si aún existe
            $delete_result = wp_delete_post($id_bloque_original_si_edita, true);
            if ($delete_result) $posts_borrados_ids[] = $id_bloque_original_si_edita; else $errores_operacion[] = "Error borrando ID original $id_bloque_original_si_edita";
         }
    }

    // Iterar de nuevo sobre los existentes para borrar los que no fueron marcados y se solapan con la operación
    // Esto es para limpiar fragmentos que quedan si un bloque grande se reemplaza por uno más pequeño.
    // Definir rango de operación con DateTime
    $dt_op_inicio_obj = new DateTime($base_date . $sanitized_data['hora_inicio_general']);
    $dt_op_fin_obj = new DateTime($base_date . $sanitized_data['hora_fin_general']);

    foreach ($horarios_existentes_db_dia as $h_existente) {
        $id_existente = $h_existente->ID;
        if (isset($ids_existentes_que_se_mantienen_o_actualizan[$id_existente])) {
            continue; // Ya manejado (se mantiene o actualizó)
        }
        // Si ya fue borrado (porque era el original), no intentar de nuevo.
        if (in_array($id_existente, $posts_borrados_ids)) {
            continue;
        }

        $inicio_existente_str = get_post_meta($id_existente, 'mph_hora_inicio', true);
        $fin_existente_str = get_post_meta($id_existente, 'mph_hora_fin', true);
        if (!$inicio_existente_str || !$fin_existente_str) continue;

        try {
            $dt_existente_inicio_obj = new DateTime($base_date . $inicio_existente_str);
            $dt_existente_fin_obj = new DateTime($base_date . $fin_existente_str);

            // ¿Este bloque existente (no mantenido/actualizado) se solapa con el rango de operación?
            $se_solapa_con_operacion = ($dt_existente_inicio_obj < $dt_op_fin_obj && $dt_existente_fin_obj > $dt_op_inicio_obj);

            if ($se_solapa_con_operacion) {
                 error_log("$log_prefix Bloque existente ID $id_existente ($inicio_existente_str-$fin_existente_str) no mantenido y solapa operación. BORRANDO.");
                 $delete_result = wp_delete_post($id_existente, true);
                 if ($delete_result) $posts_borrados_ids[] = $id_existente; else $errores_operacion[] = "Error borrando solapado $id_existente";
            }
        } catch (Exception $e) { continue; }
    }
    $posts_borrados_ids = array_unique($posts_borrados_ids);


    // D. Insertar los Nuevos Bloques Calculados que NO fueron "manejados_inteligentemente"
    foreach ($mapa_nuevos_por_intervalo as $interval_key => $b_nuevo) {
        if (empty($b_nuevo['manejado_inteligentemente'])) {
            error_log("$log_prefix Insertando nuevo bloque (no coincidió para update): " . $b_nuevo['post_title']);
            $post_data = array(
                'post_type'    => 'horario',
                'post_title'   => sanitize_text_field( $b_nuevo['post_title'] ),
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
                'meta_input'   => $b_nuevo['meta_input'],
            );
            $new_post_id = wp_insert_post( $post_data, true );
            if (is_wp_error($new_post_id)) $errores_operacion[] = $new_post_id->get_error_message();
            else $posts_creados_ids[] = $new_post_id;
        }
    }

    // --- 6. Finalizar Transacción y Enviar Respuesta ---
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

} // Fin de mph_ajax_guardar_horario_maestro_callback


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

/**
 * Callback para la acción AJAX 'mph_vaciar_horario'.
 *
 * Convierte un post 'Horario' asignado/lleno en un estado 'Vacío',
 * manteniendo sus horas y admisibles originales.
 */
function mph_ajax_vaciar_horario_callback()
{
    $log_prefix = "AJAX mph_vaciar_horario:";
    error_log("$log_prefix Petición AJAX recibida.");

    // 1. Obtener y Validar Datos + Nonce
    $horario_id = isset($_POST["horario_id"])
        ? intval($_POST["horario_id"])
        : 0;
    $nonce = isset($_POST["nonce"]) ? sanitize_key($_POST["nonce"]) : "";
    if (empty($horario_id) || empty($nonce)) {
        error_log("$log_prefix Error: Faltan horario_id o nonce para vaciar.");
        wp_send_json_error(
            [
                "message" => __(
                    "Datos insuficientes para vaciar.",
                    "mi-plugin-horarios"
                ),
            ],
            400
        );
        return;
    }
    error_log("$log_prefix Intentando vaciar Horario ID: $horario_id");

    // 2. Verificar Nonce específico para vaciar este ID
    if (!wp_verify_nonce($nonce, "mph_vaciar_horario_" . $horario_id)) {
        error_log("$log_prefix Error: Falló Nonce específico para vaciar.");
        wp_send_json_error(
            [
                "message" => __(
                    "Error de seguridad (Vaciar).",
                    "mi-plugin-horarios"
                ),
            ],
            403
        );
        return;
    }
    error_log("$log_prefix Nonce para vaciar verificado.");

    // 3. Verificar Permisos
    if (!current_user_can("edit_others_posts")) {
        error_log("$log_prefix Error: Permisos insuficientes para vaciar.");
        wp_send_json_error(
            [
                "message" => __(
                    "No tienes permisos para vaciar horarios.",
                    "mi-plugin-horarios"
                ),
            ],
            403
        );
        return;
    }

    // 4. Obtener Post y Metadatos Actuales
    $post_a_vaciar = get_post($horario_id);
    if (!$post_a_vaciar || $post_a_vaciar->post_type !== "horario") {
        error_log(
            "$log_prefix Error: El post ID $horario_id no existe o no es un 'horario' para vaciar."
        );
        wp_send_json_error(
            [
                "message" => __(
                    "El horario a vaciar no es válido.",
                    "mi-plugin-horarios"
                ),
            ],
            404
        );
        return;
    }
    $meta = get_post_meta($horario_id); // Obtener todos los meta

    // Extraer datos necesarios
    $maestro_id = isset($meta["maestro_id"][0])
        ? intval($meta["maestro_id"][0])
        : 0;
    $dia_semana = isset($meta["mph_dia_semana"][0])
        ? intval($meta["mph_dia_semana"][0])
        : 0;
    $hora_inicio = isset($meta["mph_hora_inicio"][0])
        ? $meta["mph_hora_inicio"][0]
        : "";
    $hora_fin = isset($meta["mph_hora_fin"][0]) ? $meta["mph_hora_fin"][0] : "";
    // ¡IMPORTANTE! Necesitamos los admisibles ORIGINALES. Asumimos que se guardaron correctamente antes.
    $programas_admisibles = isset($meta["mph_programas_admisibles"][0])
        ? $meta["mph_programas_admisibles"][0]
        : "";
    $sedes_admisibles = isset($meta["mph_sedes_admisibles"][0])
        ? $meta["mph_sedes_admisibles"][0]
        : "";
    $rangos_admisibles = isset($meta["mph_rangos_admisibles"][0])
        ? $meta["mph_rangos_admisibles"][0]
        : "";

    if (
        empty($maestro_id) ||
        empty($dia_semana) ||
        empty($hora_inicio) ||
        empty($hora_fin)
    ) {
        error_log(
            "$log_prefix Error: No se pudieron obtener datos esenciales del post $horario_id para vaciar."
        );
        wp_send_json_error(
            [
                "message" => __(
                    "Error al leer datos del horario original.",
                    "mi-plugin-horarios"
                ),
            ],
            500
        );
        return;
    }

    // 5. Preparar Datos para Actualizar a 'Vacío'
    $nuevo_estado = "Vacío";
    $nuevo_titulo = sprintf(
        "Maestro %d - Día %d - %s-%s - %s",
        $maestro_id,
        $dia_semana,
        $hora_inicio,
        $hora_fin,
        $nuevo_estado
    );

    $update_post_args = [
        "ID" => $horario_id,
        "post_title" => $nuevo_titulo,
        "meta_input" => [
            "mph_estado" => $nuevo_estado,
            // Mantener maestro, día, horas, admisibles
            "maestro_id" => $maestro_id,
            "mph_dia_semana" => $dia_semana,
            "mph_hora_inicio" => $hora_inicio,
            "mph_hora_fin" => $hora_fin,
            "mph_programas_admisibles" => $programas_admisibles,
            "mph_sedes_admisibles" => $sedes_admisibles,
            "mph_rangos_admisibles" => $rangos_admisibles,
            // Resetear campos de asignación
            "mph_vacantes" => 0,
            "mph_programa_asignado" => 0,
            "mph_sede_asignada" => 0,
            "mph_rango_de_edad_asignado" => 0,
            "mph_buffer_antes" => 0,
            "mph_buffer_despues" => 0,
        ],
    ];

    // 6. Actualizar el Post
    $update_result = wp_update_post($update_post_args, true); // true = devolver WP_Error

    // 7. Enviar Respuesta
    if (is_wp_error($update_result)) {
        error_log(
            "$log_prefix Error al actualizar post $horario_id a Vacío: " .
                $update_result->get_error_message()
        );
        wp_send_json_error(
            [
                "message" => __(
                    "Error al vaciar el horario.",
                    "mi-plugin-horarios"
                ),
            ],
            500
        );
    } else {
        error_log("$log_prefix Éxito: Horario ID $horario_id vaciado.");
        // Devolver tabla actualizada
        $html_tabla = "";
        if ($maestro_id && function_exists("mph_get_horarios_table_html")) {
            $html_tabla = mph_get_horarios_table_html($maestro_id);
        }
        wp_send_json_success([
            "message" => __("Horario vaciado con éxito.", "mi-plugin-horarios"),
            "html_tabla" => $html_tabla,
        ]);
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