<?php
/**
 * Create User Subscriptions Table Migration
 * 
 * Migración para crear la tabla de suscripciones y planes de usuarios
 * del sistema Club Psychology Pro. Gestiona límites de tests, planes
 * de membresía y seguimiento de uso.
 * 
 * @package ClubPsychologyPro
 * @subpackage Database\Migrations
 * @version 1.0.0
 * @author Club Psychology Pro Team
 */

namespace ClubPsychologyPro\Database\Migrations;

use ClubPsychologyPro\Database\Migration;
use ClubPsychologyPro\Database\TableBuilder;

class CreateUserSubscriptionsTable extends Migration {
    
    /**
     * Migration version
     * 
     * @var string
     */
    protected string $version = '2024_01_01_000002';
    
    /**
     * Run the migration UP - Create the user_subscriptions table
     * 
     * @return bool
     */
    public function up(): bool {
        $this->log("Starting creation of user_subscriptions table");
        
        return $this->createTable('user_subscriptions', function(TableBuilder $table) {
            // Primary key
            $table->id();
            
            // User reference
            $table->bigInteger('user_id', true)
                  ->comment('WordPress user ID');
            
            // Subscription plan information
            $table->string('plan_slug', 50)
                  ->comment('Plan identifier: free, basic, premium, enterprise');
            
            $table->string('plan_name', 100)
                  ->comment('Human readable plan name');
            
            $table->string('plan_type', 30, false, 'subscription')
                  ->comment('Type: subscription, one_time, lifetime');
            
            // Subscription status
            $table->enum('status', [
                'active',       // Suscripción activa
                'inactive',     // Temporalmente inactiva
                'cancelled',    // Cancelada por usuario
                'expired',      // Expirada
                'suspended',    // Suspendida por admin
                'pending',      // Pendiente de activación
                'trialing'      // En período de prueba
            ], 'pending')->comment('Current subscription status');
            
            // Plan limits and features
            $table->integer('test_limit_monthly', false, false, 0)
                  ->comment('Number of tests allowed per month (0 = unlimited)');
            
            $table->integer('test_limit_total', false, false, 0)
                  ->comment('Total tests allowed in plan (0 = unlimited)');
            
            $table->json('allowed_test_types')
                  ->comment('JSON array of allowed test types');
            
            $table->json('plan_features')
                  ->comment('JSON object with plan features and capabilities');
            
            // Usage tracking
            $table->integer('tests_used_current_month', false, false, 0)
                  ->comment('Tests used in current billing period');
            
            $table->integer('tests_used_total', false, false, 0)
                  ->comment('Total tests used since subscription start');
            
            $table->date('usage_reset_date')
                  ->comment('When monthly usage counter resets');
            
            // Subscription dates
            $table->timestamp('subscription_start_date')
                  ->comment('When subscription became active');
            
            $table->timestamp('subscription_end_date', true)
                  ->comment('When subscription expires (null for lifetime)');
            
            $table->timestamp('trial_start_date', true)
                  ->comment('Trial period start date');
            
            $table->timestamp('trial_end_date', true)
                  ->comment('Trial period end date');
            
            $table->integer('trial_days', true)
                  ->comment('Number of trial days offered');
            
            // Billing and payment integration
            $table->string('billing_cycle', 20, true)
                  ->comment('monthly, yearly, lifetime, one_time');
            
            $table->decimal('plan_price', 10, 2, true)
                  ->comment('Plan price in configured currency');
            
            $table->string('currency', 3, false, 'USD')
                  ->comment('Currency code (USD, EUR, etc.)');
            
            $table->string('payment_gateway', 50, true)
                  ->comment('stripe, paypal, woocommerce, etc.');
            
            $table->string('external_subscription_id', 100, true)
                  ->comment('ID from external payment processor');
            
            $table->string('external_customer_id', 100, true)
                  ->comment('Customer ID from external payment processor');
            
            // WooCommerce integration
            $table->bigInteger('wc_order_id', true, true)
                  ->comment('WooCommerce order ID if applicable');
            
            $table->bigInteger('wc_subscription_id', true, true)
                  ->comment('WooCommerce subscription ID if applicable');
            
            $table->bigInteger('wc_product_id', true, true)
                  ->comment('WooCommerce product ID');
            
            // Automatic renewal and notifications
            $table->boolean('auto_renew', true)
                  ->comment('Whether subscription auto-renews');
            
            $table->timestamp('next_billing_date', true)
                  ->comment('Next billing/renewal date');
            
            $table->boolean('renewal_reminder_sent', false)
                  ->comment('Whether renewal reminder was sent');
            
            $table->timestamp('reminder_sent_at', true)
                  ->comment('When renewal reminder was sent');
            
            // Cancellation tracking
            $table->timestamp('cancelled_at', true)
                  ->comment('When subscription was cancelled');
            
            $table->string('cancellation_reason', 255, true)
                  ->comment('Reason for cancellation');
            
            $table->boolean('cancel_at_period_end', false)
                  ->comment('Cancel at end of current period');
            
            // Admin and support fields
            $table->text('admin_notes', true)
                  ->comment('Internal notes for admin/support');
            
            $table->string('source', 50, false, 'manual')
                  ->comment('How subscription was created: manual, woocommerce, stripe, etc.');
            
            $table->json('metadata', true)
                  ->comment('Additional metadata and custom fields');
            
            // Referral and promotional codes
            $table->string('coupon_code', 50, true)
                  ->comment('Promotional/discount code used');
            
            $table->decimal('discount_amount', 10, 2, true)
                  ->comment('Discount amount applied');
            
            $table->string('discount_type', 20, true)
                  ->comment('percentage, fixed_amount');
            
            $table->string('referral_code', 50, true)
                  ->comment('Referral code used for signup');
            
            $table->bigInteger('referred_by_user_id', true, true)
                  ->comment('User ID who referred this user');
            
            // Audit trail
            $table->bigInteger('created_by_user_id', true, true)
                  ->comment('Admin user who created this subscription');
            
            $table->bigInteger('modified_by_user_id', true, true)
                  ->comment('Last admin user who modified this subscription');
            
            // Soft delete support
            $table->timestamp('deleted_at', true)
                  ->comment('Soft delete timestamp');
            
            // Standard timestamps
            $table->timestamps();
            
            // === INDEXES FOR PERFORMANCE ===
            
            // Primary user queries
            $table->index(['user_id', 'status'], 'idx_user_status')
                  ->comment('Fast lookup of user subscription status');
            
            $table->unique(['user_id', 'plan_slug'], 'unq_user_plan')
                  ->comment('Prevent duplicate plan subscriptions per user');
            
            // Plan and status queries
            $table->index(['plan_slug', 'status'], 'idx_plan_status')
                  ->comment('Analytics by plan type and status');
            
            $table->index(['status', 'subscription_end_date'], 'idx_status_expiry')
                  ->comment('Find expiring subscriptions');
            
            // Billing and renewal queries
            $table->index(['next_billing_date'], 'idx_next_billing')
                  ->comment('Process upcoming renewals');
            
            $table->index(['auto_renew', 'next_billing_date'], 'idx_auto_renew')
                  ->comment('Auto-renewal processing');
            
            // Usage tracking
            $table->index(['usage_reset_date'], 'idx_usage_reset')
                  ->comment('Monthly usage reset processing');
            
            // External system integration
            $table->index(['external_subscription_id'], 'idx_external_sub')
                  ->comment('Payment gateway subscription lookup');
            
            $table->index(['wc_subscription_id'], 'idx_wc_subscription')
                  ->comment('WooCommerce subscription integration');
            
            // Trial and promotional
            $table->index(['trial_end_date'], 'idx_trial_end')
                  ->comment('Trial expiration processing');
            
            $table->index(['coupon_code'], 'idx_coupon_usage')
                  ->comment('Coupon usage analytics');
            
            // Referral system
            $table->index(['referred_by_user_id'], 'idx_referrals')
                  ->comment('Referral tracking and rewards');
            
            // Soft delete support
            $table->index(['deleted_at'], 'idx_deleted_at')
                  ->comment('Soft delete filtering');
            
            // Audit and admin queries
            $table->index(['created_at', 'status'], 'idx_created_status')
                  ->comment('Admin dashboard chronological filtering');
            
            // === FOREIGN KEY CONSTRAINTS ===
            
            // Reference to WordPress users table
            $table->foreignKey('user_id', 'users', 'ID', 'CASCADE', 'CASCADE')
                  ->comment('Link to WordPress user');
            
            // Self-referencing for referrals
            $table->foreignKey('referred_by_user_id', 'users', 'ID', 'SET NULL', 'CASCADE')
                  ->comment('User who made the referral');
            
            // Admin users who manage subscriptions
            $table->foreignKey('created_by_user_id', 'users', 'ID', 'SET NULL', 'CASCADE')
                  ->comment('Admin who created subscription');
            
            $table->foreignKey('modified_by_user_id', 'users', 'ID', 'SET NULL', 'CASCADE')
                  ->comment('Admin who last modified subscription');
        });
    }
    
