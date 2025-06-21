<?php
/**
 * Clase para manejar la activación del plugin
 *
 * @package ClubPsychologyPro\Includes
 * @since 1.0.0
 */

namespace ClubPsychologyPro\Includes;

use ClubPsychologyPro\Core\Plugin;
use ClubPsychologyPro\Database\Migration;

/**
 * Clase Activator
 * 
 * Se ejecuta cuando el plugin se activa
 */
class Activator {
    
    /**
     * Activar el plugin
     * 
     * @return void
     */
    public static function activate(): void {
        try {
            // Verificar versiones mínimas
            self::checkRequirements();
            
            // Crear las tablas necesarias
            self::createTables();
            
            // Registrar post types temporalmente para flush
            self::registerPostTypes();
            
            // Crear páginas necesarias
            self::createPages();
            
            // Configurar capacidades
            self::setupCapabilities();
            
            // Configurar opciones por defecto
            self::setupDefaultOptions();
            
            // Programar tareas cron
            self::scheduleCronJobs();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Marcar que se necesita inicializar
            update_option('cpp_needs_initialization', true);
            
            // Log de activación exitosa
            error_log('Club Psychology Pro: Plugin activado exitosamente');
            
        } catch (\Exception $e) {
            // Log del error
            error_log('Club Psychology Pro - Error en activación: ' . $e->getMessage());
            
            // Desactivar el plugin si hay error crítico
            deactivate_plugins(plugin_basename(CLUB_PSYCHOLOGY_PRO_PLUGIN_FILE));
            
            // Mostrar error al usuario
            wp_die(
                sprintf(
                    __('Error activando Club Psychology Pro: %s', 'club-psychology-pro'),
                    $e->getMessage()
                ),
                __('Error de Activación', 'club-psychology-pro'),
                ['back_link' => true]
            );
        }
    }
    
    /**
     * Verificar requisitos del sistema
     * 
     * @return void
     * @throws \Exception Si no cumple los requisitos
     */
    private static function checkRequirements(): void {
        global $wp_version;
        
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            throw new \Exception(
                sprintf(
                    __('Club Psychology Pro requiere PHP 7.4 o superior. Versión actual: %s', 'club-psychology-pro'),
                    PHP_VERSION
                )
            );
        }
        
        // Verificar versión de WordPress
        if (version_compare($wp_version, '5.0', '<')) {
            throw new \Exception(
                sprintf(
                    __('Club Psychology Pro requiere WordPress 5.0 o superior. Versión actual: %s', 'club-psychology-pro'),
                    $wp_version
                )
            );
        }
        
