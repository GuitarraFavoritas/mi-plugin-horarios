/**
 * Lógica JavaScript para el Meta Box y Modal de Gestión de Horarios del Maestro.
 *
 * Maneja la apertura/cierre del modal, la interacción con los campos del formulario,
 * la visualización condicional de la sección de asignación, validaciones básicas
 * y la preparación para enviar los datos vía AJAX.
 *
 * @package MiPluginHorarios/Assets/JS
 * @version 1.0.0
 */

/* global jQuery, mph_admin_obj, console */ // Informa a linters sobre variables globales

jQuery(document).ready(function ($) {
	'use strict';

	// --- Variables y Selectores Comunes ---
	const $metaBoxWrapper = $('.mph-gestion-horarios-wrapper'); // Contenedor del meta box
	if (!$metaBoxWrapper.length) {
		return; // Salir si el meta box no está presente en la página
	}

	const $modal = $('#mph-modal-horario');
	// const $form = $('#mph-form-horario');
	const $btnAbrirModal = $('#mph-abrir-modal-horario');
	const $btnCancelarModal = $('#mph-cancelar-modal');
	const $btnMostrarAsignacion = $('#mph-mostrar-asignacion');
	const $seccionAsignacion = $('.mph-asignacion-especifica');
	const $btnGuardar = $('#mph-guardar-horario');
	const $spinner = $metaBoxWrapper.find('.spinner').first(); // Spinner junto al botón principal
    const $spinnerModal = $modal.find('.mph-modal-acciones .spinner'); // Spinner dentro del modal
	const $feedbackModal = $modal.find('.mph-modal-feedback');
    const $errorModal = $modal.find('.mph-modal-error');

    // Campos del formulario (selección frecuente)
    const $horarioIdEditando = $('#mph_horario_id_editando');
    const $diaSemana = $('#mph_dia_semana');
    const $horaInicioGeneral = $('#mph_hora_inicio_general');
    const $horaFinGeneral = $('#mph_hora_fin_general');
    const $programasAdmisiblesCheckboxes = $('#mph-programas-admisibles-container input[type="checkbox"]');
    const $sedesAdmisiblesCheckboxes = $('#mph-sedes-admisibles-container input[type="checkbox"]');
    const $rangosAdmisiblesCheckboxes = $('#mph-rangos-admisibles-container input[type="checkbox"]');
    const $horaInicioAsignada = $('#mph_hora_inicio_asignada');
    const $horaFinAsignada = $('#mph_hora_fin_asignada');
    const $selectProgramaAsignado = $('#mph_programa_asignado');
    const $selectSedeAsignada = $('#mph_sede_asignada');
    const $selectRangoAsignado = $('#mph_rango_edad_asignado');
    const $vacantes = $('#mph_vacantes');
    const $bufferAntes = $('#mph_buffer_minutos_antes');
    const $bufferDespues = $('#mph_buffer_minutos_despues');
    const $bufferLinkeado = $('#mph_buffer_linkeado');


	// --- Inicialización del Modal (jQuery UI Dialog) ---
	$modal.dialog({
		autoOpen: false, // No abrir automáticamente al cargar la página
		modal: true, // Efecto modal (oscurece el fondo)
		width: 600, // Ancho del modal
		height: 'auto', // Altura automática basada en contenido
		draggable: true, // Permitir arrastrar
		resizable: false, // No permitir redimensionar
		closeText: "Cerrar", // Texto para el botón de cierre (accesibilidad)
		classes: { // Clases CSS adicionales para personalizar si es necesario
        	"ui-dialog": "mph-jquery-ui-modal", // Clase para el contenedor principal del diálogo
    	},
		open: function() {
			// Código a ejecutar cuando el modal se abre
			$feedbackModal.hide().text('');
            $errorModal.hide().text('');
            $spinnerModal.removeClass('is-active');
		},
		close: function() {
			// Código a ejecutar cuando el modal se cierra (por botón X o Cancelar)
			// Limpiar el formulario para la próxima vez que se abra
			resetModalForm();
		}
	});

	// --- Funciones Auxiliares ---

	/**
	 * Resetea el formulario del modal a su estado inicial.
	 */
	function resetModalForm() {

        const $form = $('#mph-form-horario'); // <-- Define $form aquí
        if (!$form.length) {
             console.error('Error: No se encontró el elemento #mph-form-horario.');
             return; // Salir si no se encuentra el formulario
        }

		$form[0].reset(); // Resetea campos estándar del formulario
        $horarioIdEditando.val(''); // Limpia el ID de edición
		// Desmarcar checkboxes (si .reset() no lo hace bien para checkboxes cargados dinámicamente)
		$programasAdmisiblesCheckboxes.prop('checked', false);
		$sedesAdmisiblesCheckboxes.prop('checked', false);
		$rangosAdmisiblesCheckboxes.prop('checked', false);
        // Limpiar selects de asignación
        $selectProgramaAsignado.empty().append('<option value="">-- Seleccionar Programa --</option>');
        $selectSedeAsignada.empty().append('<option value="">-- Seleccionar Sede --</option>');
        $selectRangoAsignado.empty().append('<option value="">-- Seleccionar Rango --</option>');
        // Ocultar sección de asignación y mensajes de error
		$seccionAsignacion.hide();
		$modal.find('.mph-error-hora, .mph-error-duplicado, .mph-error-hora-asignada').hide();
        $feedbackModal.hide().text('');
        $errorModal.hide().text('');
        $spinnerModal.removeClass('is-active');
        // Restaurar valores por defecto específicos si es necesario
        $vacantes.val(1);
        $bufferAntes.val(60);
        $bufferDespues.val(60);
        $bufferLinkeado.prop('checked', true); // Asegurar que el link esté activo por defecto
	}

    /**
	 * Puebla los selects de la sección de asignación basándose
     * en los checkboxes seleccionados en la sección general.
	 */
    function poblarSelectsAsignacion() {
        // Limpiar selects actuales
        // $selectProgramaAsignado.empty().append('<option value="">-- Seleccionar Programa --</option>');
        // $selectSedeAsignada.empty().append('<option value="">-- Seleccionar Sede --</option>');
        // $selectRangoAsignado.empty().append('<option value="">-- Seleccionar Rango --</option>');

        $selectProgramaAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Programa --' }));
        $selectSedeAsignada.empty().append($('<option>', { value: '', text: '-- Seleccionar Sede --' }));
        $selectRangoAsignado.empty().append($('<option>', { value: '', text: '-- Seleccionar Rango --' }));


        // Programas: Iterar sobre los checkboxes de programas admisibles *seleccionados*
        // let primerProgramaNoComunAnadido = false;
        // let primerSedeAnadida = false; // Asumimos preseleccionar la primera sede (común o no)
        // let primerRangoAnadido = false; // Asumimos preseleccionar el primer rango (común o no)
        let primerProgramaNoComunOption = null; // Guardará la referencia al primer <option> no común
        let primerProgramaOption = null;        // Guardará la referencia al primer <option> (sea común o no)

        let primerSedeNoComunOption = null;
        let primerSedeOption = null;

        let primerRangoNoComunOption = null;
        let primerRangoOption = null;

        // Programas: Iterar sobre los checkboxes de programas admisibles *seleccionados*
        
        
        
        // $('#mph-programas-admisibles-container input:checked').each(function() {
        //     const termId = $(this).val();
        //     const termName = $(this).parent('label').text().trim().replace(' (Común)', ''); // Limpiar etiqueta visual

        //     // Buscar la data completa de este programa para saber si es común 
        //     const programaData = mph_admin_obj.todos_programas.find(p => p.term_id == termId);
        //     const esComun = programaData ? programaData.es_comun : false; // Obtener el flag 'es_comun'

        //     // Añadir al select SOLO si NO es común (según req 4.6.2 implícito)
        //     if (!esComun) {
        //         const $option = $('<option>', {
        //             value: termId,
        //             text: termName
        //         });
        //         $selectProgramaAsignado.append($option);

        //         // Seleccionar la primera opción no común que se añade
        //         if (!primerProgramaNoComunAnadido) {
        //             $option.prop('selected', true);
        //             primerProgramaNoComunAnadido = true;
        //         }
        //     }
        // });

        // // Sedes: Iterar sobre checkboxes de sedes admisibles *seleccionados*
        // $('#mph-sedes-admisibles-container input:checked').each(function() {
        //     const termId = $(this).val();
        //     const termName = $(this).parent('label').text().trim().replace(' (Común)', '');

        //     // Buscar la data completa de esta sede para saber si es común
        //     const sedeData = mph_admin_obj.todas_sedes.find(s => s.term_id == termId); // Podríamos necesitar info de sede común más tarde
        //     const esSedeComun = sedeData ? sedeData.es_comun : false;

        //     const $option = $('<option>', { value: termId, text: termName });
        //     $selectSedeAsignada.append($option);

        //      // Seleccionar la primera sede que se añade (req 4.6.5)

        //      if (!esSedeComun) {
        //         const $option = $('<option>', {
        //             value: termId,
        //             text: termName
        //         });
        //         $selectSedeAsignada.append($option);

        //         // Seleccionar la primera opción no común que se añade
        //         if (!primerSedeAnadida) {
        //             $option.prop('selected', true);
        //             primerSedeAnadida = true;
        //         }
        //     }
             
        // });

        // // Rangos: Iterar sobre checkboxes de rangos admisibles *seleccionados*
        //  $('#mph-rangos-admisibles-container input:checked').each(function() {
        //     const termId = $(this).val();
        //     const termName = $(this).parent('label').text().trim().replace(' (Común)', '');


        //     const rangoData = mph_admin_obj.todos_rangos.find(r => r.term_id == termId);
        //     const esRangoComun = rangoData ? rangoData.es_comun : false;
        //     // Buscar la data completa de este rango para saber si es común
        //     const $option = $('<option>', { value: termId, text: termName });
        //     $selectRangoAsignado.append($option);

        //      // Seleccionar el primer rango que se añade (req 4.6.4)

        //      if (!esRangoComun) {
        //         const $option = $('<option>', {
        //             value: termId,
        //             text: termName
        //         });
        //         $selectRangoAsignado.append($option);

        //         // Seleccionar la primera opción no común que se añade
        //         if (!primerRangoAnadido) {
        //             $option.prop('selected', true);
        //             primerRangoAnadido = true;
        //          }
        //     }



             
        // });


        $('#mph-programas-admisibles-container input:checked').each(function() {
            const termId = $(this).val();
            const termName = $(this).parent('label').text().trim().replace(' (Común)', '');
            const programaData = mph_admin_obj.todos_programas.find(p => p.term_id == termId);
            const esComun = programaData ? programaData.es_comun : false;

            // Crear la opción y añadirla siempre
            const $option = $('<option>', {
                value: termId,
                text: termName,
                // Podemos añadir un data-attribute si necesitamos saber si es común más tarde
                'data-es-comun': esComun
            });
            $selectProgramaAsignado.append($option);

            // Guardar referencia a la primera opción añadida (para fallback)
            if (primerProgramaOption === null) {
                primerProgramaOption = $option;
            }
            // Guardar referencia a la primera opción NO común añadida
            if (!esComun && primerProgramaNoComunOption === null) {
                primerProgramaNoComunOption = $option;
            }
        });

        // Pre-seleccionar Programa: Prioriza la primera no común, si no hay, selecciona la primera común.
        if (primerProgramaNoComunOption) {
            primerProgramaNoComunOption.prop('selected', true);
        } else if (primerProgramaOption) {
            primerProgramaOption.prop('selected', true); // Fallback a la primera opción si todas eran comunes
        }


        // Sedes: Iterar sobre checkboxes de sedes admisibles *seleccionados*
        $('#mph-sedes-admisibles-container input:checked').each(function() { // Aplicar misma lógica
            const termId = $(this).val();
            const termName = $(this).parent('label').text().trim().replace(' (Común)', '');
            const sedeData = mph_admin_obj.todas_sedes.find(s => s.term_id == termId);
            const esSedeComun = sedeData ? sedeData.es_comun : false;

            const $option = $('<option>', { value: termId, text: termName, 'data-es-comun': esSedeComun });
            $selectSedeAsignada.append($option);

            if (primerSedeOption === null) { primerSedeOption = $option; }
            if (!esSedeComun && primerSedeNoComunOption === null) { primerSedeNoComunOption = $option; }
        });

        // Pre-seleccionar Sede:
        if (primerSedeNoComunOption) {
            primerSedeNoComunOption.prop('selected', true);
        } else if (primerSedeOption) {
            primerSedeOption.prop('selected', true);
        }


        // Rangos: Iterar sobre checkboxes de rangos admisibles *seleccionados*
         $('#mph-rangos-admisibles-container input:checked').each(function() { // Aplicar misma lógica
            const termId = $(this).val();
            const termName = $(this).parent('label').text().trim().replace(' (Común)', '');
            const rangoData = mph_admin_obj.todos_rangos.find(r => r.term_id == termId);
            const esRangoComun = rangoData ? rangoData.es_comun : false;

            const $option = $('<option>', { value: termId, text: termName, 'data-es-comun': esRangoComun });
            $selectRangoAsignado.append($option);

            if (primerRangoOption === null) { primerRangoOption = $option; }
            if (!esRangoComun && primerRangoNoComunOption === null) { primerRangoNoComunOption = $option; }
        });

         // Pre-seleccionar Rango:
        if (primerRangoNoComunOption) {
            primerRangoNoComunOption.prop('selected', true);
        } else if (primerRangoOption) {
            primerRangoOption.prop('selected', true);
        }

    }

     /**
     * Valida los campos del formulario antes de enviar.
     * @returns {boolean} True si es válido, False si no.
     */
     function validarFormulario() {
        console.log('--- Iniciando validarFormulario ---');
        let isValid = true;
        $errorModal.hide().text(''); // Limpiar errores previos

        // Validación básica de horas generales
        const horaInicioGen = $horaInicioGeneral.val();
        const horaFinGen = $horaFinGeneral.val();
        if (!horaInicioGen || !horaFinGen) {
             $errorModal.text(mph_admin_obj.i18n.error_general || 'Faltan horas generales.').show(); // Usar texto localizado
             // Podríamos marcar los campos específicos
             isValid = false;
        } else if (horaFinGen <= horaInicioGen) {
            $modal.find('.mph-error-hora').show();
            isValid = false;
        } else {
             $modal.find('.mph-error-hora').hide();
        }

         // Validación de al menos una opción admisible seleccionada por tipo (opcional pero recomendable)
         // EJEMPLO de cómo loguear una validación específica:
         if ($('#mph-programas-admisibles-container input:checked').length === 0) {
            console.log('Validación fallida: No hay programas admisibles seleccionados.'); // Log específico
             $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_programa || 'Debe seleccionar al menos un programa admisible.') + '</p>').show();
             isValid = false;
         }
          if ($('#mph-sedes-admisibles-container input:checked').length === 0) {
             $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_sede || 'Debe seleccionar al menos una sede admisible.') + '</p>').show();
             isValid = false;
         }
         if ($('#mph-rangos-admisibles-container input:checked').length === 0) {
             $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_rango || 'Debe seleccionar al menos un rango de edad admisible.') + '</p>').show();
             isValid = false;
         }


        // Si la sección de asignación está visible, validar sus campos
        if ($seccionAsignacion.is(':visible')) {
            const horaInicioAsig = $horaInicioAsignada.val();
            const horaFinAsig = $horaFinAsignada.val();

            if (!horaInicioAsig || !horaFinAsig) {
                $errorModal.append('<p>' + (mph_admin_obj.i18n.error_faltan_horas_asignadas || 'Faltan horas de asignación.') + '</p>').show();
                isValid = false;
            } else {
                if (horaFinAsig <= horaInicioAsig) {
                     $modal.find('.mph-error-hora-asignada').text(mph_admin_obj.i18n.error_hora_asignada_invalida || 'Hora fin asignada debe ser posterior a inicio.').show();
                     isValid = false;
                } else if (horaInicioAsig < horaInicioGen || horaFinAsig > horaFinGen) {
                    $modal.find('.mph-error-hora-asignada').text(mph_admin_obj.i18n.error_hora_asignada_rango || 'Horas asignadas fuera del rango general.').show();
                    isValid = false;
                } else {
                    $modal.find('.mph-error-hora-asignada').hide();
                }
            }


            // Validar que se haya seleccionado una opción en los selects de asignación
            if (!$selectProgramaAsignado.val()) {
                 $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_programa_asig || 'Debe seleccionar un programa para la asignación.') + '</p>').show();
                 isValid = false;
            }
             if (!$selectSedeAsignada.val()) {
                 $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_sede_asig || 'Debe seleccionar una sede para la asignación.') + '</p>').show();
                 isValid = false;
            }
            if (!$selectRangoAsignado.val()) {
                 $errorModal.append('<p>' + (mph_admin_obj.i18n.error_seleccionar_rango_asig || 'Debe seleccionar un rango de edad para la asignación.') + '</p>').show();
                 isValid = false;
            }

            // Validar vacantes
             if (parseInt($vacantes.val(), 10) < 0) {
                 $errorModal.append('<p>' + (mph_admin_obj.i18n.error_vacantes_negativas || 'Las vacantes no pueden ser negativas.') + '</p>').show();
                isValid = false;
             }

             // Validar buffer (ej. >= 0)
            const bufferAntesVal = parseInt($bufferAntes.val(), 10);
            const bufferDespuesVal = parseInt($bufferDespues.val(), 10);

            if (isNaN(bufferAntesVal) || bufferAntesVal < 0) {
                $errorModal.append('<p>' + (mph_admin_obj.i18n.error_buffer_antes_invalido || 'El tiempo de buffer Antes debe ser un número positivo.') + '</p>').show();
                isValid = false;
            }
            if (isNaN(bufferDespuesVal) || bufferDespuesVal < 0) {
                $errorModal.append('<p>' + (mph_admin_obj.i18n.error_buffer_despues_invalido || 'El tiempo de buffer Después debe ser un número positivo.') + '</p>').show();
                isValid = false;
            }



        }

        console.log('--- Finalizando validarFormulario --- Retornando:', isValid);

        return isValid;
     }


	// --- Manejadores de Eventos ---

	// Abrir el modal al hacer clic en el botón principal

	$btnAbrirModal.on('click', function () { //<-- Comenta o elimina esta línea
        console.log('¡Clic detectado en el botón Abrir Modal!')
    // $metaBoxWrapper.on('click', '#mph-abrir-modal-horario', function () { // <-- Usa esta línea en su lugar
        //console.log('¡Clic detectado en el botón Abrir Modal (Delegado)!'); // Modifica el log para diferenciar
        resetModalForm(); // Asegura que el form esté limpio al abrir para 'nuevo'
		$modal.dialog('open');
	});

	// Cerrar el modal al hacer clic en Cancelar
	$btnCancelarModal.on('click', function () {
		$modal.dialog('close');
	});

	// Mostrar/Ocultar sección de asignación específica
	$btnMostrarAsignacion.on('click', function () {
		$seccionAsignacion.slideToggle(function() {
             if ($seccionAsignacion.is(':visible')) {
                // Poblar selects al mostrar la sección
                poblarSelectsAsignacion();
                // Pre-llenar horas de asignación con las generales
                $horaInicioAsignada.val($horaInicioGeneral.val());
                $horaFinAsignada.val($horaFinGeneral.val());
             }
        });
	});

    // Actualizar selects de asignación si cambian los checkboxes generales *mientras* la sección está visible
    $('#mph-programas-admisibles-container, #mph-sedes-admisibles-container, #mph-rangos-admisibles-container').on('change', 'input[type="checkbox"]', function() {
        if ($seccionAsignacion.is(':visible')) {
            poblarSelectsAsignacion();
        }
    });



    /* Inicia Modificación: Añadir lógica para sincronizar buffers */
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

