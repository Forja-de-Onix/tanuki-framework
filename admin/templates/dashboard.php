<h1>Admin Dashboard</h1>
<p class="text-muted">Manage your data below.</p>
<div class="admin-resource-grid">
    <?php foreach ($resources as $slug => $res): ?>
        <a href="/admin/<?= e($slug) ?>" class="admin-resource-card"><?= e($res['label']) ?></a>
    <?php endforeach; ?>
</div>