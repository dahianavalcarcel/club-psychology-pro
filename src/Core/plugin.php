<?php
/**
 * Clase principal del plugin Club Psychology Pro
 *
 * @package ClubPsychologyPro\Core
 * @since 1.0.0
 */

namespace ClubPsychologyPro\Core;

use ClubPsychologyPro\Database\Migration;
use ClubPsychologyPro\Tests\Types\BigFiveTest;
use ClubPsychologyPro\Tests\Types\CohesionTest;
use ClubPsychologyPro\Tests\Types\MonitorTest;
use ClubPsychologyPro\Users\UserManager;
use ClubPsychologyPro\Users\SubscriptionManager;
use ClubPsychologyPro\Email\EmailManager;
use ClubPsychologyPro\WhatsApp\WhatsAppManager;
use ClubPsychologyPro\UI\Dashboard;
use ClubPsychologyPro\UI\AdminPages;
use ClubPsychologyPro\Email\TemplateEngine;

/**
 * Clase principal que maneja la inicialización y coordinación del plugin
 */
class Plugin {
    
    /**
     * Instancia única del plugin (Singleton)
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;
    
    /**
     * Contenedor de dependencias
     * @var Container
     */
    private Container $container;
    
    /**
     * Gestor de eventos
     * @var EventManager
     */
    private EventManager $event_manager;
    
    /**
     * Estado de inicialización
     * @var bool
     */
    private bool $initialized = false;
    
    /**
     * Constructor privado para Singleton
     */
    private function __construct() {
        $this->container = new Container();
        $this->event_manager = new EventManager();
    }
    
    /**
     * Obtener instancia única del plugin
     * 
     * @return Plugin
     */
    public static function getInstance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar el plugin
     * 
     * @return void
     */
    public function init(): void {
        if ($this->initialized) {
            return;
        }
        
        try {
            $this->registerServices();
            $this->setupHooks();
            $this->loadTextDomain();
            $this->initComponents();
            
            $this->initialized = true;
            
            // Disparar evento de inicialización
            $this->event_manager->dispatch('plugin_initialized', $this);
            
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
    }
    
    /**
 * Registrar servicios en el contenedor
 * 
 * @return void
 */
private function registerServices(): void
{
    // 1) Servicios core
    $this->container->singleton('migration', function() {
        return new Migration();
    });

    $this->container->singleton('user_manager', function(Container $c) {
        return new UserManager($c);
    });

    $this->container->singleton('subscription_manager', function(Container $c) {
        return new SubscriptionManager($c);
    });

    // 2) TemplateEngine: indicamos dónde están nuestras plantillas
    $this->container->singleton(
        TemplateEngine::class,
        function(Container $c) {
            return new TemplateEngine(
                CLUB_PSYCHOLOGY_PRO_PLUGIN_DIR . 'templates/'
            );
        }
    );

    // 3) EmailManager: capturamos la instancia de Plugin en $plugin
$plugin = $this;

$this->container->singleton('email_manager', function(Container $c) use ($plugin) {
    return new EmailManager($plugin);
});

/* 4) WhatsAppManager
    $this->container->singleton('whatsapp_manager', function(Container $c) {
        return new WhatsAppManager($c);
    })

    // 5) Tipos de tests
    $this->container->singleton('bigfive_test', function(Container $c) {
        return new BigFiveTest($c);
    });

    $this->container->singleton('cohesion_test', function(Container $c) {
        return new CohesionTest($c);
    });

    $this->container->singleton('monitor_test', function(Container $c) {
        return new MonitorTest($c);
    });
*/
    // 6) Componentes de UI
    $this->container->singleton('dashboard', function(Container $c) {
        return new Dashboard($c);
    });

    $this->container->singleton('admin_pages', function(Container $c) {
        return new AdminPages($c);
    });
}

    
    /**
     * Configurar hooks de WordPress
     * 
     * @return void
     */
    private function setupHooks(): void {
        // Hooks de inicialización
        add_action('init', [$this, 'onWordPressInit']);
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // REST API
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // AJAX handlers
        add_action('wp_ajax_cpp_reenviar_test', [$this, 'handleReenviarTest']);
        add_action('wp_ajax_cpp_get_test_results', [$this, 'handleGetTestResults']);
        
        // Shortcodes
        add_action('init', [$this, 'registerShortcodes']);
        
        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_account_menu_items', [$this, 'addAccountMenuItem']);
            add_action('init', [$this, 'addAccountEndpoint']);
        }
    }
    