        // Verificar extensiones PHP necesarias
        $required_extensions = ['json', 'curl', 'mbstring'];
        $missing_extensions = [];
        
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing_extensions[] = $extension;
            }
        }
        
        if (!empty($missing_extensions)) {
            throw new \Exception(
                sprintf(
                    __('Extensiones PHP faltantes: %s', 'club-psychology-pro'),
                    implode(', ', $missing_extensions)
                )
            );
        }
        
        // Verificar permisos de escritura
        if (!is_writable(WP_CONTENT_DIR)) {
            throw new \Exception(
                __('El directorio wp-content no tiene permisos de escritura', 'club-psychology-pro')
            );
        }
    }
    
    /**
     * Crear tablas de base de datos
     * 
     * @return void
     */
    private static function createTables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla para estadísticas de tests
        $table_stats = $wpdb->prefix . 'cpp_test_stats';
        $sql_stats = "CREATE TABLE $table_stats (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            test_type varchar(50) NOT NULL,
            completion_time int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY user_id (user_id),
            KEY test_type (test_type),
            KEY completed_at (completed_at)
        ) $charset_collate;";
        
        // Tabla para configuraciones de WhatsApp
        $table_whatsapp = $wpdb->prefix . 'cpp_whatsapp_config';
        $sql_whatsapp = "CREATE TABLE $table_whatsapp (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            instance_id varchar(100) NOT NULL,
            api_key varchar(255) NOT NULL,
            webhook_url varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'inactive',
            last_connection datetime DEFAULT NULL,
            settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY instance_id (instance_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabla para logs de email
        $table_email_logs = $wpdb->prefix . 'cpp_email_logs';
        $sql_email_logs = "CREATE TABLE $table_email_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id bigint(20) UNSIGNED NOT NULL,
            recipient_email varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            attempts int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY recipient_email (recipient_email),
            KEY status (status),
            KEY sent_at (sent_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_stats);
        dbDelta($sql_whatsapp);
        dbDelta($sql_email_logs);
        
        // Guardar versión de la base de datos
        update_option('cpp_db_version', CLUB_PSYCHOLOGY_PRO_VERSION);
    }
    
    /**
     * Registrar post types temporalmente para flush
     * 
     * @return void
     */
    private static function registerPostTypes(): void {
        // Test de personalidad
        register_post_type('cpp_test', [
            'public' => true,
            'rewrite' => ['slug' => 'test-personalidad'],
        ]);
        
        // Resultados
        register_post_type('cpp_result_bf', [
            'public' => true,
            'rewrite' => ['slug' => 'resultado-bigfive'],
        ]);
        
        register_post_type('cpp_result_cohesion', [
            'public' => true,
            'rewrite' => ['slug' => 'resultado-cohesion'],
        ]);
        
        register_post_type('cpp_result_monitor', [
            'public' => true,
            'rewrite' => ['slug' => 'resultado-monitor'],
        ]);
    }
    
    /**
     * Crear páginas necesarias
     * 
     * @return void
     */
    private static function createPages(): void {
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
            ],
            'panel-tests' => [
                'title' => __('Panel de Tests', 'club-psychology-pro'),
                'content' => '[cpp_panel_usuario]'
            ]
        ];

        foreach ($pages as $slug => $page) {
            // Verificar si la página ya existe
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                $page_id = wp_insert_post([
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                    'post_author' => 1, // Admin user
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ]);
                
                if (!is_wp_error($page_id)) {
                    // Guardar ID de la página para referencia futura
                    update_option('cpp_page_' . str_replace('-', '_', $slug), $page_id);
                }
            } else {
                // Actualizar contenido si la página existe pero está vacía
                if (empty($existing_page->post_content)) {
                    wp_update_post([
                        'ID' => $existing_page->ID,
                        'post_content' => $page['content']
                    ]);
                }
                
                update_option('cpp_page_' . str_replace('-', '_', $slug), $existing_page->ID);
            }
        }
    }
    
    /**
     * Configurar capacidades de usuario
     * 
     * @return void
     */
    private static function setupCapabilities(): void {
        // Capacidades para administradores
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_capabilities = [
                'manage_psychology_tests',
                'view_all_test_results',
                'export_test_data',
                'manage_whatsapp_integration',
                'view_test_statistics'
            ];
            
            foreach ($admin_capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Capacidades para editores
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_capabilities = [
                'view_all_test_results',
                'export_test_data'
            ];
            
            foreach ($editor_capabilities as $cap) {
                $editor_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Configurar opciones por defecto
     * 
     * @return void
     */
    private static function setupDefaultOptions(): void {
        $default_options = [
            'cpp_email_notifications' => true,
            'cpp_whatsapp_enabled' => false,
            'cpp_auto_cleanup_days' => 30,
            'cpp_max_test_attempts' => 3,
            'cpp_test_timeout_minutes' => 60,
            'cpp_enable_statistics' => true,
            'cpp_admin_email_notifications' => true,
            'cpp_default_test_language' => 'es',
            'cpp_results_retention_days' => 365,
            'cpp_enable_user_dashboard' => true
        ];
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
        
        // Configuración de email templates
        $email_templates = [
            'test_invitation' => [
                'subject' => __('Invitación para realizar test de personalidad', 'club-psychology-pro'),
                'template' => 'test-invitation'
            ],
            'test_completed' => [
                'subject' => __('Test completado - Resultados disponibles', 'club-psychology-pro'),
                'template' => 'test-completed'
            ],
            'test_reminder' => [
                'subject' => __('Recordatorio: Test pendiente de completar', 'club-psychology-pro'),
                'template' => 'test-reminder'
            ]
        ];
        
        foreach ($email_templates as $key => $template) {
            if (get_option("cpp_email_template_$key") === false) {
                add_option("cpp_email_template_$key", $template);
            }
        }
    }
    
    /**
     * Programar tareas cron
     * 
     * @return void
     */
    private static function scheduleCronJobs(): void {
        // Limpieza diaria de datos antiguos
        if (!wp_next_scheduled('cpp_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'cpp_daily_cleanup');
        }
        
        // Reportes semanales
        if (!wp_next_scheduled('cpp_weekly_reports')) {
            wp_schedule_event(time(), 'weekly', 'cpp_weekly_reports');
        }
        
        // Verificación de estado de WhatsApp cada hora
        if (!wp_next_scheduled('cpp_whatsapp_health_check')) {
            wp_schedule_event(time(), 'hourly', 'cpp_whatsapp_health_check');
        }
        
        // Recordatorios de tests pendientes
        if (!wp_next_scheduled('cpp_test_reminders')) {
            wp_schedule_event(time(), 'twicedaily', 'cpp_test_reminders');
        }
    }
}