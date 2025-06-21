<?php
namespace MonitorTests;

/**
 * Class PermissionManager
 * 
 * Encapsula la gestión de capacidades y permisos necesarios para Monitor Tests.
 */
class PermissionManager {
    /**
     * Inicializa los ganchos necesarios para registrar capacidades.
     */
    public static function init() {
        add_action('init', [self::class, 'registerCapabilities'], 20);
    }

    /**
     * Registra capacidades personalizadas y las asigna a los roles adecuados.
     */
    public static function registerCapabilities() {
        // Roles que recibirán las capacidades
        $roles = [
            'administrator',
            // Añadir otros roles si es necesario
        ];

        // Capacidades personalizadas para Monitor Tests
        $caps = [
            'manage_monitor_tests',   // Registrar/editar tests
            'view_monitor_tests',     // Ver resultados
            'edit_monitor_results',   // Editar resultados
            'publish_monitor_results' // Publicar resultados
        ];

        foreach ($roles as $role_key) {
            $role = get_role($role_key);
            if (!$role) {
                continue;
            }
            foreach ($caps as $cap) {
                if (!$role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
        }
    }

    /**
     * Comprueba si el usuario actual puede gestionar los Monitor Tests.
     *
     * @return bool
     */
    public static function canManageTests() {
        return current_user_can('manage_monitor_tests');
    }

    /**
     * Comprueba si el usuario actual puede ver los resultados.
     *
     * @return bool
     */
    public static function canViewResults() {
        return current_user_can('view_monitor_tests');
    }

    /**
     * Comprueba si el usuario actual puede editar un resultado específico.
     *
     * @param int $post_id ID del post de tipo resultado_monitor
     * @return bool
     */
    public static function canEditResult($post_id) {
        if (!is_singular('resultado_monitor') && !get_post($post_id)) {
            return false;
        }
        return current_user_can('edit_monitor_results', $post_id);
    }
}

// Inicializar permisos
PermissionManager::init();
