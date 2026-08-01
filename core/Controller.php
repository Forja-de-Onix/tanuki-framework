<?php

/**
 * Tanuki Framework — Base controller class
 *
 * Provides common helpers: redirection, access to Request,
 * view rendering, and HTTP responses.
 */
class Controller
{
    /** Current request object */
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    // ─── Rendering ────────────────────────────────────────────────────────────

    /**
     * Renders a view inside the main layout.
     *
     * @param string $name  Path relative to /views/ without extension (e.g. 'todo/index')
     * @param array  $data  Variables available in the view
     */
    protected function view(string $name, array $data = []): void
    {
        view($name, $data);
    }

    // ─── HTTP responses ───────────────────────────────────────────────────────

    /**
     * Redirects to the given path and stops execution.
     */
    protected function redirect(string $path): void
    {
        header("Location: " . url($path));
        exit;
    }

    /**
     * Returns JSON and stops execution. Useful for simple API endpoints.
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Responds with a 404 and stops execution.
     */
    protected function abort404(): void
    {
        http_response_code(404);
        view('errors/404');
        exit;
    }

    // ─── Flash messages ───────────────────────────────────────────────────────

    /**
     * Stores a message in the session to show it on the next request.
     */
    protected function flash(string $key, string $message): void
    {
        session_ensure();
        $_SESSION['flash'][$key] = $message;
    }
}