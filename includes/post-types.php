<?php
/**
 * Funciones para registrar los Custom Post Types (CPTs) del plugin.
 *
 * Este archivo define y registra los tipos de contenido personalizados
 * necesarios para el funcionamiento del plugin de horarios.
 *
 * @package MiPluginHorarios/Includes
 * @version 1.0.0
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Función principal para registrar todos los CPTs del plugin.
 *
 * Esta función actúa como un controlador central que llama a las funciones
 * específicas para registrar cada CPT. Se engancha a la acción 'init' de WordPress.
 *
 * Es llamada desde la función de activación en mi-plugin-horarios.php
 * y también se engancha a 'init' para el registro normal en cada carga de página.
 */
function mph_register_post_types() {

    // Llama a la función para registrar el CPT 'Maestro'.
    mph_register_maestro_cpt();
    mph_register_horario_cpt();

    // Aquí añadiremos la llamada para registrar el CPT 'Horario' más adelante.
    // mph_register_horario_cpt();

}
// Engancha la función principal al hook 'init'.
// La prioridad 10 es la estándar, pero se puede ajustar si hay conflictos.
add_action( 'init', 'mph_register_post_types' );

/**
 * Registra el Custom Post Type: Maestro.
 *
 * Define las etiquetas, argumentos y registra el CPT 'maestro' que
 * representará a cada profesor de música en el sistema.
 */
function mph_register_maestro_cpt() {

    // Define las etiquetas (textos) que se mostrarán en el panel de administración.
    // Es importante usar internacionalización para que puedan ser traducidas.
    $labels = array(
        'name'                  => _x( 'Maestros', 'Post Type General Name', 'mi-plugin-horarios' ),
        'singular_name'         => _x( 'Maestro', 'Post Type Singular Name', 'mi-plugin-horarios' ),
        'menu_name'             => __( 'Maestros', 'mi-plugin-horarios' ),
        'name_admin_bar'        => __( 'Maestro', 'mi-plugin-horarios' ),
        'archives'              => __( 'Archivos de Maestros', 'mi-plugin-horarios' ),
        'attributes'            => __( 'Atributos de Maestro', 'mi-plugin-horarios' ),
        'parent_item_colon'     => __( 'Maestro Padre:', 'mi-plugin-horarios' ),
        'all_items'             => __( 'Todos los Maestros', 'mi-plugin-horarios' ),
        'add_new_item'          => __( 'Añadir Nuevo Maestro', 'mi-plugin-horarios' ),
        'add_new'               => __( 'Añadir Nuevo', 'mi-plugin-horarios' ),
        'new_item'              => __( 'Nuevo Maestro', 'mi-plugin-horarios' ),
        'edit_item'             => __( 'Editar Maestro', 'mi-plugin-horarios' ),
        'update_item'           => __( 'Actualizar Maestro', 'mi-plugin-horarios' ),
        'view_item'             => __( 'Ver Maestro', 'mi-plugin-horarios' ),
        'view_items'            => __( 'Ver Maestros', 'mi-plugin-horarios' ),
        'search_items'          => __( 'Buscar Maestro', 'mi-plugin-horarios' ),
        'not_found'             => __( 'No encontrado', 'mi-plugin-horarios' ),
        'not_found_in_trash'    => __( 'No encontrado en la Papelera', 'mi-plugin-horarios' ),
        'featured_image'        => __( 'Foto del Maestro', 'mi-plugin-horarios' ), // Cambiamos 'Imagen Destacada'
        'set_featured_image'    => __( 'Establecer Foto del Maestro', 'mi-plugin-horarios' ),
        'remove_featured_image' => __( 'Quitar Foto del Maestro', 'mi-plugin-horarios' ),
        'use_featured_image'    => __( 'Usar como Foto del Maestro', 'mi-plugin-horarios' ),
        'insert_into_item'      => __( 'Insertar en Maestro', 'mi-plugin-horarios' ),
        'uploaded_to_this_item' => __( 'Subido a este Maestro', 'mi-plugin-horarios' ),
        'items_list'            => __( 'Lista de Maestros', 'mi-plugin-horarios' ),
        'items_list_navigation' => __( 'Navegación de lista de Maestros', 'mi-plugin-horarios' ),
        'filter_items_list'     => __( 'Filtrar lista de Maestros', 'mi-plugin-horarios' ),
    );

    // Define los argumentos de configuración para el CPT.
    $args = array(
        'label'                 => __( 'Maestro', 'mi-plugin-horarios' ),
        'description'           => __( 'Post Type para gestionar los maestros de música.', 'mi-plugin-horarios' ),
        'labels'                => $labels,
        // 'supports' define qué características de WordPress estarán disponibles para este CPT.
        // 'title': Campo de título (Nombre del Maestro).
        // 'editor': Editor de contenido principal (para biografía, notas, etc.). Puedes quitarlo si no lo necesitas.
        // 'thumbnail': Soporte para Imagen Destacada (Foto del Maestro).
        // 'custom-fields': Soporte básico para campos personalizados (aunque usaremos ACF principalmente).
        'supports'              => array( 'title', 'custom-fields' ),
        // 'taxonomies' especifica las taxonomías que se asociarán a este CPT.
        // Las registraremos en 'taxonomies.php', pero las listamos aquí para la asociación.
        'taxonomies'            => array( 'programa', 'sede', 'rango_edad' ),
        'hierarchical'          => false, // Los maestros no suelen ser jerárquicos (padre/hijo).
        'public'                => true, // Hace el CPT visible en el frontend y consultable.
        'show_ui'               => true, // Muestra la interfaz de usuario en el panel de administración.
        'show_in_menu'          => true, // Muestra el CPT en el menú principal del admin.
        'menu_position'         => 5, // Posición en el menú (5 es debajo de 'Entradas'). Ajusta si es necesario.
        'menu_icon'             => 'dashicons-businessman', // Icono del menú (puedes elegir otro de Dashicons).
        'show_in_admin_bar'     => true, // Muestra en la barra de administración superior.
        'show_in_nav_menus'     => true, // Permite añadir maestros a los menús de navegación.
        'can_export'            => true, // Permite exportar los datos de este CPT.
        'has_archive'           => true, // Genera una página de archivo para listar todos los maestros (ej. tusitio.com/maestros/).
        'exclude_from_search'   => false, // Incluye estos posts en las búsquedas del sitio (puedes ponerlo a true si no quieres).
        'publicly_queryable'    => true, // Permite que se realicen consultas directas a este CPT desde la URL.
        'capability_type'       => 'post', // Tipo de permisos base (como las entradas normales). Podríamos definir capacidades personalizadas más adelante si fuera necesario.
        'show_in_rest'          => true, // Habilita la disponibilidad de este CPT en la API REST de WordPress (importante si usas el editor de bloques Gutenberg o planeas interacciones modernas).
        // 'rewrite' controla cómo se forman las URLs (permalinks) para este CPT.
        'rewrite'               => array(
            'slug'       => 'maestros', // URL base para los maestros (ej. tusitio.com/maestros/nombre-maestro/).
            'with_front' => false, // No prefijar con la estructura de permalinks base (ej. /blog/).
        ),
    );

    // Registra el CPT 'maestro' con los argumentos definidos.
    // El primer parámetro es el identificador único del CPT (slug). Debe ser único.
    register_post_type( 'maestro', $args );

}

