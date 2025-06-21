<?php
namespace ClubPsychologyPro\UI\Admin;

use ClubPsychologyPro\UI\Admin\UsersListTable;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class UserManagement
 *
 * Admin UI for managing users and subscriptions.
 */
class UserManagement {

    /**
     * Constructor hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register submenu under the main Psychology Pro menu.
     */
    public function register_menu() {
        add_submenu_page(
            'psychology-pro', // Parent slug
            __( 'User Management', 'cpp' ),
            __( 'Users', 'cpp' ),
            'manage_options',
            'cpp-user-management',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Enqueue admin-specific CSS and JS for the user management page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( 'psychology-pro_page_cpp-user-management' !== $hook ) {
            return;
        }

        // Stylesheet for users table
        wp_enqueue_style(
            'cpp-admin-user-management',
            CPP_ASSETS_URL . 'css/admin-user-management.css',
            [],
            CPP_VERSION
        );

        // Script for AJAX filtering, bulk actions, etc.
        wp_enqueue_script(
            'cpp-admin-user-management',
            CPP_ASSETS_URL . 'js/admin-user-management.js',
            [ 'jquery' ],
            CPP_VERSION,
            true
        );
    }

    /**
     * Render the User Management admin page.
     */
    public function render_page() {
        // Instantiate the list table class
        $users_table = new UsersListTable();
        $users_table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'User Management', 'cpp' ) . '</h1>';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=cpp-add-user' ) ) . '" class="page-title-action">'
             . esc_html__( 'Add New User', 'cpp' ) . '</a>';
        echo '<form method="post">';
        $users_table->display();
        echo '</form>';
        echo '</div>';
    }
}

// Initialize
new UserManagement();
