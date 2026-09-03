<?php

/**
 * AdminController — Generic CRUD driven by admin/admin.php
 *
 * Part of the tanuki_admin extension. Fully self-contained inside
 * the admin/ folder — delete this folder, remove its require and
 * routes from routes.php, and the rest of the app is unaffected.
 *
 * Requires tanuki_login (auth_require(), auth_user()) and an
 * `is_admin` column on the users table.
 */
class AdminController extends Controller
{
    private array $registry;

    public function __construct()
    {
        parent::__construct();
        $this->registry = require __DIR__ . '/admin.php';
        $this->guard();
    }

    /** Restricts the whole panel to authenticated admins. */
    private function guard(): void
    {
        auth_require();
        $user = auth_user();
        if (empty($user['is_admin'])) {
            http_response_code(403);
            exit('Forbidden: admin access only.');
        }
    }

    /** Finds a resource definition by its slug, or 404s. */
    private function resource(string $slug): array
    {
        if (!isset($this->registry[$slug])) {
            $this->abort404();
        }
        return $this->registry[$slug];
    }

    // ── GET /admin ───────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $this->render('dashboard', [
            'title'     => 'Admin',
            'resources' => $this->registry,
        ]);
    }

    // ── GET /admin/{resource} ────────────────────────────────────────────────

    public function index(string $slug): void
    {
        $res      = $this->resource($slug);
        $model    = $res['model'];
        $orderBy  = $res['order_by']  ?? 'id';
        $orderDir = $res['order_dir'] ?? 'ASC';

        $this->render('index', [
            'title'    => $res['label'] . ' — Admin',
            'slug'     => $slug,
            'resource' => $res,
            'records'  => $model::all($orderBy, $orderDir),
        ]);
    }

    // ── GET /admin/{resource}/create ─────────────────────────────────────────

    public function create(string $slug): void
    {
        $res = $this->resource($slug);

        $this->render('form', [
            'title'    => 'New ' . $res['label'] . ' — Admin',
            'slug'     => $slug,
            'resource' => $res,
            'record'   => null,
        ]);
    }

    // ── POST /admin/{resource} ───────────────────────────────────────────────

    public function store(string $slug): void
    {
        $res = $this->resource($slug);

        if (!csrf_verify($this->request->post('_token'))) {
            $this->redirect("/admin/$slug/create");
        }

        // A password field can't be left blank when creating a brand new
        // record — "keep current password" only makes sense on edit.
        foreach ($res['form_fields'] as $field => $meta) {
            if ($meta['type'] === 'password' && trim((string) $this->request->post($field, '')) === '') {
                $this->flash('error', 'Password is required when creating a new record.');
                $this->redirect("/admin/$slug/create");
            }
        }

        $res['model']::create($this->collectFormData($res));
        $this->redirect("/admin/$slug");
    }

    // ── GET /admin/{resource}/{id}/edit ──────────────────────────────────────

    public function edit(string $slug, string $id): void
    {
        $res    = $this->resource($slug);
        $record = $res['model']::find((int) $id);

        if (!$record) {
            $this->abort404();
        }

        $this->render('form', [
            'title'    => 'Edit ' . $res['label'] . ' — Admin',
            'slug'     => $slug,
            'resource' => $res,
            'record'   => $record,
        ]);
    }

    // ── PUT /admin/{resource}/{id} ────────────────────────────────────────────

    public function update(string $slug, string $id): void
    {
        $res = $this->resource($slug);

        if (!csrf_verify($this->request->post('_token'))) {
            $this->redirect("/admin/$slug/$id/edit");
        }

        $res['model']::update((int) $id, $this->collectFormData($res));
        $this->redirect("/admin/$slug");
    }

    // ── DELETE /admin/{resource}/{id} ────────────────────────────────────────

    public function destroy(string $slug, string $id): void
    {
        $res = $this->resource($slug);

        if (!csrf_verify($this->request->post('_token'))) {
            $this->redirect("/admin/$slug");
        }

        $res['model']::delete((int) $id);
        $this->redirect("/admin/$slug");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function collectFormData(array $res): array
    {
        $data = [];

        foreach ($res['form_fields'] as $field => $meta) {
            if ($meta['type'] === 'checkbox') {
                $data[$field] = $this->request->post($field) === '1' ? 1 : 0;
                continue;
            }

            if ($meta['type'] === 'password') {
                $value = trim((string) $this->request->post($field, ''));
                if ($value === '') {
                    continue; // blank = keep the existing password unchanged
                }
                $data[$field] = password_hash($value, PASSWORD_DEFAULT);
                continue;
            }

            $data[$field] = $this->request->post($field, '');
        }

        return $data;
    }

    /** Renders an admin template wrapped in the admin layout. Self-contained — doesn't use the global view(). */
    private function render(string $template, array $data = []): void
    {
        $data['registry'] = $this->registry;
        extract($data);

        ob_start();
        require __DIR__ . "/templates/$template.php";
        $content = ob_get_clean();

        require __DIR__ . '/templates/layout.php';
    }
}