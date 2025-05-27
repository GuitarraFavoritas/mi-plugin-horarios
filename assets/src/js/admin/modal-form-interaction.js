// assets/src/js/admin/modal-form-interaction.js
import $ from 'jquery';

// --- Selectores (Podrían pasarse o definirse globalmente si es necesario) ---
// Asumimos que estos selectores apuntan a elementos que existen cuando se llama initFormInteractions
const $modal = $('#mph-modal-horario'); // Necesario para delegar eventos
const $seccionAsignacion = $('.mph-asignacion-especifica'); // Podríamos buscarlo dentro del modal: $modal.find(...)
const $btnMostrarAsignacion = $('#mph-mostrar-asignacion');
const $programasAdmisiblesContainer = $('#mph-programas-admisibles-container');
const $sedesAdmisiblesContainer = $('#mph-sedes-admisibles-container');
const $rangosAdmisiblesContainer = $('#mph-rangos-admisibles-container');
const $selectProgramaAsignado = $('#mph_programa_asignado');
const $selectSedeAsignada = $('#mph_sede_asignada');
const $selectRangoAsignado = $('#mph_rango_edad_asignado');
const $horaInicioGeneral = $('#mph_hora_inicio_general');
const $horaFinGeneral = $('#mph_hora_fin_general');
const $horaInicioAsignada = $('#mph_hora_inicio_asignada');
const $horaFinAsignada = $('#mph_hora_fin_asignada');
const $bufferAntes = $('#mph_buffer_minutos_antes');
const $bufferDespues = $('#mph_buffer_minutos_despues');
const $bufferLinkeado = $('#mph_buffer_linkeado');

/**
 * Puebla los selects de la sección de asignación basándose
 * en los checkboxes seleccionados en la sección general.
 */
export function poblarSelectsAsignacion() {
    console.log("Poblando selects de asignación..."); // Log

    if (typeof window.mph_admin_obj === 'undefined' || !mph_admin_obj.todos_programas) {
        console.error("poblarSelectsAsignacion: mph_admin_obj o sus datos no están disponibles.");
        // Quizás mostrar un error al usuario o deshabilitar selects?
         $selectProgramaAsignado.empty().append($('<option value="">Error datos</option>'));
         $selectSedeAsignada.empty().append($('<option value="">Error datos</option>'));
         $selectRangoAsignado.empty().append($('<option value="">Error datos</option>'));
        return; // No continuar si no hay datos
    }

    // Limpiar selects actuales
    $selectProgramaAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Programa --' }));
    $selectSedeAsignada.empty().append($('<option>', { value: '', text: '-- Seleccionar Sede --' }));
    $selectRangoAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Rango --' }));

    let primerProgramaNoComunOption = null;
    let primerProgramaOption = null;
    let primerSedeNoComunOption = null;
    let primerSedeOption = null;
    let primerRangoNoComunOption = null;
    let primerRangoOption = null;

    // Verificar si mph_admin_obj está disponible (debe cargarse antes)
    if (typeof window.mph_admin_obj === 'undefined') {
        console.error("poblarSelectsAsignacion: mph_admin_obj no está disponible.");
        return;
    }

    // Programas
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

    // Sedes
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

    // Rangos
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


/**
 * Sincroniza los valores de los campos de buffer si están linkeados.
 * @param {jQuery} campoModificado - El campo input que cambió.
 */
function sincronizarBuffers(campoModificado) {
    if ($bufferLinkeado.is(':checked')) {
        const valor = campoModificado.val();
        if (campoModificado.attr('id') === 'mph_buffer_minutos_antes') {
            $bufferDespues.val(valor);
        } else {
            $bufferAntes.val(valor);
        }
    }
}

/**
 * Inicializa los manejadores de eventos para la interacción del formulario del modal.
 */
export function initFormInteractions() {
    console.log('Inicializando interacciones del formulario del modal...');

    if (!$modal.length) {
        console.error("initFormInteractions: Modal no encontrado.");
        return;
    }

    // Mostrar/Ocultar sección de asignación específica (delegado al modal)
    $modal.on('click', '#mph-mostrar-asignacion', function () {
        console.log("Botón Mostrar/Ocultar Asignación clickeado.");
        // Asegurarse que $seccionAsignacion es correcta
        const $seccion = $modal.find('.mph-asignacion-especifica');
        $seccion.slideToggle(function() {
             if ($seccion.is(':visible')) {
                console.log("Sección asignación visible. Poblando selects y prellenando horas.");
                poblarSelectsAsignacion();
                // Pre-llenar horas de asignación con las generales (buscar dentro del modal)
                $modal.find('#mph_hora_inicio_asignada').val($modal.find('#mph_hora_inicio_general').val());
                $modal.find('#mph_hora_fin_asignada').val($modal.find('#mph_hora_fin_general').val());
             } else {
                 console.log("Sección asignación oculta.");
             }
        });
    });

    // Actualizar selects de asignación si cambian los checkboxes generales (delegado al modal)
     $modal.on('change', '#mph-programas-admisibles-container input[type="checkbox"], #mph-sedes-admisibles-container input[type="checkbox"], #mph-rangos-admisibles-container input[type="checkbox"]', function() {
        // Usar $seccionAsignacion global o buscarla de nuevo
         if ($modal.find('.mph-asignacion-especifica').is(':visible')) {
             console.log("Checkbox admisible cambiado. Repoblando selects.");
            poblarSelectsAsignacion();
        }
    });

     // Sincronizar buffers (delegado al modal)
    $modal.on('change input', '#mph_buffer_minutos_antes', function() {
        sincronizarBuffers($(this));
    });
    $modal.on('change input', '#mph_buffer_minutos_despues', function() {
        sincronizarBuffers($(this));
    });

    // Validaciones en tiempo real (delegadas al modal)
    $modal.on('change', '#mph_hora_fin_general', function() {
        // Usar $horaInicioGeneral global o buscarla: const $inicio = $modal.find('#mph_hora_inicio_general');
        if ($horaInicioGeneral.val() && $(this).val() <= $horaInicioGeneral.val()) {
            $modal.find('.mph-error-hora').show();
        } else {
            $modal.find('.mph-error-hora').hide();
        }
    });
     $modal.on('change', '#mph_hora_fin_asignada', function() {
        const $errorDiv = $modal.find('.mph-error-hora-asignada');
         // Usar globales o buscar dentro del modal
        const inicioAsig = $horaInicioAsignada.val();
        const finAsig = $(this).val();
        const inicioGen = $horaInicioGeneral.val();
        const finGen = $horaFinGeneral.val();
        if (inicioAsig && finAsig <= inicioAsig) { $errorDiv.text( (window.mph_admin_obj?.i18n?.error_hora_asignada_invalida) || 'Hora fin asignada debe ser posterior a inicio.').show(); }
        else if (inicioGen && finGen && (inicioAsig < inicioGen || finAsig > finGen)) { $errorDiv.text( (window.mph_admin_obj?.i18n?.error_hora_asignada_rango) || 'Horas asignadas fuera del rango general.').show(); }
        else { $errorDiv.hide(); }
    });

    console.log('Interacciones del formulario inicializadas.');
}