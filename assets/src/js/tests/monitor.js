// assets/src/js/test/monitor.js

/**
 * Lógica para los Monitor Tests (e.g., PHQ-SADS, ARS, MISS, WHO-5):
 * - Temporizador opcional
 * - Preventa de envío múltiple
 */
export default function initMonitorTest(formSelector = '.monitor-form') {
  const form = document.querySelector(formSelector);
  if (!form) return;

  const submitBtn = form.querySelector('button[type="submit"]');
  let inProgress = false;

  // Temporizador si existe
  const timerEl = form.querySelector('.monitor-timer');
  if (timerEl) {
    let seconds = parseInt(timerEl.dataset.time, 10) || 0;
    const updateTimer = () => {
      const mins = Math.floor(seconds / 60);
      const secs = seconds % 60;
      timerEl.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
      if (seconds > 0) {
        seconds--;
        setTimeout(updateTimer, 1000);
      }
    };
    updateTimer();
  }

  // Previene envío doble
  form.addEventListener('submit', (e) => {
    if (inProgress) {
      e.preventDefault();
      return;
    }
    inProgress = true;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';
  });
}
