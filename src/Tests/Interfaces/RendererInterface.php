<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Interfaces;

/**
 * Interface para renderizar formularios y resultados de tests.
 */
interface RendererInterface
{
    /**
     * Devuelve el identificador único del tipo de test que este renderer maneja.
     *
     * @return string Ejemplo: 'bronca', 'ansiedad', 'sugestion', 'depresion', 'attending_emotions', etc.
     */
    public function getTestType(): string;

    /**
     * Genera el HTML del formulario de un test.
     *
     * @param int   $testId     ID interno del test.
     * @param array $testConfig Configuración completa del test (preguntas, opciones, etc.).
     * @param array $userData   Datos adicionales del usuario (p. ej. respuestas previas, meta).
     *
     * @return string HTML listo para imprimir en la página.
     */
    public function renderForm(int $testId, array $testConfig, array $userData = []): string;

    /**
     * Genera el HTML de la vista de resultados de un test.
     *
     * @param int   $resultId   ID del resultado (post meta).
     * @param array $resultData Datos calculados del resultado (puntuaciones, interpretación, etc.).
     *
     * @return string HTML listo para imprimir en la página.
     */
    public function renderResult(int $resultId, array $resultData): string;
}
