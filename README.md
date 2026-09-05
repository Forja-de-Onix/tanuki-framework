# 🦝 Tanuki Framework

A lightweight PHP framework with a clean MVC architecture. No ORM, no template engine — a skeleton you build on top of, written for developers who like vanilla PHP and want to see exactly what their code does.

**Requirements:** PHP 8.1+ · MySQL/MariaDB or PostgreSQL · Apache or Nginx

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