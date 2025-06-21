<?php
/**
 * SettingsPage.php
 *
 * Admin settings page for Club Psychology Pro.
 *
 * @package ClubPsychologyPro\UI\Admin
 */

namespace ClubPsychologyPro\UI\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class SettingsPage
 *
 * Registers and renders the plugin settings page in the WP admin.
 */
class SettingsPage {
    /**
     * Settings group/option name
     *
     * @var string
     */
    private $option_name = 'cpp_settings';

    /**
     * Initialize hooks
     */
    public function register() {
        add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    /**
     * Add settings submenu under Psychology Pro menu
     */
    public function addAdminMenu() {
        add_submenu_page(
            'cpp-dashboard',                          // parent slug
            __( 'Settings', 'club-psychology-pro' ),  // page title
            __( 'Settings', 'club-psychology-pro' ),  // menu title
            'manage_options',                         // capability
            'cpp-settings',                           // menu slug
            [ $this, 'renderSettingsPage' ]
        );
    }

    /**
     * Register plugin settings, sections, and fields
     */
    public function registerSettings() {
        // Register the option
        register_setting(
            $this->option_name,                       // option group
            $this->option_name,                       // option name
            [ $this, 'sanitizeSettings' ]             // sanitize callback
        );

        // General Section
        add_settings_section(
            'cpp_general_section',
            __( 'General Settings', 'club-psychology-pro' ),
            [ $this, 'generalSectionCallback' ],
            $this->option_name
        );

        add_settings_field(
            'site_logo_url',
            __( 'Site Logo URL', 'club-psychology-pro' ),
            [ $this, 'fieldInputCallback' ],
            $this->option_name,
            'cpp_general_section',
            [
                'label_for'   => 'site_logo_url',
                'type'        => 'url',
                'description' => __( 'URL to the plugin header logo', 'club-psychology-pro' ),
            ]
        );

        add_settings_field(
            'enable_logging',
            __( 'Enable Debug Logging', 'club-psychology-pro' ),
            [ $this, 'fieldCheckboxCallback' ],
            $this->option_name,
            'cpp_general_section',
            [
                'label_for'   => 'enable_logging',
                'description' => __( 'Toggle detailed debug logs for troubleshooting.', 'club-psychology-pro' ),
            ]
        );

        // WhatsApp Section
        add_settings_section(
            'cpp_whatsapp_section',
            __( 'WhatsApp Integration', 'club-psychology-pro' ),
            [ $this, 'whatsappSectionCallback' ],
            $this->option_name
        );

        add_settings_field(
            'whatsapp_enabled',
            __( 'Enable WhatsApp', 'club-psychology-pro' ),
            [ $this, 'fieldCheckboxCallback' ],
            $this->option_name,
            'cpp_whatsapp_section',
            [
                'label_for'   => 'whatsapp_enabled',
                'description' => __( 'Enable notifications via WhatsApp service.', 'club-psychology-pro' ),
            ]
        );

        add_settings_field(
            'whatsapp_service_url',
            __( 'WhatsApp Service URL', 'club-psychology-pro' ),
            [ $this, 'fieldInputCallback' ],
            $this->option_name,
            'cpp_whatsapp_section',
            [
                'label_for'   => 'whatsapp_service_url',
                'type'        => 'url',
                'description' => __( 'Base URL of the Node.js WhatsApp service.', 'club-psychology-pro' ),
            ]
        );
    }

    /**
     * Sanitize and validate settings
     *
     * @param array $input Raw input.
     * @return array Sanitized input.
     */
    public function sanitizeSettings( $input ) {
        $sanitized = [];

        $sanitized['site_logo_url']       = isset( $input['site_logo_url'] ) ? esc_url_raw( $input['site_logo_url'] ) : '';
        $sanitized['enable_logging']      = ! empty( $input['enable_logging'] ) ? 1 : 0;
        $sanitized['whatsapp_enabled']    = ! empty( $input['whatsapp_enabled'] ) ? 1 : 0;
        $sanitized['whatsapp_service_url'] = isset( $input['whatsapp_service_url'] ) ? esc_url_raw( $input['whatsapp_service_url'] ) : '';

        return $sanitized;
    }

    /**
     * General section description
     */
    public function generalSectionCallback() {
        echo '<p>' . esc_html__( 'Basic plugin settings.', 'club-psychology-pro' ) . '</p>';
    }

    /**
     * WhatsApp section description
     */
    public function whatsappSectionCallback() {
        echo '<p>' . esc_html__( 'Configure WhatsApp notification integration.', 'club-psychology-pro' ) . '</p>';
    }

    /**
     * Render text/url/input field
     *
     * @param array $args Field args.
     */
    public function fieldInputCallback( $args ) {
        $options = get_option( $this->option_name );
        $id      = $args['label_for'];
        $type    = isset( $args['type'] ) ? $args['type'] : 'text';
        $value   = isset( $options[ $id ] ) ? esc_attr( $options[ $id ] ) : '';

        printf(
            '<input type="%1$s" id="%2$s" name="%3$s[%2$s]" value="%4$s" class="regular-text" />',
            esc_attr( $type ),
            esc_attr( $id ),
            esc_attr( $this->option_name ),
            $value
        );

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render checkbox field
     *
     * @param array $args Field args.
     */
    public function fieldCheckboxCallback( $args ) {
        $options = get_option( $this->option_name );
        $id      = $args['label_for'];
        $checked = ! empty( $options[ $id ] ) ? 'checked' : '';

        printf(
            '<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
            esc_attr( $id ),
            esc_attr( $this->option_name ),
            esc_attr( $checked ),
            isset( $args['description'] ) ? esc_html( $args['description'] ) : ''
        );
    }

    /**
     * Render the settings page markup
     */
    public function renderSettingsPage() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Club Psychology Pro Settings', 'club-psychology-pro' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_name );
                do_settings_sections( $this->option_name );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
