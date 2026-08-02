<?php

/**
 * TodoController — Full CRUD for tasks
 *
 * Associated routes (see routes.php):
 *   GET    /todo              → index()
 *   GET    /todo/create       → create()
 *   POST   /todo              → store()
 *   GET    /todo/{id}         → show($id)
 *   GET    /todo/{id}/edit    → edit($id)
 *   PUT    /todo/{id}         → update($id)
 *   DELETE /todo/{id}         → destroy($id)
 */
class TodoController extends Controller
{
    // ── GET /todo ────────────────────────────────────────────────────────────

    public function index(): void
    {
        $todo = TodoModel::allOrdered();

        $this->view('todo/index', [
            'title'     => t('todo.list_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
            'todo'      => $todo,
            'total'     => count($todo),
            'pending'   => count(array_filter($todo, fn($t) => !$t['completed'])),
            'completed' => count(array_filter($todo, fn($t) =>  $t['completed'])),
        ]);
    }

    // ── GET /todo/create ─────────────────────────────────────────────────────

    public function create(): void
    {
        $this->view('todo/create', [
            'title' => t('todo.new_task_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
        ]);
    }

    // ── POST /todo ───────────────────────────────────────────────────────────

    public function store(): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', 'Invalid or expired session. Please try again.');
            $this->redirect('/todo/create');
        }

        $title       = $this->request->post('title');
        $description = $this->request->post('description', '');

        if (empty($title)) {
            keep_old(['title' => $title, 'description' => $description]);
            $this->flash('error', t('todo.flash_title_required'));
            $this->redirect('/todo/create');
        }

        $id = TodoModel::create([
            'title'       => $title,
            'description' => $description,
            'completed'   => 0,
        ]);

        if ($id) {
            $this->flash('success', t('todo.flash_created'));
            $this->redirect('/todo');
        } else {
            $this->flash('error', t('todo.flash_create_failed'));
            $this->redirect('/todo/create');
        }
    }

    // ── GET /todo/{id} ───────────────────────────────────────────────────────

    public function show(string $id): void
    {
        $todo = TodoModel::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $this->view('todo/show', [
            'title' => $todo['title'] . ' — ' . env('APP_NAME', 'Tanuki App'),
            'todo'  => $todo,
        ]);
    }

    // ── GET /todo/{id}/edit ──────────────────────────────────────────────────

    public function edit(string $id): void
    {
        $todo = TodoModel::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $this->view('todo/edit', [
            'title' => t('todo.edit_task_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
            'todo'  => $todo,
        ]);
    }

    // ── PUT /todo/{id} ───────────────────────────────────────────────────────

    public function update(string $id): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', 'Invalid or expired session. Please try again.');
            $this->redirect("/todo/$id/edit");
        }

        $todo = TodoModel::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $title       = $this->request->post('title');
        $description = $this->request->post('description', '');
        $completed   = $this->request->post('completed') === '1' ? 1 : 0;

        if (empty($title)) {
            keep_old(['title' => $title, 'description' => $description]);
            $this->flash('error', t('todo.flash_title_required'));
            $this->redirect("/todo/$id/edit");
        }

        $ok = TodoModel::update((int) $id, [
            'title'       => $title,
            'description' => $description,
            'completed'   => $completed,
        ]);

        if ($ok) {
            $this->flash('success', t('todo.flash_updated'));
        } else {
            $this->flash('error', t('todo.flash_update_failed'));
        }

        $this->redirect('/todo');
    }

    // ── DELETE /todo/{id} ────────────────────────────────────────────────────

    public function destroy(string $id): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', 'Invalid or expired session. Please try again.');
            $this->redirect('/todo');
        }

        $todo = TodoModel::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $ok = TodoModel::delete((int) $id);

        if ($ok) {
            $this->flash('success', t('todo.flash_deleted'));
        } else {
            $this->flash('error', t('todo.flash_delete_failed'));
        }

        $this->redirect('/todo');
    }
}