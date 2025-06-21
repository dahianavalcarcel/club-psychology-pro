<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Interfaces;

/**
 * Interface que deben implementar todos los calculadores de tests.
 */
interface CalculatorInterface
{
    /**
     * Calcula los resultados del test a partir de las respuestas del usuario.
     *
     * @param array<string,int> $responses Array asociativo de respuestas,
     *                                      donde la clave es el ID de la pregunta
     *                                      y el valor es la puntuación seleccionada.
     *
     * @return array<string,mixed> Un array con los datos del resultado:
     *                             - 'totalScore' => int
     *                             - 'subscales'   => array<string,int>
     *                             - 'level'       => string
     *                             - 'interpretation' => string
     *                             - cualquier otro dato específico
     */
    public function calculate(array $responses): array;

    /**
     * Devuelve el identificador único del tipo de test para el que este calculador aplica.
     *
     * @return string Ejemplo: 'bronca', 'ansiedad', 'sugestion', 'depresion', 'attending_emotions', etc.
     */
    public function getTestType(): string;
}
