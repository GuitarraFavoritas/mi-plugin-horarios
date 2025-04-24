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
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registra las acciones AJAX de WordPress.
 *
 * Engancha nuestras funciones PHP a los hooks AJAX correspondientes.
 * 'wp_ajax_{action}' para usuarios logueados.
 * 'wp_ajax_nopriv_{action}' para usuarios no logueados (si fuera necesario).
 */
function mph_register_ajax_actions() {
    // Acción para guardar/actualizar horario desde el modal del maestro
    add_action( 'wp_ajax_mph_guardar_horario_maestro', 'mph_ajax_guardar_horario_maestro_callback' );

    // Aquí podríamos añadir otras acciones AJAX en el futuro (ej. eliminar, filtrar frontend)
    // add_action( 'wp_ajax_mph_eliminar_horario', 'mph_ajax_eliminar_horario_callback' );
    // add_action( 'wp_ajax_nopriv_mph_filtrar_horarios', 'mph_ajax_filtrar_horarios_callback' ); // Ejemplo no logueado
}
add_action( 'init', 'mph_register_ajax_actions' ); // Registrar las acciones al inicio


/**
 * Callback para la acción AJAX 'mph_guardar_horario_maestro'.
 *
 * Procesa los datos enviados desde el modal de gestión de horarios,
 * calcula los bloques resultantes, crea/actualiza los posts CPT 'Horario',
 * y devuelve una respuesta JSON.
 */
