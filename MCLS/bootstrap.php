<?php
/**
 * Bootstrap file for MCLS
 * Handles class loading and initialization
 */

// Prevent multiple inclusions
if (defined('MCLS_BOOTSTRAP_LOADED')) {
    return;
}
define('MCLS_BOOTSTRAP_LOADED', true);

// Load configuration
require_once __DIR__ . '/config/config.php';

// Auto-load classes to prevent redeclaration
function mcls_autoload($className) {
    $classFile = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($classFile) && !class_exists($className)) {
        require_once $classFile;
    }
}

// Register autoloader
spl_autoload_register('mcls_autoload');

// Load classes manually if autoloader doesn't work
$classes = ['ActiveDirectoryAuth', 'SessionManager', 'MaintenanceCall'];
foreach ($classes as $class) {
    if (!class_exists($class)) {
        $classFile = __DIR__ . '/classes/' . $class . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
        }
    }
}
?>