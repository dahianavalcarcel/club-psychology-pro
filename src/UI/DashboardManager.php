<?php
namespace ClubPsychologyPro\UI;

use ClubPsychologyPro\Core\Plugin;

/**
 * Class DashboardManager
 *
 * Admin dashboard manager: registers menu pages, enqueues assets and renders views.
 */
class DashboardManager
{
    /**
     * Initialize hooks
     */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    /**
     * Register the main dashboard and its subpages
     */
    public static function registerAdminMenu(): void
    {
        $capability = 'manage_options';
        $slug       = 'cpp_dashboard';

        // Main menu page
        add_menu_page(
            __('Psychology Pro', Plugin::TEXT_DOMAIN),
            __('Psychology Pro', Plugin::TEXT_DOMAIN),
            $capability,
            $slug,
            [self::class, 'renderDashboard'],
            'dashicons-admin-site-alt3',
            60
        );

        // Submenu: Test Management
        add_submenu_page(
            $slug,
            __('Manage Tests', Plugin::TEXT_DOMAIN),
            __('Tests', Plugin::TEXT_DOMAIN),
            $capability,
            'cpp_test_management',
            [TestManagement::class, 'render']
        );

        // Submenu: User Management
        add_submenu_page(
            $slug,
            __('Manage Users', Plugin::TEXT_DOMAIN),
            __('Users', Plugin::TEXT_DOMAIN),
            $capability,
            'cpp_user_management',
            [UserManagement::class, 'render']
        );

        // Submenu: Settings
        add_submenu_page(
            $slug,
            __('Settings', Plugin::TEXT_DOMAIN),
            __('Settings', Plugin::TEXT_DOMAIN),
            $capability,
            'cpp_settings',
            [SettingsPage::class, 'render']
        );
    }

    /**
     * Enqueue admin styles and scripts
     *
     * @param string $hook_suffix
     */
    public static function enqueueAssets(string $hook_suffix): void
    {
        // Only load on our plugin pages
        if (!in_array($hook_suffix, [
            'toplevel_page_cpp_dashboard',
            'cpp_page_cpp_test_management',
            'cpp_page_cpp_user_management',
            'cpp_page_cpp_settings',
        ], true)) {
            return;
        }

        $version = Plugin::VERSION;
        $urlBase = Plugin::assetUrl('admin');

        // Styles
        wp_enqueue_style(
            'cpp-admin-styles',
            "$urlBase/css/admin.css",
            [],
            $version
        );

        // Scripts
        wp_enqueue_script(
            'cpp-admin-scripts',
            "$urlBase/js/admin.js",
            ['jquery'],
            $version,
            true
        );
    }

    /**
     * Render the dashboard overview page
     */
    public static function renderDashboard(): void
    {
        // Pass data to view if needed
        $stats = [
            'total_tests' => TestManager::countAll(),
            'active_users'=> UserManager::countActive(),
            'sent_notifications' => WhatsAppManager::countSent(),
        ];

        include Plugin::template('admin/dashboard-overview.php', $stats);
    }
}
