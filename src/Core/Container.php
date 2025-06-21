<?php
/**
 * Contenedor de dependencias para Club Psychology Pro
 *
 * @package ClubPsychologyPro\Core
 * @since 1.0.0
 */

namespace ClubPsychologyPro\Core;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Contenedor de inyección de dependencias simple pero potente
 * 
 * Soporta:
 * - Registro de servicios como singletons o factories
 * - Resolución automática de dependencias
 * - Lazy loading de servicios
 * - Aliases para servicios
 * - Verificación de servicios registrados
 */
class Container {
    
    /**
     * Servicios registrados como singletons
     * @var array<string, Closure>
     */
    private array $singletons = [];
    
    /**
     * Instancias resueltas de singletons
     * @var array<string, object>
     */
    private array $instances = [];
    
    /**
     * Servicios registrados como factories
     * @var array<string, Closure>
     */
    private array $factories = [];
    
    /**
     * Aliases para servicios
     * @var array<string, string>
     */
    private array $aliases = [];
    
    /**
     * Cache de reflexión para clases
     * @var array<string, ReflectionClass>
     */
    private array $reflectionCache = [];
    
    /**
     * Servicios que están siendo resueltos (para detectar dependencias circulares)
     * @var array<string, bool>
     */
    private array $resolving = [];
    
    /**
     * Registrar un servicio como singleton
     *
     * @param string $id Identificador del servicio
     * @param Closure|string $resolver Función de resolución o nombre de clase
     * @return self
     */
    public function singleton(string $id, $resolver): self {
        $this->singletons[$id] = $this->normalizeResolver($resolver);
        
        // Limpiar instancia existente si la hay
        unset($this->instances[$id]);
        
        return $this;
    }
    
    /**
     * Registrar un servicio como factory (nueva instancia cada vez)
     *
     * @param string $id Identificador del servicio
     * @param Closure|string $resolver Función de resolución o nombre de clase
     * @return self
     */
    public function factory(string $id, $resolver): self {
        $this->factories[$id] = $this->normalizeResolver($resolver);
        return $this;
    }
    
    /**
     * Registrar un alias para un servicio
     *
     * @param string $alias Alias del servicio
     * @param string $service Identificador del servicio real
     * @return self
     */
    public function alias(string $alias, string $service): self {
        $this->aliases[$alias] = $service;
        return $this;
    }
    
    /**
     * Obtener un servicio del contenedor
     *
     * @param string $id Identificador del servicio
     * @return mixed
     * @throws InvalidArgumentException Si el servicio no está registrado
     */
    public function get(string $id) {
        // Resolver alias
        $id = $this->resolveAlias($id);
        
        // Detectar dependencias circulares
        if (isset($this->resolving[$id])) {
            throw new InvalidArgumentException(
                sprintf('Dependencia circular detectada para el servicio: %s', $id)
            );
        }
        
        try {
            $this->resolving[$id] = true;
            
            // Si es singleton y ya está instanciado, devolver la instancia
            if (isset($this->singletons[$id]) && isset($this->instances[$id])) {
                return $this->instances[$id];
            }
            
            // Si es singleton pero no está instanciado
            if (isset($this->singletons[$id])) {
                $instance = $this->singletons[$id]($this);
                $this->instances[$id] = $instance;
                return $instance;
            }
            
            // Si es factory
            if (isset($this->factories[$id])) {
                return $this->factories[$id]($this);
            }
            
            // Intentar auto-resolución por nombre de clase
            if (class_exists($id)) {
                return $this->autowire($id);
            }
            
            throw new InvalidArgumentException(
                sprintf('Servicio no encontrado: %s', $id)
            );
            
        } finally {
            unset($this->resolving[$id]);
        }
    }
    
    /**
     * Verificar si un servicio está registrado
     *
     * @param string $id Identificador del servicio
     * @return bool
     */
    public function has(string $id): bool {
        $id = $this->resolveAlias($id);
        
        return isset($this->singletons[$id]) || 
               isset($this->factories[$id]) || 
               class_exists($id);
    }
    
