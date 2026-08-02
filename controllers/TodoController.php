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

    /** List of all todos */
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

    /** Form to create a new todo */
    public function create(): void
    {
        $this->view('todo/create', [
            'title' => t('todo.new_task_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
        ]);
    }

    // ── POST /todo ───────────────────────────────────────────────────────────

    /** Saves a new todo to the DB */
    public function store(): void
    {
        $title       = $this->request->post('title');
        $description = $this->request->post('description', '');

        // Basic validation
        if (empty($title)) {
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

    /** Todo detail */
    public function show(string $id): void
    {
        $todo = TodoModel::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $this->view('todo/show', [
            'title' => e($todo['title']) . ' — ' . env('APP_NAME', 'Tanuki App'),
            'todo'  => $todo,
        ]);
    }

    // ── GET /todo/{id}/edit ──────────────────────────────────────────────────

    /** Edit form */
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

    /** Updates an existing todo */
    public function update(string $id): void
    {
        $todo = TodoModel::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $title       = $this->request->post('title');
        $description = $this->request->post('description', '');
        $completed   = $this->request->post('completed') === '1' ? 1 : 0;

        if (empty($title)) {
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

    /** Deletes a todo */
    public function destroy(string $id): void
    {
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