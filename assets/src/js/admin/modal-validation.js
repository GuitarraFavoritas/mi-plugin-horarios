// assets/src/js/admin/modal-validation.js
import $ from 'jquery';

// Selectores globales (o podrían pasarse a la función)
const $modal = $('#mph-modal-horario');
const $errorModal = $('#mph-modal-horario .mph-modal-error');
const $diaSemana = $('#mph_dia_semana');
const $horaInicioGeneral = $('#mph_hora_inicio_general');
const $horaFinGeneral = $('#mph_hora_fin_general');
const $programasAdmisiblesContainer = $('#mph-programas-admisibles-container');
const $sedesAdmisiblesContainer = $('#mph-sedes-admisibles-container');
const $rangosAdmisiblesContainer = $('#mph-rangos-admisibles-container');
const $seccionAsignacion = $('#mph-modal-horario .mph-asignacion-especifica'); // Buscar dentro de modal por seguridad
const $horaInicioAsignada = $('#mph_hora_inicio_asignada');
const $horaFinAsignada = $('#mph_hora_fin_asignada');
const $selectProgramaAsignado = $('#mph_programa_asignado');
const $selectSedeAsignada = $('#mph_sede_asignada');
const $selectRangoAsignado = $('#mph_rango_edad_asignado');
const $vacantes = $('#mph_vacantes');
const $bufferAntes = $('#mph_buffer_minutos_antes');
const $bufferDespues = $('#mph_buffer_minutos_despues');

/**
 * Valida los campos del formulario del modal antes de enviar.
 * @param {jQuery} $form - El objeto jQuery del formulario a validar.
 * @returns {boolean} True si es válido, False si no.
 */
export function validarFormulario($form) {
   console.log('--- Iniciando validarFormulario ---');
    if (!$form || !$form.length) {
        console.error('Error en validarFormulario: No se recibió un objeto de formulario válido.');
        return false;
    }
    let isValid = true;
    // Asegurarse que $errorModal existe
    if (!$errorModal.length) {
        console.error("Error en validarFormulario: Contenedor de errores no encontrado.");
        // Quizás mostrar un alert genérico si falla la UI de errores
        // alert("Error interno al validar.");
        // return false; // O intentar continuar sin UI de error
    } else {
         $errorModal.hide().empty(); // Limpiar errores previos y ocultar
    }


    // --- Validación Sección General ---
    if (!$diaSemana.val()) { $errorModal.append('<p>Seleccione un día.</p>'); isValid = false; }
    const horaInicioGen = $horaInicioGeneral.val();
    const horaFinGen = $horaFinGeneral.val();
    if (!horaInicioGen || !horaFinGen) { $errorModal.append('<p>Ingrese horas generales.</p>'); isValid = false; }
    else if (horaFinGen <= horaInicioGen) { $modal.find('.mph-error-hora').show(); isValid = false; } // Mostrar error junto al campo
    else { $modal.find('.mph-error-hora').hide(); }

    const i18n = window.mph_admin_obj?.i18n || {}; // Acceso seguro a i18n

    if ($programasAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (i18n.error_seleccionar_programa || 'Debe seleccionar al menos un programa admisible.') + '</p>'); isValid = false; }
    if ($sedesAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (i18n.error_seleccionar_sede || 'Debe seleccionar al menos una sede admisible.') + '</p>'); isValid = false; }
    if ($rangosAdmisiblesContainer.find('input:checked').length === 0) { $errorModal.append('<p>' + (i18n.error_seleccionar_rango || 'Debe seleccionar al menos un rango de edad admisible.') + '</p>'); isValid = false; }

    // --- Validación Sección Asignación (si visible) ---
    if ($seccionAsignacion.is(':visible')) {
        console.log("Validando sección de asignación...");
        const horaInicioAsig = $horaInicioAsignada.val();
        const horaFinAsig = $horaFinAsignada.val();
        const $errorHoraAsignada = $modal.find('.mph-error-hora-asignada'); // Selector error específico

        if (!horaInicioAsig || !horaFinAsig) { $errorModal.append('<p>' + (i18n.error_faltan_horas_asignadas || 'Faltan horas de asignación.') + '</p>'); isValid = false; }
        else {
            if (horaFinAsig <= horaInicioAsig) { $errorHoraAsignada.text(i18n.error_hora_asignada_invalida || 'Hora fin asignada debe ser posterior a inicio.').show(); isValid = false; }
            else if (horaInicioGen && horaFinGen && (horaInicioAsig < horaInicioGen || horaFinAsig > horaFinGen)) { $errorHoraAsignada.text(i18n.error_hora_asignada_rango || 'Horas asignadas fuera del rango general.').show(); isValid = false; }
            else { $errorHoraAsignada.hide(); }
        }
        if (!$selectProgramaAsignado.val()) { $errorModal.append('<p>' + (i18n.error_seleccionar_programa_asig || 'Debe seleccionar un programa para la asignación.') + '</p>'); isValid = false; }
        if (!$selectSedeAsignada.val()) { $errorModal.append('<p>' + (i18n.error_seleccionar_sede_asig || 'Debe seleccionar una sede para la asignación.') + '</p>'); isValid = false; }
        if (!$selectRangoAsignado.val()) { $errorModal.append('<p>' + (i18n.error_seleccionar_rango_asig || 'Debe seleccionar un rango de edad para la asignación.') + '</p>'); isValid = false; }
        if (parseInt($vacantes.val(), 10) < 0) { $errorModal.append('<p>' + (i18n.error_vacantes_negativas || 'Las vacantes no pueden ser negativas.') + '</p>'); isValid = false; }
        const bufferAntesVal = parseInt($bufferAntes.val(), 10);
        const bufferDespuesVal = parseInt($bufferDespues.val(), 10);
        if (isNaN(bufferAntesVal) || bufferAntesVal < 0) { $errorModal.append('<p>' + (i18n.error_buffer_antes_invalido || 'El tiempo de buffer Antes debe ser un número positivo.') + '</p>'); isValid = false; }
        if (isNaN(bufferDespuesVal) || bufferDespuesVal < 0) { $errorModal.append('<p>' + (i18n.error_buffer_despues_invalido || 'El tiempo de buffer Después debe ser un número positivo.') + '</p>'); isValid = false; }
    }

    // Si hay errores, mostrar el contenedor de errores
    if (!isValid && $errorModal.length) {
        $errorModal.show();
        console.log("Errores de validación encontrados.");
    } else if (isValid) {
         console.log("Validación pasada.");
    }


    console.log('--- Finalizando validarFormulario --- Retornando:', isValid);
    return isValid;
}