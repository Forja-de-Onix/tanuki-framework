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

    $data['uri'] = $data['uri'] ?? current_uri();
    extract($data, EXTR_SKIP);

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    // Old input is meant to survive exactly one render (the form that
    // redisplays it after a failed validation) — clear it now.
    unset($_SESSION['old']);

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

/**
 * Saves the given data to the session so old() can retrieve it after
 * a redirect (typically used on validation failure, before redirecting
 * back to the form).
 */
function keep_old(array $data): void
{
    session_ensure();
    $_SESSION['old'] = $data;
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
 * Formats a date according to the app's locale (APP_LOCALE).
 *
 *   en → m/d/Y H:i  (e.g. 07/31/2026 14:05)
 *   es → d/m/Y H:i  (e.g. 31/07/2026 14:05)
 *
 * @param string $datetime  A date/time string parseable by strtotime()
 */
function format_date(string $datetime): string
{
    $locale = current_locale();
    $format = $locale === 'es' ? 'd/m/Y H:i' : 'm/d/Y H:i';
    return date($format, strtotime($datetime));
}

// ─── Translations ────────────────────────────────────────────────────────────

/**
 * Loads and caches the translation dictionary for the given locale.
 * Falls back to an empty array if the file doesn't exist or is invalid.
 */
function load_translations(string $locale): array
{
    static $cache = [];

    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $file = __DIR__ . "/lang/$locale.json";
    if (!file_exists($file)) {
        return $cache[$locale] = [];
    }

    $json = json_decode(file_get_contents($file), true);
    return $cache[$locale] = is_array($json) ? $json : [];
}

/**
 * Translates a dot-notation key using APP_LOCALE (e.g. t('nav.home')).
 *
 * Falls back to the English dictionary if the key is missing in the
 * current locale, and to the key itself if it's missing everywhere.
 *
 * @param string $key      Dot-notation key, e.g. 'errors.404_title'
 * @param array  $replace  Optional placeholders, e.g. ['name' => 'Sam'] replaces ":name"
 */
function t(string $key, array $replace = []): string
{
    $locale  = current_locale();
    $segments = explode('.', $key);

    $lookup = function (array $dict) use ($segments): ?string {
        $value = $dict;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return is_string($value) ? $value : null;
    };

    $translated = $lookup(load_translations($locale))
        ?? $lookup(load_translations('en'))
        ?? $key;

    foreach ($replace as $placeholder => $value) {
        $translated = str_replace(":$placeholder", (string) $value, $translated);
    }

    return $translated;
}

// ─── CSRF protection (opt-in) ──────────────────────────────────────────────

/**
 * Returns the current CSRF token, generating one and storing it in the
 * session on first call. The same token persists for the whole session
 * (not regenerated per request), so multiple open tabs/forms keep working.
 */
function csrf_token(): string
{
    session_ensure();
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Renders a hidden input with the current CSRF token, ready to drop
 * inside any <form>.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifies a submitted token against the session's CSRF token using a
 * timing-safe comparison. Returns false if missing or mismatched.
 */
function csrf_verify(?string $submittedToken): bool
{
    session_ensure();
    if (empty($_SESSION['_csrf_token']) || empty($submittedToken)) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $submittedToken);
}

// ─── Locale ────────────────────────────────────────────────────────────────

/**
 * Returns the active locale: session override if set (chosen via the
 * language switcher or a /xx/ URL prefix), otherwise APP_LOCALE from .env.
 */
function current_locale(): string
{
    session_ensure();
    return $_SESSION['locale'] ?? env('APP_LOCALE', 'en');
}

/**
 * Builds the URL for switching to a given language, prefixing the
 * current path with /{code}. Strips any existing locale prefix first,
 * so switching from /es/todo to English gives /en/todo, not /en/es/todo.
 */
function locale_switch_url(string $code): string
{
    $path = current_uri();

    if (preg_match('#^/([a-z]{2})(/.*)?$#', $path, $m) && file_exists(__DIR__ . "/lang/{$m[1]}.json")) {
        $path = $m[2] !== '' && $m[2] !== null ? $m[2] : '/';
    }

    return $path === '/' ? "/$code" : "/$code$path";
}

/**
 * Returns the list of enabled language codes from ACCEPTED_LANGUAGES (.env),
 * or an empty array if that variable isn't set — meaning the project is
 * single-language and the switcher should not be shown.
 */
function accepted_locales(): array
{
    $raw = env('ACCEPTED_LANGUAGES', null);

    if ($raw === null || trim($raw) === '') {
        return [];
    }

    return array_map('trim', explode(',', $raw));
}

/**
 * Returns true if the language switcher should be shown at all —
 * i.e. ACCEPTED_LANGUAGES is set and lists more than one language.
 */
function locale_switcher_enabled(): bool
{
    return count(accepted_locales()) > 1;
}

/**
 * Maps a locale code to its flag emoji and display label.
 * Add an entry here when you introduce a new language dictionary.
 */
function locale_meta(string $code): array
{
    $known = [
        'en' => ['flag' => '🇬🇧', 'label' => 'English'],
        'es' => ['flag' => '🇪🇸', 'label' => 'Español'],
    ];

    return $known[$code] ?? ['flag' => strtoupper($code), 'label' => $code];
}