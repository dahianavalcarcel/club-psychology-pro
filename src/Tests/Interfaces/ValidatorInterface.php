<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Interfaces;

/**
 * Interface para validadores de respuestas de test.
 */
interface ValidatorInterface
{
    /**
     * Valida un conjunto de datos (respuestas) para un test.
     *
     * @param array $data Clave => valor de respuestas o datos de entrada.
     * @return bool       True si los datos son válidos, false en caso contrario.
     */
    public function validate(array $data): bool;

    /**
     * Si la validación falla, retorna una lista de errores descriptivos.
     *
     * @return string[] Array de mensajes de error.
     */
    public function getErrors(): array;
}
