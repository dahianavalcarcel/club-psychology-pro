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
// Minimum Requirements
// --------------------------------------------------
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    add_action( 'admin_notices', function() {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html( sprintf(
                __( 'Club Psychology Pro requiere PHP 7.4 o superior. Tu versión actual es %s.', 'club-psychology-pro' ),
                PHP_VERSION
            ) )
        );
    } );
    return;
}

global $wp_version;
if ( version_compare( $wp_version, '5.0', '<' ) ) {
    add_action( 'admin_notices', function() {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'Club Psychology Pro requiere WordPress 5.0 o superior.', 'club-psychology-pro' )
        );
    } );
    return;
}

// --------------------------------------------------
// Autoloader
// --------------------------------------------------
$autoloader = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
    require_once $autoloader;
} else {
    spl_autoload_register( function ( $class ) {
        $prefix  = 'ClubPsychologyPro\\';
        $baseDir = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'src/';

        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relClass = substr( $class, strlen( $prefix ) );
        $file     = $baseDir . str_replace( '\\', '/', $relClass ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    } );
}

// --------------------------------------------------
// Main Plugin Class
// --------------------------------------------------
class ClubPsychologyPro_Main {

    /** @var ClubPsychologyPro_Main|null */
    private static $instance = null;

    /**
     * Singleton access
     *
     * @return ClubPsychologyPro_Main
     */
    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Bootstraps hooks, activation, deactivation
     */
    private function init() {
        register_activation_hook(   __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

        add_action( 'plugins_loaded', [ $this, 'onPluginsLoaded' ] );
        add_action( 'init',           [ $this, 'onInit' ] );
        add_action( 'admin_init',     [ $this, 'ensureAssets' ] );
    }

    /**
     * Activation hook
     */
    public function activate() {
        $this->createAssetStructure();
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Load textdomain
     */
    public function onPluginsLoaded() {
        load_plugin_textdomain(
            'club-psychology-pro',
            false,
            dirname( CLUB_PSYCHOLOGY_PRO_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Init: register assets, shortcodes, AJAX
     */
    public function onInit() {
        $this->registerAssets();
        $this->registerShortcodes();
        $this->registerAjaxHandlers();
    }

    /**
     * Ensure AssetManager structure
     */
    public function ensureAssets() {
        if ( class_exists( 'ClubPsychologyPro\\UI\\AssetManager' ) ) {
            \ClubPsychologyPro\UI\AssetManager::ensureAssetStructure();
        }
    }

    /**
     * Hook into WP to enqueue scripts & styles
     */
    private function registerAssets() {
        add_action( 'wp_enqueue_scripts',      [ $this, 'enqueuePublicAssets' ] );
        add_action( 'admin_enqueue_scripts',   [ $this, 'enqueueAdminAssets' ] );
    }

    /**
     * Enqueue public/frontend assets
     */
    public function enqueuePublicAssets() {
        $ver      = CLUB_PSYCHOLOGY_PRO_VERSION;
        $assetUrl = CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/dist/';

        $css = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/dist/css/frontend.css';
        if ( file_exists( $css ) ) {
            wp_enqueue_style( 'cpp-frontend-style', $assetUrl . 'css/frontend.css', [], $ver );
        }

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
                'nonce'   => wp_create_nonce( 'cpp_nonce' ),
                'restUrl' => rest_url( 'cpp/v1/' ),
                'siteUrl' => site_url(),
            ] );
        }
    }

    /**
     * Enqueue admin assets on plugin pages
     *
     * @param string $hook
     */
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

    /**
     * Register public shortcodes
     */
    private function registerShortcodes() {
        add_shortcode( 'cpp_user_panel', [ $this, 'renderUserPanel' ] );
        add_shortcode( 'cpp_test_form', [ $this, 'renderTestForm' ] );
        add_shortcode( 'cpp_result',    [ $this, 'renderResult' ] );
    }

    /**
     * [cpp_user_panel]
     */
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
           <button type="button"
        class="button button-primary js-open-test-form">
    <?php esc_html_e( 'Crear Nuevo Test', 'club-psychology-pro' ); ?>
</button>

        </div>
    </div>
    <?php
    return ob_get_clean();
}


    /**
     * [cpp_test_form]
     */
    public function renderTestForm( $atts ) {
        $atts = shortcode_atts( [
            'type'    => 'bigfive',
            'test_id' => 0,
        ], $atts, 'cpp_test_form' );

        ob_start();
        ?>
        <form id="cpp-test-form" class="cpp-test-form" method="post" action="">
            <?php wp_nonce_field( 'cpp_test_form', 'cpp_nonce' ); ?>
            <input type="hidden" name="test_type" value="<?php echo esc_attr( $atts['type'] ); ?>">
            <p>
                <label><?php _e( 'Nombre del Participante:', 'club-psychology-pro' ); ?></label>
                <input type="text" name="participant_name" required>
            </p>
            <p>
                <label><?php _e( 'Email:', 'club-psychology-pro' ); ?></label>
                <input type="email" name="participant_email" required>
            </p>
            <p>
                <button type="submit" class="button button-primary">
                    <?php _e( 'Crear Test', 'club-psychology-pro' ); ?>
                </button>
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * [cpp_result id="123"]
     */
    public function renderResult( $atts ) {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'cpp_result' );
        $id   = intval( $atts['id'] );
        if ( ! $id ) {
            return '<p>' . __( 'ID de resultado no válido.', 'club-psychology-pro' ) . '</p>';
        }
        ob_start();
        ?>
        <div class="cpp-result-viewer">
            <h3><?php _e( 'Resultado del Test', 'club-psychology-pro' ); ?></h3>
            <p><?php printf( __( 'Mostrando resultado ID: %d', 'club-psychology-pro' ), $id ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Register AJAX handlers
     */
    private function registerAjaxHandlers() {
        add_action( 'wp_ajax_cpp_test_action',        [ $this, 'handleTestAction' ] );
        add_action( 'wp_ajax_nopriv_cpp_test_action', [ $this, 'handleTestAction' ] );
    }

    /**
     * Route AJAX calls
     */
    public function handleTestAction() {
        check_ajax_referer( 'cpp_nonce', 'nonce' );

        $action = sanitize_text_field( $_POST['test_action'] ?? '' );
        switch ( $action ) {
            case 'create_test':
                $this->ajaxCreateTest();
                break;
            case 'get_results':
                $this->ajaxGetResults();
                break;
            default:
                wp_send_json_error( 'Acción no válida' );
        }
    }

    /**
     * Create a new test (AJAX)
     */
    private function ajaxCreateTest() {
        $name  = sanitize_text_field( $_POST['participant_name'] ?? '' );
        $email = sanitize_email( $_POST['participant_email'] ?? '' );
        $type  = sanitize_text_field( $_POST['test_type'] ?? '' );

        if ( empty( $name ) || empty( $email ) || empty( $type ) ) {
            wp_send_json_error( 'Datos incompletos' );
        }

        $postId = wp_insert_post( [
            'post_type'   => 'cpp_test',
            'post_title'  => $name,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ] );

        if ( $postId ) {
            update_post_meta( $postId, '_participant_name',  $name  );
            update_post_meta( $postId, '_participant_email', $email );
            update_post_meta( $postId, '_test_type',        $type  );

            wp_send_json_success( [
                'test_id' => $postId,
                'message' => 'Test creado exitosamente',
            ] );
        }

        wp_send_json_error( 'Error creando test' );
    }

    /**
     * Get test results (AJAX)
     */
    private function ajaxGetResults() {
        $testId = intval( $_POST['test_id'] ?? 0 );
        if ( ! $testId ) {
            wp_send_json_error( 'ID de test no válido' );
        }
        $post = get_post( $testId );
        if ( ! $post || 'cpp_test' !== $post->post_type ) {
            wp_send_json_error( 'Test no encontrado' );
        }
        wp_send_json_success( [
            'test' => [
                'id'     => $post->ID,
                'title'  => $post->post_title,
                'date'   => $post->post_date,
                'status' => $post->post_status,
            ],
        ] );
    }

    /**
     * Create asset directories & files
     */
    private function createAssetStructure() {
        $base    = CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'assets/';
        $dist    = $base . 'dist/';
        $cssDir  = $dist . 'css/';
        $jsDir   = $dist . 'js/';
        wp_mkdir_p( $cssDir );
        wp_mkdir_p( $jsDir );
        $this->createBasicFiles( $cssDir, $jsDir );
    }

    private function createBasicFiles( $cssDir, $jsDir ) {
        if ( ! file_exists( $cssDir . 'frontend.css' ) ) {
            file_put_contents( $cssDir . 'frontend.css', "/* CPP Frontend */\n.cpp-user-panel{padding:20px;border:1px solid #ddd;}\n" );
        }
        if ( ! file_exists( $cssDir . 'admin.css' ) ) {
            file_put_contents( $cssDir . 'admin.css', "/* CPP Admin */\n.cpp-admin-panel{margin:20px;}\n" );
        }
        if ( ! file_exists( $jsDir . 'frontend.js' ) ) {
            file_put_contents( $jsDir . 'frontend.js', "// CPP Frontend JS loaded\n" );
        }
        if ( ! file_exists( $jsDir . 'admin.js' ) ) {
            file_put_contents( $jsDir . 'admin.js', "// CPP Admin JS loaded\n" );
        }
    }
}

// Initialize
ClubPsychologyPro_Main::getInstance();
