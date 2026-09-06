# 🦝 Tanuki Framework

Framework PHP ligero con arquitectura MVC limpia. Sin ORM, sin motor de plantillas — un esqueleto sobre el que construir, escrito para desarrolladores que aman PHP vanilla y quieren ver exactamente qué hace su código.

**Requisitos:** PHP 8.1+ · MySQL/MariaDB o PostgreSQL · Apache o Nginx

## Propósito y a quién va dirigido

La mayoría de frameworks PHP te hacen elegir entre dos extremos: algo tan mínimo que reconstruyes la misma fontanería en cada proyecto, o algo tan opinado (ORMs con queries ocultas, lenguajes de plantillas que aprender, contenedores de servicios, pipelines de middleware) que acabas peleando con el framework tanto como construyendo tu app.

Tanuki es para desarrolladores que aman **PHP vanilla** — que quieren ver exactamente qué hace su código, sin magia de por medio. Te da las partes aburridas y repetitivas que todo proyecto necesita (rutas, capa de base de datos, sesiones, CSRF, i18n) como código plano y legible que puedes abrir y entender en cinco minutos, y se aparta del camino para todo lo demás.

**Te vas a sentir cómodo con Tanuki si:**
- Prefieres escribir SQL directo con PDO antes que aprender el query builder de un ORM.
- Quieres que tus vistas sean PHP, no una sintaxis de plantillas nueva.
- Prefieres borrar una carpeta que no necesitas antes que configurar un flag para desactivarla.
- Estás construyendo un producto real (con usuarios, formularios y base de datos) y quieres tener login y panel admin disponibles — pero solo cuando los pidas.

**Este es un único repositorio, no varias ediciones.** Todo lo que va más allá del router/modelos/vistas del núcleo — el CRUD de TODO de ejemplo, la autenticación (`tanuki_login`), el panel admin (`tanuki_admin`) — viene en el repo pero permanece completamente inerte hasta que registras sus rutas. Si no lo necesitas, bórralo:

| ¿Quieres solo lo mínimo? | Borra esto |
|---|---|
| Sin CRUD de ejemplo | `models/TodoModel.php`, `controllers/TodoController.php`, `views/todo/`, sus entradas en `routes.php`/`config/nav.php` |
| Sin login | `auth.php`, `config/mail.php`, `controllers/AuthController.php`, `controllers/ProfileController.php`, `models/UserModel.php`, `models/PasswordResetModel.php`, `views/auth/`, `views/profile/`, sus entradas en `routes.php` |
| Sin panel admin | toda la carpeta `admin/`, sus entradas en `routes.php` |

Nada más en el proyecto referencia estas piezas — se construyeron deliberadamente aisladas para que eliminarlas sea seguro.

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

Este proyecto está licenciado bajo la **GNU General Public License v3.0 (GPLv3)** — consulta el archivo [LICENSE](LICENSE) para el texto completo.

En resumen: eres libre de usar, modificar y redistribuir este proyecto, incluso comercialmente, siempre que cualquier trabajo derivado que distribuyas también esté licenciado bajo GPLv3 y su código fuente permanezca disponible. Es una licencia copyleft — no restringe cómo usas Tanuki para construir tus propios proyectos, pero si modificas y redistribuyes el propio Tanuki, esas modificaciones deben seguir siendo abiertas bajo los mismos términos.