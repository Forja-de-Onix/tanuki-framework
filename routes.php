<?php

/**
 * Tanuki Framework — Route Definitions
 *
 * Format: 'METHOD /path' => 'ControllerClass@method'
 *
 * Route parameters: use {name} to capture dynamic segments.
 *   Example: 'GET /users/{id}' => 'UserController@show'
 *   The captured value is passed as the first argument to the method: show($id)
 *
 * Method override support for HTML forms:
 *   Add <input type="hidden" name="_method" value="DELETE"> in the form.
 *   The framework detects it and routes correctly.
 */

return [

    // ── Main pages ─────────────────────────────────────────────────────────
    'GET /'      => 'HomeController@index',
    'GET /about' => 'AboutController@index',

    // ── TODO List — full CRUD ──────────────────────────────────────────────
    'GET /todo'              => 'TodoController@index',   // List
    'GET /todo/create'       => 'TodoController@create',  // Creation form
    'POST /todo'             => 'TodoController@store',   // Save new
    'GET /todo/{id}'         => 'TodoController@show',    // View detail
    'GET /todo/{id}/edit'    => 'TodoController@edit',    // Edit form
    'PUT /todo/{id}'         => 'TodoController@update',  // Save changes
    'DELETE /todo/{id}'      => 'TodoController@destroy', // Delete

];