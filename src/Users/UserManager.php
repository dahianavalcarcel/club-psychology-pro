<?php
/**
 * Gestor de usuarios para Club Psychology Pro
 *
 * @package ClubPsychologyPro\Users
 * @since 1.0.0
 */

namespace ClubPsychologyPro\Users;

use ClubPsychologyPro\Core\Container;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_User;
use WP_Query;

/**
 * Clase UserManager
 * 
 * Maneja la gestión de usuarios, tests y sus relaciones
 */
class UserManager {
    
    /**
     * Contenedor de dependencias
     * @var Container
     */
    private Container $container;
    
    /**
     * Planes de membresía disponibles
     * @var array
     */
    private array $membership_plans = [
        'free' => [
            'name' => 'Gratuito',
            'max_tests' => 1,
            'features' => ['basic_reports']
        ],
        'basic' => [
            'name' => 'Básico',
            'max_tests' => 3,
            'features' => ['basic_reports', 'email_notifications']
        ],
        'premium' => [
            'name' => 'Premium',
            'max_tests' => 10,
            'features' => ['basic_reports', 'email_notifications', 'advanced_reports', 'whatsapp_integration']
        ],
        'enterprise' => [
            'name' => 'Empresarial',
            'max_tests' => -1, // Ilimitado
            'features' => ['basic_reports', 'email_notifications', 'advanced_reports', 'whatsapp_integration', 'custom_branding', 'api_access']
        ]
    ];
    
    /**
     * Constructor
     * 
     * @param Container|null $container Contenedor de dependencias
     */
    public function __construct(?Container $container = null) {
        $this->container = $container ?? new Container();
    }
    
    /**
     * Inicializar el manager
     * 
     * @return void
     */
    public function init(): void {
        // Hooks de usuario
        add_action('user_register', [$this, 'onUserRegister']);
        add_action('delete_user', [$this, 'onUserDelete']);
        add_action('wp_login', [$this, 'onUserLogin'], 10, 2);
        
        // Meta boxes en perfil de usuario
        add_action('show_user_profile', [$this, 'addUserProfileFields']);
        add_action('edit_user_profile', [$this, 'addUserProfileFields']);
        add_action('personal_options_update', [$this, 'saveUserProfileFields']);
        add_action('edit_user_profile_update', [$this, 'saveUserProfileFields']);
        
        // Columnas personalizadas en lista de usuarios
        add_filter('manage_users_columns', [$this, 'addUserColumns']);
        add_filter('manage_users_custom_column', [$this, 'renderUserColumn'], 10, 3);
        
        // Filtros en lista de usuarios
        add_action('restrict_manage_users', [$this, 'addUserFilters']);
        add_filter('pre_get_users', [$this, 'filterUsers']);
        
        // AJAX handlers
        add_action('wp_ajax_cpp_get_user_stats', [$this, 'getUserStatsAjax']);
        add_action('wp_ajax_cpp_update_user_plan', [$this, 'updateUserPlanAjax']);
    }
    
    /**
     * Obtener tests de un usuario
     * 
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response
     */
    public function getTests(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_REST_Response([
                'error' => __('Usuario no autenticado', 'club-psychology-pro')
            ], 401);
        }
        
        try {
            $tests = $this->getUserTests($user_id);
            
            return new WP_REST_Response([
                'success' => true,
                'data' => $tests,
                'user_stats' => $this->getUserStats($user_id)
            ], 200);
            
        } catch (\Exception $e) {
            return new WP_REST_Response([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener un test específico
     * 
     * @param WP_REST_Request $request Petición REST
     * @return WP_REST_Response
     */
    public function getTest(WP_REST_Request $request): WP_REST_Response {
        $test_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_REST_Response([
                'error' => __('Usuario no autenticado', 'club-psychology-pro')
            ], 401);
        }
        
        $test = get_post($test_id);
        
        if (!$test || $test->post_type !== 'cpp_test') {
            return new WP_REST_Response([
                'error' => __('Test no encontrado', 'club-psychology-pro')
            ], 404);
        }
        
        // Verificar permisos
        if ($test->post_author != $user_id && !current_user_can('view_all_test_results')) {
            return new WP_REST_Response([
                'error' => __('Sin permisos para ver este test', 'club-psychology-pro')
            ], 403);
        }
        
        $test_data = $this->formatTestData($test);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $test_data
        ], 200);
    }
    
