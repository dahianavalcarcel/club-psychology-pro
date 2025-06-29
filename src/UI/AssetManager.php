<?php
namespace ClubPsychologyPro\UI;

use ClubPsychologyPro\Core\Plugin;

/**
 * Class AssetManager
 *
 * Se encarga de registrar y encolar todos los scripts y estilos
 * públicos y de administración.
 */
class AssetManager
{
    /**
     * Registra todos los hooks necesarios para encolar assets.
     */
    public static function init(): void
    {
        // Frontend
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueueFrontendAssets' ] );

        // Admin
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueAdminAssets' ] );
    }

    /**
     * Asegura que la carpeta de dist existe.
     */
    public static function ensureAssetStructure(): void
    {
        $dist = CPP_PLUGIN_DIR . 'assets/dist/';
        if ( ! file_exists( $dist . 'css' ) || ! file_exists( $dist . 'js' ) ) {
            wp_mkdir_p( $dist . 'css' );
            wp_mkdir_p( $dist . 'js' );
        }
    }

    /**
     * Encola los estilos y scripts para la parte pública.
     */
    public static function enqueueFrontendAssets(): void
    {
        $version  = CPP_VERSION;
        $dist_url = CPP_PLUGIN_URL . 'assets/dist/';

        // CSS público
        $css_file = 'css/frontend.css';
        if ( file_exists( CPP_PLUGIN_DIR . "assets/dist/{$css_file}" ) ) {
            wp_enqueue_style(
                'cpp-frontend',
                "{$dist_url}{$css_file}",
                [],
                $version
            );
        }

        // JS público (ahora apuntando a frontend.js)
        $js_file = 'js/frontend.js';
        if ( file_exists( CPP_PLUGIN_DIR . "assets/dist/{$js_file}" ) ) {
            wp_enqueue_script(
                'cpp-frontend',
                "{$dist_url}{$js_file}",
                [ 'jquery' ],
                $version,
                true
            );

            // Pasamos variables a frontend.js
            wp_localize_script(
                'cpp-frontend',
                'cppData',
                [
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'restUrl' => rest_url( 'cpp/v1/' ),
                    'nonce'   => wp_create_nonce( 'cpp_nonce' ),
                    'config'  => [
                        'debug'  => WP_DEBUG,
                        'locale' => get_locale(),
                    ],
                ]
            );
        }
    }

    /**
     * Encola los estilos y scripts para el área de administración.
     *
     * @param string $hook_suffix
     */
    public static function enqueueAdminAssets( string $hook_suffix ): void
    {
        // Solo en las páginas propias del plugin
        $allowed = [
            'toplevel_page_cpp-dashboard',
            'cpp_page_cpp-tests',
            'cpp_page_cpp-settings',
            'cpp_page_cpp-test_management',
            'cpp_page_cpp-user_management',
        ];
        if ( ! in_array( $hook_suffix, $allowed, true ) ) {
            return;
        }

        $version  = CPP_VERSION;
        $dist_url = CPP_PLUGIN_URL . 'assets/dist/';

        // CSS admin
        $css_file = 'css/admin.css';
        if ( file_exists( CPP_PLUGIN_DIR . "assets/dist/{$css_file}" ) ) {
            wp_enqueue_style(
                'cpp-admin',
                "{$dist_url}{$css_file}",
                [],
                $version
            );
        }

        // JS admin
        $js_file = 'js/admin.js';
        if ( file_exists( CPP_PLUGIN_DIR . "assets/dist/{$js_file}" ) ) {
            wp_enqueue_script(
                'cpp-admin',
                "{$dist_url}{$js_file}",
                [ 'jquery', 'wp-api' ],
                $version,
                true
            );
        }
    }
}

// Inicializar hooks
AssetManager::init();
