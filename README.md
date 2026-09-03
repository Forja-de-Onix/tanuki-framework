# 🦝 Tanuki Framework — Base MVC

A lightweight PHP framework with a clean MVC architecture, database access, and zero external dependencies. No ORM, no template engine — a skeleton you build on top of.

**Requirements:** PHP 8.1+ · MySQL/MariaDB or PostgreSQL · Apache or Nginx

---

## Table of contents

1. [Prerequisites: installing PHP and a database from scratch (Ubuntu/Mint/Corvorum)](#prerequisites-installing-php-and-a-database-from-scratch-ubuntu-mint-corvorum)
2. [Installation](#installation)
3. [Project structure](#project-structure)
4. [Request lifecycle](#request-lifecycle)
5. [Routes](#routes)
6. [Controllers](#controllers)
7. [The Request class](#the-request-class)
8. [Models](#models)
9. [Views and layout](#views-and-layout)
10. [Global helpers](#global-helpers)
11. [Optional connections: Redis and MongoDB](#optional-connections-redis-and-mongodb)
12. [Authentication (tanuki_login)](#authentication-tanuki_login)
13. [Walkthrough: the built-in TODO CRUD](#walkthrough-the-built-in-todo-crud)
14. [Adding your own CRUD](#adding-your-own-crud)
15. [Security](#security)
16. [Local development with Xdebug](#local-development-with-xdebug)
17. [Internationalization (i18n)](#internationalization-i18n)
18. [Testing](#testing)
19. [Docker](#docker)
20. [FAQ](#faq)

---

## Prerequisites: installing PHP and a database from scratch (Ubuntu/Mint/Corvorum)

This section assumes a bare Ubuntu/Mint/Corvorum machine with nothing installed. If PHP and your database are already set up, skip to [Installation](#installation).

### 1. PHP

```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip unzip
```

Add the database driver for the engine you'll use (see step 2):

```bash
sudo apt install php8.1-mysql    # for MySQL/MariaDB
# and/or
sudo apt install php8.1-pgsql    # for PostgreSQL
```

Verify:

```bash
php -v
php -m | grep -iE "pdo_mysql|pdo_pgsql|xml|mbstring"
```

### 2. Database — choose MySQL/MariaDB, PostgreSQL, or both

Tanuki supports both through `DB_DRIVER` in `.env` — you only need to install the one(s) you're actually going to use.

#### Option A — MySQL/MariaDB

```bash
sudo apt install mariadb-server
sudo systemctl enable --now mariadb
sudo mysql_secure_installation
```

`mysql_secure_installation` walks you through setting a root password, removing anonymous users, and disabling remote root login — answer "yes" to all of it for a normal local setup.

Create the app's database and a dedicated user (never use `root` in `.env`):

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE my_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'my_app_user'@'localhost' IDENTIFIED BY 'a_strong_password';
GRANT ALL PRIVILEGES ON my_database.* TO 'my_app_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

These values go directly into `.env` as `DB_NAME`, `DB_USER`, `DB_PASS` — see [Installation](#installation).

#### Option B — PostgreSQL

```bash
sudo apt install postgresql postgresql-contrib
sudo systemctl enable --now postgresql
```

Create the app's database and a dedicated user:

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE my_database;
CREATE USER my_app_user WITH ENCRYPTED PASSWORD 'a_strong_password';
GRANT ALL PRIVILEGES ON DATABASE my_database TO my_app_user;
\q
```

Set `DB_DRIVER=pgsql` in `.env` when using this option — see [Installation](#installation).

### 3. Everything else installs as you go

Composer, PHPUnit, Redis, MongoDB, Docker, and Xdebug each have their own installation steps in their respective sections below ([Testing](#testing), [Optional connections](#optional-connections-redis-and-mongodb), [Local development with Xdebug](#local-development-with-xdebug)) — install them only when you actually need them.

## Installation

```bash
# 1. Clone the repository
git clone <repo-url> my-project
cd my-project

# 2. Copy the environment file and configure it
cp .env-example .env
nano .env   # Adjust DB_NAME, DB_USER, DB_PASS, APP_URL

# 3. Point your Apache/Nginx VirtualHost to the public/ folder (see below)
```

### Environment configuration (`.env`)

From the project root we copy `.env-example` to `.env` and update all the data based on our configuration:

```ini
APP_NAME="My App"
APP_ENV=development     # development | production
APP_DEBUG=true          # true = show detailed errors
APP_URL=http://localhost

DB_DRIVER=mysql          # mysql | pgsql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_database
DB_USER=root
DB_PASS=my_password
DB_CHARSET=utf8mb4

# Optional
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

MONGO_URI=mongodb://127.0.0.1:27017
MONGO_DATABASE=
```

> **Important:** `.env` is in `.gitignore` and must never be committed to the repository.

### Local development server

```bash
php -S localhost:8050 -t public public/index.php
```

`-t public` tells PHP's built-in server that the document root is `public/` — the same folder that Nginx/Apache will point to in production.

---

## Project structure

```
tanuki_base/
│
├── public/ ← ONLY folder exposed to the web
│ ├── index.php ← Entry point (only calls App::run())
│ ├── .htaccess ← Apache rewrite rules
│ └── assets/
│ ├── css/app.css
│ └── js/app.js
│
├── routes.php ← All route definitions
├── utils.php ← Global helpers: e(), view(), env(), redirect()…
│
├── .env ← Local environment variables (NOT in git)
├── .env-example ← Documented template
├── nginx.config ← Nginx server block reference
│
├── config/
│ ├── database.php ← Database class (PDO Singleton, mysql/pgsql)
│ ├── redis.php ← Redis class (optional, native phpredis)
│ ├── mongo.php ← Mongo class (optional, mongodb/mongodb)
│ └── nav.php ← Navigation menu entries
│
├── core/
│ ├── App.php ← Bootstrap: env loader, autoloader, router
│ ├── Controller.php ← Base controller class
│ ├── Model.php ← Lightweight Active Record base
│ └── Request.php ← HTTP request wrapper
│
├── controllers/ ← Your controllers (extend Controller)
├── models/ ← Your models (extend Model)
├── views/ ← Pure PHP templates
│ └── errors/ ← 404 / 500 / 503 error views
└── includes/
├── head.php ← HTML head + nav + opens <main>
└── footer.php ← Closes </main> + footer + app.js
```

### Why `public/` is the only exposed folder

Everything except `public/` sits outside the web server's document root. This means `core/`, `controllers/`, `models/`, and `config/` can never be requested directly by URL, no matter how the server is configured — the framework's PHP classes simply aren't reachable from outside. This is the same pattern used by Laravel, Symfony, and most modern PHP frameworks.

---

## Request lifecycle

```
Browser → public/index.php → App::run()
│
├── loadEnv() Reads .env → $_ENV
├── registerAutoloader() Finds classes in core/, controllers/, models/
├── configureErrors() Debug on/off based on APP_DEBUG
└── dispatch()
│
├── Detects HTTP method (+ _method override for PUT/DELETE)
├── Normalizes the URI
├── Matches an exact route or a {param} pattern
└── Instantiates the Controller → calls method($params)
│
└── this->view('name', data)
│
├── extract($data)
├── Captures the view in a buffer
├── head.php (uses $title, $uri)
├── echo $content
└── footer.php
```

---

## Routes

### Format

```php
// routes.php
return [
    'GET  /path'        => 'MyController@method',
    'POST /path'        => 'MyController@store',
    'PUT  /path/{id}'   => 'MyController@update',
    'DELETE /path/{id}' => 'MyController@destroy',
];
```

### Route parameters

Segments wrapped in `{name}` are captured and passed as method arguments:

```php
// routes.php
'GET /articles/{slug}' => 'ArticleController@show',

// ArticleController.php
public function show(string $slug): void { /* ... */ }
```

**Order matters.** More specific literal routes must come before dynamic patterns that could match the same URL:

```php
'GET /todo/create'  => 'TodoController@create', // must come first
'GET /todo/{id}'     => 'TodoController@show',   // otherwise this catches "create" as an id
```

### Method override (PUT/DELETE from HTML forms)

Browsers only send GET and POST natively. To use PUT/DELETE from a plain HTML form:

```html
<form method="POST" action="/todo/42">
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit">Delete</button>
</form>
```

---

## Controllers

Every controller extends `Controller` and gets access to `$this->request`, plus a set of response helpers.

```php
<?php

class ArticleController extends Controller
{
    public function index(): void
    {
        $this->view('article/index', [
            'title'    => 'Articles',
            'articles' => ArticleModel::all(),
        ]);
    }

    public function store(): void
    {
        $title = $this->request->post('title');

        if (empty($title)) {
            $this->flash('error', 'Title is required.');
            $this->redirect('/article/create');
        }

        ArticleModel::create(['title' => $title]);
        $this->flash('success', 'Article created.');
        $this->redirect('/article');
    }
}
```

### Methods available on `Controller`

| Method | Description |
|---|---|
| `$this->view($name, $data)` | Renders a view inside the layout |
| `$this->redirect($path)` | Redirects and stops execution |
| `$this->json($data, $status)` | Returns JSON and stops execution |
| `$this->abort404()` | Shows the 404 page and stops execution |
| `$this->flash($key, $message)` | Stores a flash message in the session |
| `$this->request` | The current `Request` instance |

---

## The Request class

```php
$this->request->post('field')        // Value from $_POST
$this->request->get('param')         // Value from $_GET
$this->request->input('field')       // POST first, falls back to GET
$this->request->all()                // All inputs merged (POST wins)
$this->request->only(['a', 'b'])     // Only those fields
$this->request->except(['token'])    // All fields except those
$this->request->has('field')         // bool: exists and not empty?
$this->request->file('avatar')       // $_FILES entry, or null
$this->request->method()             // 'GET', 'POST', 'PUT'…
$this->request->isGet() / isPost() / isPut() / isDelete()
$this->request->isAjax()             // bool
$this->request->ip()                 // Client IP (see Security notes below)
$this->request->uri()                // Current path, no query string
```

---

## Models

Every model extends `Model` and declares its table name:

```php
<?php

class ArticleModel extends Model
{
    protected static string $table = 'articles';
    protected static bool $timestamps = true; // auto created_at / updated_at
}
```

> **Naming convention:** model classes and files use the `XxxModel` suffix (e.g. `TodoModel.php` → `class TodoModel`) to avoid ambiguity with the table name or with unrelated classes. The autoloader matches the file name to the class name exactly, so renaming one always requires renaming the other.

### Model API

```php
// ── Read ─────────────────────────────────────────────────────────────
ArticleModel::all()                        // All records
ArticleModel::all('title', 'DESC')         // With custom order
ArticleModel::find(5)                      // By ID → array | null
ArticleModel::where('active', 1)           // Simple filter
ArticleModel::where('views', '>', 100)     // With operator (=, !=, <, >, <=, >=, LIKE)
ArticleModel::first()                      // First record
ArticleModel::count()                      // Total records

// Pagination
$result = ArticleModel::paginate(page: 1, perPage: 10);
// $result['data']    → records for this page
// $result['total']   → total records in the table
// $result['pages']   → total number of pages
// $result['current'] → current page

// ── Write ────────────────────────────────────────────────────────────
$id = ArticleModel::create(['title' => 'Hello']);   // → int (inserted ID)
ArticleModel::update(5, ['title' => 'Updated']);    // → bool
ArticleModel::delete(5);                            // → bool
```

> If `$timestamps = true`, `create()` fills `created_at`/`updated_at` automatically, and `update()` refreshes `updated_at`.

> Column and table identifiers are validated and quoted automatically (backticks for MySQL, double quotes for PostgreSQL based on `DB_DRIVER`) — you never need to escape them yourself, and dynamic column names are rejected if they aren't simple identifiers.

---

## Views and layout

Views are plain PHP files under `views/`. They're always rendered inside the shared layout (`includes/head.php` + view content + `includes/footer.php`).

```php
// Controller
$this->view('article/show', [
    'title'   => 'Article detail',
    'article' => $article,
]);
```

```php
<!-- views/article/show.php -->
<h1><?= e($article['title']) ?></h1>
<p><?= e($article['body']) ?></p>
```

**Golden rule:** always use `e()` to print data. Never `echo $var` directly — it's how XSS gets in.

### What's available inside every view

- `$uri` — the current normalized path, injected automatically by `view()`. Used by `head.php` to highlight the active nav item.
- Any key you pass in the `$data` array to `view()` or `$this->view()`.
- All global helpers (`e()`, `url()`, `old()`, `flash()`, etc.), since they're plain functions.

### Styling and assets

All shared styles live in `public/assets/css/app.css`, loaded once from `head.php`. Avoid inline `style="..."` attributes in new views — add a class to `app.css` instead, so the whole project stays visually consistent and themeable from one file.

`public/assets/js/app.js` currently only auto-dismisses flash messages after 5 seconds. Add new interactive behavior there rather than inline `<script>` blocks in views.

---

## Global helpers (`utils.php`)

| Function | Description |
|---|---|
| `e($value)` | Escapes HTML to prevent XSS |
| `env($key, $default)` | Reads a variable from `.env` |
| `view($name, $data)` | Renders a view inside the layout |
| `url($path)` | Absolute URL: `url('/about')` → `http://localhost:8050/about` |
| `redirect($path)` | Redirects and stops execution |
| `current_uri()` | Current normalized path (single source of truth for router + views) |
| `session_ensure()` | Starts the session if not already started |
| `flash($key)` | Reads and clears a flash message |
| `old($key, $default)` | Retrieves a previous input value (for re-populating forms) |
| `format_date($datetime)` | Formats a date according to `APP_LOCALE` (`en` → m/d/Y, `es` → d/m/Y) |
| `t($key, $replace = [])` | Translates a dot-notation key (see [Internationalization](#internationalization)) |
| `current_locale()` | Active locale: session override if set, otherwise `APP_LOCALE` from `.env` |
| `locale_switch_url($code)` | Builds the URL for switching to a given language, preserving the current path |
| `format_date($datetime)` | Formats a date according to `APP_LOCALE` (`en` → m/d/Y, `es` → d/m/Y) |
| `keep_old($data)` | Saves data to the session so `old()` can retrieve it after a redirect (used on validation failure) |
| `csrf_field()` | Renders a hidden `<input>` with the current CSRF token |
| `csrf_verify($token)` | Timing-safe verification of a submitted CSRF token |
| `auth_check()` | Returns `true` if a user is currently logged in |
| `auth_user()` | Returns the logged-in user's record, or `null` |
| `auth_require()` | Redirects to `/login` if not authenticated; call as the first line of a protected controller method |

---

## Optional connections: Redis and MongoDB

Tanuki ships with three independent, lazy-loaded connection classes — `Database`, `Redis`, and `Mongo`. They don't share an interface on purpose: each exposes its native driver API directly, so the framework never grows into a hidden ORM/ODM. Use whichever your project needs; unused ones never connect.

```php
// Relational (PDO) — see Models section above for the full API
Database::connect();

// Redis (requires the native php-redis extension)
Redis::connect()->set('key', 'value');
Redis::connect()->get('key');
Redis::connect()->expire('key', 3600);

// MongoDB (requires the mongodb extension + composer require mongodb/mongodb)
Mongo::connect()->selectCollection('posts')->find(['status' => 'published']);
Mongo::connect()->selectCollection('posts')->insertOne(['title' => 'Hello']);
```

### Installing the drivers

```bash
# Redis
sudo apt install php-redis

# MongoDB
sudo apt install php-mongodb
composer require mongodb/mongodb
```

Both are entirely optional — a project that never calls `Redis::connect()` or `Mongo::connect()` never opens those connections.

---

## Authentication (`tanuki_login`)

Tanuki ships with a complete, session-based authentication system — login, logout, registration, and password recovery by email. Like the TODO CRUD, it lives in the repo from the start but stays completely inert: **no route is registered by default**, so it costs nothing at runtime until you uncomment its routes in `routes.php`.

### What's included

| Piece | File |
|---|---|
| Mail sending (SMTP) | `config/mail.php` — `Mail::send($to, $subject, $html)` |
| Session auth helpers | `auth.php` — `auth_check()`, `auth_user()`, `auth_login()`, `auth_logout()`, `auth_require()` |
| User model | `models/UserModel.php` |
| Password reset tokens | `models/PasswordResetModel.php` |
| Controller | `controllers/AuthController.php` (login, register, forgot/reset password) |
| Profile editing | `controllers/ProfileController.php` |
| Views | `views/auth/*.php`, `views/profile/edit.php` |

### 1. Install PHPMailer

```bash
composer require phpmailer/phpmailer
```

### 2. Create the tables

**MySQL/MariaDB:**

```sql
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    token         VARCHAR(255) NOT NULL,
    expires_at    TIMESTAMP NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**PostgreSQL:**

```sql
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id            SERIAL PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    token         VARCHAR(255) NOT NULL,
    expires_at    TIMESTAMP NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

If you register the auth routes without creating these tables first, you'll get a clear 500 error (or the detailed exception in debug mode) the first time a query touches `users` — that's the intended signal that this setup step was skipped, not something the framework silently hides behind a 404.

### 3. Load the mail config and auth helpers

In `core/App.php`, alongside the other `config/` requires:

```php
require_once __DIR__ . '/../config/mail.php';
```

And add this new require (auth.php lives at the project root, next to `utils.php`):

```php
require_once __DIR__ . '/../auth.php';
```

### 4. Configure SMTP in `.env`

```ini
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Uncomment the routes

```php
// routes.php
'GET  /login'                   => 'AuthController@showLogin',
'POST /login'                   => 'AuthController@login',
'POST /logout'                  => 'AuthController@logout',
'GET  /register'                => 'AuthController@showRegister',
'POST /register'                => 'AuthController@register',
'GET  /forgot-password'         => 'AuthController@showForgot',
'POST /forgot-password'         => 'AuthController@sendResetLink',
'GET  /reset-password/{token}'  => 'AuthController@showReset',
'POST /reset-password/{token}'  => 'AuthController@resetPassword',
'GET  /profile'                 => 'ProfileController@edit',
'POST /profile'                 => 'ProfileController@update',
```

> **Watch your spacing.** Route keys are compared as exact strings (`"GET /login"`), so an extra space for column alignment (`'GET  /login'`) silently breaks the match — you'll get a 404 with no error, since the string just doesn't equal any registered key. Keep exactly one space between the method and the path.

### Protecting a route

```php
class DashboardController extends Controller
{
    public function index(): void
    {
        auth_require(); // redirects to /login if not authenticated
        $user = auth_user();

        $this->view('dashboard/index', ['user' => $user]);
    }
}
```

`auth_require()` also saves the current URL before redirecting, so `AuthController::login()` sends the user back to the page they originally wanted after a successful login.

### The user menu in the nav

`includes/head.php` already renders a user dropdown (avatar with the user's initial, profile link, logout button) when `auth_check()` is true, and a "Log In" button when it's false — no extra wiring needed once the routes are active.

### Password reset flow

1. User submits their email at `/forgot-password`.
2. A random token is generated, hashed with SHA-256, and stored in `password_resets` with a 1-hour expiry. The **plain** token (not the hash) is emailed as part of the reset link — this is standard practice: even if the `password_resets` table leaks, the stored hashes can't be used to reset accounts.
3. The same "link sent" message is shown whether or not the email exists, to avoid leaking which emails are registered.
4. `/reset-password/{token}` validates the token against the hash and expiry before allowing a new password.

### Editing your profile

`/profile` lets a logged-in user change their name and, optionally, their password (current password required to confirm the change) — but **not** their email, by design, to keep this first version simple. If your project needs email changes, that typically requires its own re-verification flow (confirm the new address before switching), which is a deliberate addition, not something bolted onto this simpler form.

### CSS note

If you're integrating this into a project that predates `tanuki_login`, make sure `input[type="password"]` is included in `assets/css/app.css`'s form-field selectors — it's easy to only style `input[type="text"]`/`input[type="email"]` and forget password fields, since the TODO CRUD example never used one.

---

## Admin Panel (`tanuki_admin`)

A lightweight Django-inspired admin: register a model in one file, get a CRUD list/create/edit/delete interface for it — no per-resource controllers or views to write. Fully isolated inside `admin/`; **depends on `tanuki_login`** (requires an authenticated user with `is_admin = 1`).

### What's included

| Piece | File |
|---|---|
| Resource registry | `admin/admin.php` — declare which models appear and how |
| Generic controller | `admin/AdminController.php` — one controller drives every registered resource |
| Templates | `admin/templates/*.php` — editable independently of the rest of the app |
| Superuser script | `admin/create-superuser.php` |

### 1. Add the `is_admin` column

```sql
-- MySQL/MariaDB
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;

-- PostgreSQL
ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT false;
```

### 2. Create your first admin account

```bash
php admin/create-superuser.php
```

Prompts for name, email, and password, and inserts the user with `is_admin = 1`.

### 3. Register your models

```php
// admin/admin.php
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
];
```

Field types: `text`, `textarea`, `checkbox`, `password`. The `password` type never displays the stored hash and only updates the field if left non-empty — required when creating a record, optional (keeps current value) when editing.

### 4. Enable the routes

```php
// routes.php — at the top
require_once __DIR__ . '/admin/AdminController.php';

// in the routes array
'GET    /admin'                      => 'AdminController@dashboard',
'GET    /admin/{resource}'           => 'AdminController@index',
'GET    /admin/{resource}/create'    => 'AdminController@create',
'POST   /admin/{resource}'           => 'AdminController@store',
'GET    /admin/{resource}/{id}/edit' => 'AdminController@edit',
'PUT    /admin/{resource}/{id}'      => 'AdminController@update',
'DELETE /admin/{resource}/{id}'      => 'AdminController@destroy',
```

### Removing the extension entirely

Delete the `admin/` folder and remove the `require_once` and routes above from `routes.php` — nothing elsewhere in the project references `admin/`, so no other file needs to change.

### Customizing templates

`admin/templates/layout.php`, `dashboard.php`, `index.php`, and `form.php` are plain PHP — edit them directly to change how the panel looks or behaves. Since `AdminController::render()` doesn't use the main app's `view()`, admin templates are entirely independent of `includes/head.php`/`footer.php`.

### Changing your own password vs. changing another user's

- Your own password: `/profile` (from `tanuki_login`) — asks for the current password before allowing a change.
- Another user's password: register `users` in `admin/admin.php` (see example above) and edit them from `/admin/users` — no current-password check applies here, since it's an admin action on someone else's account, not a self-service change.

---

## Walkthrough: the built-in TODO CRUD

The `todo` feature (model, controller, routes, and views) ships as a complete working example — it's the fastest way to see every part of the framework working together. It's meant as a **learning reference**, not a permanent part of your app: once you're comfortable with the pattern, remove it and build your own resources the same way.

### Step 1 — Create the table

```sql
CREATE TABLE todo (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    completed   TINYINT(1)  DEFAULT 0,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Step 2 — The model (`models/TodoModel.php`)

```php
<?php

class TodoModel extends Model
{
    protected static string $table = 'todo';
    protected static bool $timestamps = true;

    public static function allOrdered(): array
    {
        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->query("SELECT * FROM $table ORDER BY completed ASC, created_at DESC");
        return $stmt->fetchAll();
    }
}
```

### Step 3 — The routes (`routes.php`)

```php
'GET    /todo'           => 'TodoController@index',
'GET    /todo/create'    => 'TodoController@create',
'POST   /todo'           => 'TodoController@store',
'GET    /todo/{id}'      => 'TodoController@show',
'GET    /todo/{id}/edit' => 'TodoController@edit',
'PUT    /todo/{id}'      => 'TodoController@update',
'DELETE /todo/{id}'      => 'TodoController@destroy',
```

### Step 4 — The controller (`controllers/TodoController.php`)

See `controllers/TodoController.php` in the repo for the full implementation — it covers listing, creating, showing, editing, updating, and deleting, including flash messages and validation.

### Step 5 — The views (`views/todo/`)

- `index.php` — table listing with inline "complete/reopen" and "delete" actions
- `create.php` — creation form, re-populates fields with `old()` on validation failure
- `edit.php` — edit form + a "danger zone" delete section with a JS confirm dialog
- `show.php` — single-record detail view with quick toggle/delete actions

### Step 6 — Done

There is no step 6. The autoloader finds `TodoModel.php` and `TodoController.php` automatically by class name. Just visit `/todo` in the browser.

### Notes on the updated tutorial

Since the last revision, the example CRUD demonstrates two additional security patterns you can reuse in your own resources:

- **Form repopulation (`old()` + `keep_old()`):** when `store()`/`update()` fail validation (empty title), the controller calls `keep_old(['title' => ..., 'description' => ...])` before redirecting. `view()` automatically clears that value after the next render, so it only survives a single form. `create.php` already consumes it via `old('title')`/`old('description')` — no further view changes needed.
- **CSRF protection (`csrf_field()` + `csrf_verify()`):** every `<form>` in the CRUD now includes `<?= csrf_field() ?>`, and every controller action that mutates data (`store()`, `update()`, `destroy()`) starts by verifying `csrf_verify($this->request->post('_token'))` before touching the database. See the [Security](#security) section for the full detail on these two helpers and why they aren't enforced globally by the router.

---

## Adding your own CRUD

1. **SQL table** in your database.
2. **`models/YourModel.php`** → extends `Model`, sets `$table`.
3. **Routes** in `routes.php`.
4. **`controllers/YourController.php`** → extends `Controller`.
5. **Views** in `views/your-resource/` (one file per controller method: `index.php`, `show.php`, etc.).

---

## Security

| Risk | Mitigation |
|---|---|
| **XSS** | Use `e()` in every view |
| **SQL injection** | All `Model` methods use prepared statements; column/table names are validated against a strict identifier pattern |
| **Credential exposure** | Credentials only live in `.env` (excluded from git) |
| **Error exposure** | `APP_DEBUG=false` in production |
| **Source code exposure** | Only `public/` is reachable by the web server; `core/`, `controllers/`, `models/`, `config/` sit outside the document root |
| **Directory listing** | `Options -Indexes` in `public/.htaccess` |
| **Sensitive files (Nginx)** | `deny all` for `.env`, `.git`, `.htaccess` |
| **Spoofed client IP** | `Request::ip()` trusts `X-Forwarded-For`, which any client can set — only rely on it behind a trusted reverse proxy that overwrites that header |
| **Password storage** | `password_hash()`/`password_verify()` (bcrypt), never plain text |
| **Password reset tokens** | Stored as SHA-256 hashes, single-use (deleted after redemption), 1-hour expiry |
| **Session fixation** | `session_regenerate_id(true)` on login and logout |
| **Email enumeration** | `/forgot-password` shows the same message whether or not the email is registered |

---

## Local development with Xdebug

### 1. Install Xdebug

```bash
sudo apt install php-xdebug
sudo phpenmod xdebug
```

Verify it loaded:

```bash
php -v
# Should show: with Xdebug v3.x.x, ...
```

### 2. Configure `xdebug.ini`

Find the config file:

```bash
php --ini
```

Edit the Xdebug ini (typically `/etc/php/8.1/mods-available/xdebug.ini`) and add:

```ini
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

> `xdebug.mode=debug` enables step debugging (`develop` alone does NOT work).
> `xdebug.start_with_request=yes` makes Xdebug try to connect on every request without a manual trigger.

Verify it applied:

```bash
php -i | grep -i "xdebug.mode\|xdebug.start_with_request"
```

### 3. Editor extension (Antigravity / VS Code)

Install the **PHP Debug** extension (`xdebug.php-debug`) from the marketplace.

### 4. Create `launch.json`

In the Run & Debug view, create `.vscode/launch.json` at the project root:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003
        }
    ]
}
```

> **Don't add `pathMappings`** for purely local development (editor and server on the same machine) — a wrong or placeholder value there leaves breakpoints `unresolved` and they never trigger.

### 5. Debug

1. Select "Listen for Xdebug" in Run & Debug and press **F5**.
2. Start the server (from the project root, not from `public/`):
```bash
   php -S localhost:8050 -t public public/index.php
```
3. Set breakpoints and visit `http://localhost:8050`.

### Troubleshooting

- Check something is listening on the port: `sudo ss -ltnp | grep 9003`
- Temporarily enable Xdebug's log to diagnose:
```ini
  xdebug.log=/tmp/xdebug.log
  xdebug.log_level=7
```
  Then check `cat /tmp/xdebug.log` after making a request; look for whether `breakpoint_set` entries show `resolved` or `unresolved`.

---

## FAQ

**How do I add authentication?**
Tanuki ships with a complete session-based auth system (login, registration, password recovery by email) — see [Authentication](#authentication-tanuki_login). It's inert by default; uncomment its routes in `routes.php` to activate it.

**Can I use a different database engine (PostgreSQL, SQLite)?**
MySQL and PostgreSQL are supported today via `DB_DRIVER` in `.env`. SQLite isn't wired in yet.

**Can I use the framework without a database?**
Yes. The connection is lazy — if no route ever calls a model, no connection is ever opened.

**Where do assets (CSS, JS) go?**
`public/assets/`. They're served directly by the web server since they live inside the document root. Reference them with `url('/assets/css/app.css')`.

**Will there be a language/i18n system?**
It's already built in: `t()` + JSON dictionaries for translations, plus a working URL-prefix language switcher (`/en/...`, `/es/...`) with flags in the nav — see [Internationalization](#internationalization).

## Internationalization (i18n)

Tanuki ships with a minimal, file-based translation system: a global `t()` helper plus one JSON dictionary per language in `lang/`.

```
lang/
├── en.json
└── es.json
```

### How it works

```php
t('nav.home')                              // → "Home" (or "Inicio" if APP_LOCALE=es)
t('todo.count_summary', ['total' => 5])    // → replaces ":total" inside the string
```

- The active language is read from `APP_LOCALE` in `.env` (`en` or `es` by default).
- Keys use dot notation to reach nested sections of the JSON file (`errors.404_title` → `{"errors": {"404_title": "..."}}`).
- If a key is missing in the active locale, `t()` falls back to English; if it's missing there too, it returns the raw key — so a missing translation is visible in the UI instead of breaking the page.
- Dictionaries are loaded and cached once per request (`load_translations()`), so calling `t()` many times in the same page has no extra cost.
- Placeholders use a leading colon (`:name`) and are replaced via the second argument: `t('key', ['name' => 'value'])`.

### Adding a new language

No code changes are required — just add a new dictionary file following the exact same key structure as `lang/en.json`:

```bash
cp lang/en.json lang/fr.json
# translate the values in lang/fr.json
```

Then set `APP_LOCALE=fr` in `.env`. Any key you leave untranslated (or forget to add) automatically falls back to English rather than breaking.

### Language selector

Unlike the base i18n system (`t()`, dictionaries), the switcher **is wired in by default** but stays entirely inert until a `/xx/` URL is actually visited or a switcher link is clicked — it adds no overhead to a project that never uses it.

**How it works:**

- Visiting a URL prefixed with a 2-letter code that has a matching dictionary (`/es/todo`, `/en/about`) sets that language for the session and strips the prefix before normal routing continues — you never need to duplicate routes with a language segment in `routes.php`.
- `ACCEPTED_LANGUAGES` in `.env` (comma-separated, e.g. `en,es`) controls which prefixes are actually enabled. Visiting a prefix whose dictionary exists but isn't listed there returns a 404 — this is a deliberate deployment-level restriction, not a bug: it lets you ship translation files for a language you're still working on without exposing it yet.
- Once a language is chosen (via URL prefix or the flag switcher), it's remembered in the session — the rest of the site's links don't need any prefix.
- If no language was ever selected (no prefix visited, no flag clicked), `APP_LOCALE` in `.env` is the default.
- Flags for English and Spanish are already in `includes/head.php`, using `locale_switch_url()` to preserve the current page when switching.

**Adding a new language to the switcher:** add its dictionary (`lang/fr.json`), add the code to `ACCEPTED_LANGUAGES`, and add a flag link in `includes/head.php` following the existing two as a template.

**Known limitation:** a route's first path segment can't share a name with an enabled language code that also has a matching dictionary file (e.g. avoid a route literally named `/es` for something unrelated to Spanish) — the router treats any 2-letter segment with a matching `lang/xx.json` file as a locale prefix before attempting normal route matching.

If `ACCEPTED_LANGUAGES` isn't set in `.env` at all, the switcher doesn't render and `/xx/` URL prefixes aren't recognized as locale prefixes — the project is treated as single-language, using only `APP_LOCALE`.

## Testing

This section assumes a fresh Ubuntu/Mint/Corvorum machine with nothing installed yet. If you already have PHP and Composer, skip to [Installing PHPUnit](#installing-phpunit).

### Installing PHP from scratch (Ubuntu/Mint/Corvorum)

```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip unzip
```

What each package is for:

| Package | Why it's needed |
|---|---|
| `php8.1` / `php8.1-cli` | The PHP runtime itself and the command-line binary |
| `php8.1-mysql` | PDO driver for MySQL/MariaDB (`Database::connect()`) |
| `php8.1-xml` | Provides `dom` and `xmlwriter`, required by PHPUnit's report generation |
| `php8.1-mbstring` | Multi-byte string handling, required by Composer and most packages |
| `php8.1-curl` | Used by Composer to fetch packages |
| `php8.1-zip` / `unzip` | Composer needs these to unpack downloaded packages |

Verify:

```bash
php -v
php -m | grep -iE "pdo_mysql|dom|xmlwriter|mbstring|curl|zip"
```

If you're on PostgreSQL instead of MySQL, install `php8.1-pgsql` instead of (or alongside) `php8.1-mysql`.

### Installing Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Installing PHPUnit

PHP 8.1 requires PHPUnit 10.x — newer major versions (11+) drop support for it. Initialize `composer.json` first if the project doesn't have one yet:

```bash
composer init --name="your-username/tanuki-base" --type=project --no-interaction
composer require --dev "phpunit/phpunit:^10.5"
```

Verify:

```bash
./vendor/bin/phpunit --version
```

### Test configuration

**`phpunit.xml`** (project root):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>core</directory>
            <directory>models</directory>
            <directory>controllers</directory>
        </include>
    </source>
</phpunit>
```

**`tests/bootstrap.php`** (new file):

```php
<?php

/**
 * Tanuki Framework — PHPUnit bootstrap
 *
 * Loads Composer's autoloader (for PHPUnit itself) plus every core
 * class the test suite needs, since Tanuki's own autoloader only
 * activates once App::run() runs — tests load classes directly instead.
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Controller.php';

// Use a dedicated .env.testing if present, otherwise fall back to .env
$envFile = file_exists(__DIR__ . '/../.env.testing')
    ? __DIR__ . '/../.env.testing'
    : __DIR__ . '/../.env';

if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \t\"'");
    }
}
```

**`.env.testing`** (new file — keeps tests from ever touching your real database):

```ini
APP_NAME="Tanuki Test"
APP_ENV=testing
APP_DEBUG=true
APP_URL=http://localhost
APP_LOCALE=en

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=tanuki_db
DB_USER=your_test_user
DB_PASS=your_test_password
DB_CHARSET=utf8mb4
```

Create the test database and table it points to:

```sql
CREATE DATABASE IF NOT EXISTS tanuki_db;
USE tanuki_db;

CREATE TABLE todo (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    completed   TINYINT(1)  DEFAULT 0,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Update `.gitignore`** to keep `.env.testing` out of version control (it's still a credentials file, even if it points to a throwaway database):

```
.env
.env.testing
vendor/
```

### Writing tests for the TODO CRUD

**`tests/Unit/HelpersTest.php`** (new file — no database needed):

```php
<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/database.php';

final class HelpersTest extends TestCase
{
    public function testEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
    }

    public function testEscapeReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', e(null));
    }

    public function testEnvReturnsDefaultWhenMissing(): void
    {
        $this->assertSame('fallback', env('SOME_UNDEFINED_KEY', 'fallback'));
    }

    public function testEnvReturnsRealValueOfZero(): void
    {
        // Regression test: a falsy-but-real value like "0" must not be
        // treated as missing (env() previously used the Elvis operator).
        $_ENV['ZERO_TEST'] = '0';
        $this->assertSame('0', env('ZERO_TEST', 'should-not-see-this'));
        unset($_ENV['ZERO_TEST']);
    }

    public function testTranslationFallsBackToEnglishWhenKeyMissingInLocale(): void
    {
        $_ENV['APP_LOCALE'] = 'es';
        $this->assertNotSame('nav.home', t('nav.home'));
    }

    public function testTranslationReturnsKeyWhenMissingEverywhere(): void
    {
        $this->assertSame('this.key.does.not.exist', t('this.key.does.not.exist'));
    }

    public function testTranslationReplacesPlaceholders(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $result = t('todo.count_summary', ['total' => 5, 'pending' => 2, 'completed' => 3]);
        $this->assertStringContainsString('5 tasks', $result);
    }
}
```

**`tests/Feature/TodoModelTest.php`** (new file — hits the `tanuki_db` database):

```php
<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/TodoModel.php';

final class TodoModelTest extends TestCase
{
    protected function setUp(): void
    {
        Database::reset();
        Database::connect()->exec('DELETE FROM todo');
    }

    public function testCreateAndFind(): void
    {
        $id = TodoModel::create([
            'title'       => 'Test task',
            'description' => 'Created from PHPUnit',
            'completed'   => 0,
        ]);

        $this->assertIsInt($id);

        $todo = TodoModel::find($id);
        $this->assertNotNull($todo);
        $this->assertSame('Test task', $todo['title']);
        $this->assertSame('0', (string) $todo['completed']);
    }

    public function testAllOrderedPutsPendingFirst(): void
    {
        TodoModel::create(['title' => 'Done one',    'completed' => 1]);
        TodoModel::create(['title' => 'Pending one', 'completed' => 0]);

        $ordered = TodoModel::allOrdered();

        $this->assertSame('Pending one', $ordered[0]['title']);
    }

    public function testUpdateChangesFields(): void
    {
        $id = TodoModel::create(['title' => 'Original', 'completed' => 0]);

        $ok = TodoModel::update($id, ['title' => 'Updated', 'completed' => 1]);

        $this->assertTrue($ok);
        $todo = TodoModel::find($id);
        $this->assertSame('Updated', $todo['title']);
        $this->assertSame('1', (string) $todo['completed']);
    }

    public function testDeleteRemovesRecord(): void
    {
        $id = TodoModel::create(['title' => 'To delete', 'completed' => 0]);

        $ok = TodoModel::delete($id);

        $this->assertTrue($ok);
        $this->assertNull(TodoModel::find($id));
    }

    public function testInvalidColumnNameIsRejected(): void
    {
        // Regression test for the SQL-injection-via-column-name fix in Model.php
        $this->expectException(\InvalidArgumentException::class);
        TodoModel::where('title; DROP TABLE todo;--', 'x');
    }
}
```

### Running the tests

```bash
# Everything
./vendor/bin/phpunit

# Only Unit (no database required)
./vendor/bin/phpunit --testsuite Unit

# Only Feature (requires tanuki_db to exist and be reachable)
./vendor/bin/phpunit --testsuite Feature

# A single file
./vendor/bin/phpunit tests/Feature/TodoModelTest.php
```

### Notes on this test setup

- **Unit vs Feature** are split on purpose: Unit tests never touch a database, so they can run in any environment (including CI without MySQL configured); Feature tests exercise real queries against `tanuki_db`.
- `setUp()` clears the `todo` table before every test for isolation. As the suite grows, consider switching to `beginTransaction()` / `rollBack()` around each test instead — faster, and removes any dependency on test execution order.
- `testInvalidColumnNameIsRejected` and `testEnvReturnsRealValueOfZero` are regression tests tied directly to real bugs found and fixed during development — keep this pattern going: whenever you fix a subtle bug, add a test that would have caught it.

## Docker

Tanuki ships with a full Docker setup: an app container (PHP-FPM), Nginx as a reverse proxy, and a database container (MySQL/MariaDB or PostgreSQL — choose one, see `docker-compose.yml`). Apache is intentionally excluded from this stack; `.htaccess` already covers shared-hosting deployments that use Apache instead.

### Starting the stack

```bash
# Build and start everything (uses docker-compose.override.yml automatically
# if present — installs PHPUnit + Xdebug for local development)
docker compose up -d --build

# View logs
docker compose logs -f app

# Stop everything
docker compose down

# Stop and also delete volumes (wipes the database)
docker compose down -v
```

Once running, the app is reachable at `http://localhost:8050` (the port mapped in `docker-compose.yml`'s `nginx` service — adjust it there if it conflicts with something else on your machine).

### Creating the table inside the container

The `db` container starts with an empty database — you need to create the `todo` table manually the first time, running the SQL client **inside** the container (not on your local machine):

**If using MySQL/MariaDB:**

```bash
docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD:-changeme}" "${DB_NAME:-tanuki_db}"
```

```sql
CREATE TABLE todo (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    completed   TINYINT(1)  DEFAULT 0,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
EXIT;
```

**If using PostgreSQL:**

```bash
docker compose exec db psql -U "${DB_USER:-tanuki}" -d "${DB_NAME:-tanuki_db}"
```

```sql
CREATE TABLE todo (
    id          SERIAL PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    completed   BOOLEAN     DEFAULT false,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
);
\q
```

> PostgreSQL doesn't natively support `TINYINT` or `ON UPDATE CURRENT_TIMESTAMP` — hence the different `SERIAL`/`BOOLEAN` types. Auto-refreshing `updated_at` in PostgreSQL would require a trigger; for now `TodoModel::update()` already updates it manually from PHP thanks to `$timestamps = true`, so the trigger isn't needed for the CRUD to work.

**Alternative: load the SQL from a file instead of typing it each time:**

```bash
docker compose exec -T db mysql -u root -p"${DB_ROOT_PASSWORD:-changeme}" "${DB_NAME:-tanuki_db}" < docker/init.sql
```

If you prefer this, create `docker/init.sql` with the `CREATE TABLE` for your engine, and consider mounting it for automatic initialization by adding this to the `db` service in `docker-compose.yml` (both MySQL/MariaDB and Postgres support this auto-init mechanism, running any `.sql` file present **only the first time** the data volume is empty):

```yaml
  db:
    # ...
    volumes:
      - db_data:/var/lib/mysql
      - ./docker/init.sql:/docker-entrypoint-initdb.d/init.sql:ro
```

(for PostgreSQL, the mount path is the same `/docker-entrypoint-initdb.d/init.sql`, but the data volume is `/var/lib/postgresql/data` instead of `/var/lib/mysql`).

### `.env` for Docker

Set `DB_HOST=db` so the app container reaches the database container by its service name (containers on the same Docker network resolve each other by service name, not `localhost`):

```ini
DB_HOST=db
```

Everything else in `.env` (`DB_NAME`, `DB_USER`, `DB_PASS`, etc.) should match what's set under the `db` service's `environment:` block in `docker-compose.yml`.

### Development vs. production builds

`docker-compose.override.yml` is loaded automatically by Docker Compose whenever it's present alongside `docker-compose.yml` — no extra flag needed for local development. It:

- Builds the app image with `INSTALL_DEV_DEPS=true`, which installs PHPUnit and the Xdebug extension (both skipped in a normal build).
- Sets `APP_DEBUG=true` and configures Xdebug to reach your host machine.

**For a production build, exclude this file** so dev tools never ship in the image:

```bash
docker compose -f docker-compose.yml up -d --build
```

Or simply delete/rename `docker-compose.override.yml` before building your production image.

### Running PHPUnit inside the container

The dev build already has PHPUnit installed via Composer:

```bash
docker compose exec app ./vendor/bin/phpunit
docker compose exec app ./vendor/bin/phpunit --testsuite Unit
docker compose exec app ./vendor/bin/phpunit tests/Feature/TodoModelTest.php
```

If `tests/Feature/*` tests need a real database, make sure `DB_HOST`, `DB_NAME`, etc. in `.env` point at the `db` service (not `tanuki_db` on `localhost`) — either add a second `db_test` service to `docker-compose.override.yml`, or point `.env.testing` at the same `db` service using a separate database name.

### Debugging with Xdebug inside Docker

This differs from the [local Xdebug setup](#local-development-with-xdebug) in one key way: your editor (Antigravity/VS Code) runs on your **host machine**, not inside the container, so Xdebug has to reach *out* of the container to find it.

**1. Already configured by `docker-compose.override.yml`:**

```yaml
environment:
  - XDEBUG_MODE=debug
  - XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003
extra_hosts:
  - "host.docker.internal:host-gateway"
```

`host.docker.internal` is a special DNS name Docker provides that resolves to your host machine's IP from inside a container — this is the piece that replaces `client_host=127.0.0.1` from the local setup, since `127.0.0.1` inside a container points to the container itself, not your host.

**2. `.vscode/launch.json`** — same as the local setup, no changes needed:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

**Unlike local development, `pathMappings` is required here** — the container sees your project at `/var/www/html`, while your editor sees it at your actual project folder on disk. Without this mapping, breakpoints resolve to the wrong path and never trigger (the same failure mode we diagnosed earlier when `pathMappings` had a stale placeholder — here it's necessary and must point to the real container path).

**3. Debug:**

1. Select "Listen for Xdebug" in Run & Debug and press **F5**.
2. Make sure the stack is running: `docker compose up -d`.
3. Set breakpoints and visit `http://localhost:8050`.

**Troubleshooting:** if breakpoints stay `unresolved`, verify `host.docker.internal` resolves correctly from inside the container:

```bash
docker compose exec app getent hosts host.docker.internal
```

If that fails, your Docker version may need `--add-host=host.docker.internal:host-gateway` handled differently — this is already set via `extra_hosts` in `docker-compose.override.yml`, but very old Docker Engine versions on native Linux (without Docker Desktop) sometimes need the host's actual `docker0` bridge IP instead. Find it with `ip addr show docker0` and use that IP directly as `client_host` if `host.docker.internal` doesn't resolve.