function mph_ajax_guardar_horario_maestro_callback() {
    $log_prefix = "AJAX mph_guardar_horario_maestro:";
    error_log("$log_prefix Petición AJAX recibida.");

    // --- 1. Verificación de Seguridad ---
    // Verificar Nonce (Enviado desde JS como 'nonce')
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key($_POST['nonce']), 'mph_gestionar_horarios_nonce' ) ) {
        error_log("$log_prefix Error: Falló la verificación del Nonce.");
        wp_send_json_error( array( 'message' => __( 'Error de seguridad (Nonce inválido). Por favor, recarga la página e inténtalo de nuevo.', 'mi-plugin-horarios' ) ), 403 ); // 403 Forbidden
        return; // Salir
    }

    // Verificar Permisos del Usuario
    // ¿Qué capacidad se necesita para gestionar horarios de maestros?
    // Podría ser 'edit_posts', o una capacidad personalizada si la definimos.
    // Usaremos 'edit_others_posts' como ejemplo restrictivo (solo roles altos), ajusta según necesidad.
    if ( ! current_user_can( 'edit_others_posts' ) ) {
         error_log("$log_prefix Error: Permisos insuficientes.");
         wp_send_json_error( array( 'message' => __( 'No tienes permisos suficientes para realizar esta acción.', 'mi-plugin-horarios' ) ), 403 );
         return; // Salir
    }

    // --- 2. Obtención y Sanitización de Datos POST ---
    error_log("$log_prefix Datos POST recibidos: " . print_r($_POST, true));

    // Recoger y sanitizar cada dato esperado del formulario
    // (Usar sanitize_text_field, intval, array_map con intval, etc.)
    $sanitized_data = array();
    $sanitized_data['maestro_id'] = isset($_POST['maestro_id']) ? intval($_POST['maestro_id']) : 0;
    $sanitized_data['dia_semana'] = isset($_POST['dia_semana']) ? intval($_POST['dia_semana']) : 0;

    // Horas (sanitizar, validación de formato ya hecha en JS y se hará en calculator)
    $sanitized_data['hora_inicio_general'] = isset($_POST['hora_inicio_general']) ? sanitize_text_field($_POST['hora_inicio_general']) : '';
    $sanitized_data['hora_fin_general'] = isset($_POST['hora_fin_general']) ? sanitize_text_field($_POST['hora_fin_general']) : '';
    $sanitized_data['hora_inicio_asignada'] = isset($_POST['hora_inicio_asignada']) ? sanitize_text_field($_POST['hora_inicio_asignada']) : '';
    $sanitized_data['hora_fin_asignada'] = isset($_POST['hora_fin_asignada']) ? sanitize_text_field($_POST['hora_fin_asignada']) : '';

    // Taxonomías Admisibles (esperamos arrays de IDs)
    $sanitized_data['programa_admisibles'] = isset($_POST['programa_admisibles']) ? array_map('intval', (array)$_POST['programa_admisibles']) : array();
    $sanitized_data['sede_admisibles'] = isset($_POST['sede_admisibles']) ? array_map('intval', (array)$_POST['sede_admisibles']) : array();
    $sanitized_data['rango_de_edad_admisibles'] = isset($_POST['rango_edad_admisibles']) ? array_map('intval', (array)$_POST['rango_edad_admisibles']) : array(); // Ojo al name 'rango_edad_admisibles[]'

    // Datos de Asignación (enteros)
    $sanitized_data['programa_asignado'] = isset($_POST['programa_asignado']) ? intval($_POST['programa_asignado']) : 0;
    $sanitized_data['sede_asignada'] = isset($_POST['sede_asignada']) ? intval($_POST['sede_asignada']) : 0;
    $sanitized_data['rango_de_edad_asignado'] = isset($_POST['rango_edad_asignado']) ? intval($_POST['rango_edad_asignado']) : 0;
    $sanitized_data['vacantes'] = isset($_POST['vacantes']) ? intval($_POST['vacantes']) : 0;
    $sanitized_data['buffer_minutos_antes'] = isset($_POST['buffer_minutos_antes']) ? intval($_POST['buffer_minutos_antes']) : 0;
    $sanitized_data['buffer_minutos_despues'] = isset($_POST['buffer_minutos_despues']) ? intval($_POST['buffer_minutos_despues']) : 0;

    // ID del horario que se está editando (si aplica)
    $horario_id_editando = isset($_POST['horario_id']) ? intval($_POST['horario_id']) : 0;
     error_log("$log_prefix Editando Horario ID: $horario_id_editando");


    // Validar datos sanitizados básicos
    if ( empty($sanitized_data['maestro_id']) || empty($sanitized_data['dia_semana']) || empty($sanitized_data['hora_inicio_general']) || empty($sanitized_data['hora_fin_general']) ) {
         error_log("$log_prefix Error: Datos sanitizados insuficientes.");
         wp_send_json_error( array( 'message' => __( 'Faltan datos requeridos o son inválidos.', 'mi-plugin-horarios' ) ), 400 ); // 400 Bad Request
         return;
    }
    error_log("$log_prefix Datos sanitizados listos: " . print_r($sanitized_data, true));


    // --- 3. Lógica de Negocio: Calcular Bloques ---
    // Llamar a la función central de cálculo que definimos en calculator.php
    error_log("$log_prefix Llamando a mph_calcular_bloques_horario...");
    $bloques_calculados = mph_calcular_bloques_horario( $sanitized_data['maestro_id'], $sanitized_data );

    // Manejar posible WP_Error devuelto por la función de cálculo
    if ( is_wp_error( $bloques_calculados ) ) {
        error_log("$log_prefix Error devuelto por mph_calcular_bloques_horario: " . $bloques_calculados->get_error_message());
        wp_send_json_error( array( 'message' => $bloques_calculados->get_error_message() ), 400 );
        return;
    }

    if ( empty( $bloques_calculados ) ) {
         error_log("$log_prefix mph_calcular_bloques_horario no devolvió bloques.");
         wp_send_json_error( array( 'message' => __( 'No se pudieron calcular los bloques de horario.', 'mi-plugin-horarios' ) ), 500 ); // 500 Internal Server Error
         return;
    }
    error_log("$log_prefix Bloques calculados (" . count($bloques_calculados) . "): " . print_r($bloques_calculados, true));


    // --- 4. Persistencia: Crear/Actualizar Posts CPT 'Horario' ---
    // Aquí viene la lógica para interactuar con la base de datos.

    // **Estrategia:**
    // 1. Obtener TODOS los horarios existentes para ese maestro y día ANTES de hacer cambios.
    // 2. Comparar los bloques calculados con los existentes.
    // 3. Identificar qué bloques existentes necesitan ser BORRADOS (porque ya no aplican o serán reemplazados).
    // 4. Identificar qué bloques existentes necesitan ser ACTUALIZADOS (si un bloque calculado coincide en tiempo con uno existente pero cambia estado/meta).
    // 5. Identificar qué bloques calculados son NUEVOS y necesitan ser CREADOS.
    // Esta estrategia de comparación/actualización es compleja.

    // **Estrategia más simple (pero potencialmente menos eficiente para muchas ediciones):**
    // 1. BORRAR todos los horarios existentes del maestro para el rango de tiempo general afectado por esta entrada.
    // 2. CREAR nuevos posts 'Horario' para cada uno de los $bloques_calculados.
    // Adoptaremos esta estrategia más simple por ahora.

    // --- 4.a. Borrar Horarios Existentes en el Rango General ---
    error_log("$log_prefix Intentando borrar horarios existentes en el rango general...");
    $args_delete = array(
        'post_type'      => 'horario',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array( 'key' => 'maestro_id', 'value' => $sanitized_data['maestro_id'], 'compare' => '=', 'type' => 'NUMERIC' ),
            array( 'key' => 'mph_dia_semana', 'value' => $sanitized_data['dia_semana'], 'compare' => '=', 'type' => 'NUMERIC' ),
            // Seleccionar posts cuyas horas se solapen con el rango general CUBIERTO por esta entrada
            // Esta query de solapamiento de tiempo con meta es compleja. Simplificamos:
            // Borramos todos los del día y maestro que caigan *dentro* del inicio/fin general.
            // ¡PRECAUCIÓN! Esto podría borrar más de lo necesario si los rangos se solapan de formas complejas.
            // Una alternativa sería borrar solo si el horario_id_editando se proporcionó.
            // O borrar TODOS los del día para este maestro y recrear. Vamos con esto último por simplicidad ahora.
        ),
        'fields' => 'ids' // Obtener solo IDs para borrar
    );
     // TODO: Refinar la query para borrar solo los posts estrictamente reemplazados por los nuevos bloques.
     // Por ahora, borramos TODOS los del día para este maestro para asegurar limpieza.
     // ¡¡ESTO ES DRÁSTICO!! Considera una estrategia de actualización si esto causa problemas.
    $horarios_a_borrar_query = new WP_Query( $args_delete );
    $horarios_a_borrar_ids = $horarios_a_borrar_query->posts;

    $borrados_count = 0;
    if ( !empty($horarios_a_borrar_ids) ) {
        error_log("$log_prefix Encontrados " . count($horarios_a_borrar_ids) . " horarios existentes para borrar.");
        foreach ( $horarios_a_borrar_ids as $horario_id ) {
            $delete_result = wp_delete_post( $horario_id, true ); // true = forzar borrado (sin papelera)
            if ($delete_result) {
                $borrados_count++;
            } else {
                error_log("$log_prefix Error al borrar Horario ID: $horario_id");
            }
        }
        error_log("$log_prefix Borrados $borrados_count horarios existentes.");
    } else {
        error_log("$log_prefix No se encontraron horarios existentes para borrar.");
    }


    // --- 4.b. Crear Nuevos Posts Horario ---
    $creados_count = 0;
    $errores_creacion = array();

    foreach ( $bloques_calculados as $bloque ) {
        error_log("$log_prefix Intentando crear post para bloque: " . $bloque['post_title']);
        $post_data = array(
            'post_type'    => 'horario',
            'post_title'   => sanitize_text_field( $bloque['post_title'] ),
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(), // Asignar al usuario actual
            'meta_input'   => $bloque['meta_input'], // Array de metadatos ya preparado
        );

        $new_post_id = wp_insert_post( $post_data, true ); // true = devolver WP_Error si falla

        if ( is_wp_error( $new_post_id ) ) {
            $error_message = $new_post_id->get_error_message();
            error_log("$log_prefix Error al crear post para bloque '" . $bloque['post_title'] . "': " . $error_message);
            $errores_creacion[] = $error_message;
        } else {
            error_log("$log_prefix Post Horario creado con ID: $new_post_id");
            $creados_count++;
        }
    }

    // --- 5. Preparar y Enviar Respuesta JSON ---
    if ( $creados_count > 0 && empty($errores_creacion) ) {
        error_log("$log_prefix Éxito: Creados $creados_count bloques.");
        // Opcional: Generar HTML actualizado de la tabla para devolver al JS
        $html_tabla = '';
        if ( function_exists( 'mph_get_horarios_table_html' ) ) {
             // Pasamos el ID del maestro para obtener su tabla actualizada
            $html_tabla = mph_get_horarios_table_html( $sanitized_data['maestro_id'] );
             error_log("$log_prefix HTML de tabla generado.");
        } else {
             error_log("$log_prefix Advertencia: Función mph_get_horarios_table_html no encontrada.");
        }

        wp_send_json_success( array(
            'message' => __( 'Horario guardado con éxito.', 'mi-plugin-horarios' ),
            'html_tabla' => $html_tabla // Enviar HTML al JS para actualizar la vista
        ) );

    } else {
         // Hubo errores al crear algunos o todos los posts
         $error_string = implode( '; ', $errores_creacion );
         error_log("$log_prefix Fallo: Creados $creados_count bloques, pero con errores: $error_string");
         wp_send_json_error( array(
             'message' => __( 'Se produjo un error al guardar algunos bloques de horario: ', 'mi-plugin-horarios' ) . $error_string
             ), 500 );
    }

    // wp_die(); // Es importante terminar la ejecución AJAX con wp_die() o similar después de wp_send_json_*
    // Sin embargo, wp_send_json_* ya incluye die()

} // Fin de mph_ajax_guardar_horario_maestro_callback

?>