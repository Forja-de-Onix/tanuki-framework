<div class="admin-header-row">
    <h1><?= e($resource['label']) ?></h1>
    <a href="/admin/<?= e($slug) ?>/create" class="btn btn-primary">+ New</a>
</div>

<?php if (empty($records)): ?>
    <p class="text-muted">No records yet.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <?php foreach ($resource['list_fields'] as $field): ?>
                    <th><?= e(ucfirst(str_replace('_', ' ', $field))) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $record): ?>
            <tr>
                <?php foreach ($resource['list_fields'] as $field): ?>
                    <td><?= e($record[$field] ?? '') ?></td>
                <?php endforeach; ?>
                <td class="actions">
                    <a href="/admin/<?= e($slug) ?>/<?= e($record['id']) ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" action="/admin/<?= e($slug) ?>/<?= e($record['id']) ?>" class="inline-form"
                          onsubmit="return confirm('Delete this record?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>