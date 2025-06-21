// assets/src/js/components/testForm.js

/**
 * Inicializa el formulario de test: manejo de envío via AJAX y barra de progreso.
 */
export default function initTestForm(formSelector = '.cpp-test-form') {
  const form = document.querySelector(formSelector);
  if (!form) return;

  const progressBar = document.createElement('div');
  progressBar.className = 'test-progress-bar';
  form.prepend(progressBar);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);
    progressBar.style.width = '10%';
    
    try {
      const response = await fetch(cppData.restUrl + 'tests/' + formData.get('test_type') + '/submit', {
        method: 'POST',
        headers: {
          'X-WP-Nonce': cppData.nonce,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(Object.fromEntries(formData))
      });
      progressBar.style.width = '70%';
      const result = await response.json();
      progressBar.style.width = '100%';
      // Redirigir o mostrar resultado
      window.location.href = `${cppData.siteUrl}/resultados?id=${result.id}`;
    } catch (err) {
      console.error('Error enviando test:', err);
      progressBar.classList.add('error');
    }
  });
}
