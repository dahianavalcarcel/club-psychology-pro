<?php
/**
 * Funciones helper globales para Club Psychology Pro
 * 
 * Este archivo contiene funciones utilitarias que están disponibles globalmente
 * y que facilitan el uso del plugin desde cualquier parte del código.
 * 
 * @package ClubPsychologyPro
 * @since 2.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('¡Acceso directo no permitido!');
}

// =============================================
// FUNCIONES DE ACCESO AL PLUGIN
// =============================================

if (!function_exists('cpp_plugin')) {
    /**
     * Obtener instancia del plugin principal
     * 
     * @return \ClubPsychologyPro\Core\Plugin|null
     */
    function cpp_plugin() {
        if (class_exists('ClubPsychologyPro\\Core\\Plugin')) {
            return \ClubPsychologyPro\Core\Plugin::getInstance();
        }
        return null;
    }
}

if (!function_exists('cpp_container')) {
    /**
     * Obtener el container de dependencias
     * 
     * @return \ClubPsychologyPro\Core\Container|null
     */
    function cpp_container() {
        $plugin = cpp_plugin();
        return $plugin ? $plugin->getContainer() : null;
    }
}

if (!function_exists('cpp_manager')) {
    /**
     * Obtener un manager específico
     * 
     * @param string $name Nombre del manager (tests, users, email, etc.)
     * @return mixed|null
     */
    function cpp_manager(string $name) {
        $plugin = cpp_plugin();
        return $plugin ? $plugin->getManager($name) : null;
    }
}

// =============================================
// FUNCIONES DE CONFIGURACIÓN
// =============================================

if (!function_exists('cpp_config')) {
    /**
     * Obtener configuración del plugin
     * 
     * @param string $key Clave de configuración (usar notación de punto: 'tests.defaults.time_limit')
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    function cpp_config(string $key = '', $default = null) {
        static $config = null;
        
        // Cargar configuración si no está cargada
        if ($config === null) {
            $config_file = CPP_CONFIG_DIR . 'app.php';
            $config = file_exists($config_file) ? include $config_file : [];
        }
        
        // Si no se especifica clave, devolver toda la configuración
        if (empty($key)) {
            return $config;
        }
        
        // Soportar notación de punto para acceso anidado
        return cpp_array_get($config, $key, $default);
    }
}

if (!function_exists('cpp_set_config')) {
    /**
     * Establecer valor de configuración en runtime
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function cpp_set_config(string $key, $value): void {
        // Esta función modifica la configuración en memoria solamente
        // Para cambios persistentes, usar las opciones de WordPress
        $plugin = cpp_plugin();
        if ($plugin && method_exists($plugin, 'setConfig')) {
            $plugin->setConfig($key, $value);
        }
    }
}

// =============================================
// FUNCIONES DE TESTS
// =============================================

if (!function_exists('cpp_get_test')) {
    /**
     * Obtener un test por ID
     * 
     * @param int $test_id
     * @return \WP_Post|null
     */
    function cpp_get_test(int $test_id): ?\WP_Post {
        $post = get_post($test_id);
        
        if (!$post || !in_array($post->post_type, ['cpp_test', 'test_personalidad'])) {
            return null;
        }
        
        return $post;
    }
}

if (!function_exists('cpp_get_test_type')) {
    /**
     * Obtener tipo de test
     * 
     * @param string $type_name
     * @return \ClubPsychologyPro\Tests\Interfaces\TestTypeInterface|null
     */
    function cpp_get_test_type(string $type_name) {
        $test_manager = cpp_manager('tests');
        return $test_manager ? $test_manager->getTestType($type_name) : null;
    }
}

if (!function_exists('cpp_get_test_calculator')) {
    /**
     * Obtener calculadora de test
     * 
     * @param string $type_name
     * @return \ClubPsychologyPro\Tests\Interfaces\CalculatorInterface|null
     */
    function cpp_get_test_calculator(string $type_name) {
        $test_manager = cpp_manager('tests');
        return $test_manager ? $test_manager->getCalculator($type_name) : null;
    }
}

if (!function_exists('cpp_create_test')) {
    /**
     * Crear un nuevo test
     * 
     * @param array $args Argumentos del test
     * @return int|false ID del test creado o false en error
     */
    function cpp_create_test(array $args) {
        $test_manager = cpp_manager('tests');
        return $test_manager ? $test_manager->createTest($args) : false;
    }
}

