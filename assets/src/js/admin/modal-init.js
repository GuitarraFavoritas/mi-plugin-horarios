// assets/src/js/admin/modal-init.js
import $ from 'jquery';

// Variables para selectores comunes dentro del modal (podrían moverse aquí o dejarse globales en main si se prefiere)
const $feedbackModal = $('#mph-modal-horario .mph-modal-feedback');
const $errorModal = $('#mph-modal-horario .mph-modal-error');
const $spinnerModal = $('#mph-modal-horario .mph-modal-acciones .spinner');
// ... otros selectores de campos si se usan en resetModalForm

let resetCallback = null; // Para guardar la función de reseteo pasada

/**
 * Inicializa el jQuery UI Dialog.
 * @param {string} modalSelector - Selector CSS para el div del modal.
 * @param {function} closeCallback - Función a llamar al cerrar.
 */
export function initDialog(modalSelector, closeCallback) {
    const $modal = $(modalSelector);
    if (!$modal.length) {
        console.error(`Error Crítico: Elemento modal "${modalSelector}" no encontrado.`);
        return;
    }

    resetCallback = closeCallback; // Guardar callback

    $modal.dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        height: 'auto',
        draggable: true,
        resizable: false,
        closeText: "Cerrar",
        classes: { "ui-dialog": "mph-jquery-ui-modal" },
        open: function() {
            console.log("Evento 'open' del diálogo disparado.");
            $feedbackModal.hide().text('');
            $errorModal.hide().text('');
            $spinnerModal.removeClass('is-active');
        },
        close: function() {
            console.log("Evento 'close' del diálogo disparado.");
            if (typeof resetCallback === 'function') {
                resetCallback($(this)); // Llamar al callback de reseteo pasando el elemento
            }
        }
    });
    console.log(`Diálogo "${modalSelector}" inicializado.`);
}

/**
 * Abre el diálogo especificado.
 * @param {string} modalSelector - Selector CSS para el div del modal.
 */
export function openModal(modalSelector) {
    const $modal = $(modalSelector);
     if ($modal.length && $modal.hasClass('ui-dialog-content')) { // Verificar si ya está inicializado
        console.log(`Abriendo modal "${modalSelector}"...`);
        try {
             $modal.dialog('open');
             console.log("Modal abierto.");
         } catch(e) {
             console.error("Error al abrir diálogo:", e);
         }
     } else {
         console.error(`Modal "${modalSelector}" no encontrado o no inicializado.`);
     }
}

 /**
 * Cierra el diálogo especificado.
 * @param {string} modalSelector - Selector CSS para el div del modal.
 */
 export function closeModal(modalSelector) {
    const $modal = $(modalSelector);
     if ($modal.length && $modal.hasClass('ui-dialog-content')) {
         console.log(`Cerrando modal "${modalSelector}"...`);
        $modal.dialog('close');
     }
 }


/**
 * Resetea el formulario dentro del modal.
 * @param {jQuery} $dialogElement - El objeto jQuery del elemento del diálogo.
 */
export function resetModalForm($dialogElement) {
    console.log('Intentando resetear formulario...');
     if (!$dialogElement || !$dialogElement.length) {console.error("resetModalForm: No se recibió $dialogElement."); return; }
    const $form = $dialogElement.find('form#mph-form-horario');
    if (!$form.length) {
        console.error('Error en resetModalForm: No se encontró form#mph-form-horario dentro del contexto.');
        // Loggear contenido si falla
        console.log("Contenido HTML dentro del contexto:", $dialogElement.html());
        console.log("Hijos dentro del contexto:", $dialogElement.children());
        return; }
    console.log('Formulario encontrado en resetModalForm. Reseteando...');
    try {
        $form[0].reset(); // Reset nativo
        $form.find('#mph_horario_id_editando').val('');

        // --- Restaurar Visibilidad ---

        $dialogElement.removeClass('mph-modal-mode-edit-vacantes mph-modal-mode-edit-disp mph-modal-mode-assign'); // Quitar clases de modo

       $dialogElement.find('#mph-editar-info').hide().empty();
        $form.find('.mph-modal-seccion.mph-disponibilidad-general').show(); // Mostrar sección general
        $form.find('.mph-modal-seccion.mph-asignacion-especifica').hide(); // Ocultar sección asignación
        $form.find('#mph-mostrar-asignacion').show(); // Mostrar botón toggle
        /* Finaliza Modificación */

        // Reset checkboxes comunes (requiere mph_admin_obj disponible globalmente o pasado)
        if (window.mph_admin_obj) {
            $form.find('#mph-programas-admisibles-container input[type="checkbox"]').each(function(){
                 const termId = $(this).val();
                 const programaData = mph_admin_obj.todos_programas.find(p => p.term_id == termId);
                 $(this).prop('checked', (programaData && programaData.es_comun));
            });
             $form.find('#mph-sedes-admisibles-container input[type="checkbox"]').each(function(){
                 const termId = $(this).val();
                 const sedeData = mph_admin_obj.todas_sedes.find(s => s.term_id == termId);
                 $(this).prop('checked', (sedeData && sedeData.es_comun));
            });
             $form.find('#mph-rangos-admisibles-container input[type="checkbox"]').each(function(){
                 const termId = $(this).val();
                 const rangoData = mph_admin_obj.todos_rangos.find(r => r.term_id == termId);
                  $(this).prop('checked', (rangoData && rangoData.es_comun));
            });
        } else {
            console.warn("mph_admin_obj no disponible para resetear checkboxes comunes.");
        }

        // Limpiar selects y ocultar sección
        $form.find('#mph_programa_asignado, #mph_sede_asignada, #mph_rango_edad_asignado').empty().append($('<option>', { value: '', text: '-- Seleccionar --' })); // Texto genérico

        // Resetear errores y valores por defecto
        $dialogElement.find('.mph-error-hora, .mph-error-duplicado, .mph-error-hora-asignada').hide();
        $dialogElement.find('.mph-modal-feedback, .mph-modal-error').hide().text('');
        $form.find('#mph_vacantes').val(1); // Restaurar valor vacantes
        $form.find('#mph_buffer_minutos_antes').val(60);
        $form.find('#mph_buffer_minutos_despues').val(60);
        $form.find('#mph_buffer_linkeado').prop('checked', true);

        // Restaurar Botón Guardar
        const $btnGuardar = $form.find('#mph-guardar-horario');
        if ($btnGuardar.length) {
            const textoOriginal = window.mph_admin_obj?.i18n?.guardar_horario || 'Guardar Disponibilidad/Asignación';
            $btnGuardar.text(textoOriginal);
            $btnGuardar.removeAttr('data-action-mode'); // Limpiar modo
        }
         console.log("Visibilidad y botón guardar restaurados a default.");

    } catch (e) { console.error("Error durante resetModalForm:", e); }
} // Fin resetModalForm