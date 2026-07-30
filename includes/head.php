<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mi Sitio' ?></title>
</head>
<body>
    <header>
        <h1>Mi Sitio Web</h1>
        <nav>
            <a href="/">Inicio</a>
            <a href="/about">Acerca de</a>
        </nav>
    </header>
    <main>
        <?php echo $content ?? ''; ?>
    </main>