// assets/src/js/utils/ajax.js

/**
 * Envia una petición AJAX a admin-ajax.php
 * @param {string} action Acción de WordPress
 * @param {Object} data   Datos a enviar
 * @returns {Promise<any>}
 */
export function ajaxAction(action, data = {}) {
  const formData = new FormData();
  formData.append('action', action);
  formData.append('security', window.cppData?.nonce || '');

  Object.entries(data).forEach(([key, val]) => {
    formData.append(key, val);
  });

  return fetch(window.cppData.ajaxUrl, {
    method: 'POST',
    credentials: 'same-origin',
    body: formData,
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`AJAX ${action} failed: ${response.status}`);
      }
      return response.json();
    });
}
