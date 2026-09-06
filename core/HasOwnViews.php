<?php

trait HasOwnViews
{
    /** Renderiza una vista propia del módulo, envuelta en su layout local. Autónomo — no usa el view() global. */
    protected function view(string $template, array $data = []): void
    {
        $dir = dirname((new ReflectionClass($this))->getFileName());
        $path = "$dir/views/$template.php";
        $layoutPath = "$dir/views/layout.php";

        if (!file_exists($path)) {
            throw new RuntimeException("View '$template' does not exist at $path");
        }

        extract($data);

        ob_start();
        require $path;
        $content = ob_get_clean();

        if (file_exists($layoutPath)) {
            require $layoutPath; // el layout usa $content para insertar el HTML capturado
        } else {
            echo $content; // fallback: sin layout local, se muestra el contenido tal cual
        }
    }
}