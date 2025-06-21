<?php
/**
 * Create Test Results Table Migration
 * 
 * Migración para crear la tabla de resultados de tests del sistema
 * Club Psychology Pro. Esta tabla almacena los resultados detallados
 * y cálculos de todos los tipos de tests.
 * 
 * @package ClubPsychologyPro
 * @subpackage Database\Migrations
 * @version 1.0.0
 * @author Club Psychology Pro Team
 */

namespace ClubPsychologyPro\Database\Migrations;

use ClubPsychologyPro\Database\Migration;
use ClubPsychologyPro\Database\TableBuilder;

class CreateTestResultsTable extends Migration {
    
    /**
     * Migration version
     * 
     * @var string
     */
    protected string $version = '2024_01_01_000002';
    
    /**
     * Run the migration UP - Create the test_results table
     * 
     * @return bool
     */
    public function up(): bool {
        $this->log("Starting creation of test_results table");
        
        return $this->createTable('test_results', function(TableBuilder $table) {
            // Primary key
            $table->id();
            
            // Reference to main test
            $table->bigInteger('test_id', true)
                  ->comment('Foreign key to tests table');
            
            // Test identification
            $table->string('test_type', 50)
                  ->comment('Type of test: test_b5_ai, cohesion_equipo, monitor_test');
            
            $table->string('test_subtype', 50, true)
                  ->comment('Subtype for monitor tests');
            
            // Participant identification (denormalized for performance)
            $table->string('participant_name')
                  ->comment('Participant name (denormalized from tests table)');
            
            $table->string('participant_email')
                  ->comment('Participant email (denormalized from tests table)');
            
            $table->date('participant_birth_date', true)
                 ->comment('Participant birth date for age calculations');
            
            // Raw test data
            $table->json('raw_responses')
                  ->comment('Complete raw responses from the participant');
            
            $table->json('response_metadata', true)
                  ->comment('Metadata about responses: timing, IP, browser, etc.');
            
            // === CALCULATED SCORES AND RESULTS ===
            
            // Primary scores (main results)
            $table->json('primary_scores')
                  ->comment('Main calculated scores (e.g., Big5 factors, cohesion dimensions)');
            
            // Secondary scores (subscales, facets)
            $table->json('secondary_scores', true)
                  ->comment('Detailed subscale scores and facets');
            
            // Percentiles and norms
            $table->json('percentiles', true)
                  ->comment('Percentile scores based on normative data');
            
            $table->json('norm_references', true)
                  ->comment('Information about normative groups used');
            
            // === INTERPRETATION AND ANALYSIS ===
            
            // Overall result level/category
            $table->string('overall_level', 50, true)
                  ->comment('Overall result level: Low, Moderate, High, etc.');
            
            $table->text('overall_interpretation', true)
                  ->comment('Main interpretation summary');
            
            // Detailed interpretations per dimension
            $table->json('dimension_interpretations', true)
                  ->comment('Interpretations for each measured dimension');
            
            // Risk flags and alerts
            $table->json('risk_flags', true)
                  ->comment('Clinical or risk indicators if applicable');
            
            $table->boolean('requires_followup', false)
                  ->comment('Whether results suggest professional followup');
            
            // === RECOMMENDATIONS AND FEEDBACK ===
            
            $table->json('recommendations', true)
                  ->comment('Personalized recommendations based on results');
            
            $table->text('feedback_summary', true)
                  ->comment('Summary feedback for the participant');
            
            $table->json('development_areas', true)
                  ->comment('Areas identified for development or attention');
            
            // === VALIDITY AND QUALITY INDICATORS ===
            
            $table->decimal('response_validity_score', 5, 3, true)
                  ->comment('Validity score for response pattern (0-1)');
            
            $table->json('validity_indicators', true)
                  ->comment('Detailed validity checks and flags');
            
            $table->boolean('results_valid', true)
                  ->comment('Whether results are considered valid');
            
            $table->text('validity_notes', true)
                  ->comment('Notes about validity concerns if any');
            
            // === TEST-SPECIFIC DATA ===
            
            // Big Five specific
            $table->json('bigfive_data', true)
                  ->comment('Big Five specific calculations and data');
            
            // Cohesion specific
            $table->json('cohesion_data', true)
                  ->comment('Team cohesion specific data');
            
            // Monitor test specific
            $table->json('monitor_data', true)
                  ->comment('Monitor test specific calculations');
            
            // === COMPARISON AND BENCHMARKING ===
            
            $table->json('comparison_groups', true)
                  ->comment('Relevant comparison groups and percentiles');
            
            $table->json('benchmarking_data', true)
                  ->comment('Industry or demographic benchmarking');
            
            // === PROCESSING METADATA ===
            
            $table->string('calculation_version', 20)
                  ->comment('Version of calculation algorithms used');
            
            $table->json('processing_metadata')
                  ->comment('Technical metadata about result processing');
            
            $table->timestamp('calculated_at')
                  ->comment('When results were calculated');
            
            $table->string('calculated_by', 100, true)
                  ->comment('System or user that calculated results');
            
            // === EXPORT AND SHARING ===
            
            $table->boolean('pdf_generated', false)
                  ->comment('Whether PDF report has been generated');
            
            $table->string('pdf_file_path', 500, true)
                  ->comment('Path to generated PDF report');
            
            $table->timestamp('pdf_generated_at', true)
                  ->comment('When PDF was generated');
            
            $table->boolean('results_shared', false)
                  ->comment('Whether results were shared with participant');
            
            $table->timestamp('shared_at', true)
                  ->comment('When results were first shared');
            
            $table->integer('view_count', false, false, 0)
                  ->comment('Number of times results were viewed');
            
            $table->timestamp('last_viewed_at', true)
                  ->comment('Last time results were accessed');
            
            // === REVISION AND VERSIONING ===
            
            $table->integer('result_version', false, false, 1)
                  ->comment('Version number for result revisions');
            
            $table->text('revision_notes', true)
                  ->comment('Notes about result revisions if any');
            
            $table->json('previous_versions', true)
                  ->comment('Archive of previous result versions');
            
            // === PRIVACY AND ANONYMIZATION ===
            
            $table->boolean('anonymized', false)
                  ->comment('Whether personal data has been anonymized');
            
            $table->timestamp('anonymized_at', true)
                  ->comment('When data was anonymized');
            
            $table->string('anonymization_method', 100, true)
                  ->comment('Method used for anonymization');
            
            // Soft delete support
            $table->timestamp('deleted_at', true)
                  ->comment('Soft delete timestamp');
            
            // Standard timestamps
            $table->timestamps();
            
            // === INDEXES FOR PERFORMANCE ===
            
            // Primary relationship
            $table->unique('test_id', 'unq_test_result')
                  ->comment('One result per test');
            
            // Type-based queries
            $table->index(['test_type', 'test_subtype'], 'idx_test_types')
                  ->comment('Filtering by test type and subtype');
            
            // Result level analysis
            $table->index(['test_type', 'overall_level'], 'idx_type_level')
                  ->comment('Analytics by test type and result level');
            
            // Validity filtering
            $table->index(['results_valid', 'calculated_at'], 'idx_validity_date')
                  ->comment('Valid results chronological order');
            
            // Participant queries (for anonymization/privacy)
            $table->index('participant_email', 'idx_participant_email')
                  ->comment('Participant result lookup');
            
            // PDF and sharing tracking
            $table->index(['pdf_generated', 'results_shared'], 'idx_export_status')
                  ->comment('Export and sharing status tracking');
            
            // Age-based analysis
            $table->index('participant_birth_date', 'idx_birth_date')
                  ->comment('Age demographic analysis');
            
            // Processing version tracking
            $table->index(['calculation_version', 'calculated_at'], 'idx_calc_version')
                  ->comment('Version tracking for algorithm updates');
            
            // Anonymization tracking
            $table->index(['anonymized', 'anonymized_at'], 'idx_anonymization')
                  ->comment('Privacy compliance tracking');
            
            // Soft delete support
            $table->index('deleted_at', 'idx_deleted_at')
                  ->comment('Soft delete filtering');
            
            // === FOREIGN KEY CONSTRAINTS ===
            
            // Reference to tests table
            $table->foreignKey('test_id', 'tests', 'id', 'CASCADE', 'CASCADE')
                  ->comment('Link to main test record');
        });
    }
    
