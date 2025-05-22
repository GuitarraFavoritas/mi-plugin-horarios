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
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
// assets/src/js/admin/modal-ajax.js




/**
 * Inicializa el manejador de envío AJAX para el formulario del modal.
 * @param {jQuery} $modal - El objeto jQuery del elemento modal.
 */
function initAjaxSubmit($modal) {
  console.log("Inicializando manejador CLICK para #mph-guardar-horario...");
  if (!$modal || !$modal.length || typeof $modal.find !== 'function') {
    console.error("initAjaxSubmit: No se recibió el objeto $modal válido.");
    return;
  }
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
    var actionMode = $button.attr('data-action-mode') || 'save_full';
    console.log("Modo de Acción (leído del botón):", actionMode);

    // --- Validación ---
    var isValid = false;
    /* Inicia Modificación: Asegurar que la validación correcta se llama */
    if (actionMode === 'update_vacantes') {
      console.log("Validando solo vacantes...");
      var $vacantesInput = $form.find('#mph_vacantes'); // Buscar input
      var vacantesVal = parseInt($vacantesInput.val(), 10);
      // Validar si es número y no negativo
      if ($vacantesInput.length && !isNaN(vacantesVal) && vacantesVal >= 0) {
        isValid = true;
        if ($errorModal.length) $errorModal.hide().text(''); // Limpiar error si es válido
      } else {
        var _window$mph_admin_obj;
        isValid = false;
        if ($errorModal.length) $errorModal.text(((_window$mph_admin_obj = window.mph_admin_obj) === null || _window$mph_admin_obj === void 0 || (_window$mph_admin_obj = _window$mph_admin_obj.i18n) === null || _window$mph_admin_obj === void 0 ? void 0 : _window$mph_admin_obj.error_vacantes_negativas) || 'Las vacantes deben ser un número positivo.').show();
      }
      console.log("Resultado Validación Vacantes:", isValid);
    } else {
      // save_full
      console.log('Llamando a validarFormulario (modo save_full)...');
      isValid = (0,_modal_validation__WEBPACK_IMPORTED_MODULE_1__.validarFormulario)($form); // Llama a la función completa
      if (!isValid) console.log('validarFormulario (save_full) devolvió false.');
    }
    if (!isValid) {
      return;
    } // Detener si no es válido

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

    // --- Preparar Datos AJAX según el modo ---
    var dataToSend = '';
    var ajaxAction = ''; // Variable para la acción

    var nonceValue = '';
    var nonceFieldName = '';
    if (actionMode === 'update_vacantes') {
      ajaxAction = 'mph_actualizar_vacantes';
      nonceFieldName = 'mph_nonce_actualizar_vacantes'; // Nombre del nuevo nonce field
      var $nonceField = $form.find('input[name="' + nonceFieldName + '"]');
      if ($nonceField.length) {
        nonceValue = $nonceField.val();
      } else {
        var _window$mph_admin_obj2;
        console.error("Campo Nonce para Actualizar Vacantes no encontrado en el formulario!");
        if ($errorModal.length) $errorModal.text((_window$mph_admin_obj2 = window.mph_admin_obj) === null || _window$mph_admin_obj2 === void 0 || (_window$mph_admin_obj2 = _window$mph_admin_obj2.i18n) === null || _window$mph_admin_obj2 === void 0 ? void 0 : _window$mph_admin_obj2.error_seguridad_interna /*|| "Error de seguridad interno (Nonce UV)."*/).show();
        $button.prop('disabled', false);
        if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
        return;
      }
      var horarioId = $form.find('#mph_horario_id_editando').val();
      var _vacantesVal = $form.find('#mph_vacantes').val();
      dataToSend = _defineProperty({
        // Construir objeto
        action: ajaxAction,
        horario_id: horarioId,
        vacantes: _vacantesVal
      }, nonceFieldName, nonceValue);
      console.log('Datos a enviar (update_vacantes):', dataToSend);
    } else {
      // save_full
      console.log("Preparando datos para guardar completo/asignar a existente...");
      ajaxAction = 'mph_guardar_horario_maestro';
      nonceFieldName = 'mph_nonce_guardar'; // Nombre del nonce field original
      var _$nonceField = $form.find('input[name="' + nonceFieldName + '"]');
      if (_$nonceField.length) {
        nonceValue = _$nonceField.val();
      } else {
        var _window$mph_admin_obj3;
        console.error("Campo Nonce para Guardar no encontrado en el formulario!");
        if ($errorModal.length) $errorModal.text((_window$mph_admin_obj3 = window.mph_admin_obj) === null || _window$mph_admin_obj3 === void 0 || (_window$mph_admin_obj3 = _window$mph_admin_obj3.i18n) === null || _window$mph_admin_obj3 === void 0 ? void 0 : _window$mph_admin_obj3.error_seguridad_interna /*|| "Error de seguridad interno (Nonce GS)."*/).show();
        $button.prop('disabled', false);
        if ($spinnerModal.length) $spinnerModal.removeClass('is-active');
        return;
      }
      var _formData = $form.serialize(); // Obtener todos los datos
      // Crear dataToSend como objeto también para consistencia (opcional)
      // O seguir con string: dataToSend = formData + '&action=' + ajaxAction;
      // Si usamos objeto, necesitamos parsear formData o añadir campos manualmente
      // Mantengamos string por ahora para save_full:
      dataToSend = _formData + '&action=' + ajaxAction; // formData ya incluye nonce guardar
      console.log('Datos COMPLETOS a enviar (save_full):', dataToSend);
    }

    // --- Enviar Petición AJAX ---
    if (!window.mph_admin_obj || !window.mph_admin_obj.ajax_url) {
      /*...*/return;
    }
    var ajax_url = window.mph_admin_obj.ajax_url;
    var i18n = window.mph_admin_obj.i18n || {};

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

  console.log("Manejador CLICK para #mph-guardar-horario inicializado.");
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
    $form.find('#mph_horario_id_editando').val('');

    // --- Restaurar Visibilidad ---

    $dialogElement.removeClass('mph-modal-mode-edit-vacantes mph-modal-mode-edit-disp mph-modal-mode-assign'); // Quitar clases de modo

    $dialogElement.find('#mph-editar-info').hide().empty();
    $form.find('.mph-modal-seccion.mph-disponibilidad-general').show(); // Mostrar sección general
    $form.find('.mph-modal-seccion.mph-asignacion-especifica').hide(); // Ocultar sección asignación
    $form.find('#mph-mostrar-asignacion').show(); // Mostrar botón toggle
    /* Finaliza Modificación */

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

    // Resetear errores y valores por defecto
    $dialogElement.find('.mph-error-hora, .mph-error-duplicado, .mph-error-hora-asignada').hide();
    $dialogElement.find('.mph-modal-feedback, .mph-modal-error').hide().text('');
    $form.find('#mph_vacantes').val(1); // Restaurar valor vacantes
    $form.find('#mph_buffer_minutos_antes').val(60);
    $form.find('#mph_buffer_minutos_despues').val(60);
    $form.find('#mph_buffer_linkeado').prop('checked', true);

    // Restaurar Botón Guardar
    var $btnGuardar = $form.find('#mph-guardar-horario');
    if ($btnGuardar.length) {
      var _window$mph_admin_obj;
      var textoOriginal = ((_window$mph_admin_obj = window.mph_admin_obj) === null || _window$mph_admin_obj === void 0 || (_window$mph_admin_obj = _window$mph_admin_obj.i18n) === null || _window$mph_admin_obj === void 0 ? void 0 : _window$mph_admin_obj.guardar_horario) || 'Guardar Disponibilidad/Asignación';
      $btnGuardar.text(textoOriginal);
      $btnGuardar.removeAttr('data-action-mode'); // Limpiar modo
    }
    console.log("Visibilidad y botón guardar restaurados a default.");
  } catch (e) {
    console.error("Error durante resetModalForm:", e);
  }
} // Fin resetModalForm

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


