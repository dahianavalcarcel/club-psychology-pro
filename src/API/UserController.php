<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Api\Controller;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use ClubPsychologyPro\Core\Container;
use ClubPsychologyPro\Users\UserManager;
use ClubPsychologyPro\Test\TestManager;

/**
 * UserController
 *
 * Controlador REST para operaciones relacionadas con el usuario actual.
 */
class UserController
{
    /**
     * Devuelve la información básica del usuario autenticado.
     *
     * GET /wp-json/cpp/v1/users/me
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getCurrentUser(WP_REST_Request $request)
    {
        $userManager = Container::getInstance()->get(UserManager::class);

        $user = $userManager->getCurrentUser();
        if (!$user) {
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to access this endpoint.', 'club-psychology-pro'),
                ['status' => 401]
            );
        }

        return rest_ensure_response([
            'id'       => $user->ID,
            'username' => $user->user_login,
            'email'    => $user->user_email,
            'name'     => $user->display_name,
        ]);
    }

    /**
     * Lista los tests asociados al usuario autenticado.
     *
     * GET /wp-json/cpp/v1/users/me/tests
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getUserTests(WP_REST_Request $request)
    {
        $userManager  = Container::getInstance()->get(UserManager::class);
        $testManager  = Container::getInstance()->get(TestManager::class);

        $user = $userManager->getCurrentUser();
        if (!$user) {
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to access this endpoint.', 'club-psychology-pro'),
                ['status' => 401]
            );
        }

        $tests = $testManager->getTestsForUser($user->ID);

        return rest_ensure_response([
            'user'  => [
                'id'       => $user->ID,
                'username' => $user->user_login,
                'email'    => $user->user_email,
                'name'     => $user->display_name,
            ],
            'tests' => $tests,
        ]);
    }
}
