<?php

namespace ClubPsychologyPro\UI\Shortcode;

use ClubPsychologyPro\Tests\TestManager;
use ClubPsychologyPro\Core\TemplateLoader;

class ResultShortcode
{
    /**
     * Registra el shortcode [cpp_result id=""]
     */
    public static function register(): void
    {
        add_shortcode('cpp_result', [self::class, 'render']);
    }

    /**
     * Renderiza el resultado de un test específico
     *
     * @param array $atts Atributos del shortcode (id)
     * @return string HTML del resultado
     */
    public static function render(array $atts): string
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'cpp_result');

        $result_id = absint($atts['id']);
        if (! $result_id) {
            return '<div class="cpp-error">ID de resultado no válido.</div>';
        }

        // Obtenemos datos del resultado
        $result = TestManager::getResultById($result_id);
        if (! $result) {
            return '<div class="cpp-error">Resultado no encontrado.</div>';
        }

        // Pasamos datos a la plantilla
        $data = [
            'result' => $result,
        ];

        // Cargamos template: templates/shortcodes/result.php
        return TemplateLoader::render('shortcodes/result', $data);
    }
}
