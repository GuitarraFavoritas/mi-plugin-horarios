// assets/src/js/admin/table-actions.js
import $ from 'jquery';

import { openModal, resetModalForm } from './modal-init';
import { poblarSelectsAsignacion } from './modal-form-interaction';
// Podríamos necesitar una función específica para pre-llenar el formulario
// import { prefillModalForAssignment } from './modal-helpers'; // (La crearemos si es necesario)

// import { openModal, resetModalForm, /* otras funciones como prellenarFormulario */ } from './modal-init';

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

    // --- Acción Asignar (Placeholder) ---
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

             // 2. Verificar si obtuvimos un objeto válido
             if (!horarioInfo || typeof horarioInfo !== 'object') {
                 throw new Error("No se pudo obtener un objeto válido de data-horario-info.");
             }    
            

             // 3. Obtener referencia al modal (necesario para funciones de modal)
             const $modal = $('#mph-modal-horario');
             if (!$modal.length) {
                  console.error("Error: Modal #mph-modal-horario no encontrado.");
                  return;
             }

             // 4. Resetear el formulario completamente
             console.log("Reseteando modal antes de asignar...");
             resetModalForm($modal); // Llamar a la función importada

             // 5. Pre-llenar campos de Disponibilidad General
             console.log("Pre-llenando campos generales...");
             $modal.find('#mph_dia_semana').val(horarioInfo.dia || '');
             $modal.find('#mph_hora_inicio_general').val(horarioInfo.inicio || '');
             $modal.find('#mph_hora_fin_general').val(horarioInfo.fin || '');

             // 6. Pre-seleccionar checkboxes admisibles
             // (resetModalForm ya debería marcar los comunes, aquí marcamos los específicos de este bloque)
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
             console.log("Mostrando sección de asignación...");
             const $seccionAsignacion = $modal.find('.mph-asignacion-especifica');
             if ($seccionAsignacion.length) {
                 // Llamar a poblarSelects (necesitaríamos importarla o rehacer la lógica aquí)
                 // Por ahora, asumimos que existe una función global o la importamos si es necesario.
                 // Necesita ejecutarse DESPUÉS de marcar los checkboxes.
                 if (typeof poblarSelectsAsignacion === "function") { // ¿Está disponible globalmente? (No ideal)
                     poblarSelectsAsignacion($modal); // Necesita $modal si busca elementos internos
                     console.log("Selects de asignación poblados.");
                 } else {
                      console.warn("Función poblarSelectsAsignacion no encontrada/importada en table-actions.js");
                      // Podríamos llamar al trigger change de los checkboxes para que se actualice si el listener está activo
                      $modal.find('#mph-programas-admisibles-container input:checked').first().trigger('change');
                 }


                 // Pre-llenar horas de asignación con las generales (que acabamos de poner)
                 $modal.find('#mph_hora_inicio_asignada').val(horarioInfo.inicio || '');
                 $modal.find('#mph_hora_fin_asignada').val(horarioInfo.fin || '');
                 console.log("Horas de asignación pre-llenadas.");

                 // Mostrar la sección (sin animación para rapidez)
                 $seccionAsignacion.show();
                 console.log("Sección de asignación mostrada.");
             } else {
                  console.error("Error: Sección de asignación no encontrada en el modal.");
             }


             // 8. Añadir el ID del horario original que se va a reemplazar/dividir
             $modal.find('#mph_horario_id_editando').val(horarioInfo.horario_id || '');
             console.log("ID de horario original (" + horarioInfo.horario_id + ") establecido para edición.");


             // 9. Abrir el modal
             console.log("Abriendo modal para asignar...");
             openModal($modal); // Llamar a la función importada

         } catch (e) {
             console.error("Error al parsear horarioInfo o pre-llenar modal para Asignar:", e);
             alert("Error al preparar el formulario de asignación.");
         }
    });

     // --- Acción Editar (Placeholder) ---
     $tableContainer.on('click', '.mph-accion-editar', function(e) {
         e.preventDefault();
         const $button = $(this);
         const horarioInfo = $button.data('horario-info'); // Obtener el JSON de datos
         console.log('Botón Editar clickeado. Info:', horarioInfo);
          alert('Funcionalidad "Editar" aún no implementada en JS.');
         // TODO:
         // 1. Parsear horarioInfo.
         // 2. Llamar a resetModalForm.
         // 3. Pre-llenar TODOS los campos del modal (generales y específicos) con los datos de horarioInfo.
         // 4. Mostrar ambas secciones del modal (general y específica).
         // 5. Abrir el modal.
         // 6. Añadir el 'horario_id' al campo oculto #mph_horario_id_editando.
    });

    console.log(`Acciones de tabla para "${tableContainerSelector}" inicializadas.`);
}