    /**
     * Inicializar componentes del plugin
     * 
     * @return void
     */
    private function initComponents(): void {
        // Inicializar managers
        $this->container->get('user_manager')->init();
        $this->container->get('subscription_manager')->init();
        $this->container->get('email_manager')->init();
        $this->container->get('whatsapp_manager')->init();
        
        // Inicializar tipos de tests
        $this->container->get('bigfive_test')->init();
        $this->container->get('cohesion_test')->init();
        $this->container->get('monitor_test')->init();
        
        // Inicializar UI
        $this->container->get('dashboard')->init();
        $this->container->get('admin_pages')->init();
    }
    
    /**
     * Cargar traducciones
     * 
     * @return void
     */
    private function loadTextDomain(): void {
        load_plugin_textdomain(
            'club-psychology-pro',
            false,
            dirname(CLUB_PSYCHOLOGY_PRO_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Handler para inicialización de WordPress
     * 
     * @return void
     */
    public function onWordPressInit(): void {
        // Registrar post types
        $this->registerPostTypes();
        
        // Registrar taxonomías
        $this->registerTaxonomies();
        
        // Flush rewrite rules si es necesario
        $this->maybeFlushRewriteRules();
    }
    
    /**
     * Registrar Custom Post Types
     * 
     * @return void
     */
    private function registerPostTypes(): void {
        // Test de personalidad (solicitudes)
        register_post_type('cpp_test', [
            'labels' => [
                'name' => __('Tests de Personalidad', 'club-psychology-pro'),
                'singular_name' => __('Test de Personalidad', 'club-psychology-pro'),
                'add_new' => __('Añadir nuevo', 'club-psychology-pro'),
                'add_new_item' => __('Añadir nuevo test', 'club-psychology-pro'),
                'edit_item' => __('Editar test', 'club-psychology-pro'),
                'view_item' => __('Ver test', 'club-psychology-pro'),
                'search_items' => __('Buscar tests', 'club-psychology-pro'),
            ],
            'public' => true,
            'has_archive' => false,
            'supports' => ['title', 'editor', 'custom-fields', 'author'],
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-clipboard',
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'test-personalidad'],
            'show_in_rest' => true,
        ]);
        
        // Resultados Big Five
        register_post_type('cpp_result_bf', [
            'labels' => [
                'name' => __('Resultados Big Five', 'club-psychology-pro'),
                'singular_name' => __('Resultado Big Five', 'club-psychology-pro'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'custom-fields'],
            'menu_icon' => 'dashicons-chart-bar',
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'resultado-bigfive'],
            'show_in_rest' => true,
        ]);
        
        // Resultados de Cohesión
        register_post_type('cpp_result_cohesion', [
            'labels' => [
                'name' => __('Resultados Cohesión', 'club-psychology-pro'),
                'singular_name' => __('Resultado Cohesión', 'club-psychology-pro'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'custom-fields'],
            'menu_icon' => 'dashicons-groups',
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'resultado-cohesion'],
            'show_in_rest' => true,
        ]);
        
        // Resultados Monitor
        register_post_type('cpp_result_monitor', [
            'labels' => [
                'name' => __('Resultados Monitor', 'club-psychology-pro'),
                'singular_name' => __('Resultado Monitor', 'club-psychology-pro'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'custom-fields'],
            'menu_icon' => 'dashicons-chart-line',
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'resultado-monitor'],
            'show_in_rest' => true,
        ]);
    }
    
    /**
     * Registrar taxonomías
     * 
     * @return void
     */
    private function registerTaxonomies(): void {
        // Taxonomía para tipos de test
        register_taxonomy('cpp_test_type', 'cpp_test', [
            'labels' => [
                'name' => __('Tipos de Test', 'club-psychology-pro'),
                'singular_name' => __('Tipo de Test', 'club-psychology-pro'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
        ]);
    }
    
    /**
     * Encolar assets públicos
     * 
     * @return void
     */
    public function enqueuePublicAssets(): void {
        // CSS principal
        wp_enqueue_style(
            'cpp-public-styles',
            CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/css/public.css',
            [],
            CLUB_PSYCHOLOGY_PRO_VERSION
        );
        
        // JavaScript principal
        wp_enqueue_script(
            'cpp-public-scripts',
            CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/js/public.js',
            ['jquery'],
            CLUB_PSYCHOLOGY_PRO_VERSION,
            true
        );
        
        // Localizar script con datos
        wp_localize_script('cpp-public-scripts', 'cppData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpp_nonce'),
            'siteUrl' => site_url(),
            'restUrl' => rest_url('cpp/v1/'),
            'strings' => [
                'loading' => __('Cargando...', 'club-psychology-pro'),
                'error' => __('Error', 'club-psychology-pro'),
                'success' => __('Éxito', 'club-psychology-pro'),
            ]
        ]);
    }
    
    /**
     * Encolar assets de administración
     * 
     * @return void
     */
    public function enqueueAdminAssets(): void {
        $screen = get_current_screen();
        
        // Solo cargar en páginas relevantes
        if (!$screen || !in_array($screen->id, [
            'edit-cpp_test',
            'cpp_test',
            'edit-cpp_result_bf',
            'cpp_result_bf',
            'edit-cpp_result_cohesion',
            'cpp_result_cohesion',
            'edit-cpp_result_monitor',
            'cpp_result_monitor',
            'club-psychology-pro_page_cpp-dashboard'
        ])) {
            return;
        }
        
        wp_enqueue_style(
            'cpp-admin-styles',
            CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CLUB_PSYCHOLOGY_PRO_VERSION
        );
        
        wp_enqueue_script(
            'cpp-admin-scripts',
            CLUB_PSYCHOLOGY_PRO_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            CLUB_PSYCHOLOGY_PRO_VERSION,
            true
        );
        
        wp_localize_script('cpp-admin-scripts', 'cppAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpp_admin_nonce'),
            'restUrl' => rest_url('cpp/v1/'),
        ]);
    }
    
