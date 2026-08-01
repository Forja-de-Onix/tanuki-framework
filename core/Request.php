<?php

/**
 * Tanuki Framework — HTTP Request wrapper
 *
 * Abstracts access to $_GET, $_POST, $_FILES and $_SERVER
 * with basic input sanitization.
 */
class Request
{
    // ─── Method and URI ───────────────────────────────────────────────────────

    /** Returns the real HTTP method (GET, POST, PUT, DELETE…) */
    public function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }
        return $method;
    }

    /** Returns the clean URI (no query string) */
    public function uri(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return $path !== null && $path !== false ? $path : '/';
    }

    /** Returns the full query string */
    public function queryString(): string
    {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    // ─── Input access ─────────────────────────────────────────────────────────

    /** Gets a value from $_GET */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($_GET[$key] ?? $default);
    }

    /** Gets a value from $_POST */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($_POST[$key] ?? $default);
    }

    /**
     * Gets a value from GET or POST (POST takes priority).
     * Equivalent to request()->input('field')
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post($key) ?? $this->get($key, $default);
    }

    /** Returns all inputs (GET + POST merged, POST wins) */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /** Returns only the inputs listed in $keys */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /** Returns all inputs except the ones listed in $keys */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /** Checks whether an input with that name exists */
    public function has(string $key): bool
    {
        $all = $this->all();
        return isset($all[$key]) && $all[$key] !== '';
    }

    // ─── Files ────────────────────────────────────────────────────────────────

    /** Accesses $_FILES by key */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    // ─── Request information ─────────────────────────────────────────────────

    public function isGet(): bool    { return $this->method() === 'GET'; }
    public function isPost(): bool   { return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
    public function isPut(): bool    { return $this->method() === 'PUT'; }
    public function isDelete(): bool { return $this->method() === 'DELETE'; }
    public function isAjax(): bool   { return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'; }

    /** Client IP */
    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    // ─── Sanitization ─────────────────────────────────────────────────────────

    /**
     * Trims leading/trailing whitespace from strings.
     * Arrays are processed recursively.
     */
    private function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return $value;
    }
}