if (!function_exists('cpp_get_test_url')) {
    /**
     * Obtener URL para realizar un test
     * 
     * @param int $test_id
     * @param array $args Argumentos adicionales
     * @return string
     */
    function cpp_get_test_url(int $test_id, array $args = []): string {
        $base_url = cpp_config('tests.pages.test_form_url', home_url('/realizar-test/'));
        $args['test_id'] = $test_id;
        
        return add_query_arg($args, $base_url);
    }
}

if (!function_exists('cpp_get_result_url')) {
    /**
     * Obtener URL para ver resultados
     * 
     * @param int $result_id
     * @return string
     */
    function cpp_get_result_url(int $result_id): string {
        $base_url = cpp_config('tests.pages.results_url', home_url('/resultados/'));
        
        return add_query_arg(['id' => $result_id], $base_url);
    }
}

// =============================================
// FUNCIONES DE USUARIOS
// =============================================

if (!function_exists('cpp_get_user_test_limit')) {
    /**
     * Obtener límite de tests para un usuario
     * 
     * @param int|null $user_id ID del usuario (null para usuario actual)
     * @return int
     */
    function cpp_get_user_test_limit(?int $user_id = null): int {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 0;
        }
        
        $user_manager = cpp_manager('users');
        return $user_manager ? $user_manager->getUserTestLimit($user_id) : 0;
    }
}

if (!function_exists('cpp_can_user_take_test')) {
    /**
     * Verificar si un usuario puede tomar un test
     * 
     * @param int|null $user_id
     * @param string $test_type
     * @return bool
     */
    function cpp_can_user_take_test(?int $user_id = null, string $test_type = ''): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user_manager = cpp_manager('users');
        return $user_manager ? $user_manager->canUserTakeTest($user_id, $test_type) : false;
    }
}

// =============================================
// FUNCIONES DE UTILIDAD
// =============================================

if (!function_exists('cpp_array_get')) {
    /**
     * Obtener valor de array usando notación de punto
     * 
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function cpp_array_get(array $array, string $key, $default = null) {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        // Soportar notación de punto
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $array;
            
            foreach ($keys as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return $default;
                }
                $value = $value[$segment];
            }
            
            return $value;
        }
        
        return $default;
    }
}

if (!function_exists('cpp_array_set')) {
    /**
     * Establecer valor en array usando notación de punto
     * 
     * @param array &$array
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function cpp_array_set(array &$array, string $key, $value): void {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        
        $current = $value;
    }
}

if (!function_exists('cpp_log')) {
    /**
     * Registrar mensaje en el log
     * 
     * @param string $message
     * @param string $level
     * @param array $context
     * @return void
     */
    function cpp_log(string $message, string $level = 'info', array $context = []): void {
        if (!cpp_config('logging.enabled', true)) {
            return;
        }
        
        $min_level = cpp_config('logging.level', 'error');
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        
        if (($levels[$level] ?? 1) < ($levels[$min_level] ?? 1)) {
            return;
        }
        
        $log_message = sprintf(
            '[%s] [%s] %s %s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CPP: {$log_message}");
        }
        
        // Aquí se puede agregar logging a archivo o base de datos
        do_action('cpp_log', $message, $level, $context);
    }
}

