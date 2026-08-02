<!DOCTYPE html>
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? env('APP_NAME', 'Tanuki App')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
    <header>
        <a href="/" class="brand">🦝 <span>Tanuki</span></a>
        <nav>
            <?php foreach (require __DIR__ . '/../config/nav.php' as $item): ?>
                <a href="<?= e($item['href']) ?>"
                <?= (($item['match'] === '/') ? ($uri === '/') : str_starts_with($uri, $item['match'])) ? 'class="active"' : '' ?>>
                    <?= e(t($item['label'])) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </header>
    <main>
        <?php
        foreach (['success', 'error', 'warning'] as $type) {
            $msg = flash($type);
            if ($msg): ?>
                <div class="flash <?= $type ?>"><?= e($msg) ?></div>
            <?php endif;
        }
        ?>