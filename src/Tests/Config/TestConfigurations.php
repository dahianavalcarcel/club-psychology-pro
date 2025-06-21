<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Config;

use ClubPsychologyPro\Core\ConfigManager;

/**
 * Provee acceso centralizado a las configuraciones de todos los tests.
 */
class TestConfigurations
{
    private ConfigManager $configManager;
    private array $testsConfig;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
        $this->testsConfig = (array) $this->configManager->get('tests');
    }

    /**
     * Retorna la configuración completa de un test por su clave.
     *
     * @param string $testType Identificador del test (p.ej. 'bronca', 'ansiedad', 'sugestion', 'depresion', 'attending_emotions', etc.)
     * @return array<string,mixed>|null Configuración del test o null si no existe.
     */
    public function get(string $testType): ?array
    {
        return $this->testsConfig[$testType] ?? null;
    }

    /**
     * Retorna todas las configuraciones de tests disponibles.
     *
     * @return array<string,array<string,mixed>> Matriz de configuraciones indexada por tipo de test.
     */
    public function all(): array
    {
        return $this->testsConfig;
    }

    /**
     * Verifica si existe configuración para un tipo de test dado.
     *
     * @param string $testType
     * @return bool
     */
    public function has(string $testType): bool
    {
        return array_key_exists($testType, $this->testsConfig);
    }
}