    /**
     * Obtener tests de un usuario específico
     * 
     * @param int $user_id ID del usuario
     * @param array $args Argumentos adicionales
     * @return array
     */
    public function getUserTests(int $user_id, array $args = []): array {
        $defaults = [
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [],
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        $args['post_type'] = 'cpp_test';
        $args['author'] = $user_id;
        
        $query = new WP_Query($args);
        $tests = [];
        
        foreach ($query->posts as $post) {
            $tests[] = $this->formatTestData($post);
        }
        
        return $tests;
    }
    
    /**
     * Formatear datos de un test
     * 
     * @param \WP_Post $post Post del test
     * @return array
     */
    private function formatTestData(\WP_Post $post): array {
        $test_id = $post->ID;
        $tipo_test = get_post_meta($test_id, '_tipo_test', true);
        $subtipo_test = get_post_meta($test_id, '_subtipo_test', true);
        $estado = get_post_meta($test_id, '_estado_test', true);
        $nombre_jugador = get_post_meta($test_id, '_nombre_jugador', true);
        $email_jugador = get_post_meta($test_id, '_email_jugador', true);
        $fecha_nacimiento = get_post_meta($test_id, '_fecha_nacimiento', true);
        
        // Buscar resultados
        $resultado = $this->getTestResults($test_id, $tipo_test);
        
        return [
            'id' => $test_id,
            'title' => $post->post_title,
            'tipo' => $tipo_test,
            'subtipo' => $subtipo_test,
            'estado' => $estado ?: 'pendiente',
            'nombre_jugador' => $nombre_jugador,
            'email_jugador' => $email_jugador,
            'fecha_nacimiento' => $fecha_nacimiento,
            'fecha_creacion' => $post->post_date,
            'fecha_modificacion' => $post->post_modified,
            'resultado' => [
                'exists' => !empty($resultado),
                'id' => $resultado['id'] ?? null,
                'url' => $resultado['url'] ?? null,
                'completado_en' => $resultado['completado_en'] ?? null
            ],
            'url_test' => $this->getTestUrl($test_id, $tipo_test, $subtipo_test),
            'estadisticas' => $this->getTestStatistics($test_id)
        ];
    }
    
    /**
     * Obtener resultados de un test
     * 
     * @param int $test_id ID del test
     * @param string $tipo_test Tipo de test
     * @return array|null
     */
    private function getTestResults(int $test_id, string $tipo_test): ?array {
        $post_type_map = [
            'test_b5_ai' => 'cpp_result_bf',
            'cohesion_equipo' => 'cpp_result_cohesion',
            'monitor_test' => 'cpp_result_monitor'
        ];
        
        $result_post_type = $post_type_map[$tipo_test] ?? null;
        
        if (!$result_post_type) {
            return null;
        }
        
        $results = get_posts([
            'post_type' => $result_post_type,
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'parent_test_id',
                    'value' => $test_id,
                    'compare' => '='
                ]
            ]
        ]);
        
        if (empty($results)) {
            return null;
        }
        
        $result = $results[0];
        $url_map = [
            'cpp_result_bf' => site_url('/?p=' . $result->ID),
            'cpp_result_cohesion' => site_url('/resultado-cohesion/?id=' . $result->ID),
            'cpp_result_monitor' => site_url('/resultado-monitor/?id=' . $result->ID)
        ];
        
