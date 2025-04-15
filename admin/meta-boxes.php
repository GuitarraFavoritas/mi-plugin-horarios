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
 *
 * Esta función se engancha a 'add_meta_boxes' para añadir nuestro meta box personalizado.
 */
function mph_add_horarios_meta_box() {
    add_meta_box(
        'mph_gestion_horarios_metabox',                 // ID único del meta box.
        __( 'Gestión de Horarios del Maestro', 'mi-plugin-horarios' ), // Título visible del meta box.
        'mph_render_horarios_meta_box_content',         // Función callback que renderiza el HTML del meta box.
        'maestro',                                      // Pantalla (post type) donde se mostrará ('maestro').
        'normal',                                       // Contexto donde aparecerá ('normal', 'side', 'advanced'). 'normal' es el área principal.
        'high'                                          // Prioridad ('high', 'core', 'default', 'low'). 'high' lo sitúa más arriba.
    );
}
add_action( 'add_meta_boxes_maestro', 'mph_add_horarios_meta_box' ); // Engancha específicamente para el CPT 'maestro'.

/**
 * Renderiza el contenido HTML del Meta Box 'Gestión de Horarios'.
 *
 * Esta función es llamada por WordPress para generar el HTML dentro de nuestro meta box.
 * Incluye el botón para abrir el modal y un contenedor para la tabla de horarios existentes.
 *
 * @param WP_Post $post El objeto del post actual (en este caso, el maestro).
 */
function mph_render_horarios_meta_box_content( $post ) {
    // Añadir un nonce field para seguridad en futuras acciones AJAX desde este meta box.
    wp_nonce_field( 'mph_gestionar_horarios_nonce', 'mph_horarios_nonce' );

    ?>
    <div class="mph-gestion-horarios-wrapper">

        <p>
            <button type="button" id="mph-abrir-modal-horario" class="button button-primary">
                <?php esc_html_e( 'Añadir/Gestionar Disponibilidad', 'mi-plugin-horarios' ); ?>
            </button>
            <span class="spinner" style="float: none; vertical-align: middle;"></span> <?php // Spinner para feedback visual en AJAX ?>
        </p>

        <hr> <?php // Separador visual ?>

        <h3><?php esc_html_e( 'Horarios Registrados', 'mi-plugin-horarios' ); ?></h3>

        <div id="mph-tabla-horarios-container">
            <?php
            // Cargamos la tabla inicial aquí llamando a la función que la genera.
            // Definiremos esta función más adelante en la Fase 3,
            // pero preparamos la llamada ahora.
            if ( function_exists( 'mph_get_horarios_table_html' ) ) {
                echo mph_get_horarios_table_html( $post->ID );
            } else {
                // Mensaje provisional hasta que la función exista
                echo '<p>' . esc_html__( 'La tabla de horarios se cargará aquí.', 'mi-plugin-horarios' ) . '</p>';
                // Podríamos incluso hacer una llamada AJAX aquí para cargar la tabla inicialmente
                // si preveemos que puede ser muy pesada, pero empezar así es más simple.
            }
            ?>
        </div>

        <?php // Incluimos aquí el HTML del Modal (inicialmente oculto) ?>
        <?php mph_render_horario_modal_html( $post ); ?>

    </div> <?php // Fin de .mph-gestion-horarios-wrapper ?>
    <?php
}

/**
 * Renderiza el HTML del Modal para añadir/editar horarios.
 *
 * Este modal estará oculto por defecto y se mostrará con JavaScript.
 * Contiene todos los campos necesarios para definir disponibilidad general y asignaciones.
 *
 * @param WP_Post $post El objeto del post actual (maestro).
 */
