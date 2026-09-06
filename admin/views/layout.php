<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? t('admin.title')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="/admin" class="admin-brand">🦝 <?= e(t('admin.title')) ?></a>
            <nav class="admin-nav">
                <?php foreach ($registry as $slug => $res): ?>
                    <a href="/admin/<?= e($slug) ?>"
                       class="<?= ($currentSlug ?? null) === $slug ? 'active' : '' ?>">
                        <?= e($res['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <a href="/" class="admin-back">← <?= e(t('admin.back_to_site')) ?></a>
        </aside>
        <main class="admin-main">
            <?= $content ?>
        </main>
    </div>
</body>
</html>