    /**
     * Registrar rutas REST API
     * 
     * @return void
     */
    public function registerRestRoutes(): void {
        // Rutas para tests
        register_rest_route('cpp/v1', '/tests', [
            'methods' => 'GET',
            'callback' => [$this, 'getTests'],
            'permission_callback' => 'is_user_logged_in',
        ]);
        
        register_rest_route('cpp/v1', '/tests/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getTest'],
            'permission_callback' => 'is_user_logged_in',
        ]);
        
        // Rutas para resultados
        register_rest_route('cpp/v1', '/results/bigfive', [
            'methods' => 'POST',
            'callback' => [$this, 'saveBigFiveResult'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('cpp/v1', '/reenviar-test', [
            'methods' => 'POST',
            'callback' => [$this, 'reenviarTest'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }
    
    /**
     * Registrar shortcodes
     * 
     * @return void
     */
    public function registerShortcodes(): void {
        add_shortcode('cpp_panel_usuario', [$this, 'renderPanelUsuario']);
        add_shortcode('cpp_bigfive_test', [$this, 'renderBigFiveTest']);
        add_shortcode('cpp_cohesion_test', [$this, 'renderCohesionTest']);
        add_shortcode('cpp_monitor_test', [$this, 'renderMonitorTest']);
        add_shortcode('cpp_resultado_bigfive', [$this, 'renderResultadoBigFive']);
        add_shortcode('cpp_resultado_cohesion', [$this, 'renderResultadoCohesion']);
        add_shortcode('cpp_resultado_monitor', [$this, 'renderResultadoMonitor']);
    }
    
    /**
     * Handler para activación del plugin
     * 
     * @return void
     */
    public function onActivation(): void {
        // Ejecutar migraciones
        $migration = $this->container->get('migration');
        $migration->run();
        
        // Crear páginas necesarias
        $this->createRequiredPages();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Configurar capacidades
        $this->setupCapabilities();
    }
    
    /**
     * Handler para desactivación del plugin
     * 
     * @return void
     */
    public function onDeactivation(): void {
        // Limpiar tareas programadas
        wp_clear_scheduled_hook('cpp_daily_cleanup');
        wp_clear_scheduled_hook('cpp_weekly_reports');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Crear páginas requeridas
     * 
     * @return void
     */
    private function createRequiredPages(): void {
        $pages = [
            'haz-tu-test' => [
                'title' => __('Test B5 AI', 'club-psychology-pro'),
                'content' => '[cpp_bigfive_test]'
            ],
            'test-cohesion' => [
                'title' => __('Test de Cohesión', 'club-psychology-pro'),
                'content' => '[cpp_cohesion_test]'
            ],
            'monitor-test' => [
                'title' => __('Monitor Test', 'club-psychology-pro'),
                'content' => '[cpp_monitor_test]'
            ],
            'resultado-cohesion' => [
                'title' => __('Resultado Test de Cohesión', 'club-psychology-pro'),
                'content' => '[cpp_resultado_cohesion]'
            ],
            'resultado-monitor' => [
                'title' => __('Resultado Monitor Test', 'club-psychology-pro'),
                'content' => '[cpp_resultado_monitor]'
            ]
        ];

        foreach ($pages as $slug => $page) {
            if (!get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ]);
            }
        }
    }
    
    /**
     * Configurar capacidades de usuario
     * 
     * @return void
     */
    private function setupCapabilities(): void {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_psychology_tests');
            $admin_role->add_cap('view_all_test_results');
            $admin_role->add_cap('export_test_data');
        }
    }
    
    /**
     * Verificar si es necesario hacer flush de rewrite rules
     * 
     * @return void
     */
    private function maybeFlushRewriteRules(): void {
        if (get_option('cpp_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('cpp_flush_rewrite_rules');
        }
    }
    
    /**
     * Obtener el contenedor de dependencias
     * 
     * @return Container
     */
    public function getContainer(): Container {
        return $this->container;
    }
    
    /**
     * Obtener el gestor de eventos
     * 
     * @return EventManager
     */
    public function getEventManager(): EventManager {
        return $this->event_manager;
    }
    
    /**
     * Verificar si el plugin está inicializado
     * 
     * @return bool
     */
    public function isInitialized(): bool {
        return $this->initialized;
    }
    
    /**
     * Shortcode handlers (delegar a las clases correspondientes)
     */
    public function renderPanelUsuario($atts): string {
        return $this->container->get('dashboard')->renderUserPanel($atts);
    }
    
    public function renderBigFiveTest($atts): string {
        return $this->container->get('bigfive_test')->renderForm($atts);
    }
    
    public function renderCohesionTest($atts): string {
        return $this->container->get('cohesion_test')->renderForm($atts);
    }
    
    public function renderMonitorTest($atts): string {
        return $this->container->get('monitor_test')->renderForm($atts);
    }
    
    public function renderResultadoBigFive($atts): string {
        return $this->container->get('bigfive_test')->renderResult($atts);
    }
    
    public function renderResultadoCohesion($atts): string {
        return $this->container->get('cohesion_test')->renderResult($atts);
    }
    
    public function renderResultadoMonitor($atts): string {
        return $this->container->get('monitor_test')->renderResult($atts);
    }
    
    /**
     * AJAX handlers (delegar a las clases correspondientes)
     */
    public function handleReenviarTest(): void {
        $this->container->get('email_manager')->handleReenviarTest();
    }
    
    public function handleGetTestResults(): void {
        // Implementar según necesidades
        wp_send_json_error('Not implemented yet');
    }
    
    /**
     * REST API handlers (delegar a las clases correspondientes)
     */
    public function getTests(\WP_REST_Request $request) {
        return $this->container->get('user_manager')->getTests($request);
    }
    
    public function getTest(\WP_REST_Request $request) {
        return $this->container->get('user_manager')->getTest($request);
    }
    
    public function saveBigFiveResult(\WP_REST_Request $request) {
        return $this->container->get('bigfive_test')->saveResult($request);
    }
    
    public function reenviarTest(\WP_REST_Request $request) {
        return $this->container->get('email_manager')->reenviarTest($request);
    }
    
    /**
     * WooCommerce integration
     */
    public function addAccountMenuItem($items): array {
        $new_items = [];
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'dashboard') {
                $new_items['tests'] = __('Mis Tests', 'club-psychology-pro');
            }
        }
        return $new_items;
    }
    
    public function addAccountEndpoint(): void {
        add_rewrite_endpoint('tests', EP_ROOT | EP_PAGES);
        add_action('woocommerce_account_tests_endpoint', function() {
            echo do_shortcode('[cpp_panel_usuario]');
        });
    }
}