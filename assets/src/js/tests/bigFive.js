// assets/src/js/test/bigFive.js

/**
 * Lógica específica para el Test Big Five (B5-AI):
 * - Manejo de secciones (dominios y facetas)
 * - Cálculo de progreso por sección
 */
export default function initBigFiveTest(formSelector = '.bigfive-form') {
  const form = document.querySelector(formSelector);
  if (!form) return;

  const sections = Array.from(form.querySelectorAll('.domain-section'));
  const progressBar = form.querySelector('.test-progress-bar');

  const updateProgress = () => {
    const answered = form.querySelectorAll('input[type="radio"]:checked').length;
    const total    = form.querySelectorAll('input[type="radio"]').length;
    const pct      = Math.round((answered / total) * 100);
    if (progressBar) progressBar.style.width = `${pct}%`;
  };

  // Escucha cambios en todas las preguntas del formulario
  form.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', updateProgress);
  });

  // Navegación entre secciones
  form.querySelectorAll('.next-domain').forEach(btn => {
    btn.addEventListener('click', () => {
      const current = btn.closest('.domain-section');
      const index   = sections.indexOf(current);
      if (index < sections.length - 1) {
        current.classList.remove('active');
        sections[index + 1].classList.add('active');
      }
    });
  });

  form.querySelectorAll('.prev-domain').forEach(btn => {
    btn.addEventListener('click', () => {
      const current = btn.closest('.domain-section');
      const index   = sections.indexOf(current);
      if (index > 0) {
        current.classList.remove('active');
        sections[index - 1].classList.add('active');
      }
    });
  });

  // Inicializar progreso
  updateProgress();
}
