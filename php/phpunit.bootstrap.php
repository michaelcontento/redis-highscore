<?php

/**
 * Configure PHP
 */
if (!defined('E_DEPRECATED')) {
    define('E_DEPRECATED', 8192);
}
ini_set('error_reporting', E_ALL | E_STRICT | E_DEPRECATED);
ini_set('display_errors', true);

/**
 * Register the autoloader
 */
spl_autoload_register(
    function($class){
        require str_replace(array('_', '\\'), '/', $class) . '.php';
    }
);

/**
 * Check for the all-in-one Predis.php file
 */
$includePath = get_include_path();
$includePathParts = explode(PATH_SEPARATOR, $includePath);

foreach ($includePathParts as $path) {
    $masterFile = $path . '/Predis.php';
    if (file_exists($masterFile)) {
        require $masterFile;
        break;
    }
}

unset($includePath);
unset($includePathParts);
unset($masterFile);