    /**
     * Run the migration DOWN - Drop the test_results table
     * 
     * @return bool
     */
    public function down(): bool {
        $this->log("Dropping test_results table");
        
        return $this->dropTable('test_results');
    }
    
    /**
     * Get table schema documentation
     * 
     * @return array
     */
    public function getSchema(): array {
        return [
            'table_name' => 'test_results',
            'description' => 'Detailed test results and calculations for all test types',
            'relationships' => [
                'tests' => 'One-to-one relationship with tests table'
            ],
            'column_groups' => [
                'identification' => ['id', 'test_id', 'test_type', 'test_subtype'],
                'participant_data' => ['participant_name', 'participant_email', 'participant_birth_date'],
                'raw_data' => ['raw_responses', 'response_metadata'],
                'calculated_scores' => ['primary_scores', 'secondary_scores', 'percentiles'],
                'interpretation' => ['overall_level', 'overall_interpretation', 'dimension_interpretations'],
                'validity' => ['response_validity_score', 'validity_indicators', 'results_valid'],
                'specific_data' => ['bigfive_data', 'cohesion_data', 'monitor_data'],
                'processing' => ['calculation_version', 'processing_metadata', 'calculated_at'],
                'export' => ['pdf_generated', 'pdf_file_path', 'results_shared'],
                'privacy' => ['anonymized', 'anonymized_at', 'anonymization_method']
            ],
            'json_fields' => [
                'raw_responses' => 'Complete participant responses',
                'primary_scores' => 'Main calculated scores',
                'secondary_scores' => 'Detailed subscale scores',
                'dimension_interpretations' => 'Per-dimension analysis',
                'bigfive_data' => 'Big Five specific data',
                'cohesion_data' => 'Team cohesion data',
                'monitor_data' => 'Monitor test data',
                'validity_indicators' => 'Response validity checks'
            ]
        ];
    }
    
