<?php

function view($viewName, $data = [])
{
    extract($data); 
    $viewFile = __DIR__ . "/views/$viewName.php";

    if (file_exists($viewFile)) {
        require $viewFile;
    } else {
        die("La vista '$viewName' no existe.");
    }
}