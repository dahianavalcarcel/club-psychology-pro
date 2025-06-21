<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Renderers\Templates;

use ClubPsychologyPro\Test\Interfaces\RendererInterface;
use InvalidArgumentException;

/**
 * FormRenderer se encarga de cargar y renderizar la plantilla
 * de formulario para un tipo de test específico.
 */
class FormRenderer extends AbstractRenderer implements RendererInterface
{
    /**
     * Constructor.
     *
     * @param string $testType Slug del test (e.g. "anger_rumination").
     * @throws InvalidArgumentException Si no se encuentra la plantilla.
     */
    public function __construct(string $testType)
    {
        $template = $this->findTemplate($testType);
        parent::__construct($template);
    }

    /**
     * Busca la plantilla de formulario en el tema o en el plugin.
     *
     * @param string $testType
     * @return string Ruta absoluta al archivo de plantilla.
     * @throws InvalidArgumentException
     */
    private function findTemplate(string $testType): string
    {
        // 1) Intentar override en el tema activo
        $themePath = get_stylesheet_directory() 
            . "/club-psychology-pro/templates/tests/{$testType}.php";
        if (file_exists($themePath) && is_readable($themePath)) {
            return $themePath;
        }

        // 2) Fallback a la plantilla incluida en el plugin
        // __DIR__ = src/Test/Renderers/Templates
        $pluginRoot = dirname(__DIR__, 4); // cuatro niveles hasta la raíz del plugin
        $pluginPath = $pluginRoot 
            . "/templates/tests/{$testType}.php";
        if (file_exists($pluginPath) && is_readable($pluginPath)) {
            return $pluginPath;
        }

        throw new InvalidArgumentException(
            sprintf(
                'No se encontró la plantilla de formulario para el test "%s". ' .
                'Buscado en tema: %s y en plugin: %s',
                $testType,
                $themePath,
                $pluginPath
            )
        );
    }
}
