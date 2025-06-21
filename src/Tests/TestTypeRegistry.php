<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test;

use InvalidArgumentException;

/**
 * Class TestTypeRegistry
 *
 * Lleva un registro de las clases correspondientes a cada tipo de test.
 */
class TestTypeRegistry
{
    /**
     * @var array<string, string> Mapa test key => FQCN de la clase
     */
    private array $types = [];

    /**
     * Registra una clase de test bajo una clave determinada.
     *
     * @param string $key        Identificador único del test
     * @param string $className  Nombre completo de la clase que implementa TestInterface
     */
    public function register(string $key, string $className): void
    {
        $this->types[$key] = $className;
    }

    /**
     * Verifica si existe un test registrado bajo la clave dada.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /**
     * Obtiene la FQCN de la clase de test registrada para la clave dada.
     *
     * @param string $key
     * @return string
     *
     * @throws InvalidArgumentException Si no existe registro para la clave
     */
    public function get(string $key): string
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Test type not registered: {$key}");
        }
        return $this->types[$key];
    }

    /**
     * Devuelve todas las claves de test registradas y sus clases asociadas.
     *
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->types;
    }
}
