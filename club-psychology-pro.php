<?php
/**
 * Plugin Name: Club Psychology Pro
 * Plugin URI: https://aquea.org
 * Description: Sistema avanzado de tests psicológicos con gestión de usuarios, WhatsApp y reportes.
 * Version: 1.0.0
 * Author: Aquea Team
 * Author URI: https://aquea.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: club-psychology-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package ClubPsychologyPro
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CLUB_PSYCHOLOGY_PRO_VERSION', '1.0.0');
define('CLUB_PSYCHOLOGY_PRO_PLUGIN_FILE', __FILE__);
define('CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLUB_PSYCHOLOGY_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLUB_PSYCHOLOGY_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Verificar versión mínima de PHP
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            esc_html__('Club Psychology Pro requiere PHP 7.4 o superior. Tu versión actual es %s.', 'club-psychology-pro'),
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

// Verificar versión de WordPress
global $wp_version;
if (version_compare($wp_version, '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Club Psychology Pro requiere WordPress 5.0 o superior.', 'club-psychology-pro');
        echo '</p></div>';
    });
    return;
}

// Cargar Composer autoloader
$autoloader = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Fallback autoloader simple si Composer no está disponible
    spl_autoload_register(function ($class) {
        $prefix = 'ClubPsychologyPro\\';
        $base_dir = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'src/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Inicializar el plugin
add_action('plugins_loaded', function() {
    try {
        $plugin = \ClubPsychologyPro\Core\Plugin::getInstance();
        $plugin->init();
    } catch (\Exception $e) {
        error_log('Club Psychology Pro - Error de inicialización: ' . $e->getMessage());
        
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                esc_html__('Error inicializando Club Psychology Pro: %s', 'club-psychology-pro'),
                esc_html($e->getMessage())
            );
            echo '</p></div>';
        });
    }
});

// Hooks de activación/desactivación (deben estar en el archivo principal)
register_activation_hook(__FILE__, function() {
    require_once CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'includes/class-activator.php';
    \ClubPsychologyPro\Includes\Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'includes/class-deactivator.php';
    \ClubPsychologyPro\Includes\Deactivator::deactivate();
});