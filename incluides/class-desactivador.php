<?php
/**
 * Clase para manejar la desactivación del plugin
 *
 * @package ClubPsychologyPro\Includes
 * @since 1.0.0
 */

namespace ClubPsychologyPro\Includes;

/**
 * Clase Deactivator
 * 
 * Se ejecuta cuando el plugin se desactiva
 */
class Deactivator {
    
    /**
     * Desactivar el plugin
     * 
     * @return void
     */
    public static function deactivate(): void {
        try {
            // Limpiar tareas cron programadas
            self::clearScheduledEvents();
            
            // Limpiar cache y transients
            self::clearCache();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Marcar que el plugin está desactivado
            update_option('cpp_plugin_active', false);
            update_option('cpp_deactivated_at', current_time('mysql'));
            
            // Log de desactivación
            error_log('Club Psychology Pro: Plugin desactivado exitosamente');
            
        } catch (\Exception $e) {
            error_log('Club Psychology Pro - Error en desactivación: ' . $e->getMessage());
        }
    }
    
    /**
     * Limpiar eventos programados
     * 
     * @return void
     */
    private static function clearScheduledEvents(): void {
        $scheduled_events = [
            'cpp_daily_cleanup',
            'cpp_weekly_reports',
            'cpp_whatsapp_health_check',
            'cpp_test_reminders'
        ];
        
        foreach ($scheduled_events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
            
            // Limpiar todas las instancias del evento
            wp_clear_scheduled_hook($event);
        }
    }
    
    /**
     * Limpiar cache y transients
     * 
     * @return void
     */
    private static function clearCache(): void {
        global $wpdb;
        
        // Limpiar transients relacionados con el plugin
        $transients = [
            'cpp_test_stats_',
            'cpp_user_tests_',
            'cpp_whatsapp_status_',
            'cpp_email_queue_',
            'cpp_system_status_'
        ];
        
        foreach ($transients as $prefix) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_' . $prefix . '%'
                )
            );
            
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_timeout_' . $prefix . '%'
                )
            );
        }
        
        // Limpiar cache de objetos
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Limpiar datos opcionales (solo si el usuario lo solicita)
     * 
     * @param bool $delete_data Si se deben eliminar todos los datos
     * @return void
     */
    public static function uninstall(bool $delete_data = false): void {
        if (!$delete_data) {
            return;
        }
        
        try {
            // Eliminar tablas personalizadas
            self::dropCustomTables();
            
            // Eliminar opciones del plugin
            self::deleteOptions();
            
            // Eliminar posts y metadatos
            self::deletePostsAndMeta();
            
            // Eliminar capacidades personalizadas
            self::removeCustomCapabilities();
            
            // Eliminar páginas creadas por el plugin
            self::deleteCreatedPages();
            
            // Log de desinstalación
            error_log('Club Psychology Pro: Plugin desinstalado completamente');
            
        } catch (\Exception $e) {
            error_log('Club Psychology Pro - Error en desinstalación: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar tablas personalizadas
     * 
     * @return void
     */
    private static function dropCustomTables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'cpp_test_stats',
            $wpdb->prefix . 'cpp_whatsapp_config',
            $wpdb->prefix . 'cpp_email_logs'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Eliminar opciones del plugin
     * 
     * @return void
     */
    private static function deleteOptions(): void {
        global $wpdb;
        
        // Eliminar opciones que empiezan con 'cpp_'
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'cpp_%'"
        );
        
        // Eliminar transients relacionados
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cpp_%'"
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cpp_%'"
        );
    }
    
    /**
     * Eliminar posts y metadatos del plugin
     * 
     * @return void
     */
    private static function deletePostsAndMeta(): void {
        global $wpdb;
        
        $post_types = [
            'cpp_test',
            'cpp_result_bf',
            'cpp_result_cohesion',
            'cpp_result_monitor'
        ];
        
        foreach ($post_types as $post_type) {
            // Obtener todos los posts de este tipo
            $posts = get_posts([
                'post_type' => $post_type,
                'numberposts' => -1,
                'post_status' => 'any'
            ]);
            
            // Eliminar cada post y sus metadatos
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
        }
        
        // Limpiar metadatos huérfanos
        $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm 
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE p.ID IS NULL"
        );
    }
    
    /**
     * Eliminar capacidades personalizadas
     * 
     * @return void
     */
    private static function removeCustomCapabilities(): void {
        $capabilities = [
            'manage_psychology_tests',
            'view_all_test_results',
            'export_test_data',
            'manage_whatsapp_integration',
            'view_test_statistics'
        ];
        
        // Obtener todos los roles
        $roles = wp_roles();
        
        foreach ($roles->roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    /**
     * Eliminar páginas creadas por el plugin
     * 
     * @return void
     */
    private static function deleteCreatedPages(): void {
        $page_options = [
            'cpp_page_haz_tu_test',
            'cpp_page_test_cohesion',
            'cpp_page_monitor_test',
            'cpp_page_resultado_cohesion',
            'cpp_page_resultado_monitor',
            'cpp_page_panel_tests'
        ];
        
        foreach ($page_options as $option) {
            $page_id = get_option($option);
            if ($page_id) {
                wp_delete_post($page_id, true);
                delete_option($option);
            }
        }
    }
}