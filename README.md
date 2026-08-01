# 🦝 Tanuki Framework — Base MVC

A lightweight PHP framework with a clean MVC architecture, database access, and zero external dependencies. No ORM, no template engine — a skeleton you build on top of.

**Requirements:** PHP 8.1+ · MySQL/MariaDB or PostgreSQL · Apache or Nginx

---

## Table of contents

1. [Installation](#installation)
2. [Project structure](#project-structure)
3. [Request lifecycle](#request-lifecycle)
4. [Routes](#routes)
5. [Controllers](#controllers)
6. [The Request class](#the-request-class)
7. [Models](#models)
8. [Views and layout](#views-and-layout)
9. [Global helpers](#global-helpers)
10. [Optional connections: Redis and MongoDB](#optional-connections-redis-and-mongodb)
11. [Walkthrough: the built-in TODO CRUD](#walkthrough-the-built-in-todo-crud)
12. [Adding your own CRUD](#adding-your-own-crud)
13. [Security](#security)
14. [Local development with Xdebug](#local-development-with-xdebug)
15. [FAQ](#faq)

---

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
Add the logic in the controller's constructor, or in a private method called from each protected action. Formal middleware support is planned for **Tanuki Lock**, a dedicated version of the framework built on top of this base.

**Can I use a different database engine (PostgreSQL, SQLite)?**
MySQL and PostgreSQL are supported today via `DB_DRIVER` in `.env`. SQLite isn't wired in yet.

**Can I use the framework without a database?**
Yes. The connection is lazy — if no route ever calls a model, no connection is ever opened.

**Where do assets (CSS, JS) go?**
`public/assets/`. They're served directly by the web server since they live inside the document root. Reference them with `url('/assets/css/app.css')`.

**Will there be a language/i18n system?**
Yes, planned: an `APP_LOCALE` variable (`en`/`es`) plus simple, expandable JSON translation files that views can consume through a helper.