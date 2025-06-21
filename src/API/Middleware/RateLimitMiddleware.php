<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Api\Middleware;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * RateLimitMiddleware
 *
 * Controla el ritmo de peticiones a la API REST para evitar abusos.
 * Utiliza transients de WordPress para llevar la cuenta de solicitudes por usuario o IP.
 */
class RateLimitMiddleware
{
    /**
     * Maneja la limitación de tasa para una petición REST.
     *
     * @param WP_REST_Request $request La petición entrante.
     * @param callable        $next    La siguiente función/middleware a invocar.
     *
     * @return mixed WP_Error|WP_REST_Response Lo devuelto por el siguiente middleware o un error.
     */
    public function handle(WP_REST_Request $request, callable $next)
    {
        // Obtener identificador: usuario logueado o dirección IP
        if (is_user_logged_in()) {
            $identifier = 'user_' . get_current_user_id();
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $identifier = 'ip_' . preg_replace('/[^0-9a-fA-F:\.]/', '', $ip);
        }

        // Ruta del endpoint como parte de la clave
        $route = str_replace('/', '_', trim($request->get_route(), '/'));
        $key = "cpp_rl_{$identifier}_{$route}";

        // Parámetros de configuración: límite y ventana (en segundos)
        $config = $this->getRateLimitConfig();
        $limit  = (int) ($config['limit']  ?? 60);
        $period = (int) ($config['window'] ?? 60);

        // Obtener contador actual
        $count = (int) get_transient($key);

        if ($count >= $limit) {
            return new WP_Error(
                'cpp_rate_limit_exceeded',
                sprintf(
                    /* translators: 1: número de segundos restantes en la ventana */
                    __('Rate limit exceeded. Please try again in %d seconds.', 'club-psychology-pro'),
                    $period
                ),
                [ 'status' => 429 ]
            );
        }

        // Incrementar y reconfigurar el transient (ventana deslizante)
        $count++;
        set_transient($key, $count, $period);

        return $next($request);
    }

    /**
     * Recupera la configuración de limitación de tasa.
     *
     * Espera un array con las claves:
     *  - 'limit'  => número máximo de peticiones en la ventana
     *  - 'window' => duración de la ventana en segundos
     *
     * @return array
     */
    protected function getRateLimitConfig(): array
    {
        // Podría leerse desde opciones del plugin o un ConfigManager central
        $default = [
            'limit'  => 60,
            'window' => 60,
        ];

        $opts = get_option('cpp_api_rate_limit');
        if ( is_array($opts) && isset($opts['limit'], $opts['window']) ) {
            return [
                'limit'  => (int) $opts['limit'],
                'window' => (int) $opts['window'],
            ];
        }

        return $default;
    }
}
