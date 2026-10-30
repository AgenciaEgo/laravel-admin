# laravel-admin (fork de AgenciaEgo)

Fork de `z-song/laravel-admin` (`encore/laravel-admin`), el paquete de CMS admin abandonado por su autor original — sin updates, sin soporte para versiones nuevas de Laravel o PHP.

## Por qué existe este fork

Este paquete es consumido por **sullair-click** (Laravel 9, PHP 8.3), un sitio de concursos fotográficos cuyo panel admin (montado bajo el prefijo de ruta `cms`, no `/admin`) depende de él directamente en **31 archivos** de `app/Admin` (clases `Encore\Admin\*`). Reemplazarlo por otra solución (ej. Filament) implicaría reescribir todo eso, así que se decidió forkearlo y mantenerlo en paralelo a los upgrades de PHP/Laravel que sullair-click necesita por los EOL de Azure.

Cronología relevante en sullair-click:
- PHP 8.2 → 8.3 (Azure EOL de 8.2): el paquete original en `^1.8.19` sobrevivió sin cambios de código.
- MySQL 8.0 → 8.4 (EOL de 8.0): también sin fricción del lado del paquete.
- Próximo salto grande previsto: **Laravel 9 → 10/11/12**. Ahí es donde se espera que el paquete original empiece a romper de verdad (bindings de service providers, cambios en el query builder, etc.) — ese es el motivo concreto de tener este fork listo antes de necesitarlo con urgencia.

## Cómo lo consume sullair-click

En el `composer.json` de sullair-click:

```json
"repositories": [
  { "type": "vcs", "url": "https://github.com/AgenciaEgo/laravel-admin" }
],
"require": {
  "encore/laravel-admin": "dev-master as 1.8.19"
}
```

Esto le dice a Composer: traé el paquete desde este repo Git (rama `master`), pero hacé como si fuera la versión `1.8.19` (alias) — así `laravel-admin-ext/helpers` (otra dependencia de sullair-click, que pide `~1.6`) queda satisfecha sin tener que tocarla también.

Para que sullair-click reciba cambios nuevos de este fork: `composer update encore/laravel-admin --with-dependencies` (el alias de versión ya queda fijo, no hace falta cambiarlo en cada update).

Al momento de crear este fork, su `composer.json` era una copia sin tocar del original (`"name": "encore/laravel-admin"`, `"php": ">=7.0.0"`, `"laravel/framework": ">=5.5"`, `"symfony/dom-crawler": "~3.1|~4.0|~5.0"`, `"doctrine/dbal": "2.*|3.*"`) — todavía sin ningún parche propio.

## Instrucciones para trabajar en este repo

1. **No renombrar el paquete.** Tiene que seguir siendo `"name": "encore/laravel-admin"` en `composer.json` — sullair-click (y cualquier otro consumidor futuro) lo importa por ese nombre exacto. Cambiarlo rompe la resolución de Composer del lado consumidor.
2. **Trabajar sobre `master`** (decisión ya tomada por el usuario — no crear branches de feature separadas salvo que se indique lo contrario).
3. **Objetivo de los upgrades:** cuando el usuario lo pida, actualizar las constraints de `composer.json` (`php`, `laravel/framework`, `symfony/dom-crawler`, `doctrine/dbal`, etc.) para soportar versiones más nuevas de Laravel y PHP, y parchear el código fuente donde haga falta (deprecations, cambios de firma del framework, etc.). Probablemente incremental, a medida que sullair-click vaya subiendo de versión — no asumir que hay que saltar directo a la última versión de Laravel sin que el usuario lo confirme.
4. **Validar siempre contra el consumidor real**, nunca en el vacío — los cambios de este fork solo importan si sullair-click sigue funcionando. Flujo de prueba típico: cambiar algo acá → pushear a `master` → en sullair-click, `composer update encore/laravel-admin --with-dependencies` → `php artisan serve` → chequear home pública + `/cms/auth/login` + al menos un CRUD real logueado. (sullair-click corre localmente con MySQL 8.4 en Docker — si algo da 500 apenas arranca, lo primero a chequear es si el contenedor está levantado, no el código del fork.)
5. **Releases/versionado:** no hay convención definida todavía. Si se pide "crear un release" o poner un tag, preguntar primero qué esquema quiere el usuario (semver propio del fork, ej. `1.8.20-sullair.1`, vs. seguir pegado a la numeración upstream) — no asumir ni inventar un esquema.
6. **Nunca tocar `z-song/laravel-admin`** (el repo original). Este fork es independiente; cualquier sync con upstream sería un merge manual y deliberado que el usuario debe pedir explícitamente, nunca algo a automatizar por iniciativa propia.

