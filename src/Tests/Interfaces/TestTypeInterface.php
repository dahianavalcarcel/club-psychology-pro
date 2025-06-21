<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Interfaces;

/**
 * Interface para definir un tipo de test.
 */
interface TestTypeInterface
{
    /**
     * Devuelve el identificador único del test.
     *
     * @return string Ejemplo: 'anger_rumination', 'phq_sads', 'miss', 'who5', 'ipip_ate', etc.
     */
    public function getType(): string;

    /**
     * Retorna la configuración completa del test (preguntas, subescalas, rangos, etc.).
     *
     * @return array Configuración según TestConfigurations
     */
    public function getConfig(): array;

    /**
     * Valida las respuestas enviadas por el usuario.
     *
     * @param array $responses Clave => valor de respuestas
     * @return bool             True si es válido, false si falta algo o hay errores
     * @throws \InvalidArgumentException En caso de respuestas mal formadas
     */
    public function validateResponses(array $responses): bool;

    /**
     * Procesa y calcula el resultado del test.
     *
     * @param array $responses Clave => valor de respuestas
     * @return array           Datos calculados: puntajes, subescalas, nivel, interpretación, etc.
     */
    public function processResponses(array $responses): array;

    /**
     * Devuelve el nombre de la clase del renderer para este test.
     *
     * @return string Nombre de la clase que implementa RendererInterface
     */
    public function getRendererClass(): string;
}
