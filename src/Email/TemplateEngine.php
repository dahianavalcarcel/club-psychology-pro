<?php
namespace ClubPsychologyPro\Email;

use WP_Error;

/**
 * Class TemplateEngine
 *
 * Motor simple de plantillas para correos: reemplaza {{variable}} por su valor.
 */
class TemplateEngine
{
    /**
     * Directorio base de plantillas (sin la extensión .html.php)
     *
     * @var string
     */
    protected string $templatesDir;

    /**
     * TemplateEngine constructor.
     *
     * @param string $templatesDir Ruta absoluta al directorio de plantillas
     */
    public function __construct(string $templatesDir)
    {
        $this->templatesDir = rtrim($templatesDir, '/');
    }

    /**
     * Renderiza una plantilla HTML con los datos provistos.
     *
     * @param string $template Nombre de la plantilla (sin extensión).
     * @param array  $context  Datos para interpolar en la plantilla.
     *
     * @return string|WP_Error HTML resultante o WP_Error si falla.
     */
    public function render(string $template, array $context = [])
    {
        $file = "{$this->templatesDir}/{$template}.html.php";
        if (! file_exists($file) || ! is_readable($file)) {
            return new WP_Error(
                'template_not_found',
                sprintf(__('Plantilla "%s" no encontrada en %s', 'club-psychology-pro'), $template, $this->templatesDir)
            );
        }

        $content = file_get_contents($file);
        if (false === $content) {
            return new WP_Error(
                'template_read_error',
                sprintf(__('No se pudo leer la plantilla "%s"', 'club-psychology-pro'), $template)
            );
        }

        // Reemplazar las llaves dobles por sus respectivos valores
        $rendered = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function ($matches) use ($context) {
                $key = $matches[1];
                return isset($context[$key]) ? esc_html((string) $context[$key]) : $matches[0];
            },
            $content
        );

        return $rendered;
    }
}
