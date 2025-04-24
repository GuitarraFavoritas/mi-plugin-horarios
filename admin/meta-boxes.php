<?php
/**
 * Funciones para añadir y gestionar los Meta Boxes personalizados en el área de administración.
 *
 * Principalmente, el meta box para la gestión de horarios en la pantalla de edición del CPT Maestro.
 *
 * @package MiPluginHorarios/Admin
 * @version 1.0.0
 */

// Salida de seguridad: Evita el acceso directo al archivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Registra el Meta Box 'Gestión de Horarios' para el CPT 'maestro'.
 */
function mph_add_horarios_meta_box() {
    add_meta_box(
        'mph_gestion_horarios_metabox',
        __( 'Gestión de Horarios del Maestro', 'mi-plugin-horarios' ),
        'mph_render_horarios_meta_box_content',
        'maestro',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_maestro', 'mph_add_horarios_meta_box' );

/**
 * Renderiza el contenido HTML del Meta Box 'Gestión de Horarios'.
 */
function mph_render_horarios_meta_box_content( $post ) {
    wp_nonce_field( 'mph_gestionar_horarios_nonce', 'mph_horarios_nonce' );
    ?>
    <div class="mph-gestion-horarios-wrapper">

        <p>
            <button type="button" id="mph-abrir-modal-horario" class="button button-primary">
                <?php esc_html_e( 'Añadir/Gestionar Disponibilidad', 'mi-plugin-horarios' ); ?>
            </button>
            <span class="spinner" style="float: none; vertical-align: middle;"></span>
        </p>

        <hr>

        <h3><?php esc_html_e( 'Horarios Registrados', 'mi-plugin-horarios' ); ?></h3>

        <div id="mph-tabla-horarios-container">
            <?php
            // Comprobar si la función existe (por si acaso) y llamarla
            if ( function_exists( 'mph_get_horarios_table_html' ) ) {
                echo mph_get_horarios_table_html( $post->ID ); // Llamar a la función
            } else {
                // Mensaje de fallback si la función no se cargó
                echo '<p style="color:red;">' . esc_html__( 'Error: La función para mostrar la tabla no está disponible.', 'mi-plugin-horarios' ) . '</p>';
            }
            ?>
        </div>

        <?php // Incluimos aquí el HTML del Modal (inicialmente oculto) ?>
        <?php // mph_render_horario_modal_html( $post ); ?>

    </div> <?php // Fin de .mph-gestion-horarios-wrapper ?>
    <?php
}

/**
 * Renderiza el HTML del Modal para añadir/editar horarios.
 */
function mph_render_horario_modal_html( $post ) {
    ?>
    <div id="mph-modal-horario" title="<?php esc_attr_e( 'Añadir/Editar Disponibilidad', 'mi-plugin-horarios' ); ?>" style="display: none;">
        <form id="mph-form-horario"> <?php // <-- Formulario AQUI ?>

            <input type="hidden" id="mph_horario_id_editando" name="horario_id" value="">
            <input type="hidden" id="mph_maestro_id" name="maestro_id" value="<?php echo esc_attr( $post->ID ); ?>">

            <div class="mph-modal-seccion mph-disponibilidad-general">
                <h4><?php esc_html_e( '1. Disponibilidad General', 'mi-plugin-horarios' ); ?></h4>
                <p>
                    <label for="mph_dia_semana"><?php esc_html_e( 'Día de la Semana:', 'mi-plugin-horarios' ); ?></label><br>
                    <select id="mph_dia_semana" name="dia_semana" required>
                        <option value=""><?php esc_html_e( '-- Seleccionar Día --', 'mi-plugin-horarios' ); ?></option>
                        <option value="1"><?php esc_html_e( 'Lunes', 'mi-plugin-horarios' ); ?></option>
                        <option value="2"><?php esc_html_e( 'Martes', 'mi-plugin-horarios' ); ?></option>
                        <option value="3"><?php esc_html_e( 'Miércoles', 'mi-plugin-horarios' ); ?></option>
                        <option value="4"><?php esc_html_e( 'Jueves', 'mi-plugin-horarios' ); ?></option>
                        <option value="5"><?php esc_html_e( 'Viernes', 'mi-plugin-horarios' ); ?></option>
                        <option value="6"><?php esc_html_e( 'Sábado', 'mi-plugin-horarios' ); ?></option>
                        <option value="7"><?php esc_html_e( 'Domingo', 'mi-plugin-horarios' ); ?></option>
                    </select>
                </p>
                <p>
                    <label for="mph_hora_inicio_general"><?php esc_html_e( 'Hora Inicio:', 'mi-plugin-horarios' ); ?></label>
                    <input type="time" id="mph_hora_inicio_general" name="hora_inicio_general" required step="1800">
                    <label for="mph_hora_fin_general"><?php esc_html_e( 'Hora Fin:', 'mi-plugin-horarios' ); ?></label>
                    <input type="time" id="mph_hora_fin_general" name="hora_fin_general" required step="1800">
                    <small class="mph-error-hora" style="color: red; display: none;"><?php esc_html_e( 'La hora de fin debe ser posterior a la hora de inicio.', 'mi-plugin-horarios' ); ?></small>
                    <small class="mph-error-duplicado" style="color: red; display: none;"><?php esc_html_e( 'Ya existe un bloque de disponibilidad general para este día y hora.', 'mi-plugin-horarios' ); ?></small>
                </p>
                <p><strong><?php esc_html_e( 'Capacidades del Maestro en este Bloque:', 'mi-plugin-horarios' ); ?></strong><br>
                   <small><?php esc_html_e( '(Solo se muestran las opciones asignadas a este maestro y las comunes)', 'mi-plugin-horarios' ); ?></small>
                </p>
                <fieldset class="mph-checkbox-group">
                     <legend><?php esc_html_e( 'Programas Admisibles:', 'mi-plugin-horarios' ); ?></legend>
                     <div id="mph-programas-admisibles-container">
                         <?php mph_render_taxonomy_checkboxes('programa', wp_get_object_terms( $post->ID, 'programa', array('fields' => 'ids') )); ?>
                     </div>
                </fieldset>
                <fieldset class="mph-checkbox-group">
                     <legend><?php esc_html_e( 'Sedes Admisibles:', 'mi-plugin-horarios' ); ?></legend>
                     <div id="mph-sedes-admisibles-container">
                        <?php mph_render_taxonomy_checkboxes('sede', wp_get_object_terms( $post->ID, 'sede', array('fields' => 'ids') )); ?>
                     </div>
                </fieldset>
                <fieldset class="mph-checkbox-group">
                    <legend><?php esc_html_e( 'Rangos de Edad Admisibles:', 'mi-plugin-horarios' ); ?></legend>
                     <div id="mph-rangos-admisibles-container">
                        <?php mph_render_taxonomy_checkboxes('rango_edad', wp_get_object_terms( $post->ID, 'rango_edad', array('fields' => 'ids') )); ?>
                     </div>
                </fieldset>
                <p>
                    <button type="button" id="mph-mostrar-asignacion" class="button">
                        <?php esc_html_e( 'Asignar Horario Específico (Opcional)', 'mi-plugin-horarios' ); ?>
                    </button>
                </p>
            </div>
            <div class="mph-modal-seccion mph-asignacion-especifica" style="display: none; margin-top: 20px; padding-top: 15px; border-top: 1px dashed #ccc;">
                 <h4><?php esc_html_e( '2. Asignación Específica (Opcional)', 'mi-plugin-horarios' ); ?></h4>
                 <p><small><?php esc_html_e( 'Define una clase o bloqueo específico dentro del rango general anterior.', 'mi-plugin-horarios' ); ?></small></p>
                 <p>
                    <label for="mph_hora_inicio_asignada"><?php esc_html_e( 'Hora Inicio Asignada:', 'mi-plugin-horarios' ); ?></label>
                    <input type="time" id="mph_hora_inicio_asignada" name="hora_inicio_asignada" step="1800">
                    <label for="mph_hora_fin_asignada"><?php esc_html_e( 'Hora Fin Asignada:', 'mi-plugin-horarios' ); ?></label>
                    <input type="time" id="mph_hora_fin_asignada" name="hora_fin_asignada" step="1800">
                    <small class="mph-error-hora-asignada" style="color: red; display: none;"><?php esc_html_e( 'Horas fuera del rango general o inválidas.', 'mi-plugin-horarios' ); ?></small>
                 </p>
                 <p>
                     <label for="mph_programa_asignado"><?php esc_html_e( 'Programa Asignado:', 'mi-plugin-horarios' ); ?></label><br>
                     <select id="mph_programa_asignado" name="programa_asignado">
                        <option value=""><?php esc_html_e( '-- Seleccionar Programa --', 'mi-plugin-horarios' ); ?></option>
                     </select>
                 </p>
                 <p>
                     <label for="mph_sede_asignada"><?php esc_html_e( 'Sede Asignada:', 'mi-plugin-horarios' ); ?></label><br>
                     <select id="mph_sede_asignada" name="sede_asignada">
                         <option value=""><?php esc_html_e( '-- Seleccionar Sede --', 'mi-plugin-horarios' ); ?></option>
                     </select>
                 </p>
                 <p>
                     <label for="mph_rango_edad_asignado"><?php esc_html_e( 'Rango Edad Asignado:', 'mi-plugin-horarios' ); ?></label><br>
                     <select id="mph_rango_edad_asignado" name="rango_edad_asignado">
                         <option value=""><?php esc_html_e( '-- Seleccionar Rango --', 'mi-plugin-horarios' ); ?></option>
                     </select>
                 </p>
                 <p>
                     <label for="mph_vacantes"><?php esc_html_e( 'Vacantes:', 'mi-plugin-horarios' ); ?></label>
                     <input type="number" id="mph_vacantes" name="vacantes" min="0" step="1" value="1" style="width: 60px;">
                 </p>
                 <fieldset class="mph-buffer-tiempo">
                     <legend><?php esc_html_e( 'Tiempo "Mismo o Traslado":', 'mi-plugin-horarios' ); ?></legend>
                     <p>
                         <label for="mph_buffer_minutos_antes">
                             <?php esc_html_e( 'Antes:', 'mi-plugin-horarios' ); ?>
                             <input type="number" id="mph_buffer_minutos_antes" name="buffer_minutos_antes" min="0" step="15" value="60" style="width: 70px;">
                             <?php esc_html_e( 'minutos', 'mi-plugin-horarios' ); ?>
                         </label>
                     </p>
                     <p>
                         <label for="mph_buffer_minutos_despues">
                             <?php esc_html_e( 'Después:', 'mi-plugin-horarios' ); ?>
                             <input type="number" id="mph_buffer_minutos_despues" name="buffer_minutos_despues" min="0" step="15" value="60" style="width: 70px;">
                             <?php esc_html_e( 'minutos', 'mi-plugin-horarios' ); ?>
                         </label>
                     </p>
                     <p>
                         <label for="mph_buffer_linkeado">
                             <input type="checkbox" id="mph_buffer_linkeado" name="buffer_linkeado" value="1" checked="checked">
                             <?php esc_html_e( 'Mantener tiempos iguales (linkeados)', 'mi-plugin-horarios' ); ?>
                         </label>
                     </p>
                      <small><?php esc_html_e( '(Tiempo reservado alrededor de la clase asignada)', 'mi-plugin-horarios' ); ?></small>
                 </fieldset>
            </div>
            <div class="mph-modal-acciones" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ccc;">
                <button type="submit" id="mph-guardar-horario" class="button button-primary">
                    <?php esc_html_e( 'Guardar Disponibilidad/Asignación', 'mi-plugin-horarios' ); ?>
                </button>
                <button type="button" id="mph-cancelar-modal" class="button">
                     <?php esc_html_e( 'Cancelar', 'mi-plugin-horarios' ); ?>
                 </button>
                 <span class="spinner" style="float: none; vertical-align: middle;"></span>
                 <div class="mph-modal-feedback" style="color: green; display: none; margin-top: 10px;"></div>
                 <div class="mph-modal-error" style="color: red; display: none; margin-top: 10px;"></div>
            </div>
        </form> <?php // <-- CIERRE Formulario AQUI ?>
    </div> <?php // Fin de #mph-modal-horario ?>
    <?php
}

/**
 * Función auxiliar para renderizar checkboxes de una taxonomía.
 * Muestra los términos asignados al maestro Y los términos comunes.
 * Pre-selecciona los comunes.
 */
function mph_render_taxonomy_checkboxes( $taxonomy_slug, $assigned_term_ids ) {
    // Corregir slug si es necesario (basado en tu comentario anterior)
    if ($taxonomy_slug === 'rango_edad') {
        $taxonomy_slug = 'rango_edad'; // Asegurar el slug correcto de la taxonomía
    }

    // 1. Obtener términos comunes para esta taxonomía
    $common_field_key = $taxonomy_slug . '_comun';
    if ($taxonomy_slug === 'rango_edad') {
        $common_field_key = 'rango_edad_comun'; // Nombre de campo ACF específico si es diferente
    } elseif ($taxonomy_slug === 'sede') {
        $common_field_key = 'sede_comun'; // Nombre de campo ACF específico si es diferente
    } elseif ($taxonomy_slug === 'programa') {
        $common_field_key = 'programa_comun';
    }

    $common_terms_query_args = array(
        'taxonomy'   => $taxonomy_slug,
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'     => $common_field_key,
                'value'   => '1',
                'compare' => '=',
            ),
        ),
        'fields' => 'ids',
    );
    $common_term_ids = get_terms( $common_terms_query_args );
    if ( is_wp_error( $common_term_ids ) ) {
        error_log("Error get_terms comunes para $taxonomy_slug: " . $common_term_ids->get_error_message()); // Log de error
        $common_term_ids = array();
    }

    // 2. Combinar IDs asignados y comunes (sin duplicados)
    $all_relevant_term_ids = array_unique( array_merge( (array) $assigned_term_ids, (array) $common_term_ids ) );

    if ( empty( $all_relevant_term_ids ) ) {
        echo '<em>' . sprintf( esc_html__( 'No hay %s asignados o comunes.', 'mi-plugin-horarios' ), str_replace('_', ' ', $taxonomy_slug) ) . '</em>';
        return;
    }

    // 3. Obtener los objetos de término completos para los IDs relevantes
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy_slug,
        'hide_empty' => false,
        'include'    => $all_relevant_term_ids,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        error_log("Error get_terms final para $taxonomy_slug: " . (is_wp_error($terms) ? $terms->get_error_message() : 'Empty terms')); // Log de error
        echo '<em>' . sprintf( esc_html__( 'No se encontraron %s aplicables.', 'mi-plugin-horarios' ), str_replace('_', ' ', $taxonomy_slug) ) . '</em>';
        return;
    }

    // 4. Renderizar los checkboxes, marcando los comunes
    echo '<ul>';
    foreach ( $terms as $term ) {
        $field_name = esc_attr( $taxonomy_slug ) . '_admisibles[]';
        $field_id = esc_attr( $taxonomy_slug . '_' . $term->term_id );
        $is_common_term = in_array( $term->term_id, $common_term_ids );
        $checked_attr = $is_common_term ? ' checked="checked"' : '';

        echo '<li>';
        echo '<label for="' . $field_id . '">';
        echo '<input type="checkbox" name="' . $field_name . '" id="' . $field_id . '" value="' . esc_attr( $term->term_id ) . '"' . $checked_attr .'> ';
        echo esc_html( $term->name );
        if ($is_common_term) {
             echo ' <small>(' . esc_html__('Común', 'mi-plugin-horarios') . ')</small>';
        }
        echo '</label>';
        echo '</li>';
    }
    echo '</ul>';
}

/**
 * Añade el HTML del modal al footer del admin en la pantalla de edición del maestro.
 */
function mph_add_modal_to_admin_footer() {
    // Obtener la pantalla actual
    $screen = get_current_screen();

    // Comprobar si estamos en la pantalla de edición del CPT 'maestro'
    if ( $screen && $screen->post_type == 'maestro' && $screen->base == 'post' ) {
        global $post; // Asegurar que $post está disponible
        if ( $post ) {
            mph_render_horario_modal_html( $post ); // Llama a la función que renderiza el modal
        }
    }
}
add_action( 'admin_footer', 'mph_add_modal_to_admin_footer' );
