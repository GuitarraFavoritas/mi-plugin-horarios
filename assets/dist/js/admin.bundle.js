/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/js/admin/modal-ajax.js":
/*!*******************************************!*\
  !*** ./assets/src/js/admin/modal-ajax.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initAjaxSubmit: () => (/* binding */ initAjaxSubmit)
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _modal_validation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modal-validation */ "./assets/src/js/admin/modal-validation.js");
/* harmony import */ var _modal_init__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modal-init */ "./assets/src/js/admin/modal-init.js");
// assets/src/js/admin/modal-ajax.js




/**
 * Inicializa el manejador de envío AJAX para el formulario del modal.
 * @param {jQuery} $modal - El objeto jQuery del elemento modal.
 */
function initAjaxSubmit($modal) {
  /* Inicia Modificación: Corregir log y asegurar $modal es válido */
  console.log("Inicializando manejador CLICK para #mph-guardar-horario...");
  if (!$modal || !$modal.length || typeof $modal.find !== 'function') {
    console.error("initAjaxSubmit: No se recibió el objeto $modal válido.");
    return;
  }
  /* Finaliza Modificación */

  var $spinnerModal = $modal.find('.mph-modal-acciones .spinner');
  var $feedbackModal = $modal.find('.mph-modal-feedback');
  var $errorModal = $modal.find('.mph-modal-error');
  var $tablaContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-tabla-horarios-container'); // Asumimos que existe fuera del modal

  $modal.on('click', '#mph-guardar-horario', function (e) {
    console.log('--- Botón Guardar Clickeado ---');
    console.log('Estado de window.mph_admin_obj JUSTO AL HACER CLICK:', window.mph_admin_obj);
    var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    var $form = $modal.find('form#mph-form-horario');
    if (!$form.length) {
      console.error("Error en click Guardar: No se encontró el formulario.");
      alert("Error interno del formulario.");
      return;
    }
    console.log('Llamando a validarFormulario...');
    if (!(0,_modal_validation__WEBPACK_IMPORTED_MODULE_1__.validarFormulario)($form)) {
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
    var formData = $form.serialize();

    // Acceso seguro al objeto global mph_admin_obj
    if (!window.mph_admin_obj || typeof window.mph_admin_obj.ajax_url === 'undefined' || window.mph_admin_obj.ajax_url === '') {
      console.error("Error crítico: window.mph_admin_obj o window.mph_admin_obj.ajax_url no están definidos o están vacíos.");
      if ($errorModal.length) $errorModal.text("Error de configuración interna (AJAX URL). Contacte al administrador.").show();
      $button.prop('disabled', false);
      if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
      return; // Detener si falta
    }

    // Si llegamos aquí, el objeto y ajax_url son válidos.
    var ajax_url = mph_admin_obj.ajax_url;
    // const nonce = mph_admin_obj.nonce; // Ya no necesitamos el nonce de mph_admin_obj
    var i18n = mph_admin_obj.i18n || {};

    // Nombre del campo nonce (debe coincidir con wp_nonce_field en PHP)
    var nonceFieldName = 'mph_nonce_guardar'; // Nuevo nombre del campo
    var $nonceField = $form.find('input[name="' + nonceFieldName + '"]');
    var nonceValue = '';
    if ($nonceField.length) {
      nonceValue = $nonceField.val();
      console.log("Valor del campo nonce (".concat(nonceFieldName, ") le\xEDdo del DOM:"), nonceValue);
    } else {
      console.error("\xA1Error cr\xEDtico! No se encontr\xF3 el campo nonce \"".concat(nonceFieldName, "\" en el formulario."));
      if ($errorModal.length) $errorModal.text("Error de seguridad interno (Falta campo Nonce).").show();
      $button.prop('disabled', false);
      if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
      return;
    }

    // Construir dataToSend: acción + formData (que ya incluye nonce)
    // NO necesitamos añadir el nonce explícitamente si está en formData
    var dataToSend = formData + '&action=mph_guardar_horario_maestro';
    console.log('Datos COMPLETOS a enviar (formData incluye nonce):', dataToSend);

    // Petición AJAX
    jquery__WEBPACK_IMPORTED_MODULE_0___default().post(ajax_url, dataToSend).done(function (response) {
      if (response.success) {
        console.log("Respuesta AJAX exitosa:", response);
        if ($feedbackModal.length) $feedbackModal.text(i18n.horario_guardado || '¡Guardado!').show();
        if (response.data && response.data.html_tabla && $tablaContainer.length) {
          console.log("Actualizando tabla de horarios...");
          $tablaContainer.html(response.data.html_tabla);
        } else {
          console.warn("Respuesta exitosa pero no se encontró HTML de tabla para actualizar.");
        }
        setTimeout(function () {
          (0,_modal_init__WEBPACK_IMPORTED_MODULE_2__.closeModal)($modal);
        }, 1500);
      } else {
        var _response$data, _response$data2;
        console.error('Error servidor (success=false):', (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.message);
        if ($errorModal.length) $errorModal.text(((_response$data2 = response.data) === null || _response$data2 === void 0 ? void 0 : _response$data2.message) || i18n.error_general || 'Error desconocido.').show();
      }
    }).fail(function (jqXHR, textStatus, errorThrown) {
      console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText);
      // Mostrar mensaje de error específico si es 403 (Nonce)
      if (jqXHR.status === 403) {
        if ($errorModal.length) $errorModal.text(i18n.error_seguridad || 'Error de seguridad. Intente recargar la página.').show();
      } else {
        if ($errorModal.length) $errorModal.text(i18n.error_general || 'Error de comunicación.').show();
      }
    }).always(function () {
      console.log("Petición AJAX completada (always).");
      if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
      $button.prop('disabled', false);
    });
  }); // Fin click #mph-guardar-horario

  /* Inicia Modificación: Corregir log final */
  console.log("Manejador CLICK para #mph-guardar-horario inicializado.");
  /* Finaliza Modificación */
} // Fin initAjaxSubmit

/***/ }),

