// assets/src/js/test/cohesion.js

/**
 * Lógica para el Test de Cohesión de Equipo (GEQ):
 * - Validaciones simples por dimensión
 * - Muestra tooltips con ayuda contextual
 */
export default function initCohesionTest(formSelector = '.cohesion-form') {
  const form = document.querySelector(formSelector);
  if (!form) return;

  // Mostrar ayuda
  form.querySelectorAll('.help-icon').forEach(icon => {
    const tip = icon.nextElementSibling;
    icon.addEventListener('mouseover', () => tip.classList.add('visible'));
    icon.addEventListener('mouseout',  () => tip.classList.remove('visible'));
  });

  // Validar que cada dimensión tenga al menos una respuesta
  form.addEventListener('submit', (e) => {
    const dimensions = form.querySelectorAll('.dimension-group');
    let valid = true;
    dimensions.forEach(group => {
      if (!group.querySelector('input[type="radio"]:checked')) {
        valid = false;
        group.classList.add('error');
      } else {
        group.classList.remove('error');
      }
    });
    if (!valid) {
      e.preventDefault();
      alert('Por favor responde todas las dimensiones antes de continuar.');
    }
  });
}
