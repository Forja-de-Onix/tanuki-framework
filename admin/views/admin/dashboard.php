<div class="admin-header-row">
    <h1><?= e(t('admin.dashboard_title')) ?></h1>
</div>
<p class="admin-subtitle"><?= e(t('admin.dashboard_subtitle')) ?></p>

<div class="admin-resource-grid">
    <?php foreach ($registry as $slug => $res): ?>
        <a href="/admin/<?= e($slug) ?>" class="admin-resource-card">
            <?= e($res['label']) ?>
        </a>
    <?php endforeach; ?>
</div>