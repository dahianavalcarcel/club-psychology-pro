<?php
namespace ClubPsychologyPro\UI;

/**
 * Class AssetManager
 *
 * Registra y encola los assets (CSS/JS) para frontend y área de administración.
 */
class AssetManager
{
    /**
     * Inicializa los hooks para encolar estilos y scripts.
     */
    public static function register(): void
    {
        // Frontend
        add_action('wp_enqueue_scripts', [ self::class, 'enqueueFrontAssets' ]);

        // Administración
        add_action('admin_enqueue_scripts', [ self::class, 'enqueueAdminAssets' ]);
    }

    /**
     * Encola estilos y scripts para el frontend.
     */
    public static function enqueueFrontAssets(): void
    {
        // Definir versión a partir de la constante del plugin
        $version = defined('CLUB_PS_VERSION') ? CLUB_PS_VERSION : false;

        // Estilos principales
        wp_enqueue_style(
            'cpp-frontend-style',
            plugins_url('assets/css/frontend.css', CLUB_PS_PLUGIN_FILE),
            [],
            $version
        );

        // Scripts principales (cargar en footer)
        wp_enqueue_script(
            'cpp-frontend-script',
            plugins_url('assets/js/frontend.js', CLUB_PS_PLUGIN_FILE),
            [ 'jquery' ],
            $version,
            true
        );
    }

    /**
     * Encola estilos y scripts para el área de administración.
     *
     * @param string $hook Suffix de la página admin actual
     */
    public static function enqueueAdminAssets(string $hook): void
    {
        // Opcional: restringir a páginas específicas
        // if ( strpos($hook, 'psychology_pro') === false ) {
        //     return;
        // }

        $version = defined('CLUB_PS_VERSION') ? CLUB_PS_VERSION : false;

        // Estilos del admin
        wp_enqueue_style(
            'cpp-admin-style',
            plugins_url('assets/css/admin.css', CLUB_PS_PLUGIN_FILE),
            [],
            $version
        );

        // Scripts del admin
        wp_enqueue_script(
            'cpp-admin-script',
            plugins_url('assets/js/admin.js', CLUB_PS_PLUGIN_FILE),
            [ 'jquery' ],
            $version,
            true
        );
    }
}

// Registrar los assets al inicializar el plugin
AssetManager::register();
