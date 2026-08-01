# 🦝 Tanuki Framework — Base MVC

Framework PHP ligero con arquitectura MVC limpia, acceso a base de datos y cero dependencias externas. Sin ORM, sin motor de plantillas — un esqueleto sobre el que construir.

**Requisitos:** PHP 8.1+ · MySQL/MariaDB o PostgreSQL · Apache o Nginx

---

## Índice

1. [Instalación](#instalación)
2. [Estructura del proyecto](#estructura-del-proyecto)
3. [Ciclo de vida de una petición](#ciclo-de-vida-de-una-petición)
4. [Rutas](#rutas)
5. [Controladores](#controladores)
6. [La clase Request](#la-clase-request)
7. [Modelos](#modelos)
8. [Vistas y layout](#vistas-y-layout)
9. [Helpers globales](#helpers-globales)
10. [Conexiones opcionales: Redis y MongoDB](#conexiones-opcionales-redis-y-mongodb)
11. [Tutorial: el CRUD de TODO incluido](#tutorial-el-crud-de-todo-incluido)
12. [Añadir tu propio CRUD](#añadir-tu-propio-crud)
13. [Seguridad](#seguridad)
14. [Desarrollo local con Xdebug](#desarrollo-local-con-xdebug)
15. [FAQ](#faq)

---

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