$bufferAntes.on('change input', function() { // 'input' para respuesta más inmediata
    sincronizarBuffers($(this));
});

$bufferDespues.on('change input', function() {
    sincronizarBuffers($(this));
});

// Opcional: Si desmarcan el link, no hacemos nada, pero si lo marcan, igualamos al último modificado?
$bufferLinkeado.on('change', function() {
    if ($(this).is(':checked')) {
        // Podríamos forzar la sincronización aquí si quisiéramos
        $bufferDespues.val($bufferAntes.val()); // Por ejemplo
    }
});
/* Finaliza Modificación */


	// Manejar el envío del formulario (AJAX)
	// $form.on('submit', function (e) {
    $modal.on('submit', '#mph-form-horario', function (e) { // <-- Adjunta el evento al modal, escucha por submits del form
        console.log('--- Evento Submit Detectado en #mph-form-horario ---');
		e.preventDefault(); // Evita el envío normal del formulario
        const $form = $(this); // <-- Define $form aquí, $(this) es el formulario que disparó el submit

        // Validar formulario
        console.log('Llamando a validarFormulario...');
        if (!validarFormulario()) {
            console.log('validarFormulario devolvió false. Envío detenido.');
            return; // Detener si no es válido
        }
        console.log('Validación pasada. Procediendo con AJAX...');

		// Mostrar spinner y limpiar mensajes previos
		$spinnerModal.addClass('is-active');
        $feedbackModal.hide().text('');
        $errorModal.hide().text('');
		$btnGuardar.prop('disabled', true); // Deshabilitar botón para evitar doble envío

        console.log('Spinner y botón modificados (intentado). Preparando datos AJAX...'); // Log adicional

		// Recopilar datos del formulario
		const formData = $form.serialize(); // Recoge todos los campos con 'name'

        // Añadir la acción AJAX y el nonce a los datos
        const dataToSend = formData +
                           '&action=mph_guardar_horario_maestro' + // Nombre de la acción AJAX que crearemos en PHP
                           '&nonce=' + mph_admin_obj.nonce;
        
        console.log('Datos a enviar (sin incluir action/nonce):', formData); // Log de datos

        // *** NOTA IMPORTANTE SOBRE CHECKBOXES NO MARCADOS ***
        // .serialize() NO incluye checkboxes desmarcados. Si en PHP necesitas saber
        // explícitamente TODOS los admisibles (incluyendo los no marcados por el usuario
        // pero sí asignados al maestro o los comunes), deberías construir el objeto de datos
        // manualmente en JS o ajustar la lógica PHP para manejar esto.
        // Por ahora, asumimos que PHP trabajará con los IDs que SÍ se envían (los marcados).

		// Enviar petición AJAX
		$.post(mph_admin_obj.ajax_url, dataToSend)
			.done(function (response) {
				// Éxito
				if (response.success) {
					$feedbackModal.text(mph_admin_obj.i18n.horario_guardado || '¡Guardado!').show();
                    // Actualizar la tabla de horarios (la función se creará en Fase 3)
                    if (response.data && response.data.html_tabla) {
                         $('#mph-tabla-horarios-container').html(response.data.html_tabla);
                    } else {
                        // Si no se devuelve tabla, al menos recargarla o mostrar mensaje
                        // Por ahora, cerramos el modal tras un breve retraso
                    }

					// Cerrar el modal después de un momento
					setTimeout(function () {
						$modal.dialog('close');
					}, 1500);

				} else {
					// Error devuelto por el servidor (ej. validación fallida en PHP, error guardando)
					$errorModal.text(response.data.message || mph_admin_obj.i18n.error_general || 'Error desconocido.').show();
				}
			})
			.fail(function (jqXHR, textStatus, errorThrown) {
				// Error de conexión o del servidor (ej. error 500)
                console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText);
				$errorModal.text(mph_admin_obj.i18n.error_general || 'Error de comunicación.').show();
			})
			.always(function () {
				// Se ejecuta siempre (éxito o fallo)
				$spinnerModal.removeClass('is-active'); // Ocultar spinner
				$btnGuardar.prop('disabled', false); // Habilitar botón
			});
	});

    // --- Manejadores para botones de acción en la tabla (se añadirán en Fase 3) ---
    // Ejemplo:
    // $('#mph-tabla-horarios-container').on('click', '.accion-eliminar', function(e){ ... });
    // $('#mph-tabla-horarios-container').on('click', '.accion-editar', function(e){ ... });
    // $('#mph-tabla-horarios-container').on('click', '.accion-asignar', function(e){ ... });


    // --- Lógica Adicional ---
    // (Podríamos añadir validación de horas en tiempo real al cambiar los inputs 'time', etc.)
    // Ejemplo básico validación hora fin >= hora inicio
    $horaFinGeneral.on('change', function() {
        if ($horaInicioGeneral.val() && $(this).val() <= $horaInicioGeneral.val()) {
            $modal.find('.mph-error-hora').show();
        } else {
            $modal.find('.mph-error-hora').hide();
        }
    });
     $horaFinAsignada.on('change', function() {
        const $errorDiv = $modal.find('.mph-error-hora-asignada');
        const inicioAsig = $horaInicioAsignada.val();
        const finAsig = $(this).val();
        const inicioGen = $horaInicioGeneral.val();
        const finGen = $horaFinGeneral.val();

        if (inicioAsig && finAsig <= inicioAsig) {
            $errorDiv.text(mph_admin_obj.i18n.error_hora_asignada_invalida || 'Hora fin asignada debe ser posterior a inicio.').show();
        } else if (inicioGen && finGen && (inicioAsig < inicioGen || finAsig > finGen)) {
             $errorDiv.text(mph_admin_obj.i18n.error_hora_asignada_rango || 'Horas asignadas fuera del rango general.').show();
        } else {
            $errorDiv.hide();
        }
    });


}); // Fin de jQuery(document).ready