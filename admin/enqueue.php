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
         $todos_programas = get_terms( array( 'taxonomy' => 'programa', 'hide_empty' => false ) );
         $todas_sedes = get_terms( array( 'taxonomy' => 'sede', 'hide_empty' => false ) );
         $todos_rangos = get_terms( array( 'taxonomy' => 'rango_edad', 'hide_empty' => false ) );


        wp_localize_script( 'mph-admin-modal-script', 'mph_admin_obj', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ), // URL estándar para peticiones AJAX en WordPress
            'nonce'    => wp_create_nonce( 'mph_gestionar_horarios_nonce' ), // Pasamos el mismo nonce que creamos en el meta box
            'maestro_id' => $maestro_id,
            'programas_maestro' => $programas_maestro_ids, // IDs de programas asignados al maestro
            'sedes_maestro' => $sedes_maestro_ids,       // IDs de sedes asignadas al maestro
            'rangos_maestro' => $rangos_maestro_ids,     // IDs de rangos asignados al maestro
            'todos_programas' => $todos_programas,        // Lista completa de programas para selects
            'todas_sedes' => $todas_sedes,              // Lista completa de sedes para selects
            'todos_rangos' => $todos_rangos,            // Lista completa de rangos para selects
            'i18n' => array( // Textos traducibles para usar en JS
                'error_general' => __( 'Ocurrió un error. Por favor, inténtalo de nuevo.', 'mi-plugin-horarios' ),
                'error_hora_fin' => __( 'La hora de fin debe ser posterior a la hora de inicio.', 'mi-plugin-horarios' ),
                 'error_hora_asignada_rango' => __( 'Las horas asignadas deben estar dentro del rango general.', 'mi-plugin-horarios' ),
                 'error_hora_asignada_invalida' => __( 'La hora de fin asignada debe ser posterior a la hora de inicio asignada.', 'mi-plugin-horarios' ),
                'confirmar_eliminacion' => __( '¿Estás seguro de que deseas eliminar este horario?', 'mi-plugin-horarios' ),
                'horario_guardado' => __( '¡Horario guardado con éxito!', 'mi-plugin-horarios' ),
                'horario_eliminado' => __( 'Horario eliminado.', 'mi-plugin-horarios' ),
                // Añade más textos según necesites
            ),
        ) );
    }
}
add_action( 'admin_enqueue_scripts', 'mph_admin_enqueue_scripts' );

?>