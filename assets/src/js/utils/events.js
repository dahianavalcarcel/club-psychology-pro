// assets/src/js/utils/events.js

const listeners = {};

/**
 * Registra un listener para un evento
 * @param {string} event
 * @param {Function} cb
 */
export function subscribe(event, cb) {
  if (!listeners[event]) listeners[event] = [];
  listeners[event].push(cb);
}

/**
 * Desuscribe un listener
 * @param {string} event
 * @param {Function} cb
 */
export function unsubscribe(event, cb) {
  if (!listeners[event]) return;
  listeners[event] = listeners[event].filter(fn => fn !== cb);
}

/**
 * Emite un evento con datos
 * @param {string} event
 * @param {any} data
 */
export function publish(event, data) {
  (listeners[event] || []).forEach(fn => {
    try { fn(data); } catch (e) { console.error(e); }
  });
}
