<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Calculators;

use ClubPsychologyPro\Core\ConfigManager;
use InvalidArgumentException;

/**
 * Calculador para el test de Cohesión de Equipo (GEQ).
 */
class TeamCohesionCalculator extends AbstractCalculator
{
    private ConfigManager $config;

    /**
     * @param ConfigManager $config El administrador de configuración.
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * Calcula la puntuación total, por subescalas y devuelve una interpretación.
     *
     * @param array<string,int> $responses Respuestas del usuario, índice por ID de ítem.
     * @return array{
     *   total_score: int,
     *   subscales: array<string,int>,
     *   interpretation: array<string,mixed>|null
     * }
     * @throws InvalidArgumentException Si no se encuentra la configuración de test.
     */
    public function calculate(array $responses): array
    {
        $allTests = $this->config->get('tests');
        if (!isset($allTests['team_cohesion'])) {
            throw new InvalidArgumentException("Configuración de 'team_cohesion' no encontrada.");
        }

        $testConfig = $allTests['team_cohesion'];

        // Subescalas definidas en config/tests.php
        $subscalesConfig = $testConfig['subscales'] ?? [];
        if (empty($subscalesConfig)) {
            throw new InvalidArgumentException("No hay subescalas definidas para 'team_cohesion'.");
        }

        $subscaleScores = [];
        $totalScore     = 0;

        // Sumar puntuaciones por subescala
        foreach ($subscalesConfig as $subscaleKey => $itemIds) {
            $sum = 0;
            foreach ($itemIds as $itemId) {
                // Si falta alguna respuesta, asumimos 0
                $sum += $responses[$itemId] ?? 0;
            }
            $subscaleScores[$subscaleKey] = $sum;
            $totalScore += $sum;
        }

        // Interpretación según rangos en config/tests.php
        $interpretation = null;
        foreach ($testConfig['results'] ?? [] as $range) {
            if ($totalScore >= $range['min'] && $totalScore <= $range['max']) {
                $interpretation = $range;
                break;
            }
        }

        return [
            'total_score'   => $totalScore,
            'subscales'     => $subscaleScores,
            'interpretation'=> $interpretation,
        ];
    }
}
