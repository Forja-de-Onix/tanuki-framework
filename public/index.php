<?php

/**
 * Tanuki Framework — Entry Point
 *
 * All the work is delegated to App::run().
 * This file only loads the bootstrap and starts the application.
 */

// When running through `php -S`, let the built-in server serve existing
// static files directly (assets, images, etc.) instead of routing them
// through the application.
if (php_sapi_name() === 'cli-server') {
    $requestedFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($requestedFile !== __DIR__ . '/index.php' && is_file($requestedFile)) {
        return false;
    }
}

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/../core/App.php';

App::run();
