<?php

namespace ClubPsychologyPro\Core;

use Dotenv\Dotenv;
use InvalidArgumentException;

class ConfigManager
{
    /**
     * @var ConfigManager
     */
    private static $instance;

    /**
     * @var array
     */
    private $settings = [];

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct()
    {
        // Load defaults first
        $this->settings = $this->getDefaultSettings();

        // Load .env into $_ENV if phpdotenv is available
        $this->loadEnv();

        // Override with environment variables
        $this->applyEnvOverrides();

        // Finally override with values stored in WP options
        $this->applyWpOptions();
    }

    /**
     * Retrieve the singleton instance.
     *
     * @return ConfigManager
     */
    public static function getInstance(): ConfigManager
    {
        if (null === self::$instance) {
            self::$instance = new ConfigManager();
        }
        return self::$instance;
    }

    /**
     * Get a configuration value by key.
     *
     * @param string $key     The configuration key (dot-notation supported).
     * @param mixed  $default Fallback value if key not found.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = $this->settings;

        foreach ($parts as $part) {
            if (! is_array($value) || ! array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Set a configuration value at runtime (won't persist to DB).
     *
     * @param string $key   The configuration key (dot-notation supported).
     * @param mixed  $value The value to set.
     */
    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $ref =& $this->settings;

        while (count($parts) > 1) {
            $part = array_shift($parts);
            if (! isset($ref[$part]) || ! is_array($ref[$part])) {
                $ref[$part] = [];
            }
            $ref =& $ref[$part];
        }

        $ref[array_shift($parts)] = $value;
    }

    /**
     * Load environment variables from .env (if library present).
     *
     * Expects a `.env` file at plugin root.
     */
    private function loadEnv(): void
    {
        if (class_exists(Dotenv::class)) {
            try {
                $dotenv = Dotenv::createImmutable(
                    dirname(__DIR__, 2),
                    '.env'
                );
                $dotenv->safeLoad();
            } catch (\Exception $e) {
                // fail silently if .env missing or invalid
            }
        }
    }

    /**
     * Override default settings with ENV values.
     */
    private function applyEnvOverrides(): void
    {
        // Map of ENV keys to config keys
        $map = [
            'DB_HOST'             => 'database.host',
            'DB_NAME'             => 'database.name',
            'DB_USER'             => 'database.user',
            'DB_PASS'             => 'database.pass',
            'WHATSAPP_ENABLED'    => 'whatsapp.enabled',
            'WHATSAPP_SERVICE_URL'=> 'whatsapp.service_url',
            'SMTP_HOST'           => 'email.smtp.host',
            'SMTP_PORT'           => 'email.smtp.port',
            'SMTP_USER'           => 'email.smtp.user',
            'SMTP_PASS'           => 'email.smtp.pass',
            'OPENAI_API_KEY'      => 'openai.api_key',
        ];

        foreach ($map as $envKey => $configKey) {
            if (getenv($envKey) !== false) {
                $this->set($configKey, getenv($envKey));
            }
        }
    }

    /**
     * Override settings with values saved in WP options.
     */
    private function applyWpOptions(): void
    {
        $opt = get_option( 'cpp_plugin_settings', [] );
        if (! is_array( $opt ) ) {
            return;
        }
        // Recursive merge
        $this->settings = array_replace_recursive( $this->settings, $opt );
    }

    /**
     * Persist current settings into WP options.
     *
     * @return bool True on success.
     */
    public function saveToDatabase(): bool
    {
        return update_option( 'cpp_plugin_settings', $this->settings );
    }

    /**
     * Register plugin settings in WP admin.
     */
    public function registerSettings(): void
    {
        add_action( 'admin_init', function() {
            register_setting(
                'cpp_general',
                'cpp_plugin_settings',
                [ $this, 'validateSettings' ]
            );
            add_settings_section(
                'cpp_section_general',
                __( 'General Settings', 'club-psychology-pro' ),
                '__return_false',
                'cpp_general'
            );
            add_settings_field(
                'cpp_site_mode',
                __( 'Site Mode', 'club-psychology-pro' ),
                function() {
                    $value = esc_attr( $this->get('site.mode', 'production') );
                    printf(
                        '<select name="cpp_plugin_settings[site][mode]">
                            <option value="production"%s>Production</option>
                            <option value="staging"%s>Staging</option>
                            <option value="development"%s>Development</option>
                        </select>',
                        selected($value,'production',false),
                        selected($value,'staging',false),
                        selected($value,'development',false)
                    );
                },
                'cpp_general',
                'cpp_section_general'
            );
        } );
    }

    /**
     * Sanitize & validate settings before saving.
     *
     * @param array $input Raw input.
     * @return array Sanitized.
     */
    public function validateSettings( $input ): array
    {
        $valid = [];

        // Example: sanitize site.mode
        if ( isset( $input['site']['mode'] ) && in_array( $input['site']['mode'], ['production','staging','development'], true ) ) {
            $valid['site']['mode'] = $input['site']['mode'];
        } else {
            $valid['site']['mode'] = $this->get('site.mode');
        }

        // Add further sanitization for email, whatsapp, etc.

        return array_replace_recursive( $this->settings, $valid );
    }

    /**
     * Default configuration values.
     *
     * @return array
     */
    private function getDefaultSettings(): array
    {
        return [
            'version' => '2.0.0',
            'site'    => [
                'mode' => 'production',
            ],
            'database' => [
                'host' => DB_HOST ?? 'localhost',
                'name' => DB_NAME ?? '',
                'user' => DB_USER ?? '',
                'pass' => DB_PASSWORD ?? '',
            ],
            'whatsapp' => [
                'enabled'      => false,
                'service_url'  => '',
            ],
            'email' => [
                'smtp' => [
                    'host' => '',
                    'port' => 587,
                    'user' => '',
                    'pass' => '',
                ],
            ],
            'openai' => [
                'api_key' => '',
            ],
        ];
    }
}
