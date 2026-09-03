<?php

require_once __DIR__ . '/admin/AdminController.php'; // tanuki_admin (comment to disable)

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

    // ── tanuki_admin (comment to disable) ────
    'GET /admin'                      => 'AdminController@dashboard',
    'GET /admin/{resource}'           => 'AdminController@index',
    'GET /admin/{resource}/create'    => 'AdminController@create',
    'POST /admin/{resource}'          => 'AdminController@store',
    'GET /admin/{resource}/{id}/edit' => 'AdminController@edit',
    'PUT /admin/{resource}/{id}'      => 'AdminController@update',
    'DELETE /admin/{resource}/{id}'   => 'AdminController@destroy',

    // ── tanuki_login (comment to disable) ──────────────────────────────────
    'GET /login'                   => 'AuthController@showLogin',
    'POST /login'                  => 'AuthController@login',
    'POST /logout'                 => 'AuthController@logout',
    'GET /register'                => 'AuthController@showRegister',
    'POST /register'               => 'AuthController@register',
    'GET /forgot-password'         => 'AuthController@showForgot',
    'POST /forgot-password'        => 'AuthController@sendResetLink',
    'GET /reset-password/{token}'  => 'AuthController@showReset',
    'POST /reset-password/{token}' => 'AuthController@resetPassword',
    'GET /profile' => 'ProfileController@edit',
    'POST /profile' => 'ProfileController@update',
    
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