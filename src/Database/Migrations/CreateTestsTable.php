<?php
/**
 * Create Tests Table Migration
 * 
 * Migración para crear la tabla principal de tests del sistema
 * Club Psychology Pro. Esta tabla almacena la información básica
 * de cada test solicitado por los usuarios.
 * 
 * @package ClubPsychologyPro
 * @subpackage Database\Migrations
 * @version 1.0.0
 * @author Club Psychology Pro Team
 */

namespace ClubPsychologyPro\Database\Migrations;

use ClubPsychologyPro\Database\Migration;
use ClubPsychologyPro\Database\TableBuilder;

class CreateTestsTable extends Migration {
    
    /**
     * Migration version
     * 
     * @var string
     */
    protected string $version = '2024_01_01_000001';
    
    /**
     * Run the migration UP - Create the tests table
     * 
     * @return bool
     */
    public function up(): bool {
        $this->log("Starting creation of tests table");
        
        return $this->createTable('tests', function(TableBuilder $table) {
            // Primary key
            $table->id();
            
            // User information
            $table->bigInteger('user_id', true)
                  ->comment('WordPress user ID who created the test');
            
            // Test type and configuration
            $table->string('test_type', 50)
                  ->comment('Type of test: test_b5_ai, cohesion_equipo, monitor_test');
            
            $table->string('test_subtype', 50, true)
                  ->comment('Subtype for monitor tests: bronca, ansiedad, sugestion, etc.');
            
            // Participant information
            $table->string('participant_name')
                  ->comment('Name of the person taking the test');
            
            $table->string('participant_email')
                  ->comment('Email of the participant');
            
            $table->date('participant_birth_date', true)
                 ->comment('Birth date of the participant');
            
            // Test status and workflow
            $table->enum('status', [
                'pending',      // Test created, invitation sent
                'in_progress',  // Test started by participant
                'completed',    // Test finished, results available
                'expired',      // Test expired (optional timeout)
                'cancelled'     // Test cancelled by user
            ], 'pending')->comment('Current status of the test');
            
            // Test configuration and metadata
            $table->json('test_config', true)
                  ->comment('Test-specific configuration and parameters');
            
            $table->string('invitation_token', 64, true)
                  ->comment('Unique token for test access via email link');
            
            $table->timestamp('token_expires_at', true)
                  ->comment('Expiration date for the invitation token');
            
            // Email and communication tracking
            $table->boolean('email_sent', false)
                  ->comment('Whether invitation email was sent successfully');
            
            $table->timestamp('email_sent_at', true)
                  ->comment('When the invitation email was sent');
            
            $table->integer('email_attempts', false, false, 0)
                  ->comment('Number of email send attempts');
            
            $table->text('email_error', true)
                  ->comment('Last email sending error message');
            
            // WhatsApp integration (future feature)
            $table->boolean('whatsapp_sent', false)
                  ->comment('Whether WhatsApp invitation was sent');
            
            $table->timestamp('whatsapp_sent_at', true)
                  ->comment('When WhatsApp invitation was sent');
            
            $table->string('whatsapp_phone', 20, true)
                  ->comment('WhatsApp phone number for invitation');
            
            // Test execution tracking
            $table->timestamp('started_at', true)
                  ->comment('When participant started the test');
            
            $table->timestamp('completed_at', true)
                  ->comment('When participant completed the test');
            
            $table->integer('completion_time_seconds', true)
                  ->comment('Total time taken to complete test in seconds');
            
            // Results reference
            $table->boolean('has_results', false)
                  ->comment('Whether test results are available');
            
            $table->string('result_summary', 100, true)
                  ->comment('Brief summary of test results');
            
            // Analytics and tracking
            $table->string('user_agent', 500, true)
                  ->comment('Browser user agent when test was taken');
            
            $table->string('ip_address', 45, true)
                  ->comment('IP address of participant');
            
            $table->string('referrer_url', 500, true)
                  ->comment('Referring URL if any');
            
            // Soft delete support
            $table->timestamp('deleted_at', true)
                  ->comment('Soft delete timestamp');
            
            // Standard timestamps
            $table->timestamps();
            
            // === INDEXES FOR PERFORMANCE ===
            
            // Primary queries - user tests
            $table->index(['user_id', 'status'], 'idx_user_status')
                  ->comment('Fast lookup of user tests by status');
            
            $table->index(['user_id', 'created_at'], 'idx_user_created')
                  ->comment('Chronological user test listing');
            
            // Test type and subtype queries
            $table->index(['test_type', 'status'], 'idx_type_status')
                  ->comment('Analytics by test type and status');
            
            $table->index(['test_type', 'test_subtype'], 'idx_type_subtype')
                  ->comment('Monitor test subtype filtering');
            
            // Participant email for duplicate prevention
            $table->index('participant_email', 'idx_participant_email')
                  ->comment('Participant email lookup');
            
            // Token-based access
            $table->unique('invitation_token', 'unq_invitation_token')
                  ->comment('Unique token for secure test access');
            
            // Status-based queries for admin
            $table->index(['status', 'created_at'], 'idx_status_created')
                  ->comment('Admin dashboard status filtering');
            
            // Completion tracking
            $table->index(['completed_at'], 'idx_completed_at')
                  ->comment('Completed tests chronological order');
            
            // Email tracking
            $table->index(['email_sent', 'email_sent_at'], 'idx_email_tracking')
                  ->comment('Email delivery tracking');
            
            // Soft delete support
            $table->index('deleted_at', 'idx_deleted_at')
                  ->comment('Soft delete filtering');
            
            // === FOREIGN KEY CONSTRAINTS ===
            
            // Reference to WordPress users table
            $table->foreignKey('user_id', 'users', 'ID', 'CASCADE', 'CASCADE')
                  ->comment('Link to WordPress user who created the test');
        });
    }
    
