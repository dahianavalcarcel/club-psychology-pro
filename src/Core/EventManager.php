<?php
/**
 * Event Manager - Sistema de eventos interno
 * 
 * Maneja eventos internos del plugin, complementando el sistema
 * de hooks de WordPress con funcionalidad adicional.
 * 
 * @package ClubPsychologyPro\Core
 * @since 2.0.0
 */

namespace ClubPsychologyPro\Core;

/**
 * Gestor de eventos del plugin
 */
class EventManager {
    
    /**
     * Listeners registrados
     */
    private array $listeners = [];
    
    /**
     * Eventos disparados (para debug)
     */
    private array $fired = [];
    
    /**
     * Máximo número de listeners por evento
     */
    private int $maxListeners = 100;
    
    /**
     * Registrar un listener para un evento
     * 
     * @param string $event
     * @param callable $callback
     * @param int $priority
     * @return void
     */
    public function listen(string $event, callable $callback, int $priority = 10): void {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        if (!isset($this->listeners[$event][$priority])) {
            $this->listeners[$event][$priority] = [];
        }
        
        // Verificar límite de listeners
        $totalListeners = array_sum(array_map('count', $this->listeners[$event]));
        if ($totalListeners >= $this->maxListeners) {
            cpp_log("Advertencia: Demasiados listeners para evento '{$event}'", 'warning');
        }
        
        $this->listeners[$event][$priority][] = $callback;
        
        // Ordenar por prioridad
        ksort($this->listeners[$event]);
        
        cpp_log("Listener registrado para evento '{$event}' con prioridad {$priority}", 'debug');
    }
    
    /**
     * Alias para listen()
     * 
     * @param string $event
     * @param callable $callback
     * @param int $priority
     * @return void
     */
    public function on(string $event, callable $callback, int $priority = 10): void {
        $this->listen($event, $callback, $priority);
    }
    
    /**
     * Registrar un listener que se ejecuta solo una vez
     * 
     * @param string $event
     * @param callable $callback
     * @param int $priority
     * @return void
     */
    public function once(string $event, callable $callback, int $priority = 10): void {
        $onceCallback = function(...$args) use ($event, $callback) {
            $this->removeListener($event, $callback);
            return call_user_func_array($callback, $args);
        };
        
        $this->listen($event, $onceCallback, $priority);
    }
    
