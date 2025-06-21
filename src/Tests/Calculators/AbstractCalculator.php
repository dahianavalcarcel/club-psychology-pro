<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Calculators;

use InvalidArgumentException;

/**
 * Class AbstractCalculator
 *
 * Clase base para todos los calculadores de tests psicológicos.
 */
abstract class AbstractCalculator implements CalculatorInterface
{
    /**
     * @var array<string,mixed> Configuración específica del test
     */
    protected array $config;

    /**
     * AbstractCalculator constructor.
     *
     * @param array<string,mixed> $config Parámetros de configuración del test
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Ejecuta el cálculo de puntuaciones e interpretaciones.
     *
     * @param array<string,mixed> $responses Respuestas crudas del usuario
     * @return array<string,mixed> Resultado del cálculo, por ejemplo:
     *                             [
     *                               'total_score' => 42,
     *                               'level'       => 'Moderado',
     *                               'details'     => [ ... ]
     *                             ]
     */
    abstract public function calculate(array $responses): array;

    /**
     * Valida la entrada antes de calcular.
     *
     * @param array<string,mixed> $responses
     * @return void
     * @throws InvalidArgumentException Si faltan respuestas o son inválidas
     */
    protected function validateResponses(array $responses): void
    {
        if (empty($responses)) {
            throw new InvalidArgumentException('No se proporcionaron respuestas para el cálculo.');
        }

        foreach ($responses as $key => $value) {
            if (!is_int($value) && !ctype_digit((string)$value)) {
                throw new InvalidArgumentException("Respuesta inválida para '{$key}': debe ser un número entero.");
            }
        }
    }

    /**
     * Sanea las respuestas convirtiendo todo a enteros.
     *
     * @param array<string,mixed> $responses
     * @return array<string,int>
     */
    protected function sanitizeResponses(array $responses): array
    {
        $sanitized = [];
        foreach ($responses as $key => $value) {
            $sanitized[$key] = (int) $value;
        }
        return $sanitized;
    }

    /**
     * Obtiene un valor de configuración, devolviendo un valor por defecto si no existe.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
