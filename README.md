# 🦝 Tanuki Framework

A lightweight PHP framework with a clean MVC architecture. No ORM, no template engine — a skeleton you build on top of, written for developers who like vanilla PHP and want to see exactly what their code does.

**Requirements:** PHP 8.1+ · MySQL/MariaDB or PostgreSQL · Apache or Nginx

## Purpose & who it's for

Most PHP frameworks make you choose between two extremes: something so minimal you rebuild the same plumbing on every project, or something so opinionated (ORMs with hidden queries, template languages to learn, service containers, middleware pipelines) that you spend as much time fighting the framework as building your app.

Tanuki is for developers who like **vanilla PHP** — who want to see exactly what their code does, without magic in between. It gives you the boring, repetitive parts every project needs (routing, a database layer, sessions, CSRF, i18n) as plain, readable code you can open and understand in five minutes, and gets out of your way for everything else.

**You'll feel at home with Tanuki if:**
- You'd rather write raw SQL through PDO than learn an ORM's query builder.
- You want your views to be PHP, not a new templating syntax.
- You'd rather delete a folder you don't need than configure a flag to disable it.
- You're building a real product (with users, forms, and a database) and want auth and an admin panel available — but only when you ask for them.

**This is one repository, not several editions.** Everything beyond the core router/models/views — the example TODO CRUD, authentication (`tanuki_login`), the admin panel (`tanuki_admin`) — ships in the repo but stays completely inert until you register its routes. If you don't need it, delete it:

| Want just the bare minimum? | Delete this |
|---|---|
| No example CRUD | `models/TodoModel.php`, `controllers/TodoController.php`, `views/todo/`, its entries in `routes.php`/`config/nav.php` |
| No login | `auth.php`, `config/mail.php`, `controllers/AuthController.php`, `controllers/ProfileController.php`, `models/UserModel.php`, `models/PasswordResetModel.php`, `views/auth/`, `views/profile/`, its `routes.php` entries |
| No admin panel | the whole `admin/` folder, its `routes.php` entries |

Nothing else in the project references these — they were built deliberately isolated so removing them is safe.

## What you get out of the box

- Simple router with `{param}` capture and PUT/DELETE form support
- Lightweight Active Record (`Model`) — no ORM magic, real SQL underneath
- Plain PHP views, no template language to learn
- A working TODO CRUD as a learning example
- CSRF protection, XSS escaping, prepared statements everywhere
- i18n (`t()` + JSON dictionaries) with an optional URL-based language switcher
- Optional extensions you can add, use, or delete independently: authentication (`tanuki_login`) and an admin panel (`tanuki_admin`)

## Quick install

```bash
git clone <repo-url> my-project
cd my-project
cp .env-example .env
nano .env   # set DB_NAME, DB_USER, DB_PASS, APP_URL
php -S localhost:8050 -t public public/index.php
```

## Documentation

Full documentation lives in the [wiki](../../wiki):

- **[Quickstart](../../wiki/Quickstart)** — install, first steps, your first page
- **[Project Structure](../../wiki/Project-Structure)**
- **[Routing](../../wiki/Routing)** · **[Controllers & Requests](../../wiki/Controllers-and-Requests)** · **[Models](../../wiki/Models)** · **[Views & Layout](../../wiki/Views-and-Layout)**
- **[Global Helpers](../../wiki/Global-Helpers)** · **[Security](../../wiki/Security)**
- **[Optional Connections](../../wiki/Optional-Connections)** (Redis/MongoDB) · **[Internationalization](../../wiki/Internationalization)**
- **[Authentication](../../wiki/Authentication)** (`tanuki_login`) · **[Admin Panel](../../wiki/Admin-Panel)** (`tanuki_admin`)
- **[Testing](../../wiki/Testing)** · **[Docker](../../wiki/Docker)** · **[Xdebug](../../wiki/Xdebug)**
- **[FAQ](../../wiki/FAQ)**

## License

<!-- Add your license here -->