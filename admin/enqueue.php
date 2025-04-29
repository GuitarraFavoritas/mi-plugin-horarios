<?php
/**
 * Encolar Scripts y Estilos para el Área de Administración.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function mph_admin_enqueue_scripts( $hook ) {
    global $post_type;
    error_log("mph_admin_enqueue_scripts Hook: $hook, Post Type: " . ($post_type ?? 'N/A'));

    if ( ( $hook == 'post.php' || $hook == 'post-new.php' ) && isset( $post_type ) && $post_type == 'maestro' ) {
        error_log("mph_admin_enqueue_scripts: ¡Condición cumplida! Encolando scripts para 'maestro'.");

        // Encolar Estilos
        wp_enqueue_style( 'jquery-ui-dialog-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css', array(), '1.12.1' );
        wp_enqueue_style( 'mph-admin-styles', MI_PLUGIN_HORARIOS_URL . 'assets/css/admin-styles.css', array(), MI_PLUGIN_HORARIOS_VERSION );

        // Encolar Dependencias JS
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'jquery-ui-mouse' );
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-dialog' );

        // Encolar el bundle compilado
        wp_enqueue_script( 'mph-admin-bundle', MI_PLUGIN_HORARIOS_URL . 'assets/dist/js/admin.bundle.js', array('jquery', 'jquery-ui-dialog'), MI_PLUGIN_HORARIOS_VERSION, true );

        // Preparar datos para localizar
        global $post;
        $maestro_id = isset($post->ID) ? $post->ID : 0;
        $programas_maestro_ids = wp_get_object_terms( $maestro_id, 'programa', array('fields' => 'ids') );
        $sedes_maestro_ids = wp_get_object_terms( $maestro_id, 'sede', array('fields' => 'ids') );
        $rango_taxonomy_slug = 'rango_edad'; // Ajusta si tu slug es diferente
        $rangos_maestro_ids = wp_get_object_terms( $maestro_id, $rango_taxonomy_slug, array('fields' => 'ids') );

        // --- Procesar Programas ---
        $todos_programas_data = array();
        $todos_programas_terms = get_terms( array( 'taxonomy' => 'programa', 'hide_empty' => false ) );
        if ( !is_wp_error($todos_programas_terms) && !empty($todos_programas_terms) ) {
            foreach ( $todos_programas_terms as $term ) {
                $es_comun_raw = get_term_meta( $term->term_id, 'programa_comun', true );
                $es_comun_bool = !empty($es_comun_raw) && $es_comun_raw === '1';
                $term_data = array( 'term_id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'es_comun' => $es_comun_bool );
                $todos_programas_data[] = $term_data;
            }
        }

         // --- Procesar Sedes (similar) ---
         $todas_sedes_data = array();
         $todas_sedes_terms = get_terms( array( 'taxonomy' => 'sede', 'hide_empty' => false ) );
         if ( !is_wp_error($todas_sedes_terms) && !empty($todas_sedes_terms) ) {
             foreach ( $todas_sedes_terms as $term ) {
                 $es_comun_raw = get_term_meta( $term->term_id, 'sede_comun', true );
                 $es_comun_bool = !empty($es_comun_raw) && $es_comun_raw === '1';
                 $hora_cierre_raw = get_term_meta( $term->term_id, 'hora_cierre', true );
                 $hora_cierre = sanitize_text_field($hora_cierre_raw);
                 $term_data = array( 'term_id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'es_comun' => $es_comun_bool, 'hora_cierre' => $hora_cierre );
                 $todas_sedes_data[] = $term_data;
             }
         }

        // --- Procesar Rangos (similar) ---
        $todos_rangos_data = array();
        $rango_meta_key_comun = 'rango_edad_comun'; // Ajusta si tu meta key es diferente
        $todos_rangos_terms = get_terms( array( 'taxonomy' => $rango_taxonomy_slug, 'hide_empty' => false ) );
         if ( !is_wp_error($todos_rangos_terms) && !empty($todos_rangos_terms) ) {
             foreach ( $todos_rangos_terms as $term ) {
                 $es_comun_raw = get_term_meta( $term->term_id, $rango_meta_key_comun, true );
                 $es_comun_bool = !empty($es_comun_raw) && $es_comun_raw === '1';
                 $term_data = array( 'term_id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'es_comun' => $es_comun_bool );
                 $todos_rangos_data[] = $term_data;
             }
         }

        // Crear el array de datos a localizar (SIN el nonce principal)
        $data_to_localize = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            // 'nonce'    => wp_create_nonce( 'mph_gestionar_horarios_nonce' ), // Eliminado de aquí
            'maestro_id' => $maestro_id,
            'programas_maestro' => $programas_maestro_ids,
            'sedes_maestro'     => $sedes_maestro_ids,
            'rangos_maestro'    => $rangos_maestro_ids,
            'todos_programas'   => $todos_programas_data,
            'todas_sedes'       => $todas_sedes_data,
            'todos_rangos'      => $todos_rangos_data,
            'i18n'              => array(
                // --- Asegúrate de tener todos los textos i18n necesarios ---
                'error_general' => __( 'Ocurrió un error. Por favor, inténtalo de nuevo.', 'mi-plugin-horarios' ),
                'error_hora_fin' => __( 'La hora de fin debe ser posterior a la hora de inicio.', 'mi-plugin-horarios' ),
                'error_hora_asignada_rango' => __( 'Las horas asignadas deben estar dentro del rango general.', 'mi-plugin-horarios' ),
                'error_hora_asignada_invalida' => __( 'La hora de fin asignada debe ser posterior a la hora de inicio asignada.', 'mi-plugin-horarios' ),
                'confirmar_eliminacion' => __( '¿Estás seguro de que deseas eliminar este horario?', 'mi-plugin-horarios' ),
                'horario_guardado' => __( '¡Horario guardado con éxito!', 'mi-plugin-horarios' ),
                'horario_eliminado' => __( 'Horario eliminado.', 'mi-plugin-horarios' ),
                'error_seleccionar_programa' => __('Debe seleccionar al menos un programa admisible.', 'mi-plugin-horarios'),
                'error_seleccionar_sede' => __('Debe seleccionar al menos una sede admisible.', 'mi-plugin-horarios'),
                'error_seleccionar_rango' => __('Debe seleccionar al menos un rango de edad admisible.', 'mi-plugin-horarios'),
                'error_faltan_horas_asignadas' => __('Faltan horas de asignación.', 'mi-plugin-horarios'),
                'error_seleccionar_programa_asig' => __('Debe seleccionar un programa para la asignación.', 'mi-plugin-horarios'),
                'error_seleccionar_sede_asig' => __('Debe seleccionar una sede para la asignación.', 'mi-plugin-horarios'),
                'error_seleccionar_rango_asig' => __('Debe seleccionar un rango de edad para la asignación.', 'mi-plugin-horarios'),
                'error_vacantes_negativas' => __('Las vacantes no pueden ser negativas.', 'mi-plugin-horarios'),
                'error_buffer_antes_invalido' => __('El tiempo de buffer Antes debe ser un número positivo.', 'mi-plugin-horarios'),
                'error_buffer_despues_invalido' => __('El tiempo de buffer Después debe ser un número positivo.', 'mi-plugin-horarios'),
                'error_seguridad' => __('Error de seguridad. Intente recargar la página.', 'mi-plugin-horarios'),
            ),
        );
        error_log("mph_admin_enqueue_scripts: Datos a localizar (SIN NONCE): " . print_r($data_to_localize, true));

        // Localizar los datos
        wp_localize_script( 'mph-admin-bundle', 'mph_admin_obj', $data_to_localize );
        error_log("mph_admin_enqueue_scripts: wp_localize_script EJECUTADO.");

    } else {
         error_log("mph_admin_enqueue_scripts: Condición NO cumplida. No se encolan scripts.");
    }
}
add_action( 'admin_enqueue_scripts', 'mph_admin_enqueue_scripts' );
?>