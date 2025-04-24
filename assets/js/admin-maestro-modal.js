/**
 * Lógica JavaScript para el Meta Box y Modal de Gestión de Horarios del Maestro.
 */

/* global jQuery, mph_admin_obj, console */

// jQuery(document).ready(function ($) { // Usar window.load si document.ready da problemas
jQuery(window).on('load', function() {
    const $ = jQuery;
    'use strict';

    const $metaBoxWrapper = $('.mph-gestion-horarios-wrapper');
    if (!$metaBoxWrapper.length) { return; }

    const $modal = $('#mph-modal-horario');
    // $form se define dentro de las funciones ahora

    const $btnAbrirModal = $('#mph-abrir-modal-horario'); // Selector global ok si el botón está fuera del modal
    const $btnCancelarModal = $('#mph-cancelar-modal'); // Selector global ok
    const $btnMostrarAsignacion = $('#mph-mostrar-asignacion'); // Selector global ok
    const $seccionAsignacion = $('.mph-asignacion-especifica'); // Selector global ok

    // Selectores globales para campos (asumiendo que el modal existe al inicio via window.load)
    const $horarioIdEditando = $('#mph_horario_id_editando');
    const $diaSemana = $('#mph_dia_semana');
    const $horaInicioGeneral = $('#mph_hora_inicio_general');
    const $horaFinGeneral = $('#mph_hora_fin_general');
    const $programasAdmisiblesContainer = $('#mph-programas-admisibles-container'); // Contenedor
    const $sedesAdmisiblesContainer = $('#mph-sedes-admisibles-container');       // Contenedor
    const $rangosAdmisiblesContainer = $('#mph-rangos-admisibles-container');     // Contenedor
    const $horaInicioAsignada = $('#mph_hora_inicio_asignada');
    const $horaFinAsignada = $('#mph_hora_fin_asignada');
    const $selectProgramaAsignado = $('#mph_programa_asignado');
    const $selectSedeAsignada = $('#mph_sede_asignada');
    const $selectRangoAsignado = $('#mph_rango_edad_asignado');
    const $vacantes = $('#mph_vacantes');
    const $bufferAntes = $('#mph_buffer_minutos_antes');
    const $bufferDespues = $('#mph_buffer_minutos_despues');
    const $bufferLinkeado = $('#mph_buffer_linkeado');

    // Selectores para feedback (buscados dentro del modal cuando se necesiten o globalmente si son únicos)
    const $spinner = $metaBoxWrapper.find('.spinner').first();
    const $spinnerModal = $('#mph-modal-horario .mph-modal-acciones .spinner'); // Buscar dentro del modal
    const $feedbackModal = $('#mph-modal-horario .mph-modal-feedback');       // Buscar dentro del modal
    const $errorModal = $('#mph-modal-horario .mph-modal-error');             // Buscar dentro del modal


    // --- Inicialización del Modal (jQuery UI Dialog) ---
     if (!$modal.length) {
         console.error("Error Crítico: El elemento del modal #mph-modal-horario no se encontró en window.load. El modal no funcionará.");
         return; // Detener si el modal no existe
     }
     $modal.dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        height: 'auto',
        draggable: true,
        resizable: false,
        closeText: "Cerrar",
        classes: { "ui-dialog": "mph-jquery-ui-modal" },
        open: function(event, ui) {
            console.log("Evento 'open' del diálogo disparado.");
            $feedbackModal.hide().text('');
            $errorModal.hide().text('');
            $spinnerModal.removeClass('is-active');
            // Intentar resetear aquí también, usando ui.panel si está disponible
            // resetModalForm($(this)); // Pasar el elemento del diálogo
        },
        close: function(event, ui) {
            console.log("Evento 'close' del diálogo disparado.");
            resetModalForm($(this));
        }
    });

    // --- Funciones Auxiliares ---

    function resetModalForm($dialogElement) {
        console.log('Intentando resetear formulario (modal en footer)...');
        let $form = null;
        const $context = $dialogElement && $dialogElement.length ? $dialogElement : $modal; // Usar $dialogElement si existe, sino $modal global

        if ($context.length) {
             console.log("Intentando buscar form dentro del contexto...");
             $form = $context.find('form#mph-form-horario'); // Buscar DENTRO del contexto
        } else {
             console.log("Contexto de búsqueda ($dialogElement o $modal) no encontrado.");
        }


        if (!$form || !$form.length) {
            console.error('Error en resetModalForm: No se encontró form#mph-form-horario dentro del contexto.');
             if ($context.length) {
                 console.log("Contenido HTML dentro del contexto de búsqueda:", $context.html());
                 console.log("Elementos encontrados dentro del contexto:", $context.children());
             }
            return;
        }





        console.log('Formulario encontrado en resetModalForm. Reseteando...');
        $form[0].reset();
        $horarioIdEditando.val('');
        // Desmarcar checkboxes excepto los comunes
        $programasAdmisiblesContainer.find('input[type="checkbox"]').each(function(){
             const termId = $(this).val();
             const programaData = mph_admin_obj.todos_programas.find(p => p.term_id == termId);
             if (!programaData || !programaData.es_comun) { $(this).prop('checked', false); }
             else { $(this).prop('checked', true); } // Asegurar que comunes estén marcados
        });
         $sedesAdmisiblesContainer.find('input[type="checkbox"]').each(function(){
             const termId = $(this).val();
             const sedeData = mph_admin_obj.todas_sedes.find(s => s.term_id == termId);
              if (!sedeData || !sedeData.es_comun) { $(this).prop('checked', false); }
              else { $(this).prop('checked', true); }
        });
         $rangosAdmisiblesContainer.find('input[type="checkbox"]').each(function(){
             const termId = $(this).val();
             const rangoData = mph_admin_obj.todos_rangos.find(r => r.term_id == termId);
              if (!rangoData || !rangoData.es_comun) { $(this).prop('checked', false); }
              else { $(this).prop('checked', true); }
        });

        $selectProgramaAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Programa --' }));
        $selectSedeAsignada.empty().append($('<option>', { value: '', text: '-- Seleccionar Sede --' }));
        $selectRangoAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Rango --' }));
        $seccionAsignacion.hide();
        $modal.find('.mph-error-hora, .mph-error-duplicado, .mph-error-hora-asignada').hide();
        $feedbackModal.hide().text('');
        $errorModal.hide().text('');
        $spinnerModal.removeClass('is-active');
        $vacantes.val(1);
        $bufferAntes.val(60);
        $bufferDespues.val(60);
        $bufferLinkeado.prop('checked', true);
    }

    function poblarSelectsAsignacion() {
        $selectProgramaAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Programa --' }));
        $selectSedeAsignada.empty().append($('<option>', { value: '', text: '-- Seleccionar Sede --' }));
        $selectRangoAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Rango --' }));

        let primerProgramaNoComunOption = null;
        let primerProgramaOption = null;
        let primerSedeNoComunOption = null;
        let primerSedeOption = null;
        let primerRangoNoComunOption = null;
        let primerRangoOption = null;

        $programasAdmisiblesContainer.find('input:checked').each(function() {
            const termId = $(this).val();
            const termName = $(this).parent('label').text().trim().replace(' (Común)', '');
            const programaData = mph_admin_obj.todos_programas.find(p => p.term_id == termId);
            const esComun = programaData ? programaData.es_comun : false;
            const $option = $('<option>', { value: termId, text: termName, 'data-es-comun': esComun });
            $selectProgramaAsignado.append($option);
            if (primerProgramaOption === null) { primerProgramaOption = $option; }
            if (!esComun && primerProgramaNoComunOption === null) { primerProgramaNoComunOption = $option; }
        });
        if (primerProgramaNoComunOption) { primerProgramaNoComunOption.prop('selected', true); }
        else if (primerProgramaOption) { primerProgramaOption.prop('selected', true); }

        $sedesAdmisiblesContainer.find('input:checked').each(function() {
            const termId = $(this).val();
            const termName = $(this).parent('label').text().trim().replace(' (Común)', '');
            const sedeData = mph_admin_obj.todas_sedes.find(s => s.term_id == termId);
            const esSedeComun = sedeData ? sedeData.es_comun : false;
            const $option = $('<option>', { value: termId, text: termName, 'data-es-comun': esSedeComun });
            $selectSedeAsignada.append($option);
            if (primerSedeOption === null) { primerSedeOption = $option; }
            if (!esSedeComun && primerSedeNoComunOption === null) { primerSedeNoComunOption = $option; }
        });
        if (primerSedeNoComunOption) { primerSedeNoComunOption.prop('selected', true); }
        else if (primerSedeOption) { primerSedeOption.prop('selected', true); }

        $rangosAdmisiblesContainer.find('input:checked').each(function() {
            const termId = $(this).val();
            const termName = $(this).parent('label').text().trim().replace(' (Común)', '');
            const rangoData = mph_admin_obj.todos_rangos.find(r => r.term_id == termId);
            const esRangoComun = rangoData ? rangoData.es_comun : false;
            const $option = $('<option>', { value: termId, text: termName, 'data-es-comun': esRangoComun });
            $selectRangoAsignado.append($option);
            if (primerRangoOption === null) { primerRangoOption = $option; }
            if (!esRangoComun && primerRangoNoComunOption === null) { primerRangoNoComunOption = $option; }
        });
        if (primerRangoNoComunOption) { primerRangoNoComunOption.prop('selected', true); }
        else if (primerRangoOption) { primerRangoOption.prop('selected', true); }
    }

    function validarFormulario($form) { // Acepta $form como argumento
       console.log('--- Iniciando validarFormulario ---');
        if (!$form || !$form.length) {
            console.error('Error en validarFormulario: No se recibió un objeto de formulario válido.');
            return false;
        }
        let isValid = true;
        $errorModal.hide().text(''); // Limpiar errores previos (usa variable global $errorModal)

        // Validación de campos usando variables globales
        if (!$diaSemana.val()) { $errorModal.append('<p>Seleccione un día.</p>').show(); isValid = false; } // Ejemplo
        const horaInicioGen = $horaInicioGeneral.val();
        const horaFinGen = $horaFinGeneral.val();
        if (!horaInicioGen || !horaFinGen) { $errorModal.append('<p>Ingrese horas generales.</p>').show(); isValid = false; }
        else if (horaFinGen <= horaInicioGen) { $modal.find('.mph-error-hora').show(); isValid = false; }
        else { $modal.find('.mph-error-hora').hide(); }

        if ($programasAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_programa || 'Debe seleccionar al menos un programa admisible.') + '</p>').show(); isValid = false; }
        if ($sedesAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_sede || 'Debe seleccionar al menos una sede admisible.') + '</p>').show(); isValid = false; }
        if ($rangosAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_rango || 'Debe seleccionar al menos un rango de edad admisible.') + '</p>').show(); isValid = false; }

        if ($seccionAsignacion.is(':visible')) {
            const horaInicioAsig = $horaInicioAsignada.val();
            const horaFinAsig = $horaFinAsignada.val();
            if (!horaInicioAsig || !horaFinAsig) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_faltan_horas_asignadas || 'Faltan horas de asignación.') + '</p>').show(); isValid = false; }
            else {
                if (horaFinAsig <= horaInicioAsig) { $modal.find('.mph-error-hora-asignada').text(mph_admin_obj.i18n.error_hora_asignada_invalida || 'Hora fin asignada debe ser posterior a inicio.').show(); isValid = false; }
                else if (horaInicioAsig < horaInicioGen || horaFinAsig > horaFinGen) { $modal.find('.mph-error-hora-asignada').text(mph_admin_obj.i18n.error_hora_asignada_rango || 'Horas asignadas fuera del rango general.').show(); isValid = false; }
                else { $modal.find('.mph-error-hora-asignada').hide(); }
            }
            if (!$selectProgramaAsignado.val()) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_programa_asig || 'Debe seleccionar un programa para la asignación.') + '</p>').show(); isValid = false; }
            if (!$selectSedeAsignada.val()) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_sede_asig || 'Debe seleccionar una sede para la asignación.') + '</p>').show(); isValid = false; }
            if (!$selectRangoAsignado.val()) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_rango_asig || 'Debe seleccionar un rango de edad para la asignación.') + '</p>').show(); isValid = false; }
            if (parseInt($vacantes.val(), 10) < 0) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_vacantes_negativas || 'Las vacantes no pueden ser negativas.') + '</p>').show(); isValid = false; }
            const bufferAntesVal = parseInt($bufferAntes.val(), 10);
            const bufferDespuesVal = parseInt($bufferDespues.val(), 10);
            if (isNaN(bufferAntesVal) || bufferAntesVal < 0) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_buffer_antes_invalido || 'El tiempo de buffer Antes debe ser un número positivo.') + '</p>').show(); isValid = false; }
            if (isNaN(bufferDespuesVal) || bufferDespuesVal < 0) { $errorModal.append('<p>' + (mph_admin_obj.i18n.error_buffer_despues_invalido || 'El tiempo de buffer Después debe ser un número positivo.') + '</p>').show(); isValid = false; }
        }
        console.log('--- Finalizando validarFormulario --- Retornando:', isValid);
        return isValid;
    }

    // --- Manejadores de Eventos ---
    $metaBoxWrapper.on('click', '#mph-abrir-modal-horario', function () { // Delegación desde wrapper
        console.log('¡Clic detectado en el botón Abrir Modal (Delegado)!');
        // resetModalForm(); // Ya no llamamos aquí, se llama en el evento 'open' del diálogo
        if ($modal.length) {
            console.log('Modal encontrado (#mph-modal-horario). Intentando abrir...');
            try {
                // Llamar a reset ANTES de abrir
                resetModalForm($modal); // Intentar resetear usando $modal como contexto
                 $modal.dialog('open');
                 console.log('Llamada a $modal.dialog("open") completada.');
             } catch (e) {
                 console.error('¡ERROR al ejecutar $modal.dialog("open")!:', e);
             }
         } else {
             console.error("Error al abrir: No se encontró el elemento #mph-modal-horario");
         }
    });

    $modal.on('click', '#mph-cancelar-modal', function () { // Delegar cancelar al modal
        $modal.dialog('close');
    });

    $modal.on('click', '#mph-mostrar-asignacion', function () { // Delegar mostrar/ocultar al modal
        $seccionAsignacion.slideToggle(function() {
             if ($seccionAsignacion.is(':visible')) {
                poblarSelectsAsignacion();
                $horaInicioAsignada.val($horaInicioGeneral.val());
                $horaFinAsignada.val($horaFinGeneral.val());
             }
        });
    });

    // Usar delegación para checkboxes cargados dinámicamente
    $modal.on('change', '#mph-programas-admisibles-container input[type="checkbox"], #mph-sedes-admisibles-container input[type="checkbox"], #mph-rangos-admisibles-container input[type="checkbox"]', function() {
        if ($seccionAsignacion.is(':visible')) {
            poblarSelectsAsignacion();
        }
    });

    function sincronizarBuffers(campoModificado) {
        if ($bufferLinkeado.is(':checked')) { // Usar variable global
            const valor = campoModificado.val();
            if (campoModificado.attr('id') === 'mph_buffer_minutos_antes') {
                $bufferDespues.val(valor); // Usar variable global
            } else {
                $bufferAntes.val(valor); // Usar variable global
            }
        }
    }
    $modal.on('change input', '#mph_buffer_minutos_antes', function() { // Delegar al modal
        sincronizarBuffers($(this));
    });
    $modal.on('change input', '#mph_buffer_minutos_despues', function() { // Delegar al modal
        sincronizarBuffers($(this));
    });

    // Manejar el envío del formulario (AJAX)
    $modal.on('submit', 'form#mph-form-horario', function (e) {
        console.log('--- Evento Submit Detectado en form#mph-form-horario dentro de $modal ---');
        e.preventDefault();
        const $form = $(this);
        if (!$form.length) { console.error("Error en submit: El objeto $form (this) está vacío."); return; }

        console.log('Llamando a validarFormulario...');
        if (!validarFormulario($form)) {
            console.log('validarFormulario devolvió false. Envío detenido.');
            return;
        }
        console.log('Validación pasada. Procediendo con AJAX...');

        $spinnerModal.addClass('is-active');
        $feedbackModal.hide().text('');
        $errorModal.hide().text('');
        const $btnGuardarSubmit = $form.find('#mph-guardar-horario');
        $btnGuardarSubmit.prop('disabled', true);

        console.log('Spinner y botón modificados (intentado). Preparando datos AJAX...');
        const formData = $form.serialize();
        const dataToSend = formData + '&action=mph_guardar_horario_maestro&nonce=' + mph_admin_obj.nonce;
        console.log('Datos a enviar (sin incluir action/nonce):', formData);

        $.post(mph_admin_obj.ajax_url, dataToSend)
            .done(function (response) {
                if (response.success) {
                    $feedbackModal.text(mph_admin_obj.i18n.horario_guardado || '¡Guardado!').show();
                    if (response.data && response.data.html_tabla) {
                         $('#mph-tabla-horarios-container').html(response.data.html_tabla);
                    }
                    setTimeout(function () { $modal.dialog('close'); }, 1500);
                } else {
                    $errorModal.text(response.data.message || mph_admin_obj.i18n.error_general || 'Error desconocido.').show();
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText);
                $errorModal.text(mph_admin_obj.i18n.error_general || 'Error de comunicación.').show();
            })
            .always(function () {
                $spinnerModal.removeClass('is-active');
                $btnGuardarSubmit.prop('disabled', false);
            });
    });

    // Validaciones en tiempo real (delegadas al modal)
    $modal.on('change', '#mph_hora_fin_general', function() {
        if ($horaInicioGeneral.val() && $(this).val() <= $horaInicioGeneral.val()) {
            $modal.find('.mph-error-hora').show();
        } else {
            $modal.find('.mph-error-hora').hide();
        }
    });
    $modal.on('change', '#mph_hora_fin_asignada', function() {
        const $errorDiv = $modal.find('.mph-error-hora-asignada');
        const inicioAsig = $horaInicioAsignada.val();
        const finAsig = $(this).val();
        const inicioGen = $horaInicioGeneral.val();
        const finGen = $horaFinGeneral.val();
        if (inicioAsig && finAsig <= inicioAsig) { $errorDiv.text(mph_admin_obj.i18n.error_hora_asignada_invalida || 'Hora fin asignada debe ser posterior a inicio.').show(); }
        else if (inicioGen && finGen && (inicioAsig < inicioGen || finAsig > finGen)) { $errorDiv.text(mph_admin_obj.i18n.error_hora_asignada_rango || 'Horas asignadas fuera del rango general.').show(); }
        else { $errorDiv.hide(); }
    });

    /* Inicia Modificación: Añadir manejador para Eliminar */
