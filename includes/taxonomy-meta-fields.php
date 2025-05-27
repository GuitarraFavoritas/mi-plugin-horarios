<?php

/**
 * =========================================================================
 *  Metadatos Personalizados para Taxonomías (Sin ACF)
 * =========================================================================
 */

// --- Campos para Taxonomía: Sede ---

/**
 * Añade campos personalizados al formulario de 'Añadir Nueva Sede'.
 * Se engancha a 'sede_add_form_fields'.
 *
 * @param string $taxonomy Slug de la taxonomía actual ('sede').
 */
function mph_add_sede_meta_fields( $taxonomy ) {
    ?>
    <div class="form-field term-hora-cierre-wrap">
        <label for="term-meta-hora-cierre"><?php _e( 'Hora de Cierre', 'mi-plugin-horarios' ); ?></label>
        <input type="time" name="term_meta[hora_cierre]" id="term-meta-hora-cierre" value="">
        <p class="description"><?php _e( 'Introduce la hora de cierre de esta sede (opcional). Formato HH:MM.', 'mi-plugin-horarios' ); ?></p>
    </div>
     <div class="form-field term-sede-comun-wrap">
        <label for="term-meta-sede-comun"><?php _e( 'Sede Común', 'mi-plugin-horarios' ); ?></label>
        <input type="checkbox" name="term_meta[sede_comun]" id="term-meta-sede-comun" value="1">
        <p class="description"><?php _e( 'Marcar si esta sede se considera común y aplicable por defecto.', 'mi-plugin-horarios' ); ?></p>
    </div>
    <?php
}
add_action( 'sede_add_form_fields', 'mph_add_sede_meta_fields', 10, 1 );

/**
 * Añade campos personalizados al formulario de 'Editar Sede'.
 * Se engancha a 'sede_edit_form_fields'.
 *
 * @param WP_Term $term Objeto del término actual que se está editando.
 * @param string $taxonomy Slug de la taxonomía actual ('sede').
 */
function mph_edit_sede_meta_fields( $term, $taxonomy ) {
    // Obtener valores guardados si existen
    $hora_cierre = get_term_meta( $term->term_id, 'hora_cierre', true );
    $sede_comun = get_term_meta( $term->term_id, 'sede_comun', true );
    ?>
    <tr class="form-field term-hora-cierre-wrap">
        <th scope="row"><label for="term-meta-hora-cierre"><?php _e( 'Hora de Cierre', 'mi-plugin-horarios' ); ?></label></th>
        <td>
            <input type="time" name="term_meta[hora_cierre]" id="term-meta-hora-cierre" value="<?php echo esc_attr( $hora_cierre ); ?>">
            <p class="description"><?php _e( 'Introduce la hora de cierre de esta sede (opcional). Formato HH:MM.', 'mi-plugin-horarios' ); ?></p>
        </td>
    </tr>
    <tr class="form-field term-sede-comun-wrap">
         <th scope="row"><label for="term-meta-sede-comun"><?php _e( 'Sede Común', 'mi-plugin-horarios' ); ?></label></th>
         <td>
            <input type="checkbox" name="term_meta[sede_comun]" id="term-meta-sede-comun" value="1" <?php checked( $sede_comun, '1' ); ?>>
            <p class="description"><?php _e( 'Marcar si esta sede se considera común y aplicable por defecto.', 'mi-plugin-horarios' ); ?></p>
         </td>
     </tr>
    <?php
}
add_action( 'sede_edit_form_fields', 'mph_edit_sede_meta_fields', 10, 2 );





// --- Campos para Taxonomía: Programa ---

/**
 * Añade campos personalizados al formulario de 'Añadir Nuevo Programa'.
 * Se engancha a 'programa_add_form_fields'.
 *
 * @param string $taxonomy Slug de la taxonomía actual ('programa').
 */
function mph_add_programa_meta_fields( $taxonomy ) {
    ?>

     <div class="form-field term-programa-comun-wrap">
        <label for="term-meta-programa-comun"><?php _e( 'Programa Común', 'mi-plugin-horarios' ); ?></label>
        <input type="checkbox" name="term_meta[programa_comun]" id="term-meta-programa-comun" value="1">
        <p class="description"><?php _e( 'Marcar si esta programa se considera común y aplicable por defecto.', 'mi-plugin-horarios' ); ?></p>
    </div>
    <?php
}
add_action( 'programa_add_form_fields', 'mph_add_programa_meta_fields', 10, 1 );

/**
 * Añade campos personalizados al formulario de 'Editar Programa'.
 * Se engancha a 'programa_edit_form_fields'.
 *
 * @param WP_Term $term Objeto del término actual que se está editando.
 * @param string $taxonomy Slug de la taxonomía actual ('programa').
 */
