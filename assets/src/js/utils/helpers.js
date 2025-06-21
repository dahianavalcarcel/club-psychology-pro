// assets/src/js/utils/helpers.js

/**
 * Retorna una función que solo ejecuta fn después de wait ms sin llamar de nuevo.
 * @param {Function} fn
 * @param {number} wait
 */
export function debounce(fn, wait = 250) {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => fn.apply(this, args), wait);
  };
}

/**
 * Retorna una función que solo ejecuta fn cada interval ms como mínimo.
 * @param {Function} fn
 * @param {number} interval
 */
export function throttle(fn, interval = 250) {
  let last = 0;
  return (...args) => {
    const now = Date.now();
    if (now - last >= interval) {
      last = now;
      fn.apply(this, args);
    }
  };
}

/**
 * Formatea una fecha tipo "YYYY-MM-DD HH:mm:ss"
 * @param {Date} date
 */
export function formatDateTime(date) {
  const pad = n => n.toString().padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}`
       + ` ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}