## Mapa del codebase

Estado de partida: `master` = último commit upstream (`67c441eb`, 2023-02-03), ~3170 commits de historia heredada. `origin` apunta a `git@github.com:AgenciaEgo/laravel-admin.git`. Ojo: `Admin::VERSION` en [src/Admin.php](src/Admin.php) dice `1.8.17` (upstream nunca lo subió), aunque sullair-click lo aliasa como `1.8.19`.

### Dependencias (`composer.json`)

- **Runtime:** `laravel/framework >=5.5` (sin techo), `php >=7.0.0`, `symfony/dom-crawler ~3.1|~4.0|~5.0`, `doctrine/dbal 2.*|3.*`.
- **Opcionales (`suggest`):** `intervention/image ~2.3` (manipulación de imágenes en uploads — `Form\Field\Image`/`ImageField`), `spatie/eloquent-sortable` (grids ordenables, `Grid\Displayers\Orderable`).
- **Dev:** `laravel/laravel`, `fzaninotto/faker` (abandonado), `laravel/browser-kit-testing ^6.0`, `spatie/phpunit-watcher`.
- **Auto-discovery:** registra `Encore\Admin\AdminServiceProvider` y el alias `Admin` → `Encore\Admin\Facades\Admin`.
- **Frontend:** no hay build (sin npm/webpack). Todos los assets vienen vendoreados en [resources/assets/](resources/assets/) y se publican a `public/vendor/laravel-admin`: AdminLTE 2 + Bootstrap 3, jQuery + jquery-pjax, Font Awesome 4, bootstrap-fileinput, bootstrap-switch, bootstrap3-editable, eonasdan datetimepicker + moment, flatpickr, iconpicker, nestable, nprogress, sweetalert2, toastr, duallistbox.

Versiones efectivamente instaladas en sullair-click (composer.lock, oct-2026): laravel/framework v9.52.22, doctrine/dbal 3.10.6, symfony/dom-crawler v5.4.52, intervention/image 2.7.2, laravel-admin-ext/helpers v1.2. PHP local 8.3.

### Estructura de `src/` (~300 archivos, namespace `Encore\Admin\`)

- [src/AdminServiceProvider.php](src/AdminServiceProvider.php) — registra 18 comandos artisan (`admin:install`, `admin:make`, `admin:menu`, `admin:create-user`, etc.), middlewares (`admin.auth`, `admin.pjax`, `admin.log`, `admin.permission`, `admin.bootstrap`, `admin.session`) y el grupo `admin`; carga vistas con namespace `admin`, `admin_path('routes.php')` del consumidor, publishing de config/lang/migrations/assets, directivas Blade `@box/@endbox`, macros de router.
- [src/Admin.php](src/Admin.php) — clase central (facade `Admin`): rutas internas (`routes()`), menú, usuario/guard, bootstrap (`app/Admin/bootstrap.php` del consumidor), assets JS/CSS.
- [src/helpers.php](src/helpers.php) — helpers globales autoloadeados: `admin_path`, `admin_url`, `admin_asset`, `admin_trans`, `admin_toastr`, `admin_success/error/warning/info`, `admin_get_route`, etc.
- **Builders principales:** [src/Grid.php](src/Grid.php) + [src/Grid/](src/Grid/) (columnas, ~36 displayers, ~20 filtros, exporters CSV, acciones, selectable, inline editing); [src/Form.php](src/Form.php) + [src/Form/](src/Form/) (~65 tipos de campo en `Form/Field`, hasMany/embeds/tabs/layout); [src/Show.php](src/Show.php) + [src/Show/](src/Show/) (vista de detalle); [src/Tree.php](src/Tree.php) + `Traits/ModelTree.php` (árboles con nestable).
- [src/Actions/](src/Actions/) — sistema de acciones (Row/Batch/Grid actions) con interactores Form/Dialog; usa `symfony/dom-crawler`.
- [src/Auth/](src/Auth/) — modelos Eloquent en `Auth/Database/` (más `AdminTablesSeeder`): `Administrator`, `Role`, `Permission`, `Menu`, `OperationLog` + chequeo de permisos.
- [src/Controllers/](src/Controllers/) — `AdminController` (base para CRUDs del consumidor), `AuthController`, `UserController`, `RoleController`, `PermissionController`, `MenuController`, `LogController`, `HandleController` (endpoints de acciones/forms/selectable), `Dashboard`.
- [src/Middleware/](src/Middleware/) — `Authenticate`, `Permission`, `LogOperation`, `Pjax` (usa DomCrawler para recortar respuestas), `Bootstrap`, `Session`.
- [src/Layout/](src/Layout/) (Content/Row/Column), [src/Widgets/](src/Widgets/) (Box, Tab, Table, Form, InfoBox, StepForm, Navbar…), [src/Console/](src/Console/) (comandos + stubs), [src/Traits/](src/Traits/), [src/Exception/Handler.php](src/Exception/Handler.php), [src/Extension.php](src/Extension.php) (base para extensiones tipo `laravel-admin-ext/*`).

