<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Calculators;

use InvalidArgumentException;

/**
 * Class BigFiveCalculator
 *
 * Calculador específico para el test BigFive (B5-AI).
 */
class BigFiveCalculator extends AbstractCalculator
{
    /**
     * Ejecuta el cálculo de puntuaciones de facetas y dominios para BigFive.
     *
     * @param array<string,int> $responses Respuestas crudas del usuario (ítems → valor)
     * @return array<string,mixed> Resultado con:
     *   - facets: array de puntuaciones por faceta
     *   - domains: array de puntuaciones por dominio
     *   - interpretation: niveles interpretativos por dominio
     *   - total_score: suma de puntuaciones de todos los dominios
     *
     * @throws InvalidArgumentException Si faltan respuestas o la configuración es inválida
     */
    public function calculate(array $responses): array
    {
        // 1. Validar y sanear respuestas
        $this->validateResponses($responses);
        $sanitized = $this->sanitizeResponses($responses);

        // 2. Cargar configuración desde Config/tests.php
        /** @var array<string,string[]> $facetsConfig */
        $facetsConfig = $this->getConfig('facets', []);
        /** @var array<string,string[]> $domainsConfig */
        $domainsConfig = $this->getConfig('domains', []);
        /** @var array<string,array{min:float,max:float,level:string}[]> $interpretations */
        $interpretations = $this->getConfig('interpretations', []);

        if (empty($facetsConfig) || empty($domainsConfig)) {
            throw new InvalidArgumentException('Configuración de facetas o dominios no definida para BigFive.');
        }

        // 3. Calcular puntuación por faceta
        $facetsScores = [];
        foreach ($facetsConfig as $facetName => $itemIds) {
            $sum = 0;
            foreach ($itemIds as $itemId) {
                if (!isset($sanitized[$itemId])) {
                    throw new InvalidArgumentException("Falta respuesta para el ítem '{$itemId}' en la faceta '{$facetName}'.");
                }
                $sum += $sanitized[$itemId];
            }
            $facetsScores[$facetName] = $sum;
        }

        // 4. Calcular puntuación por dominio (media de sus facetas)
        $domainsScores = [];
        foreach ($domainsConfig as $domainName => $facetNames) {
            $subtotal = 0;
            $count = 0;
            foreach ($facetNames as $facetName) {
                if (!isset($facetsScores[$facetName])) {
                    throw new InvalidArgumentException("Faceta '{$facetName}' no encontrada al procesar dominio '{$domainName}'.");
                }
                $subtotal += $facetsScores[$facetName];
                $count++;
            }
            $domainsScores[$domainName] = $count > 0 ? $subtotal / $count : 0.0;
        }

        // 5. Interpretar cada dominio según rangos definidos
        $domainsInterpretation = [];
        foreach ($domainsScores as $domainName => $score) {
            $domainsInterpretation[$domainName] = $this->interpretDomainScore($domainName, $score, $interpretations);
        }

        // 6. Puntuación total (suma de todos los dominios)
        $totalScore = array_sum($domainsScores);

        return [
            'facets'        => $facetsScores,
            'domains'       => $domainsScores,
            'interpretation'=> $domainsInterpretation,
            'total_score'   => $totalScore,
        ];
    }

    /**
     * Interpreta la puntuación de un dominio según la configuración de rangos.
     *
     * @param string $domainName
     * @param float  $score
     * @param array<string,array{min:float,max:float,level:string}[]> $interpretations
     * @return string Nivel interpretativo (por ejemplo, 'Bajo', 'Moderado', 'Alto')
     */
    protected function interpretDomainScore(string $domainName, float $score, array $interpretations): string
    {
        if (!isset($interpretations[$domainName])) {
            return '';
        }
        foreach ($interpretations[$domainName] as $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $range['level'];
            }
        }
        return '';
    }
}
