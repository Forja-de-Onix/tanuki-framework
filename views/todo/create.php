<div class="view-container">
    <div class="view-header">
        <a href="/todo" class="btn btn-secondary btn-sm">← Back</a>
        <h1>New Task</h1>
    </div>

    <div class="card">
        <form method="POST" action="/todo">
            <div class="form-group">
                <label for="title">Title <span style="color:var(--danger)">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= e(old('title')) ?>"
                    placeholder="E.g.: Buy milk"
                    autofocus
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description <span class="text-muted">(optional)</span></label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Add more details about the task..."
                ><?= e(old('description')) ?></textarea>
            </div>

            <div class="form-actions">
                <a href="/todo" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create task</button>
            </div>
        </form>
    </div>
</div>