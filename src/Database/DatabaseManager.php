<?php
/**
 * Database Manager
 * 
 * Gestor principal de base de datos para el plugin Club Psychology Pro.
 * Proporciona una capa de abstracción sobre wpdb con funcionalidades
 * avanzadas como transacciones, query builder, cache y migraciones.
 * 
 * @package ClubPsychologyPro
 * @subpackage Database
 * @version 1.0.0
 */

namespace ClubPsychologyPro\Database;

use wpdb;
use Exception;
use ClubPsychologyPro\Core\EventManager;

class DatabaseManager {
    
    /** @var wpdb Instancia de WordPress Database */
    private wpdb $wpdb;
    
    /** @var string Prefijo de tablas del plugin */
    private string $prefix;
    
    /** @var array Configuración de la base de datos */
    private array $config;
    
    /** @var array Cache de queries */
    private array $queryCache = [];
    
    /** @var bool Estado de transacción actual */
    private bool $inTransaction = false;
    
    /** @var int Nivel de anidamiento de transacciones */
    private int $transactionLevel = 0;
    
    /** @var EventManager|null Event Manager para hooks */
    private ?EventManager $events = null;
    
    /** @var array Mapeo de nombres de tabla a nombres completos */
    private array $tableMap = [];
    
    /** @var bool Cache habilitado */
    private bool $cacheEnabled = true;
    
    /** @var int TTL del cache (en segundos) */
    private int $cacheTtl = 300; // 5 minutos
    
    /**
     * Constructor
     * 
     * @param wpdb|null $wpdbInstancia Instancia de wpdb (opcional)
     * @param array     $config        Configuración de base de datos
     */
    public function __construct(?wpdb $wpdbInstancia = null, array $config = []) {
        // Obtener la instancia global de wpdb si no se pasa una
        global $wpdb;
        $this->wpdb   = $wpdbInstancia ?? $wpdb;
        $this->prefix = $this->wpdb->prefix . 'cpp_';
        
        // Configuración por defecto + sobreescritura
        $this->config = array_merge([
            'cache_enabled' => true,
            'cache_ttl'     => 300,
            'debug_mode'    => WP_DEBUG,
            'charset'       => 'utf8mb4',
            'collate'       => 'utf8mb4_unicode_ci',
        ], $config);
        
        $this->cacheEnabled = (bool) $this->config['cache_enabled'];
        $this->cacheTtl     = (int)  $this->config['cache_ttl'];
        
        $this->initializeTables();
        $this->setupErrorHandling();
    }
    
    /**
     * Inyecta el Event Manager
     */
    public function setEventManager(EventManager $events): void {
        $this->events = $events;
    }
    
    /**
     * Obtener la instancia de wpdb
     */
    public function getWpdb(): wpdb {
        return $this->wpdb;
    }
    
    /**
     * Obtener prefijo de tablas
     */
    public function getPrefix(): string {
        return $this->prefix;
    }
    
    /**
     * Obtener nombre completo de tabla
     */
    public function getTableName(string $tableName): string {
        return $this->tableMap[$tableName] ?? $this->prefix . $tableName;
    }
    
