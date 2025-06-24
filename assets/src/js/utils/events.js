// assets/src/js/utils/events.js

const events = {};

/**
 * Suscribe un handler a un evento.
 * @param {string} event Nombre del evento.
 * @param {Function} handler Función a ejecutar cuando se dispare el evento.
 */
export function subscribe(event, handler) {
  if (!events[event]) {
    events[event] = [];
  }
  events[event].push(handler);
}

/**
 * Desuscribe un handler de un evento.
 * @param {string} event Nombre del evento.
 * @param {Function} handler Función previamente suscrita.
 */
export function unsubscribe(event, handler) {
  if (!events[event]) return;
  events[event] = events[event].filter(h => h !== handler);
}

/**
 * Dispara un evento, ejecutando todos los handlers suscritos.
 * @param {string} event Nombre del evento.
 * @param {any} payload Datos a pasar a los handlers.
 */
export function publish(event, payload) {
  if (!events[event]) return;
  events[event].forEach(handler => {
    try {
      handler(payload);
    } catch (err) {
      console.error(`Error en handler de evento "${event}":`, err);
    }
  });
}
