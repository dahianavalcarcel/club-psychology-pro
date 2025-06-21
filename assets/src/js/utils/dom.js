// assets/src/js/utils/dom.js

/**
 * Selector único
 * @param {string} sel
 * @returns {Element|null}
 */
export function qs(sel) {
  return document.querySelector(sel);
}

/**
 * Selector múltiple
 * @param {string} sel
 * @returns {NodeListOf<Element>}
 */
export function qsa(sel) {
  return document.querySelectorAll(sel);
}

/**
 * Añade un listener
 * @param {Element | string} target
 * @param {string} event
 * @param {Function} handler
 */
export function on(target, event, handler) {
  const el = typeof target === 'string' ? qs(target) : target;
  if (el) el.addEventListener(event, handler);
}

/**
 * Elimina un listener
 * @param {Element | string} target
 * @param {string} event
 * @param {Function} handler
 */
export function off(target, event, handler) {
  const el = typeof target === 'string' ? qs(target) : target;
  if (el) el.removeEventListener(event, handler);
}

/**
 * Añade o quita clase CSS
 * @param {Element} el
 * @param {string} className
 */
export function toggleClass(el, className) {
  el.classList.toggle(className);
}
