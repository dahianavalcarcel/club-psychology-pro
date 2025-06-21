<?php
declare(strict_types=1);

namespace ClubPsychologyPro\Test\Renderers\Templates;

use ClubPsychologyPro\Test\Interfaces\RendererInterface;
use InvalidArgumentException;

/**
 * Clase base para renderizar plantillas de tests.
 */
abstract class AbstractRenderer implements RendererInterface
{
    /**
     * Ruta del archivo de plantilla a incluir.
     *
     * @var string
     */
    protected string $templatePath;

    /**
     * Datos que se pasarán a la plantilla.
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Constructor.
     *
     * @param string $templatePath Ruta absoluta o relativa a la plantilla.
     */
    public function __construct(string $templatePath)
    {
        $this->setTemplatePath($templatePath);
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $data = []): string
    {
        $this->context = $data;

        $templateFile = $this->resolveTemplate();
        if (!file_exists($templateFile) || !is_readable($templateFile)) {
            throw new InvalidArgumentException(sprintf(
                'No se puede cargar la plantilla en "%s".',
                $templateFile
            ));
        }

        // Hacer disponibles las variables en la plantilla
        extract($this->context, EXTR_SKIP);

        ob_start();
        include $templateFile;
        return (string) ob_get_clean();
    }

    /**
     * Establece la ruta de la plantilla.
     *
     * @param string $path
     * @return void
     */
    public function setTemplatePath(string $path): void
    {
        $this->templatePath = $path;
    }

    /**
     * Devuelve la ruta de la plantilla.
     *
     * @return string
     */
    protected function resolveTemplate(): string
    {
        return $this->templatePath;
    }
}
