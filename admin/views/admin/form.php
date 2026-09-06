<h1><?= e($title) ?></h1>

<form method="POST" action="<?= $record ? "/admin/{$slug}/{$record['id']}" : "/admin/{$slug}" ?>">
    <?= csrf_field() ?>
    <?php if ($record): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <?php foreach ($resource['form_fields'] as $field => $meta): ?>
        <?php $value = $record[$field] ?? ''; ?>
        <div class="form-group">
            <label for="<?= e($field) ?>"><?= e($meta['label']) ?></label>

            <?php if ($meta['type'] === 'textarea'): ?>
                <textarea id="<?= e($field) ?>" name="<?= e($field) ?>"><?= e($value) ?></textarea>
            <?php elseif ($meta['type'] === 'checkbox'): ?>
                <input type="hidden" name="<?= e($field) ?>" value="0">
                <input type="checkbox" id="<?= e($field) ?>" name="<?= e($field) ?>" value="1" <?= $value ? 'checked' : '' ?>>
            <?php elseif ($meta['type'] === 'password'): ?>
                <input
                    type="password"
                    id="<?= e($field) ?>"
                    name="<?= e($field) ?>"
                    placeholder="<?= $record ? 'Leave blank to keep current password' : '' ?>"
                >
            <?php else: ?>
                <input type="text" id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e($value) ?>">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="form-actions">
        <a href="/admin/<?= e($slug) ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>