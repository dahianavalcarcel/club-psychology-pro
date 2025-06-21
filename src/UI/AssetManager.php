<?php
/**
 * Asset Manager corregido para Club Psychology Pro
 */

namespace ClubPsychologyPro\UI;

class AssetManager
{
    /**
     * Inicializa los hooks para encolar estilos y scripts.
     */
    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontAssets']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);
    }

    /**
     * Encola estilos y scripts para el frontend.
     */
    public static function enqueuePublicAssets(): void
    {
        $version = CLUB_PSYCHOLOGY_PRO_VERSION ?? '1.0.0';
        $asset_url = CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/dist/';

        // Verificar si los archivos existen antes de encolarlos
        $css_file = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/css/frontend.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'cpp-frontend-style',
                $asset_url . 'css/frontend.css',
                [],
                $version
            );
        }

        $js_file = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/js/frontend.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'cpp-frontend-script',
                $asset_url . 'js/frontend.js',
                ['jquery'],
                $version,
                true
            );

            // Localizar script con datos necesarios
            wp_localize_script('cpp-frontend-script', 'cppData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cpp_nonce'),
                'restUrl' => rest_url('cpp/v1/'),
                'siteUrl' => site_url(),
                'strings' => [
                    'loading' => __('Cargando...', 'club-psychology-pro'),
                    'error' => __('Error', 'club-psychology-pro'),
                    'success' => __('Éxito', 'club-psychology-pro'),
                ]
            ]);
        }
    }

    /**
     * Encola estilos y scripts para el área de administración.
     */
    public static function enqueueAdminAssets(string $hook): void
    {
        // Solo cargar en páginas del plugin
        $cpp_pages = [
            'toplevel_page_cpp-dashboard',
            'psychology-pro_page_cpp-settings',
            'psychology-pro_page_cpp-tests',
            'psychology-pro_page_cpp-users',
        ];

        if (!in_array($hook, $cpp_pages)) {
            return;
        }

        $version = CLUB_PSYCHOLOGY_PRO_VERSION ?? '1.0.0';
        $asset_url = CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/dist/';

        // CSS Admin
        $admin_css = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/css/admin.css';
        if (file_exists($admin_css)) {
            wp_enqueue_style(
                'cpp-admin-style',
                $asset_url . 'css/admin.css',
                [],
                $version
            );
        }

        // JS Admin
        $admin_js = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/js/admin.js';
        if (file_exists($admin_js)) {
            wp_enqueue_script(
                'cpp-admin-script',
                $asset_url . 'js/admin.js',
                ['jquery'],
                $version,
                true
            );

            wp_localize_script('cpp-admin-script', 'cppAdminData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cpp_admin_nonce'),
                'restUrl' => rest_url('cpp/v1/'),
            ]);
        }
    }

    /**
     * Verificar y crear estructura de assets si no existe
     */
    public static function ensureAssetStructure(): void
    {
        $base_dir = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/';
        $dist_dir = $base_dir . 'dist/';
        $css_dir = $dist_dir . 'css/';
        $js_dir = $dist_dir . 'js/';

        // Crear directorios si no existen
        if (!file_exists($dist_dir)) {
            wp_mkdir_p($dist_dir);
        }
        if (!file_exists($css_dir)) {
            wp_mkdir_p($css_dir);
        }
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }

        // Crear archivos básicos si no existen
        self::createBasicAssets($css_dir, $js_dir);
    }

    /**
     * Crear archivos básicos de CSS y JS
     */
    private static function createBasicAssets(string $css_dir, string $js_dir): void
    {
        // CSS Frontend básico
        $frontend_css = $css_dir . 'frontend.css';
        if (!file_exists($frontend_css)) {
            $basic_css = "/* Club Psychology Pro - Frontend Styles */\n";
            $basic_css .= ".cpp-test-form { margin: 20px 0; }\n";
            $basic_css .= ".cpp-result-viewer { padding: 20px; }\n";
            $basic_css .= ".cpp-user-panel { background: #f9f9f9; padding: 15px; }\n";
            file_put_contents($frontend_css, $basic_css);
        }

        // CSS Admin básico
        $admin_css = $css_dir . 'admin.css';
        if (!file_exists($admin_css)) {
            $admin_styles = "/* Club Psychology Pro - Admin Styles */\n";
            $admin_styles .= ".cpp-admin-panel { margin: 20px 0; }\n";
            $admin_styles .= ".cpp-stats-card { background: white; padding: 15px; margin: 10px; }\n";
            file_put_contents($admin_css, $admin_styles);
        }

        // JS Frontend básico
        $frontend_js = $js_dir . 'frontend.js';
        if (!file_exists($frontend_js)) {
            $basic_js = "// Club Psychology Pro - Frontend Scripts\n";
            $basic_js .= "document.addEventListener('DOMContentLoaded', function() {\n";
            $basic_js .= "    console.log('CPP Frontend loaded');\n";
            $basic_js .= "});\n";
            file_put_contents($frontend_js, $basic_js);
        }

        // JS Admin básico
        $admin_js = $js_dir . 'admin.js';
        if (!file_exists($admin_js)) {
            $admin_script = "// Club Psychology Pro - Admin Scripts\n";
            $admin_script .= "document.addEventListener('DOMContentLoaded', function() {\n";
            $admin_script .= "    console.log('CPP Admin loaded');\n";
            $admin_script .= "});\n";
            file_put_contents($admin_js, $admin_script);
        }
    }
}