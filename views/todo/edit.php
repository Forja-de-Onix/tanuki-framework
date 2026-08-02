<div class="view-container">
    <div class="view-header">
        <a href="/todo" class="btn btn-secondary btn-sm">← <?= e(t('common.back')) ?></a>
        <h1><?= e(t('todo.edit_task_title')) ?></h1>
    </div>

    <div class="card">
        <form method="POST" action="/todo/<?= e($todo['id']) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="PUT">

            <div class="form-group">
                <label for="title"><?= e(t('todo.label_title')) ?> <span style="color:var(--danger)">*</span></label>
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
                <label for="description"><?= e(t('todo.label_description')) ?></label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="<?= e(t('todo.placeholder_description_edit')) ?>"
                ><?= e($todo['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="completed"><?= e(t('todo.label_status')) ?></label>
                <select id="completed" name="completed">
                    <option value="0" <?= !$todo['completed'] ? 'selected' : '' ?>><?= e(t('todo.option_pending')) ?></option>
                    <option value="1" <?=  $todo['completed'] ? 'selected' : '' ?>><?= e(t('todo.option_completed')) ?></option>
                </select>
            </div>

            <div class="form-actions">
                <a href="/todo" class="btn btn-secondary"><?= e(t('common.cancel')) ?></a>
                <button type="submit" class="btn btn-primary"><?= e(t('todo.save_changes')) ?></button>
            </div>
        </form>
    </div>

    <!-- Danger zone -->
    <div class="card danger-zone">
        <h2><?= e(t('todo.danger_zone_title')) ?></h2>
        <p><?= e(t('todo.danger_zone_text')) ?></p>
        <form method="POST" action="/todo/<?= e($todo['id']) ?>"
              onsubmit="return confirm('<?= e(t('todo.confirm_delete_edit')) ?>')">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger btn-sm"><?= e(t('todo.delete_task_button')) ?></button>
        </form>
    </div>
</div>