/***/ "./assets/src/js/admin/modal-form-interaction.js":
/*!*******************************************************!*\
  !*** ./assets/src/js/admin/modal-form-interaction.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initFormInteractions: () => (/* binding */ initFormInteractions),
/* harmony export */   poblarSelectsAsignacion: () => (/* binding */ poblarSelectsAsignacion)
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
// assets/src/js/admin/modal-form-interaction.js


// --- Selectores (Podrían pasarse o definirse globalmente si es necesario) ---
// Asumimos que estos selectores apuntan a elementos que existen cuando se llama initFormInteractions
var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario'); // Necesario para delegar eventos
var $seccionAsignacion = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.mph-asignacion-especifica'); // Podríamos buscarlo dentro del modal: $modal.find(...)
var $btnMostrarAsignacion = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-mostrar-asignacion');
var $programasAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-programas-admisibles-container');
var $sedesAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-sedes-admisibles-container');
var $rangosAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-rangos-admisibles-container');
var $selectProgramaAsignado = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_programa_asignado');
var $selectSedeAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_sede_asignada');
var $selectRangoAsignado = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_rango_edad_asignado');
var $horaInicioGeneral = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_inicio_general');
var $horaFinGeneral = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_fin_general');
var $horaInicioAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_inicio_asignada');
var $horaFinAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_fin_asignada');
var $bufferAntes = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_buffer_minutos_antes');
var $bufferDespues = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_buffer_minutos_despues');
var $bufferLinkeado = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_buffer_linkeado');

/**
 * Puebla los selects de la sección de asignación basándose
 * en los checkboxes seleccionados en la sección general.
 */
function poblarSelectsAsignacion() {
  console.log("Poblando selects de asignación..."); // Log

  if (typeof window.mph_admin_obj === 'undefined' || !mph_admin_obj.todos_programas) {
    console.error("poblarSelectsAsignacion: mph_admin_obj o sus datos no están disponibles.");
    // Quizás mostrar un error al usuario o deshabilitar selects?
    $selectProgramaAsignado.empty().append(jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option value="">Error datos</option>'));
    $selectSedeAsignada.empty().append(jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option value="">Error datos</option>'));
    $selectRangoAsignado.empty().append(jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option value="">Error datos</option>'));
    return; // No continuar si no hay datos
  }

  // Limpiar selects actuales
  $selectProgramaAsignado.empty().append(jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option>', {
    value: '',
    text: '-- Seleccionar Programa --'
  }));
  $selectSedeAsignada.empty().append(jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option>', {
    value: '',
    text: '-- Seleccionar Sede --'
  }));
  $selectRangoAsignado.empty().append(jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option>', {
    value: '',
    text: '-- Seleccionar Rango --'
  }));
  var primerProgramaNoComunOption = null;
  var primerProgramaOption = null;
  var primerSedeNoComunOption = null;
  var primerSedeOption = null;
  var primerRangoNoComunOption = null;
  var primerRangoOption = null;

  // Verificar si mph_admin_obj está disponible (debe cargarse antes)
  if (typeof window.mph_admin_obj === 'undefined') {
    console.error("poblarSelectsAsignacion: mph_admin_obj no está disponible.");
    return;
  }

  // Programas
  $programasAdmisiblesContainer.find('input:checked').each(function () {
    var termId = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
    var termName = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).parent('label').text().trim().replace(' (Común)', '');
    var programaData = mph_admin_obj.todos_programas.find(function (p) {
      return p.term_id == termId;
    });
    var esComun = programaData ? programaData.es_comun : false;
    var $option = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option>', {
      value: termId,
      text: termName,
      'data-es-comun': esComun
    });
    $selectProgramaAsignado.append($option);
    if (primerProgramaOption === null) {
      primerProgramaOption = $option;
    }
    if (!esComun && primerProgramaNoComunOption === null) {
      primerProgramaNoComunOption = $option;
    }
  });
  if (primerProgramaNoComunOption) {
    primerProgramaNoComunOption.prop('selected', true);
  } else if (primerProgramaOption) {
    primerProgramaOption.prop('selected', true);
  }

  // Sedes
  $sedesAdmisiblesContainer.find('input:checked').each(function () {
    var termId = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
    var termName = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).parent('label').text().trim().replace(' (Común)', '');
    var sedeData = mph_admin_obj.todas_sedes.find(function (s) {
      return s.term_id == termId;
    });
    var esSedeComun = sedeData ? sedeData.es_comun : false;
    var $option = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option>', {
      value: termId,
      text: termName,
      'data-es-comun': esSedeComun
    });
    $selectSedeAsignada.append($option);
    if (primerSedeOption === null) {
      primerSedeOption = $option;
    }
    if (!esSedeComun && primerSedeNoComunOption === null) {
      primerSedeNoComunOption = $option;
    }
  });
  if (primerSedeNoComunOption) {
    primerSedeNoComunOption.prop('selected', true);
  } else if (primerSedeOption) {
    primerSedeOption.prop('selected', true);
  }

  // Rangos
  $rangosAdmisiblesContainer.find('input:checked').each(function () {
    var termId = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
    var termName = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).parent('label').text().trim().replace(' (Común)', '');
    var rangoData = mph_admin_obj.todos_rangos.find(function (r) {
      return r.term_id == termId;
    });
    var esRangoComun = rangoData ? rangoData.es_comun : false;
    var $option = jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option>', {
      value: termId,
      text: termName,
      'data-es-comun': esRangoComun
    });
    $selectRangoAsignado.append($option);
    if (primerRangoOption === null) {
      primerRangoOption = $option;
    }
    if (!esRangoComun && primerRangoNoComunOption === null) {
      primerRangoNoComunOption = $option;
    }
  });
  if (primerRangoNoComunOption) {
    primerRangoNoComunOption.prop('selected', true);
  } else if (primerRangoOption) {
    primerRangoOption.prop('selected', true);
  }
}

