<?php

/**
 * Tanuki Framework — App Bootstrap
 *
 * Central entry point. Orchestrates:
 *  - Loading environment variables (.env)
 *  - Autoloading classes (core, controllers, models)
 *  - Global error and exception handling
 *  - Router dispatch with parameter support and method override
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/redis.php';
require_once __DIR__ . '/../config/mongo.php';

// ── tanuki_login (uncomment every line below to enable) ──────────────────
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../login/mail.php';
require_once __DIR__ . '/../login/UserModel.php';
require_once __DIR__ . '/../login/PasswordResetModel.php';
require_once __DIR__ . '/../login/AuthController.php';
require_once __DIR__ . '/../login/ProfileController.php';


class App
{
    private static array $routes = [];

    public static function run(): void
    {
        session_ensure();
        self::loadEnv();
        self::registerAutoloader();
        self::configureErrors();
        self::$routes = require __DIR__ . '/../routes.php';
        self::dispatch();
    }

    // ─── Environment ──────────────────────────────────────────────────────────

    /**
     * Loads the .env file and populates $_ENV.
     * Supported format: KEY=value  (# for comments, quotes optional)
     *
     * Environment variables already present (injected by the OS or a
     * container orchestrator like Docker Compose) take priority over
     * the .env file — this line only fills in what isn't already set.
     */
    private static function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and lines without '='
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\"'"); // Strip optional quotes

            if (array_key_exists($key, $_ENV)) {
                continue; // Already set in $_ENV — don't override
            }

            $external = getenv($key);
            $_ENV[$key] = $external !== false ? $external : $value;
        }
    }

    // ─── Autoloader ───────────────────────────────────────────────────────────

    /**
     * Registers an autoloader that looks for classes in:
     *   core/, controllers/, models/
     */
    private static function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            $base = __DIR__ . '/..';
            $paths = [
                "$base/core/$class.php",
                "$base/controllers/$class.php",
                "$base/models/$class.php",
            ];
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    return;
                }
            }
        });
    }

    // ─── Error handling ───────────────────────────────────────────────────────

    private static function configureErrors(): void
    {
        $debug = env('APP_DEBUG', 'false') === 'true';

        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting($debug ? E_ALL : 0);

        set_exception_handler(function (\Throwable $e) use ($debug): void {
            error_log('[Tanuki] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            if ($debug) {
                echo '<h1>Error 500 — Uncaught exception</h1>';
                echo '<pre style="background:#1a1a2e;color:#e94560;padding:1rem;border-radius:8px">';
                echo htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8');
                echo '</pre>';
            } else {
                view('errors/500');
            }
        });
    }

    // ─── Router ───────────────────────────────────────────────────────────────

    private static function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $uri = current_uri();

        // ── Language prefix detection (optional — inert unless a /xx/ path
        // is actually visited; see lang/ folder) ─────────────────────────────
        if (preg_match('#^/([a-z]{2})(/.*)?$#', $uri, $langMatches)) {
            $code     = $langMatches[1];
            $langFile = __DIR__ . "/../lang/$code.json";

            // Only treat it as a locale prefix if a dictionary for that
            // code actually exists — otherwise a route like "/ok" would
            // wrongly be swallowed by this check.
            if (file_exists($langFile)) {
                $accepted = accepted_locales();

                if (empty($accepted) || !in_array($code, $accepted, true)) {
                    // No hay selector configurado, o este idioma no está habilitado — 404
                    http_response_code(404);
                    view('errors/404');
                    return;
                }

                session_ensure();
                $_SESSION['locale'] = $code;
                $uri = ($langMatches[2] ?? '') !== '' ? $langMatches[2] : '/';
            }
        }

        $routeKey = "$method $uri";

        // 1. Exact match (fastest)
        if (isset(self::$routes[$routeKey])) {
            self::callRoute(self::$routes[$routeKey], []);
            return;
        }

        // 2. Match with parameters  e.g. /todo/{id}
        foreach (self::$routes as $pattern => $handler) {
            preg_match_all('/\{([^}]+)\}/', $pattern, $paramMatches);
            $paramNames = $paramMatches[1];

            $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $routeKey, $matches)) {
                array_shift($matches);
                $params = !empty($paramNames)
                    ? array_combine($paramNames, array_slice($matches, 0, count($paramNames)))
                    : $matches;
                self::callRoute($handler, array_values($params));
                return;
            }
        }

        // 3. No route → 404
        http_response_code(404);
        view('errors/404');
    }

    /**
     * Instantiates the controller and calls the method with the route parameters.
     *
     * @param string $handler  Format "ControllerClass@method"
     * @param array  $params   Route parameters captured (positional)
     */
    private static function callRoute(string $handler, array $params): void
    {
        [$controllerClass, $method] = explode('@', $handler, 2);

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller '$controllerClass' not found.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method '$method' does not exist on '$controllerClass'.");
        }

        $controller->$method(...$params);
    }
}