if (!function_exists('cpp_sanitize_test_responses')) {
    /**
     * Sanitizar respuestas de test
     * 
     * @param array $responses
     * @return array
     */
    function cpp_sanitize_test_responses(array $responses): array {
        $sanitized = [];
        
        foreach ($responses as $key => $value) {
            $clean_key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$clean_key] = cpp_sanitize_test_responses($value);
            } elseif (is_numeric($value)) {
                $sanitized[$clean_key] = (float) $value;
            } else {
                $sanitized[$clean_key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}

if (!function_exists('cpp_format_score')) {
    /**
     * Formatear puntuación para mostrar
     * 
     * @param float $score
     * @param int $decimals
     * @return string
     */
    function cpp_format_score(float $score, int $decimals = 2): string {
        return number_format($score, $decimals, '.', '');
    }
}

if (!function_exists('cpp_get_score_level')) {
    /**
     * Obtener nivel basado en puntuación
     * 
     * @param float $score
     * @param array $ranges
     * @return string
     */
    function cpp_get_score_level(float $score, array $ranges): string {
        foreach ($ranges as $range) {
            if (isset($range['min'], $range['max'], $range['nivel']) &&
                $score >= $range['min'] && $score <= $range['max']) {
                return $range['nivel'];
            }
        }
        
        return 'Indeterminado';
    }
}

// =============================================
// FUNCIONES DE COMPATIBILIDAD CON CÓDIGO LEGACY
// =============================================

if (!function_exists('get_monitor_test_config')) {
    /**
     * Compatibilidad con código legacy
     * 
     * @param string $type
     * @return array|false
     */
    function get_monitor_test_config(string $type) {
        $test_type = cpp_get_test_type($type);
        return $test_type ? $test_type->getConfiguration() : false;
    }
}

if (!function_exists('get_test_url')) {
    /**
     * Compatibilidad con código legacy
     * 
     * @param int $test_id
     * @return string
     */
    function get_test_url(int $test_id): string {
        return cpp_get_test_url($test_id);
    }
}

if (!function_exists('get_tests_data_extended')) {
    /**
     * Compatibilidad con código legacy
     * 
     * @return array
     */
    function get_tests_data_extended(): array {
        $test_manager = cpp_manager('tests');
        return $test_manager ? $test_manager->getUserTests() : [];
    }
}

// =============================================
// FUNCIONES DE DESARROLLO Y DEBUG
// =============================================

if (!function_exists('cpp_debug')) {
    /**
     * Debug helper - solo funciona en modo debug
     * 
     * @param mixed $data
     * @param string $label
     * @return void
     */
    function cpp_debug($data, string $label = 'Debug'): void {
        if (!cpp_config('development.debug_mode', false)) {
            return;
        }
        
        if (function_exists('dump')) {
            dump($label . ':', $data);
        } else {
            error_log("CPP Debug - {$label}: " . print_r($data, true));
        }
    }
}

if (!function_exists('cpp_dd')) {
    /**
     * Debug and die - solo funciona en modo debug
     * 
     * @param mixed $data
     * @param string $label
     * @return void
     */
    function cpp_dd($data, string $label = 'Debug & Die'): void {
        if (!cpp_config('development.debug_mode', false)) {
            return;
        }
        
        cpp_debug($data, $label);
        wp_die('CPP Debug & Die called');
    }
}

if (!function_exists('cpp_is_dev_mode')) {
    /**
     * Verificar si estamos en modo desarrollo
     * 
     * @return bool
     */
    function cpp_is_dev_mode(): bool {
        return cpp_config('development.debug_mode', false) || 
               (defined('WP_DEBUG') && WP_DEBUG);
    }
}

// =============================================
// FUNCIONES DE CACHE
// =============================================

if (!function_exists('cpp_cache_get')) {
    /**
     * Obtener valor del cache
     * 
     * @param string $key
     * @param string $group
     * @return mixed|false
     */
    function cpp_cache_get(string $key, string $group = 'cpp') {
        if (!cpp_config('cache.enabled', true)) {
            return false;
        }
        
        return wp_cache_get($key, $group);
    }
}

if (!function_exists('cpp_cache_set')) {
    /**
     * Establecer valor en cache
     * 
     * @param string $key
     * @param mixed $data
     * @param string $group
     * @param int $expiration
     * @return bool
     */
    function cpp_cache_set(string $key, $data, string $group = 'cpp', int $expiration = 0): bool {
        if (!cpp_config('cache.enabled', true)) {
            return false;
        }
        
        if (!$expiration) {
            $expiration = cpp_config('cache.default_ttl', 3600);
        }
        
        return wp_cache_set($key, $data, $group, $expiration);
    }
}

if (!function_exists('cpp_cache_delete')) {
    /**
     * Eliminar valor del cache
     * 
     * @param string $key
     * @param string $group
     * @return bool
     */
    function cpp_cache_delete(string $key, string $group = 'cpp'): bool {
        return wp_cache_delete($key, $group);
    }
}

// =============================================
// HOOKS Y FILTERS
// =============================================

/**
 * Hook que se ejecuta cuando las funciones helper están disponibles
 */
do_action('cpp_helpers_loaded');