    /**
     * Run the migration DOWN - Drop the user_subscriptions table
     * 
     * @return bool
     */
    public function down(): bool {
        $this->log("Dropping user_subscriptions table");
        
        return $this->dropTable('user_subscriptions');
    }
    
    /**
     * Seed initial subscription plans
     * 
     * @return bool
     */
    public function seed(): bool {
        $this->log("Seeding default subscription plans");
        
        $defaultPlans = [
            [
                'plan_slug' => 'free',
                'plan_name' => 'Plan Gratuito',
                'plan_type' => 'subscription',
                'test_limit_monthly' => 1,
                'test_limit_total' => 0,
                'allowed_test_types' => json_encode(['test_b5_ai']),
                'plan_features' => json_encode([
                    'max_tests_monthly' => 1,
                    'test_types' => ['test_b5_ai'],
                    'email_support' => false,
                    'priority_support' => false,
                    'whatsapp_integration' => false,
                    'advanced_analytics' => false,
                    'export_results' => false
                ]),
                'plan_price' => 0.00,
                'billing_cycle' => 'monthly'
            ],
            [
                'plan_slug' => 'basic',
                'plan_name' => 'Plan Básico',
                'plan_type' => 'subscription',
                'test_limit_monthly' => 5,
                'test_limit_total' => 0,
                'allowed_test_types' => json_encode(['test_b5_ai', 'cohesion_equipo']),
                'plan_features' => json_encode([
                    'max_tests_monthly' => 5,
                    'test_types' => ['test_b5_ai', 'cohesion_equipo'],
                    'email_support' => true,
                    'priority_support' => false,
                    'whatsapp_integration' => false,
                    'advanced_analytics' => false,
                    'export_results' => true
                ]),
                'plan_price' => 19.99,
                'billing_cycle' => 'monthly'
            ],
            [
                'plan_slug' => 'premium',
                'plan_name' => 'Plan Premium',
                'plan_type' => 'subscription',
                'test_limit_monthly' => 15,
                'test_limit_total' => 0,
                'allowed_test_types' => json_encode(['test_b5_ai', 'cohesion_equipo', 'monitor_test']),
                'plan_features' => json_encode([
                    'max_tests_monthly' => 15,
                    'test_types' => ['test_b5_ai', 'cohesion_equipo', 'monitor_test'],
                    'email_support' => true,
                    'priority_support' => true,
                    'whatsapp_integration' => true,
                    'advanced_analytics' => true,
                    'export_results' => true,
                    'bulk_invitations' => true
                ]),
                'plan_price' => 49.99,
                'billing_cycle' => 'monthly'
            ],
            [
                'plan_slug' => 'enterprise',
                'plan_name' => 'Plan Empresarial',
                'plan_type' => 'subscription',
                'test_limit_monthly' => 0, // Unlimited
                'test_limit_total' => 0,
                'allowed_test_types' => json_encode(['test_b5_ai', 'cohesion_equipo', 'monitor_test']),
                'plan_features' => json_encode([
                    'max_tests_monthly' => 'unlimited',
                    'test_types' => ['test_b5_ai', 'cohesion_equipo', 'monitor_test'],
                    'email_support' => true,
                    'priority_support' => true,
                    'phone_support' => true,
                    'whatsapp_integration' => true,
                    'advanced_analytics' => true,
                    'export_results' => true,
                    'bulk_invitations' => true,
                    'custom_branding' => true,
                    'api_access' => true,
                    'dedicated_manager' => true
                ]),
                'plan_price' => 199.99,
                'billing_cycle' => 'monthly'
            ]
        ];
        
        // Note: En una implementación real, estos planes se insertarían
        // en una tabla separada de planes, no directamente en subscriptions
        // Aquí es solo para documentar la estructura esperada
        
        $this->log("Default plans structure documented");
        return true;
    }
    
