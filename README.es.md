# 🦝 Tanuki Framework — Base MVC

Framework PHP ligero con arquitectura MVC limpia, acceso a base de datos y cero dependencias externas. Sin ORM, sin motor de plantillas — un esqueleto sobre el que construir.

**Requisitos:** PHP 8.1+ · MySQL/MariaDB o PostgreSQL · Apache o Nginx

---

## Índice

1. [Prerrequisitos: instalar PHP y una base de datos desde cero (Ubuntu/Mint/Corvorum)](#prerrequisitos-instalar-php-y-una-base-de-datos-desde-cero-ubuntu-mint-corvorum)
2. [Instalación](#instalación)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Ciclo de vida de una petición](#ciclo-de-vida-de-una-petición)
5. [Rutas](#rutas)
6. [Controladores](#controladores)
7. [La clase Request](#la-clase-request)
8. [Modelos](#modelos)
9. [Vistas y layout](#vistas-y-layout)
10. [Helpers globales](#helpers-globales)
11. [Conexiones opcionales: Redis y MongoDB](#conexiones-opcionales-redis-y-mongodb)
12. [Tutorial: el CRUD de TODO incluido](#tutorial-el-crud-de-todo-incluido)
13. [Añadir tu propio CRUD](#añadir-tu-propio-crud)
14. [Seguridad](#seguridad)
15. [Desarrollo local con Xdebug](#desarrollo-local-con-xdebug)
16. [Internacionalización (i18n)](#internacionalización-i18n)
17. [Testing](#testing)
18. [Docker](#docker)
19. [FAQ](#faq)

---

## Prerrequisitos: instalar PHP y una base de datos desde cero (Ubuntu/Mint/Corvorum)

Esta sección asume una máquina Ubuntu/Mint/Corvorum limpia, sin nada instalado. Si ya tienes PHP y tu base de datos configurados, salta directamente a [Instalación](#instalación).

### 1. PHP

```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip unzip
```

Añade el driver de base de datos según el motor que vayas a usar (ver paso 2):

```bash
sudo apt install php8.1-mysql    # para MySQL/MariaDB
# y/o
sudo apt install php8.1-pgsql    # para PostgreSQL
```

Verifica:

```bash
php -v
php -m | grep -iE "pdo_mysql|pdo_pgsql|xml|mbstring"
```

### 2. Base de datos — elige MySQL/MariaDB, PostgreSQL, o ambos

Tanuki soporta los dos mediante `DB_DRIVER` en `.env` — solo necesitas instalar el que realmente vayas a usar.

#### Opción A — MySQL/MariaDB

```bash
sudo apt install mariadb-server
sudo systemctl enable --now mariadb
sudo mysql_secure_installation
```

`mysql_secure_installation` te guía para poner contraseña de root, eliminar usuarios anónimos y desactivar el login remoto de root — responde "sí" a todo para una instalación local normal.

Crea la base de datos de la app y un usuario dedicado (nunca uses `root` en `.env`):

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE mi_base CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mi_usuario_app'@'localhost' IDENTIFIED BY 'una_contraseña_fuerte';
GRANT ALL PRIVILEGES ON mi_base.* TO 'mi_usuario_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Estos valores van directamente al `.env` como `DB_NAME`, `DB_USER`, `DB_PASS` — ver [Instalación](#instalación).

#### Opción B — PostgreSQL

```bash
sudo apt install postgresql postgresql-contrib
sudo systemctl enable --now postgresql
```

Crea la base de datos de la app y un usuario dedicado:

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE mi_base;
CREATE USER mi_usuario_app WITH ENCRYPTED PASSWORD 'una_contraseña_fuerte';
GRANT ALL PRIVILEGES ON DATABASE mi_base TO mi_usuario_app;
\q
```

Pon `DB_DRIVER=pgsql` en `.env` si usas esta opción — ver [Instalación](#instalación).

### 3. El resto se instala sobre la marcha

Composer, PHPUnit, Redis, MongoDB, Docker y Xdebug tienen cada uno sus propios pasos de instalación en sus secciones correspondientes más abajo ([Testing](#testing), [Conexiones opcionales](#conexiones-opcionales-redis-y-mongodb), [Desarrollo local con Xdebug](#desarrollo-local-con-xdebug)) — instálalos solo cuando realmente los necesites.

## Instalación

```bash
# 1. Clona el repositorio
git clone <repo-url> mi-proyecto
cd mi-proyecto

# 2. Copia el archivo de entorno y configúralo
cp .env-example .env
nano .env   # Ajusta DB_NAME, DB_USER, DB_PASS, APP_URL

# 3. Apunta el VirtualHost de Apache/Nginx a la carpeta public/ (ver abajo)
```

### Configuración del entorno (`.env`)

De la raiz del proyecto copiamos .env-example con el nombre .env y actualizamos todos los datos en base a nuestra configuración:

```ini
APP_NAME="Mi App"
APP_ENV=development     # development | production
APP_DEBUG=true          # true = muestra errores detallados
APP_URL=http://localhost

DB_DRIVER=mysql          # mysql | pgsql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mi_base
DB_USER=root
DB_PASS=mi_password
DB_CHARSET=utf8mb4

# Opcional
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

MONGO_URI=mongodb://127.0.0.1:27017
MONGO_DATABASE=
```

> **Importante:** `.env` está en `.gitignore` y nunca debe subirse al repositorio.

### Servidor de desarrollo local

```bash
php -S localhost:8050 -t public public/index.php
```

`-t public` le indica al servidor integrado de PHP que la raíz de documentos es `public/` — la misma carpeta a la que apuntará Nginx/Apache en producción.

---

## Estructura del proyecto

```
tanuki_base/
│
├── public/ ← ÚNICA carpeta expuesta a la web
│ ├── index.php ← Punto de entrada (solo llama a App::run())
│ ├── .htaccess ← Reglas de rewrite para Apache
│ └── assets/
│ ├── css/app.css
│ └── js/app.js
│
├── routes.php ← Definición de todas las rutas
├── utils.php ← Helpers globales: e(), view(), env(), redirect()…
│
├── .env ← Variables de entorno locales (NO en git)
├── .env-example ← Plantilla documentada
├── nginx.config ← Referencia de bloque de servidor Nginx
│
├── config/
│ ├── database.php ← Clase Database (Singleton PDO, mysql/pgsql)
│ ├── redis.php ← Clase Redis (opcional, phpredis nativo)
│ ├── mongo.php ← Clase Mongo (opcional, mongodb/mongodb)
│ └── nav.php ← Entradas del menú de navegación
│
├── core/
│ ├── App.php ← Bootstrap: carga de env, autoloader, router
│ ├── Controller.php ← Clase base de controladores
│ ├── Model.php ← Base ligera tipo Active Record
│ └── Request.php ← Encapsulado de la petición HTTP
│
├── controllers/ ← Tus controladores (extienden Controller)
├── models/ ← Tus modelos (extienden Model)
├── views/ ← Plantillas PHP puras
│ └── errors/ ← Vistas de error 404 / 500 / 503
└── includes/
├── head.php ← HTML head + nav + apertura de <main>
└── footer.php ← Cierre de </main> + footer + app.js
```

### Por qué `public/` es la única carpeta expuesta

Todo excepto `public/` queda fuera de la raíz de documentos del servidor web. Esto significa que `core/`, `controllers/`, `models/` y `config/` nunca pueden pedirse directamente por URL, sin importar cómo esté configurado el servidor — las clases PHP del framework simplemente no son alcanzables desde fuera. Es el mismo patrón que usan Laravel, Symfony y la mayoría de frameworks PHP modernos.

---

## Ciclo de vida de una petición

```
Navegador → public/index.php → App::run()
│
├── loadEnv() Lee .env → $_ENV
├── registerAutoloader() Busca clases en core/, controllers/, models/
├── configureErrors() Debug on/off según APP_DEBUG
└── dispatch()
│
├── Detecta método HTTP (+ override _method para PUT/DELETE)
├── Normaliza la URI
├── Encuentra ruta exacta o patrón {param}
└── Instancia el Controller → llama método($params)
│
└── $this->view('nombre', $data)
│
├── extract($data)
├── Captura la vista en un buffer
├── head.php (usa $title, $uri)
├── echo $content
└── footer.php
```

---

## Rutas

### Formato

```php
// routes.php
return [
    'GET  /ruta'        => 'MiController@metodo',
    'POST /ruta'        => 'MiController@store',
    'PUT  /ruta/{id}'   => 'MiController@update',
    'DELETE /ruta/{id}' => 'MiController@destroy',
];
```

### Parámetros de ruta

Los segmentos entre `{nombre}` se capturan y se pasan como argumentos al método:

```php
// routes.php
'GET /articulos/{slug}' => 'ArticleController@show',

// ArticleController.php
public function show(string $slug): void { /* ... */ }
```

**El orden importa.** Las rutas literales más específicas deben ir antes que los patrones dinámicos que puedan coincidir con la misma URL:

```php
'GET /todo/create'  => 'TodoController@create', // debe ir primero
'GET /todo/{id}'     => 'TodoController@show',   // si no, esto captura "create" como id
```

### Method override (PUT/DELETE desde formularios HTML)

Los navegadores solo envían GET y POST de forma nativa. Para usar PUT/DELETE desde un formulario HTML normal:

```html
<form method="POST" action="/todo/42">
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit">Eliminar</button>
</form>
```

---

## Controladores

Cada controlador extiende `Controller` y obtiene acceso a `$this->request`, además de un conjunto de helpers de respuesta.

```php
<?php

class ArticleController extends Controller
{
    public function index(): void
    {
        $this->view('article/index', [
            'title'    => 'Artículos',
            'articles' => ArticleModel::all(),
        ]);
    }

    public function store(): void
    {
        $title = $this->request->post('title');

        if (empty($title)) {
            $this->flash('error', 'El título es obligatorio.');
            $this->redirect('/article/create');
        }

        ArticleModel::create(['title' => $title]);
        $this->flash('success', 'Artículo creado.');
        $this->redirect('/article');
    }
}
```

### Métodos disponibles en `Controller`

| Método | Descripción |
|---|---|
| `$this->view($nombre, $data)` | Renderiza una vista dentro del layout |
| `$this->redirect($path)` | Redirige y detiene la ejecución |
| `$this->json($data, $status)` | Devuelve JSON y detiene la ejecución |
| `$this->abort404()` | Muestra la página 404 y detiene la ejecución |
| `$this->flash($key, $mensaje)` | Guarda un mensaje flash en sesión |
| `$this->request` | Instancia actual de `Request` |

---

## La clase Request

```php
$this->request->post('campo')        // Valor de $_POST
$this->request->get('param')         // Valor de $_GET
$this->request->input('campo')       // POST primero, si no GET
$this->request->all()                // Todos los inputs fusionados (POST prevalece)
$this->request->only(['a', 'b'])     // Solo esos campos
$this->request->except(['token'])    // Todos excepto esos campos
$this->request->has('campo')         // bool: ¿existe y no está vacío?
$this->request->file('avatar')       // Entrada de $_FILES, o null
$this->request->method()             // 'GET', 'POST', 'PUT'…
$this->request->isGet() / isPost() / isPut() / isDelete()
$this->request->isAjax()             // bool
$this->request->ip()                 // IP del cliente (ver notas de seguridad abajo)
$this->request->uri()                // Path actual, sin query string
```

---

## Modelos

Cada modelo extiende `Model` y declara el nombre de su tabla:

```php
<?php

class ArticleModel extends Model
{
    protected static string $table = 'articles';
    protected static bool $timestamps = true; // auto created_at / updated_at
}
```

> **Convención de nombres:** las clases y archivos de modelos usan el sufijo `XxxModel` (por ejemplo `TodoModel.php` → `class TodoModel`) para evitar ambigüedad con el nombre de la tabla u otras clases. El autoloader busca el archivo por el nombre exacto de la clase, así que renombrar uno siempre exige renombrar el otro.

### API del Model

```php
// ── Lectura ──────────────────────────────────────────────────────────
ArticleModel::all()                        // Todos los registros
ArticleModel::all('title', 'DESC')         // Con orden personalizado
ArticleModel::find(5)                      // Por ID → array | null
ArticleModel::where('active', 1)           // Filtro simple
ArticleModel::where('views', '>', 100)     // Con operador (=, !=, <, >, <=, >=, LIKE)
ArticleModel::first()                      // Primer registro
ArticleModel::count()                      // Total de registros

// Paginación
$result = ArticleModel::paginate(page: 1, perPage: 10);
// $result['data']    → registros de esta página
// $result['total']   → total de registros en la tabla
// $result['pages']   → número total de páginas
// $result['current'] → página actual

// ── Escritura ────────────────────────────────────────────────────────
$id = ArticleModel::create(['title' => 'Hola']);    // → int (ID insertado)
ArticleModel::update(5, ['title' => 'Actualizado']); // → bool
ArticleModel::delete(5);                             // → bool
```

> Si `$timestamps = true`, `create()` rellena `created_at`/`updated_at` automáticamente, y `update()` refresca `updated_at`.

> Los identificadores de columna y tabla se validan y escapan automáticamente (backticks para MySQL, comillas dobles para PostgreSQL según `DB_DRIVER`) — nunca necesitas escaparlos tú mismo, y los nombres de columna dinámicos se rechazan si no son identificadores simples.

---

## Vistas y layout

Las vistas son archivos PHP puros bajo `views/`. Siempre se renderizan dentro del layout compartido (`includes/head.php` + contenido de la vista + `includes/footer.php`).

```php
// Controlador
$this->view('article/show', [
    'title'   => 'Detalle del artículo',
    'article' => $article,
]);
```

```php
<!-- views/article/show.php -->
<h1><?= e($article['title']) ?></h1>
<p><?= e($article['body']) ?></p>
```

**Regla de oro:** usa siempre `e()` para imprimir datos. Nunca `echo $var` directamente — así es como se cuela el XSS.

### Qué está disponible dentro de cada vista

- `$uri` — el path actual normalizado, inyectado automáticamente por `view()`. Lo usa `head.php` para resaltar el ítem de navegación activo.
- Cualquier clave que pases en el array `$data` a `view()` o `$this->view()`.
- Todos los helpers globales (`e()`, `url()`, `old()`, `flash()`, etc.), al ser funciones normales.

### Estilos y assets

Todos los estilos compartidos viven en `public/assets/css/app.css`, cargado una sola vez desde `head.php`. Evita atributos `style="..."` inline en vistas nuevas — añade una clase a `app.css` en su lugar, para que todo el proyecto se mantenga visualmente coherente y personalizable desde un solo archivo.

`public/assets/js/app.js` por ahora solo hace desaparecer los mensajes flash automáticamente a los 5 segundos. Añade nuevo comportamiento interactivo ahí, en vez de bloques `<script>` inline en las vistas.

---

## Helpers globales (`utils.php`)

| Función | Descripción |
|---|---|
| `e($value)` | Escapa HTML para prevenir XSS |
| `env($key, $default)` | Lee una variable del `.env` |
| `view($name, $data)` | Renderiza una vista dentro del layout |
| `url($path)` | URL absoluta: `url('/about')` → `http://localhost:8050/about` |
| `redirect($path)` | Redirige y detiene la ejecución |
| `current_uri()` | Path actual normalizado (única fuente de verdad para router + vistas) |
| `session_ensure()` | Inicia la sesión si no está ya iniciada |
| `flash($key)` | Lee y elimina un mensaje flash |
| `old($key, $default)` | Recupera un valor de input anterior (para re-poblar formularios) |
| `format_date($datetime)` | Formatea una fecha según `APP_LOCALE` (`en` → m/d/Y, `es` → d/m/Y) |
| `t($clave, $replace = [])` | Traduce una clave en notación de puntos (ver [Internacionalización](#internacionalización)) |
| `format_date($datetime)` | Formatea una fecha según `APP_LOCALE` (`en` → m/d/Y, `es` → d/m/Y) |


---

## Conexiones opcionales: Redis y MongoDB

Tanuki incluye tres clases de conexión independientes y de carga perezosa — `Database`, `Redis` y `Mongo`. No comparten interfaz a propósito: cada una expone directamente la API nativa de su driver, para que el framework nunca crezca hacia un ORM/ODM oculto. Usa la que necesite tu proyecto; las que no uses nunca se conectan.

```php
// Relacional (PDO) — ver la sección Modelos arriba para la API completa
Database::connect();

// Redis (requiere la extensión nativa php-redis)
Redis::connect()->set('key', 'value');
Redis::connect()->get('key');
Redis::connect()->expire('key', 3600);

// MongoDB (requiere la extensión mongodb + composer require mongodb/mongodb)
Mongo::connect()->selectCollection('posts')->find(['status' => 'published']);
Mongo::connect()->selectCollection('posts')->insertOne(['title' => 'Hola']);
```

### Instalar los drivers

```bash
# Redis
sudo apt install php-redis

# MongoDB
sudo apt install php-mongodb
composer require mongodb/mongodb
```

Ambos son totalmente opcionales — un proyecto que nunca llama a `Redis::connect()` o `Mongo::connect()` nunca abre esas conexiones.

---

## Tutorial: el CRUD de TODO incluido

La funcionalidad `todo` (modelo, controlador, rutas y vistas) viene como ejemplo completo y funcional — es la forma más rápida de ver todas las piezas del framework trabajando juntas. Está pensada como **referencia de aprendizaje**, no como parte permanente de tu app: una vez te sientas cómodo con el patrón, elimínala y construye tus propios recursos de la misma forma.

### Paso 1 — Crea la tabla

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

### Paso 2 — El modelo (`models/TodoModel.php`)

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

### Paso 3 — Las rutas (`routes.php`)

```php
'GET    /todo'           => 'TodoController@index',
'GET    /todo/create'    => 'TodoController@create',
'POST   /todo'           => 'TodoController@store',
'GET    /todo/{id}'      => 'TodoController@show',
'GET    /todo/{id}/edit' => 'TodoController@edit',
'PUT    /todo/{id}'      => 'TodoController@update',
'DELETE /todo/{id}'      => 'TodoController@destroy',
```

### Paso 4 — El controlador (`controllers/TodoController.php`)

Consulta `controllers/TodoController.php` en el repo para la implementación completa — cubre listado, creación, detalle, edición, actualización y borrado, incluyendo mensajes flash y validación.

### Paso 5 — Las vistas (`views/todo/`)

- `index.php` — listado en tabla con acciones inline de "completar/reabrir" y "eliminar"
- `create.php` — formulario de creación, repuebla campos con `old()` si falla la validación
- `edit.php` — formulario de edición + una sección de "zona de peligro" con confirmación JS para borrar
- `show.php` — vista de detalle de un solo registro con acciones rápidas de toggle/borrado

### Paso 6 — Listo

No hay paso 6. El autoloader encuentra `TodoModel.php` y `TodoController.php` automáticamente por el nombre de la clase. Solo visita `/todo` en el navegador.

### Notas sobre el tutorial actualizado

Desde la última revisión, el CRUD de ejemplo demuestra dos patrones adicionales de seguridad que puedes reutilizar en tus propios recursos:

- **Repoblado de formularios (`old()` + `keep_old()`):** cuando `store()`/`update()` fallan la validación (título vacío), el controlador llama a `keep_old(['title' => ..., 'description' => ...])` antes de redirigir. `view()` limpia automáticamente ese valor tras el siguiente render, así que solo sobrevive a un único formulario. `create.php` ya lo consume con `old('title')`/`old('description')` — no hace falta ningún cambio adicional en la vista.
- **Protección CSRF (`csrf_field()` + `csrf_verify()`):** cada `<form>` del CRUD incluye ahora `<?= csrf_field() ?>`, y cada acción del controlador que muta datos (`store()`, `update()`, `destroy()`) empieza verificando `csrf_verify($this->request->post('_token'))` antes de tocar la base de datos. Ver la sección [Seguridad](#seguridad) para el detalle completo de estos dos helpers y por qué no vienen forzados por defecto en el router.

---

## Añadir tu propio CRUD

1. **Tabla SQL** en tu base de datos.
2. **`models/TuModelo.php`** → extiende `Model`, define `$table`.
3. **Rutas** en `routes.php`.
4. **`controllers/TuController.php`** → extiende `Controller`.
5. **Vistas** en `views/tu-recurso/` (un archivo por método del controlador: `index.php`, `show.php`, etc.).

---

## Seguridad

| Riesgo | Mitigación |
|---|---|
| **XSS** | Usar `e()` en todas las vistas |
| **Inyección SQL** | Todos los métodos de `Model` usan prepared statements; los nombres de columna/tabla se validan contra un patrón estricto de identificador |
| **Exposición de credenciales** | Las credenciales solo viven en `.env` (excluido de git) |
| **Exposición de errores** | `APP_DEBUG=false` en producción |
| **Exposición del código fuente** | Solo `public/` es alcanzable por el servidor web; `core/`, `controllers/`, `models/`, `config/` quedan fuera de la raíz de documentos |
| **Listado de directorios** | `Options -Indexes` en `public/.htaccess` |
| **Archivos sensibles (Nginx)** | `deny all` para `.env`, `.git`, `.htaccess` |
| **IP de cliente falsificada** | `Request::ip()` confía en `X-Forwarded-For`, que cualquier cliente puede establecer — solo confía en ella detrás de un proxy inverso de confianza que sobrescriba esa cabecera |

---

## Desarrollo local con Xdebug

### 1. Instalar Xdebug

```bash
sudo apt install php-xdebug
sudo phpenmod xdebug
```

Verifica que se cargó:

```bash
php -v
# Debe mostrar: with Xdebug v3.x.x, ...
```

### 2. Configurar `xdebug.ini`

Localiza el archivo de configuración:

```bash
php --ini
```

Edita el ini de Xdebug (normalmente en `/etc/php/8.1/mods-available/xdebug.ini`) y añade:

```ini
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

> `xdebug.mode=debug` activa el step debugging (con `develop` únicamente NO funciona).
> `xdebug.start_with_request=yes` hace que Xdebug intente conectar en cada petición sin necesidad de un trigger manual.

Verifica que aplicó:

```bash
php -i | grep -i "xdebug.mode\|xdebug.start_with_request"
```

### 3. Extensión del editor (Antigravity / VS Code)

Instala la extensión **PHP Debug** (`xdebug.php-debug`) desde el marketplace.

### 4. Crear `launch.json`

En la vista de Run & Debug, crea `.vscode/launch.json` en la raíz del proyecto:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Escuchar Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003
        }
    ]
}
```

> **No añadas `pathMappings`** en desarrollo puramente local (editor y servidor en la misma máquina) — un valor incorrecto o de ejemplo ahí hace que los breakpoints queden `unresolved` y nunca se disparen.

### 5. Depurar

1. Selecciona "Escuchar Xdebug" en Run & Debug y pulsa **F5**.
2. Arranca el servidor (desde la raíz del proyecto, no desde `public/`):
```bash
   php -S localhost:8050 -t public public/index.php
```
3. Pon breakpoints y visita `http://localhost:8050`.

### Solución de problemas

- Comprueba que algo escucha en el puerto: `sudo ss -ltnp | grep 9003`
- Activa el log de Xdebug temporalmente para diagnosticar:
```ini
  xdebug.log=/tmp/xdebug.log
  xdebug.log_level=7
```
  Luego revisa `cat /tmp/xdebug.log` tras hacer una petición; busca si los `breakpoint_set` quedan `resolved` o `unresolved`.

---

## FAQ

**¿Cómo añado autenticación?**
Añade la lógica en el constructor del controlador, o en un método privado que llames desde cada acción protegida. El soporte formal de middleware está planeado para **Tanuki Lock**, una versión dedicada del framework construida sobre esta base.

**¿Puedo usar otro motor de base de datos (PostgreSQL, SQLite)?**
MySQL y PostgreSQL ya están soportados hoy vía `DB_DRIVER` en `.env`. SQLite todavía no está integrado.

**¿Puedo usar el framework sin base de datos?**
Sí. La conexión es perezosa — si ninguna ruta llama nunca a un modelo, nunca se abre ninguna conexión.

**¿Dónde van los assets (CSS, JS)?**
En `public/assets/`. Se sirven directamente por el servidor web al estar dentro de la raíz de documentos. Referéncialos con `url('/assets/css/app.css')`.

**¿Habrá un sistema de idiomas/i18n?**
Sí, está planeado: una variable `APP_LOCALE` (`en`/`es`) más archivos de traducción JSON simples y expandibles que las vistas puedan consumir a través de un helper.

## Internacionalización (i18n)

Tanuki incluye un sistema de traducciones mínimo basado en archivos: un helper global `t()` más un diccionario JSON por idioma en `lang/`.

```
lang/
├── en.json
└── es.json
```

### Cómo funciona

```php
t('nav.home')                              // → "Inicio" (o "Home" si APP_LOCALE=en)
t('todo.count_summary', ['total' => 5])    // → sustituye ":total" dentro del string
```

- El idioma activo se lee de `APP_LOCALE` en `.env` (`en` o `es` por defecto).
- Las claves usan notación de puntos para llegar a secciones anidadas del JSON (`errors.404_title` → `{"errors": {"404_title": "..."}}`).
- Si una clave falta en el idioma activo, `t()` cae al inglés; si tampoco existe ahí, devuelve la clave tal cual — así una traducción faltante se ve en la interfaz en vez de romper la página.
- Los diccionarios se cargan y cachean una vez por petición (`load_translations()`), así que llamar a `t()` muchas veces en la misma página no tiene coste extra.
- Los placeholders usan dos puntos delante (`:nombre`) y se sustituyen con el segundo argumento: `t('clave', ['nombre' => 'valor'])`.

### Añadir un nuevo idioma

No hace falta tocar código — solo añade un nuevo archivo de diccionario siguiendo exactamente la misma estructura de claves que `lang/en.json`:

```bash
cp lang/en.json lang/fr.json
# traduce los valores en lang/fr.json
```

Luego pon `APP_LOCALE=fr` en `.env`. Cualquier clave que dejes sin traducir (o que olvides añadir) cae automáticamente al inglés en vez de romper nada.

### Selector de idiomas

La versión base (`tanuki_base`) no tiene interfaz, así que no hay nada donde integrar un selector por defecto — `APP_LOCALE` es una configuración fija por despliegue, que se cambia editando `.env`. Un selector de idioma en tiempo real (que el visitante elija su idioma desde el navegador) está pensado para versiones del framework que sí incluyen interfaz real, como **Tanuki Pro**.

Si tu proyecto necesita un selector antes de eso, aquí tienes el patrón a seguir — no viene conectado por defecto, pero encaja limpiamente sobre lo que ya hay:

**1. Guarda el idioma elegido en sesión, no solo en `.env`.**

Añade un pequeño helper a `utils.php`:

```php
/**
 * Devuelve el idioma activo: override de sesión si existe, si no APP_LOCALE.
 */
function current_locale(): string
{
    session_ensure();
    return $_SESSION['locale'] ?? env('APP_LOCALE', 'en');
}
```

Y actualiza la primera línea de `t()` para que use esto en vez de leer `env()` directamente:

```php
$locale = current_locale();
```

**2. Añade una ruta + acción de controlador para cambiar de idioma.**

```php
// routes.php
'GET /locale/{lang}' => 'LocaleController@switch',
```

```php
<?php

class LocaleController extends Controller
{
    private const SUPPORTED = ['en', 'es'];

    public function switch(string $lang): void
    {
        if (in_array($lang, self::SUPPORTED, true)) {
            session_ensure();
            $_SESSION['locale'] = $lang;
        }
        $this->redirect($this->request->post('return_to') ?? '/');
    }
}
```

**3. Añade los enlaces del selector en `includes/head.php`.**

```php
<a href="/locale/en">EN</a> | <a href="/locale/es">ES</a>
```

**4. (Opcional, avanzado) Idioma con prefijo en la URL en vez de sesión.**

Algunos proyectos prefieren que el idioma sea visible en la propia URL (`/en/todo`, `/es/todo`) en vez de guardarse en sesión — útil para SEO, ya que los buscadores indexan cada idioma como una URL separada. Esto requiere un pequeño cambio en el router en vez de un controlador nuevo:

- Prefija cada ruta de `routes.php` con un segmento `{lang}`, o genera el array de rutas de forma programática recorriendo los idiomas soportados y anteponiendo `/{lang}` a cada path.
- En `App::dispatch()`, antes de comparar contra `self::$routes`, quita el segmento inicial `/en` o `/es` de `$uri`, valídalo contra una lista de idiomas soportados, y guárdalo (por ejemplo en una propiedad estática o en sesión) antes de seguir con el resto de la URI como hasta ahora.
- Cada enlace interno (`url()`, `redirect()`, hrefs en vistas) necesitará entonces el prefijo del idioma actual automáticamente — lo más limpio es hacer que `url()` anteponga `/{locale}` cuando el enrutado con prefijo esté activado, controlado por un nuevo flag en `.env` (por ejemplo `APP_LOCALE_IN_URL=true`).

Esto es una decisión de arquitectura deliberada (con prefijo en URL vs basado en sesión), no una funcionalidad "enchufa y listo", ya que cambia cómo se genera cada ruta y cada enlace interno — planéalo antes de conectarlo a un proyecto con muchas rutas ya existentes.

## Testing

Esta sección asume una máquina Ubuntu/Mint/Corvorum limpia, sin nada instalado todavía. Si ya tienes PHP y Composer, salta directamente a [Instalar PHPUnit](#instalar-phpunit).

### Instalar PHP desde cero (Ubuntu/Mint/Corvorum)

```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip unzip
```

Para qué sirve cada paquete:

| Paquete | Por qué hace falta |
|---|---|
| `php8.1` / `php8.1-cli` | El propio runtime de PHP y el binario de línea de comandos |
| `php8.1-mysql` | Driver PDO para MySQL/MariaDB (`Database::connect()`) |
| `php8.1-xml` | Provee `dom` y `xmlwriter`, necesarios para la generación de reportes de PHPUnit |
| `php8.1-mbstring` | Manejo de strings multibyte, necesario para Composer y la mayoría de paquetes |
| `php8.1-curl` | Lo usa Composer para descargar paquetes |
| `php8.1-zip` / `unzip` | Composer los necesita para descomprimir los paquetes descargados |

Verifica:

```bash
php -v
php -m | grep -iE "pdo_mysql|dom|xmlwriter|mbstring|curl|zip"
```

Si usas PostgreSQL en vez de MySQL, instala `php8.1-pgsql` en lugar de (o junto a) `php8.1-mysql`.

### Instalar Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Instalar PHPUnit

PHP 8.1 requiere PHPUnit 10.x — las versiones mayores más recientes (11+) dejan de soportarlo. Inicializa `composer.json` primero si el proyecto todavía no tiene uno:

```bash
composer init --name="tu-usuario/tanuki-base" --type=project --no-interaction
composer require --dev "phpunit/phpunit:^10.5"
```

Verifica:

```bash
./vendor/bin/phpunit --version
```

### Configuración de tests

**`phpunit.xml`** (raíz del proyecto):

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

**`tests/bootstrap.php`** (archivo nuevo):

```php
<?php

/**
 * Tanuki Framework — Bootstrap de PHPUnit
 *
 * Carga el autoloader de Composer (para PHPUnit mismo) más cada clase
 * del core que necesita la suite de tests, ya que el autoloader propio
 * de Tanuki solo se activa cuando corre App::run() — los tests cargan
 * las clases directamente en su lugar.
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Controller.php';

// Usa un .env.testing dedicado si existe, si no cae al .env normal
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

**`.env.testing`** (archivo nuevo — evita que los tests toquen tu base de datos real):

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
DB_USER=tu_usuario_test
DB_PASS=tu_password_test
DB_CHARSET=utf8mb4
```

Crea la base de datos de test y la tabla a la que apunta:

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

**Actualiza `.gitignore`** para mantener `.env.testing` fuera del control de versiones (sigue siendo un archivo de credenciales, aunque apunte a una base de datos desechable):

```
.env
.env.testing
vendor/
```

### Escribir tests para el CRUD de TODO

**`tests/Unit/HelpersTest.php`** (archivo nuevo — no necesita base de datos):

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
        // Test de regresión: un valor falsy-pero-real como "0" no debe
        // tratarse como ausente (env() usaba antes el operador Elvis).
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

**`tests/Feature/TodoModelTest.php`** (archivo nuevo — usa la base de datos `tanuki_db`):

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
            'title'       => 'Tarea de prueba',
            'description' => 'Creada desde PHPUnit',
            'completed'   => 0,
        ]);

        $this->assertIsInt($id);

        $todo = TodoModel::find($id);
        $this->assertNotNull($todo);
        $this->assertSame('Tarea de prueba', $todo['title']);
        $this->assertSame('0', (string) $todo['completed']);
    }

    public function testAllOrderedPutsPendingFirst(): void
    {
        TodoModel::create(['title' => 'Completada', 'completed' => 1]);
        TodoModel::create(['title' => 'Pendiente',  'completed' => 0]);

        $ordered = TodoModel::allOrdered();

        $this->assertSame('Pendiente', $ordered[0]['title']);
    }

    public function testUpdateChangesFields(): void
    {
        $id = TodoModel::create(['title' => 'Original', 'completed' => 0]);

        $ok = TodoModel::update($id, ['title' => 'Actualizada', 'completed' => 1]);

        $this->assertTrue($ok);
        $todo = TodoModel::find($id);
        $this->assertSame('Actualizada', $todo['title']);
        $this->assertSame('1', (string) $todo['completed']);
    }

    public function testDeleteRemovesRecord(): void
    {
        $id = TodoModel::create(['title' => 'Para eliminar', 'completed' => 0]);

        $ok = TodoModel::delete($id);

        $this->assertTrue($ok);
        $this->assertNull(TodoModel::find($id));
    }

    public function testInvalidColumnNameIsRejected(): void
    {
        // Test de regresión del fix de inyección SQL vía nombre de columna en Model.php
        $this->expectException(\InvalidArgumentException::class);
        TodoModel::where('title; DROP TABLE todo;--', 'x');
    }
}
```

### Ejecutar los tests

```bash
# Todo
./vendor/bin/phpunit

# Solo Unit (no requiere base de datos)
./vendor/bin/phpunit --testsuite Unit

# Solo Feature (requiere que tanuki_db exista y sea accesible)
./vendor/bin/phpunit --testsuite Feature

# Un archivo concreto
./vendor/bin/phpunit tests/Feature/TodoModelTest.php
```

### Notas sobre esta configuración de tests

- **Unit vs Feature** están separados a propósito: los Unit nunca tocan base de datos, así que pueden correr en cualquier entorno (incluido CI sin MySQL configurado); los Feature ejecutan queries reales contra `tanuki_db`.
- `setUp()` vacía la tabla `todo` antes de cada test para aislarlos. Según crezca la suite, valdría la pena migrar a `beginTransaction()` / `rollBack()` alrededor de cada test — más rápido, y elimina cualquier dependencia del orden de ejecución.
- `testInvalidColumnNameIsRejected` y `testEnvReturnsRealValueOfZero` son tests de regresión atados directamente a bugs reales encontrados y corregidos durante el desarrollo — mantén este patrón: cada vez que arregles un bug sutil, añade un test que lo hubiera detectado.

## Docker

Tanuki incluye una configuración completa de Docker: un contenedor de app (PHP-FPM), Nginx como proxy inverso, y un contenedor de base de datos (MySQL/MariaDB o PostgreSQL — elige uno, ver `docker-compose.yml`). Apache queda excluido a propósito de este stack; `.htaccess` ya cubre los despliegues en hosting compartido que usan Apache en su lugar.

### Arrancar el stack

```bash
# Construye y arranca todo (usa docker-compose.override.yml automáticamente
# si está presente — instala PHPUnit + Xdebug para desarrollo local)
docker compose up -d --build

# Ver logs
docker compose logs -f app

# Parar todo
docker compose down

# Parar y borrar también los volúmenes (borra la base de datos)
docker compose down -v
```

Una vez arrancado, la app está disponible en `http://localhost:8050` (el puerto mapeado en el servicio `nginx` de `docker-compose.yml` — ajústalo ahí si entra en conflicto con algo más en tu máquina).

### Crear la tabla dentro del contenedor

El contenedor `db` arranca con la base de datos vacía — hay que crear la tabla `todo` manualmente la primera vez, ejecutando el cliente SQL **dentro** del contenedor (no en tu máquina local):

**Si usas MySQL/MariaDB:**

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

**Si usas PostgreSQL:**

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

> PostgreSQL no soporta `TINYINT` ni `ON UPDATE CURRENT_TIMESTAMP` de forma nativa — de ahí los tipos `SERIAL`/`BOOLEAN` distintos. El refresco automático de `updated_at` en PostgreSQL requeriría un trigger; por ahora, `TodoModel::update()` ya lo actualiza manualmente desde PHP gracias a `$timestamps = true`, así que no hace falta el trigger para que el CRUD funcione.

**Alternativa: cargar el SQL desde un archivo, sin escribirlo a mano cada vez:**

```bash
docker compose exec -T db mysql -u root -p"${DB_ROOT_PASSWORD:-changeme}" "${DB_NAME:-tanuki_db}" < docker/init.sql
```

Si prefieres esto, crea `docker/init.sql` con el `CREATE TABLE` correspondiente a tu motor, y considera montarlo automáticamente en el arranque del contenedor añadiendo esto al servicio `db` en `docker-compose.yml` (MySQL/MariaDB y Postgres soportan ambos este mecanismo de auto-inicialización, ejecutando cualquier `.sql` presente ahí **solo la primera vez** que el volumen de datos está vacío):

```yaml
  db:
    # ...
    volumes:
      - db_data:/var/lib/mysql
      - ./docker/init.sql:/docker-entrypoint-initdb.d/init.sql:ro
```

(para PostgreSQL, la ruta de montaje sería `/docker-entrypoint-initdb.d/init.sql` igual, pero el volumen de datos es `/var/lib/postgresql/data` en vez de `/var/lib/mysql`).

### `.env` para Docker

Pon `DB_HOST=db` para que el contenedor de la app alcance al contenedor de la base de datos por su nombre de servicio (los contenedores en la misma red de Docker se resuelven por nombre de servicio, no por `localhost`):

```ini
DB_HOST=db
```

El resto del `.env` (`DB_NAME`, `DB_USER`, `DB_PASS`, etc.) debe coincidir con lo configurado en el bloque `environment:` del servicio `db` en `docker-compose.yml`.

### Builds de desarrollo vs. producción

`docker-compose.override.yml` se carga automáticamente por Docker Compose siempre que esté presente junto a `docker-compose.yml` — no hace falta ningún flag extra para desarrollo local. Este archivo:

- Construye la imagen de la app con `INSTALL_DEV_DEPS=true`, que instala PHPUnit y la extensión Xdebug (ambos se omiten en un build normal).
- Establece `APP_DEBUG=true` y configura Xdebug para que alcance tu máquina anfitriona.

**Para un build de producción, excluye este archivo** para que las herramientas de desarrollo nunca vayan en la imagen:

```bash
docker compose -f docker-compose.yml up -d --build
```

O simplemente elimina/renombra `docker-compose.override.yml` antes de construir tu imagen de producción.

### Ejecutar PHPUnit dentro del contenedor

El build de desarrollo ya tiene PHPUnit instalado vía Composer:

```bash
docker compose exec app ./vendor/bin/phpunit
docker compose exec app ./vendor/bin/phpunit --testsuite Unit
docker compose exec app ./vendor/bin/phpunit tests/Feature/TodoModelTest.php
```

Si los tests de `tests/Feature/*` necesitan una base de datos real, asegúrate de que `DB_HOST`, `DB_NAME`, etc. en `.env` apunten al servicio `db` (no a `tanuki_db` en `localhost`) — o bien añade un segundo servicio `db_test` a `docker-compose.override.yml`, o apunta `.env.testing` al mismo servicio `db` usando un nombre de base de datos distinto.

### Depurar con Xdebug dentro de Docker

Esto difiere de la [configuración local de Xdebug](#desarrollo-local-con-xdebug) en un punto clave: tu editor (Antigravity/VS Code) corre en tu **máquina anfitriona**, no dentro del contenedor, así que Xdebug tiene que "salir" del contenedor para encontrarlo.

**1. Ya configurado por `docker-compose.override.yml`:**

```yaml
environment:
  - XDEBUG_MODE=debug
  - XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003
extra_hosts:
  - "host.docker.internal:host-gateway"
```

`host.docker.internal` es un nombre DNS especial que Docker provee y que resuelve a la IP de tu máquina anfitriona desde dentro de un contenedor — esta es la pieza que reemplaza a `client_host=127.0.0.1` de la configuración local, ya que `127.0.0.1` dentro de un contenedor apunta al propio contenedor, no a tu máquina.

**2. `.vscode/launch.json`** — igual que la configuración local, sin cambios necesarios salvo uno:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Escuchar Xdebug",
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

**A diferencia del desarrollo local, aquí `pathMappings` sí es necesario** — el contenedor ve tu proyecto en `/var/www/html`, mientras que tu editor lo ve en la carpeta real de tu proyecto en disco. Sin este mapeo, los breakpoints resuelven a la ruta equivocada y nunca se disparan (el mismo fallo que diagnosticamos antes cuando `pathMappings` tenía un placeholder obsoleto — aquí es necesario y debe apuntar a la ruta real del contenedor).

**3. Depurar:**

1. Selecciona "Escuchar Xdebug" en Run & Debug y pulsa **F5**.
2. Asegúrate de que el stack está corriendo: `docker compose up -d`.
3. Pon breakpoints y visita `http://localhost:8050`.

**Solución de problemas:** si los breakpoints se quedan en `unresolved`, verifica que `host.docker.internal` resuelve correctamente desde dentro del contenedor:

```bash
docker compose exec app getent hosts host.docker.internal
```

Si esto falla, tu versión de Docker podría necesitar manejar `--add-host=host.docker.internal:host-gateway` de forma distinta — esto ya está configurado vía `extra_hosts` en `docker-compose.override.yml`, pero versiones muy antiguas de Docker Engine en Linux nativo (sin Docker Desktop) a veces necesitan la IP real del puente `docker0` del host en su lugar. Encuéntrala con `ip addr show docker0` y usa esa IP directamente como `client_host` si `host.docker.internal` no resuelve.