/**
 * Sincroniza los valores de los campos de buffer si están linkeados.
 * @param {jQuery} campoModificado - El campo input que cambió.
 */
function sincronizarBuffers(campoModificado) {
  if ($bufferLinkeado.is(':checked')) {
    var valor = campoModificado.val();
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
function initFormInteractions() {
  console.log('Inicializando interacciones del formulario del modal...');
  if (!$modal.length) {
    console.error("initFormInteractions: Modal no encontrado.");
    return;
  }

  // Mostrar/Ocultar sección de asignación específica (delegado al modal)
  $modal.on('click', '#mph-mostrar-asignacion', function () {
    console.log("Botón Mostrar/Ocultar Asignación clickeado.");
    // Asegurarse que $seccionAsignacion es correcta
    var $seccion = $modal.find('.mph-asignacion-especifica');
    $seccion.slideToggle(function () {
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
  $modal.on('change', '#mph-programas-admisibles-container input[type="checkbox"], #mph-sedes-admisibles-container input[type="checkbox"], #mph-rangos-admisibles-container input[type="checkbox"]', function () {
    // Usar $seccionAsignacion global o buscarla de nuevo
    if ($modal.find('.mph-asignacion-especifica').is(':visible')) {
      console.log("Checkbox admisible cambiado. Repoblando selects.");
      poblarSelectsAsignacion();
    }
  });

  // Sincronizar buffers (delegado al modal)
  $modal.on('change input', '#mph_buffer_minutos_antes', function () {
    sincronizarBuffers(jquery__WEBPACK_IMPORTED_MODULE_0___default()(this));
  });
  $modal.on('change input', '#mph_buffer_minutos_despues', function () {
    sincronizarBuffers(jquery__WEBPACK_IMPORTED_MODULE_0___default()(this));
  });

  // Validaciones en tiempo real (delegadas al modal)
  $modal.on('change', '#mph_hora_fin_general', function () {
    // Usar $horaInicioGeneral global o buscarla: const $inicio = $modal.find('#mph_hora_inicio_general');
    if ($horaInicioGeneral.val() && jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val() <= $horaInicioGeneral.val()) {
      $modal.find('.mph-error-hora').show();
    } else {
      $modal.find('.mph-error-hora').hide();
    }
  });
  $modal.on('change', '#mph_hora_fin_asignada', function () {
    var $errorDiv = $modal.find('.mph-error-hora-asignada');
    // Usar globales o buscar dentro del modal
    var inicioAsig = $horaInicioAsignada.val();
    var finAsig = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
    var inicioGen = $horaInicioGeneral.val();
    var finGen = $horaFinGeneral.val();
    if (inicioAsig && finAsig <= inicioAsig) {
      var _window$mph_admin_obj;
      $errorDiv.text(((_window$mph_admin_obj = window.mph_admin_obj) === null || _window$mph_admin_obj === void 0 || (_window$mph_admin_obj = _window$mph_admin_obj.i18n) === null || _window$mph_admin_obj === void 0 ? void 0 : _window$mph_admin_obj.error_hora_asignada_invalida) || 'Hora fin asignada debe ser posterior a inicio.').show();
    } else if (inicioGen && finGen && (inicioAsig < inicioGen || finAsig > finGen)) {
      var _window$mph_admin_obj2;
      $errorDiv.text(((_window$mph_admin_obj2 = window.mph_admin_obj) === null || _window$mph_admin_obj2 === void 0 || (_window$mph_admin_obj2 = _window$mph_admin_obj2.i18n) === null || _window$mph_admin_obj2 === void 0 ? void 0 : _window$mph_admin_obj2.error_hora_asignada_rango) || 'Horas asignadas fuera del rango general.').show();
    } else {
      $errorDiv.hide();
    }
  });
  console.log('Interacciones del formulario inicializadas.');
}

/***/ }),

/***/ "./assets/src/js/admin/modal-init.js":
/*!*******************************************!*\
  !*** ./assets/src/js/admin/modal-init.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   closeModal: () => (/* binding */ closeModal),
/* harmony export */   initDialog: () => (/* binding */ initDialog),
/* harmony export */   openModal: () => (/* binding */ openModal),
/* harmony export */   resetModalForm: () => (/* binding */ resetModalForm)
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
// assets/src/js/admin/modal-init.js


// Variables para selectores comunes dentro del modal (podrían moverse aquí o dejarse globales en main si se prefiere)
var $feedbackModal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario .mph-modal-feedback');
var $errorModal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario .mph-modal-error');
var $spinnerModal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario .mph-modal-acciones .spinner');
// ... otros selectores de campos si se usan en resetModalForm

var resetCallback = null; // Para guardar la función de reseteo pasada

/**
 * Inicializa el jQuery UI Dialog.
 * @param {string} modalSelector - Selector CSS para el div del modal.
 * @param {function} closeCallback - Función a llamar al cerrar.
 */
function initDialog(modalSelector, closeCallback) {
  var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()(modalSelector);
  if (!$modal.length) {
    console.error("Error Cr\xEDtico: Elemento modal \"".concat(modalSelector, "\" no encontrado."));
    return;
  }
  resetCallback = closeCallback; // Guardar callback

  $modal.dialog({
    autoOpen: false,
    modal: true,
    width: 600,
    height: 'auto',
    draggable: true,
    resizable: false,
    closeText: "Cerrar",
    classes: {
      "ui-dialog": "mph-jquery-ui-modal"
    },
    open: function open() {
      console.log("Evento 'open' del diálogo disparado.");
      $feedbackModal.hide().text('');
      $errorModal.hide().text('');
      $spinnerModal.removeClass('is-active');
    },
    close: function close() {
      console.log("Evento 'close' del diálogo disparado.");
      if (typeof resetCallback === 'function') {
        resetCallback(jquery__WEBPACK_IMPORTED_MODULE_0___default()(this)); // Llamar al callback de reseteo pasando el elemento
      }
    }
  });
  console.log("Di\xE1logo \"".concat(modalSelector, "\" inicializado."));
}

/**
 * Abre el diálogo especificado.
 * @param {string} modalSelector - Selector CSS para el div del modal.
 */
function openModal(modalSelector) {
  var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()(modalSelector);
  if ($modal.length && $modal.hasClass('ui-dialog-content')) {
    // Verificar si ya está inicializado
    console.log("Abriendo modal \"".concat(modalSelector, "\"..."));
    try {
      $modal.dialog('open');
      console.log("Modal abierto.");
    } catch (e) {
      console.error("Error al abrir diálogo:", e);
    }
  } else {
    console.error("Modal \"".concat(modalSelector, "\" no encontrado o no inicializado."));
  }
}

/**
* Cierra el diálogo especificado.
* @param {string} modalSelector - Selector CSS para el div del modal.
*/
function closeModal(modalSelector) {
  var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()(modalSelector);
  if ($modal.length && $modal.hasClass('ui-dialog-content')) {
    console.log("Cerrando modal \"".concat(modalSelector, "\"..."));
    $modal.dialog('close');
  }
}

/**
 * Resetea el formulario dentro del modal.
 * @param {jQuery} $dialogElement - El objeto jQuery del elemento del diálogo.
 */
function resetModalForm($dialogElement) {
  console.log('Intentando resetear formulario...');
  if (!$dialogElement || !$dialogElement.length) {
    console.error("resetModalForm: No se recibió $dialogElement.");
    return;
  }
  var $form = $dialogElement.find('form#mph-form-horario');
  if (!$form.length) {
    console.error('Error en resetModalForm: No se encontró form#mph-form-horario dentro del contexto.');
    // Loggear contenido si falla
    console.log("Contenido HTML dentro del contexto:", $dialogElement.html());
    console.log("Hijos dentro del contexto:", $dialogElement.children());
    return;
  }
  console.log('Formulario encontrado en resetModalForm. Reseteando...');
  try {
    $form[0].reset(); // Reset nativo

    // Reset específico para nuestros campos (usando selectores buscados DENTRO del form o globales)
    $form.find('#mph_horario_id_editando').val(''); // Limpiar ID

    // Reset checkboxes comunes (requiere mph_admin_obj disponible globalmente o pasado)
    if (window.mph_admin_obj) {
      $form.find('#mph-programas-admisibles-container input[type="checkbox"]').each(function () {
        var termId = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
        var programaData = mph_admin_obj.todos_programas.find(function (p) {
          return p.term_id == termId;
        });
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).prop('checked', programaData && programaData.es_comun);
      });
      $form.find('#mph-sedes-admisibles-container input[type="checkbox"]').each(function () {
        var termId = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
        var sedeData = mph_admin_obj.todas_sedes.find(function (s) {
          return s.term_id == termId;
        });
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).prop('checked', sedeData && sedeData.es_comun);
      });
      $form.find('#mph-rangos-admisibles-container input[type="checkbox"]').each(function () {
        var termId = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).val();
        var rangoData = mph_admin_obj.todos_rangos.find(function (r) {
          return r.term_id == termId;
        });
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).prop('checked', rangoData && rangoData.es_comun);
      });
    } else {
      console.warn("mph_admin_obj no disponible para resetear checkboxes comunes.");
    }

    // Limpiar selects y ocultar sección
    $form.find('#mph_programa_asignado, #mph_sede_asignada, #mph_rango_edad_asignado').empty().append(jquery__WEBPACK_IMPORTED_MODULE_0___default()('<option>', {
      value: '',
      text: '-- Seleccionar --'
    })); // Texto genérico
    $form.find('.mph-asignacion-especifica').hide();

    // Resetear errores y valores por defecto
    $dialogElement.find('.mph-error-hora, .mph-error-duplicado, .mph-error-hora-asignada').hide(); // Buscar errores dentro del diálogo
    $form.find('#mph_vacantes').val(1);
    $form.find('#mph_buffer_minutos_antes').val(60);
    $form.find('#mph_buffer_minutos_despues').val(60);
    $form.find('#mph_buffer_linkeado').prop('checked', true);
  } catch (e) {
    console.error("Error durante resetModalForm:", e);
  }
}