function mph_edit_programa_meta_fields( $term, $taxonomy ) {
    // Obtener valores guardados si existen
    $programa_comun = get_term_meta( $term->term_id, 'programa_comun', true );
    ?>    
    <tr class="form-field term-programa-comun-wrap">
         <th scope="row"><label for="term-meta-programa-comun"><?php _e( 'Programa Común', 'mi-plugin-horarios' ); ?></label></th>
         <td>
            <input type="checkbox" name="term_meta[programa_comun]" id="term-meta-programa-comun" value="1" <?php checked( $programa_comun, '1' ); ?>>
            <p class="description"><?php _e( 'Marcar si esta programa se considera común y aplicable por defecto.', 'mi-plugin-horarios' ); ?></p>
         </td>
     </tr>
    <?php
}
add_action( 'programa_edit_form_fields', 'mph_edit_programa_meta_fields', 10, 2 );




// --- Campos para Taxonomía: Rango de Edad ---

/**
 * Añade campos personalizados al formulario de 'Añadir Nuevo Rango de Edad'.
 * Se engancha a 'rango_de_edad_add_form_fields'.
 *
 * @param string $taxonomy Slug de la taxonomía actual ('rango_edad').
 */
function mph_add_rango_edad_meta_fields( $taxonomy ) {
    ?>

     <div class="form-field term-rango-edad-comun-wrap">
        <label for="term-meta-rango-edad-comun"><?php _e( 'Rango de Edad Común', 'mi-plugin-horarios' ); ?></label>
        <input type="checkbox" name="term_meta[rango_edad_comun]" id="term-meta-rango-edad-comun" value="1">
        <p class="description"><?php _e( 'Marcar si este rango de edad se considera común y aplicable por defecto.', 'mi-plugin-horarios' ); ?></p>
    </div>
    <?php
}
add_action( 'rango_edad_add_form_fields', 'mph_add_rango_edad_meta_fields', 10, 1 );

/**
 * Añade campos personalizados al formulario de 'Editar Rango de Edad'.
 * Se engancha a 'rango_edad_edit_form_fields'.
 *
 * @param WP_Term $term Objeto del término actual que se está editando.
 * @param string $taxonomy Slug de la taxonomía actual ('rango_edad').
 */
function mph_edit_rango_edad_meta_fields( $term, $taxonomy ) {
    // Obtener valores guardados si existen
    $rango_edad_comun = get_term_meta( $term->term_id, 'rango_edad_comun', true );
    ?>    
    <tr class="form-field term-rango-edad-comun-wrap">
         <th scope="row"><label for="term-meta-rango-edad-comun"><?php _e( 'Rango de Edad Común', 'mi-plugin-horarios' ); ?></label></th>
         <td>
            <input type="checkbox" name="term_meta[rango_edad_comun]" id="term-meta-rango-edad-comun" value="1" <?php checked( $rango_edad_comun, '1' ); ?>>
            <p class="description"><?php _e( 'Marcar si este rango de edad se considera común y aplicable por defecto.', 'mi-plugin-horarios' ); ?></p>
         </td>
     </tr>
    <?php
}
add_action( 'rango_edad_edit_form_fields', 'mph_edit_rango_edad_meta_fields', 10, 2 );



/**
 * Guarda los valores de los metadatos personalizados para la taxonomía 'Programa'.
 * Se engancha a 'saved_programa'.
 *
 * @param int     $term_id  ID del término que se acaba de crear o editar.
 * @param int     $tt_id    Term Taxonomy ID.
 * @param bool    $update   True si es una actualización, false si es creación.
 */

function mph_save_programa_meta_fields( $term_id, $tt_id, $update ) { // Aceptar los 3 args
    error_log("--- Ejecutando mph_save_programa_meta_fields --- Term ID: $term_id, TT_ID: $tt_id, Update: " . ($update ? 'true' : 'false'));

    // Verificar si vienen datos del formulario
    if ( ! isset( $_POST['term_meta'] ) ) {
         // Incluso si no viene term_meta (ej. solo cambió nombre), queremos borrar la meta si antes existía y ahora no se envió el check
         // Así que no salimos aquí si es una actualización. Solo si es creación y no viene nada.
         if (!$update) { // Solo salir si es creación y no hay datos meta
             error_log("mph_save_programa_meta_fields: Creación sin _POST[term_meta]. Saliendo.");
             return;
         }
         // Si es update y no viene $_POST['term_meta'], asumimos que hay que borrar/poner '0'
         $meta_data = array(); // Crear array vacío para que isset funcione abajo
    } else {
        $meta_data = $_POST['term_meta'];
        error_log("mph_save_programa_meta_fields: Datos recibidos en _POST[term_meta]: " . print_r($meta_data, true));
    }


    $programa_comun_value = isset( $meta_data['programa_comun'] ) ? '1' : '0';
    error_log("mph_save_programa_meta_fields: Valor a guardar para programa_comun: $programa_comun_value");

    if ( $programa_comun_value === '1' ) {
         update_term_meta( $term_id, 'programa_comun', '1' );
         error_log("mph_save_programa_meta_fields: Ejecutado update_term_meta con valor 1 para ID $term_id");
    } else {
         // Siempre intentar borrar si el valor no es '1' (cubre desmarcado y caso de solo cambio de nombre)
         $deleted = delete_term_meta( $term_id, 'programa_comun' );
         error_log("mph_save_programa_meta_fields: Ejecutado delete_term_meta para programa_comun para ID $term_id. Resultado: " . ($deleted ? 'true' : 'false'));
    }

    error_log("--- Finalizando mph_save_programa_meta_fields para Term ID: $term_id ---");
}
// Enganchar a la acción unificada 'saved_{taxonomy}' especificando que aceptamos 3 argumentos.
add_action( 'saved_programa', 'mph_save_programa_meta_fields', 10, 3 ); // <-- Especificar 3 argumentos


