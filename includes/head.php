<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
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

        <?php if (locale_switcher_enabled()): ?>
            <div class="lang-switcher">
                <?php foreach (accepted_locales() as $code): ?>
                    <?php $meta = locale_meta($code); ?>
                    <a href="<?= e(locale_switch_url($code)) ?>"
                    class="lang-flag <?= current_locale() === $code ? 'active' : '' ?>"
                    title="<?= e($meta['label']) ?>">
                        <?= $meta['flag'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (function_exists('auth_check') && auth_check()): ?>
            <?php $currentUser = auth_user(); ?>
            <div class="user-menu">
                <button type="button" class="user-avatar">
                    <?= e(mb_strtoupper(mb_substr($currentUser['name'], 0, 1))) ?>
                </button>
                <div class="user-dropdown">
                    <div class="user-dropdown-name"><?= e($currentUser['name']) ?></div>
                    <a href="/profile"><?= e(t('profile.edit_link')) ?></a>
                    <form method="POST" action="/logout">
                        <?= csrf_field() ?>
                        <button type="submit"><?= e(t('auth.logout_button')) ?></button>
                    </form>
                </div>
            </div>
        <?php elseif (function_exists('auth_check')): ?>
            <a href="/login" class="btn btn-primary btn-sm"><?= e(t('auth.login_button')) ?></a>
        <?php endif; ?>
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