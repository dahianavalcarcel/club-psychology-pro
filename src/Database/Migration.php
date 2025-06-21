<?php
/**
 * Migration System
 * 
 * Sistema de migraciones para la gestión de esquemas de base de datos
 * del plugin Club Psychology Pro. Permite crear, modificar y eliminar
 * tablas de forma versionada y controlada.
 * 
 * @package ClubPsychologyPro
 * @subpackage Database
 * @version 1.0.0
 * @author Club Psychology Pro Team
 */

namespace ClubPsychologyPro\Database;

use Exception;
use ClubPsychologyPro\Database\DatabaseManager;

abstract class Migration {
    
    /**
     * Database Manager instance
     * 
     * @var DatabaseManager
     */
    protected DatabaseManager $db;
    
    /**
     * Migration name/identifier
     * 
     * @var string
     */
    protected string $migrationName;
    
    /**
     * Migration version
     * 
     * @var string
     */
    protected string $version;
    
    /**
     * Charset for tables
     * 
     * @var string
     */
    protected string $charset = 'utf8mb4';
    
    /**
     * Collation for tables
     * 
     * @var string
     */
    protected string $collate = 'utf8mb4_unicode_ci';
    
    /**
     * Tables created by this migration
     * 
     * @var array
     */
    protected array $createdTables = [];
    
    /**
     * Columns added by this migration
     * 
     * @var array
     */
    protected array $addedColumns = [];
    
    /**
     * Indexes created by this migration
     * 
     * @var array
     */
    protected array $createdIndexes = [];
    
    /**
     * SQL commands executed
     * 
     * @var array
     */
    protected array $executedCommands = [];
    
    /**
     * Debug mode
     * 
     * @var bool
     */
    protected bool $debug = false;
    
    /**
     * Constructor
     * 
     * @param DatabaseManager $db
     */
    public function __construct(DatabaseManager $db) {
        $this->db = $db;
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
        
        // Set charset/collate from WordPress
        global $wpdb;
        if ($wpdb->charset) {
            $this->charset = $wpdb->charset;
        }
        if ($wpdb->collate) {
            $this->collate = $wpdb->collate;
        }
        
        // Extract migration name from class name
        $this->migrationName = $this->extractMigrationName();
        $this->version = $this->getVersion();
    }
    
    /**
     * Execute the migration UP
     * 
     * @return bool
     */
    abstract public function up(): bool;
    
    /**
     * Execute the migration DOWN (rollback)
     * 
     * @return bool
     */
    abstract public function down(): bool;
    
    /**
     * Get migration version
     * 
     * @return string
     */
    protected function getVersion(): string {
        return date('Y_m_d_His');
    }
    
