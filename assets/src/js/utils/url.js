// assets/src/js/utils/url.js

/**
 * Parsea la query string de la URL actual
 * @returns {Object<string,string>}
 */
export function parseQuery() {
  return window.location.search
    .substring(1)
    .split('&')
    .reduce((acc, pair) => {
      const [k, v] = pair.split('=').map(decodeURIComponent);
      if (k) acc[k] = v;
      return acc;
    }, {});
}

/**
 * Navega a una nueva URL (conservar historial)
 * @param {string} path
 * @param {Object} params
 */
export function navigate(path, params = {}) {
  const qs = Object.entries(params)
    .map(([k,v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&');
  window.location.href = path + (qs ? `?${qs}` : '');
}