function mph_render_horario_modal_html( $post ) {
    // Obtenemos las taxonomías asignadas a ESTE maestro para pre-filtrar opciones.
    $maestro_id = $post->ID;
    $programas_maestro = wp_get_object_terms( $maestro_id, 'programa', array('fields' => 'ids') );
    $sedes_maestro = wp_get_object_terms( $maestro_id, 'sede', array('fields' => 'ids') );
    $rangos_maestro = wp_get_object_terms( $maestro_id, 'rango_edad', array('fields' => 'ids') );

    // Necesitaremos obtener TODOS los términos para mostrarlos, pero filtraremos en JS o aquí.
    // Por simplicidad ahora, pasaremos los IDs asignados al JS.

    ?>
    <div id="mph-modal-horario" title="<?php esc_attr_e( 'Añadir/Editar Disponibilidad', 'mi-plugin-horarios' ); ?>" style="display: none;">
        <form id="mph-form-horario">

            <?php // Campo oculto para el ID del horario que se está editando (si aplica) ?>
            <input type="hidden" id="mph_horario_id_editando" name="horario_id" value="">
            <?php // Campo oculto para el ID del maestro (siempre necesario) ?>
            <input type="hidden" id="mph_maestro_id" name="maestro_id" value="<?php echo esc_attr( $maestro_id ); ?>">

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
                    <input type="time" id="mph_hora_inicio_general" name="hora_inicio_general" required step="1800"> <?php // step="1800" = incrementos de 30 min ?>

                    <label for="mph_hora_fin_general"><?php esc_html_e( 'Hora Fin:', 'mi-plugin-horarios' ); ?></label>
                    <input type="time" id="mph_hora_fin_general" name="hora_fin_general" required step="1800">
                    <small class="mph-error-hora" style="color: red; display: none;"><?php esc_html_e( 'La hora de fin debe ser posterior a la hora de inicio.', 'mi-plugin-horarios' ); ?></small>
                    <small class="mph-error-duplicado" style="color: red; display: none;"><?php esc_html_e( 'Ya existe un bloque de disponibilidad general para este día y hora.', 'mi-plugin-horarios' ); ?></small>
                </p>

                <p><strong><?php esc_html_e( 'Capacidades del Maestro en este Bloque:', 'mi-plugin-horarios' ); ?></strong><br>
                   <small><?php esc_html_e( '(Solo se muestran las opciones asignadas a este maestro)', 'mi-plugin-horarios' ); ?></small>
                </p>

                <fieldset class="mph-checkbox-group">
                     <legend><?php esc_html_e( 'Programas Admisibles:', 'mi-plugin-horarios' ); ?></legend>
                     <div id="mph-programas-admisibles-container">
                         <?php // Los checkboxes se cargarán aquí (o se podrían pre-renderizar) ?>
                         <?php mph_render_taxonomy_checkboxes('programa', $programas_maestro); ?>
                     </div>
                </fieldset>

                <fieldset class="mph-checkbox-group">
                     <legend><?php esc_html_e( 'Sedes Admisibles:', 'mi-plugin-horarios' ); ?></legend>
                     <div id="mph-sedes-admisibles-container">
                        <?php mph_render_taxonomy_checkboxes('sede', $sedes_maestro); ?>
                     </div>
                </fieldset>

                <fieldset class="mph-checkbox-group">
                    <legend><?php esc_html_e( 'Rangos de Edad Admisibles:', 'mi-plugin-horarios' ); ?></legend>
                     <div id="mph-rangos-admisibles-container">
                        <?php mph_render_taxonomy_checkboxes('rango_edad', $rangos_maestro); ?>
                     </div>
                </fieldset>

                <p>
                    <button type="button" id="mph-mostrar-asignacion" class="button">
                        <?php esc_html_e( 'Asignar Horario Específico (Opcional)', 'mi-plugin-horarios' ); ?>
                    </button>
                </p>

            </div> <?php // Fin de .mph-disponibilidad-general ?>

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
                         <?php // Las opciones se poblarán dinámicamente con JS basadas en los programas admisibles seleccionados arriba ?>
                     </select>
                 </p>

                 <p>
                     <label for="mph_sede_asignada"><?php esc_html_e( 'Sede Asignada:', 'mi-plugin-horarios' ); ?></label><br>
                     <select id="mph_sede_asignada" name="sede_asignada">
                         <option value=""><?php esc_html_e( '-- Seleccionar Sede --', 'mi-plugin-horarios' ); ?></option>
                          <?php // Opciones pobladas dinámicamente con JS ?>
                     </select>
                 </p>

                 <p>
                     <label for="mph_rango_edad_asignado"><?php esc_html_e( 'Rango Edad Asignado:', 'mi-plugin-horarios' ); ?></label><br>
                     <select id="mph_rango_edad_asignado" name="rango_edad_asignado">
                         <option value=""><?php esc_html_e( '-- Seleccionar Rango --', 'mi-plugin-horarios' ); ?></option>
                          <?php // Opciones pobladas dinámicamente con JS ?>
                     </select>
                 </p>

                 <p>
                     <label for="mph_vacantes"><?php esc_html_e( 'Vacantes:', 'mi-plugin-horarios' ); ?></label>
                     <input type="number" id="mph_vacantes" name="vacantes" min="0" step="1" value="1" style="width: 60px;">
                 </p>

                 <p>
                     <label for="mph_buffer_tiempo"><?php esc_html_e( 'Tiempo "Mismo o Traslado" (Antes y Después):', 'mi-plugin-horarios' ); ?></label><br>
                     <input type="number" id="mph_buffer_minutos" name="buffer_minutos" min="0" step="15" value="60" style="width: 70px;"> <?php esc_html_e( 'minutos', 'mi-plugin-horarios' ); ?>
                     <small><?php esc_html_e( '(Tiempo reservado alrededor de la clase asignada)', 'mi-plugin-horarios' ); ?></small>
                 </p>

            </div> <?php // Fin de .mph-asignacion-especifica ?>

            <div class="mph-modal-acciones" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ccc;">
                <button type="submit" id="mph-guardar-horario" class="button button-primary">
                    <?php esc_html_e( 'Guardar Disponibilidad/Asignación', 'mi-plugin-horarios' ); ?>
                </button>
                <button type="button" id="mph-cancelar-modal" class="button">
                     <?php esc_html_e( 'Cancelar', 'mi-plugin-horarios' ); ?>
                 </button>
                 <span class="spinner" style="float: none; vertical-align: middle;"></span> <?php // Spinner para feedback de guardado ?>
                 <div class="mph-modal-feedback" style="color: green; display: none; margin-top: 10px;"></div>
                 <div class="mph-modal-error" style="color: red; display: none; margin-top: 10px;"></div>
            </div>

        </form>
    </div> <?php // Fin de #mph-modal-horario ?>
    <?php
}


