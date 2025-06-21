<?php
/**
 * AdminDashboard.php
 *
 * Gestiona la interfaz administrativa para el panel de Monitor Tests.
 */

namespace MyPlugin\UI\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AdminDashboard {
    /**
     * Inicializa los hooks de admin
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    /**
     * Registra el menú y la página de dashboard en el admin
     */
    public static function register_menu() {
        add_menu_page(
            'Monitor Tests Dashboard',        // Page title
            'Monitor Tests',                  // Menu title
            'manage_options',                 // Capability
            'monitor-tests-dashboard',        // Menu slug
            [ __CLASS__, 'render_dashboard' ],// Callback
            'dashicons-chart-area',           // Icon
            6                                  // Position
        );
    }

    /**
     * Encola estilos y scripts necesarios para el dashboard
     */
    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_monitor-tests-dashboard' ) {
            return;
        }
        // Estilos
        wp_enqueue_style(
            'monitor-tests-admin',
            plugin_dir_url( __DIR__ ) . '../assets/css/admin-dashboard.css',
            [],
            '1.0.0'
        );
        
        // Scripts
        wp_enqueue_script(
            'monitor-tests-admin-js',
            plugin_dir_url( __DIR__ ) . '../assets/js/admin-dashboard.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
    }

    /**
     * Renderiza la página HTML del dashboard
     */
    public static function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>Monitor Tests Dashboard</h1>
            <p>Bienvenido al panel de gestión de resultados y estadísticas de Monitor Tests.</p>

            <?php
            // Query de resultados de Monitor Test
            $args = [
                'post_type'      => 'resultado_monitor',
                'posts_per_page' => 20,
                'post_status'    => 'publish',
            ];
            $query = new \WP_Query( $args );

            if ( $query->have_posts() ) :
                echo '<table class="wp-list-table widefat striped">';
                echo '<thead><tr>' .
                     '<th>ID</th>' .
                     '<th>Usuario</th>' .
                     '<th>Tipo de Test</th>' .
                     '<th>Puntaje</th>' .
                     '<th>Nivel</th>' .
                     '<th>Fecha</th>' .
                     '</tr></thead>';
                echo '<tbody>';
                while ( $query->have_posts() ) : $query->the_post();
                    $post_id   = get_the_ID();
                    $user      = get_userdata( get_post_field( 'post_author', $post_id ) );
                    $tipo      = get_post_meta( $post_id, 'tipo_test', true );
                    $puntaje   = get_post_meta( $post_id, 'puntaje_total', true );
                    $nivel     = get_post_meta( $post_id, 'nivel_resultado', true );
                    $fecha     = get_post_meta( $post_id, 'fecha_resultado', true );

                    printf(
                        '<tr>' .
                        '<td>%d</td>' .
                        '<td>%s</td>' .
                        '<td>%s</td>' .
                        '<td>%s</td>' .
                        '<td>%s</td>' .
                        '<td>%s</td>' .
                        '</tr>',
                        esc_html( $post_id ),
                        esc_html( $user->display_name ),
                        esc_html( ucfirst( $tipo ) ),
                        esc_html( $puntaje ),
                        esc_html( $nivel ),
                        esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $fecha ) ) )
                    );
                endwhile;
                echo '</tbody></table>';
                wp_reset_postdata();
            else :
                echo '<p>No se encontraron resultados recientes.</p>';
            endif;
            ?>
        </div>
        <?php
    }
}

// Inicializar al cargar admin
AdminDashboard::init();
