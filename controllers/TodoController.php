<?php

/**
 * TodoController — CRUD completo de tareas
 *
 * Rutas asociadas (ver routes.php):
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

    /** Listado de todos los todo */
    public function index(): void
    {
        $todo = Todo::allOrdered();

        $this->view('todo/index', [
            'title' => 'TODO List — ' . env('APP_NAME', 'Tanuki App'),
            'todo' => $todo,
            'total'     => count($todo),
            'pending'   => count(array_filter($todo, fn($t) => !$t['completed'])),
            'completed' => count(array_filter($todo, fn($t) =>  $t['completed'])),
        ]);
    }

    // ── GET /todo/create ─────────────────────────────────────────────────────

    /** Formulario para crear un nuevo todo */
    public function create(): void
    {
        $this->view('todo/create', [
            'title' => 'Nueva Tarea — ' . env('APP_NAME', 'Tanuki App'),
        ]);
    }

    // ── POST /todo ───────────────────────────────────────────────────────────

    /** Guarda un nuevo todo en la BD */
    public function store(): void
    {
        $title       = $this->request->post('title');
        $description = $this->request->post('description', '');

        // Validación básica
        if (empty($title)) {
            $this->flash('error', 'El título es obligatorio.');
            $this->redirect('/todo/create');
        }

        $id = Todo::create([
            'title'       => $title,
            'description' => $description,
            'completed'   => 0,
        ]);

        if ($id) {
            $this->flash('success', '¡Tarea creada correctamente!');
            $this->redirect('/todo');
        } else {
            $this->flash('error', 'No se pudo crear la tarea. Inténtalo de nuevo.');
            $this->redirect('/todo/create');
        }
    }

    // ── GET /todo/{id} ───────────────────────────────────────────────────────

    /** Detalle de un todo */
    public function show(string $id): void
    {
        $todo = Todo::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $this->view('todo/show', [
            'title' => e($todo['title']) . ' — ' . env('APP_NAME', 'Tanuki App'),
            'todo'  => $todo,
        ]);
    }

    // ── GET /todo/{id}/edit ──────────────────────────────────────────────────

    /** Formulario de edición */
    public function edit(string $id): void
    {
        $todo = Todo::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $this->view('todo/edit', [
            'title' => 'Editar Tarea — ' . env('APP_NAME', 'Tanuki App'),
            'todo'  => $todo,
        ]);
    }

    // ── PUT /todo/{id} ───────────────────────────────────────────────────────

    /** Actualiza un todo existente */
    public function update(string $id): void
    {
        $todo = Todo::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $title       = $this->request->post('title');
        $description = $this->request->post('description', '');
        $completed   = $this->request->post('completed') === '1' ? 1 : 0;

        if (empty($title)) {
            $this->flash('error', 'El título es obligatorio.');
            $this->redirect("/todo/$id/edit");
        }

        $ok = Todo::update((int) $id, [
            'title'       => $title,
            'description' => $description,
            'completed'   => $completed,
        ]);

        if ($ok) {
            $this->flash('success', '¡Tarea actualizada correctamente!');
        } else {
            $this->flash('error', 'No se pudo actualizar la tarea.');
        }

        $this->redirect('/todo');
    }

    // ── DELETE /todo/{id} ────────────────────────────────────────────────────

    /** Elimina un todo */
    public function destroy(string $id): void
    {
        $todo = Todo::find((int) $id);

        if (!$todo) {
            $this->abort404();
        }

        $ok = Todo::delete((int) $id);

        if ($ok) {
            $this->flash('success', 'Tarea eliminada.');
        } else {
            $this->flash('error', 'No se pudo eliminar la tarea.');
        }

        $this->redirect('/todo');
    }
}
