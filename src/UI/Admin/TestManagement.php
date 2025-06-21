<?php

namespace ClubPsychologyPro\UI\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use ClubPsychologyPro\Tests\TestManager;
use WP_List_Table;

class TestManagement {
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    /**
     * Register the "Test Management" submenu under the Psychology Pro menu
     */
    public static function register_menu_page() {
        add_submenu_page(
            'psychology-pro',           // Parent slug
            __( 'Test Management', 'club-psychology-pro' ),
            __( 'Tests', 'club-psychology-pro' ),
            'manage_options',
            'cpp-test-management',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Enqueue admin-specific CSS/JS
     */
    public static function enqueue_assets( $hook ) {
        if ( 'psychology-pro_page_cpp-test-management' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'cpp-admin-tests', plugins_url( 'assets/css/admin-tests.css', dirname( __FILE__, 4 ) ), [], '1.0.0' );
        wp_enqueue_script( 'cpp-admin-tests', plugins_url( 'assets/js/admin-tests.js', dirname( __FILE__, 4 ) ), [ 'jquery' ], '1.0.0', true );
    }

    /**
     * Render the Test Management page
     */
    public static function render_page() {
        // Instantiate a custom List Table to show tests
        if ( ! class_exists( 'CPP_Tests_List_Table' ) ) {
            require_once plugin_dir_path( dirname( dirname( dirname( __FILE__ ) ) ) ) . 'src/UI/Admin/TestsListTable.php';
        }

        $list_table = new \CPP_Tests_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap cpp-admin-tests">
            <h1><?php esc_html_e( 'Manage Psychological Tests', 'club-psychology-pro' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cpp-test-editor&action=new' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Add New Test', 'club-psychology-pro' ); ?>
            </a>
            <form method="post">
                <?php
                // Search box
                $list_table->search_box( __( 'Search Tests', 'club-psychology-pro' ), 'cpp' );
                // Display the table
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize
TestManagement::init();
