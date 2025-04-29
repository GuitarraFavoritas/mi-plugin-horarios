// assets/src/js/admin/table-actions.js
import $ from 'jquery';
// Importar funciones para interactuar con el modal si es necesario para Editar/Asignar
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
         const horarioInfo = $button.data('horario-info'); // Obtener el JSON de datos
         console.log('Botón Asignar clickeado. Info:', horarioInfo);
         alert('Funcionalidad "Asignar" aún no implementada en JS.');
         // TODO:
         // 1. Parsear horarioInfo (es un string JSON).
         // 2. Llamar a resetModalForm (importada o global).
         // 3. Pre-llenar el modal con los datos de horarioInfo (horas generales, admisibles).
         // 4. Mostrar directamente la sección de asignación específica.
         // 5. Abrir el modal (llamar a openModal importada o global).
         // 6. Añadir el 'horario_id' al campo oculto #mph_horario_id_editando para que el backend sepa qué reemplazar/dividir.
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