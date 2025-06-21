/**
 * main.js
 *
 * JavaScript principal para la parte pública de Club Psychology Pro.
 * Se encarga de:
 *  - Envío de formularios de test vía AJAX
 *  - Carga dinámica de panel de usuario
 *  - Visualización de resultados
 *  - Manejo de errores y estados de carga
 *
 * Variables globales inyectadas por wp_localize_script en PHP:
 *   cppData.ajaxUrl   – URL de admin-ajax.php para peticiones AJAX
 *   cppData.restUrl   – Base de la REST API (/wp-json/cpp/v1/)
 *   cppData.nonce     – Nonce para validar peticiones AJAX
 *   cppData.config    – Configuración adicional (debug, locale, etc.)
 */

// assets/src/js/main.js

import initDashboard      from './components/dashboard';
import initSettingsPage   from './components/settingsPage';

import initTestForm       from './components/testForm';
import initResultViewer   from './components/resultViewer';
import initUserPanel      from './components/userPanel';

import initBigFiveTest    from './test/bigFive';
import initCohesionTest   from './test/cohesion';
import initMonitorTest    from './test/monitor';

import { ajaxAction }     from './utils/ajax';
import { qs, on }         from './utils/dom';
import { debounce, showMessage } from './utils/helpers';
import { parseQuery }     from './utils/url';
import { subscribe }      from './utils/events';

document.addEventListener('DOMContentLoaded', () => {
  // 1) Inicializaciones globales
  initDashboard();
  initSettingsPage();

  // 2) Shortcodes / páginas de usuario
  initTestForm();
  initResultViewer();
  initUserPanel();

  // 3) Tests específicos
  initBigFiveTest();
  initCohesionTest();
  initMonitorTest();

  // 4) Ejemplo de uso de utilidades
  on('#resend-btn', 'click', () => {
    const { id } = parseQuery();
    ajaxAction('cpp_resend_test', { result_id: id })
      .then(resp => {
        if (resp.success) {
          showMessage('#cpp-global-message', 'Email reenviado correctamente', 'success');
        }
      })
      .catch(() => {
        showMessage('#cpp-global-message', 'Error al reenviar email', 'error');
      });
  });

  // 5) Formularios y paneles dinámicos
  bindTestForm();
  loadUserPanel();
});

/**
 * Enviar respuestas de un test vía AJAX
 */
function bindTestForm() {
  const $form = $('#cpp-test-form');
  if (!$form.length) return;

  const $msg = $('<div id="cpp-form-message" class="cpp-message-container"></div>');
  $form.before($msg);

  $form.on('submit', function(e) {
    e.preventDefault();

    const data = $form.serializeArray().reduce((obj, field) => {
      obj[field.name] = field.value;
      return obj;
    }, {});
    data.action = 'cpp_submit_test';
    data._wpnonce = cppData.nonce;

    showMessage('#cpp-form-message', 'Procesando...', 'info');
    $.post(cppData.ajaxUrl, data)
      .done(res => {
        if (res.success) {
          showMessage('#cpp-form-message', res.data.message || 'Test enviado con éxito', 'success');
          if (res.data.redirect) {
            window.location.href = res.data.redirect;
          }
        } else {
          showMessage('#cpp-form-message', res.data || 'Error al enviar el test', 'error');
        }
      })
      .fail(() => {
        showMessage('#cpp-form-message', 'Error de red', 'error');
      });
  });
}

/**
 * Cargar panel de usuario vía REST API
 */
function loadUserPanel() {
  const $panel = $('#cpp-user-panel');
  if (!$panel.length) return;

  $panel.html('<p class="cpp-loading">Cargando panel...</p>');
  fetch(cppData.restUrl + 'users/me/tests', {
    headers: { 'X-WP-Nonce': cppData.nonce }
  })
  .then(res => res.json())
  .then(json => {
    if (Array.isArray(json)) {
      let html = '<ul class="cpp-test-list">';
      json.forEach(test => {
        html += `
          <li class="cpp-test-item">
            <strong>${test.name}</strong> — ${test.status}
            <button class="cpp-btn-view-result" data-id="${test.result_id}">Ver resultado</button>
          </li>`;
      });
      html += '</ul><div id="cpp-result-container"></div>';
      $panel.html(html);
      bindResultButtons();
    } else {
      $panel.html('<p>No hay tests disponibles.</p>');
    }
  })
  .catch(() => {
    $panel.html('<p>Error al cargar el panel de usuario.</p>');
  });
}

/**
 * Vincular botones "Ver resultado" para cargarlo vía AJAX
 */
function bindResultButtons() {
  $('.cpp-btn-view-result').on('click', function() {
    const resultId = $(this).data('id');
    const $container = $('#cpp-result-container');
    $container.html('<p class="cpp-loading">Cargando resultado...</p>');

    $.ajax({
      url: cppData.ajaxUrl,
      method: 'POST',
      data: {
        action: 'cpp_get_test_results',
        result_id: resultId,
        _wpnonce: cppData.nonce
      }
    })
    .done(res => {
      if (res.success) {
        $container.html(res.data.html);
      } else {
        $container.html(`<p class="cpp-error">${res.data || 'Error al obtener resultado'}</p>`);
      }
    })
    .fail(() => {
      $container.html('<p class="cpp-error">Error de red</p>');
    });
  });
}
