<?php
/**
 * Plugin Name:     Club Psychology Pro
 * Plugin URI:      https://aquea.org
 * Description:     Sistema avanzado de tests psicológicos con gestión de usuarios, WhatsApp y reportes.
 * Version:         1.0.0
 * Author:          Aquea Team
 * Author URI:      https://aquea.org
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     club-psychology-pro
 * Domain Path:     /languages
 * Requires at least: 5.0
 * Tested up to:    6.4
 * Requires PHP:    7.4
 * Network:         false
 *
 * @package ClubPsychologyPro
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --------------------------------------------------
// Constants
// --------------------------------------------------
define( 'CLUB_PSYCHOLOGY_PRO_VERSION',        '1.0.0' );
define( 'CLUB_PSYCHOLOGY_PRO_PLUGIN_FILE',    __FILE__ );
define( 'CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'CLUB_PSYCHOLOGY_PRO_PLUGIN_URL',     plugin_dir_url( __FILE__ ) );
define( 'CLUB_PSYCHOLOGY_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// --------------------------------------------------
// Minimum Requirements (sin cambios)
// --------------------------------------------------
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    /* ... */
}
global $wp_version;
if ( version_compare( $wp_version, '5.0', '<' ) ) {
    /* ... */
}

// --------------------------------------------------
// Autoloader (sin cambios)
// --------------------------------------------------
$autoloader = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
    require_once $autoloader;
} else {
    spl_autoload_register( /* ... */ );
}

// --------------------------------------------------
// Main Plugin Class
// --------------------------------------------------
class ClubPsychologyPro_Main {

    private static $instance = null;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        register_activation_hook(   __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

        add_action( 'plugins_loaded', [ $this, 'onPluginsLoaded' ] );
        add_action( 'init',           [ $this, 'onInit' ] );
        add_action( 'admin_init',     [ $this, 'ensureAssets' ] );
    }

    public function activate() {
        $this->createAssetStructure();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function onPluginsLoaded() {
        load_plugin_textdomain(
            'club-psychology-pro',
            false,
            dirname( CLUB_PSYCHOLOGY_PRO_PLUGIN_BASENAME ) . '/languages'
        );
    }

    public function onInit() {
        $this->registerAssets();
        $this->registerShortcodes();
        $this->registerAjaxHandlers();
    }

    public function ensureAssets() {
        // crea dist/css y dist/js si no existen
        $base = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/';
        if ( ! file_exists( $base . 'css' ) || ! file_exists( $base . 'js' ) ) {
            wp_mkdir_p( $base . 'css' );
            wp_mkdir_p( $base . 'js' );
        }
    }

    private function registerAssets() {
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueuePublicAssets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets'  ] );
    }

    public function enqueuePublicAssets() {
        $ver      = CLUB_PSYCHOLOGY_PRO_VERSION;
        $assetUrl = CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/dist/';

        // CSS frontend
        $css = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/css/frontend.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'cpp-frontend-style', $assetUrl . 'css/frontend.css', [], $ver );
        }

        // JS frontend (ahora frontend.js)
        $js = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/js/frontend.js';
        if ( file_exists( $js ) ) {
            wp_enqueue_script(
                'cpp-frontend-script',
                $assetUrl . 'js/frontend.js',
                [ 'jquery' ],
                $ver,
                true
            );
            wp_localize_script( 'cpp-frontend-script', 'cppData', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'restUrl' => rest_url( 'cpp/v1/' ),
                'nonce'   => wp_create_nonce( 'cpp_nonce' ),
                'i18n'    => [
                    'selectMonitorTest' => __( 'Por favor selecciona un test monitor específico.', 'club-psychology-pro' ),
                    'creatingTest'      => __( 'Creando Test…', 'club-psychology-pro' ),
                ],
            ] );
        }
    }

    public function enqueueAdminAssets( $hook ) {
        if ( false === strpos( $hook, 'cpp' ) ) {
            return;
        }

        $ver      = CLUB_PSYCHOLOGY_PRO_VERSION;
        $assetUrl = CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/dist/';

        $css = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/css/admin.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'cpp-admin-style', $assetUrl . 'css/admin.css', [], $ver );
        }

        $js = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/js/admin.js';
        if ( file_exists( $js ) ) {
            wp_enqueue_script( 'cpp-admin-script', $assetUrl . 'js/admin.js', [ 'jquery' ], $ver, true );
        }
    }

    private function registerShortcodes() {
        add_shortcode( 'cpp_user_panel', [ $this, 'renderUserPanel' ] );
        add_shortcode( 'cpp_test_form',  [ $this, 'renderTestForm' ] );
        add_shortcode( 'cpp_result',     [ $this, 'renderResult'   ] );
    }

    public function renderUserPanel( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Debes iniciar sesión para ver tu panel.', 'club-psychology-pro' ) . '</p>';
        }
        ob_start();
        ?>
        <div class="cpp-user-panel">
            <h3><?php _e( 'Mi Panel de Tests', 'club-psychology-pro' ); ?></h3>
            <p><?php _e( 'Aquí puedes ver tus tests y resultados.', 'club-psychology-pro' ); ?></p>
            <div class="cpp-test-actions">
                <button type="button" class="button button-primary js-open-test-form">
                    <?php esc_html_e( 'Crear Nuevo Test', 'club-psychology-pro' ); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function renderTestForm( $atts ) {
        $atts = shortcode_atts( [
            'type'    => 'bigfive',
            'test_id' => 0,
        ], $atts, 'cpp_test_form' );

        ob_start();
        ?>
        <div id="cpp-test-form-container" class="cpp-test-form-wrapper" style="display:none;">
            <form id="cpp-test-form" class="cpp-test-form" method="post" action="">
                <?php wp_nonce_field( 'test_form_submit', 'test_form_nonce' ); ?>
                <input type="hidden" name="action" value="submit_test_request">

                <!-- aquí va TODO el contenido de tu tabla y campos, sin ningún <script> inline -->
                <!-- ... -->

                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Crear Test', 'club-psychology-pro' ); ?>">
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function renderResult( $atts ) {
        // ... sigue igual ...
    }

    private function registerAjaxHandlers() {
        // ... sigue igual ...
    }

    // Ajax handlers, createAssetStructure, etc. siguen igual...
}

// Initialize
ClubPsychologyPro_Main::getInstance();
