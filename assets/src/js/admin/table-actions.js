// assets/src/js/admin/table-actions.js
import $ from 'jquery';
import { openModal, resetModalForm } from './modal-init';
import { poblarSelectsAsignacion } from './modal-form-interaction';


/**
 * Prepara el modal para el modo "Editar Vacantes".
 * @param {jQuery} $modal - El objeto jQuery del modal.
 * @param {object} horarioInfo - El objeto con la info del horario.
 */
function prepareModalForEditVacantes($modal, horarioInfo) {
    const $form = $modal.find('form#mph-form-horario');
    if (!$form.length) return;
    console.log("Preparando modal para Editar Vacantes...");

    // 1. Aplicar clase CSS (el CSS controla qué se muestra/oculta)
    $modal.removeClass('mph-modal-mode-assign mph-modal-mode-edit-disp').addClass('mph-modal-mode-edit-vacantes');

    // 2. Mostrar/Crear y llenar div de información #mph-editar-info
    let $infoDiv = $form.find('#mph-editar-info');
    if (!$infoDiv.length) {
        $form.prepend('<div id="mph-editar-info" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid #eee;"></div>');
        $infoDiv = $form.find('#mph-editar-info'); // Re-buscar después de añadir
    }

    // Construir el HTML de información
    let infoHtml = '<h4>Editando Vacantes para:</h4>'; // Definir ANTES del if(horarioInfo)
    if (horarioInfo) {
        const dias = ['','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        infoHtml += `<p><strong>Día:</strong> ${dias[horarioInfo.dia] || 'N/A'}</p>`;
        infoHtml += `<p><strong>Horario:</strong> ${horarioInfo.inicio_asig || 'N/A'} - ${horarioInfo.fin_asig || 'N/A'}</p>`;
        if (window.mph_admin_obj) {
            const prog = mph_admin_obj.todos_programas?.find(p=>p.term_id == horarioInfo.prog_asig);
            const sede = mph_admin_obj.todas_sedes?.find(s=>s.term_id == horarioInfo.sede_asig);
            const rango = mph_admin_obj.todos_rangos?.find(r=>r.term_id == horarioInfo.rango_asig);
            infoHtml += `<p><strong>Programa:</strong> ${prog?.name || 'N/A'}</p>`;
            infoHtml += `<p><strong>Sede:</strong> ${sede?.name || 'N/A'}</p>`;
            infoHtml += `<p><strong>Rango Edad:</strong> ${rango?.name || 'N/A'}</p>`;
        }
    } else {
        infoHtml += '<p>Error al cargar información del horario.</p>'; // Añadir al HTML existente
    }

    // Asignar el HTML al div (si existe)
    if ($infoDiv.length) {
        $infoDiv.html(infoHtml); // Asignar el HTML construido
        // El CSS se encarga de mostrarlo porque el modal tiene la clase .mph-modal-mode-edit-vacantes
    } else {
         console.warn("Div #mph-editar-info no encontrado, info no mostrada.");
    }


    // 3. Pre-llenar input Vacantes
    const $vacantesInput = $form.find('#mph_vacantes');
    if ($vacantesInput.length) {
         $vacantesInput.val(horarioInfo?.vacantes !== undefined ? horarioInfo.vacantes : 0);
         console.log("Campo vacantes prellenado con:", $vacantesInput.val());
    } else { console.error("Input de Vacantes no encontrado al preparar modal."); }

    // 4. Ajustar botón Guardar
    const $btnGuardar = $form.find('#mph-guardar-horario');
    if ($btnGuardar.length) {
         const textoBoton = window.mph_admin_obj?.i18n?.actualizar_vacantes || 'Actualizar Vacantes';
         $btnGuardar.text(textoBoton);
         $btnGuardar.attr('data-action-mode', 'update_vacantes');
         console.log("Botón Guardar ajustado para 'update_vacantes'.");
    }

    // 5. Establecer ID
    $form.find('#mph_horario_id_editando').val(horarioInfo?.horario_id || '');
    console.log("ID horario establecido:", horarioInfo?.horario_id);
}


/**
 * Inicializa los manejadores de eventos para los botones de acción en la tabla de horarios.
 * @param {string} tableContainerSelector - Selector CSS para el div que contiene la tabla.
 */
export function initTableActions(tableContainerSelector) {
    console.log(`Inicializando acciones de tabla para "${tableContainerSelector}"...`);
    const $tableContainer = $(tableContainerSelector);

    if (!$tableContainer.length) {
        console.error("initTableActions: Contenedor de tabla no encontrado.");
        return;
    }

    // --- Acción Eliminar ---
    $tableContainer.on('click', '.mph-accion-eliminar', function(e) {
        e.preventDefault();
        const $button = $(this);
        const horarioId = $button.data('horario-id');
        const nonce = $button.data('nonce'); // Nonce específico para eliminar este ID
        const $fila = $button.closest('tr');

        // Acceso seguro al objeto global mph_admin_obj
         if (typeof window.mph_admin_obj === 'undefined' || !window.mph_admin_obj.ajax_url || !window.mph_admin_obj.i18n) {
             console.error("Error crítico: mph_admin_obj o sus propiedades no están disponibles para Eliminar.");
             alert("Error interno.");
             return;
         }
         const ajax_url = mph_admin_obj.ajax_url;
         const i18n = mph_admin_obj.i18n;


        if (!horarioId || !nonce) {
            console.error('Error: Faltan horario_id o nonce para eliminar.');
            alert(i18n.error_general || 'Error inesperado.');
            return;
        }

        const confirmarMsg = i18n.confirmar_eliminacion || '¿Estás seguro de que deseas eliminar este horario?';
        if (confirm(confirmarMsg)) {
            console.log(`Intentando eliminar horario ID: ${horarioId}`);
            $button.prop('disabled', true).css('opacity', 0.5);

            const dataToSend = {
                action: 'mph_eliminar_horario',
                horario_id: horarioId,
                nonce: nonce
            };

            $.post(ajax_url, dataToSend)
                .done(function (response) {
                    if (response.success) {
                        console.log(`Horario ID: ${horarioId} eliminado.`);
                        $fila.fadeOut(400, function() { $(this).remove(); });
                        // alert(i18n.horario_eliminado || 'Horario eliminado.'); // Opcional
                    } else {
                        console.error('Error servidor al eliminar:', response.data?.message);
                        alert(i18n.error_general + (response.data?.message ? ' (' + response.data.message + ')' : ''));
                        $button.prop('disabled', false).css('opacity', 1);
                    }
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.error("Error AJAX al eliminar:", textStatus, errorThrown);
                    alert(i18n.error_general || 'Error de comunicación.');
                    $button.prop('disabled', false).css('opacity', 1);
                });
        } else {
            console.log('Eliminación cancelada.');
        }
    }); // Fin click .mph-accion-eliminar

    // --- Acción Asignar (Desde la tabla) ---
    /* Inicia Modificación: Reescribir Acción Asignar */
    $tableContainer.on('click', '.mph-accion-asignar', function(e) {
         e.preventDefault();
         const $button = $(this);
         console.log('Botón Asignar (desde tabla) clickeado.');

         try {
             const horarioInfo = $button.data('horario-info');
             console.log('Info Horario para Asignar (desde tabla):', horarioInfo);
             if (!horarioInfo || typeof horarioInfo !== 'object' || !horarioInfo.horario_id) {
                 throw new Error("Datos inválidos o falta horario_id en data-horario-info.");
             }

             const $modal = $('#mph-modal-horario');
             if (!$modal.length) { throw new Error("Modal #mph-modal-horario no encontrado."); }
             const $form = $modal.find('form#mph-form-horario');
             if (!$form.length) { throw new Error("Formulario #mph-form-horario no encontrado."); }

             // 1. Resetear el modal a su estado por defecto
             console.log("Reseteando modal antes de asignar...");
             resetModalForm($modal); // Esto quita clases de modo y oculta sección asignación

             // 2. Aplicar clase para modo "Asignar" (CSS oculta sección general y muestra sección asignación)
             $modal.addClass('mph-modal-mode-assign');
             console.log("Clase 'mph-modal-mode-assign' añadida al modal.");

             // 3. Mostrar información del bloque original en #mph-editar-info
             let $infoDiv = $form.find('#mph-editar-info');
             if (!$infoDiv.length) { // Crear si no existe
                 $form.prepend('<div id="mph-editar-info"></div>');
                 $infoDiv = $form.find('#mph-editar-info');
             }
             if ($infoDiv.length) {
                 const dias = ['','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
                 let infoHtml = `<h4>Asignando a: ${dias[horarioInfo.dia] || ''} ${horarioInfo.inicio || ''} - ${horarioInfo.fin || ''}</h4>`;
                 // Podríamos añadir los admisibles originales aquí si quisiéramos
                 $infoDiv.html(infoHtml).show(); // El CSS .mph-modal-mode-assign #mph-editar-info lo muestra
             }

             // 4. Pre-llenar campos ocultos necesarios para que el backend sepa el contexto
             $form.find('#mph_horario_id_editando').val(horarioInfo.horario_id); // ID del bloque Vacío/Mismo que se reemplaza
             $form.find('#mph_dia_semana').val(horarioInfo.dia); // Este campo está oculto por CSS pero lo llenamos
             $form.find('#mph_hora_inicio_general').val(horarioInfo.inicio); // Idem
             $form.find('#mph_hora_fin_general').val(horarioInfo.fin);     // Idem

             // Guardar los admisibles originales del bloque "Vacío" en algún lugar si el backend los necesita
             // y no los puede deducir del horario_id_editando.
             // Por ahora, el backend (mph_guardar_horario_maestro + mph_calcular_bloques)
             // usa hora_inicio_general, hora_fin_general y los checkboxes de la sección 1
             // para determinar los admisibles. Como la sección 1 está oculta, ¡esto es un problema!
             // SOLUCIÓN: Debemos marcar los checkboxes admisibles en la sección 1 (aunque esté oculta).
             console.log("Pre-marcando checkboxes admisibles (ocultos) del bloque original...");
             $form.find('#mph-programas-admisibles-container input').prop('checked', false); // Desmarcar todos primero
             if (horarioInfo.programas_admisibles && Array.isArray(horarioInfo.programas_admisibles)) {
                 horarioInfo.programas_admisibles.forEach(id => { if(id) $form.find('#programa_' + id).prop('checked', true); });
             }
             $form.find('#mph-sedes-admisibles-container input').prop('checked', false);
             if (horarioInfo.sedes_admisibles && Array.isArray(horarioInfo.sedes_admisibles)) {
                 horarioInfo.sedes_admisibles.forEach(id => { if(id) $form.find('#sede_' + id).prop('checked', true); });
             }
             $form.find('#mph-rangos-admisibles-container input').prop('checked', false);
             if (horarioInfo.rangos_admisibles && Array.isArray(horarioInfo.rangos_admisibles)) {
                 horarioInfo.rangos_admisibles.forEach(id => { if(id) $form.find('#rango_edad_' + id).prop('checked', true); });
             }


             // 5. Pre-llenar campos de la Sección de Asignación Específica
             console.log("Pre-llenando campos de asignación específica...");
             $form.find('#mph_hora_inicio_asignada').val(horarioInfo.inicio); // Por defecto, ocupa todo el bloque
             $form.find('#mph_hora_fin_asignada').val(horarioInfo.fin);   // Por defecto, ocupa todo el bloque
             $form.find('#mph_vacantes').val(1); // Default vacantes
             $form.find('#mph_buffer_minutos_antes').val(60); // Default buffer
             $form.find('#mph_buffer_minutos_despues').val(60);
             $form.find('#mph_buffer_linkeado').prop('checked', true);

             // Poblar los selects de Programa/Sede/Rango basándose en los checkboxes recién marcados
             console.log("Poblando selects para asignación...");
             poblarSelectsAsignacion($modal); // Usar la función importada (que lee checkboxes)

             // 6. Ajustar botón Guardar
             const $btnGuardar = $form.find('#mph-guardar-horario');
             if ($btnGuardar.length) {
                 $btnGuardar.text(window.mph_admin_obj?.i18n?.guardar_asignacion || 'Guardar Asignación'); // Nuevo texto i18n
                 $btnGuardar.attr('data-action-mode', 'assign_to_existing'); // Nuevo modo
             }

             // 7. Abrir el modal
             console.log("Abriendo modal para asignar a existente...");
             openModal($modal);

         } catch (e) {
             console.error("Error al preparar modal para Asignar:", e);
             alert("Error al preparar el formulario de asignación.");
         }
    });

    // --- Acción Editar (Disponibilidad) --- (Placeholder por ahora)
     $tableContainer.on('click', '.mph-accion-editar-disp', function(e) {
        e.preventDefault();
        console.log('Botón Editar Disp. clickeado (Funcionalidad Pendiente)');
        alert('Editar Disponibilidad aún no implementado.');
        // TODO: Lógica similar a Asignar pero pre-llenando el modal completo
    }); 

    // --- Acción Editar Vacantes ---
     $tableContainer.on('click', '.mph-accion-editar-vacantes', function(e) {
         e.preventDefault();
         const $button = $(this);
         console.log('Botón Editar Vacantes clickeado.');
         try {
             const horarioInfo = $button.data('horario-info');
             // ... (verificación horarioInfo) ...
             const $modal = $('#mph-modal-horario');
             if (!$modal.length) { 
                console.error("Error en prepareModalForEditVacantes: Modal #mph-modal-horario no encontrado.");
                throw new Error("Modal #mph-modal-horario no encontrado."); // Re-lanzar para que el catch externo lo maneje
            }

             resetModalForm($modal); // Resetear primero (quita clases de modo)
             prepareModalForEditVacantes($modal, horarioInfo); // Preparar (añade clase de modo)
             openModal($modal); // Abrir

        } catch (e) { console.error("Error al preparar/abrir modal para Editar Vacantes:", e);
        alert(window.mph_admin_obj?.i18n?.error_preparar_edicion); }
    });
    
    // --- Acción Vaciar */
    $tableContainer.on('click', '.mph-accion-vaciar', function(e) {
         e.preventDefault();
         const $button = $(this);
         const horarioId = $button.data('horario-id');
         const nonce = $button.data('nonce');
         const $fila = $button.closest('tr'); // Fila para feedback visual

         if (!horarioId || !nonce) { console.error('Error: Faltan horario_id o nonce para vaciar.');
         alert(window.mph_admin_obj?.i18n?.error_general || 'Error inesperado.'); return; }
         
         if (typeof window.mph_admin_obj === 'undefined' || !window.mph_admin_obj.ajax_url || !window.mph_admin_obj.i18n) { console.error("Error crítico: mph_admin_obj o sus propiedades no están disponibles para Vaciar.");
         alert(window.mph_admin_obj?.i18n?.error_configuracion /*|| "Error interno de configuración."*/); return; }
         const ajax_url = mph_admin_obj.ajax_url;
         const i18n = mph_admin_obj.i18n;

         const confirmarMsg = i18n.confirmar_vaciado || '¿Estás seguro? Esto convertirá el horario asignado en un bloque vacío.';
         if (confirm(confirmarMsg)) {
             console.log(`Intentando vaciar horario ID: ${horarioId}`);
             $button.prop('disabled', true).css('opacity', 0.5);

             const dataToSend = {
                 action: 'mph_vaciar_horario', // NUEVA ACCIÓN PHP
                 horario_id: horarioId,
                 nonce: nonce // Nonce específico para vaciar
             };

             $.post(ajax_url, dataToSend)
                 .done(function (response) {
                     if (response.success) {
                         console.log(`Horario ID: ${horarioId} vaciado con éxito.`);
                         // Actualizar la tabla completa para reflejar el cambio
                         if (response.data && response.data.html_tabla && $tableContainer.length) {
                             console.log("Actualizando tabla de horarios después de vaciar...");
                             $tableContainer.html(response.data.html_tabla);
                         } else {
                             console.warn("Respuesta exitosa pero no se encontró HTML de tabla para actualizar.");
                             // Quizás solo actualizar la fila visualmente? Más complejo.
                             $button.prop('disabled', false).css('opacity', 1); // Reactivar si no hay update
                         }
                         // alert(i18n.horario_vaciado || 'Horario vaciado.'); // Opcional
                     } else {
                        console.error('Error servidor al vaciar:', response.data?.message);
                        alert( (window.mph_admin_obj?.i18n?.error_general || 'Error.') + (response.data?.message ? ' (' + response.data.message + ')' : '')); $button.prop('disabled', false).css('opacity', 1); }
                 })
                 .fail(function (jqXHR, textStatus, errorThrown) { console.error("Error AJAX al vaciar:", textStatus, errorThrown, jqXHR.responseText); 
                    alert(window.mph_admin_obj?.i18n?.error_comunicacion /*|| 'Error de comunicación al intentar vaciar.'*/); $button.prop('disabled', false).css('opacity', 1); });
         } else { console.log('Vaciado cancelado.'); }
    });

    console.log(`Acciones de tabla para "${tableContainerSelector}" inicializadas.`);
}