/**
 * Función auxiliar para renderizar checkboxes de una taxonomía.
 * Muestra solo los términos asignados al maestro actual.
 *
 * @param string $taxonomy_slug Slug de la taxonomía (ej. 'programa').
 * @param array $allowed_term_ids Array de IDs de términos permitidos para este maestro.
 */
function mph_render_taxonomy_checkboxes( $taxonomy_slug, $allowed_term_ids ) {
    if ( empty( $allowed_term_ids ) ) {
         echo '<em>' . esc_html__( 'No hay términos asignados a este maestro.', 'mi-plugin-horarios' ) . '</em>';
         return;
    }

    $terms = get_terms( array(
        'taxonomy'   => $taxonomy_slug,
        'hide_empty' => false, // Mostrar todos, incluso si no tienen posts asociados aún
        'include'    => $allowed_term_ids, // ¡Importante! Solo incluye los asignados al maestro
    ) );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        echo '<em>' . esc_html__( 'No se encontraron términos aplicables.', 'mi-plugin-horarios' ) . '</em>';
        return;
    }

    echo '<ul>';
    foreach ( $terms as $term ) {
        // Usamos name="taxonomia_slug[]" para que PHP reciba un array de los IDs seleccionados.
        $field_name = esc_attr( $taxonomy_slug ) . '_admisibles[]';
        $field_id = esc_attr( $taxonomy_slug . '_' . $term->term_id );
        echo '<li>';
        echo '<label for="' . $field_id . '">';
        echo '<input type="checkbox" name="' . $field_name . '" id="' . $field_id . '" value="' . esc_attr( $term->term_id ) . '"> ';
        echo esc_html( $term->name );
        echo '</label>';
        echo '</li>';
    }
    echo '</ul>';
}

?>