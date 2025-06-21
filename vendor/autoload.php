<?php
/**
 * Autoloader PSR-4 simple para ClubPsychologyPro
 */

spl_autoload_register(function (string $class) {
    // Namespace raíz de tu plugin
    $prefix = 'ClubPsychologyPro\\';
    // Ruta absoluta al directorio src/s
    $base_dir = __DIR__ . '/../src/';

    // ¿Coincide la clase con nuestro namespace?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no es de nuestro espacio de nombres
        return;
    }

    // Obtiene la parte relativa de la clase
    $relative_class = substr($class, $len);

    // Construye la ruta al fichero
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Incluye si existe
    if (file_exists($file)) {
        require $file;
    }
});
