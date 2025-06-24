<?php
namespace ClubPsychologyPro\UI;

use ClubPsychologyPro\Core\Plugin;
use ClubPsychologyPro\Tests\TestManager;
use ClubPsychologyPro\Users\UserManager;
use ClubPsychologyPro\WhatsApp\WhatsAppManager;
use ClubPsychologyPro\UI\Admin\TestManagement;
use ClubPsychologyPro\UI\Admin\UserManagement;
use ClubPsychologyPro\UI\Admin\SettingsPage;

/**
 * Class DashboardManager
 *
 * Admin dashboard manager: registers menu pages, enqueues assets and renders views.
 */
class DashboardManager
{
    /**
     * Hookea los métodos de este manager a WordPress.
     */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    /**
     * Registra el menú principal y sus subpáginas.
     */
    public static function registerAdminMenu(): void
    {
        $capability = 'manage_options';
        $slug       = 'cpp_dashboard';

        // Menú principal
        add_menu_page(
            __('Psychology Pro', CPP_TEXT_DOMAIN),
            __('Psychology Pro', CPP_TEXT_DOMAIN),
            $capability,
            $slug,
            [self::class, 'renderDashboard'],
            'dashicons-admin-site-alt3',
            60
        );

        // Submenú: Gestión de Tests
        add_submenu_page(
            $slug,
            __('Manage Tests', CPP_TEXT_DOMAIN),
            __('Tests', CPP_TEXT_DOMAIN),
            $capability,
            'cpp_test_management',
            [TestManagement::class, 'render']
        );

        // Submenú: Gestión de Usuarios
        add_submenu_page(
            $slug,
            __('Manage Users', CPP_TEXT_DOMAIN),
            __('Users', CPP_TEXT_DOMAIN),
            $capability,
            'cpp_user_management',
            [UserManagement::class, 'render']
        );

        // Submenú: Configuraciones
        add_submenu_page(
            $slug,
            __('Settings', CPP_TEXT_DOMAIN),
            __('Settings', CPP_TEXT_DOMAIN),
            $capability,
            'cpp_settings',
            [SettingsPage::class, 'render']
        );
    }

    /**
     * Encola estilos y scripts solo en las páginas de nuestro plugin.
     *
     * @param string $hook_suffix
     */
    public static function enqueueAssets(string $hook_suffix): void
    {
        if (! in_array($hook_suffix, [
            'toplevel_page_cpp_dashboard',
            'cpp_page_cpp_test_management',
            'cpp_page_cpp_user_management',
            'cpp_page_cpp_settings',
        ], true)) {
            return;
        }

        $version = CPP_VERSION;
        $urlBase = CPP_DIST_URL;

        // Estilos
        wp_enqueue_style(
            'cpp-admin-styles',
            "{$urlBase}css/admin.css",
            [],
            $version
        );

        // Scripts
        wp_enqueue_script(
            'cpp-admin-scripts',
            "{$urlBase}js/admin.js",
            ['jquery'],
            $version,
            true
        );
    }

    /**
     * Renderiza la página principal del dashboard.
     */
    public static function renderDashboard(): void
    {
        // Obtener estadísticas con los managers
        $stats = [
            'total_tests'        => TestManager::countAll(),
            'active_users'       => UserManager::countActive(),
            'sent_notifications' => WhatsAppManager::countSent(),
        ];

        // Carga la plantilla pasando $stats
        include Plugin::template('admin/dashboard-overview.php', $stats);
    }
}
