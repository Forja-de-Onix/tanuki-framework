# 🦝 Tanuki Framework

Framework PHP ligero con arquitectura MVC limpia. Sin ORM, sin motor de plantillas — un esqueleto sobre el que construir, escrito para desarrolladores que aman PHP vanilla y quieren ver exactamente qué hace su código.

**Requisitos:** PHP 8.1+ · MySQL/MariaDB o PostgreSQL · Apache o Nginx

## Qué incluye de serie

- Router simple con captura de `{param}` y soporte de formularios PUT/DELETE
- Active Record ligero (`Model`) — sin magia de ORM, SQL real debajo
- Vistas en PHP puro, sin lenguaje de plantillas que aprender
- Un CRUD de TODO funcional como ejemplo de aprendizaje
- Protección CSRF, escapado XSS, prepared statements en todas partes
- i18n (`t()` + diccionarios JSON) con selector de idioma opcional basado en URL
- Extensiones opcionales que puedes añadir, usar o eliminar de forma independiente: autenticación (`tanuki_login`) y panel de administración (`tanuki_admin`)

## Instalación rápida

```bash
git clone <repo-url> mi-proyecto
cd mi-proyecto
cp .env-example .env
nano .env   # ajusta DB_NAME, DB_USER, DB_PASS, APP_URL
php -S localhost:8050 -t public public/index.php
```

## Documentación

La documentación completa vive en la [wiki](../../wiki):

- **[Inicio rápido](../../wiki/Inicio-Rapido)** — instalación, primeros pasos, tu primera página
- **[Estructura del proyecto](../../wiki/Estructura-del-Proyecto)**
- **[Rutas](../../wiki/Rutas)** · **[Controladores y peticiones](../../wiki/Controladores-y-Peticiones)** · **[Modelos](../../wiki/Modelos)** · **[Vistas y layout](../../wiki/Vistas-y-Layout)**
- **[Helpers globales](../../wiki/Helpers-Globales)** · **[Seguridad](../../wiki/Seguridad)**
- **[Conexiones opcionales](../../wiki/Conexiones-Opcionales)** (Redis/MongoDB) · **[Internacionalización](../../wiki/Internacionalizacion)**
- **[Autenticación](../../wiki/Autenticacion)** (`tanuki_login`) · **[Panel admin](../../wiki/Panel-Admin)** (`tanuki_admin`)
- **[Testing](../../wiki/Testing-ES)** · **[Docker](../../wiki/Docker-ES)** · **[Xdebug](../../wiki/Xdebug-ES)**
- **[Preguntas frecuentes](../../wiki/Preguntas-Frecuentes)**

## Licencia

<!-- Añade aquí tu licencia -->