<div class="view-container">
    <div class="view-header">
        <a href="/todo" class="btn btn-secondary btn-sm">← <?= e(t('common.back')) ?></a>
        <h1><?= e(t('todo.new_task_title')) ?></h1>
    </div>

    <div class="card">
        <form method="POST" action="/todo">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="title"><?= e(t('todo.label_title')) ?> <span style="color:var(--danger)">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= e(old('title')) ?>"
                    placeholder="<?= e(t('todo.placeholder_title')) ?>"
                    autofocus
                    required
                >
            </div>

            <div class="form-group">
                <label for="description"><?= e(t('todo.label_description')) ?></label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="<?= e(t('todo.placeholder_description_new')) ?>"
                ><?= e(old('description')) ?></textarea>
            </div>

            <div class="form-actions">
                <a href="/todo" class="btn btn-secondary"><?= e(t('common.cancel')) ?></a>
                <button type="submit" class="btn btn-primary"><?= e(t('todo.create_task')) ?></button>
            </div>
        </form>
    </div>
</div>