<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Calculators;

use ClubPsychologyPro\Core\ConfigManager;
use InvalidArgumentException;

/**
 * Calculador para la Escala de Sugestionabilidad (MISS / SSS).
 */
class SuggestibilityCalculator extends AbstractCalculator
{
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * Calcula la puntuación total de la escala de sugestionabilidad y devuelve
     * su interpretación.
     *
     * @param array<string,int> $responses Respuestas indexadas por ID ('s1'..'s21').
     * @return array{
     *   total_score: int,
     *   interpretation: array{level: string, description: string}
     * }
     * @throws InvalidArgumentException Si falta configuración o respuestas inválidas.
     */
    public function calculate(array $responses): array
    {
        // Obtener la configuración de tests
        $tests = $this->config->get('tests');
        if (!isset($tests['suggestibility'])) {
            throw new InvalidArgumentException("Configuración 'suggestibility' no encontrada.");
        }
        $cfg = $tests['suggestibility'];

        // Sumar las 21 respuestas (cada una entre 1 y 5)
        $total = 0;
        for ($i = 1; $i <= 21; $i++) {
            $key = 's' . $i;
            if (!isset($responses[$key])) {
                throw new InvalidArgumentException("Respuesta para '{$key}' faltante.");
            }
            $val = (int)$responses[$key];
            if ($val < 1 || $val > 5) {
                throw new InvalidArgumentException("Valor de respuesta inválido para '{$key}': {$val}");
            }
            $total += $val;
        }

        // Determinar interpretación según rangos definidos en config
        $interpretation = null;
        foreach ($cfg['results'] as $range) {
            if ($total >= $range['min'] && $total <= $range['max']) {
                $interpretation = [
                    'level'       => $range['nivel'],
                    'description' => $range['descripcion'],
                ];
                break;
            }
        }

        if ($interpretation === null) {
            throw new InvalidArgumentException("No se pudo determinar interpretación para la puntuación {$total}.");
        }

        return [
            'total_score'    => $total,
            'interpretation' => $interpretation,
        ];
    }
}