const $tablaContainer = $('#mph-tabla-horarios-container'); // Selector para el contenedor de la tabla

$tablaContainer.on('click', '.mph-accion-eliminar', function(e) {
    e.preventDefault(); // Prevenir cualquier acción por defecto del botón/enlace

    const $button = $(this);
    const horarioId = $button.data('horario-id');
    const nonce = $button.data('nonce');
    const $fila = $button.closest('tr'); // Encontrar la fila de la tabla a eliminar

    if (!horarioId || !nonce) {
        console.error('Error: No se encontraron el ID del horario o el Nonce en el botón de eliminar.');
        alert(mph_admin_obj.i18n.error_general || 'Error inesperado al intentar eliminar.');
        return;
    }

    // Pedir confirmación
    // Usar texto localizado pasado desde PHP
    const confirmarMsg = mph_admin_obj.i18n.confirmar_eliminacion || '¿Estás seguro de que deseas eliminar este horario?';
    if (confirm(confirmarMsg)) {
        console.log(`Intentando eliminar horario ID: ${horarioId}`);

        // Mostrar un feedback visual (ej. deshabilitar botón, añadir spinner a la fila?)
        $button.prop('disabled', true).css('opacity', 0.5);
        // Podríamos añadir un pequeño spinner junto al botón si quisiéramos

        // Preparar datos para AJAX
        const dataToSend = {
            action: 'mph_eliminar_horario', // Nueva acción AJAX
            horario_id: horarioId,
            nonce: nonce // Nonce específico para esta acción/ID
        };

        // Enviar petición AJAX
        $.post(mph_admin_obj.ajax_url, dataToSend)
            .done(function (response) {
                if (response.success) {
                    console.log(`Horario ID: ${horarioId} eliminado con éxito.`);
                    // Eliminar la fila de la tabla con una animación suave
                    $fila.fadeOut(400, function() {
                        $(this).remove();
                        // Opcional: Mostrar un mensaje de éxito temporal
                        // alert(mph_admin_obj.i18n.horario_eliminado || 'Horario eliminado.');
                    });
                } else {
                    console.error('Error devuelto por el servidor al eliminar:', response.data.message);
                    alert(mph_admin_obj.i18n.error_general + (response.data.message ? ' (' + response.data.message + ')' : ''));
                    $button.prop('disabled', false).css('opacity', 1); // Reactivar botón si falla
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX al eliminar:", textStatus, errorThrown, jqXHR.responseText);
                alert(mph_admin_obj.i18n.error_general || 'Error de comunicación al intentar eliminar.');
                $button.prop('disabled', false).css('opacity', 1); // Reactivar botón si falla
            })
            .always(function() {
                // Código que se ejecuta siempre (ej. quitar spinner si lo añadimos)
            });

    } else {
        console.log('Eliminación cancelada por el usuario.');
    }
});

}); // Fin de jQuery(window).on('load', ...)