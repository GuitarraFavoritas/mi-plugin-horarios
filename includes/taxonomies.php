<?php
/**
 * Funciones para registrar las Taxonomías Personalizadas del plugin.
 *
 * Este archivo define y registra las clasificaciones (categorías)
 * que se asociarán a los CPTs, como Programas, Sedes y Rangos de Edades.
 *
 * @package MiPluginHorarios/Includes
 * @version 1.0.0
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Función principal para registrar todas las Taxonomías del plugin.
 *
 * Esta función actúa como un controlador central que llama a las funciones
 * específicas para registrar cada taxonomía. Se engancha a la acción 'init' de WordPress.
 *
 * Es llamada desde la función de activación en mi-plugin-horarios.php
 * y también se engancha a 'init' para el registro normal en cada carga de página.
 */
function mph_register_taxonomies() {

    // Llama a la función para registrar la taxonomía 'Programa'.
    mph_register_programa_taxonomy();

    // Aquí añadiremos las llamadas para registrar las taxonomías 'Sede' y 'Rango de Edad' más adelante.
    mph_register_sede_taxonomy();
    mph_register_rango_de_edad_taxonomy();

}
// Engancha la función principal al hook 'init'.
// Es importante que las taxonomías se registren en 'init', al igual que los CPTs.
// A menudo se usa la misma prioridad (10) o una ligeramente posterior si dependen de los CPTs,
// pero en este caso, registrarlas en la misma prioridad está bien.
add_action( 'init', 'mph_register_taxonomies' );

/**
 * Registra la Taxonomía Personalizada: Programa.
 *
 * Define las etiquetas, argumentos y registra la taxonomía 'programa' que
 * permitirá clasificar a los maestros según los programas que pueden enseñar.
 */
function mph_register_programa_taxonomy() {

    // Define las etiquetas para la taxonomía 'programa'.
    $labels = array(
        'name'                       => _x( 'Programas', 'Taxonomy General Name', 'mi-plugin-horarios' ),
        'singular_name'              => _x( 'Programa', 'Taxonomy Singular Name', 'mi-plugin-horarios' ),
        'menu_name'                  => __( 'Programas', 'mi-plugin-horarios' ),
        'all_items'                  => __( 'Todos los Programas', 'mi-plugin-horarios' ),
        'parent_item'                => __( 'Programa Padre', 'mi-plugin-horarios' ),
        'parent_item_colon'          => __( 'Programa Padre:', 'mi-plugin-horarios' ),
        'new_item_name'              => __( 'Nuevo Nombre de Programa', 'mi-plugin-horarios' ),
        'add_new_item'               => __( 'Añadir Nuevo Programa', 'mi-plugin-horarios' ),
        'edit_item'                  => __( 'Editar Programa', 'mi-plugin-horarios' ),
        'update_item'                => __( 'Actualizar Programa', 'mi-plugin-horarios' ),
        'view_item'                  => __( 'Ver Programa', 'mi-plugin-horarios' ),
        'separate_items_with_commas' => __( 'Separar programas con comas', 'mi-plugin-horarios' ), // Para taxonomías no jerárquicas
        'add_or_remove_items'        => __( 'Añadir o quitar programas', 'mi-plugin-horarios' ), // Para el meta box
        'choose_from_most_used'      => __( 'Elegir de los más usados', 'mi-plugin-horarios' ), // Para el meta box
        'popular_items'              => __( 'Programas Populares', 'mi-plugin-horarios' ), // Para taxonomías no jerárquicas
        'search_items'               => __( 'Buscar Programas', 'mi-plugin-horarios' ),
        'not_found'                  => __( 'No encontrados', 'mi-plugin-horarios' ),
        'no_terms'                   => __( 'Sin programas', 'mi-plugin-horarios' ),
        'items_list'                 => __( 'Lista de programas', 'mi-plugin-horarios' ),
        'items_list_navigation'      => __( 'Navegación de lista de programas', 'mi-plugin-horarios' ),
    );

    // Define los argumentos de configuración para la taxonomía.
    $args = array(
        'labels'                     => $labels,
        // 'hierarchical' = true simula el comportamiento de las Categorías (jerarquía padre/hijo).
        // 'hierarchical' = false simula el comportamiento de las Etiquetas (nube de tags, no jerárquico).
        // Para Programas, podríamos querer agruparlos (ej. Instrumento > Nivel), así que ponemos true. Ajusta si prefieres que sea no jerárquico.
        'hierarchical'               => true,
        'public'                     => true, // Hace la taxonomía visible en el frontend.
        'show_ui'                    => true, // Muestra la interfaz de usuario en el admin (ej. un submenú bajo 'Maestros').
        'show_admin_column'          => true, // Muestra una columna con los términos asignados en la tabla de listado del CPT asociado ('Maestros').
        'show_in_nav_menus'          => true, // Permite añadir términos de esta taxonomía a los menús de navegación.
        'show_tagcloud'              => false, // No suele ser útil para programas mostrar una nube de etiquetas.
        'show_in_rest'               => true, // Habilita la taxonomía en la API REST.
        // 'rewrite' controla las URLs de los archivos de esta taxonomía.
        'rewrite'                    => array(
            'slug'         => 'programas', // URL base (ej. tusitio.com/programas/nombre-programa/).
            'with_front'   => false, // No prefijar con la estructura de permalinks base.
            'hierarchical' => true, // Permite URLs tipo /programas/padre/hijo/ si es jerárquica.
        ),
    );

    // Registra la taxonomía 'programa'.
    // El primer parámetro es el identificador único (slug) de la taxonomía.
    // El segundo parámetro es el CPT (o array de CPTs) al que se asociará esta taxonomía.
    register_taxonomy( 'programa', array( 'maestro' ), $args );

}

