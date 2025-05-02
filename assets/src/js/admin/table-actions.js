// assets/src/js/admin/table-actions.js
import $ from 'jquery';
import { openModal, resetModalForm } from './modal-init';


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

    // --- Acción Asignar ---
    $tableContainer.on('click', '.mph-accion-asignar', function(e) {
         e.preventDefault();
         const $button = $(this);
         const horarioInfoString = $button.data('horario-info');

         console.log('Botón Asignar clickeado.');

         if (!horarioInfoString) {
             console.error("Error: No se encontró data-horario-info en el botón Asignar.");
             alert("Error al obtener información del horario.");
             return;
         }

         try {
            // 1. Leer el atributo data-* usando jQuery. jQuery intenta parsear automáticamente.
            const horarioInfo = $button.data('horario-info');
            console.log('Info Horario Objeto (de .data()):', horarioInfo);




            const $modal = $('#mph-modal-horario');
             if (!$modal.length) { throw new Error("No se pudo obtener un objeto válido de data-horario-info."); }
             const $form = $modal.find('form#mph-form-horario');
             if (!$form.length) { throw new Error("No se pudo obtener un objeto válido de data-horario-info."); }

             // 4. Resetear el formulario completamente
             console.log("Reseteando modal antes de asignar...");
             resetModalForm($modal); // Llamar a la función importada

             // 5. Pre-llenar campos de Disponibilidad General
             console.log("Pre-llenando campos generales...");
             $form.find('#mph_dia_semana').val(horarioInfo.dia || ''); // Buscar dentro de $form
             $form.find('#mph_hora_inicio_general').val(horarioInfo.inicio || '');
             $form.find('#mph_hora_fin_general').val(horarioInfo.fin || '');






             // 6. Pre-seleccionar checkboxes
             if (horarioInfo.programas_admisibles && Array.isArray(horarioInfo.programas_admisibles)) {
                 horarioInfo.programas_admisibles.forEach(id => {
                     if(id) $modal.find('#programa_' + id).prop('checked', true);
                 });
             }
              if (horarioInfo.sedes_admisibles && Array.isArray(horarioInfo.sedes_admisibles)) {
                 horarioInfo.sedes_admisibles.forEach(id => {
                      if(id) $modal.find('#sede_' + id).prop('checked', true);
                 });
             }
             if (horarioInfo.rangos_admisibles && Array.isArray(horarioInfo.rangos_admisibles)) {
                 // Ojo con el slug aquí si era diferente (rango_edad vs rango_de_edad)
                 horarioInfo.rangos_admisibles.forEach(id => {
                      if(id) $modal.find('#rango_edad_' + id).prop('checked', true); // Usar slug correcto
                 });
             }
             console.log("Checkboxes admisibles pre-seleccionados.");

             // 7. Mostrar sección de asignación específica y poblar sus selects
             // console.log("Mostrando sección de asignación...");
             // const $seccionAsignacion = $modal.find('.mph-asignacion-especifica');
             // if ($seccionAsignacion.length) {
             //     // Llamar a poblarSelects (necesitaríamos importarla o rehacer la lógica aquí)
             //     // Por ahora, asumimos que existe una función global o la importamos si es necesario.
             //     // Necesita ejecutarse DESPUÉS de marcar los checkboxes.
             //     if (typeof poblarSelectsAsignacion === "function") { // ¿Está disponible globalmente? (No ideal)
             //         poblarSelectsAsignacion($modal); // Necesita $modal si busca elementos internos
             //         console.log("Selects de asignación poblados.");
             //     } else {
             //          console.warn("Función poblarSelectsAsignacion no encontrada/importada en table-actions.js");
             //          // Podríamos llamar al trigger change de los checkboxes para que se actualice si el listener está activo
             //          $modal.find('#mph-programas-admisibles-container input:checked').first().trigger('change');
             //     }


                 // Pre-llenar horas de asignación con las generales (que acabamos de poner)
                 $modal.find('#mph_hora_inicio_asignada').val(horarioInfo.inicio || '');
                 $modal.find('#mph_hora_fin_asignada').val(horarioInfo.fin || '');
                 console.log("Horas de asignación pre-llenadas.");

             //     // Mostrar la sección (sin animación para rapidez)
             //     $seccionAsignacion.show();
             //     console.log("Sección de asignación mostrada.");
             // } else {
             //      console.error("Error: Sección de asignación no encontrada en el modal.");
             // }


             // 8. Añadir el ID del horario original que se va a reemplazar/dividir
             $modal.find('#mph_horario_id_editando').val(horarioInfo.horario_id || '');
             console.log("ID de horario original (" + (horarioInfo.horario_id || 'N/A') + ") establecido para reemplazo.");


             // 9. Abrir el modal
             console.log("Abriendo modal para asignar...");
             openModal($modal); // Llamar a la función importada

         } catch (e) {
             console.error("Error al parsear horarioInfo o pre-llenar modal para Asignar:", e);
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
             if (!$modal.length) { throw new Error(/* ... */); }

             resetModalForm($modal); // Resetear primero (quita clases de modo)
             prepareModalForEditVacantes($modal, horarioInfo); // Preparar (añade clase de modo)
             openModal($modal); // Abrir

        } catch (e) { /* ... manejo error ... */ }
    });
    
    // --- Acción Vaciar */
    $tableContainer.on('click', '.mph-accion-vaciar', function(e) {
         e.preventDefault();
         const $button = $(this);
         const horarioId = $button.data('horario-id');
         const nonce = $button.data('nonce');
         const $fila = $button.closest('tr'); // Fila para feedback visual

         if (!horarioId || !nonce) { /* ... error ... */ return; }
         if (typeof window.mph_admin_obj === 'undefined' || !window.mph_admin_obj.ajax_url || !window.mph_admin_obj.i18n) { /* ... error ... */ return; }
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
                     } else { /* ... manejo error servidor ... */ $button.prop('disabled', false).css('opacity', 1); }
                 })
                 .fail(function (jqXHR, textStatus, errorThrown) { /* ... manejo error AJAX ... */ $button.prop('disabled', false).css('opacity', 1); });
         } else { console.log('Vaciado cancelado.'); }
    });

    console.log(`Acciones de tabla para "${tableContainerSelector}" inicializadas.`);
}