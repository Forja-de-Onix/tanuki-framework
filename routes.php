<?php

$routes = [
    '/'        => 'HomeController@index',
    '/about'   => 'AboutController@show',
];

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (array_key_exists($request_uri, $routes)) {
    list($controller, $method) = explode('@', $routes[$request_uri]);
    
    require_once __DIR__ . "/controllers/$controller.php";

    $controllerInstance = new $controller();
    echo $controllerInstance->$method();
} else {
    http_response_code(404);
    echo "404 - Página no encontrada";
}