    /**
     * Run the migration DOWN - Drop the tests table
     * 
     * @return bool
     */
    public function down(): bool {
        $this->log("Dropping tests table");
        
        // Drop related indexes first (if needed)
        if ($this->tableExists('test_results')) {
            $this->log("Warning: test_results table exists. Consider dropping it first.");
        }
        
        if ($this->tableExists('test_sessions')) {
            $this->log("Warning: test_sessions table exists. Consider dropping it first.");
        }
        
        return $this->dropTable('tests');
    }
    
    /**
     * Get table schema documentation
     * 
     * @return array
     */
    public function getSchema(): array {
        return [
            'table_name' => 'tests',
            'description' => 'Main table for storing test requests and basic information',
            'columns' => [
                'id' => 'Auto-increment primary key',
                'user_id' => 'WordPress user ID (foreign key to wp_users.ID)',
                'test_type' => 'Type of test: test_b5_ai, cohesion_equipo, monitor_test',
                'test_subtype' => 'Subtype for monitor tests (bronca, ansiedad, etc.)',
                'participant_name' => 'Full name of the person taking the test',
                'participant_email' => 'Email address for test invitation',
                'participant_birth_date' => 'Birth date for age-related analysis',
                'status' => 'Current workflow status of the test',
                'test_config' => 'JSON configuration specific to test type',
                'invitation_token' => 'Secure token for email link access',
                'token_expires_at' => 'Expiration time for the invitation',
                'email_sent' => 'Email delivery success flag',
                'email_sent_at' => 'Timestamp of email sending',
                'email_attempts' => 'Number of email send attempts',
                'email_error' => 'Last email error message',
                'whatsapp_sent' => 'WhatsApp delivery flag (future)',
                'whatsapp_sent_at' => 'WhatsApp sending timestamp',
                'whatsapp_phone' => 'WhatsApp phone number',
                'started_at' => 'When participant began the test',
                'completed_at' => 'When participant finished the test',
                'completion_time_seconds' => 'Total test duration',
                'has_results' => 'Whether results are available',
                'result_summary' => 'Brief summary of test outcome',
                'user_agent' => 'Browser information',
                'ip_address' => 'Participant IP address',
                'referrer_url' => 'Source URL if applicable',
                'deleted_at' => 'Soft delete timestamp',
                'created_at' => 'Record creation timestamp',
                'updated_at' => 'Last modification timestamp'
            ],
            'indexes' => [
                'PRIMARY' => ['id'],
                'idx_user_status' => ['user_id', 'status'],
                'idx_user_created' => ['user_id', 'created_at'],
                'idx_type_status' => ['test_type', 'status'],
                'idx_type_subtype' => ['test_type', 'test_subtype'],
                'idx_participant_email' => ['participant_email'],
                'unq_invitation_token' => ['invitation_token'] // UNIQUE,
                'idx_status_created' => ['status', 'created_at'],
                'idx_completed_at' => ['completed_at'],
                'idx_email_tracking' => ['email_sent', 'email_sent_at'],
                'idx_deleted_at' => ['deleted_at']
            ],
            'foreign_keys' => [
                'fk_tests_user_id' => [
                    'column' => 'user_id',
                    'references' => 'wp_users.ID',
                    'on_delete' => 'CASCADE',
                    'on_update' => 'CASCADE'
                ]
            ]
        ];
    }
    
    /**
     * Seed initial data if needed
     * 
     * @return bool
     */
    public function seed(): bool {
        $this->log("Seeding initial test data");
        
        // Aquí puedes insertar datos de ejemplo si es necesario
        // Por ejemplo, tipos de test válidos, configuraciones por defecto, etc.
        
        return true;
    }
    
    /**
     * Validate table structure after creation
     * 
     * @return bool
     */
    public function validate(): bool {
        if (!$this->tableExists('tests')) {
            $this->log("Validation failed: tests table does not exist");
            return false;
        }
        
        // Verify critical columns exist
        $requiredColumns = [
            'id', 'user_id', 'test_type', 'participant_name', 
            'participant_email', 'status', 'created_at', 'updated_at'
        ];
        
        foreach ($requiredColumns as $column) {
            if (!$this->columnExists('tests', $column)) {
                $this->log("Validation failed: column {$column} does not exist");
                return false;
            }
        }
        
        // Verify critical indexes exist
        $requiredIndexes = [
            'idx_user_status', 'idx_type_status', 'unq_invitation_token'
        ];
        
        foreach ($requiredIndexes as $index) {
            if (!$this->indexExists('tests', $index)) {
                $this->log("Validation failed: index {$index} does not exist");
                return false;
            }
        }
        
        $this->log("Table validation successful");
        return true;
    }
    
    /**
     * Get estimated table size and performance info
     * 
     * @return array
     */
    public function getPerformanceInfo(): array {
        return [
            'estimated_rows_per_user' => 10, // Average tests per user
            'estimated_total_rows' => 10000, // For 1000 users
            'critical_indexes' => [
                'idx_user_status' => 'Essential for user dashboard',
                'idx_type_status' => 'Essential for admin analytics',
                'unq_invitation_token' => 'Essential for secure access'
            ],
            'storage_considerations' => [
                'test_config' => 'JSON field, can grow large with complex tests',
                'email_error' => 'TEXT field for error messages',
                'user_agent' => 'Can be large for some browsers'
            ],
            'maintenance_notes' => [
                'Consider partitioning by created_at for large datasets',
                'Monitor JSON field sizes in test_config',
                'Clean up expired tokens periodically',
                'Archive old completed tests if needed'
            ]
        ];
    }
}