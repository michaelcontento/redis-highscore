<?php

/**
 * Copyright 2011 Michael Contento <michaelcontento@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @author  Michael Contento <michaelcontento@gmail.com>
 * @link    https://github.com/michaelcontento/redis-highscore
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache
 */

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
