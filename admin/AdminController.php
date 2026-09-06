<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/HasOwnViews.php';

class AdminController extends Controller
{
    use HasOwnViews;

    private array $registry;

    public function __construct()
    {
        parent::__construct();
        $this->registry = require __DIR__ . '/admin.php';
        $this->guard();
    }

    private function guard(): void
    {
        auth_require();
        $user = auth_user();
        if (empty($user['is_admin'])) {
            http_response_code(403);
            exit('Forbidden: admin access only.');
        }
    }

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
        $this->view('admin/dashboard', [
            'title'     => 'Admin',
            'registry'  => $this->registry,
        ]);
    }


    // ── GET /admin/{resource} ────────────────────────────────────────────────

    public function index(string $slug): void
    {
        $res      = $this->resource($slug);
        $model    = $res['model'];
        $orderBy  = $res['order_by']  ?? 'id';
        $orderDir = $res['order_dir'] ?? 'ASC';

        $this->view('admin/index', [
            'title'    => $res['label'] . ' — Admin',
            'slug'     => $slug,
            'resource' => $res,
            'records'  => $model::all($orderBy, $orderDir),
            'registry' => $this->registry,
        ]);
    }

    // ── GET /admin/{resource}/create ─────────────────────────────────────────

    public function create(string $slug): void
    {
        $res = $this->resource($slug);

        $this->view('admin/form', [
            'title'    => 'New ' . $res['label'] . ' — Admin',
            'slug'     => $slug,
            'resource' => $res,
            'record'   => null,
            'registry' => $this->registry,
        ]);
    }

    // ── POST /admin/{resource} ───────────────────────────────────────────────

    public function store(string $slug): void
    {
        $res = $this->resource($slug);

        if (!csrf_verify($this->request->post('_token'))) {
            $this->redirect("/admin/$slug/create");
        }

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

        $this->view('admin/form', [
            'title'    => 'Edit ' . $res['label'] . ' — Admin',
            'slug'     => $slug,
            'resource' => $res,
            'record'   => $record,
            'registry' => $this->registry,
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
                    continue;
                }
                $data[$field] = password_hash($value, PASSWORD_DEFAULT);
                continue;
            }

            $data[$field] = $this->request->post($field, '');
        }

        return $data;
    }
}