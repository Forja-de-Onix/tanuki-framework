<?php

/**
 * Tanuki Framework — Global helpers
 *
 * Functions available anywhere in the framework:
 *   env($key, $default)   — reads an environment variable
 *   e($value)             — escapes HTML (anti-XSS)
 *   view($name, $data)    — renders a view inside the layout
 *   url($path)            — generates an absolute URL
 *   redirect($path)       — redirects and stops execution
 *   flash($key)           — reads (and clears) a session flash message
 *   old($key, $default)   — retrieves a previous input value (after validation)
 */

// ─── Environment ──────────────────────────────────────────────────────────────

/**
 * Gets an environment variable loaded from .env.
 * Checks $_ENV first, then getenv() (system variables).
 */
function env(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// ─── Security ─────────────────────────────────────────────────────────────────

/**
 * Escapes special HTML characters to prevent XSS.
 * Use it whenever you print user or database data.
 *
 * Example in views:  <?= e($user['name']) ?>
 */
function e(mixed $value): string
{
    if ($value === null) return '';
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ─── URI ──────────────────────────────────────────────────────────────────────

/**
 * Returns the current URI, normalized (no query string, no trailing
 * slash except root). Single source of truth used both by the router
 * (App::dispatch) and by views (via view()).
 */
function current_uri(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($uri === null || $uri === false) {
        return '/';
    }
    if ($uri !== '/' && str_ends_with($uri, '/')) {
        $uri = rtrim($uri, '/');
    }
    return $uri;
}

// ─── Views ────────────────────────────────────────────────────────────────────

/**
 * Renders a view inside the layout (head + view content + footer).
 *
 * Flow:
 *   1. Runs extract($data) → variables available in the view AND in head.php
 *   2. Captures the view's output in a buffer
 *   3. Includes head.php (which already has access to $title thanks to extract)
 *   4. Prints the captured content
 *   5. Includes footer.php
 *
 * @param string $name  Path relative to /views/ without extension (e.g. 'todo/index')
 * @param array  $data  Variables available in the view and in the layout
 *
 * @throws RuntimeException if the view file doesn't exist
 */
function view(string $name, array $data = []): void
{
    $viewFile = __DIR__ . "/views/$name.php";

    if (!file_exists($viewFile)) {
        throw new RuntimeException("View '$name' does not exist at $viewFile");
    }

    // Current normalized URI — available in every view
    $data['uri'] = $data['uri'] ?? current_uri();

    // Extract $data into the current scope (makes $title, etc. available)
    extract($data, EXTR_SKIP);

    // Capture the view's content in a buffer
    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    // Render layout: head receives $title (from extract) + footer closes </main>
    require __DIR__ . '/includes/head.php';
    echo $content;
    require __DIR__ . '/includes/footer.php';
}

// ─── URLs and redirects ───────────────────────────────────────────────────────

/**
 * Generates an absolute URL from a path.
 *
 * Example: url('/about') → 'http://localhost/about'
 */
function url(string $path = ''): string
{
    $base = rtrim(env('APP_URL', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Redirects to the given path and stops execution.
 */
function redirect(string $path): void
{
    header("Location: " . url($path));
    exit;
}

// ─── Session and flash ─────────────────────────────────────────────────────────

/**
 * Starts the session if it hasn't been started yet.
 */
function session_ensure(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Reads and clears a flash message from the session.
 * Returns null if it doesn't exist.
 */
function flash(string $key): ?string
{
    session_ensure();
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Retrieves a value from the previous input (saved to session after an error).
 * Useful for re-populating forms after failed validation.
 */
function old(string $key, mixed $default = ''): mixed
{
    session_ensure();
    return $_SESSION['old'][$key] ?? $default;
}

// ─── Locale ────────────────────────────────────────────────────────────────────

/**
 * Returns the active application locale ('en' or 'es'), read from APP_LOCALE.
 * Defaults to 'en' if not set or set to something else.
 */
function locale(): string
{
    $value = strtolower(env('APP_LOCALE', 'en'));
    return $value === 'es' ? 'es' : 'en';
}

/**
 * Formats a date/datetime string according to the active locale.
 * en → m/d/Y H:i   (US format)
 * es → d/m/Y H:i   (day/month first, Spanish convention)
 *
 * Pass $format explicitly to override the locale default for a specific case.
 *
 * @param string      $datetime  Any string accepted by strtotime() (e.g. a DB TIMESTAMP)
 * @param string|null $format    Optional explicit date() format, bypasses locale default
 */
function format_date(string $datetime, ?string $format = null): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return '';
    }

    $format ??= locale() === 'es' ? 'd/m/Y H:i' : 'm/d/Y H:i';

    return date($format, $timestamp);
}