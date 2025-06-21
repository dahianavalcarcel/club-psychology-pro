<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Calculators;

use InvalidArgumentException;

/**
 * Registry para los calculadores de tests.
 */
class CalculatorRegistry
{
    /**
     * @var array<string, AbstractCalculator>
     */
    private array $calculators = [];

    /**
     * Registra un calculador bajo una clave única.
     *
     * @param string             $key        Identificador del tipo de test (por ejemplo, 'bigfive', 'phq_sads', etc.).
     * @param AbstractCalculator $calculator Instancia del calculador.
     */
    public function register(string $key, AbstractCalculator $calculator): void
    {
        $this->calculators[$key] = $calculator;
    }

    /**
     * Comprueba si existe un calculador registrado para la clave dada.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->calculators[$key]);
    }

    /**
     * Devuelve el calculador registrado para la clave dada.
     *
     * @param string $key
     * @return AbstractCalculator
     * @throws InvalidArgumentException Si no existe un calculador para esa clave.
     */
    public function get(string $key): AbstractCalculator
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException(
                sprintf("No se encontró ningún calculador registrado para la clave '%s'.", $key)
            );
        }
        return $this->calculators[$key];
    }

    /**
     * Devuelve todos los calculadores registrados.
     *
     * @return AbstractCalculator[]
     */
    public function getAll(): array
    {
        return $this->calculators;
    }
}