// --- Aquí irá la función mph_register_horario_cpt() ---

function mph_register_horario_cpt() {

    // Define las etiquetas (textos) que se mostrarán en el panel de administración.
    // Es importante usar internacionalización para que puedan ser traducidas.
    $labels = array(
        'name'                  => _x( 'Horarios', 'Post Type General Name', 'mi-plugin-horarios' ),
        'singular_name'         => _x( 'Horario', 'Post Type Singular Name', 'mi-plugin-horarios' ),
        'menu_name'             => __( 'Horarios', 'mi-plugin-horarios' ),
        'name_admin_bar'        => __( 'Horario', 'mi-plugin-horarios' ),
        'archives'              => __( 'Archivos de Horarios', 'mi-plugin-horarios' ),
        'attributes'            => __( 'Atributos de Horario', 'mi-plugin-horarios' ),
        'parent_item_colon'     => __( 'Horario Padre:', 'mi-plugin-horarios' ),
        'all_items'             => __( 'Todos los Horarios', 'mi-plugin-horarios' ),
        'add_new_item'          => __( 'Añadir Nuevo Horario', 'mi-plugin-horarios' ),
        'add_new'               => __( 'Añadir Nuevo', 'mi-plugin-horarios' ),
        'new_item'              => __( 'Nuevo Horario', 'mi-plugin-horarios' ),
        'edit_item'             => __( 'Editar Horario', 'mi-plugin-horarios' ),
        'update_item'           => __( 'Actualizar Horario', 'mi-plugin-horarios' ),
        'view_item'             => __( 'Ver Horario', 'mi-plugin-horarios' ),
        'view_items'            => __( 'Ver Horarios', 'mi-plugin-horarios' ),
        'search_items'          => __( 'Buscar Horario', 'mi-plugin-horarios' ),
        'not_found'             => __( 'No encontrado', 'mi-plugin-horarios' ),
        'not_found_in_trash'    => __( 'No encontrado en la Papelera', 'mi-plugin-horarios' ),
        'featured_image'        => __( 'Foto del Horario', 'mi-plugin-horarios' ), // Cambiamos 'Imagen Destacada'
        'set_featured_image'    => __( 'Establecer Foto del Horario', 'mi-plugin-horarios' ),
        'remove_featured_image' => __( 'Quitar Foto del Horario', 'mi-plugin-horarios' ),
        'use_featured_image'    => __( 'Usar como Foto del Horario', 'mi-plugin-horarios' ),
        'insert_into_item'      => __( 'Insertar en Horario', 'mi-plugin-horarios' ),
        'uploaded_to_this_item' => __( 'Subido a este Horario', 'mi-plugin-horarios' ),
        'items_list'            => __( 'Lista de Horarios', 'mi-plugin-horarios' ),
        'items_list_navigation' => __( 'Navegación de lista de Horarios', 'mi-plugin-horarios' ),
        'filter_items_list'     => __( 'Filtrar lista de Horarios', 'mi-plugin-horarios' ),
    );

    // Define los argumentos de configuración para el CPT.
    $args = array(
        'label'                 => __( 'Horario', 'mi-plugin-horarios' ),
        'description'           => __( 'Almacena cada bloque de tiempo (asignado o disponible) de los maestros.', 'mi-plugin-horarios' ),
        'labels'                => $labels,
        // 'supports' define qué características de WordPress estarán disponibles para este CPT.
        // 'title': Campo de título (Nombre del Horario).
        // 'editor': Editor de contenido principal (para biografía, notas, etc.). Puedes quitarlo si no lo necesitas.
        // 'thumbnail': Soporte para Imagen Destacada (Foto del Horario).
        // 'custom-fields': Soporte básico para campos personalizados (aunque usaremos ACF principalmente).
        'supports'              => array( 'title', 'custom-fields' ),
        // 'taxonomies' especifica las taxonomías que se asociarán a este CPT.
        // Las registraremos en 'taxonomies.php', pero las listamos aquí para la asociación.
        'hierarchical'          => false, // Los horarios no suelen ser jerárquicos (padre/hijo).
        'public'                => false, // Como decidimos, no queremos que tengan páginas individuales accesibles directamente por URL.
        'show_ui'               => false, // No queremos el menú de administración para Horarios.
        'show_in_menu'          => false, // Oculto del menú principal.
        'menu_position'         => 5, // Posición en el menú (5 es debajo de 'Entradas'). Ajusta si es necesario.
        'menu_icon'             => 'dashicons-businessman', // Icono del menú (puedes elegir otro de Dashicons).
        'show_in_admin_bar'     => false, // No mostrar en la barra superior.
        'show_in_nav_menus'     => false, // No seleccionable para menús.
        'can_export'            => true, // Podría ser útil exportarlos.
        'has_archive'           => false, // No necesita página de archivo.
        'exclude_from_search'   => true, // Generalmente no queremos que estos bloques internos aparezcan en búsquedas globales del sitio.
        'publicly_queryable'    => true, // Importante: Aunque public es false, necesitamos que publicly_queryable sea true para poder consultarlos mediante WP_Query desde nuestro shortcode en el frontend.
        'capability_type'       => 'post', // Tipo de permisos base (como las entradas normales). Podríamos definir capacidades personalizadas más adelante si fuera necesario.
        'show_in_rest'          => false, // Probablemente no necesitemos acceso directo vía REST API para estos posts internos, a menos que planees una interfaz JS muy avanzada. Puedes ponerlo a true si prefieres mantener la opción.
        // 'rewrite' controla cómo se forman las URLs (permalinks) para este CPT.
        'rewrite'               => false, // Como no es público, no necesita reglas de reescritura.
    );

    // Registra el CPT 'horario' con los argumentos definidos.
    // El primer parámetro es el identificador único del CPT (slug). Debe ser único.
    register_post_type( 'horario', $args );

}