/***/ }),

/***/ "./assets/src/js/admin/modal-validation.js":
/*!*************************************************!*\
  !*** ./assets/src/js/admin/modal-validation.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   validarFormulario: () => (/* binding */ validarFormulario)
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
// assets/src/js/admin/modal-validation.js


// Selectores globales (o podrían pasarse a la función)
var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario');
var $errorModal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario .mph-modal-error');
var $diaSemana = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_dia_semana');
var $horaInicioGeneral = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_inicio_general');
var $horaFinGeneral = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_fin_general');
var $programasAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-programas-admisibles-container');
var $sedesAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-sedes-admisibles-container');
var $rangosAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-rangos-admisibles-container');
var $seccionAsignacion = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario .mph-asignacion-especifica'); // Buscar dentro de modal por seguridad
var $horaInicioAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_inicio_asignada');
var $horaFinAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_fin_asignada');
var $selectProgramaAsignado = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_programa_asignado');
var $selectSedeAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_sede_asignada');
var $selectRangoAsignado = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_rango_edad_asignado');
var $vacantes = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_vacantes');
var $bufferAntes = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_buffer_minutos_antes');
var $bufferDespues = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_buffer_minutos_despues');

/**
 * Valida los campos del formulario del modal antes de enviar.
 * @param {jQuery} $form - El objeto jQuery del formulario a validar.
 * @returns {boolean} True si es válido, False si no.
 */
