<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Calculators;

use ClubPsychologyPro\Core\ConfigManager;
use InvalidArgumentException;

/**
 * Calculador para el PHQ-SADS (PHQ-15, GAD-7, PHQ-9, Pánico y Funcionalidad).
 */
class PhqSadsCalculator extends AbstractCalculator
{
    private ConfigManager $config;

    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }

    /**
     * @param array<string,int> $responses Respuestas indexadas por ID, e.g. 'PHQ15_1'=>2, 'GAD7_3'=>1, 'PANIC_A'=>1, 'FUNC'=>2, etc.
     * @return array{
     *   section_scores: array<string,int|array<string,int>>,
     *   diagnostics: string[],
     *   overall: array{level:string, description:string}
     * }
     * @throws InvalidArgumentException
     */
    public function calculate(array $responses): array
    {
        $tests = $this->config->get('tests');
        if (!isset($tests['phq_sads'])) {
            throw new InvalidArgumentException("Configuración de 'phq_sads' no encontrada.");
        }
        $cfg = $tests['phq_sads'];

        // 1) Calcular puntuaciones de secciones
        $phq15 = 0;
        for ($i = 1; $i <= 15; $i++) {
            $phq15 += $responses["PHQ15_$i"] ?? 0;
        }

        $gad7 = 0;
        for ($i = 1; $i <= 7; $i++) {
            $gad7 += $responses["GAD7_$i"] ?? 0;
        }

        $phq9 = 0;
        for ($i = 1; $i <= 9; $i++) {
            $phq9 += $responses["PHQ9_$i"] ?? 0;
        }

        $func = $responses['FUNC'] ?? 0;

        $panic = [
            'A' => $responses['PANIC_A'] ?? 0,
            'B' => $responses['PANIC_B'] ?? 0,
            'C' => $responses['PANIC_C'] ?? 0,
            'D' => $responses['PANIC_D'] ?? 0,
        ];

        $sectionScores = [
            'PHQ15' => $phq15,
            'GAD7'  => $gad7,
            'PHQ9'  => $phq9,
            'PANIC' => $panic,
            'FUNC'  => $func,
        ];

        // 2) Diagnósticos
        $diagnostics = [];

        // Major Depression
        $core = ($responses['PHQ9_1'] ?? 0) >= 2 || ($responses['PHQ9_2'] ?? 0) >= 2;
        if ($core) {
            $count = 0;
            for ($i = 1; $i <= 9; $i++) {
                $v = $responses["PHQ9_$i"] ?? 0;
                if ($i === 9) {
                    if ($v >= 1) $count++;
                } else {
                    if ($v >= 2) $count++;
                }
            }
            if ($count >= 5) {
                $diagnostics[] = 'Major_Depression';
            } elseif ($count >= 2) {
                $diagnostics[] = 'Other_Depression';
            }
        }

        // Panic Syndrome
        if (
            ($panic['A'] ?? 0) === 1 &&
            ($panic['B'] ?? 0) === 1 &&
            ($panic['C'] ?? 0) === 1 &&
            ($panic['D'] ?? 0) === 1
        ) {
            $diagnostics[] = 'Panic_Syndrome';
        }

        // Other Anxiety Syndrome
        if (($responses['GAD7_1'] ?? 0) >= 2) {
            $cnt = 0;
            for ($i = 1; $i <= 7; $i++) {
                if (($responses["GAD7_$i"] ?? 0) >= 2) {
                    $cnt++;
                }
            }
            if ($cnt >= 3) {
                $diagnostics[] = 'Other_Anxiety';
            }
        }

        // 3) Severidad general
        // Mapear cada sección a nivel 1-5 según puntos de corte del cfg['interpretation_sections']
        $sevMap = [];
        foreach (['PHQ15','GAD7','PHQ9'] as $sec) {
            if (!isset($cfg['interpretation_sections'][$sec])) {
                throw new InvalidArgumentException("Interpretación inexistente para sección {$sec}");
            }
            $val = $sectionScores[$sec];
            foreach ($cfg['interpretation_sections'][$sec] as $range) {
                if ($val >= $range['min'] && $val <= $range['max']) {
                    // Asignamos un número para comparar: mínimo=1, luego 2,3,4,5 si aplica
                    $sevMap[$sec] = $range['severity_level'] ?? 1;
                    break;
                }
            }
        }
        // Panic no aporta a severidad numérica
        // Funcionalidad no incluida en severidad general

        $maxLevel = max($sevMap);
        // Buscar la interpretación global en cfg['results']
        $overall = ['level' => 'Indeterminado', 'description' => ''];
        foreach ($cfg['results'] as $range) {
            if ($maxLevel >= $range['min_level'] && $maxLevel <= $range['max_level']) {
                $overall = [
                    'level'       => $range['level'],
                    'description' => $range['description'],
                ];
                break;
            }
        }

        return [
            'section_scores' => $sectionScores,
            'diagnostics'    => $diagnostics,
            'overall'        => $overall,
        ];
    }
}