// --- Aquí irán las funciones mph_register_sede_taxonomy() y mph_register_rango_edad_taxonomy() ---

function mph_register_sede_taxonomy() {

    // Define las etiquetas para la taxonomía 'sede'.
    $labels = array(
        'name'                       => _x( 'Sedes', 'Taxonomy General Name', 'mi-plugin-horarios' ),
        'singular_name'              => _x( 'Sede', 'Taxonomy Singular Name', 'mi-plugin-horarios' ),
        'menu_name'                  => __( 'Sedes', 'mi-plugin-horarios' ),
        'all_items'                  => __( 'Todos los Sedes', 'mi-plugin-horarios' ),
        'parent_item'                => __( 'Sede Padre', 'mi-plugin-horarios' ),
        'parent_item_colon'          => __( 'Sede Padre:', 'mi-plugin-horarios' ),
        'new_item_name'              => __( 'Nuevo Nombre de Sede', 'mi-plugin-horarios' ),
        'add_new_item'               => __( 'Añadir Nuevo Sede', 'mi-plugin-horarios' ),
        'edit_item'                  => __( 'Editar Sede', 'mi-plugin-horarios' ),
        'update_item'                => __( 'Actualizar Sede', 'mi-plugin-horarios' ),
        'view_item'                  => __( 'Ver Sede', 'mi-plugin-horarios' ),
        'separate_items_with_commas' => __( 'Separar sedes con comas', 'mi-plugin-horarios' ), // Para taxonomías no jerárquicas
        'add_or_remove_items'        => __( 'Añadir o quitar sedes', 'mi-plugin-horarios' ), // Para el meta box
        'choose_from_most_used'      => __( 'Elegir de los más usados', 'mi-plugin-horarios' ), // Para el meta box
        'popular_items'              => __( 'Sedes Populares', 'mi-plugin-horarios' ), // Para taxonomías no jerárquicas
        'search_items'               => __( 'Buscar Sedes', 'mi-plugin-horarios' ),
        'not_found'                  => __( 'No encontrados', 'mi-plugin-horarios' ),
        'no_terms'                   => __( 'Sin sedes', 'mi-plugin-horarios' ),
        'items_list'                 => __( 'Lista de sedes', 'mi-plugin-horarios' ),
        'items_list_navigation'      => __( 'Navegación de lista de sedes', 'mi-plugin-horarios' ),
    );

    // Define los argumentos de configuración para la taxonomía.
    $args = array(
        'labels'                     => $labels,
        // 'hierarchical' = true simula el comportamiento de las Categorías (jerarquía padre/hijo).
        // 'hierarchical' = false simula el comportamiento de las Etiquetas (nube de tags, no jerárquico).
        // Para Sedes, podríamos querer agruparlos (ej. Instrumento > Nivel), así que ponemos true. Ajusta si prefieres que sea no jerárquico.
        'hierarchical'               => true,
        'public'                     => true, // Hace la taxonomía visible en el frontend.
        'show_ui'                    => true, // Muestra la interfaz de usuario en el admin (ej. un submenú bajo 'Maestros').
        'show_admin_column'          => true, // Muestra una columna con los términos asignados en la tabla de listado del CPT asociado ('Maestros').
        'show_in_nav_menus'          => true, // Permite añadir términos de esta taxonomía a los menús de navegación.
        'show_tagcloud'              => false, // No suele ser útil para sedes mostrar una nube de etiquetas.
        'show_in_rest'               => true, // Habilita la taxonomía en la API REST.
        // 'rewrite' controla las URLs de los archivos de esta taxonomía.
        'rewrite'                    => array(
            'slug'         => 'sedes', // URL base (ej. tusitio.com/sedes/nombre-sede/).
            'with_front'   => false, // No prefijar con la estructura de permalinks base.
            'hierarchical' => true, // Permite URLs tipo /sedes/padre/hijo/ si es jerárquica.
        ),
    );

    // Registra la taxonomía 'sede'.
    // El primer parámetro es el identificador único (slug) de la taxonomía.
    // El segundo parámetro es el CPT (o array de CPTs) al que se asociará esta taxonomía.
    register_taxonomy( 'sede', array( 'maestro' ), $args );

}


