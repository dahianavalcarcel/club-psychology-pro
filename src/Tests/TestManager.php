<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test;

use ClubPsychologyPro\Users\PermissionManager;
use ClubPsychologyPro\Users\TestLimitManager;
use InvalidArgumentException;

/**
 * Class TestManager
 *
 * Registra y orquesta la ejecución de tests psicológicos.
 */
class TestManager
{
    private TestFactory $factory;
    private ResultManager $resultManager;
    private PermissionManager $permissionManager;
    private TestLimitManager $limitManager;

    /**
     * Tests registrados: clave => configuración
     *
     * @var array<string, array>
     */
    private array $registered = [];

    public function __construct(
        TestFactory $factory,
        ResultManager $resultManager,
        PermissionManager $permissionManager,
        TestLimitManager $limitManager
    ) {
        $this->factory           = $factory;
        $this->resultManager     = $resultManager;
        $this->permissionManager = $permissionManager;
        $this->limitManager      = $limitManager;
    }

    /**
     * Registra un nuevo tipo de test.
     *
     * @param string $key    Identificador único del test
     * @param array  $config Configuración específica del test
     */
    public function registerTest(string $key, array $config = []): void
    {
        $this->registered[$key] = $config;
    }

    /**
     * Devuelve la lista de tests registrados con su config.
     *
     * @return array<string, array>
     */
    public function getRegisteredTests(): array
    {
        return $this->registered;
    }

    /**
     * Ejecuta un test: valida permisos, límites, calcula puntaje y almacena resultado.
     *
     * @param string $key       Clave del test registrado
     * @param int    $userId    ID del usuario que realiza el test
     * @param array  $responses Respuestas del test (clave ítem => valor)
     *
     * @return array{result_id:int, data:array} Datos del resultado y ID del registro
     *
     * @throws InvalidArgumentException Si el test no está registrado
     * @throws \Exception              Si falla permiso o límite
     */
    public function runTest(string $key, int $userId, array $responses): array
    {
        if (!isset($this->registered[$key])) {
            throw new InvalidArgumentException("Test no registrado: {$key}");
        }

        // Verificar permisos de usuario
        if (! $this->permissionManager->canUserTakeTest($userId, $key)) {
            throw new \Exception("El usuario no tiene permiso para este test.");
        }

        // Verificar límites de uso
        if (! $this->limitManager->checkLimits($userId, $key)) {
            throw new \Exception("Se ha alcanzado el límite de ejecución para este test.");
        }

        $config = $this->registered[$key];

        // Crear instancia del test y calcular
        $test = $this->factory->create($key, $config);
        $data = $test->calculate($responses);

        // Almacenar resultado
        $resultId = $this->resultManager
            ->storeResult($userId, $key, $responses, $data);

        // Incrementar contador de uso
        $this->limitManager->incrementUsage($userId, $key);

        return [
            'result_id' => $resultId,
            'data'      => $data,
        ];
    }
}
