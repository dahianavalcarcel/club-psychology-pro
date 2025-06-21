<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Api\Middleware;

use WP_REST_Request;
use WP_Error;
use ClubPsychologyPro\Core\Container;
use ClubPsychologyPro\Test\TestTypeRegistry;

/**
 * ValidationMiddleware
 *
 * Valida la estructura y contenido de las peticiones antes de llegar al controlador.
 */
class ValidationMiddleware
{
    /**
     * Maneja la validación de la petición REST.
     *
     * @param WP_REST_Request $request La petición entrante.
     * @param callable        $next    La siguiente función o middleware.
     *
     * @return mixed WP_Error|mixed WP_Error en caso de fallo, o la respuesta del siguiente middleware.
     */
    public function handle(WP_REST_Request $request, callable $next)
    {
        // 1. Obtener el tipo de test de la ruta: /wp-json/cpp/v1/tests/{type}/submit
        $type = $request->get_param('type');
        if (empty($type)) {
            return new WP_Error(
                'cpp_missing_test_type',
                __('Test type is missing.', 'club-psychology-pro'),
                [ 'status' => 400 ]
            );
        }

        // 2. Resolver el TestType a través del TestTypeRegistry
        /** @var TestTypeRegistry $registry */
        $registry = Container::getInstance()->get(TestTypeRegistry::class);
        if (! $registry->has($type)) {
            return new WP_Error(
                'cpp_invalid_test_type',
                __('Invalid test type.', 'club-psychology-pro'),
                [ 'status' => 404 ]
            );
        }

        $testType = $registry->get($type);

        // 3. Obtener los datos enviados
        $data = $request->get_json_params();

        // 4. Ejecutar cada validador configurado para este test
        $errors = [];
        foreach ($testType->getValidators() as $validator) {
            if (! $validator->validate($data)) {
                // merge de errores de validación
                $errors = array_merge($errors, $validator->getErrors());
            }
        }

        // 5. Si hay errores, devolver WP_Error con detalles
        if (! empty($errors)) {
            return new WP_Error(
                'cpp_validation_failed',
                __('Validation failed.', 'club-psychology-pro'),
                [
                    'status' => 422,
                    'errors' => $errors,
                ]
            );
        }

        // 6. Todo OK: continuar con el siguiente middleware/controlador
        return $next($request);
    }
}