function mph_register_rango_de_edad_taxonomy() {

    // Define las etiquetas para la taxonomía 'rango_edad'.
    $labels = array(
        'name'                       => _x( 'Rangos de  Edades', 'Taxonomy General Name', 'mi-plugin-horarios' ),
        'singular_name'              => _x( 'Rango de  Edad', 'Taxonomy Singular Name', 'mi-plugin-horarios' ),
        'menu_name'                  => __( 'Rangos de  Edades', 'mi-plugin-horarios' ),
        'all_items'                  => __( 'Todos los Rangos de  Edades', 'mi-plugin-horarios' ),
        'parent_item'                => __( 'Rango de  Edad Padre', 'mi-plugin-horarios' ),
        'parent_item_colon'          => __( 'Rango de  Edad Padre:', 'mi-plugin-horarios' ),
        'new_item_name'              => __( 'Nuevo Nombre de Rango de  Edad', 'mi-plugin-horarios' ),
        'add_new_item'               => __( 'Añadir Nuevo Rango de  Edad', 'mi-plugin-horarios' ),
        'edit_item'                  => __( 'Editar Rango de  Edad', 'mi-plugin-horarios' ),
        'update_item'                => __( 'Actualizar Rango de  Edad', 'mi-plugin-horarios' ),
        'view_item'                  => __( 'Ver Rango de  Edad', 'mi-plugin-horarios' ),
        'separate_items_with_commas' => __( 'Separar rango_de_edads con comas', 'mi-plugin-horarios' ), // Para taxonomías no jerárquicas
        'add_or_remove_items'        => __( 'Añadir o quitar rango_de_edads', 'mi-plugin-horarios' ), // Para el meta box
        'choose_from_most_used'      => __( 'Elegir de los más usados', 'mi-plugin-horarios' ), // Para el meta box
        'popular_items'              => __( 'Rangos de  Edades Populares', 'mi-plugin-horarios' ), // Para taxonomías no jerárquicas
        'search_items'               => __( 'Buscar Rangos de  Edades', 'mi-plugin-horarios' ),
        'not_found'                  => __( 'No encontrados', 'mi-plugin-horarios' ),
        'no_terms'                   => __( 'Sin rango_de_edads', 'mi-plugin-horarios' ),
        'items_list'                 => __( 'Lista de rango_de_edads', 'mi-plugin-horarios' ),
        'items_list_navigation'      => __( 'Navegación de lista de rango_de_edads', 'mi-plugin-horarios' ),
    );

    // Define los argumentos de configuración para la taxonomía.
    $args = array(
        'labels'                     => $labels,
        // 'hierarchical' = true simula el comportamiento de las Categorías (jerarquía padre/hijo).
        // 'hierarchical' = false simula el comportamiento de las Etiquetas (nube de tags, no jerárquico).
        // Para Rangos de  Edades, podríamos querer agruparlos (ej. Instrumento > Nivel), así que ponemos true. Ajusta si prefieres que sea no jerárquico.
        'hierarchical'               => true,
        'public'                     => true, // Hace la taxonomía visible en el frontend.
        'show_ui'                    => true, // Muestra la interfaz de usuario en el admin (ej. un submenú bajo 'Maestros').
        'show_admin_column'          => true, // Muestra una columna con los términos asignados en la tabla de listado del CPT asociado ('Maestros').
        'show_in_nav_menus'          => true, // Permite añadir términos de esta taxonomía a los menús de navegación.
        'show_tagcloud'              => false, // No suele ser útil para rango_de_edads mostrar una nube de etiquetas.
        'show_in_rest'               => true, // Habilita la taxonomía en la API REST.
        // 'rewrite' controla las URLs de los archivos de esta taxonomía.
        'rewrite'                    => array(
            'slug'         => 'rango_edad', // URL base (ej. tusitio.com/rango_de_edads/nombre-rango_de_edad/).
            'with_front'   => false, // No prefijar con la estructura de permalinks base.
            'hierarchical' => true, // Permite URLs tipo /rango_de_edads/padre/hijo/ si es jerárquica.
        ),
    );

    // Registra la taxonomía 'rango_edad'.
    // El primer parámetro es el identificador único (slug) de la taxonomía.
    // El segundo parámetro es el CPT (o array de CPTs) al que se asociará esta taxonomía.
    register_taxonomy( 'rango_edad', array( 'maestro' ), $args );

}