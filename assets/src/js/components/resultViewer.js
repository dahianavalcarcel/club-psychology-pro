// assets/src/js/components/resultViewer.js

/**
 * Inicializa la vista de resultados: puede cargar detalles adicionales.
 */
export default function initResultViewer(selector = '.result-viewer') {
  const container = document.querySelector(selector);
  if (!container) return;

  const loadMoreBtn = container.querySelector('.load-more-details');
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', async () => {
      const resultId = container.dataset.resultId;
      loadMoreBtn.disabled = true;
      loadMoreBtn.textContent = 'Cargando...';

      try {
        const res = await fetch(`${cppData.restUrl}results/${resultId}`, {
          headers: { 'X-WP-Nonce': cppData.nonce }
        });
        const data = await res.json();
        // Renderiza detalles adicionales (puedes usar un template)
        container.insertAdjacentHTML('beforeend', `<pre>${JSON.stringify(data, null, 2)}</pre>`);
      } catch (err) {
        console.error('Error cargando detalles:', err);
      } finally {
        loadMoreBtn.disabled = false;
        loadMoreBtn.textContent = 'Ver más detalles';
      }
    });
  }
}
