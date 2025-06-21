<?php

namespace ClubPsychologyPro\UI\Shortcode;

use ClubPsychologyPro\UI\Shortcode\UserPanelShortcode;
use ClubPsychologyPro\UI\Shortcode\ResultShortcode;
use ClubPsychologyPro\UI\Shortcode\ResultsPanelShortcode;

/**
 * Class ShortcodeManager
 *
 * Registra y gestiona todos los shortcodes del plugin.
 */
class ShortcodeManager
{
    /**
     * Registra todos los shortcodes del sistema.
     */
    public static function register(): void
    {
        // Panel principal de usuario
        add_shortcode('cpp_user_panel', [UserPanelShortcode::class, 'render']);

        // Shortcode para mostrar un resultado específico
        add_shortcode('cpp_result', [ResultShortcode::class, 'render']);

        // Panel de todos los resultados del usuario
        add_shortcode('cpp_results_panel', [ResultsPanelShortcode::class, 'render']);

        // Aquí puedes añadir más shortcodes según sea necesario
        // add_shortcode('otro_shortcode', [OtraClaseShortcode::class, 'render']);
    }
}