    /**
     * Registrar una instancia existente como singleton
     *
     * @param string $id Identificador del servicio
     * @param object $instance Instancia del objeto
     * @return self
     */
    public function instance(string $id, object $instance): self {
        $this->instances[$id] = $instance;
        
        // Registrar como singleton que devuelve la instancia
        $this->singletons[$id] = function() use ($instance) {
            return $instance;
        };
        
        return $this;
    }
    
    /**
     * Obtener todos los servicios registrados
     *
     * @return array<string, string> Array con IDs y tipos de servicios
     */
    public function getRegisteredServices(): array {
        $services = [];
        
        foreach (array_keys($this->singletons) as $id) {
            $services[$id] = 'singleton';
        }
        
        foreach (array_keys($this->factories) as $id) {
            $services[$id] = 'factory';
        }
        
        foreach ($this->aliases as $alias => $service) {
            $services[$alias] = 'alias -> ' . $service;
        }
        
        return $services;
    }
    
    /**
     * Limpiar todas las instancias de singletons
     *
     * @return self
     */
    public function flush(): self {
        $this->instances = [];
        return $this;
    }
    
    /**
     * Remover un servicio del contenedor
     *
     * @param string $id Identificador del servicio
     * @return self
     */
    public function remove(string $id): self {
        unset(
            $this->singletons[$id],
            $this->factories[$id],
            $this->instances[$id],
            $this->aliases[$id]
        );
        
        return $this;
    }
    
    /**
     * Auto-resolver una clase usando reflexión
     *
     * @param string $className Nombre de la clase
     * @return object
     * @throws InvalidArgumentException Si no se puede resolver
     */
    private function autowire(string $className): object {
        try {
            $reflection = $this->getReflectionClass($className);
            
            // Si no tiene constructor, crear instancia directamente
            $constructor = $reflection->getConstructor();
            if (!$constructor) {
                return new $className();
            }
            
            // Resolver parámetros del constructor
            $parameters = $constructor->getParameters();
            $dependencies = [];
            
            foreach ($parameters as $parameter) {
                $dependencies[] = $this->resolveParameter($parameter);
            }
            
            return $reflection->newInstanceArgs($dependencies);
            
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException(
                sprintf('No se puede auto-resolver la clase: %s. Error: %s', $className, $e->getMessage())
            );
        }
    }
    
    /**
     * Resolver un parámetro del constructor
     *
     * @param ReflectionParameter $parameter Parámetro a resolver
     * @return mixed
     * @throws InvalidArgumentException Si no se puede resolver el parámetro
     */
    private function resolveParameter(ReflectionParameter $parameter) {
        $type = $parameter->getType();
        
        // Si no tiene tipo definido
        if (!$type) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw new InvalidArgumentException(
                sprintf('No se puede resolver el parámetro sin tipo: %s', $parameter->getName())
            );
        }
        
        // Si es un tipo built-in (string, int, etc.)
        if ($type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw new InvalidArgumentException(
                sprintf('No se puede resolver el parámetro built-in: %s', $parameter->getName())
            );
        }
        
        // Si es una clase
        $className = $type->getName();
        