    /**
     * Create a new table
     * 
     * @param string $tableName Table name without prefix
     * @param callable $schema Callback to define table schema
     * @return bool
     */
    protected function createTable(string $tableName, callable $schema): bool {
        $tableBuilder = new TableBuilder($tableName, $this->db->getPrefix(), $this->charset, $this->collate);
        
        // Execute the schema callback
        $schema($tableBuilder);
        
        // Generate CREATE TABLE SQL
        $sql = $tableBuilder->toSql();
        
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}] Creating table: {$tableName}");
            error_log("SQL: {$sql}");
        }
        
        // Execute the SQL
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $result = dbDelta($sql);
        
        if (!empty($result)) {
            $this->createdTables[] = $tableName;
            $this->executedCommands[] = $sql;
            
            $this->log("Created table: {$tableName}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Drop a table
     * 
     * @param string $tableName Table name without prefix
     * @return bool
     */
    protected function dropTable(string $tableName): bool {
        $fullTableName = $this->db->getTableName($tableName);
        
        if (!$this->tableExists($tableName)) {
            $this->log("Table {$tableName} does not exist, skipping drop");
            return true;
        }
        
        $sql = "DROP TABLE IF EXISTS `{$fullTableName}`";
        
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}] Dropping table: {$tableName}");
            error_log("SQL: {$sql}");
        }
        
        $result = $this->db->query($sql);
        
        if ($result !== false) {
            $this->executedCommands[] = $sql;
            $this->log("Dropped table: {$tableName}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Add a column to an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $definition Column definition (e.g., "VARCHAR(255) NOT NULL")
     * @param string|null $after Add after this column (optional)
     * @return bool
     */
    protected function addColumn(string $tableName, string $columnName, string $definition, ?string $after = null): bool {
        $fullTableName = $this->db->getTableName($tableName);
        
        if ($this->columnExists($tableName, $columnName)) {
            $this->log("Column {$columnName} already exists in {$tableName}, skipping");
            return true;
        }
        
        $sql = "ALTER TABLE `{$fullTableName}` ADD COLUMN `{$columnName}` {$definition}";
        
        if ($after) {
            $sql .= " AFTER `{$after}`";
        }
        
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}] Adding column: {$columnName} to {$tableName}");
            error_log("SQL: {$sql}");
        }
        
        $result = $this->db->query($sql);
        
        if ($result !== false) {
            $this->addedColumns[] = "{$tableName}.{$columnName}";
            $this->executedCommands[] = $sql;
            $this->log("Added column: {$columnName} to {$tableName}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Drop a column from a table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool
     */
    protected function dropColumn(string $tableName, string $columnName): bool {
        $fullTableName = $this->db->getTableName($tableName);
        
        if (!$this->columnExists($tableName, $columnName)) {
            $this->log("Column {$columnName} does not exist in {$tableName}, skipping drop");
            return true;
        }
        
        $sql = "ALTER TABLE `{$fullTableName}` DROP COLUMN `{$columnName}`";
        
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}] Dropping column: {$columnName} from {$tableName}");
            error_log("SQL: {$sql}");
        }
        
        $result = $this->db->query($sql);
        
        if ($result !== false) {
            $this->executedCommands[] = $sql;
            $this->log("Dropped column: {$columnName} from {$tableName}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Add an index to a table
     * 
     * @param string $tableName Table name
     * @param string $indexName Index name
     * @param array $columns Columns for the index
     * @param string $type Index type (INDEX, UNIQUE, FULLTEXT)
     * @return bool
     */
    protected function addIndex(string $tableName, string $indexName, array $columns, string $type = 'INDEX'): bool {
        $fullTableName = $this->db->getTableName($tableName);
        $columnsStr = '`' . implode('`, `', $columns) . '`';
        
        if ($this->indexExists($tableName, $indexName)) {
            $this->log("Index {$indexName} already exists in {$tableName}, skipping");
            return true;
        }
        
        $sql = "ALTER TABLE `{$fullTableName}` ADD {$type} `{$indexName}` ({$columnsStr})";
        
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}] Adding index: {$indexName} to {$tableName}");
            error_log("SQL: {$sql}");
        }
        
        $result = $this->db->query($sql);
        
        if ($result !== false) {
            $this->createdIndexes[] = "{$tableName}.{$indexName}";
            $this->executedCommands[] = $sql;
            $this->log("Added {$type} index: {$indexName} to {$tableName}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Drop an index from a table
     * 
     * @param string $tableName Table name
     * @param string $indexName Index name
     * @return bool
     */
    protected function dropIndex(string $tableName, string $indexName): bool {
        $fullTableName = $this->db->getTableName($tableName);
        
        if (!$this->indexExists($tableName, $indexName)) {
            $this->log("Index {$indexName} does not exist in {$tableName}, skipping drop");
            return true;
        }
        
        $sql = "ALTER TABLE `{$fullTableName}` DROP INDEX `{$indexName}`";
        
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}] Dropping index: {$indexName} from {$tableName}");
            error_log("SQL: {$sql}");
        }
        
        $result = $this->db->query($sql);
        
        if ($result !== false) {
            $this->executedCommands[] = $sql;
            $this->log("Dropped index: {$indexName} from {$tableName}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute raw SQL
     * 
     * @param string $sql SQL query
     * @return bool
     */
    protected function executeSql(string $sql): bool {
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}] Executing raw SQL");
            error_log("SQL: {$sql}");
        }
        
        $result = $this->db->query($sql);
        
        if ($result !== false) {
            $this->executedCommands[] = $sql;
            $this->log("Executed raw SQL");
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if table exists
     * 
     * @param string $tableName Table name
     * @return bool
     */
    protected function tableExists(string $tableName): bool {
        return $this->db->tableExists($tableName);
    }
    
    /**
     * Check if column exists in table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool
     */
    protected function columnExists(string $tableName, string $columnName): bool {
        $fullTableName = $this->db->getTableName($tableName);
        
        $result = $this->db->selectValue(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = %s",
            [$fullTableName, $columnName]
        );
        
        return (int)$result > 0;
    }
    
    /**
     * Check if index exists in table
     * 
     * @param string $tableName Table name
     * @param string $indexName Index name
     * @return bool
     */
    protected function indexExists(string $tableName, string $indexName): bool {
        $fullTableName = $this->db->getTableName($tableName);
        
        $result = $this->db->selectValue(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = %s 
             AND INDEX_NAME = %s",
            [$fullTableName, $indexName]
        );
        
        return (int)$result > 0;
    }
    
    /**
     * Get table structure information
     * 
     * @param string $tableName Table name
     * @return array
     */
    protected function describeTable(string $tableName): array {
        $fullTableName = $this->db->getTableName($tableName);
        
        return $this->db->select("DESCRIBE `{$fullTableName}`") ?: [];
    }
    
    /**
     * Get migration information
     * 
     * @return array
     */
    public function getInfo(): array {
        return [
            'name' => $this->migrationName,
            'version' => $this->version,
            'created_tables' => $this->createdTables,
            'added_columns' => $this->addedColumns,
            'created_indexes' => $this->createdIndexes,
            'executed_commands' => $this->executedCommands
        ];
    }
    
    /**
     * Get migration name from class name
     * 
     * @return string
     */
    protected function extractMigrationName(): string {
        $className = static::class;
        $parts = explode('\\', $className);
        $shortName = end($parts);
        
        // Convert CamelCase to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }
    
    /**
     * Log migration activity
     * 
     * @param string $message Log message
     * @return void
     */
    protected function log(string $message): void {
        if ($this->debug) {
            error_log("Migration [{$this->migrationName}]: {$message}");
        }
    }
}

/**
 * Table Builder Helper Class
 * 
 * Assists in building table creation SQL with a fluent interface
 */
class TableBuilder {
    
    /**
     * Table name
     * 
     * @var string
     */
    private string $tableName;
    
    /**
     * Table prefix
     * 
     * @var string
     */
    private string $prefix;
    
    /**
     * Charset
     * 
     * @var string
     */
    private string $charset;
    
    /**
     * Collation
     * 
     * @var string
     */
    private string $collate;
    
    /**
     * Columns definition
     * 
     * @var array
     */
    private array $columns = [];
    
    /**
     * Indexes definition
     * 
     * @var array
     */
    private array $indexes = [];
    
    /**
     * Primary key
     * 
     * @var string|null
     */
    private ?string $primaryKey = null;
    
    /**
     * Foreign keys
     * 
     * @var array
     */
    private array $foreignKeys = [];
    
    /**
     * Table options
     * 
     * @var array
     */
    private array $options = [];
    
    /**
     * Constructor
     * 
     * @param string $tableName
     * @param string $prefix
     * @param string $charset
     * @param string $collate
     */
    public function __construct(string $tableName, string $prefix, string $charset = 'utf8mb4', string $collate = 'utf8mb4_unicode_ci') {
        $this->tableName = $tableName;
        $this->prefix = $prefix;
        $this->charset = $charset;
        $this->collate = $collate;
    }
    
    /**
     * Add auto-incrementing ID column
     * 
     * @param string $name Column name (default: 'id')
     * @return TableBuilder
     */
    public function id(string $name = 'id'): TableBuilder {
        $this->columns[] = "`{$name}` bigint(20) unsigned NOT NULL AUTO_INCREMENT";
        $this->primaryKey = $name;
        return $this;
    }
    
    /**
     * Add integer column
     * 
     * @param string $name Column name
     * @param int $length Length
     * @param bool $unsigned Unsigned
     * @param bool $nullable Nullable
     * @param mixed $default Default value
     * @return TableBuilder
     */
    public function integer(string $name, int $length = 11, bool $unsigned = false, bool $nullable = false, $default = null): TableBuilder {
        $definition = "`{$name}` int({$length})";
        
        if ($unsigned) {
            $definition .= ' unsigned';
        }
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        if ($default !== null) {
            $definition .= " DEFAULT '{$default}'";
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add big integer column
     * 
     * @param string $name Column name
     * @param bool $unsigned Unsigned
     * @param bool $nullable Nullable
     * @param mixed $default Default value
     * @return TableBuilder
     */
    public function bigInteger(string $name, bool $unsigned = false, bool $nullable = false, $default = null): TableBuilder {
        $definition = "`{$name}` bigint(20)";
        
        if ($unsigned) {
            $definition .= ' unsigned';
        }
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        if ($default !== null) {
            $definition .= " DEFAULT '{$default}'";
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add string/varchar column
     * 
     * @param string $name Column name
     * @param int $length Length
     * @param bool $nullable Nullable
     * @param string|null $default Default value
     * @return TableBuilder
     */
    public function string(string $name, int $length = 255, bool $nullable = false, ?string $default = null): TableBuilder {
        $definition = "`{$name}` varchar({$length})";
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        if ($default !== null) {
            $definition .= " DEFAULT '{$default}'";
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add text column
     * 
     * @param string $name Column name
     * @param bool $nullable Nullable
     * @return TableBuilder
     */
    public function text(string $name, bool $nullable = false): TableBuilder {
        $definition = "`{$name}` text";
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add longtext column
     * 
     * @param string $name Column name
     * @param bool $nullable Nullable
     * @return TableBuilder
     */
    public function longText(string $name, bool $nullable = false): TableBuilder {
        $definition = "`{$name}` longtext";
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add JSON column
     * 
     * @param string $name Column name
     * @param bool $nullable Nullable
     * @return TableBuilder
     */
    public function json(string $name, bool $nullable = false): TableBuilder {
        $definition = "`{$name}` json";
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add boolean column
     * 
     * @param string $name Column name
     * @param bool $default Default value
     * @return TableBuilder
     */
    public function boolean(string $name, bool $default = false): TableBuilder {
        $defaultValue = $default ? '1' : '0';
        $this->columns[] = "`{$name}` tinyint(1) NOT NULL DEFAULT '{$defaultValue}'";
        return $this;
    }
    
    /**
     * Add timestamp column
     * 
     * @param string $name Column name
     * @param bool $nullable Nullable
     * @param string|null $default Default value
     * @return TableBuilder
     */
    public function timestamp(string $name, bool $nullable = false, ?string $default = null): TableBuilder {
        $definition = "`{$name}` timestamp";
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        if ($default !== null) {
            $definition .= " DEFAULT {$default}";
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add created_at and updated_at timestamps
     * 
     * @return TableBuilder
     */
    public function timestamps(): TableBuilder {
        $this->columns[] = "`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }
    
    /**
     * Add enum column
     * 
     * @param string $name Column name
     * @param array $values Possible values
     * @param string|null $default Default value
     * @return TableBuilder
     */
    public function enum(string $name, array $values, ?string $default = null): TableBuilder {
        $valuesStr = "'" . implode("', '", $values) . "'";
        $definition = "`{$name}` enum({$valuesStr}) NOT NULL";
        
        if ($default !== null) {
            $definition .= " DEFAULT '{$default}'";
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add decimal column
     * 
     * @param string $name Column name
     * @param int $precision Total digits
     * @param int $scale Decimal places
     * @param bool $nullable Nullable
     * @param float|null $default Default value
     * @return TableBuilder
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2, bool $nullable = false, ?float $default = null): TableBuilder {
        $definition = "`{$name}` decimal({$precision},{$scale})";
        
        if (!$nullable) {
            $definition .= ' NOT NULL';
        }
        
        if ($default !== null) {
            $definition .= " DEFAULT {$default}";
        }
        
        $this->columns[] = $definition;
        return $this;
    }
    
    /**
     * Add index
     * 
     * @param array|string $columns Columns
     * @param string|null $name Index name
     * @return TableBuilder
     */
    public function index($columns, ?string $name = null): TableBuilder {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?: 'idx_' . implode('_', $columns);
        $columnsStr = '`' . implode('`, `', $columns) . '`';
        
        $this->indexes[] = "INDEX `{$name}` ({$columnsStr})";
        return $this;
    }
    
    /**
     * Add unique index
     * 
     * @param array|string $columns Columns
     * @param string|null $name Index name
     * @return TableBuilder
     */
    public function unique($columns, ?string $name = null): TableBuilder {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?: 'unq_' . implode('_', $columns);
        $columnsStr = '`' . implode('`, `', $columns) . '`';
        
        $this->indexes[] = "UNIQUE INDEX `{$name}` ({$columnsStr})";
        return $this;
    }
    
    /**
     * Add foreign key constraint
     * 
     * @param string $column Local column
     * @param string $referenceTable Reference table
     * @param string $referenceColumn Reference column
     * @param string $onDelete On delete action
     * @param string $onUpdate On update action
     * @return TableBuilder
     */
    public function foreignKey(string $column, string $referenceTable, string $referenceColumn = 'id', string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE'): TableBuilder {
        $constraintName = "fk_{$this->tableName}_{$column}";
        
        $this->foreignKeys[] = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$column}`) REFERENCES `{$this->prefix}{$referenceTable}` (`{$referenceColumn}`) ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
        return $this;
    }
    
    /**
     * Set table engine
     * 
     * @param string $engine Engine type
     * @return TableBuilder
     */
    public function engine(string $engine = 'InnoDB'): TableBuilder {
        $this->options['ENGINE'] = $engine;
        return $this;
    }
    
    /**
     * Generate CREATE TABLE SQL
     * 
     * @return string
     */
    public function toSql(): string {
        $fullTableName = $this->prefix . $this->tableName;
        
        $sql = "CREATE TABLE `{$fullTableName}` (\n";
        
        // Add columns
        $definitions = $this->columns;
        
        // Add primary key
        if ($this->primaryKey) {
            $definitions[] = "PRIMARY KEY (`{$this->primaryKey}`)";
        }
        
        // Add indexes
        $definitions = array_merge($definitions, $this->indexes);
        
        // Add foreign keys
        $definitions = array_merge($definitions, $this->foreignKeys);
        
        $sql .= "  " . implode(",\n  ", $definitions) . "\n";
        $sql .= ")";
        
        // Add table options
        $options = array_merge([
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => $this->charset,
            'COLLATE' => $this->collate
        ], $this->options);
        
        foreach ($options as $key => $value) {
            $sql .= " {$key}={$value}";
        }
        
        $sql .= ";";
        
        return $sql;
    }
}