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

// Cargar autoloader
$autoloader = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Fallback autoloader
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

/**
 * Clase principal del plugin
 */
class ClubPsychologyPro_Main {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Hooks de activación/desactivación
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Inicialización principal
        add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
        add_action('init', [$this, 'onInit']);
        
        // Asegurar estructura de assets
        add_action('admin_init', [$this, 'ensureAssets']);
    }
    
    public function activate() {
        // Crear estructura de assets
        $this->createAssetStructure();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Limpiar rewrite rules
        flush_rewrite_rules();
    }
    
    public function onPluginsLoaded() {
        // Cargar textdomain
        load_plugin_textdomain(
            'club-psychology-pro',
            false,
            dirname(CLUB_PSYCHOLOGY_PRO_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function onInit() {
        // Registrar assets
        $this->registerAssets();
        
        // Registrar shortcodes básicos
        $this->registerShortcodes();
        
        // Registrar AJAX handlers
        $this->registerAjaxHandlers();
    }
    
    public function ensureAssets() {
        if (class_exists('ClubPsychologyPro\\UI\\AssetManager')) {
            \ClubPsychologyPro\UI\AssetManager::ensureAssetStructure();
        }
    }
    
    private function registerAssets() {
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }
    
    public function enqueuePublicAssets() {
        $version = CLUB_PSYCHOLOGY_PRO_VERSION;
        $asset_url = CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/dist/';
        
        // CSS Frontend
        $css_file = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/css/frontend.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'cpp-frontend-style',
                $asset_url . 'css/frontend.css',
                [],
                $version
            );
        }
        
        // JS Frontend
        $js_file = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/js/frontend.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'cpp-frontend-script',
                $asset_url . 'js/frontend.js',
                ['jquery'],
                $version,
                true
            );
            
            wp_localize_script('cpp-frontend-script', 'cppData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cpp_nonce'),
                'restUrl' => rest_url('cpp/v1/'),
                'siteUrl' => site_url(),
            ]);
        }
    }
    
    public function enqueueAdminAssets($hook) {
        // Solo en páginas del plugin
        if (strpos($hook, 'psychology-pro') === false && strpos($hook, 'cpp') === false) {
            return;
        }
        
        $version = CLUB_PSYCHOLOGY_PRO_VERSION;
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
        }
    }
    
    private function registerShortcodes() {
        // Shortcode básico para panel de usuario
        add_shortcode('cpp_user_panel', [$this, 'renderUserPanel']);
        add_shortcode('cpp_test_form', [$this, 'renderTestForm']);
        add_shortcode('cpp_result', [$this, 'renderResult']);
    }
    
    public function renderUserPanel($atts) {
        $atts = shortcode_atts([
            'limit' => 10,
        ], $atts, 'cpp_user_panel');
        
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tu panel.', 'club-psychology-pro') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="cpp-user-panel">
            <h3><?php _e('Mi Panel de Tests', 'club-psychology-pro'); ?></h3>
            <p><?php _e('Aquí puedes ver tus tests y resultados.', 'club-psychology-pro'); ?></p>
            
            <div class="cpp-test-actions">
                <button type="button" class="button" onclick="window.location.href='<?php echo admin_url('admin.php?page=cpp-new-test'); ?>'">
                    <?php _e('Crear Nuevo Test', 'club-psychology-pro'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function renderTestForm($atts) {
        $atts = shortcode_atts([
            'type' => 'bigfive',
            'test_id' => 0,
        ], $atts, 'cpp_test_form');
        
        ob_start();
        ?>
        <div class="cpp-test-form">
            <h3><?php _e('Formulario de Test', 'club-psychology-pro'); ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field('cpp_test_form', 'cpp_nonce'); ?>
                <input type="hidden" name="test_type" value="<?php echo esc_attr($atts['type']); ?>">
                
                <p>
                    <label for="participant_name"><?php _e('Nombre del Participante:', 'club-psychology-pro'); ?></label>
                    <input type="text" id="participant_name" name="participant_name" required>
                </p>
                
                <p>
                    <label for="participant_email"><?php _e('Email:', 'club-psychology-pro'); ?></label>
                    <input type="email" id="participant_email" name="participant_email" required>
                </p>
                
                <p>
                    <button type="submit" class="button button-primary">
                        <?php _e('Crear Test', 'club-psychology-pro'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function renderResult($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'cpp_result');
        
        $result_id = intval($atts['id']);
        
        if (!$result_id) {
            return '<p>' . __('ID de resultado no válido.', 'club-psychology-pro') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="cpp-result-viewer">
            <h3><?php _e('Resultado del Test', 'club-psychology-pro'); ?></h3>
            <p><?php printf(__('Mostrando resultado ID: %d', 'club-psychology-pro'), $result_id); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function registerAjaxHandlers() {
        add_action('wp_ajax_cpp_test_action', [$this, 'handleTestAction']);
        add_action('wp_ajax_nopriv_cpp_test_action', [$this, 'handleTestAction']);
    }
    
    public function handleTestAction() {
        check_ajax_referer('cpp_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['test_action'] ?? '');
        
        switch ($action) {
            case 'create_test':
                $this->ajaxCreateTest();
                break;
            case 'get_results':
                $this->ajaxGetResults();
                break;
            default:
                wp_send_json_error('Acción no válida');
        }
    }
    
    private function ajaxCreateTest() {
        $name = sanitize_text_field($_POST['participant_name'] ?? '');
        $email = sanitize_email($_POST['participant_email'] ?? '');
        $type = sanitize_text_field($_POST['test_type'] ?? '');
        
        if (empty($name) || empty($email) || empty($type)) {
            wp_send_json_error('Datos incompletos');
        }
        
        // Crear test básico
        $test_id = wp_insert_post([
            'post_type' => 'cpp_test',
            'post_title' => $name,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);
        
        if ($test_id) {
            update_post_meta($test_id, '_participant_name', $name);
            update_post_meta($test_id, '_participant_email', $email);
            update_post_meta($test_id, '_test_type', $type);
            
            wp_send_json_success([
                'test_id' => $test_id,
                'message' => 'Test creado exitosamente',
            ]);
        } else {
            wp_send_json_error('Error creando test');
        }
    }
    
    private function ajaxGetResults() {
        $test_id = intval($_POST['test_id'] ?? 0);
        
        if (!$test_id) {
            wp_send_json_error('ID de test no válido');
        }
        
        // Obtener información del test
        $test = get_post($test_id);
        
        if (!$test || $test->post_type !== 'cpp_test') {
            wp_send_json_error('Test no encontrado');
        }
        
        wp_send_json_success([
            'test' => [
                'id' => $test->ID,
                'title' => $test->post_title,
                'date' => $test->post_date,
                'status' => $test->post_status,
            ]
        ]);
    }
    
    private function createAssetStructure() {
        $base_dir = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/';
        $dist_dir = $base_dir . 'dist/';
        $css_dir = $dist_dir . 'css/';
        $js_dir = $dist_dir . 'js/';
        
        // Crear directorios
        wp_mkdir_p($css_dir);
        wp_mkdir_p($js_dir);
        
        // Crear archivos básicos
        $this->createBasicFiles($css_dir, $js_dir);
    }
    
    private function createBasicFiles($css_dir, $js_dir) {
        // CSS Frontend
        if (!file_exists($css_dir . 'frontend.css')) {
            $css = "/* Club Psychology Pro - Frontend */\n";
            $css .= ".cpp-user-panel { padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }\n";
            $css .= ".cpp-test-form { margin: 20px 0; }\n";
            $css .= ".cpp-result-viewer { padding: 15px; }\n";
            file_put_contents($css_dir . 'frontend.css', $css);
        }
        
        // CSS Admin
        if (!file_exists($css_dir . 'admin.css')) {
            $css = "/* Club Psychology Pro - Admin */\n";
            $css .= ".cpp-admin-panel { margin: 20px; }\n";
            file_put_contents($css_dir . 'admin.css', $css);
        }
        
        // JS Frontend
        if (!file_exists($js_dir . 'frontend.js')) {
            $js = "// Club Psychology Pro Frontend\n";
            $js .= "console.log('CPP Frontend loaded');\n";
            file_put_contents($js_dir . 'frontend.js', $js);
        }
        
        // JS Admin
        if (!file_exists($js_dir . 'admin.js')) {
            $js = "// Club Psychology Pro Admin\n";
            $js .= "console.log('CPP Admin loaded');\n";
            file_put_contents($js_dir . 'admin.js', $js);
        }
    }
}

// Inicializar el plugin
ClubPsychologyPro_Main::getInstance();