        try {
            return $this->get($className);
        } catch (InvalidArgumentException $e) {
            // Si el parámetro es opcional, usar valor por defecto
            if ($parameter->isOptional()) {
                return $parameter->isDefaultValueAvailable() 
                    ? $parameter->getDefaultValue() 
                    : null;
            }
            
            throw new InvalidArgumentException(
                sprintf('No se puede resolver la dependencia: %s para el parámetro: %s', 
                    $className, $parameter->getName())
            );
        }
    }
    
    /**
     * Normalizar el resolver a una función
     *
     * @param Closure|string $resolver Resolver a normalizar
     * @return Closure
     */
    private function normalizeResolver($resolver): Closure {
        if ($resolver instanceof Closure) {
            return $resolver;
        }
        
        if (is_string($resolver) && class_exists($resolver)) {
            return function(Container $container) use ($resolver) {
                return $container->autowire($resolver);
            };
        }
        
        throw new InvalidArgumentException(
            'El resolver debe ser una función o un nombre de clase válido'
        );
    }
    
    /**
     * Resolver alias de un servicio
     *
     * @param string $id Identificador que puede ser un alias
     * @return string Identificador real del servicio
     */
    private function resolveAlias(string $id): string {
        // Resolver cadenas de aliases
        $original = $id;
        $depth = 0;
        
        while (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
            $depth++;
            
            // Prevenir loops infinitos en aliases
            if ($depth > 10) {
                throw new InvalidArgumentException(
                    sprintf('Loop de aliases detectado comenzando en: %s', $original)
                );
            }
        }
        
        return $id;
    }
    
    /**
     * Obtener ReflectionClass con cache
     *
     * @param string $className Nombre de la clase
     * @return ReflectionClass
     * @throws ReflectionException
     */
    private function getReflectionClass(string $className): ReflectionClass {
        if (!isset($this->reflectionCache[$className])) {
            $this->reflectionCache[$className] = new ReflectionClass($className);
        }
        
        return $this->reflectionCache[$className];
    }
    
    /**
     * Método mágico para acceso como propiedad
     *
     * @param string $id Identificador del servicio
     * @return mixed
     */
    public function __get(string $id) {
        return $this->get($id);
    }
    
    /**
     * Método mágico para verificar existencia como propiedad
     *
     * @param string $id Identificador del servicio
     * @return bool
     */
    public function __isset(string $id): bool {
        return $this->has($id);
    }
    
    /**
     * Método mágico para llamadas de método (sintaxis alternativa)
     *
     * @param string $method Nombre del método
     * @param array $args Argumentos
     * @return mixed
     */
    public function __call(string $method, array $args) {
        // Permitir sintaxis como $container->userManager() en lugar de $container->get('user_manager')
        $serviceId = $this->camelToSnake($method);
        
        if ($this->has($serviceId)) {
            return $this->get($serviceId);
        }
        
        throw new InvalidArgumentException(
            sprintf('Método no encontrado: %s. ¿Quisiste decir get("%s")?', $method, $serviceId)
        );
    }
    
    /**
     * Convertir camelCase a snake_case
     *
     * @param string $input String en camelCase
     * @return string String en snake_case
     */
    private function camelToSnake(string $input): string {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }
    
    /**
     * Crear un contenedor hijo que hereda los servicios del padre
     *
     * @return self
     */
    public function child(): self {
        $child = new self();
        
        // Copiar servicios registrados (no instancias)
        $child->singletons = $this->singletons;
        $child->factories = $this->factories;
        $child->aliases = $this->aliases;
        
        return $child;
    }
    
    /**
     * Registrar múltiples servicios a la vez
     *
     * @param array $services Array de servicios [id => resolver]
     * @param string $type Tipo de registro ('singleton' o 'factory')
     * @return self
     */
    public function registerMany(array $services, string $type = 'singleton'): self {
        foreach ($services as $id => $resolver) {
            if ($type === 'factory') {
                $this->factory($id, $resolver);
            } else {
                $this->singleton($id, $resolver);
            }
        }
        
        return $this;
    }
    
    /**
     * Obtener estadísticas del contenedor
     *
     * @return array<string, mixed>
     */
    public function getStats(): array {
        return [
            'singletons_registered' => count($this->singletons),
            'singletons_instantiated' => count($this->instances),
            'factories_registered' => count($this->factories),
            'aliases_registered' => count($this->aliases),
            'reflection_cache_size' => count($this->reflectionCache),
            'memory_usage' => [
                'total' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
            ]
        ];
    }
}