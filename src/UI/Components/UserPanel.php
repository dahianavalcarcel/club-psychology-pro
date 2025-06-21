<?php

namespace ClubPsychologyPro\UI\Components;

use ClubPsychologyPro\UI\Components\AbstractComponent;
use ClubPsychologyPro\Users\UserManager;

class UserPanel extends AbstractComponent
{
    /**
     * Hook registrations.
     */
    public function register(): void
    {
        add_shortcode( 'cpp_user_panel', [ $this, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ] );
    }

    /**
     * Enqueue front-end assets for the user panel.
     */
    public function enqueueAssets(): void
    {
        if ( is_page() && has_shortcode( get_post()->post_content, 'cpp_user_panel' ) ) {
            wp_enqueue_style(
                'cpp-user-panel',
                plugin_dir_url( dirname( __DIR__, 2 ) ) . 'assets/css/user-panel.css',
                [],
                CPP_PLUGIN_VERSION
            );
            wp_enqueue_script(
                'cpp-user-panel',
                plugin_dir_url( dirname( __DIR__, 2 ) ) . 'assets/js/user-panel.js',
                [ 'jquery' ],
                CPP_PLUGIN_VERSION,
                true
            );
        }
    }

    /**
     * Render the user panel.
     *
     * @param array $atts Shortcode attributes (unused).
     * @return string HTML content.
     */
    public function render( array $atts ): string
    {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to view your dashboard.', 'club-psychology-pro' ) . '</p>';
        }

        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;

        // Fetch user's tests and results
        $tests_data = UserManager::getInstance()->getUserTestsData( $user_id );

        ob_start();
        ?>
        <div class="cpp-user-panel">
            <h2><?php esc_html_e( 'My Evaluations', 'club-psychology-pro' ); ?></h2>

            <?php if ( empty( $tests_data ) ) : ?>
                <p><?php esc_html_e( 'You have not requested any tests yet.', 'club-psychology-pro' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/request-test' ) ); ?>" class="cpp-btn">
                    <?php esc_html_e( 'Request a Test', 'club-psychology-pro' ); ?>
                </a>
            <?php else : ?>
                <table class="cpp-user-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Test Name', 'club-psychology-pro' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'club-psychology-pro' ); ?></th>
                            <th><?php esc_html_e( 'Date Requested', 'club-psychology-pro' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'club-psychology-pro' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'club-psychology-pro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tests_data as $test ) : ?>
                            <tr>
                                <td><?php echo esc_html( $test['title'] ); ?></td>
                                <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $test['tipo'] ) ) ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $test['fecha'] ) ) ); ?></td>
                                <td><?php echo esc_html( ucfirst( $test['estado'] ) ); ?></td>
                                <td>
                                    <?php if ( ! $test['resultado'] ) : ?>
                                        <a href="<?php echo esc_url( add_query_arg( [
                                            'cpp_action' => 'start_test',
                                            'test_id'    => $test['id'],
                                            'type'       => $test['subtipo'],
                                        ], home_url() ) ); ?>" class="cpp-btn cpp-btn-secondary">
                                            <?php esc_html_e( 'Take Test', 'club-psychology-pro' ); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( add_query_arg( [
                                            'cpp_action'   => 'view_result',
                                            'result_id'    => $test['resultado_id'],
                                        ], home_url() ) ); ?>" class="cpp-btn cpp-btn-primary">
                                            <?php esc_html_e( 'View Result', 'club-psychology-pro' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
