<?php
/**
 * Gestor de suscripciones para Club Psychology Pro
 *
 * @package ClubPsychologyPro\Users
 * @since 1.0.0
 */

namespace ClubPsychologyPro\Users;

use ClubPsychologyPro\Core\Container;

/**
 * Clase SubscriptionManager
 * 
 * Maneja las suscripciones y planes de los usuarios
 */
class SubscriptionManager {
    
    /**
     * Contenedor de dependencias
     * @var Container
     */
    private Container $container;
    
    /**
     * Planes disponibles
     * @var array
     */
    private array $plans = [];
    
    /**
     * Constructor
     * 
     * @param Container|null $container Contenedor de dependencias
     */
    public function __construct(?Container $container = null) {
        $this->container = $container ?? new Container();
        $this->initializePlans();
    }
    
    /**
     * Inicializar el manager
     * 
     * @return void
     */
    public function init(): void {
        // Hooks de WooCommerce Memberships
        if (function_exists('wc_memberships')) {
            add_action('wc_memberships_user_membership_saved', [$this, 'onMembershipChanged']);
            add_action('wc_memberships_user_membership_expired', [$this, 'onMembershipExpired']);
            add_action('wc_memberships_user_membership_cancelled', [$this, 'onMembershipCancelled']);
        }
        
        // Hooks generales
        add_action('user_register', [$this, 'assignDefaultPlan']);
        add_filter('cpp_user_can_create_test', [$this, 'checkTestLimits'], 10, 2);
    }
    
    /**
     * Inicializar planes
     * 
     * @return void
     */
    private function initializePlans(): void {
        $this->plans = [
            'free' => [
                'name' => __('Gratuito', 'club-psychology-pro'),
                'max_tests' => 1,
                'features' => ['basic_reports'],
                'price' => 0
            ],
            'basic' => [
                'name' => __('Básico', 'club-psychology-pro'),
                'max_tests' => 3,
                'features' => ['basic_reports', 'email_notifications'],
                'price' => 9.99
            ],
            'premium' => [
                'name' => __('Premium', 'club-psychology-pro'),
                'max_tests' => 10,
                'features' => ['basic_reports', 'email_notifications', 'advanced_reports', 'whatsapp_integration'],
                'price' => 29.99
            ],
            'enterprise' => [
                'name' => __('Empresarial', 'club-psychology-pro'),
                'max_tests' => -1, // Ilimitado
                'features' => ['basic_reports', 'email_notifications', 'advanced_reports', 'whatsapp_integration', 'custom_branding', 'api_access'],
                'price' => 99.99
            ]
        ];
        
        // Permitir filtrar planes
        $this->plans = apply_filters('cpp_subscription_plans', $this->plans);
    }
    
    /**
     * Obtener plan de un usuario
     * 
     * @param int $user_id ID del usuario
     * @return string
     */
    public function getUserPlan(int $user_id): string {
        // Admin siempre tiene plan enterprise
        if (user_can($user_id, 'administrator')) {
            return 'enterprise';
        }
        
        // Verificar membresías activas de WooCommerce
        if (function_exists('wc_memberships_get_user_memberships')) {
            $memberships = wc_memberships_get_user_memberships($user_id);
            
            foreach ($memberships as $membership) {
                if ($membership->is_active()) {
                    return $this->mapWooCommercePlan($membership->get_plan()->get_slug());
                }
            }
        }
        
        // Plan personalizado
        $custom_plan = get_user_meta($user_id, 'cpp_user_plan', true);
        if ($custom_plan && isset($this->plans[$custom_plan])) {
            return $custom_plan;
        }
        
        return 'free';
    }
    
    /**
     * Mapear planes de WooCommerce a nuestros planes
     * 
     * @param string $wc_plan Plan de WooCommerce
     * @return string
     */
    private function mapWooCommercePlan(string $wc_plan): string {
        $mapping = [
            'plan_basico' => 'basic',
            'plan_premium' => 'premium',
            'plan_empresarial' => 'enterprise'
        ];
        
        return $mapping[$wc_plan] ?? 'free';
    }
    
