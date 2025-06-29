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

// Carga de vendors (asegúrate de que estos archivos existan en tu carpeta `assets/src/vendor`)
import '../vendor/bigfive.bundle.js';
import '../vendor/bigfive-es.js';

// Componentes globales
import initDashboard    from './components/dashboard';
import initSettingsPage from './components/settingsPage';

// Componentes de shortcodes/páginas
import initTestForm     from './components/testForm';
import initResultViewer from './components/resultViewer';
import initUserPanel    from './components/userPanel';

// Inits de tests concretos
import initBigFiveTest  from './test/bigFive';
import initCohesionTest from './test/cohesion';
import initMonitorTest  from './test/monitor';

// Utilidades
import { ajaxAction }   from './utils/ajax';
import { qs, on }       from './utils/dom';
import { debounce, showMessage } from './utils/helpers';
import { parseQuery }   from './utils/url';

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
  bindResultButtons();
  
  // 6) Nueva funcionalidad del panel de usuario mejorado
  initEnhancedUserPanel();
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
            <button class="button cpp-btn-view-result" data-id="${test.result_id}">Ver resultado</button>
          </li>`;
      });
      html += '</ul><div id="cpp-result-container" style="margin-top:1em;"></div>';
      $panel.html(html);
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
  $(document).on('click', '.cpp-btn-view-result', function() {
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

/**
 * ========================================================================
 * NUEVA FUNCIONALIDAD - Panel de Usuario Mejorado
 * ========================================================================
 */

/**
 * Inicialización del panel de usuario mejorado
 */
function initEnhancedUserPanel() {
  console.log('Inicializando Panel de Usuario Mejorado');
  
  // Verificar si estamos en la página del panel de usuario
  const userPanel = document.querySelector('.cpp-user-panel');
  
  if (userPanel) {
    bindToggleTestForm();
    bindTestTypeSelection();
    bindMonitorTestSelection();
    bindTestFormValidation();
    bindEnhancedResultButtons();
  } else {
    console.log('Panel de usuario no encontrado en esta página');
  }
}

/**
 * Mostrar/ocultar el formulario inline de creación de tests
 */
function bindToggleTestForm() {
  console.log('bindToggleTestForm - Enhanced Version');
  
  const openBtn = document.querySelector('.js-open-test-form');
  const closeBtn = document.querySelector('.js-close-test-form');
  const formContainer = document.getElementById('cpp-test-form-container');
  const testForm = document.getElementById('cppTestRequestForm');

  if (!openBtn || !formContainer) {
    console.warn('Elementos del formulario de test no encontrados');
    return;
  }

  // Abrir formulario
  openBtn.addEventListener('click', () => {
    formContainer.style.display = 'block';
    openBtn.style.display = 'none';
    formContainer.scrollIntoView({ behavior: 'smooth' });
  });

  // Cerrar formulario (si existe el botón)
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      formContainer.style.display = 'none';
      openBtn.style.display = 'inline-block';
      
      // Limpiar formulario
      if (testForm) {
        testForm.reset();
        hideMonitorTests();
        clearMonitorSelection();
      }
    });
  }
}

/**
 * Manejo de la selección de tipo de test
 */
function bindTestTypeSelection() {
  console.log('bindTestTypeSelection');
  
  const testTypeRadios = document.querySelectorAll('input[name="test_type"]');
  
  if (testTypeRadios.length === 0) {
    console.warn('Radios de tipo de test no encontrados');
    return;
  }

  testTypeRadios.forEach(radio => {
    radio.addEventListener('change', (e) => {
      if (e.target.value === 'monitor') {
        showMonitorTests();
      } else {
        hideMonitorTests();
        clearMonitorSelection();
      }
    });
  });
}

/**
 * Mostrar tests monitor
 */
function showMonitorTests() {
  const monitorTestsContainer = document.getElementById('cpp-monitor-tests');
  if (monitorTestsContainer) {
    monitorTestsContainer.style.display = 'block';
  }
}

/**
 * Ocultar tests monitor
 */
function hideMonitorTests() {
  const monitorTestsContainer = document.getElementById('cpp-monitor-tests');
  if (monitorTestsContainer) {
    monitorTestsContainer.style.display = 'none';
  }
}

/**
 * Limpiar selección de test monitor
 */
function clearMonitorSelection() {
  const monitorOptions = document.querySelectorAll('.cpp-monitor-option');
  const hiddenInput = document.getElementById('cpp-monitor-test-value');
  
  monitorOptions.forEach(option => {
    option.classList.remove('button-primary');
    option.classList.add('button');
  });
  
  if (hiddenInput) {
    hiddenInput.value = '';
  }
}

/**
 * Manejo de la selección de tests monitor
 */
function bindMonitorTestSelection() {
  console.log('bindMonitorTestSelection');
  
  const monitorOptions = document.querySelectorAll('.cpp-monitor-option');
  const hiddenInput = document.getElementById('cpp-monitor-test-value');
  
  if (monitorOptions.length === 0) {
    console.warn('Opciones de test monitor no encontradas');
    return;
  }

  monitorOptions.forEach(option => {
    option.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Remover selección anterior
      monitorOptions.forEach(opt => {
        opt.classList.remove('button-primary');
        opt.classList.add('button');
      });
      
      // Marcar como seleccionado
      option.classList.remove('button');
      option.classList.add('button-primary');
      
      // Establecer valor
      if (hiddenInput) {
        hiddenInput.value = option.dataset.value;
      }
    });
  });
}

/**
 * Validación del formulario de test
 */
function bindTestFormValidation() {
  console.log('bindTestFormValidation');
  
  const testForm = document.getElementById('cppTestRequestForm');
  const submitBtn = testForm?.querySelector('input[type="submit"]');
  
  if (!testForm) {
    console.warn('Formulario de test no encontrado');
    return;
  }

  testForm.addEventListener('submit', (e) => {
    const testType = testForm.querySelector('input[name="test_type"]:checked')?.value;
    const monitorTestValue = document.getElementById('cpp-monitor-test-value')?.value;
    
    // Validar test monitor si está seleccionado
    if (testType === 'monitor' && !monitorTestValue) {
      e.preventDefault();
      alert('Por favor selecciona un test monitor específico.');
      return false;
    }
    
    // Estado de carga
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.value = 'Creando Test...';
    }
    
    return true;
  });
}

/**
 * Manejo mejorado de botones para ver resultados de tests
 */
function bindEnhancedResultButtons() {
  console.log('bindEnhancedResultButtons');
  
  // Usar event delegation para manejar botones dinámicos
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('cpp-btn-view-result')) {
      const testId = e.target.dataset.id;
      const resultContainer = document.getElementById('cpp-result-container');
      
      if (resultContainer && testId) {
        resultContainer.innerHTML = '<p>Cargando resultado del test...</p>';
        loadTestResult(testId);
      }
    }
  });
}

/**
 * Función para cargar resultado de test con manejo de errores
 */
function loadTestResult(testId) {
  // Si jQuery está disponible, usar la implementación existente
  if (typeof $ !== 'undefined') {
    const $container = $('#cpp-result-container');
    $container.html('<p class="cpp-loading">Cargando resultado...</p>');

    $.ajax({
      url: cppData.ajaxUrl,
      method: 'POST',
      data: {
        action: 'cpp_get_test_results',
        result_id: testId,
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
  } else {
    // Implementación con fetch API
    const formData = new FormData();
    formData.append('action', 'cpp_get_test_results');
    formData.append('result_id', testId);
    formData.append('_wpnonce', cppData.nonce);

    fetch(cppData.ajaxUrl, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      const resultContainer = document.getElementById('cpp-result-container');
      if (resultContainer) {
        if (data.success) {
          resultContainer.innerHTML = data.data.html;
        } else {
          resultContainer.innerHTML = `<p class="cpp-error">${data.data || 'Error al obtener resultado'}</p>`;
        }
      }
    })
    .catch(error => {
      console.error('Error loading test result:', error);
      const resultContainer = document.getElementById('cpp-result-container');
      if (resultContainer) {
        resultContainer.innerHTML = '<p class="cpp-error">Error al cargar el resultado del test.</p>';
      }
    });
  }
}