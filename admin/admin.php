<?php

/**
 * Tanuki Framework — tanuki_admin resource registry
 *
 * Similar to Django's admin.site.register(): each entry exposes one
 * model in the admin panel. The array key is the URL slug (/admin/{slug}).
 *
 *   'model'       Fully-qualified model class name (must extend Model)
 *   'label'       Display name in the sidebar and page titles
 *   'list_fields' Columns shown in the list table
 *   'form_fields' Fields shown in the create/edit form:
 *                     'field_name' => ['type' => 'text|textarea|checkbox', 'label' => 'Display label']
 *   'order_by'    Column to sort the list by (default: 'id')
 *   'order_dir'   'ASC' or 'DESC' (default: 'ASC')
 */

return [

    'todo' => [
        'model'       => TodoModel::class,
        'label'       => 'Tasks',
        'list_fields' => ['id', 'title', 'completed', 'created_at'],
        'form_fields' => [
            'title'       => ['type' => 'text',     'label' => 'Title'],
            'description' => ['type' => 'textarea', 'label' => 'Description'],
            'completed'   => ['type' => 'checkbox', 'label' => 'Completed'],
        ],
        'order_by'  => 'created_at',
        'order_dir' => 'DESC',
    ],

    'users' => [
        'model'       => UserModel::class,
        'label'       => 'Users',
        'list_fields' => ['id', 'name', 'email', 'is_admin'],
        'form_fields' => [
            'name'     => ['type' => 'text',     'label' => 'Name'],
            'email'    => ['type' => 'text',     'label' => 'Email'],
            'password' => ['type' => 'password', 'label' => 'Password'],
            'is_admin' => ['type' => 'checkbox', 'label' => 'Admin'],
        ],
        'order_by' => 'name',
    ],

];