    /**
     * Create helper indexes for specific queries
     * 
     * @return bool
     */
    public function createPerformanceIndexes(): bool {
        $this->log("Creating additional performance indexes");
        
        // Composite index for renewal processing
        $this->addIndex('user_subscriptions', 'idx_renewal_processing', 
            ['status', 'auto_renew', 'next_billing_date']);
        
        // Index for usage analytics
        $this->addIndex('user_subscriptions', 'idx_usage_analytics', 
            ['plan_slug', 'created_at', 'status']);
        
        // Index for payment gateway reconciliation
        $this->addIndex('user_subscriptions', 'idx_payment_reconciliation', 
            ['payment_gateway', 'external_subscription_id', 'status']);
        
        return true;
    }
    
    /**
     * Get table schema documentation
     * 
     * @return array
     */
    public function getSchema(): array {
        return [
            'table_name' => 'user_subscriptions',
            'description' => 'User subscription plans, limits, and billing information',
            'primary_purpose' => 'Manage user access levels and test quotas',
            'key_features' => [
                'Multiple subscription plans with different limits',
                'Usage tracking per billing period',
                'Trial period management',
                'Payment gateway integration',
                'WooCommerce integration',
                'Referral system support',
                'Automatic renewal processing',
                'Comprehensive audit trail'
            ],
            'status_workflow' => [
                'pending' => 'Subscription created but not yet active',
                'trialing' => 'In trial period',
                'active' => 'Active subscription with full access',
                'inactive' => 'Temporarily disabled',
                'expired' => 'Subscription period ended',
                'cancelled' => 'User cancelled subscription',
                'suspended' => 'Admin suspended subscription'
            ],
            'plan_types' => [
                'subscription' => 'Recurring subscription with auto-renewal',
                'one_time' => 'Single payment for limited access',
                'lifetime' => 'One-time payment for permanent access'
            ],
            'billing_cycles' => [
                'monthly' => 'Billed every month',
                'yearly' => 'Billed every year',
                'lifetime' => 'One-time payment',
                'one_time' => 'Single purchase'
            ]
        ];
    }
    
