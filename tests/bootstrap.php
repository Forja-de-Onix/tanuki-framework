<?php

/**
 * Tanuki Framework — PHPUnit bootstrap
 *
 * Loads Composer's autoloader (for PHPUnit itself) plus every core
 * class the test suite needs, since Tanuki's own autoloader only
 * activates once App::run() runs — tests load classes directly instead.
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Controller.php';

// Use a dedicated .env.testing if present, otherwise fall back to .env
$envFile = file_exists(__DIR__ . '/../.env.testing')
    ? __DIR__ . '/../.env.testing'
    : __DIR__ . '/../.env';

if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \t\"'");
    }
}