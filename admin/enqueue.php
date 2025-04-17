<?php
/**
 * Encolar Scripts y Estilos para el Área de Administración.
 *
 * Carga los archivos CSS y JS necesarios para la funcionalidad del plugin
 * en el panel de administración, especialmente para el meta box de horarios.
 *
 * @package MiPluginHorarios/Admin
 * @version 1.0.0
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Encola los scripts y estilos necesarios para la pantalla de edición del CPT 'maestro'.
 *
 * Utiliza el hook 'admin_enqueue_scripts' y comprueba la pantalla actual
 * para asegurarse de que los assets solo se cargan donde son necesarios.
 *
 * @param string $hook El sufijo del hook de la página de administración actual.
 */
function mph_admin_enqueue_scripts( $hook ) {
    global $post_type;

    // Comprueba si estamos en la pantalla de edición de un 'maestro'
    // 'post.php' es la pantalla de edición, 'post-new.php' es la pantalla de añadir nuevo.
    if ( ( $hook == 'post.php' || $hook == 'post-new.php' ) && isset( $post_type ) && $post_type == 'maestro' ) {

        // 1. Encolar Estilos CSS
        //    - Estilos de jQuery UI (para el diálogo/modal)
        //    - Estilos personalizados para nuestro meta box y modal
        wp_enqueue_style( 'jquery-ui-dialog-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css', array(), '1.12.1' );
        wp_enqueue_style( 'mph-admin-styles', MI_PLUGIN_HORARIOS_URL . 'assets/css/admin-styles.css', array(), MI_PLUGIN_HORARIOS_VERSION );

        // 2. Encolar Scripts JS
        //    - jQuery (WordPress lo incluye por defecto, pero lo declaramos como dependencia)
        //    - jQuery UI Core, Widget, Mouse, Draggable, Dialog (para el modal arrastrable)
        //    - Nuestro script personalizado para manejar el modal y AJAX
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'jquery-ui-mouse' );
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-dialog' );

        wp_enqueue_script( 'mph-admin-modal-script', MI_PLUGIN_HORARIOS_URL . 'assets/js/admin-maestro-modal.js', array( 'jquery', 'jquery-ui-dialog' ), MI_PLUGIN_HORARIOS_VERSION, true ); // true = cargar en el footer

        // 3. Pasar datos de PHP a JavaScript (Localización)
        //    Esto es útil para pasar datos como nonces, URLs de AJAX,
        //    y textos traducibles a nuestro script JS.
        global $post; // Asegurarse de que $post está disponible
        $maestro_id = isset($post->ID) ? $post->ID : 0;
        $programas_maestro_ids = wp_get_object_terms( $maestro_id, 'programa', array('fields' => 'ids') );
        $sedes_maestro_ids = wp_get_object_terms( $maestro_id, 'sede', array('fields' => 'ids') );
        $rangos_maestro_ids = wp_get_object_terms( $maestro_id, 'rango_edad', array('fields' => 'ids') );

        // Obtener todos los términos para que JS pueda construir los selects de asignación
        // --- Procesar Programas ---
        $todos_programas_terms = get_terms( array( 'taxonomy' => 'programa', 'hide_empty' => false ) );
        $todos_programas_data = array(); // Cambiamos el nombre de la variable final
        if ( !is_wp_error($todos_programas_terms) && !empty($todos_programas_terms) ) {
            foreach ( $todos_programas_terms as $term ) {
                // Obtener el valor del campo ACF 'programa_comun' para este término
                // Usamos el formato 'taxonomy_' . $term_id como segundo parámetro para get_field en términos
                $es_comun = get_field( 'programa_comun', 'programa_' . $term->term_id );
                $term_data = array(
                    'term_id' => $term->term_id,
                    'name'    => $term->name,
                    'slug'    => $term->slug,
                    // Añadimos el valor del meta al array que pasaremos a JS
                    // get_field devuelve true/false para campos True/False de ACF
                    'es_comun' => (bool) $es_comun
                );
                $todos_programas_data[] = $term_data;
            }
        }

        // --- Procesar Sedes (similar) ---
        $todas_sedes_terms = get_terms( array( 'taxonomy' => 'sede', 'hide_empty' => false ) );
        $todas_sedes_data = array();
        if ( !is_wp_error($todas_sedes_terms) && !empty($todas_sedes_terms) ) {
            foreach ( $todas_sedes_terms as $term ) {
                $es_comun = get_field( 'sede_comun', 'sede_' . $term->term_id ); // <-- Asegúrate que el nombre ACF sea 'sede_comun'
                $term_data = array(
                    'term_id' => $term->term_id,
                    'name'    => $term->name,
                    'slug'    => $term->slug,
                    'es_comun' => (bool) $es_comun
                );
                $todas_sedes_data[] = $term_data;
            }
        }
         
        // --- Procesar Rangos (similar) ---
        $todos_rangos_terms = get_terms( array( 'taxonomy' => 'rango_edad', 'hide_empty' => false ) );
        $todos_rangos_data = array();
        if ( !is_wp_error($todos_rangos_terms) && !empty($todos_rangos_terms) ) {
            foreach ( $todos_rangos_terms as $term ) {
                $es_comun = get_field( 'rango_edad_comun', 'rango_edad_' . $term->term_id ); // <-- Asegúrate que el nombre ACF sea 'rango_edad_comun'
                $term_data = array(
                    'term_id' => $term->term_id,
                    'name'    => $term->name,
                    'slug'    => $term->slug,
                    'es_comun' => (bool) $es_comun
                );
                $todos_rangos_data[] = $term_data;
            }
        }


        wp_localize_script( 'mph-admin-modal-script', 'mph_admin_obj', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ), // URL estándar para peticiones AJAX en WordPress
            'nonce'    => wp_create_nonce( 'mph_gestionar_horarios_nonce' ), // Pasamos el mismo nonce que creamos en el meta box
            'maestro_id' => $maestro_id,
            'programas_maestro' => $programas_maestro_ids, // IDs de programas asignados al maestro
            'sedes_maestro' => $sedes_maestro_ids,       // IDs de sedes asignadas al maestro
            'rangos_maestro' => $rangos_maestro_ids,     // IDs de rangos asignados al maestro
            'todos_programas' => $todos_programas_data,        // Lista completa de programas para selects
            'todas_sedes' => $todas_sedes_data,              // Lista completa de sedes para selects
            'todos_rangos' => $todos_rangos_data,            // Lista completa de rangos para selects
            'i18n' => array( // Textos traducibles para usar en JS
                'error_general' => __( 'Ocurrió un error. Por favor, inténtalo de nuevo.', 'mi-plugin-horarios' ),
                'error_hora_fin' => __( 'La hora de fin debe ser posterior a la hora de inicio.', 'mi-plugin-horarios' ),
                 'error_hora_asignada_rango' => __( 'Las horas asignadas deben estar dentro del rango general.', 'mi-plugin-horarios' ),
                 'error_hora_asignada_invalida' => __( 'La hora de fin asignada debe ser posterior a la hora de inicio asignada.', 'mi-plugin-horarios' ),
                'confirmar_eliminacion' => __( '¿Estás seguro de que deseas eliminar este horario?', 'mi-plugin-horarios' ),
                'horario_guardado' => __( '¡Horario guardado con éxito!', 'mi-plugin-horarios' ),
                'horario_eliminado' => __( 'Horario eliminado.', 'mi-plugin-horarios' ),
                // Añade más textos según necesites
                'error_seleccionar_programa' => __('Debe seleccionar al menos un programa admisible.', 'mi-plugin-horarios'),
                'error_seleccionar_sede' => __('Debe seleccionar al menos una sede admisible.', 'mi-plugin-horarios'),
                'error_seleccionar_rango' => __('Debe seleccionar al menos un rango de edad admisible.', 'mi-plugin-horarios'),
                'error_faltan_horas_asignadas' => __('Faltan horas de asignación.', 'mi-plugin-horarios'),
                'error_seleccionar_programa_asig' => __('Debe seleccionar un programa para la asignación.', 'mi-plugin-horarios'),
                'error_seleccionar_sede_asig' => __('Debe seleccionar una sede para la asignación.', 'mi-plugin-horarios'),
                'error_seleccionar_rango_asig' => __('Debe seleccionar un rango de edad para la asignación.', 'mi-plugin-horarios'),
                'error_vacantes_negativas' => __('Las vacantes no pueden ser negativas.', 'mi-plugin-horarios'),
                'error_buffer_negativo' => __('El tiempo de buffer no puede ser negativo.', 'mi-plugin-horarios'),
                'error_buffer_antes_invalido' => __('El tiempo de buffer Antes debe ser un número positivo.', 'mi-plugin-horarios'),
                'error_buffer_despues_invalido' => __('El tiempo de buffer Después debe ser un número positivo.', 'mi-plugin-horarios'),
            ),
        ) );
    }
}
add_action( 'admin_enqueue_scripts', 'mph_admin_enqueue_scripts' );

?>