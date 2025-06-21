<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Api\Controller;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use ClubPsychologyPro\Core\Container;
use ClubPsychologyPro\Test\ResultManager;

/**
 * ResultController
 *
 * Controlador REST para obtener un resultado de test por ID.
 */
class ResultController
{
    /**
     * Devuelve los datos de un resultado específico.
     *
     * GET /wp-json/cpp/v1/results/{id}
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getResult(WP_REST_Request $request)
    {
        $id = (int) $request->get_param('id');
        if ($id <= 0) {
            return new WP_Error(
                'invalid_result_id',
                __('Invalid result ID.', 'club-psychology-pro'),
                ['status' => 400]
            );
        }

        /** @var ResultManager $resultManager */
        $resultManager = Container::getInstance()->get(ResultManager::class);
        $resultData    = $resultManager->getById($id);

        if (empty($resultData)) {
            return new WP_Error(
                'result_not_found',
                __('Result not found.', 'club-psychology-pro'),
                ['status' => 404]
            );
        }

        return rest_ensure_response($resultData);
    }
}
