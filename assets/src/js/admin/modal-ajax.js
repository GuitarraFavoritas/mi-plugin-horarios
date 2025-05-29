// assets/src/js/admin/modal-ajax.js
import $ from 'jquery';
import { validarFormulario } from './modal-validation';
import { closeModal } from './modal-init';

/**
 * Inicializa el manejador de envío AJAX para el formulario del modal.
 * @param {jQuery} $modal - El objeto jQuery del elemento modal.
 */
export function initAjaxSubmit($modal) {
    console.log(`Inicializando manejador CLICK para #mph-guardar-horario...`);
     if (!$modal || !$modal.length || typeof $modal.find !== 'function') {
         console.error("initAjaxSubmit: No se recibió el objeto $modal válido.");
         return;
      }

      const $spinnerModal = $modal.find('.mph-modal-acciones .spinner');
      const $feedbackModal = $modal.find('.mph-modal-feedback');
      const $errorModal = $modal.find('.mph-modal-error');
      const $tablaContainer = $('#mph-tabla-horarios-container'); // Asumimos que existe fuera del modal

    $modal.on('click', '#mph-guardar-horario', function(e) {
         console.log('--- Botón Guardar Clickeado ---');

         console.log('Estado de window.mph_admin_obj JUSTO AL HACER CLICK:', window.mph_admin_obj);
         const $button = $(this);
         const $form = $modal.find('form#mph-form-horario');

         if (!$form.length) {
             console.error("Error en click Guardar: No se encontró el formulario.");
             alert("Error interno del formulario.");
             return;
         }         

        const actionMode = $button.attr('data-action-mode') || 'save_full';
         console.log("Modo de Acción (leído del botón):", actionMode);

         // --- Validación ---
         let isValid = false;
         if (actionMode === 'update_vacantes') {
             console.log("Validando solo vacantes...");
             const $vacantesInput = $form.find('#mph_vacantes'); // Buscar input
             const vacantesVal = parseInt($vacantesInput.val(), 10);
             // Validar si es número y no negativo
             if ( $vacantesInput.length && !isNaN(vacantesVal) && vacantesVal >= 0 ) {
                 isValid = true;
                 if ($errorModal.length) $errorModal.hide().text(''); // Limpiar error si es válido
             } else {
                 isValid = false;
                 if ($errorModal.length) $errorModal.text(window.mph_admin_obj?.i18n?.error_vacantes_negativas || 'Las vacantes deben ser un número positivo.').show();
             }
             console.log("Resultado Validación Vacantes:", isValid);
         } else { // save_full, assign_to_existing, edit_existing_disp
             console.log('Llamando a validarFormulario (modo completo)...');
             isValid = validarFormulario($form); // Usar validación completa
             if (!isValid) console.log('validarFormulario (modo completo) devolvió false.');
         }

         if (!isValid) { return; } // Detener si no es válido

        console.log('Validación pasada. Procediendo con AJAX...');

        // Feedback visual
        if ($spinnerModal.length) $spinnerModal.addClass('is-active');
        if ($feedbackModal.length) $feedbackModal.hide().text('');
        if ($errorModal.length) $errorModal.hide().text('');
        $button.prop('disabled', true);

        console.log('Spinner y botón modificados (intentado). Preparando datos AJAX...');
        const formData = $form.serialize();

        // Acceso seguro al objeto global mph_admin_obj
        if ( !window.mph_admin_obj || typeof window.mph_admin_obj.ajax_url === 'undefined' || window.mph_admin_obj.ajax_url === '' ) {
            console.error("Error crítico: window.mph_admin_obj o window.mph_admin_obj.ajax_url no están definidos o están vacíos.");
            if ($errorModal.length) $errorModal.text("Error de configuración interna (AJAX URL). Contacte al administrador.").show();
            $button.prop('disabled', false);
            if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
            return; // Detener si falta
        }

        // --- Preparar Datos AJAX según el modo ---
        let dataToSend = '';
        let ajaxAction = ''; // Variable para la acción

        let nonceValue = '';
        let nonceFieldName = '';    
        if (actionMode === 'update_vacantes') {
            ajaxAction = 'mph_actualizar_vacantes';
            nonceFieldName = 'mph_nonce_actualizar_vacantes'; // Nombre del nuevo nonce field
            const $nonceField = $form.find('input[name="' + nonceFieldName + '"]');
            if ($nonceField.length) { nonceValue = $nonceField.val(); }
             else { 
                console.error("Campo Nonce para Actualizar Vacantes no encontrado en el formulario!");
                if ($errorModal.length) $errorModal.text(window.mph_admin_obj?.i18n?.error_seguridad_interna /*|| "Error de seguridad interno (Nonce UV)."*/).show();
                $button.prop('disabled', false); if ($spinnerModal.length) $spinnerModal.removeClass('is-active'); 
                return; }

            const horarioId = $form.find('#mph_horario_id_editando').val();
            const vacantesVal = $form.find('#mph_vacantes').val();
            dataToSend = { // Construir objeto
                action: ajaxAction,
                horario_id: horarioId,
                vacantes: vacantesVal,
                [nonceFieldName]: nonceValue // Incluir nonce específico
            };
             console.log('Datos a enviar (update_vacantes):', dataToSend);


        } else { // save_full, assign_to_existing, edit_existing_disp
             console.log(`Preparando datos para ${actionMode}...`);
             ajaxAction = 'mph_guardar_horario_maestro';
             const formData = $form.serialize(); // Incluye horario_id si está en el form
             dataToSend = formData + '&action=' + ajaxAction;
             console.log(`Datos COMPLETOS a enviar (${actionMode}):`, dataToSend);
        }       

        // --- Enviar Petición AJAX ---
        if (!window.mph_admin_obj || !window.mph_admin_obj.ajax_url) { /*...*/ return; }
        const ajax_url = window.mph_admin_obj.ajax_url;
        const i18n = window.mph_admin_obj.i18n || {};     

    // Petición AJAX
    $.post(ajax_url, dataToSend)
        .done(function (response) {
           if (response.success) {
                console.log("Respuesta AJAX exitosa:", response);
                if ($feedbackModal.length) $feedbackModal.text(i18n.horario_guardado || '¡Guardado!').show();
                if (response.data && response.data.html_tabla && $tablaContainer.length) {
                    console.log("Actualizando tabla de horarios...");
                    $tablaContainer.html(response.data.html_tabla);
                } else {
                     console.warn("Respuesta exitosa pero no se encontró HTML de tabla para actualizar.");
                }
                setTimeout(function () { closeModal($modal); }, 1500);
            } else {
                console.error('Error servidor (success=false):', response.data?.message);
                if ($errorModal.length) $errorModal.text(response.data?.message || i18n.error_general || 'Error desconocido.').show();
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
           console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText);
           // Mostrar mensaje de error específico si es 403 (Nonce)
            if (jqXHR.status === 403) {
                 if ($errorModal.length) $errorModal.text(i18n.error_seguridad || 'Error de seguridad. Intente recargar la página.').show();
            } else {
                if ($errorModal.length) $errorModal.text(i18n.error_general || 'Error de comunicación.').show();
            }
        })
        .always(function () {
            console.log("Petición AJAX completada (always).");
            if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
            $button.prop('disabled', false);
        });
    }); // Fin click #mph-guardar-horario

    console.log(`Manejador CLICK para #mph-guardar-horario inicializado.`);
} // Fin initAjaxSubmit