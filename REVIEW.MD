# tanuki_base - Estado de revisión

- [X] .vscode/
- [X] config/
  - [X] database.php — No revisado
  - [X] nav.php — No revisado
- [ ] controllers/
  - [ ] AboutController.php — No revisado
  - [ ] HomeController.php — No revisado
  - [ ] TodoController.php — No revisado
- [x] core/
  - [x] App.php — No revisado
  - [x] Controller.php — No revisado
  - [x] Model.php — No revisado
  - [x] Request.php — No revisado
- [x] includes/
  - [x] footer.php — No revisado
  - [x] head.php — No revisado
- [ ] models/
  - [ ] Todo.php — No revisado
- [ ] views/
  - [x] errors/
    - [x] 404.php — No revisado
    - [x] 500.php — No revisado
  - [ ] todo/
    - [ ] create.php — No revisado
    - [ ] edit.php — No revisado
    - [ ] index.php — No revisado
    - [ ] show.php — No revisado
  - [x] about.php — No revisado
  - [x] home.php — No revisado
- [x] .env — No revisado
- [x] .env-example — No revisado
- [ ] .gitignore — No revisado
- [x] .htaccess — No revisado
- [x] index.php — No revisado
- [x] nginx.config — No revisado
- [ ] README.md — No revisado
- [X] routes.php — No revisado
- [X] utils.php — No revisado


UNA VEZ REVISADOS TODOS LOS ARCHIVOS PROCEDER A CREAR LOS TESTS CON PHPUNIT.

DESPUES DE LOS TEST DOCUMENTAR COMO HACER EN EL README LOS TEST TAMBIÉN, AÑADIR TODO LO QUE INSTALAR EN LINUX UBUNTU O UN SERVIDOR XAMP EN WINDOWS, O CONTENEDORES CUANDO TOQUE.

AÑADIR EL DOCKERFILE Y EL DOCKER-COMPOSE.YML DE LA VERSIÓN TANUKI_BASE QUE INCLUYE EL CONTENEDOR DE LA APP, EL CONTENEDOR DE LA BASE DE DATOS (TODAS LAS OPCIONES COMENTADAS Y DOCUMENTADO EN README PARA SABER COMO ELEGIR UNA U OTRA) Y EL CONTENEDOR NGINX DONDE CARGAR EL ARCHIVO NGINX (APACHE QUEDA IGNORADO SIENDO SUFICIENTE EL .HTACCESS PARA HOSTINGS)

CUANDO TODO FUNCIONE ELIMINAR TODO RASTRO DEL CRUD TODO INCLUIDO TESTS Y DEJARLO SOLO EN EL README YA QUE ES UN EJERCICIO DE APRENDIZAJE DEL FRAMEWORK Y NO UN ARCHIVO, REVISA ANTES QUE COINCIDA TODO LO QUE HAY CON LO QUE ESTA EN EL README Y ACTUALIZAR README CON LOS CAMBIOS DE TODO ACTUALES.

