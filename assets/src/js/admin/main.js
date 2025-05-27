// assets/src/js/admin/main.js
import $ from 'jquery';
import { initDialog, openModal, closeModal, resetModalForm } from './modal-init'; // Sin initModalButtons
import { initFormInteractions } from './modal-form-interaction';
import { initAjaxSubmit } from './modal-ajax';
import { initTableActions } from './table-actions';

$(window).on('load', function() {
    console.log('Admin JS Bundle Loaded (window.load)');

    const $modal = $('#mph-modal-horario');
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
        initFormInteractions($modal);
        initAjaxSubmit($modal); // Pasar $modal
        initTableActions('#mph-tabla-horarios-container');
        // initModalButtons($modal); // ELIMINADA ESTA LLAMADA
        console.log("Módulos dependientes inicializados.");
    }

    console.log("Inicializando diálogo...");
    initDialog($modal, resetModalForm); // Pasar $modal

    // Manejador Botón Cancelar (puesto aquí por simplicidad de acceso a $modal y closeModal)
     if ($modal.length) {
         $modal.on('click', '#mph-cancelar-modal', function () {
             console.log("Botón Cancelar Clickeado (desde main.js)");
             closeModal($modal); // Llamar a closeModal pasando $modal
         });
         console.log("Manejador para #mph-cancelar-modal inicializado (en main.js).");
    }

    // Manejador Botón Abrir
    const $metaBoxWrapper = $('.mph-gestion-horarios-wrapper');
    if ($metaBoxWrapper.length) {
        $metaBoxWrapper.on('click', '#mph-abrir-modal-horario', function () {
            console.log('Abrir Modal Clicked');
            resetModalForm($modal); // Pasar $modal
            openModal($modal); // Pasar $modal
        });
    } else { console.warn("Wrapper del Metabox no encontrado."); }

    // Iniciar la verificación para el objeto localizado
    checkAdminObject();

    console.log('MPH Admin Initializations Started...');
}); // Fin window.load