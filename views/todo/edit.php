<div class="view-container">
    <div class="view-header">
        <a href="/todo" class="btn btn-secondary btn-sm">← Back</a>
        <h1>Edit Task</h1>
    </div>

    <div class="card">
        <!-- PUT via method override -->
        <form method="POST" action="/todo/<?= e($todo['id']) ?>">
            <input type="hidden" name="_method" value="PUT">

            <div class="form-group">
                <label for="title">Title <span style="color:var(--danger)">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= e($todo['title']) ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="description">Description <span class="text-muted">(optional)</span></label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Add more details..."
                ><?= e($todo['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="completed">Status</label>
                <select id="completed" name="completed">
                    <option value="0" <?= !$todo['completed'] ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="1" <?=  $todo['completed'] ? 'selected' : '' ?>>✓ Completed</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="/todo" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </div>

    <!-- Danger zone -->
    <div class="card danger-zone">
        <h2>Danger zone</h2>
        <p>This action is irreversible. The task will be permanently deleted.</p>
        <form method="POST" action="/todo/<?= e($todo['id']) ?>"
              onsubmit="return confirm('Are you sure you want to delete this task? This cannot be undone.')">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger btn-sm">Delete task</button>
        </form>
    </div>
</div>