    /**
     * Verificar si un usuario puede crear tests
     * 
     * @param bool $can_create Valor actual
     * @param int $user_id ID del usuario
     * @return bool
     */
    public function checkTestLimits(bool $can_create, int $user_id): bool {
        $plan = $this->getUserPlan($user_id);
        $limit = $this->plans[$plan]['max_tests'] ?? 1;
        
        // Sin límite
        if ($limit === -1) {
            return true;
        }
        
        // Contar tests actuales
        $current_tests = get_posts([
            'post_type' => 'cpp_test',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        return count($current_tests) < $limit;
    }
    
    /**
     * Asignar plan por defecto a nuevo usuario
     * 
     * @param int $user_id ID del usuario
     * @return void
     */
    public function assignDefaultPlan(int $user_id): void {
        update_user_meta($user_id, 'cpp_user_plan', 'free');
        update_user_meta($user_id, 'cpp_plan_assigned_date', current_time('mysql'));
    }
    
    /**
     * Cuando cambia la membresía
     * 
     * @param \WC_Memberships_User_Membership $membership
     * @return void
     */
    public function onMembershipChanged($membership): void {
        if (!$membership) return;
        
        $user_id = $membership->get_user_id();
        $new_plan = $this->mapWooCommercePlan($membership->get_plan()->get_slug());
        
        update_user_meta($user_id, 'cpp_user_plan', $new_plan);
        update_user_meta($user_id, 'cpp_plan_changed_date', current_time('mysql'));
        
        // Trigger action para otros plugins
        do_action('cpp_user_plan_changed', $user_id, $new_plan);
    }
    
    /**
     * Cuando expira la membresía
     * 
     * @param \WC_Memberships_User_Membership $membership
     * @return void
     */
    public function onMembershipExpired($membership): void {
        if (!$membership) return;
        
        $user_id = $membership->get_user_id();
        
        // Volver al plan gratuito
        update_user_meta($user_id, 'cpp_user_plan', 'free');
        update_user_meta($user_id, 'cpp_plan_expired_date', current_time('mysql'));
        
        do_action('cpp_user_plan_expired', $user_id);
    }
    
    /**
     * Cuando se cancela la membresía
     * 
     * @param \WC_Memberships_User_Membership $membership
     * @return void
     */
    public function onMembershipCancelled($membership): void {
        if (!$membership) return;
        
        $user_id = $membership->get_user_id();
        
        // Volver al plan gratuito
        update_user_meta($user_id, 'cpp_user_plan', 'free');
        update_user_meta($user_id, 'cpp_plan_cancelled_date', current_time('mysql'));
        
        do_action('cpp_user_plan_cancelled', $user_id);
    }
    
    /**
     * Obtener información de un plan
     * 
     * @param string $plan_id ID del plan
     * @return array|null
     */
    public function getPlanInfo(string $plan_id): ?array {
        return $this->plans[$plan_id] ?? null;
    }
    
    /**
     * Obtener todos los planes
     * 
     * @return array
     */
    public function getAllPlans(): array {
        return $this->plans;
    }
    
    /**
     * Actualizar plan de usuario manualmente
     * 
     * @param int $user_id ID del usuario
     * @param string $plan_id ID del plan
     * @return bool
     */
    public function updateUserPlan(int $user_id, string $plan_id): bool {
        if (!isset($this->plans[$plan_id])) {
            return false;
        }
        
        $old_plan = $this->getUserPlan($user_id);
        update_user_meta($user_id, 'cpp_user_plan', $plan_id);
        update_user_meta($user_id, 'cpp_plan_updated_date', current_time('mysql'));
        
        do_action('cpp_user_plan_manually_changed', $user_id, $old_plan, $plan_id);
        
        return true;
    }
    
    /**
     * Verificar si un usuario tiene una característica
     * 
     * @param int $user_id ID del usuario
     * @param string $feature Característica a verificar
     * @return bool
     */
    public function userHasFeature(int $user_id, string $feature): bool {
        $plan = $this->getUserPlan($user_id);
        $plan_info = $this->plans[$plan] ?? null;
        
        if (!$plan_info) {
            return false;
        }
        
        return in_array($feature, $plan_info['features'] ?? []);
    }
    
    /**
     * Obtener estadísticas de suscripciones
     * 
     * @return array
     */
    public function getSubscriptionStats(): array {
        global $wpdb;
        
        $stats = [
            'total_users' => 0,
            'by_plan' => [],
            'revenue' => [
                'monthly' => 0,
                'total' => 0
            ]
        ];
        
        // Contar usuarios por plan
        foreach (array_keys($this->plans) as $plan_id) {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->usermeta} 
                     WHERE meta_key = 'cpp_user_plan' AND meta_value = %s",
                    $plan_id
                )
            );
            
            $stats['by_plan'][$plan_id] = [
                'count' => (int) $count,
                'percentage' => 0
            ];
            
            $stats['total_users'] += (int) $count;
        }
        
        // Calcular porcentajes
        if ($stats['total_users'] > 0) {
            foreach ($stats['by_plan'] as $plan_id => &$plan_stats) {
                $plan_stats['percentage'] = round(
                    ($plan_stats['count'] / $stats['total_users']) * 100,
                    2
                );
            }
        }
        
        return $stats;
    }
}