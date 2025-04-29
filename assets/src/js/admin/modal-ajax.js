// assets/src/js/admin/modal-ajax.js
import $ from 'jquery';
import { validarFormulario } from './modal-validation';
import { closeModal } from './modal-init';

/**
 * Inicializa el manejador de envío AJAX para el formulario del modal.
 * @param {jQuery} $modal - El objeto jQuery del elemento modal.
 */
export function initAjaxSubmit($modal) {
    /* Inicia Modificación: Corregir log y asegurar $modal es válido */
    console.log(`Inicializando manejador CLICK para #mph-guardar-horario...`);
     if (!$modal || !$modal.length || typeof $modal.find !== 'function') {
         console.error("initAjaxSubmit: No se recibió el objeto $modal válido.");
         return;
      }
    /* Finaliza Modificación */

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

        console.log('Llamando a validarFormulario...');
        if (!validarFormulario($form)) {
            console.log('validarFormulario devolvió false. Envío detenido.');
            return;
        }
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

        // Si llegamos aquí, el objeto y ajax_url son válidos.
        const ajax_url = mph_admin_obj.ajax_url;
        // const nonce = mph_admin_obj.nonce; // Ya no necesitamos el nonce de mph_admin_obj
        const i18n = mph_admin_obj.i18n || {};







        // Nombre del campo nonce (debe coincidir con wp_nonce_field en PHP)
        const nonceFieldName = 'mph_nonce_guardar'; // Nuevo nombre del campo
            const $nonceField = $form.find('input[name="' + nonceFieldName + '"]');
            let nonceValue = '';

            if ($nonceField.length) {
                nonceValue = $nonceField.val();
                console.log(`Valor del campo nonce (${nonceFieldName}) leído del DOM:`, nonceValue);
            } else {
                console.error(`¡Error crítico! No se encontró el campo nonce "${nonceFieldName}" en el formulario.`);
                if ($errorModal.length) $errorModal.text("Error de seguridad interno (Falta campo Nonce).").show();
                $button.prop('disabled', false); if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
                return;
            }

            // Construir dataToSend: acción + formData (que ya incluye nonce)
            // NO necesitamos añadir el nonce explícitamente si está en formData
            const dataToSend = formData + '&action=mph_guardar_horario_maestro';
            console.log('Datos COMPLETOS a enviar (formData incluye nonce):', dataToSend);









        

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

     /* Inicia Modificación: Corregir log final */
    console.log(`Manejador CLICK para #mph-guardar-horario inicializado.`);
    /* Finaliza Modificación */
} // Fin initAjaxSubmit