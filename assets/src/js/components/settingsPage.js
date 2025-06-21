// assets/src/js/components/settingsPage.js

/**
 * Inicializa la página de ajustes: cambia de pestañas en sidebar.
 */
export default function initSettingsPage(sidebarSelector = '.settings-sidebar') {
  const sidebar = document.querySelector(sidebarSelector);
  if (!sidebar) return;

  sidebar.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = link.getAttribute('href').replace('#', '');
      // Desactivar todas las secciones
      document.querySelectorAll('.settings-content > .section').forEach(sec => {
        sec.classList.toggle('active', sec.id === targetId);
      });
      // Marcar link activo
      sidebar.querySelectorAll('a').forEach(a => a.classList.toggle('active', a === link));
    });
  });
}
