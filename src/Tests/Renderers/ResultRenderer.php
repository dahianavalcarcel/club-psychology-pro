<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Renderers\Templates;

use ClubPsychologyPro\Test\Interfaces\RendererInterface;
use InvalidArgumentException;

/**
 * ResultRenderer se encarga de cargar y renderizar la plantilla
 * de resultados para un tipo de test específico.
 */
class ResultRenderer extends AbstractRenderer implements RendererInterface
{
    /**
     * Constructor.
     *
     * @param string $testType Slug del test (e.g. "anger_rumination")
     * @throws InvalidArgumentException Si no se encuentra la plantilla.
     */
    public function __construct(string $testType)
    {
        $templatePath = $this->findTemplate($testType);
        parent::__construct($templatePath);
    }

    /**
     * Renderiza la plantilla de resultados pasándole el contexto.
     *
     * @param array $context Datos disponibles en la plantilla (p.ej. ['result' => $resultData, 'user' => $user]).
     * @return string HTML renderizado
     */
    public function render(array $context = []): string
    {
        ob_start();
        extract($context, EXTR_SKIP);
        include $this->templatePath;
        return ob_get_clean();
    }

    /**
     * Busca la plantilla de resultados en el tema activo o en el plugin.
     *
     * @param string $testType
     * @return string Ruta absoluta al archivo de plantilla
     * @throws InvalidArgumentException
     */
    private function findTemplate(string $testType): string
    {
        // 1) Override en el tema activo
        $themeDir  = get_stylesheet_directory();
        $themePath = "{$themeDir}/club-psychology-pro/templates/results/{$testType}.php";
        if (file_exists($themePath) && is_readable($themePath)) {
            return $themePath;
        }

        // 2) Fallback en el plugin
        // __DIR__ ≃ src/Test/Renderers/Templates
        $pluginRoot = dirname(__DIR__, 4);
        $pluginPath = "{$pluginRoot}/templates/results/{$testType}.php";
        if (file_exists($pluginPath) && is_readable($pluginPath)) {
            return $pluginPath;
        }

        throw new InvalidArgumentException(
            sprintf(
                'No se encontró la plantilla de resultados para "%s". Buscado en tema: %s, plugin: %s',
                $testType,
                $themePath,
                $pluginPath
            )
        );
    }
}
