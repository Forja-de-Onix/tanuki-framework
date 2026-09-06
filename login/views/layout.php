<!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? env('APP_NAME', 'Tanuki App')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
    <?= $content ?>
</body>
</html>