// Selectores globales (asumiendo que se definen en main.js y están disponibles en window o pasados)
// O búscalos dentro de $form o $modal si es más seguro
var $diaSemana = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_dia_semana');
var $horaInicioGeneral = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_inicio_general');
var $horaFinGeneral = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_fin_general');
var $programasAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-programas-admisibles-container');
var $sedesAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-sedes-admisibles-container');
var $rangosAdmisiblesContainer = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-rangos-admisibles-container');
// Selectores para la sección de asignación (pueden ser globales o buscados dentro de $form)
var $seccionAsignacion = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario .mph-asignacion-especifica'); // Más específico
var $horaInicioAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_inicio_asignada');
var $horaFinAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_hora_fin_asignada');
var $selectProgramaAsignado = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_programa_asignado');
var $selectSedeAsignada = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_sede_asignada');
var $selectRangoAsignado = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_rango_edad_asignado');
var $vacantes = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_vacantes');
var $bufferAntes = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_buffer_minutos_antes');
var $bufferDespues = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph_buffer_minutos_despues');
function validarFormulario($form) {
  // Acepta $form como argumento
  console.log('--- Iniciando validarFormulario ---');
  if (!$form || !$form.length) {
    console.error('Error en validarFormulario: No se recibió un objeto de formulario válido.');
    return false;
  }
  var isValid = true;
  var $modal = $form.closest('#mph-modal-horario'); // Obtener el modal desde el form
  var $errorModal = $modal.find('.mph-modal-error');
  if ($errorModal.length) $errorModal.hide().empty();

  // Obtener el modo del botón guardar que está DENTRO del form
  var $btnGuardar = $form.find('#mph-guardar-horario');
  var currentMode = $btnGuardar.attr('data-action-mode') || 'save_full'; // 'save_full' es el default si no hay attr
  console.log("validarFormulario - Modo de acción detectado:", currentMode);

  // --- Validación Sección General (SOLO para modo save_full) ---
  if (currentMode === 'save_full') {
    var _window$mph_admin_obj;
    console.log("Validando sección general para modo save_full...");
    if (!$diaSemana.val()) {
      $errorModal.append('<p>Seleccione un día.</p>');
      isValid = false;
    }
    var horaInicioGenVal = $horaInicioGeneral.val(); // Usar selectores globales
    var horaFinGenVal = $horaFinGeneral.val();
    if (!horaInicioGenVal || !horaFinGenVal) {
      $errorModal.append('<p>Ingrese horas generales.</p>');
      isValid = false;
    } else if (horaFinGenVal <= horaInicioGenVal) {
      $modal.find('.mph-error-hora').show();
      isValid = false;
    } else {
      $modal.find('.mph-error-hora').hide();
    }
    var i18n_val = ((_window$mph_admin_obj = window.mph_admin_obj) === null || _window$mph_admin_obj === void 0 ? void 0 : _window$mph_admin_obj.i18n) || {};
    if ($programasAdmisiblesContainer.find('input:checked').length === 0) {
      $errorModal.append('<p>' + (i18n_val.error_seleccionar_programa || 'Prog.') + '</p>');
      isValid = false;
    }
    if ($sedesAdmisiblesContainer.find('input:checked').length === 0) {
      $errorModal.append('<p>' + (i18n_val.error_seleccionar_sede || 'Sede.') + '</p>');
      isValid = false;
    }
    if ($rangosAdmisiblesContainer.find('input:checked').length === 0) {
      $errorModal.append('<p>' + (i18n_val.error_seleccionar_rango || 'Rango.') + '</p>');
      isValid = false;
    }
  }

  // --- Validación Sección Asignación (SOLO si está visible Y no es 'edit_vacantes') ---
  // O si el modo es 'assign_to_existing' (porque la sección se muestra directamente)
  var seccionAsignacionVisible = $seccionAsignacion.is(':visible'); // Usar selector global
  if (seccionAsignacionVisible && currentMode !== 'update_vacantes' || currentMode === 'assign_to_existing') {
    var _window$mph_admin_obj2;
    console.log("Validando sección de asignación...");
    var horaInicioAsigVal = $horaInicioAsignada.val();
    var horaFinAsigVal = $horaFinAsignada.val();
    var horaInicioGenValParaComparar = currentMode === 'assign_to_existing' ? $form.find('#mph_hora_inicio_general').val() : $horaInicioGeneral.val();
    var horaFinGenValParaComparar = currentMode === 'assign_to_existing' ? $form.find('#mph_hora_fin_general').val() : $horaFinGeneral.val();
    var $errorHoraAsignada = $modal.find('.mph-error-hora-asignada');
    var i18n_asig = ((_window$mph_admin_obj2 = window.mph_admin_obj) === null || _window$mph_admin_obj2 === void 0 ? void 0 : _window$mph_admin_obj2.i18n) || {};
    if (!horaInicioAsigVal || !horaFinAsigVal) {
      $errorModal.append('<p>' + (i18n_asig.error_faltan_horas_asignadas || 'Faltan horas.') + '</p>');
      isValid = false;
    } else {
      if (horaFinAsigVal <= horaInicioAsigVal) {
        $errorHoraAsignada.text(i18n_asig.error_hora_asignada_invalida || 'Fin < Inicio Asig.').show();
        isValid = false;
      }
      // Validar contra las horas GENERALES del contexto actual
      else if (horaInicioGenValParaComparar && horaFinGenValParaComparar && (horaInicioAsigVal < horaInicioGenValParaComparar || horaFinAsigVal > horaFinGenValParaComparar)) {
        $errorHoraAsignada.text(i18n_asig.error_hora_asignada_rango || 'Asig. fuera rango Gral.').show();
        isValid = false;
      } else {
        $errorHoraAsignada.hide();
      }
    }
    if (!$selectProgramaAsignado.val()) {
      $errorModal.append('<p>' + (i18n_asig.error_seleccionar_programa_asig || 'Prog Asig.') + '</p>');
      isValid = false;
    }
    if (!$selectSedeAsignada.val()) {
      $errorModal.append('<p>' + (i18n_asig.error_seleccionar_sede_asig || 'Sede Asig.') + '</p>');
      isValid = false;
    }
    if (!$selectRangoAsignado.val()) {
      $errorModal.append('<p>' + (i18n_asig.error_seleccionar_rango_asig || 'Rango Asig.') + '</p>');
      isValid = false;
    }

    // La validación de vacantes y buffers se hace en el modo 'update_vacantes' y también aquí para 'save_full' y 'assign_to_existing'
    if (parseInt($vacantes.val(), 10) < 0) {
      $errorModal.append('<p>' + (i18n_asig.error_vacantes_negativas || 'Vacantes < 0.') + '</p>');
      isValid = false;
    }
    var bufferAntesVal = parseInt($bufferAntes.val(), 10);
    var bufferDespuesVal = parseInt($bufferDespues.val(), 10);
    if (isNaN(bufferAntesVal) || bufferAntesVal < 0) {
      $errorModal.append('<p>' + (i18n_asig.error_buffer_antes_invalido || 'Buffer Antes < 0.') + '</p>');
      isValid = false;
    }
    if (isNaN(bufferDespuesVal) || bufferDespuesVal < 0) {
      $errorModal.append('<p>' + (i18n_asig.error_buffer_despues_invalido || 'Buffer Después < 0.') + '</p>');
      isValid = false;
    }
  }
  if (!isValid && $errorModal.length) $errorModal.show();else if (isValid) console.log("Validación pasada.");
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




/**
 * Prepara el modal para el modo "Editar Vacantes".
 * @param {jQuery} $modal - El objeto jQuery del modal.
 * @param {object} horarioInfo - El objeto con la info del horario.
 */
function prepareModalForEditVacantes($modal, horarioInfo) {
  var $form = $modal.find('form#mph-form-horario');
  if (!$form.length) return;
  console.log("Preparando modal para Editar Vacantes...");

  // 1. Aplicar clase CSS (el CSS controla qué se muestra/oculta)
  $modal.removeClass('mph-modal-mode-assign mph-modal-mode-edit-disp').addClass('mph-modal-mode-edit-vacantes');

  // 2. Mostrar/Crear y llenar div de información #mph-editar-info
  var $infoDiv = $form.find('#mph-editar-info');
  if (!$infoDiv.length) {
    $form.prepend('<div id="mph-editar-info" style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid #eee;"></div>');
    $infoDiv = $form.find('#mph-editar-info'); // Re-buscar después de añadir
  }

  // Construir el HTML de información
  var infoHtml = '<h4>Editando Vacantes para:</h4>'; // Definir ANTES del if(horarioInfo)
  if (horarioInfo) {
    var dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    infoHtml += "<p><strong>D\xEDa:</strong> ".concat(dias[horarioInfo.dia] || 'N/A', "</p>");
    infoHtml += "<p><strong>Horario:</strong> ".concat(horarioInfo.inicio_asig || 'N/A', " - ").concat(horarioInfo.fin_asig || 'N/A', "</p>");
    if (window.mph_admin_obj) {
      var _mph_admin_obj$todos_, _mph_admin_obj$todas_, _mph_admin_obj$todos_2;
      var prog = (_mph_admin_obj$todos_ = mph_admin_obj.todos_programas) === null || _mph_admin_obj$todos_ === void 0 ? void 0 : _mph_admin_obj$todos_.find(function (p) {
        return p.term_id == horarioInfo.prog_asig;
      });
      var sede = (_mph_admin_obj$todas_ = mph_admin_obj.todas_sedes) === null || _mph_admin_obj$todas_ === void 0 ? void 0 : _mph_admin_obj$todas_.find(function (s) {
        return s.term_id == horarioInfo.sede_asig;
      });
      var rango = (_mph_admin_obj$todos_2 = mph_admin_obj.todos_rangos) === null || _mph_admin_obj$todos_2 === void 0 ? void 0 : _mph_admin_obj$todos_2.find(function (r) {
        return r.term_id == horarioInfo.rango_asig;
      });
      infoHtml += "<p><strong>Programa:</strong> ".concat((prog === null || prog === void 0 ? void 0 : prog.name) || 'N/A', "</p>");
      infoHtml += "<p><strong>Sede:</strong> ".concat((sede === null || sede === void 0 ? void 0 : sede.name) || 'N/A', "</p>");
      infoHtml += "<p><strong>Rango Edad:</strong> ".concat((rango === null || rango === void 0 ? void 0 : rango.name) || 'N/A', "</p>");
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
  var $vacantesInput = $form.find('#mph_vacantes');
  if ($vacantesInput.length) {
    $vacantesInput.val((horarioInfo === null || horarioInfo === void 0 ? void 0 : horarioInfo.vacantes) !== undefined ? horarioInfo.vacantes : 0);
    console.log("Campo vacantes prellenado con:", $vacantesInput.val());
  } else {
    console.error("Input de Vacantes no encontrado al preparar modal.");
  }

  // 4. Ajustar botón Guardar
  var $btnGuardar = $form.find('#mph-guardar-horario');
  if ($btnGuardar.length) {
    var _window$mph_admin_obj;
    var textoBoton = ((_window$mph_admin_obj = window.mph_admin_obj) === null || _window$mph_admin_obj === void 0 || (_window$mph_admin_obj = _window$mph_admin_obj.i18n) === null || _window$mph_admin_obj === void 0 ? void 0 : _window$mph_admin_obj.actualizar_vacantes) || 'Actualizar Vacantes';
    $btnGuardar.text(textoBoton);
    $btnGuardar.attr('data-action-mode', 'update_vacantes');
    console.log("Botón Guardar ajustado para 'update_vacantes'.");
  }

  // 5. Establecer ID
  $form.find('#mph_horario_id_editando').val((horarioInfo === null || horarioInfo === void 0 ? void 0 : horarioInfo.horario_id) || '');
  console.log("ID horario establecido:", horarioInfo === null || horarioInfo === void 0 ? void 0 : horarioInfo.horario_id);
}

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

  // --- Acción Asignar (Desde la tabla) ---
  /* Inicia Modificación: Reescribir Acción Asignar */
  $tableContainer.on('click', '.mph-accion-asignar', function (e) {
    e.preventDefault();
    var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    console.log('Botón Asignar (desde tabla) clickeado.');
    try {
      var horarioInfo = $button.data('horario-info');
      console.log('Info Horario para Asignar (desde tabla):', horarioInfo);
      if (!horarioInfo || _typeof(horarioInfo) !== 'object' || !horarioInfo.horario_id) {
        throw new Error("Datos inválidos o falta horario_id en data-horario-info.");
      }
      var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario');
      if (!$modal.length) {
        throw new Error("Modal #mph-modal-horario no encontrado.");
      }
      var $form = $modal.find('form#mph-form-horario');
      if (!$form.length) {
        throw new Error("Formulario #mph-form-horario no encontrado.");
      }

      // 1. Resetear el modal a su estado por defecto
      console.log("Reseteando modal antes de asignar...");
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.resetModalForm)($modal); // Esto quita clases de modo y oculta sección asignación

      // 2. Aplicar clase para modo "Asignar" (CSS oculta sección general y muestra sección asignación)
      $modal.addClass('mph-modal-mode-assign');
      console.log("Clase 'mph-modal-mode-assign' añadida al modal.");

      // 3. Mostrar información del bloque original en #mph-editar-info
      var $infoDiv = $form.find('#mph-editar-info');
      if (!$infoDiv.length) {
        // Crear si no existe
        $form.prepend('<div id="mph-editar-info"></div>');
        $infoDiv = $form.find('#mph-editar-info');
      }
      if ($infoDiv.length) {
        var dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        var infoHtml = "<h4>Asignando a: ".concat(dias[horarioInfo.dia] || '', " ").concat(horarioInfo.inicio || '', " - ").concat(horarioInfo.fin || '', "</h4>");
        // Podríamos añadir los admisibles originales aquí si quisiéramos
        $infoDiv.html(infoHtml).show(); // El CSS .mph-modal-mode-assign #mph-editar-info lo muestra
      }

      // 4. Pre-llenar campos ocultos necesarios para que el backend sepa el contexto
      $form.find('#mph_horario_id_editando').val(horarioInfo.horario_id); // ID del bloque Vacío/Mismo que se reemplaza
      $form.find('#mph_dia_semana').val(horarioInfo.dia); // Este campo está oculto por CSS pero lo llenamos
      $form.find('#mph_hora_inicio_general').val(horarioInfo.inicio); // Idem
      $form.find('#mph_hora_fin_general').val(horarioInfo.fin); // Idem

      // Guardar los admisibles originales del bloque "Vacío" en algún lugar si el backend los necesita
      // y no los puede deducir del horario_id_editando.
      // Por ahora, el backend (mph_guardar_horario_maestro + mph_calcular_bloques)
      // usa hora_inicio_general, hora_fin_general y los checkboxes de la sección 1
      // para determinar los admisibles. Como la sección 1 está oculta, ¡esto es un problema!
      // SOLUCIÓN: Debemos marcar los checkboxes admisibles en la sección 1 (aunque esté oculta).
      console.log("Pre-marcando checkboxes admisibles (ocultos) del bloque original...");
      $form.find('#mph-programas-admisibles-container input').prop('checked', false); // Desmarcar todos primero
      if (horarioInfo.programas_admisibles && Array.isArray(horarioInfo.programas_admisibles)) {
        horarioInfo.programas_admisibles.forEach(function (id) {
          if (id) $form.find('#programa_' + id).prop('checked', true);
        });
      }
      $form.find('#mph-sedes-admisibles-container input').prop('checked', false);
      if (horarioInfo.sedes_admisibles && Array.isArray(horarioInfo.sedes_admisibles)) {
        horarioInfo.sedes_admisibles.forEach(function (id) {
          if (id) $form.find('#sede_' + id).prop('checked', true);
        });
      }
      $form.find('#mph-rangos-admisibles-container input').prop('checked', false);
      if (horarioInfo.rangos_admisibles && Array.isArray(horarioInfo.rangos_admisibles)) {
        horarioInfo.rangos_admisibles.forEach(function (id) {
          if (id) $form.find('#rango_edad_' + id).prop('checked', true);
        });
      }

      // 5. Pre-llenar campos de la Sección de Asignación Específica
      console.log("Pre-llenando campos de asignación específica...");
      $form.find('#mph_hora_inicio_asignada').val(horarioInfo.inicio); // Por defecto, ocupa todo el bloque
      $form.find('#mph_hora_fin_asignada').val(horarioInfo.fin); // Por defecto, ocupa todo el bloque
      $form.find('#mph_vacantes').val(1); // Default vacantes
      $form.find('#mph_buffer_minutos_antes').val(60); // Default buffer
      $form.find('#mph_buffer_minutos_despues').val(60);
      $form.find('#mph_buffer_linkeado').prop('checked', true);

      // Poblar los selects de Programa/Sede/Rango basándose en los checkboxes recién marcados
      console.log("Poblando selects para asignación...");
      (0,_modal_form_interaction__WEBPACK_IMPORTED_MODULE_2__.poblarSelectsAsignacion)($modal); // Usar la función importada (que lee checkboxes)

      // 6. Ajustar botón Guardar
      var $btnGuardar = $form.find('#mph-guardar-horario');
      if ($btnGuardar.length) {
        var _window$mph_admin_obj2;
        $btnGuardar.text(((_window$mph_admin_obj2 = window.mph_admin_obj) === null || _window$mph_admin_obj2 === void 0 || (_window$mph_admin_obj2 = _window$mph_admin_obj2.i18n) === null || _window$mph_admin_obj2 === void 0 ? void 0 : _window$mph_admin_obj2.guardar_asignacion) || 'Guardar Asignación'); // Nuevo texto i18n
        $btnGuardar.attr('data-action-mode', 'assign_to_existing'); // Nuevo modo
      }

      // 7. Abrir el modal
      console.log("Abriendo modal para asignar a existente...");
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.openModal)($modal);
    } catch (e) {
      console.error("Error al preparar modal para Asignar:", e);
      alert("Error al preparar el formulario de asignación.");
    }
  });

  // --- Acción Editar (Disponibilidad) --- (Placeholder por ahora)
  $tableContainer.on('click', '.mph-accion-editar-disp', function (e) {
    e.preventDefault();
    console.log('Botón Editar Disp. clickeado (Funcionalidad Pendiente)');
    alert('Editar Disponibilidad aún no implementado.');
    // TODO: Lógica similar a Asignar pero pre-llenando el modal completo
  });

  // --- Acción Editar Vacantes ---
  $tableContainer.on('click', '.mph-accion-editar-vacantes', function (e) {
    e.preventDefault();
    var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    console.log('Botón Editar Vacantes clickeado.');
    try {
      var horarioInfo = $button.data('horario-info');
      // ... (verificación horarioInfo) ...
      var $modal = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#mph-modal-horario');
      if (!$modal.length) {
        console.error("Error en prepareModalForEditVacantes: Modal #mph-modal-horario no encontrado.");
        throw new Error("Modal #mph-modal-horario no encontrado."); // Re-lanzar para que el catch externo lo maneje
      }
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.resetModalForm)($modal); // Resetear primero (quita clases de modo)
      prepareModalForEditVacantes($modal, horarioInfo); // Preparar (añade clase de modo)
      (0,_modal_init__WEBPACK_IMPORTED_MODULE_1__.openModal)($modal); // Abrir
    } catch (e) {
      var _window$mph_admin_obj3;
      console.error("Error al preparar/abrir modal para Editar Vacantes:", e);
      alert((_window$mph_admin_obj3 = window.mph_admin_obj) === null || _window$mph_admin_obj3 === void 0 || (_window$mph_admin_obj3 = _window$mph_admin_obj3.i18n) === null || _window$mph_admin_obj3 === void 0 ? void 0 : _window$mph_admin_obj3.error_preparar_edicion);
    }
  });

  // --- Acción Vaciar */
  $tableContainer.on('click', '.mph-accion-vaciar', function (e) {
    e.preventDefault();
    var $button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    var horarioId = $button.data('horario-id');
    var nonce = $button.data('nonce');
    var $fila = $button.closest('tr'); // Fila para feedback visual

    if (!horarioId || !nonce) {
      var _window$mph_admin_obj4;
      console.error('Error: Faltan horario_id o nonce para vaciar.');
      alert(((_window$mph_admin_obj4 = window.mph_admin_obj) === null || _window$mph_admin_obj4 === void 0 || (_window$mph_admin_obj4 = _window$mph_admin_obj4.i18n) === null || _window$mph_admin_obj4 === void 0 ? void 0 : _window$mph_admin_obj4.error_general) || 'Error inesperado.');
      return;
    }
    if (typeof window.mph_admin_obj === 'undefined' || !window.mph_admin_obj.ajax_url || !window.mph_admin_obj.i18n) {
      var _window$mph_admin_obj5;
      console.error("Error crítico: mph_admin_obj o sus propiedades no están disponibles para Vaciar.");
      alert((_window$mph_admin_obj5 = window.mph_admin_obj) === null || _window$mph_admin_obj5 === void 0 || (_window$mph_admin_obj5 = _window$mph_admin_obj5.i18n) === null || _window$mph_admin_obj5 === void 0 ? void 0 : _window$mph_admin_obj5.error_configuracion /*|| "Error interno de configuración."*/);
      return;
    }
    var ajax_url = mph_admin_obj.ajax_url;
    var i18n = mph_admin_obj.i18n;
    var confirmarMsg = i18n.confirmar_vaciado || '¿Estás seguro? Esto convertirá el horario asignado en un bloque vacío.';
    if (confirm(confirmarMsg)) {
      console.log("Intentando vaciar horario ID: ".concat(horarioId));
      $button.prop('disabled', true).css('opacity', 0.5);
      var dataToSend = {
        action: 'mph_vaciar_horario',
        // NUEVA ACCIÓN PHP
        horario_id: horarioId,
        nonce: nonce // Nonce específico para vaciar
      };
      jquery__WEBPACK_IMPORTED_MODULE_0___default().post(ajax_url, dataToSend).done(function (response) {
        if (response.success) {
          console.log("Horario ID: ".concat(horarioId, " vaciado con \xE9xito."));
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
          var _response$data3, _window$mph_admin_obj6, _response$data4;
          console.error('Error servidor al vaciar:', (_response$data3 = response.data) === null || _response$data3 === void 0 ? void 0 : _response$data3.message);
          alert((((_window$mph_admin_obj6 = window.mph_admin_obj) === null || _window$mph_admin_obj6 === void 0 || (_window$mph_admin_obj6 = _window$mph_admin_obj6.i18n) === null || _window$mph_admin_obj6 === void 0 ? void 0 : _window$mph_admin_obj6.error_general) || 'Error.') + ((_response$data4 = response.data) !== null && _response$data4 !== void 0 && _response$data4.message ? ' (' + response.data.message + ')' : ''));
          $button.prop('disabled', false).css('opacity', 1);
        }
      }).fail(function (jqXHR, textStatus, errorThrown) {
        var _window$mph_admin_obj7;
        console.error("Error AJAX al vaciar:", textStatus, errorThrown, jqXHR.responseText);
        alert((_window$mph_admin_obj7 = window.mph_admin_obj) === null || _window$mph_admin_obj7 === void 0 || (_window$mph_admin_obj7 = _window$mph_admin_obj7.i18n) === null || _window$mph_admin_obj7 === void 0 ? void 0 : _window$mph_admin_obj7.error_comunicacion /*|| 'Error de comunicación al intentar vaciar.'*/);
        $button.prop('disabled', false).css('opacity', 1);
      });
    } else {
      console.log('Vaciado cancelado.');
    }
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