<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin') ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="/admin" class="admin-brand">🦝 Admin</a>
            <nav class="admin-nav">
                <?php foreach ($registry as $adminSlug => $adminRes): ?>
                    <a href="/admin/<?= e($adminSlug) ?>"><?= e($adminRes['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <a href="/" class="admin-back">← Back to site</a>
        </aside>
        <main class="admin-main">
            <?= $content ?>
        </main>
    </div>
</body>
</html>