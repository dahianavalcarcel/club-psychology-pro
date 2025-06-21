<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Api;

use WP_REST_Server;
use ClubPsychologyPro\Core\Container;
use ClubPsychologyPro\Api\Middleware\AuthMiddleware;
use ClubPsychologyPro\Api\Middleware\ValidationMiddleware;
use ClubPsychologyPro\Api\Middleware\RateLimitMiddleware;
use ClubPsychologyPro\Test\TestTypeRegistry;

/**
 * ApiManager
 *
 * Encapsula el registro de las rutas REST y aplica middleware.
 */
class ApiManager
{
    /**
     * Inicializa la API: registra hooks de WordPress.
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Registra todas las rutas bajo el namespace /cpp/v1.
     */
    public function registerRoutes(): void
    {
        $namespace = 'cpp/v1';
        $methods   = WP_REST_Server::CREATABLE | WP_REST_Server::READABLE;

        // Inyectar middleware comunes
        $auth       = Container::getInstance()->get(AuthMiddleware::class);
        $validate   = Container::getInstance()->get(ValidationMiddleware::class);
        $rateLimit  = Container::getInstance()->get(RateLimitMiddleware::class);

        /** @var TestTypeRegistry $registry */
        $registry = Container::getInstance()->get(TestTypeRegistry::class);

        // Ruta para listar tests disponibles (GET /cpp/v1/tests)
        register_rest_route($namespace, '/tests', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [Container::getInstance()->get(Controller\TestController::class), 'listTests'],
            'permission_callback' => function () {
                return true;
            },
        ]);

        // Rutas dinámicas por tipo de test
        foreach ($registry->getTypes() as $type) {
            // Enviar respuestas: POST /cpp/v1/tests/{type}/submit
            register_rest_route($namespace, "/tests/{$type}/submit", [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [Container::getInstance()->get(Controller\TestController::class), 'submitTest'],
                'permission_callback' => [$auth, 'handle'],
                'args'                => [
                    'type' => [
                        'required' => true,
                        'validate_callback' => function ($param) use ($registry) {
                            return $registry->has($param);
                        },
                    ],
                ],
                'schema' => null,
                'args' => [],
                'before_invoke' => [
                    [$validate, 'handle'],
                    [$rateLimit, 'handle'],
                ],
            ]);

            // Obtener resultado: GET /cpp/v1/results/{id}
            register_rest_route($namespace, "/results/(?P<id>\d+)", [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [Container::getInstance()->get(Controller\ResultController::class), 'getResult'],
                'permission_callback' => [$auth, 'handle'],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'validate_callback' => 'is_numeric',
                    ],
                ],
            ]);
        }

        // Ruta usuario actual: GET /cpp/v1/users/me/tests
        register_rest_route($namespace, '/users/me/tests', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [Container::getInstance()->get(Controller\UserController::class), 'getMyTests'],
            'permission_callback' => [$auth, 'handle'],
        ]);
    }
}
