<?php
/**
 * Configuración principal de Club Psychology Pro
 * 
 * @package ClubPsychologyPro
 * @since 2.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('¡Acceso directo no permitido!');
}

return [
    
    // =============================================
    // CONFIGURACIÓN GENERAL
    // =============================================
    
    'app' => [
        'name' => 'Club Psychology Pro',
        'version' => '2.0.0',
        'env' => defined('WP_DEBUG') && WP_DEBUG ? 'development' : 'production',
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
        'timezone' => get_option('timezone_string', 'UTC'),
        'locale' => get_locale(),
        'charset' => get_option('blog_charset', 'UTF-8'),
    ],
    
    // =============================================
    // CONFIGURACIÓN DE TESTS
    // =============================================
    
    'tests' => [
        
        // Tipos de tests habilitados
        'enabled_types' => [
            'bigfive' => true,
            'team_cohesion' => true,
            'anger_rumination' => true,
            'phq_sads' => true,
            'suggestibility' => true,
            'who5_wellbeing' => true,
            'attending_emotions' => true,
            'depression_mdi' => false, // Deshabilitado por defecto
            'emotional_problems' => false, // Deshabilitado por defecto
        ],
        
        // Configuración general de tests
        'defaults' => [
            'time_limit' => 300, // 5 minutos en segundos
            'save_responses' => true,
            'allow_retake' => false,
            'require_login' => true,
            'auto_send_results' => true,
        ],
        
        // Límites por tipo de usuario
        'user_limits' => [
            'administrator' => 999,
            'plan_basico' => 1,
            'plan_premium' => 3,
            'plan_empresarial' => 10,
            'subscriber' => 0,
        ],
        
        // Configuración de resultados
        'results' => [
            'cache_duration' => 3600, // 1 hora
            'allow_public_view' => false,
            'export_formats' => ['pdf', 'json', 'csv'],
            'charts_enabled' => true,
        ],
        
        // Post types
        'post_types' => [
            'test' => 'cpp_test',
            'result' => 'cpp_result',
            // Legacy support
            'legacy_test' => 'test_personalidad',
            'legacy_bigfive_result' => 'resultado_bigfive',
            'legacy_monitor_result' => 'resultado_monitor',
            'legacy_cohesion_result' => 'resultado_cohesion',
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE USUARIOS
    // =============================================
    
    'users' => [
        'roles' => [
            'cpp_admin' => [
                'display_name' => 'Administrador de Tests',
                'capabilities' => [
                    'manage_cpp_tests',
                    'view_all_cpp_results',
                    'edit_cpp_settings',
                    'delete_cpp_data',
                ]
            ],
            'cpp_manager' => [
                'display_name' => 'Gestor de Tests',
                'capabilities' => [
                    'create_cpp_tests',
                    'view_cpp_results',
                    'edit_cpp_tests',
                ]
            ],
        ],
        
        'capabilities' => [
            'take_cpp_tests',
            'view_own_cpp_results',
            'create_cpp_tests',
            'edit_cpp_tests',
            'delete_cpp_tests',
            'manage_cpp_tests',
            'view_cpp_results',
            'view_all_cpp_results',
            'edit_cpp_settings',
            'delete_cpp_data',
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE EMAIL
    // =============================================
    
    'email' => [
        'enabled' => true,
        'from_name' => get_option('blogname', 'Club Psychology Pro'),
        'from_email' => get_option('admin_email', 'noreply@' . parse_url(home_url(), PHP_URL_HOST)),
        'templates_path' => 'templates/email/',
        
        'test_invitation' => [
            'subject' => __('Invitación para realizar test psicológico', 'club-psychology-pro'),
            'template' => 'test-invitation.php',
            'auto_send' => true,
        ],
        
        'result_notification' => [
            'subject' => __('Resultados de tu test psicológico', 'club-psychology-pro'),
            'template' => 'result-notification.php',
            'auto_send' => true,
        ],
        
        'reminder' => [
            'subject' => __('Recordatorio: Test psicológico pendiente', 'club-psychology-pro'),
            'template' => 'reminder.php',
            'auto_send' => false,
            'delay_hours' => 24,
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE WHATSAPP
    // =============================================
    
    'whatsapp' => [
        'enabled' => false,
        'service_url' => 'http://localhost:3000',
        'webhook_secret' => wp_generate_password(32, false),
        'session_timeout' => 300, // 5 minutos
        
        'messages' => [
            'test_invitation' => [
                'template' => 'Hola {name}! 👋\n\nTienes un nuevo test psicológico disponible: *{test_name}*\n\n🔗 Enlace: {test_url}\n\n⏰ Tiempo estimado: {duration} minutos',
            ],
            'result_ready' => [
                'template' => '✅ ¡Hola {name}!\n\nTus resultados del test *{test_name}* están listos.\n\n📊 Ver resultados: {result_url}',
            ],
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE UI
    // =============================================
    
    'ui' => [
        'theme' => 'dark', // dark, light, auto
        'animations' => true,
        'charts' => [
            'library' => 'chartjs', // chartjs, d3, plotly
            'colors' => [
                'primary' => '#3182f6',
                'secondary' => '#64748b',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'danger' => '#ef4444',
                'info' => '#06b6d4',
            ],
        ],
        
        'dashboard' => [
            'cards_per_row' => 3,
            'show_statistics' => true,
            'show_recent_tests' => true,
            'recent_tests_limit' => 5,
        ],
        
        'forms' => [
            'style' => 'modern', // modern, classic, minimal
            'validation' => 'live', // live, submit, manual
            'progress_bar' => true,
            'save_progress' => true,
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE API
    // =============================================
    
    'api' => [
        'enabled' => true,
        'version' => 'v1',
        'namespace' => 'cpp/v1',
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ],
        
        'authentication' => [
            'methods' => ['jwt', 'api_key', 'wp_auth'],
            'jwt_secret' => wp_generate_password(64, false),
            'token_expiry' => 3600, // 1 hora
        ],
        
        'cors' => [
            'enabled' => false,
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['Content-Type', 'Authorization'],
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE CACHE
    // =============================================
    
    'cache' => [
        'enabled' => true,
        'default_ttl' => 3600, // 1 hora
        'test_configs_ttl' => 86400, // 24 horas
        'results_ttl' => 7200, // 2 horas
        'user_data_ttl' => 1800, // 30 minutos
        
        'redis' => [
            'enabled' => false,
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'prefix' => 'cpp:',
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE LOGGING
    // =============================================
    
    'logging' => [
        'enabled' => true,
        'level' => defined('WP_DEBUG') && WP_DEBUG ? 'debug' : 'error',
        'channels' => [
            'default' => 'file',
            'test_submissions' => 'database',
            'api_requests' => 'file',
            'errors' => 'file',
        ],
        
        'file' => [
            'path' => WP_CONTENT_DIR . '/uploads/cpp-logs/',
            'max_size' => '10MB',
            'rotate' => true,
            'retain_days' => 30,
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE SEGURIDAD
    // =============================================
    
    'security' => [
        'nonce_lifetime' => 12 * HOUR_IN_SECONDS,
        'max_login_attempts' => 5,
        'lockout_duration' => 15 * MINUTE_IN_SECONDS,
        
        'encryption' => [
            'algorithm' => 'AES-256-CBC',
            'key' => defined('CPP_ENCRYPTION_KEY') ? CPP_ENCRYPTION_KEY : wp_generate_password(32, false),
        ],
        
        'data_protection' => [
            'anonymize_after_days' => 365,
            'delete_after_days' => 1095, // 3 años
            'export_format' => 'json',
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE PERFORMANCE
    // =============================================
    
    'performance' => [
        'lazy_load_results' => true,
        'compress_responses' => true,
        'minify_assets' => !defined('WP_DEBUG') || !WP_DEBUG,
        'combine_assets' => !defined('WP_DEBUG') || !WP_DEBUG,
        
        'database' => [
            'query_cache' => true,
            'use_indexes' => true,
            'optimize_queries' => true,
        ],
        
        'assets' => [
            'version' => CPP_VERSION,
            'cache_bust' => filemtime(CPP_PLUGIN_DIR . 'assets/dist/css/main.css'),
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE INTEGRACIONES
    // =============================================
    
    'integrations' => [
        'woocommerce' => [
            'enabled' => class_exists('WooCommerce'),
            'membership_integration' => true,
            'product_restrictions' => true,
        ],
        
        'learndash' => [
            'enabled' => class_exists('SFWD_LMS'),
            'course_completion_tests' => false,
        ],
        
        'memberpress' => [
            'enabled' => class_exists('MeprUser'),
            'membership_levels' => true,
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE DESARROLLO
    // =============================================
    
    'development' => [
        'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
        'profiling' => false,
        'mock_data' => false,
        'test_mode' => defined('CPP_TEST_MODE') && CPP_TEST_MODE,
        
        'webpack' => [
            'dev_server' => 'http://localhost:8080',
            'hot_reload' => false,
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN ESPECÍFICA POR ENTORNO
    // =============================================
    
    'environments' => [
        'development' => [
            'debug' => true,
            'cache_enabled' => false,
            'minify_assets' => false,
            'logging_level' => 'debug',
        ],
        
        'staging' => [
            'debug' => true,
            'cache_enabled' => true,
            'minify_assets' => true,
            'logging_level' => 'info',
        ],
        
        'production' => [
            'debug' => false,
            'cache_enabled' => true,
            'minify_assets' => true,
            'logging_level' => 'error',
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE MIGRACIÓN
    // =============================================
    
    'migration' => [
        'from_legacy' => true,
        'backup_before_migration' => true,
        'migration_batch_size' => 100,
        'legacy_post_types' => [
            'test_personalidad',
            'resultado_bigfive',
            'resultado_monitor',
            'resultado_cohesion',
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE BACKUP
    // =============================================
    
    'backup' => [
        'enabled' => false,
        'schedule' => 'weekly',
        'retention_days' => 30,
        'include_uploads' => false,
        'compression' => true,
        
        'storage' => [
            'local' => true,
            's3' => false,
            'google_drive' => false,
        ],
    ],
    
    // =============================================
    // CONFIGURACIÓN DE ANALYTICS
    // =============================================
    
    'analytics' => [
        'enabled' => true,
        'track_test_completions' => true,
        'track_user_engagement' => true,
        'track_performance' => false,
        
        'google_analytics' => [
            'enabled' => false,
            'tracking_id' => '',
            'anonymize_ip' => true,
        ],
    ],
];