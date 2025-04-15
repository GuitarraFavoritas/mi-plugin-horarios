<?php
/**
 * Plugin Name:       Mi Plugin de Horarios para Maestros
 * Plugin URI:        https://ejemplo.com/mi-plugin-horarios (Opcional: URL de información del plugin)
 * Description:       Gestiona los horarios de disponibilidad y asignación de clases para maestros de música.
 * Version:           1.0.0
 * Author:            Fer
 * Author URI:        https://ejemplo.com (Opcional: URL del autor)
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mi-plugin-horarios
 * Domain Path:       /languages (Opcional: Si planeas añadir traducciones)
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Salir si se accede directamente.
}

/**
 * Constantes Principales del Plugin
 */
// Define la versión del plugin. Útil para cache busting de scripts/styles.
define( 'MI_PLUGIN_HORARIOS_VERSION', '1.0.0' );
// Define la ruta base del plugin. Útil para incluir archivos.
define( 'MI_PLUGIN_HORARIOS_PATH', plugin_dir_path( __FILE__ ) );
// Define la URL base del plugin. Útil para encolar assets.
define( 'MI_PLUGIN_HORARIOS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Cargar Archivos Principales del Plugin
 *
 * Incluimos los archivos necesarios para registrar los tipos de post,
 * taxonomías, lógica central, y manejadores AJAX.
 */
require_once MI_PLUGIN_HORARIOS_PATH . 'includes/post-types.php';
require_once MI_PLUGIN_HORARIOS_PATH . 'includes/taxonomies.php';
require_once MI_PLUGIN_HORARIOS_PATH . 'includes/schedule-logic.php';
require_once MI_PLUGIN_HORARIOS_PATH . 'includes/ajax-handlers.php';
// Descomenta la siguiente línea si decides usar el archivo helpers.php
require_once MI_PLUGIN_HORARIOS_PATH . 'includes/helpers.php';

/**
 * Cargar Funcionalidad Específica del Área de Administración
 *
 * Solo cargamos estos archivos si estamos en el contexto del panel de administración (wp-admin),
 * para optimizar la carga en el frontend.
 */
if ( is_admin() ) {
    require_once MI_PLUGIN_HORARIOS_PATH . 'admin/meta-boxes.php';
    require_once MI_PLUGIN_HORARIOS_PATH . 'admin/enqueue.php';
    // Aquí podríamos incluir más archivos específicos del admin si fuera necesario.
}

/**
 * Cargar Funcionalidad Específica del Área Pública (Frontend)
 *
 * Estos archivos se cargan siempre (o podríamos añadir condiciones como !is_admin()),
 * ya que contienen shortcodes o funcionalidades que pueden ser necesarias
 * tanto en el frontend como potencialmente en contextos AJAX iniciados desde el frontend.
 */
if ( !is_admin()) {
	require_once MI_PLUGIN_HORARIOS_PATH . 'public/shortcodes.php';
	require_once MI_PLUGIN_HORARIOS_PATH . 'public/enqueue.php';
// Aquí podríamos incluir más archivos específicos del frontend si fuera necesario.
}



/**
 * Función de Activación del Plugin (Opcional pero recomendado)
 *
 * Se ejecuta una sola vez cuando el plugin es activado.
 * Útil para configurar opciones por defecto, roles, o limpiar permalinks.
 */
function mi_plugin_horarios_activate() {
    // Acción importante: Limpiar las reglas de reescritura (permalinks)
    // para que los nuevos CPTs y taxonomías sean reconocidos inmediatamente.
    // Registraremos los CPTs y Taxonomías en sus respectivos archivos,
    // así que nos aseguramos de que estén cargados antes de llamar a flush_rewrite_rules.
    require_once MI_PLUGIN_HORARIOS_PATH . 'includes/post-types.php';
    require_once MI_PLUGIN_HORARIOS_PATH . 'includes/taxonomies.php';
    mph_register_post_types(); // Llamamos a la función que registrará los CPTs (la crearemos pronto)
    mph_register_taxonomies(); // Llamamos a la función que registrará las taxonomías (la crearemos pronto)
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mi_plugin_horarios_activate' );

/**
 * Función de Desactivación del Plugin (Opcional)
 *
 * Se ejecuta una sola vez cuando el plugin es desactivado.
 * Útil para limpiar tareas programadas (cron jobs) o configuraciones temporales.
 * Normalmente, NO eliminamos datos (CPTs, taxonomías) en la desactivación.
 */
function mi_plugin_horarios_deactivate() {
    // Opcional: Limpiar reglas de reescritura también al desactivar.
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'mi_plugin_horarios_deactivate' );

/**
 * Función de Desinstalación del Plugin (Opcional y ¡PELIGROSO!)
 *
 * Se ejecuta si el usuario ELIMINA el plugin desde el panel de WordPress.
 * Aquí es donde podrías añadir código para eliminar CPTs, taxonomías,
 * opciones de la base de datos, y tablas personalizadas (si las usaras).
 * ¡USAR CON EXTREMA PRECAUCIÓN! La eliminación de datos es irreversible.
 * Por ahora, la dejaremos comentada o vacía como práctica segura.
 */
/*
function mi_plugin_horarios_uninstall() {
    // // Ejemplo (¡NO DESCOMENTAR A MENOS QUE ESTÉS SEGURO!):
    // // Eliminar todos los posts del CPT 'maestro' y 'horario'
    // $maestros = get_posts( array( 'post_type' => 'maestro', 'numberposts' => -1, 'post_status' => 'any' ) );
    // foreach ( $maestros as $maestro ) {
    //     wp_delete_post( $maestro->ID, true ); // true = forzar borrado permanente
    // }
    // $horarios = get_posts( array( 'post_type' => 'horario', 'numberposts' => -1, 'post_status' => 'any' ) );
    // foreach ( $horarios as $horario ) {
    //     wp_delete_post( $horario->ID, true );
    // }
    // // Eliminar opciones guardadas (si las hubiera)
    // // delete_option('mi_plugin_opcion_1');
    // // Eliminar roles/capacidades personalizadas (si las hubiera)
}
// register_uninstall_hook( __FILE__, 'mi_plugin_horarios_uninstall' );
*/

/* Finaliza Modificación */