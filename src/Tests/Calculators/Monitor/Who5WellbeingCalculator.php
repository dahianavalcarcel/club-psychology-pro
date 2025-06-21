<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Calculators;

use ClubPsychologyPro\Core\ConfigManager;
use InvalidArgumentException;

/**
 * Calculador para el Test de Bienestar WHO-5.
 *
 * Suma las 5 respuestas (0–5), multiplica por 4 y retorna puntuación 0–100
 * junto con nivel y descripción según rangos de configuración.
 */
class Who5WellbeingCalculator extends AbstractCalculator
{
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string,int> $responses Respuestas indexadas 'd1'..'d5'
     * @return array{
     *   raw_score: int,
     *   total_score: int,
     *   interpretation: array{level: string, description: string}
     * }
     * @throws InvalidArgumentException
     */
    public function calculate(array $responses): array
    {
        // Obtener configuración de tests
        $tests = $this->config->get('tests');
        if (!isset($tests['depresion'])) {
            throw new InvalidArgumentException("Configuración 'depresion' (WHO-5) no encontrada.");
        }
        $cfg = $tests['depresion'];

        // Validar y sumar 5 ítems
        $raw = 0;
        for ($i = 1; $i <= 5; $i++) {
            $key = 'd' . $i;
            if (!isset($responses[$key])) {
                throw new InvalidArgumentException("Respuesta para '{$key}' faltante.");
            }
            $val = (int)$responses[$key];
            if ($val < 0 || $val > 5) {
                throw new InvalidArgumentException("Valor inválido para '{$key}': {$val}");
            }
            $raw += $val;
        }

        // Aplicar multiplicador (por defecto 4)
        $mult = (int)($cfg['sistema_puntuacion']['multiplicador'] ?? 4);
        $total = $raw * $mult;

        // Determinar interpretación según rangos
        $interp = null;
        foreach ($cfg['resultados'] as $range) {
            if ($total >= $range['min'] && $total <= $range['max']) {
                $interp = [
                    'level'       => $range['nivel'],
                    'description' => $range['descripcion'],
                ];
                break;
            }
        }
        if ($interp === null) {
            throw new InvalidArgumentException("No se encontró interpretación para puntuación {$total}.");
        }

        return [
            'raw_score'      => $raw,
            'total_score'    => $total,
            'interpretation' => $interp,
        ];
    }
}