    /**
     * Create sample data structure for different test types
     * 
     * @return array
     */
    public function getSampleDataStructures(): array {
        return [
            'bigfive_primary_scores' => [
                'O' => ['score' => 3.2, 'percentile' => 65],
                'C' => ['score' => 4.1, 'percentile' => 78],
                'E' => ['score' => 2.8, 'percentile' => 42],
                'A' => ['score' => 4.5, 'percentile' => 85],
                'N' => ['score' => 3.0, 'percentile' => 55]
            ],
            'cohesion_primary_scores' => [
                'ATGS' => ['score' => 7.2, 'level' => 'High'],
                'ATGT' => ['score' => 6.8, 'level' => 'Moderate'],
                'GIS' => ['score' => 5.9, 'level' => 'Moderate'],
                'GIT' => ['score' => 7.5, 'level' => 'High'],
                'total' => ['score' => 6.85, 'level' => 'High']
            ],
            'monitor_anger_primary_scores' => [
                'ARS_ANGTHTS' => ['score' => 12, 'level' => 'Moderate'],
                'ARS_REV' => ['score' => 8, 'level' => 'Low'],
                'ARS_MEM' => ['score' => 14, 'level' => 'Moderate'],
                'ARS_CAUSE' => ['score' => 10, 'level' => 'Moderate'],
                'ARS_TOT' => ['score' => 44, 'level' => 'Moderate']
            ],
            'validity_indicators' => [
                'response_time_consistency' => 0.85,
                'acquiescence_bias' => 0.12,
                'extreme_responding' => 0.08,
                'response_pattern_validity' => 0.91,
                'overall_validity' => 0.89
            ]
        ];
    }
    
    /**
     * Validate table structure after creation
     * 
     * @return bool
     */
    public function validate(): bool {
        if (!$this->tableExists('test_results')) {
            $this->log("Validation failed: test_results table does not exist");
            return false;
        }
        
        // Verify critical columns exist
        $requiredColumns = [
            'id', 'test_id', 'test_type', 'raw_responses', 
            'primary_scores', 'calculated_at', 'created_at'
        ];
        
        foreach ($requiredColumns as $column) {
            if (!$this->columnExists('test_results', $column)) {
                $this->log("Validation failed: column {$column} does not exist");
                return false;
            }
        }
        
        // Verify foreign key constraint
        if (!$this->tableExists('tests')) {
            $this->log("Warning: tests table does not exist, foreign key constraint may fail");
        }
        
        // Verify unique constraint on test_id
        if (!$this->indexExists('test_results', 'unq_test_result')) {
            $this->log("Validation failed: unique constraint on test_id does not exist");
            return false;
        }
        
        $this->log("Table validation successful");
        return true;
    }
    
    /**
     * Performance considerations and recommendations
     * 
     * @return array
     */
    public function getPerformanceInfo(): array {
        return [
            'storage_considerations' => [
                'json_fields' => 'Multiple JSON fields can grow large, monitor storage usage',
                'raw_responses' => 'Can be very large for complex tests with many questions',
                'pdf_files' => 'PDF files stored separately, only paths in database',
                'previous_versions' => 'Versioning can accumulate significant data'
            ],
            'index_strategy' => [
                'unq_test_result' => 'Ensures one result per test, critical for data integrity',
                'idx_test_types' => 'Essential for analytics and filtering',
                'idx_validity_date' => 'Performance critical for valid results queries'
            ],
            'optimization_tips' => [
                'Consider archiving old results after certain period',
                'Monitor JSON field sizes, especially raw_responses',
                'Use selective column queries for large result sets',
                'Consider read replicas for analytics queries',
                'Implement result caching for frequently accessed data'
            ],
            'maintenance_tasks' => [
                'Regular cleanup of soft-deleted records',
                'Archival of old calculation versions',
                'PDF file cleanup for deleted results',
                'Anonymization of old participant data',
                'Performance monitoring of JSON field queries'
            ]
        ];
    }
    
    /**
     * Get data retention and privacy guidelines
     * 
     * @return array
     */
    public function getPrivacyGuidelines(): array {
        return [
            'data_retention' => [
                'raw_responses' => 'Consider anonymizing after 2 years',
                'participant_data' => 'Subject to GDPR/privacy laws',
                'results' => 'Can be retained longer if anonymized',
                'pdf_reports' => 'Should follow same retention as raw data'
            ],
            'anonymization_fields' => [
                'participant_name' => 'Replace with anonymous ID',
                'participant_email' => 'Hash or remove entirely',
                'participant_birth_date' => 'Convert to age ranges',
                'response_metadata' => 'Remove IP addresses and identifying info'
            ],
            'compliance_features' => [
                'soft_delete' => 'Allows for data recovery periods',
                'anonymization_tracking' => 'Audit trail for privacy compliance',
                'version_control' => 'Track data modifications',
                'export_functionality' => 'Support for data portability rights'
            ]
        ];
    }
}