    /**
     * Disparar un evento
     * 
     * @param string $event
     * @param array $args
     * @return mixed|null
     */
    public function fire(string $event, ...$args) {
        // Registrar evento disparado para debug
        if (cpp_is_dev_mode()) {
            $this->fired[] = [
                'event' => $event,
                'time' => microtime(true),
                'args_count' => count($args),
            ];
        }
        
        if (!isset($this->listeners[$event])) {
            cpp_log("Evento '{$event}' disparado pero sin listeners", 'debug');
            return null;
        }
        
        $results = [];
        
        // Ejecutar listeners en orden de prioridad
        foreach ($this->listeners[$event] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $result = call_user_func_array($callback, $args);
                    
                    // Si el listener retorna false, detener propagación
                    if ($result === false) {
                        cpp_log("Propagación de evento '{$event}' detenida por listener", 'debug');
                        return false;
                    }
                    
                    $results[] = $result;
                    
                } catch (\Exception $e) {
                    cpp_log("Error en listener para evento '{$event}': " . $e->getMessage(), 'error');
                    
                    // En modo debug, re-lanzar la excepción
                    if (cpp_is_dev_mode()) {
                        throw $e;
                    }
                }
            }
        }
        
        cpp_log("Evento '{$event}' disparado con " . count($results) . " listeners ejecutados", 'debug');
        
        return count($results) === 1 ? $results[0] : $results;
    }
    
    /**
     * Alias para fire()
     * 
     * @param string $event
     * @param mixed ...$args
     * @return mixed|null
     */
    public function emit(string $event, ...$args) {
        return $this->fire($event, ...$args);
    }
    
    /**
     * Disparar evento y retornar el primer resultado no nulo
     * 
     * @param string $event
     * @param mixed ...$args
     * @return mixed|null
     */
    public function until(string $event, ...$args) {
        if (!isset($this->listeners[$event])) {
            return null;
        }
        
        foreach ($this->listeners[$event] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $result = call_user_func_array($callback, $args);
                    
                    if ($result !== null) {
                        return $result;
                    }
                    
                } catch (\Exception $e) {
                    cpp_log("Error en listener para evento '{$event}': " . $e->getMessage(), 'error');
                }
            }
        }
        
        return null;
    }
    
    /**
     * Eliminar un listener específico
     * 
     * @param string $event
     * @param callable $callback
     * @return bool
     */
    public function removeListener(string $event, callable $callback): bool {
        if (!isset($this->listeners[$event])) {
            return false;
        }
        
        $removed = false;
        
        foreach ($this->listeners[$event] as $priority => &$callbacks) {
            foreach ($callbacks as $index => $registeredCallback) {
                if ($registeredCallback === $callback) {
                    unset($callbacks[$index]);
                    $callbacks = array_values($callbacks); // Reindexar
                    $removed = true;
                }
            }
            
            // Eliminar prioridad si está vacía
            if (empty($callbacks)) {
                unset($this->listeners[$event][$priority]);
            }
        }
        
        // Eliminar evento si no tiene listeners
        if (empty($this->listeners[$event])) {
            unset($this->listeners[$event]);
        }
        
        if ($removed) {
            cpp_log("Listener eliminado para evento '{$event}'", 'debug');
        }
        
        return $removed;
    }
    
    /**
     * Eliminar todos los listeners de un evento
     * 
     * @param string $event
     * @return void
     */
    public function removeAllListeners(string $event): void {
        if (isset($this->listeners[$event])) {
            $count = array_sum(array_map('count', $this->listeners[$event]));
            unset($this->listeners[$event]);
            
            cpp_log("Eliminados {$count} listeners para evento '{$event}'", 'debug');
        }
    }
    
    /**
     * Verificar si un evento tiene listeners
     * 
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }
    
    /**
     * Obtener cantidad de listeners para un evento
     * 
     * @param string $event
     * @return int
     */
    public function getListenerCount(string $event): int {
        if (!isset($this->listeners[$event])) {
            return 0;
        }
        
        return array_sum(array_map('count', $this->listeners[$event]));
    }
    
    /**
     * Obtener todos los eventos registrados
     * 
     * @return array
     */
    public function getEvents(): array {
        return array_keys($this->listeners);
    }
    
    /**
     * Obtener listeners de un evento específico
     * 
     * @param string $event
     * @return array
     */
    public function getListeners(string $event): array {
        return $this->listeners[$event] ?? [];
    }
    
    /**
     * Crear un filtro (evento que modifica datos)
     * 
     * @param string $filter
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public function filter(string $filter, $value, ...$args) {
        if (!isset($this->listeners[$filter])) {
            return $value;
        }
        
        $filtered = $value;
        
        foreach ($this->listeners[$filter] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $filtered = call_user_func($callback, $filtered, ...$args);
                } catch (\Exception $e) {
                    cpp_log("Error en filtro '{$filter}': " . $e->getMessage(), 'error');
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * Registrar múltiples listeners desde un array
     * 
     * @param array $listeners
     * @return void
     */
    public function subscribe(array $listeners): void {
        foreach ($listeners as $event => $callbacks) {
            if (!is_array($callbacks)) {
                $callbacks = [$callbacks];
            }
            
            foreach ($callbacks as $callback) {
                $priority = 10;
                
                // Permitir especificar prioridad como ['callback' => $callback, 'priority' => $priority]
                if (is_array($callback) && isset($callback['callback'])) {
                    $priority = $callback['priority'] ?? 10;
                    $callback = $callback['callback'];
                }
                
                $this->listen($event, $callback, $priority);
            }
        }
    }
    
    /**
     * Crear un namespace de eventos
     * 
     * @param string $namespace
     * @return EventNamespace
     */
    public function namespace(string $namespace): EventNamespace {
        return new EventNamespace($this, $namespace);
    }
    
    /**
     * Obtener estadísticas de eventos
     * 
     * @return array
     */
    public function getStats(): array {
        $stats = [
            'total_events' => count($this->listeners),
            'total_listeners' => 0,
            'events_fired' => count($this->fired),
            'listeners_by_event' => [],
        ];
        
        foreach ($this->listeners as $event => $priorities) {
            $count = array_sum(array_map('count', $priorities));
            $stats['total_listeners'] += $count;
            $stats['listeners_by_event'][$event] = $count;
        }
        
        return $stats;
    }
    
    /**
     * Limpiar historial de eventos disparados
     * 
     * @return void
     */
    public function clearHistory(): void {
        $this->fired = [];
    }
    
    /**
     * Obtener historial de eventos disparados (solo en modo debug)
     * 
     * @return array
     */
    public function getHistory(): array {
        return cpp_is_dev_mode() ? $this->fired : [];
    }
    
    /**
     * Configurar límite máximo de listeners por evento
     * 
     * @param int $max
     * @return void
     */
    public function setMaxListeners(int $max): void {
        $this->maxListeners = $max;
    }
}

/**
 * Namespace de eventos para organizar mejor
 */
class EventNamespace {
    
    private EventManager $eventManager;
    private string $namespace;
    
    public function __construct(EventManager $eventManager, string $namespace) {
        $this->eventManager = $eventManager;
        $this->namespace = rtrim($namespace, '.') . '.';
    }
    
    /**
     * Escuchar evento en el namespace
     * 
     * @param string $event
     * @param callable $callback
     * @param int $priority
     * @return void
     */
    public function listen(string $event, callable $callback, int $priority = 10): void {
        $this->eventManager->listen($this->namespace . $event, $callback, $priority);
    }
    
    /**
     * Disparar evento en el namespace
     * 
     * @param string $event
     * @param mixed ...$args
     * @return mixed|null
     */
    public function fire(string $event, ...$args) {
        return $this->eventManager->fire($this->namespace . $event, ...$args);
    }
    
    /**
     * Filtrar en el namespace
     * 
     * @param string $filter
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public function filter(string $filter, $value, ...$args) {
        return $this->eventManager->filter($this->namespace . $filter, $value, ...$args);
    }
    
    /**
     * Verificar listeners en el namespace
     * 
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool {
        return $this->eventManager->hasListeners($this->namespace . $event);
    }
}