        return [
            'id' => $result->ID,
            'url' => $url_map[$result_post_type] ?? '',
            'completado_en' => $result->post_date
        ];
    }
    
    /**
     * Obtener URL de un test
     * 
     * @param int $test_id ID del test
     * @param string $tipo_test Tipo de test
     * @param string $subtipo_test Subtipo de test
     * @return string
     */
    private function getTestUrl(int $test_id, string $tipo_test, string $subtipo_test = ''): string {
        $base_url = site_url();
        
        switch ($tipo_test) {
            case 'test_b5_ai':
                return $base_url . '/haz-tu-test/?test_id=' . $test_id;
                
            case 'cohesion_equipo':
                return $base_url . '/test-cohesion/?test_id=' . $test_id;
                
            case 'monitor_test':
                if ($subtipo_test) {
                    return $base_url . '/monitor-test/?test_id=' . $test_id . '&type=' . $subtipo_test;
                }
                return $base_url . '/monitor-test/?test_id=' . $test_id;
                
            default:
                return '';
        }
    }
    
    /**
     * Obtener estadísticas de un test específico
     * 
     * @param int $test_id ID del test
     * @return array
     */
    private function getTestStatistics(int $test_id): array {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cpp_test_stats';
        
        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE test_id = %d ORDER BY created_at DESC LIMIT 1",
                $test_id
            ),
            ARRAY_A
        );
        
        if (!$stats) {
            return [
                'intentos' => 0,
                'tiempo_promedio' => null,
                'ultimo_acceso' => null
            ];
        }
        
        return [
            'intentos' => (int) $stats['attempts'] ?? 0,
            'tiempo_completado' => (int) $stats['completion_time'] ?? null,
            'fecha_inicio' => $stats['started_at'] ?? null,
            'fecha_completado' => $stats['completed_at'] ?? null,
            'ip_address' => $stats['ip_address'] ?? null
        ];
    }
    
    /**
     * Obtener estadísticas de un usuario
     * 
     * @param int $user_id ID del usuario
     * @return array
     */
    public function getUserStats(int $user_id): array {
        $tests = $this->getUserTests($user_id);
        
        $stats = [
            'total_tests' => count($tests),
            'tests_completados' => 0,
            'tests_pendientes' => 0,
            'tests_en_proceso' => 0,
            'tipos_test' => [],
            'ultimo_test' => null,
            'plan_actual' => $this->getUserPlan($user_id),
            'limite_tests' => $this->getUserTestLimit($user_id),
            'tests_restantes' => 0
        ];
        
        foreach ($tests as $test) {
            // Contar por estado
            switch ($test['estado']) {
                case 'completado':
                    $stats['tests_completados']++;
                    break;
                case 'en_proceso':
                    $stats['tests_en_proceso']++;
                    break;
                default:
                    $stats['tests_pendientes']++;
            }
            
            // Contar por tipo
            $tipo = $test['tipo'];
            if (!isset($stats['tipos_test'][$tipo])) {
                $stats['tipos_test'][$tipo] = 0;
            }
            $stats['tipos_test'][$tipo]++;
            
            // Último test
            if (!$stats['ultimo_test'] || $test['fecha_creacion'] > $stats['ultimo_test']['fecha_creacion']) {
                $stats['ultimo_test'] = $test;
            }
        }
        
        // Calcular tests restantes
        if ($stats['limite_tests'] > 0) {
            $stats['tests_restantes'] = max(0, $stats['limite_tests'] - $stats['total_tests']);
        } else {
            $stats['tests_restantes'] = -1; // Ilimitado
        }
        
        return $stats;
    }
    
    /**
     * Obtener plan de un usuario
     * 
     * @param int $user_id ID del usuario
     * @return string
     */
    public function getUserPlan(int $user_id): string {
        // Administradores tienen plan enterprise
        if (user_can($user_id, 'administrator')) {
            return 'enterprise';
        }
        
        // Verificar membresías de WooCommerce
        if (function_exists('wc_memberships_get_user_memberships')) {
            $memberships = wc_memberships_get_user_memberships($user_id);
            
            foreach ($memberships as $membership) {
                if ($membership->is_active()) {
                    $plan_slug = $membership->get_plan()->get_slug();
                    
                    // Mapear nombres de planes de WooCommerce a nuestros planes
                    $plan_mapping = [
                        'plan_basico' => 'basic',
                        'plan_premium' => 'premium',
                        'plan_empresarial' => 'enterprise'
                    ];
                    
                    return $plan_mapping[$plan_slug] ?? 'free';
                }
            }
        }
        
        // Plan personalizado guardado en meta
        $custom_plan = get_user_meta($user_id, 'cpp_user_plan', true);
        if ($custom_plan && isset($this->membership_plans[$custom_plan])) {
            return $custom_plan;
        }
        
        return 'free';
    }
    
    /**
     * Obtener límite de tests de un usuario
     * 
     * @param int $user_id ID del usuario
     * @return int -1 para ilimitado
     */
    public function getUserTestLimit(int $user_id): int {
        $plan = $this->getUserPlan($user_id);
        return $this->membership_plans[$plan]['max_tests'] ?? 1;
    }
    
    /**
     * Verificar si un usuario puede crear más tests
     * 
     * @param int $user_id ID del usuario
     * @return bool
     */
    public function canUserCreateTest(int $user_id): bool {
        $limit = $this->getUserTestLimit($user_id);
        
        // Sin límite
        if ($limit === -1) {
            return true;
        }
        
        $current_count = count($this->getUserTests($user_id));
        return $current_count < $limit;
    }
    
    /**
     * Crear un nuevo test para un usuario
     * 
     * @param int $user_id ID del usuario
     * @param array $test_data Datos del test
     * @return int|WP_Error ID del test creado o error
     */
    public function createTest(int $user_id, array $test_data) {
        // Verificar si puede crear tests
        if (!$this->canUserCreateTest($user_id)) {
            return new WP_Error(
                'test_limit_exceeded',
                __('Has alcanzado el límite de tests para tu plan', 'club-psychology-pro')
            );
        }
        
        // Validar datos requeridos
        $required_fields = ['tipo_test', 'nombre_jugador', 'email_jugador'];
        foreach ($required_fields as $field) {
            if (empty($test_data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Campo requerido: %s', 'club-psychology-pro'), $field)
                );
            }
        }
        
        // Sanitizar datos
        $test_data = $this->sanitizeTestData($test_data);
        
        // Crear el post
        $post_data = [
            'post_title' => $test_data['nombre_jugador'],
            'post_type' => 'cpp_test',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_content' => $this->generateTestDescription($test_data)
        ];
        
        $test_id = wp_insert_post($post_data);
        
        if (is_wp_error($test_id)) {
            return $test_id;
        }
        
        // Guardar metadatos
        $meta_fields = [
            '_tipo_test' => $test_data['tipo_test'],
            '_subtipo_test' => $test_data['subtipo_test'] ?? '',
            '_nombre_jugador' => $test_data['nombre_jugador'],
            '_email_jugador' => $test_data['email_jugador'],
            '_fecha_nacimiento' => $test_data['fecha_nacimiento'] ?? '',
            '_estado_test' => 'pendiente',
            '_fecha_solicitud' => current_time('mysql')
        ];
        
        foreach ($meta_fields as $key => $value) {
            update_post_meta($test_id, $key, $value);
        }
        
        // Registrar estadística inicial
        $this->recordTestCreation($test_id, $user_id, $test_data['tipo_test']);
        
        // Enviar email si está configurado
        if (get_option('cpp_email_notifications', true)) {
            $email_manager = $this->container->get('email_manager');
            $email_manager->sendTestInvitation($test_id);
        }
        
        return $test_id;
    }
    
    /**
     * Sanitizar datos del test
     * 
     * @param array $data Datos a sanitizar
     * @return array
     */
    private function sanitizeTestData(array $data): array {
        return [
            'tipo_test' => sanitize_text_field($data['tipo_test'] ?? ''),
            'subtipo_test' => sanitize_text_field($data['subtipo_test'] ?? ''),
            'nombre_jugador' => sanitize_text_field($data['nombre_jugador'] ?? ''),
            'email_jugador' => sanitize_email($data['email_jugador'] ?? ''),
            'fecha_nacimiento' => sanitize_text_field($data['fecha_nacimiento'] ?? '')
        ];
    }
    
    /**
     * Generar descripción del test
     * 
     * @param array $test_data Datos del test
     * @return string
     */
    private function generateTestDescription(array $test_data): string {
        $tipo_names = [
            'test_b5_ai' => 'Test Big Five AI',
            'cohesion_equipo' => 'Test de Cohesión de Equipo',
            'monitor_test' => 'Monitor Test'
        ];
        
        $tipo_name = $tipo_names[$test_data['tipo_test']] ?? $test_data['tipo_test'];
        
        $description = sprintf(
            __('Test solicitado el %s\n\nJugador: %s\nTipo de test: %s', 'club-psychology-pro'),
            current_time('d/m/Y H:i'),
            $test_data['nombre_jugador'],
            $tipo_name
        );
        
        if (!empty($test_data['subtipo_test'])) {
            $description .= '\nSubtipo: ' . $test_data['subtipo_test'];
        }
        
        if (!empty($test_data['fecha_nacimiento'])) {
            $description .= '\nFecha de nacimiento: ' . date('d/m/Y', strtotime($test_data['fecha_nacimiento']));
        }
        
        return $description;
    }
    
    /**
     * Registrar creación de test en estadísticas
     * 
     * @param int $test_id ID del test
     * @param int $user_id ID del usuario
     * @param string $test_type Tipo de test
     * @return void
     */
    private function recordTestCreation(int $test_id, int $user_id, string $test_type): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'cpp_test_stats';
        
        $wpdb->insert(
            $table,
            [
                'test_id' => $test_id,
                'user_id' => $user_id,
                'test_type' => $test_type,
                'ip_address' => $this->getUserIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ],
            [
                '%d', '%d', '%s', '%s', '%s', '%s'
            ]
        );
    }
    
    /**
     * Obtener IP del usuario
     * 
     * @return string
     */
    private function getUserIP(): string {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Hook cuando se registra un usuario
     * 
     * @param int $user_id ID del usuario
     * @return void
     */
    public function onUserRegister(int $user_id): void {
        // Asignar plan por defecto
        update_user_meta($user_id, 'cpp_user_plan', 'free');
        update_user_meta($user_id, 'cpp_registration_date', current_time('mysql'));
        update_user_meta($user_id, 'cpp_last_activity', current_time('mysql'));
    }
    
    /**
     * Hook cuando se elimina un usuario
     * 
     * @param int $user_id ID del usuario
     * @return void
     */
    public function onUserDelete(int $user_id): void {
        // Limpiar datos relacionados si está configurado
        if (get_option('cpp_delete_user_data_on_delete', false)) {
            $this->deleteUserData($user_id);
        }
    }
    
    /**
     * Hook cuando un usuario hace login
     * 
     * @param string $user_login Login del usuario
     * @param WP_User $user Objeto usuario
     * @return void
     */
    public function onUserLogin(string $user_login, WP_User $user): void {
        update_user_meta($user->ID, 'cpp_last_login', current_time('mysql'));
        update_user_meta($user->ID, 'cpp_last_activity', current_time('mysql'));
    }
    
    /**
     * Eliminar datos de un usuario
     * 
     * @param int $user_id ID del usuario
     * @return void
     */
    private function deleteUserData(int $user_id): void {
        global $wpdb;
        
        // Eliminar tests del usuario
        $tests = get_posts([
            'post_type' => 'cpp_test',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($tests as $test) {
            wp_delete_post($test->ID, true);
        }
        
        // Eliminar estadísticas
        $stats_table = $wpdb->prefix . 'cpp_test_stats';
        $wpdb->delete($stats_table, ['user_id' => $user_id], ['%d']);
        
        // Eliminar logs de email
        $email_table = $wpdb->prefix . 'cpp_email_logs';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $email_table WHERE test_id IN (
                    SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'cpp_test'
                )",
                $user_id
            )
        );
    }
    
    /**
     * Añadir campos al perfil de usuario
     * 
     * @param WP_User $user Usuario
     * @return void
     */
    public function addUserProfileFields(WP_User $user): void {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        
        $user_plan = $this->getUserPlan($user->ID);
        $user_stats = $this->getUserStats($user->ID);
        
        ?>
        <h3><?php _e('Club Psychology Pro', 'club-psychology-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="cpp_user_plan"><?php _e('Plan de Membresía', 'club-psychology-pro'); ?></label></th>
                <td>
                    <select name="cpp_user_plan" id="cpp_user_plan">
                        <?php foreach ($this->membership_plans as $plan_id => $plan): ?>
                            <option value="<?php echo esc_attr($plan_id); ?>" <?php selected($user_plan, $plan_id); ?>>
                                <?php echo esc_html($plan['name']); ?>
                                (<?php echo $plan['max_tests'] === -1 ? __('Ilimitado', 'club-psychology-pro') : $plan['max_tests']; ?> tests)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php _e('Estadísticas', 'club-psychology-pro'); ?></th>
                <td>
                    <p><strong><?php _e('Tests totales:', 'club-psychology-pro'); ?></strong> <?php echo $user_stats['total_tests']; ?></p>
                    <p><strong><?php _e('Tests completados:', 'club-psychology-pro'); ?></strong> <?php echo $user_stats['tests_completados']; ?></p>
                    <p><strong><?php _e('Tests pendientes:', 'club-psychology-pro'); ?></strong> <?php echo $user_stats['tests_pendientes']; ?></p>
                    <?php if ($user_stats['ultimo_test']): ?>
                        <p><strong><?php _e('Último test:', 'club-psychology-pro'); ?></strong> 
                           <?php echo date('d/m/Y', strtotime($user_stats['ultimo_test']['fecha_creacion'])); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Guardar campos del perfil de usuario
     * 
     * @param int $user_id ID del usuario
     * @return void
     */
    public function saveUserProfileFields(int $user_id): void {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (isset($_POST['cpp_user_plan'])) {
            $plan = sanitize_text_field($_POST['cpp_user_plan']);
            if (isset($this->membership_plans[$plan])) {
                update_user_meta($user_id, 'cpp_user_plan', $plan);
            }
        }
    }
    
    /**
     * Añadir columnas a la lista de usuarios
     * 
     * @param array $columns Columnas existentes
     * @return array
     */
    public function addUserColumns(array $columns): array {
        $columns['cpp_plan'] = __('Plan CPP', 'club-psychology-pro');
        $columns['cpp_tests'] = __('Tests', 'club-psychology-pro');
        $columns['cpp_last_activity'] = __('Última Actividad', 'club-psychology-pro');
        return $columns;
    }
    
    /**
     * Renderizar columnas personalizadas
     * 
     * @param string $value Valor por defecto
     * @param string $column_name Nombre de la columna
     * @param int $user_id ID del usuario
     * @return string
     */
    public function renderUserColumn(string $value, string $column_name, int $user_id): string {
        switch ($column_name) {
            case 'cpp_plan':
                $plan = $this->getUserPlan($user_id);
                $plan_info = $this->membership_plans[$plan] ?? null;
                if ($plan_info) {
                    $class = 'cpp-plan-' . $plan;
                    return "<span class='$class'>" . esc_html($plan_info['name']) . "</span>";
                }
                return '-';
                
            case 'cpp_tests':
                $stats = $this->getUserStats($user_id);
                $completados = $stats['tests_completados'];
                $total = $stats['total_tests'];
                $limit = $stats['limite_tests'];
                
                $limit_text = $limit === -1 ? '∞' : $limit;
                return "<strong>$completados/$total</strong><br><small>Límite: $limit_text</small>";
                
            case 'cpp_last_activity':
                $last_activity = get_user_meta($user_id, 'cpp_last_activity', true);
                if ($last_activity) {
                    $diff = human_time_diff(strtotime($last_activity), current_time('timestamp'));
                    return sprintf(__('Hace %s', 'club-psychology-pro'), $diff);
                }
                return '-';
        }
        
        return $value;
    }
    
    /**
     * Añadir filtros a la lista de usuarios
     * 
     * @return void
     */
    public function addUserFilters(): void {
        if (!current_user_can('list_users')) {
            return;
        }
        
        $current_plan = $_GET['cpp_plan_filter'] ?? '';
        
        echo '<label for="cpp_plan_filter" class="screen-reader-text">' . __('Filtrar por plan', 'club-psychology-pro') . '</label>';
        echo '<select name="cpp_plan_filter" id="cpp_plan_filter">';
        echo '<option value="">' . __('Todos los planes', 'club-psychology-pro') . '</option>';
        
        foreach ($this->membership_plans as $plan_id => $plan) {
            $selected = selected($current_plan, $plan_id, false);
            echo "<option value='$plan_id' $selected>" . esc_html($plan['name']) . "</option>";
        }
        
        echo '</select>';
        
        // Filtro por actividad
        $activity_filter = $_GET['cpp_activity_filter'] ?? '';
        echo '<select name="cpp_activity_filter" id="cpp_activity_filter">';
        echo '<option value="">' . __('Toda la actividad', 'club-psychology-pro') . '</option>';
        echo '<option value="active_7d"' . selected($activity_filter, 'active_7d', false) . '>' . __('Activos (7 días)', 'club-psychology-pro') . '</option>';
        echo '<option value="active_30d"' . selected($activity_filter, 'active_30d', false) . '>' . __('Activos (30 días)', 'club-psychology-pro') . '</option>';
        echo '<option value="inactive_30d"' . selected($activity_filter, 'inactive_30d', false) . '>' . __('Inactivos (30+ días)', 'club-psychology-pro') . '</option>';
        echo '</select>';
    }
    
    /**
     * Filtrar usuarios según criterios
     * 
     * @param \WP_User_Query $query Query de usuarios
     * @return void
     */
    public function filterUsers(\WP_User_Query $query): void {
        global $wpdb;
        
        if (!is_admin() || !current_user_can('list_users')) {
            return;
        }
        
        $meta_query = $query->get('meta_query') ?: [];
        
        // Filtro por plan
        if (!empty($_GET['cpp_plan_filter'])) {
            $plan = sanitize_text_field($_GET['cpp_plan_filter']);
            if (isset($this->membership_plans[$plan])) {
                $meta_query[] = [
                    'key' => 'cpp_user_plan',
                    'value' => $plan,
                    'compare' => '='
                ];
            }
        }
        
        // Filtro por actividad
        if (!empty($_GET['cpp_activity_filter'])) {
            $activity = sanitize_text_field($_GET['cpp_activity_filter']);
            $date_query = [];
            
            switch ($activity) {
                case 'active_7d':
                    $date = date('Y-m-d H:i:s', strtotime('-7 days'));
                    $meta_query[] = [
                        'key' => 'cpp_last_activity',
                        'value' => $date,
                        'compare' => '>='
                    ];
                    break;
                    
                case 'active_30d':
                    $date = date('Y-m-d H:i:s', strtotime('-30 days'));
                    $meta_query[] = [
                        'key' => 'cpp_last_activity',
                        'value' => $date,
                        'compare' => '>='
                    ];
                    break;
                    
                case 'inactive_30d':
                    $date = date('Y-m-d H:i:s', strtotime('-30 days'));
                    $meta_query[] = [
                        'relation' => 'OR',
                        [
                            'key' => 'cpp_last_activity',
                            'value' => $date,
                            'compare' => '<'
                        ],
                        [
                            'key' => 'cpp_last_activity',
                            'compare' => 'NOT EXISTS'
                        ]
                    ];
                    break;
            }
        }
        
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }
    
    /**
     * AJAX: Obtener estadísticas de usuario
     * 
     * @return void
     */
    public function getUserStatsAjax(): void {
        check_ajax_referer('cpp_admin_nonce', 'nonce');
        
        if (!current_user_can('view_all_test_results')) {
            wp_send_json_error(__('Sin permisos', 'club-psychology-pro'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(__('ID de usuario inválido', 'club-psychology-pro'));
        }
        
        $stats = $this->getUserStats($user_id);
        $tests = $this->getUserTests($user_id, ['posts_per_page' => 10]);
        
        wp_send_json_success([
            'stats' => $stats,
            'recent_tests' => $tests
        ]);
    }
    
    /**
     * AJAX: Actualizar plan de usuario
     * 
     * @return void
     */
    public function updateUserPlanAjax(): void {
        check_ajax_referer('cpp_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error(__('Sin permisos', 'club-psychology-pro'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $new_plan = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
        
        if (!$user_id || !isset($this->membership_plans[$new_plan])) {
            wp_send_json_error(__('Datos inválidos', 'club-psychology-pro'));
        }
        
        $old_plan = $this->getUserPlan($user_id);
        update_user_meta($user_id, 'cpp_user_plan', $new_plan);
        
        // Log del cambio
        $this->logPlanChange($user_id, $old_plan, $new_plan);
        
        wp_send_json_success([
            'message' => sprintf(
                __('Plan actualizado de %s a %s', 'club-psychology-pro'),
                $this->membership_plans[$old_plan]['name'],
                $this->membership_plans[$new_plan]['name']
            ),
            'new_stats' => $this->getUserStats($user_id)
        ]);
    }
    
    /**
     * Registrar cambio de plan
     * 
     * @param int $user_id ID del usuario
     * @param string $old_plan Plan anterior
     * @param string $new_plan Plan nuevo
     * @return void
     */
    private function logPlanChange(int $user_id, string $old_plan, string $new_plan): void {
        $user = get_user_by('id', $user_id);
        $admin_user = get_current_user_id();
        
        error_log(sprintf(
            'CPP Plan Change: User %s (ID: %d) plan changed from %s to %s by admin %d',
            $user->user_login,
            $user_id,
            $old_plan,
            $new_plan,
            $admin_user
        ));
        
        // Opcional: Guardar en tabla de logs personalizada
        do_action('cpp_user_plan_changed', $user_id, $old_plan, $new_plan, $admin_user);
    }
    
    /**
     * Obtener resumen global de usuarios
     * 
     * @return array
     */
    public function getGlobalStats(): array {
        global $wpdb;
        
        // Cache por 1 hora
        $cache_key = 'cpp_global_user_stats';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $stats = [
            'total_users' => 0,
            'users_by_plan' => [],
            'active_users' => [
                '7d' => 0,
                '30d' => 0
            ],
            'total_tests' => 0,
            'tests_completed' => 0,
            'popular_test_types' => []
        ];
        
        // Total de usuarios
        $stats['total_users'] = count_users()['total_users'];
        
        // Usuarios por plan
        foreach (array_keys($this->membership_plans) as $plan) {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'cpp_user_plan' AND meta_value = %s",
                    $plan
                )
            );
            $stats['users_by_plan'][$plan] = (int) $count;
        }
        
        // Usuarios activos
        $date_7d = date('Y-m-d H:i:s', strtotime('-7 days'));
        $date_30d = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $stats['active_users']['7d'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'cpp_last_activity' AND meta_value >= %s",
                $date_7d
            )
        );
        
        $stats['active_users']['30d'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'cpp_last_activity' AND meta_value >= %s",
                $date_30d
            )
        );
        
        // Tests totales y completados
        $stats['total_tests'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'cpp_test'"
        );
        
        $stats['tests_completed'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p 
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                 WHERE p.post_type = 'cpp_test' 
                 AND pm.meta_key = '_estado_test' 
                 AND pm.meta_value = %s",
                'completado'
            )
        );
        
        // Tipos de test más populares
        $popular_types = $wpdb->get_results(
            "SELECT pm.meta_value as test_type, COUNT(*) as count 
             FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'cpp_test' 
             AND pm.meta_key = '_tipo_test' 
             GROUP BY pm.meta_value 
             ORDER BY count DESC 
             LIMIT 5",
            ARRAY_A
        );
        
        foreach ($popular_types as $type) {
            $stats['popular_test_types'][$type['test_type']] = (int) $type['count'];
        }
        
        set_transient($cache_key, $stats, HOUR_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Limpiar datos antiguos
     * 
     * @param int $days Días de antigüedad
     * @return int Número de registros eliminados
     */
    public function cleanupOldData(int $days = 30): int {
        global $wpdb;
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
        $deleted = 0;
        
        // Limpiar estadísticas antiguas
        $stats_table = $wpdb->prefix . 'cpp_test_stats';
        $deleted += $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $stats_table WHERE created_at < %s AND completed_at IS NULL",
                $date_limit
            )
        );
        
        // Limpiar logs de email antiguos exitosos
        $email_table = $wpdb->prefix . 'cpp_email_logs';
        $deleted += $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $email_table WHERE created_at < %s AND status = 'sent'",
                $date_limit
            )
        );
        
        // Limpiar transients antiguos
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cpp_%' AND option_value < UNIX_TIMESTAMP()"
        );
        
        return $deleted;
    }
    
    /**
     * Exportar datos de usuarios
     * 
     * @param array $filters Filtros a aplicar
     * @return array
     */
    public function exportUserData(array $filters = []): array {
        $users = get_users($filters);
        $export_data = [];
        
        foreach ($users as $user) {
            $stats = $this->getUserStats($user->ID);
            $plan = $this->getUserPlan($user->ID);
            
            $export_data[] = [
                'ID' => $user->ID,
                'Login' => $user->user_login,
                'Email' => $user->user_email,
                'Nombre' => $user->display_name,
                'Fecha_Registro' => $user->user_registered,
                'Plan' => $this->membership_plans[$plan]['name'] ?? $plan,
                'Tests_Totales' => $stats['total_tests'],
                'Tests_Completados' => $stats['tests_completados'],
                'Tests_Pendientes' => $stats['tests_pendientes'],
                'Ultima_Actividad' => get_user_meta($user->ID, 'cpp_last_activity', true),
                'Ultimo_Login' => get_user_meta($user->ID, 'cpp_last_login', true)
            ];
        }
        
        return $export_data;
    }
    
    /**
     * Obtener planes de membresía disponibles
     * 
     * @return array
     */
    public function getMembershipPlans(): array {
        return $this->membership_plans;
    }
}