function validarFormulario($form) {
  var _window$mph_admin_obj;
  console.log('--- Iniciando validarFormulario ---');
  if (!$form || !$form.length) {
    console.error('Error en validarFormulario: No se recibió un objeto de formulario válido.');
    return false;
  }
  var isValid = true;
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
  if (!$diaSemana.val()) {
    $errorModal.append('<p>Seleccione un día.</p>');
    isValid = false;
  }
  var horaInicioGen = $horaInicioGeneral.val();
  var horaFinGen = $horaFinGeneral.val();
  if (!horaInicioGen || !horaFinGen) {
    $errorModal.append('<p>Ingrese horas generales.</p>');
    isValid = false;
  } else if (horaFinGen <= horaInicioGen) {
    $modal.find('.mph-error-hora').show();
    isValid = false;
  } // Mostrar error junto al campo
  else {
    $modal.find('.mph-error-hora').hide();
  }
  var i18n = ((_window$mph_admin_obj = window.mph_admin_obj) === null || _window$mph_admin_obj === void 0 ? void 0 : _window$mph_admin_obj.i18n) || {}; // Acceso seguro a i18n

  if ($programasAdmisiblesContainer.find('input:checked').length === 0) {
    $errorModal.append('<p>' + (i18n.error_seleccionar_programa || 'Debe seleccionar al menos un programa admisible.') + '</p>');
    isValid = false;
  }
  if ($sedesAdmisiblesContainer.find('input:checked').length === 0) {
    $errorModal.append('<p>' + (i18n.error_seleccionar_sede || 'Debe seleccionar al menos una sede admisible.') + '</p>');
    isValid = false;
  }
  if ($rangosAdmisiblesContainer.find('input:checked').length === 0) {
    $errorModal.append('<p>' + (i18n.error_seleccionar_rango || 'Debe seleccionar al menos un rango de edad admisible.') + '</p>');
    isValid = false;
  }

  // --- Validación Sección Asignación (si visible) ---
  if ($seccionAsignacion.is(':visible')) {
    console.log("Validando sección de asignación...");
    var horaInicioAsig = $horaInicioAsignada.val();
    var horaFinAsig = $horaFinAsignada.val();
    var $errorHoraAsignada = $modal.find('.mph-error-hora-asignada'); // Selector error específico

    if (!horaInicioAsig || !horaFinAsig) {
      $errorModal.append('<p>' + (i18n.error_faltan_horas_asignadas || 'Faltan horas de asignación.') + '</p>');
      isValid = false;
    } else {
      if (horaFinAsig <= horaInicioAsig) {
        $errorHoraAsignada.text(i18n.error_hora_asignada_invalida || 'Hora fin asignada debe ser posterior a inicio.').show();
        isValid = false;
      } else if (horaInicioGen && horaFinGen && (horaInicioAsig < horaInicioGen || horaFinAsig > horaFinGen)) {
        $errorHoraAsignada.text(i18n.error_hora_asignada_rango || 'Horas asignadas fuera del rango general.').show();
        isValid = false;
      } else {
        $errorHoraAsignada.hide();
      }
    }
    if (!$selectProgramaAsignado.val()) {
      $errorModal.append('<p>' + (i18n.error_seleccionar_programa_asig || 'Debe seleccionar un programa para la asignación.') + '</p>');
      isValid = false;
    }
    if (!$selectSedeAsignada.val()) {
      $errorModal.append('<p>' + (i18n.error_seleccionar_sede_asig || 'Debe seleccionar una sede para la asignación.') + '</p>');
      isValid = false;
    }
    if (!$selectRangoAsignado.val()) {
      $errorModal.append('<p>' + (i18n.error_seleccionar_rango_asig || 'Debe seleccionar un rango de edad para la asignación.') + '</p>');
      isValid = false;
    }
    if (parseInt($vacantes.val(), 10) < 0) {
      $errorModal.append('<p>' + (i18n.error_vacantes_negativas || 'Las vacantes no pueden ser negativas.') + '</p>');
      isValid = false;
    }
    var bufferAntesVal = parseInt($bufferAntes.val(), 10);
    var bufferDespuesVal = parseInt($bufferDespues.val(), 10);
    if (isNaN(bufferAntesVal) || bufferAntesVal < 0) {
      $errorModal.append('<p>' + (i18n.error_buffer_antes_invalido || 'El tiempo de buffer Antes debe ser un número positivo.') + '</p>');
      isValid = false;
    }
    if (isNaN(bufferDespuesVal) || bufferDespuesVal < 0) {
      $errorModal.append('<p>' + (i18n.error_buffer_despues_invalido || 'El tiempo de buffer Después debe ser un número positivo.') + '</p>');
      isValid = false;
    }
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

/***/ }),