    /**
     * Get performance and maintenance information
     * 
     * @return array
     */
    public function getPerformanceInfo(): array {
        return [
            'estimated_records' => [
                'small_site' => '< 1,000 subscriptions',
                'medium_site' => '1,000 - 10,000 subscriptions', 
                'large_site' => '10,000+ subscriptions'
            ],
            'critical_indexes' => [
                'idx_user_status' => 'Essential for user access checks',
                'idx_next_billing' => 'Critical for payment processing',
                'idx_usage_reset' => 'Required for monthly usage resets',
                'unq_user_plan' => 'Prevents duplicate subscriptions'
            ],
            'maintenance_tasks' => [
                'daily' => [
                    'Process trial expirations',
                    'Check subscription expirations',
                    'Send renewal reminders'
                ],
                'monthly' => [
                    'Reset usage counters',
                    'Process billing cycles',
                    'Generate usage analytics'
                ],
                'periodic' => [
                    'Clean up expired tokens',
                    'Archive old cancelled subscriptions',
                    'Reconcile with payment gateways'
                ]
            ],
            'storage_considerations' => [
                'JSON fields grow with feature additions',
                'Metadata field can become large',
                'Consider archiving old subscription history',
                'Monitor index sizes on high-traffic sites'
            ],
            'integration_points' => [
                'WooCommerce Subscriptions plugin',
                'Stripe/PayPal webhooks',
                'Email notification system',
                'WordPress user roles sync',
                'Usage tracking system'
            ]
        ];
    }
    
    /**
     * Validate table structure and data integrity
     * 
     * @return bool
     */
    public function validate(): bool {
        if (!$this->tableExists('user_subscriptions')) {
            $this->log("Validation failed: user_subscriptions table does not exist");
            return false;
        }
        
        // Verify critical columns
        $requiredColumns = [
            'user_id', 'plan_slug', 'status', 'test_limit_monthly',
            'tests_used_current_month', 'subscription_start_date',
            'allowed_test_types', 'plan_features'
        ];
        
        foreach ($requiredColumns as $column) {
            if (!$this->columnExists('user_subscriptions', $column)) {
                $this->log("Validation failed: column {$column} missing");
                return false;
            }
        }
        
        // Verify critical indexes
        $requiredIndexes = [
            'idx_user_status', 'unq_user_plan', 'idx_next_billing'
        ];
        
        foreach ($requiredIndexes as $index) {
            if (!$this->indexExists('user_subscriptions', $index)) {
                $this->log("Validation failed: index {$index} missing");
                return false;
            }
        }
        
        $this->log("Table validation successful");
        return true;
    }
}