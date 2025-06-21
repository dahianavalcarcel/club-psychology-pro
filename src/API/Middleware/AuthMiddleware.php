<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Api\Middleware;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * AuthMiddleware
 *
 * Verifica que el usuario esté autenticado antes de permitir el acceso
 * a los endpoints de la API. Soporta WP REST nonce y Authorization Bearer token.
 */
class AuthMiddleware
{
    /**
     * Maneja la autenticación para una petición REST.
     *
     * @param WP_REST_Request $request La petición entrante.
     * @param callable        $next    La siguiente función/middleware a invocar.
     *
     * @return mixed WP_Error|WP_REST_Response Lo devuelto por el siguiente middleware o un error.
     */
    public function handle(WP_REST_Request $request, callable $next)
    {
        // 1) Verificar WP REST nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'cpp_rest_invalid_nonce',
                __('Invalid or missing nonce.', 'club-psychology-pro'),
                [ 'status' => 403 ]
            );
        }

        // 2) Verificar Authorization Bearer token (opcional, si se usa JWT u otro esquema)
        $authHeader = $request->get_header('Authorization');
        if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            // TODO: reemplazar con tu lógica de validación de token
            $userId = $this->validateBearerToken($token);
            if (empty($userId)) {
                return new WP_Error(
                    'cpp_rest_invalid_token',
                    __('Invalid authorization token.', 'club-psychology-pro'),
                    [ 'status' => 401 ]
                );
            }
            // Si el token es válido, establecer el usuario actual
            wp_set_current_user((int) $userId);
        }

        // 3) Asegurarse de que el usuario esté logueado
        if (!is_user_logged_in()) {
            return new WP_Error(
                'cpp_rest_not_logged_in',
                __('You must be logged in to access this endpoint.', 'club-psychology-pro'),
                [ 'status' => 401 ]
            );
        }

        // Pasar al siguiente middleware o controlador
        return $next($request);
    }

    /**
     * Valida un Bearer token y retorna el ID de usuario asociado o null.
     *
     * @param string $token El token JWT u otro esquema.
     * @return int|null     ID de usuario válido o null si inválido.
     */
    protected function validateBearerToken(string $token): ?int
    {
        // Ejemplo placeholder: aquí deberías implementar la verificación de tu JWT
        // Por ejemplo, usar Firebase\JWT o alguna librería personalizada:
        //
        // try {
        //     $payload = JWT::decode($token, $secretKey, ['HS256']);
        //     return (int) $payload->user_id;
        // } catch (\Exception $e) {
        //     return null;
        // }
        //
        return null;
    }
}