### Otros directorios

- [config/admin.php](config/admin.php) — config publicada al consumidor (route prefix/namespace/middleware, guard `admin`, tablas, upload disk, operation_log, skin/layout, extensiones). sullair-click usa prefix `cms` vía `ADMIN_ROUTE_PREFIX`.
- [database/migrations/2016_01_04_173148_create_admin_tables.php](database/migrations/2016_01_04_173148_create_admin_tables.php) — 9 tablas `admin_*` (users, roles, permissions, menu, pivotes, operation_log), nombres tomados de config.
- [resources/views/](resources/views/) (Blade: grid, form, show, tree, widgets, login, partials), [resources/lang/](resources/lang/) (23 idiomas, incluye `es`).
- [tests/](tests/) — PHPUnit + browser-kit-testing contra MySQL (`laravel_admin_test`); requieren levantar un `laravel/laravel` completo vía `require-dev`. CI heredado: [.travis.yml](.travis.yml) (PHP 7.2–8.0, muerto). `.github/` solo tiene dependabot y stale bot. No hay CI activo en el fork.

### Puntos sensibles para el upgrade a Laravel 10/11/12

Detectados en el recorrido inicial (no verificados todavía contra un Laravel nuevo):

- **Doctrine DBAL:** [src/Console/ResourceGenerator.php:216-231](src/Console/ResourceGenerator.php#L216-L231) usa `isDoctrineAvailable()` / `getDoctrineSchemaManager()` / `registerDoctrineTypeMapping`, que Laravel 11 eliminó (reemplazar por `Schema::getColumns()`). Solo afecta a los generadores `admin:make`/`admin:form`, no al runtime del panel. Con L11+ también se puede quitar `doctrine/dbal` del `require`.
- **`symfony/dom-crawler ~5.0`:** Laravel 10 trae Symfony 6, Laravel 11/12 Symfony 7. Hoy convive porque dom-crawler 5.4 es standalone, pero conviene ampliar a `^6.0|^7.0` (lo usan `Middleware/Pjax.php` y `Actions/Interactor/Form.php`).
- **Chequeos de versión hardcodeados:** `version_compare(app()->version(), ...)` en `AdminServiceProvider.php:119`, `Traits/DefaultDatetimeFormat.php:11`, `Grid/Model.php:635` — legacy, revisar que sigan tomando la rama correcta.
- **`intervention/image ~2.3`:** v3 cambió toda la API; si sullair-click sube a v3, rompen `Form/Field/ImageField.php`, `Image.php` y `MultipleImage.php`.
- **PHP 8.x:** constraints `>=7.0.0` muy laxas; esperar deprecations (dynamic properties en 8.2, nullables implícitos en 8.4) en las ~300 clases.
- **Tests/dev deps:** `fzaninotto/faker` abandonado (→ `fakerphp/faker`), `browser-kit-testing ^6.0` solo cubre Laravel ≤8; la suite de tests no corre tal cual en versiones nuevas.