/**
 * Guarda los valores de los metadatos personalizados para la taxonomía 'Rango de Edad'.
 * Se engancha a 'saved_rango_edad'.
 *
 * @param int     $term_id  ID del término que se acaba de crear o editar.
 * @param int     $tt_id    Term Taxonomy ID.
 * @param bool    $update   True si es una actualización, false si es creación.
 */

function mph_save_rango_edad_meta_fields( $term_id, $tt_id, $update ) { // Aceptar los 3 args
    error_log("--- Ejecutando mph_save_rango_edad_meta_fields --- Term ID: $term_id, TT_ID: $tt_id, Update: " . ($update ? 'true' : 'false'));

    // Verificar si vienen datos del formulario
    if ( ! isset( $_POST['term_meta'] ) ) {
         // Incluso si no viene term_meta (ej. solo cambió nombre), queremos borrar la meta si antes existía y ahora no se envió el check
         // Así que no salimos aquí si es una actualización. Solo si es creación y no viene nada.
         if (!$update) { // Solo salir si es creación y no hay datos meta
             error_log("mph_save_rango_edad_meta_fields: Creación sin _POST[term_meta]. Saliendo.");
             return;
         }
         // Si es update y no viene $_POST['term_meta'], asumimos que hay que borrar/poner '0'
         $meta_data = array(); // Crear array vacío para que isset funcione abajo
    } else {
        $meta_data = $_POST['term_meta'];
        error_log("mph_save_rango_edad_meta_fields: Datos recibidos en _POST[term_meta]: " . print_r($meta_data, true));
    }


    $rango_edad_comun_value = isset( $meta_data['rango_edad_comun'] ) ? '1' : '0';
    error_log("mph_save_rango_edad_meta_fields: Valor a guardar para rango_edad_comun: $rango_edad_comun_value");

    if ( $rango_edad_comun_value === '1' ) {
         update_term_meta( $term_id, 'rango_edad_comun', '1' );
         error_log("mph_save_rango_edad_meta_fields: Ejecutado update_term_meta con valor 1 para ID $term_id");
    } else {
         // Siempre intentar borrar si el valor no es '1' (cubre desmarcado y caso de solo cambio de nombre)
         $deleted = delete_term_meta( $term_id, 'rango_edad_comun' );
         error_log("mph_save_rango_edad_meta_fields: Ejecutado delete_term_meta para rango_edad_comun para ID $term_id. Resultado: " . ($deleted ? 'true' : 'false'));
    }

    error_log("--- Finalizando mph_save_rango_edad_meta_fields para Term ID: $term_id ---");
}
// Enganchar a la acción unificada 'saved_{taxonomy}' especificando que aceptamos 3 argumentos.
add_action( 'saved_rango_edad', 'mph_save_rango_edad_meta_fields', 10, 3 ); // <-- Especificar 3 argumentos


/**
 * Guarda los valores de los metadatos personalizados para la taxonomía 'Sede'.
 * Se engancha a 'created_sede' y 'edited_sede'.
 *
 * @param int $term_id ID del término que se acaba de crear o editar.
 */
function mph_save_sede_meta_fields( $term_id ) {
    // Verificar si vienen datos del formulario (usamos el array 'term_meta' que definimos en los names)
    if ( ! isset( $_POST['term_meta'] ) ) {
        return;
    }

    // Verificar nonce (¡Importante añadir nonces a los formularios si es necesario!)
    // Nota: WordPress a menudo maneja nonces básicos en las pantallas de edición de términos,
    // pero añadir uno propio puede ser más seguro. Por ahora, omitimos por simplicidad.

    // Sanitizar y guardar cada campo
    $meta_data = $_POST['term_meta'];

    // Guardar Hora Cierre
    if ( isset( $meta_data['hora_cierre'] ) ) {
        // Validar formato HH:MM (opcional pero recomendado)
        if (preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $meta_data['hora_cierre'])) {
            update_term_meta( $term_id, 'hora_cierre', sanitize_text_field( $meta_data['hora_cierre'] ) );
        } elseif (empty($meta_data['hora_cierre'])) {
             delete_term_meta( $term_id, 'hora_cierre' ); // Borrar si está vacío
        }
    }

     // Guardar Sede Común (Checkbox)
     $sede_comun_value = isset( $meta_data['sede_comun'] ) ? '1' : '0'; // Si está marcado viene '1', si no, no viene. Guardamos '1' o '0'.
     update_term_meta( $term_id, 'sede_comun', $sede_comun_value );

}
// Enganchar a ambas acciones: creación y edición
add_action( 'created_sede', 'mph_save_sede_meta_fields', 10, 1 );
add_action( 'edited_sede', 'mph_save_sede_meta_fields', 10, 1 );