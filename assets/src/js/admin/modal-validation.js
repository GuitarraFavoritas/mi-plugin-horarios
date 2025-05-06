// assets/src/js/admin/modal-validation.js
import $ from 'jquery';

// Selectores globales (asumiendo que se definen en main.js y están disponibles en window o pasados)
// O búscalos dentro de $form o $modal si es más seguro
const $diaSemana = $('#mph_dia_semana');
const $horaInicioGeneral = $('#mph_hora_inicio_general');
const $horaFinGeneral = $('#mph_hora_fin_general');
const $programasAdmisiblesContainer = $('#mph-programas-admisibles-container');
const $sedesAdmisiblesContainer = $('#mph-sedes-admisibles-container');
const $rangosAdmisiblesContainer = $('#mph-rangos-admisibles-container');
// Selectores para la sección de asignación (pueden ser globales o buscados dentro de $form)
const $seccionAsignacion = $('#mph-modal-horario .mph-asignacion-especifica'); // Más específico
const $horaInicioAsignada = $('#mph_hora_inicio_asignada');
const $horaFinAsignada = $('#mph_hora_fin_asignada');
const $selectProgramaAsignado = $('#mph_programa_asignado');
const $selectSedeAsignada = $('#mph_sede_asignada');
const $selectRangoAsignado = $('#mph_rango_edad_asignado');
const $vacantes = $('#mph_vacantes');
const $bufferAntes = $('#mph_buffer_minutos_antes');
const $bufferDespues = $('#mph_buffer_minutos_despues');


export function validarFormulario($form) { // Acepta $form como argumento
   console.log('--- Iniciando validarFormulario ---');
    if (!$form || !$form.length) {
        console.error('Error en validarFormulario: No se recibió un objeto de formulario válido.');
        return false;
    }
    let isValid = true;
    const $modal = $form.closest('#mph-modal-horario'); // Obtener el modal desde el form
    const $errorModal = $modal.find('.mph-modal-error');

    if ($errorModal.length) $errorModal.hide().empty();

    // Obtener el modo del botón guardar que está DENTRO del form
    const $btnGuardar = $form.find('#mph-guardar-horario');
    const currentMode = $btnGuardar.attr('data-action-mode') || 'save_full'; // 'save_full' es el default si no hay attr
    console.log("validarFormulario - Modo de acción detectado:", currentMode);

    // --- Validación Sección General (SOLO para modo save_full) ---
    if (currentMode === 'save_full') {
        console.log("Validando sección general para modo save_full...");
        if (!$diaSemana.val()) { $errorModal.append('<p>Seleccione un día.</p>'); isValid = false; }
        const horaInicioGenVal = $horaInicioGeneral.val(); // Usar selectores globales
        const horaFinGenVal = $horaFinGeneral.val();
        if (!horaInicioGenVal || !horaFinGenVal) { $errorModal.append('<p>Ingrese horas generales.</p>'); isValid = false; }
        else if (horaFinGenVal <= horaInicioGenVal) { $modal.find('.mph-error-hora').show(); isValid = false; }
        else { $modal.find('.mph-error-hora').hide(); }

        const i18n_val = window.mph_admin_obj?.i18n || {};
        if ($programasAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (i18n_val.error_seleccionar_programa || 'Prog.') + '</p>'); isValid = false; }
        if ($sedesAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (i18n_val.error_seleccionar_sede || 'Sede.') + '</p>'); isValid = false; }
        if ($rangosAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (i18n_val.error_seleccionar_rango || 'Rango.') + '</p>'); isValid = false; }
    }


    // --- Validación Sección Asignación (SOLO si está visible Y no es 'edit_vacantes') ---
    // O si el modo es 'assign_to_existing' (porque la sección se muestra directamente)
    const seccionAsignacionVisible = $seccionAsignacion.is(':visible'); // Usar selector global
    if ( (seccionAsignacionVisible && currentMode !== 'update_vacantes') || currentMode === 'assign_to_existing' ) {
        console.log("Validando sección de asignación...");
        const horaInicioAsigVal = $horaInicioAsignada.val();
        const horaFinAsigVal = $horaFinAsignada.val();
        const horaInicioGenValParaComparar = (currentMode === 'assign_to_existing') ? $form.find('#mph_hora_inicio_general').val() : $horaInicioGeneral.val();
        const horaFinGenValParaComparar = (currentMode === 'assign_to_existing') ? $form.find('#mph_hora_fin_general').val() : $horaFinGeneral.val();

        const $errorHoraAsignada = $modal.find('.mph-error-hora-asignada');
        const i18n_asig = window.mph_admin_obj?.i18n || {};

        if (!horaInicioAsigVal || !horaFinAsigVal) { $errorModal.append('<p>' + (i18n_asig.error_faltan_horas_asignadas || 'Faltan horas.') + '</p>'); isValid = false; }
        else {
            if (horaFinAsigVal <= horaInicioAsigVal) { $errorHoraAsignada.text(i18n_asig.error_hora_asignada_invalida || 'Fin < Inicio Asig.').show(); isValid = false; }
            // Validar contra las horas GENERALES del contexto actual
            else if (horaInicioGenValParaComparar && horaFinGenValParaComparar && (horaInicioAsigVal < horaInicioGenValParaComparar || horaFinAsigVal > horaFinGenValParaComparar)) {
                 $errorHoraAsignada.text(i18n_asig.error_hora_asignada_rango || 'Asig. fuera rango Gral.').show(); isValid = false;
            }
            else { $errorHoraAsignada.hide(); }
        }
        if (!$selectProgramaAsignado.val()) { $errorModal.append('<p>' + (i18n_asig.error_seleccionar_programa_asig || 'Prog Asig.') + '</p>'); isValid = false; }
        if (!$selectSedeAsignada.val()) { $errorModal.append('<p>' + (i18n_asig.error_seleccionar_sede_asig || 'Sede Asig.') + '</p>'); isValid = false; }
        if (!$selectRangoAsignado.val()) { $errorModal.append('<p>' + (i18n_asig.error_seleccionar_rango_asig || 'Rango Asig.') + '</p>'); isValid = false; }

        // La validación de vacantes y buffers se hace en el modo 'update_vacantes' y también aquí para 'save_full' y 'assign_to_existing'
        if (parseInt($vacantes.val(), 10) < 0) { $errorModal.append('<p>' + (i18n_asig.error_vacantes_negativas || 'Vacantes < 0.') + '</p>'); isValid = false; }
        const bufferAntesVal = parseInt($bufferAntes.val(), 10);
        const bufferDespuesVal = parseInt($bufferDespues.val(), 10);
        if (isNaN(bufferAntesVal) || bufferAntesVal < 0) { $errorModal.append('<p>' + (i18n_asig.error_buffer_antes_invalido || 'Buffer Antes < 0.') + '</p>'); isValid = false; }
        if (isNaN(bufferDespuesVal) || bufferDespuesVal < 0) { $errorModal.append('<p>' + (i18n_asig.error_buffer_despues_invalido || 'Buffer Después < 0.') + '</p>'); isValid = false; }
    }

    if (!isValid && $errorModal.length) $errorModal.show();
    else if (isValid) console.log("Validación pasada.");

    console.log('--- Finalizando validarFormulario --- Retornando:', isValid);
    return isValid;
}