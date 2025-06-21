// assets/src/js/components/dashboard.js

/**
 * Inicializa comportamientos de la dashboard administrativa.
 * 
 * Por ejemplo puede manejar clicks en las cards para filtrar datos.
 */
export default function initDashboard(selector = '.dashboard-stats') {
  const container = document.querySelector(selector);
  if (!container) return;

  container.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', () => {
      const metric = card.dataset.metric;
      // Aquí podrías disparar un evento o recargar datos vía AJAX
      console.log(`Filtrando dashboard por métrica: ${metric}`);
      // fetch(`/wp-json/cpp/v1/stats?metric=${metric}`)
      //   .then(res => res.json())
      //   .then(renderDashboard);
    });
  });
}