    /**
     * Verificar existencia de tabla
     */
    public function tableExists(string $tableName): bool {
        $full = $this->getTableName($tableName);
        $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $full);
        return $this->wpdb->get_var($query) === $full;
    }
    
    /**
     * SELECT múltiples filas
     */
    public function select(string $query, array $params = [], string $output = OBJECT, bool $useCache = true): ?array {
        $sql = $this->prepareQuery($query, $params);
        $key = $this->getCacheKey($sql);
        
        if ($useCache && $this->cacheEnabled && ($cached = $this->getFromCache($key)) !== false) {
            $this->fireEvent('database.query.cache_hit', ['query' => $sql]);
            return $cached;
        }
        
        $this->fireEvent('database.query.before', ['query' => $sql]);
        $start = microtime(true);
        $results = $this->wpdb->get_results($sql, $output);
        $end = microtime(true);
        
        $this->handleErrors($sql);
        
        if ($useCache && $this->cacheEnabled && $results !== null) {
            $this->setCache($key, $results);
        }
        
        $this->fireEvent('database.query.after', [
            'query' => $sql,
            'results_count' => is_array($results) ? count($results) : 0,
            'execution_time' => $end - $start,
        ]);
        
        return $results;
    }
    
    /**
     * SELECT una fila
     */
    public function selectOne(string $query, array $params = [], string $output = OBJECT, bool $useCache = true) {
        $sql = $this->prepareQuery($query, $params);
        $key = $this->getCacheKey($sql);
        
        if ($useCache && $this->cacheEnabled && ($cached = $this->getFromCache($key)) !== false) {
            return $cached;
        }
        
        $this->fireEvent('database.query.before', ['query' => $sql]);
        $result = $this->wpdb->get_row($sql, $output);
        $this->handleErrors($sql);
        
        if ($useCache && $this->cacheEnabled && $result !== null) {
            $this->setCache($key, $result);
        }
        
        $this->fireEvent('database.query.after', ['query' => $sql, 'type' => 'select_one']);
        return $result;
    }
    
    /**
     * SELECT un valor
     */
    public function selectValue(string $query, array $params = [], bool $useCache = true) {
        $sql = $this->prepareQuery($query, $params);
        $key = $this->getCacheKey($sql);
        
        if ($useCache && $this->cacheEnabled && ($cached = $this->getFromCache($key)) !== false) {
            return $cached;
        }
        
        $this->fireEvent('database.query.before', ['query' => $sql]);
        $value = $this->wpdb->get_var($sql);
        $this->handleErrors($sql);
        
        if ($useCache && $this->cacheEnabled) {
            $this->setCache($key, $value);
        }
        
        $this->fireEvent('database.query.after', ['query' => $sql, 'type' => 'select_value']);
        return $value;
    }
    
    /**
     * INSERT
     */
    public function insert(string $tableName, array $data, array $format = []): int|false {
        $full = $this->getTableName($tableName);
        $data = $this->addTimestamps($data, 'insert');
        $this->fireEvent('database.insert.before', ['table' => $tableName, 'data' => $data]);
        
        $res = $this->wpdb->insert($full, $data, $format);
        if ($res === false) {
            $this->handleErrors("INSERT INTO {$full}");
            return false;
        }
        
        $id = $this->wpdb->insert_id;
        $this->invalidateTableCache($tableName);
        $this->fireEvent('database.insert.after', ['table' => $tableName, 'data' => $data, 'insert_id' => $id]);
        return $id;
    }
    
    /**
     * UPDATE
     */
    public function update(string $tableName, array $data, array $where, array $format = [], array $whereFormat = []): int|false {
        $full = $this->getTableName($tableName);
        $data = $this->addTimestamps($data, 'update');
        $this->fireEvent('database.update.before', ['table' => $tableName, 'data' => $data, 'where' => $where]);
        
        $res = $this->wpdb->update($full, $data, $where, $format, $whereFormat);
        if ($res === false) {
            $this->handleErrors("UPDATE {$full}");
            return false;
        }
        
        $this->invalidateTableCache($tableName);
        $this->fireEvent('database.update.after', [
            'table' => $tableName,
            'data' => $data,
            'where' => $where,
            'affected_rows' => $res
        ]);
        return $res;
    }
    
    /**
     * DELETE
     */
    public function delete(string $tableName, array $where, array $whereFormat = []): int|false {
        $full = $this->getTableName($tableName);
        $this->fireEvent('database.delete.before', ['table' => $tableName, 'where' => $where]);
        
        $res = $this->wpdb->delete($full, $where, $whereFormat);
        if ($res === false) {
            $this->handleErrors("DELETE FROM {$full}");
            return false;
        }
        
        $this->invalidateTableCache($tableName);
        $this->fireEvent('database.delete.after', [
            'table' => $tableName,
            'where' => $where,
            'affected_rows' => $res
        ]);
        return $res;
    }
    
    /**
     * QUERY genérica
     */
    public function query(string $query, array $params = []): int|false {
        $sql = $this->prepareQuery($query, $params);
        $this->fireEvent('database.query.before', ['query' => $sql]);
        $res = $this->wpdb->query($sql);
        $this->handleErrors($sql);
        $this->fireEvent('database.query.after', ['query' => $sql, 'affected_rows' => $res]);
        return $res;
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction(): bool {
        $this->transactionLevel++;
        if ($this->transactionLevel === 1) {
            $this->inTransaction = true;
            $ok = $this->wpdb->query('START TRANSACTION') !== false;
            $this->fireEvent('database.transaction.start');
            return $ok;
        }
        $sp = 'sp_level_' . $this->transactionLevel;
        return $this->wpdb->query("SAVEPOINT {$sp}") !== false;
    }
    
    /**
     * Commit
     */
    public function commit(): bool {
        if ($this->transactionLevel === 0) {
            throw new Exception('No active transaction to commit');
        }
        $this->transactionLevel--;
        if ($this->transactionLevel === 0) {
            $this->inTransaction = false;
            $ok = $this->wpdb->query('COMMIT') !== false;
            $this->fireEvent('database.transaction.commit');
            return $ok;
        }
        $sp = 'sp_level_' . ($this->transactionLevel + 1);
        return $this->wpdb->query("RELEASE SAVEPOINT {$sp}") !== false;
    }
    
    /**
     * Rollback
     */
    public function rollback(): bool {
        if ($this->transactionLevel === 0) {
            throw new Exception('No active transaction to rollback');
        }
        if ($this->transactionLevel === 1) {
            $this->inTransaction = false;
            $this->transactionLevel = 0;
            $ok = $this->wpdb->query('ROLLBACK') !== false;
            $this->fireEvent('database.transaction.rollback');
            return $ok;
        }
        $this->transactionLevel--;
        $sp = 'sp_level_' . ($this->transactionLevel + 1);
        return $this->wpdb->query("ROLLBACK TO SAVEPOINT {$sp}") !== false;
    }
    
    /**
     * Ejecutar callback en transacción
     */
    public function transaction(callable $callback) {
        $this->beginTransaction();
        try {
            $res = $callback($this);
            $this->commit();
            return $res;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * ¿En transacción?
     */
    public function inTransaction(): bool {
        return $this->inTransaction;
    }
    
    /**
     * Estadísticas
     */
    public function getStats(): array {
        return [
            'queries_executed'   => $this->wpdb->num_queries,
            'cache_hits'         => count($this->queryCache),
            'cache_enabled'      => $this->cacheEnabled,
            'transaction_level'  => $this->transactionLevel,
            'in_transaction'     => $this->inTransaction,
            'last_error'         => $this->wpdb->last_error,
            'last_query'         => $this->wpdb->last_query,
        ];
    }
    
    /**
     * Limpiar cache
     */
    public function clearCache(?string $pattern = null): void {
        if ($pattern === null) {
            $this->queryCache = [];
        } else {
            foreach ($this->queryCache as $k => $v) {
                if (strpos($k, $pattern) !== false) {
                    unset($this->queryCache[$k]);
                }
            }
        }
        $this->fireEvent('database.cache.cleared', ['pattern' => $pattern]);
    }
    
    /**
     * Preparar query
     */
    private function prepareQuery(string $query, array $params = []): string {
        return empty($params) ? $query : $this->wpdb->prepare($query, $params);
    }
    
    /**
     * Manejar errores
     */
    private function handleErrors(string $query): void {
        if (!empty($this->wpdb->last_error)) {
            $err = $this->wpdb->last_error;
            $this->fireEvent('database.error', ['error' => $err, 'query' => $query]);
            if ($this->config['debug_mode']) {
                error_log("Database Error: {$err} | Query: {$query}");
            }
            throw new Exception("Database Error: {$err}");
        }
    }
    
    /**
     * Agregar timestamps
     */
    private function addTimestamps(array $data, string $op): array {
        $now = current_time('mysql');
        if ($op === 'insert') {
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
        } else {
            $data['updated_at'] = $now;
        }
        return $data;
    }
    
    /**
     * Generar clave cache
     */
    private function getCacheKey(string $query): string {
        return 'cpp_db_' . md5($query);
    }
    
    /**
     * Obtener de cache
     */
    private function getFromCache(string $key) {
        if (!isset($this->queryCache[$key])) {
            return false;
        }
        $entry = $this->queryCache[$key];
        if (time() - $entry['timestamp'] > $this->cacheTtl) {
            unset($this->queryCache[$key]);
            return false;
        }
        return $entry['data'];
    }
    
    /**
     * Guardar en cache
     */
    private function setCache(string $key, $data): void {
        $this->queryCache[$key] = ['data' => $data, 'timestamp' => time()];
        if (count($this->queryCache) > 100) {
            $times = array_column($this->queryCache, 'timestamp');
            $oldest = array_search(min($times), $times);
            unset($this->queryCache[$oldest]);
        }
    }
    
    /**
     * Invalidar cache por tabla
     */
    private function invalidateTableCache(string $tableName): void {
        $this->clearCache($tableName);
    }
    
    /**
     * Inicializar mapeo de tablas
     */
    private function initializeTables(): void {
        $this->tableMap = [
            'tests'             => $this->prefix . 'tests',
            'test_results'      => $this->prefix . 'test_results',
            'test_invitations'  => $this->prefix . 'test_invitations',
            'user_subscriptions'=> $this->prefix . 'user_subscriptions',
            'test_sessions'     => $this->prefix . 'test_sessions',
            'email_templates'   => $this->prefix . 'email_templates',
            'whatsapp_sessions' => $this->prefix . 'whatsapp_sessions',
            'audit_logs'        => $this->prefix . 'audit_logs',
        ];
    }
    
    /**
     * Configurar manejo de errores y charset
     */
    private function setupErrorHandling(): void {
        if ($this->config['debug_mode']) {
            $this->wpdb->show_errors = true;
        }
        if (!empty($this->config['charset'])) {
            $this->wpdb->set_charset($this->wpdb->dbh, $this->config['charset']);
        }
    }
    
    /**
     * Disparar evento
     */
    private function fireEvent(string $event, array $data = []): void {
        $this->events?->fire($event, $data);
    }
}