/***/ "./assets/src/js/admin/table-actions.js":
/*!**********************************************!*\
  !*** ./assets/src/js/admin/table-actions.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initTableActions: () => (/* binding */ initTableActions)
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _modal_init__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modal-init */ "./assets/src/js/admin/modal-init.js");
/* harmony import */ var _modal_form_interaction__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modal-form-interaction */ "./assets/src/js/admin/modal-form-interaction.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
// assets/src/js/admin/table-actions.js



// Podríamos necesitar una función específica para pre-llenar el formulario
// import { prefillModalForAssignment } from './modal-helpers'; // (La crearemos si es necesario)

// import { openModal, resetModalForm, /* otras funciones como prellenarFormulario */ } from './modal-init';

/**
 * Inicializa los manejadores de eventos para los botones de acción en la tabla de horarios.
 * @param {string} tableContainerSelector - Selector CSS para el div que contiene la tabla.
 */
function initTableActions(tableContainerSelector) {
  console.log("Inicializando acciones de tabla para \"".concat(tableContainerSelector, "\"..."));
  var $tableContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()(tableContainerSelector);
  if (!$tableContainer.length) {
    console.error("initTableActions: Contenedor de tabla no encontrado.");
    return;
  }

  // --- Acción Eliminar ---
  $tableContainer.on('click', '.mph-accion-eliminar', function (e) {
    e.preventDefault();
    var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    var horarioId = $button.data('horario-id');
    var nonce = $button.data('nonce'); // Nonce específico para eliminar este ID
    var $fila = $button.closest('tr');

    // Acceso seguro al objeto global mph_admin_obj
    if (typeof window.mph_admin_obj === 'undefined' || !window.mph_admin_obj.ajax_url || !window.mph_admin_obj.i18n) {
      console.error("Error crítico: mph_admin_obj o sus propiedades no están disponibles para Eliminar.");
      alert("Error interno.");
      return;
    }
    var ajax_url = mph_admin_obj.ajax_url;
    var i18n = mph_admin_obj.i18n;
    if (!horarioId || !nonce) {
      console.error('Error: Faltan horario_id o nonce para eliminar.');
      alert(i18n.error_general || 'Error inesperado.');
      return;
    }
    var confirmarMsg = i18n.confirmar_eliminacion || '¿Estás seguro de que deseas eliminar este horario?';
    if (confirm(confirmarMsg)) {
      console.log("Intentando eliminar horario ID: ".concat(horarioId));
      $button.prop('disabled', true).css('opacity', 0.5);
      var dataToSend = {
        action: 'mph_eliminar_horario',
        horario_id: horarioId,
        nonce: nonce
      };
      jquery__WEBPACK_IMPORTED_MODULE_0___default().post(ajax_url, dataToSend).done(function (response) {
        if (response.success) {
          console.log("Horario ID: ".concat(horarioId, " eliminado."));
          $fila.fadeOut(400, function () {
            jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).remove();
          });
          // alert(i18n.horario_eliminado || 'Horario eliminado.'); // Opcional
        } else {
          var _response$data, _response$data2;
          console.error('Error servidor al eliminar:', (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.message);
          alert(i18n.error_general + ((_response$data2 = response.data) !== null && _response$data2 !== void 0 && _response$data2.message ? ' (' + response.data.message + ')' : ''));
          $button.prop('disabled', false).css('opacity', 1);
        }
      }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Error AJAX al eliminar:", textStatus, errorThrown);
        alert(i18n.error_general || 'Error de comunicación.');
        $button.prop('disabled', false).css('opacity', 1);
      });
    } else {
      console.log('Eliminación cancelada.');
    }
  }); // Fin click .mph-accion-eliminar

  // --- Acción Asignar (Placeholder) ---
  $tableContainer.on('click', '.mph-accion-asignar', function (e) {
    e.preventDefault();
    var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    var horarioInfoString = $button.data('horario-info');
    console.log('Botón Asignar clickeado.');
    if (!horarioInfoString) {
      console.error("Error: No se encontró data-horario-info en el botón Asignar.");
      alert("Error al obtener información del horario.");
      return;
    }
    try {
      // 1. Leer el atributo data-* usando jQuery. jQuery intenta parsear automáticamente.
      var horarioInfo = $button.data('horario-info');
      console.log('Info Horario Objeto (de .data()):', horarioInfo);

      // 2. Verificar si obtuvimos un objeto válido
      if (!horarioInfo || _typeof(horarioInfo) !== 'object') {
        throw new Error("No se pudo obtener un objeto válido de data-horario-info.");
      }

      // 3. Obtener referencia al modal (necesario para funciones de modal)
      var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario');
      if (!$modal.length) {
        console.error("Error: Modal #mph-modal-horario no encontrado.");
        return;
      }

      // 4. Resetear el formulario completamente
      console.log("Reseteando modal antes de asignar...");
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.resetModalForm)($modal); // Llamar a la función importada

      // 5. Pre-llenar campos de Disponibilidad General
      console.log("Pre-llenando campos generales...");
      $modal.find('#mph_dia_semana').val(horarioInfo.dia || '');
      $modal.find('#mph_hora_inicio_general').val(horarioInfo.inicio || '');
      $modal.find('#mph_hora_fin_general').val(horarioInfo.fin || '');

      // 6. Pre-seleccionar checkboxes admisibles
      // (resetModalForm ya debería marcar los comunes, aquí marcamos los específicos de este bloque)
      if (horarioInfo.programas_admisibles && Array.isArray(horarioInfo.programas_admisibles)) {
        horarioInfo.programas_admisibles.forEach(function (id) {
          if (id) $modal.find('#programa_' + id).prop('checked', true);
        });
      }
      if (horarioInfo.sedes_admisibles && Array.isArray(horarioInfo.sedes_admisibles)) {
        horarioInfo.sedes_admisibles.forEach(function (id) {
          if (id) $modal.find('#sede_' + id).prop('checked', true);
        });
      }
      if (horarioInfo.rangos_admisibles && Array.isArray(horarioInfo.rangos_admisibles)) {
        // Ojo con el slug aquí si era diferente (rango_edad vs rango_de_edad)
        horarioInfo.rangos_admisibles.forEach(function (id) {
          if (id) $modal.find('#rango_edad_' + id).prop('checked', true); // Usar slug correcto
        });
      }
      console.log("Checkboxes admisibles pre-seleccionados.");

      // 7. Mostrar sección de asignación específica y poblar sus selects
      console.log("Mostrando sección de asignación...");
      var $seccionAsignacion = $modal.find('.mph-asignacion-especifica');
      if ($seccionAsignacion.length) {
        // Llamar a poblarSelects (necesitaríamos importarla o rehacer la lógica aquí)
        // Por ahora, asumimos que existe una función global o la importamos si es necesario.
        // Necesita ejecutarse DESPUÉS de marcar los checkboxes.
        if (typeof _modal_form_interaction__WEBPACK_IMPORTED_MODULE_2__.poblarSelectsAsignacion === "function") {
          // ¿Está disponible globalmente? (No ideal)
          (0,_modal_form_interaction__WEBPACK_IMPORTED_MODULE_2__.poblarSelectsAsignacion)($modal); // Necesita $modal si busca elementos internos
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
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.openModal)($modal); // Llamar a la función importada
    } catch (e) {
      console.error("Error al parsear horarioInfo o pre-llenar modal para Asignar:", e);
      alert("Error al preparar el formulario de asignación.");
    }
  });

  // --- Acción Editar (Placeholder) ---
  $tableContainer.on('click', '.mph-accion-editar', function (e) {
    e.preventDefault();
    var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    var horarioInfo = $button.data('horario-info'); // Obtener el JSON de datos
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
  console.log("Acciones de tabla para \"".concat(tableContainerSelector, "\" inicializadas."));
}

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/***/ ((module) => {

module.exports = jQuery;

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************************!*\
  !*** ./assets/src/js/admin/main.js ***!
  \*************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _modal_init__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modal-init */ "./assets/src/js/admin/modal-init.js");
/* harmony import */ var _modal_form_interaction__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modal-form-interaction */ "./assets/src/js/admin/modal-form-interaction.js");
/* harmony import */ var _modal_ajax__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./modal-ajax */ "./assets/src/js/admin/modal-ajax.js");
/* harmony import */ var _table_actions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./table-actions */ "./assets/src/js/admin/table-actions.js");
// assets/src/js/admin/main.js

 // Sin initModalButtons



jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).on('load', function () {
  console.log('Admin JS Bundle Loaded (window.load)');
  var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario');
  if (!$modal.length) {
    console.error("CRÍTICO: Elemento modal #mph-modal-horario no encontrado en window.load.");
    return;
  }
  function checkAdminObject() {
    if (typeof window.mph_admin_obj !== 'undefined' && window.mph_admin_obj.todos_programas) {
      console.log('mph_admin_obj está listo.');
      initDependentModules();
    } else {
      console.log('mph_admin_obj aún no está listo, reintentando...');
      setTimeout(checkAdminObject, 100);
    }
  }
  function initDependentModules() {
    console.log("Inicializando módulos dependientes de mph_admin_obj...");
    (0,_modal_form_interaction__WEBPACK_IMPORTED_MODULE_2__.initFormInteractions)($modal);
    (0,_modal_ajax__WEBPACK_IMPORTED_MODULE_3__.initAjaxSubmit)($modal); // Pasar $modal
    (0,_table_actions__WEBPACK_IMPORTED_MODULE_4__.initTableActions)('#mph-tabla-horarios-container');
    // initModalButtons($modal); // ELIMINADA ESTA LLAMADA
    console.log("Módulos dependientes inicializados.");
  }
  console.log("Inicializando diálogo...");
  (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.initDialog)($modal, _modal_init__WEBPACK_IMPORTED_MODULE_1__.resetModalForm); // Pasar $modal

  // Manejador Botón Cancelar (puesto aquí por simplicidad de acceso a $modal y closeModal)
  if ($modal.length) {
    $modal.on('click', '#mph-cancelar-modal', function () {
      console.log("Botón Cancelar Clickeado (desde main.js)");
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.closeModal)($modal); // Llamar a closeModal pasando $modal
    });
    console.log("Manejador para #mph-cancelar-modal inicializado (en main.js).");
  }

  // Manejador Botón Abrir
  var $metaBoxWrapper = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.mph-gestion-horarios-wrapper');
  if ($metaBoxWrapper.length) {
    $metaBoxWrapper.on('click', '#mph-abrir-modal-horario', function () {
      console.log('Abrir Modal Clicked');
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.resetModalForm)($modal); // Pasar $modal
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.openModal)($modal); // Pasar $modal
    });
  } else {
    console.warn("Wrapper del Metabox no encontrado.");
  }

  // Iniciar la verificación para el objeto localizado
  checkAdminObject();
  console.log('MPH Admin Initializations Started...');
}); // Fin window.load
})();

/******/ })()
;
//# sourceMappingURL=admin.bundle.js.map