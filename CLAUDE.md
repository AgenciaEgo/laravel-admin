# laravel-admin (fork de AgenciaEgo)

Fork de `z-song/laravel-admin` (`encore/laravel-admin`), el paquete de CMS admin abandonado por su autor original — sin updates, sin soporte para versiones nuevas de Laravel o PHP.

## Por qué existe este fork

Este paquete es consumido por **sullair-click** (Laravel 13, PHP 8.3), un sitio de concursos fotográficos cuyo panel admin (montado bajo el prefijo de ruta `cms`, no `/admin`) depende de él directamente en **31 archivos** de `app/Admin` (clases `Encore\Admin\*`). Reemplazarlo por otra solución (ej. Filament) implicaría reescribir todo eso, así que se decidió forkearlo y mantenerlo en paralelo a los upgrades de PHP/Laravel que sullair-click necesita por los EOL de Azure.

Cronología relevante en sullair-click:
- PHP 8.2 → 8.3 (Azure EOL de 8.2): el paquete original en `^1.8.19` sobrevivió sin cambios de código.
- MySQL 8.0 → 8.4 (EOL de 8.0): también sin fricción del lado del paquete.
- Laravel 9 → 10 → 11 → 12 → 13 (oct/nov-2026), ya sobre este fork (1.9.x/1.10.x). En el camino sullair-click dejó de usar `laravel-admin-ext/helpers` y pasó a pedir `doctrine/dbal ^3.0` por su cuenta.
- **2.0 (nov-2026): UI en Bootstrap 5 + AdminLTE 4**, desarrollada en la rama `ui-upgrade` (ya mergeada a `master`) y probada en una rama de sullair-click. Plan, inventario y decisiones en [docs/ui-upgrade.md](docs/ui-upgrade.md); guía para consumidores en [UPGRADE-2.0.md](UPGRADE-2.0.md). La 1.x sigue en mantenimiento en la rama `1.x` (ver instrucción 2).

## Cómo lo consume sullair-click

En el `composer.json` de sullair-click:

```json
"repositories": [
  { "type": "vcs", "url": "https://github.com/AgenciaEgo/laravel-admin" }
],
"require": {
  "encore/laravel-admin": "^2.0"
}
```

Composer resuelve el paquete desde los tags `vX.Y.Z` de este repo. Para que sullair-click reciba un release nuevo: `composer update encore/laravel-admin --with-dependencies` (y, si el release toca [resources/assets/](resources/assets/), republicar assets — ver pendientes). Históricamente usó `dev-master as 1.8.19` mientras dependía de `laravel-admin-ext/helpers` (`~1.6`); ya no hace falta.

Para probar cambios sin release, en una rama propia de sullair-click: `"encore/laravel-admin": "dev-<rama> as 2.x.y"` (rama pusheada) o un path repository a este working copy (`{"type":"path","url":"/Applications/MAMP/htdocs/laravel-admin","options":{"symlink":true}}` antes del repo vcs; con el symlink, Blade/PHP/lang se ven al instante y los assets necesitan `vendor:publish --force`). Ese `composer.json`/`composer.lock` no se commitea.

Al momento de crear este fork, su `composer.json` era una copia sin tocar del original (`"name": "encore/laravel-admin"`, `"php": ">=7.0.0"`, `"laravel/framework": ">=5.5"`, `"symfony/dom-crawler": "~3.1|~4.0|~5.0"`, `"doctrine/dbal": "2.*|3.*"`) — todavía sin ningún parche propio.

## Instrucciones para trabajar en este repo

1. **No renombrar el paquete.** Tiene que seguir siendo `"name": "encore/laravel-admin"` en `composer.json` — sullair-click (y cualquier otro consumidor futuro) lo importa por ese nombre exacto. Cambiarlo rompe la resolución de Composer del lado consumidor.
2. **Trabajar sobre `master`** (decisión ya tomada por el usuario — no crear branches de feature separadas salvo que se indique lo contrario). `master` es la **2.x** (Bootstrap 5 + AdminLTE 4). La **1.x** (Bootstrap 3) vive en la rama **`1.x`**, solo para fixes: un bug que exista en las dos líneas se arregla en `1.x` y se mergea hacia `master` (o se cherry-pickea si el código divergió). Los fixes de la 1.x no salen de a uno: se juntan y se liberan juntos, salvo que algo rompa sullair-click en producción. Los íconos son compatibles entre líneas: el iconpicker de la 2.0 sigue guardando nombres FA4 en `admin_menu.icon` y FA6 los muestra con los v4-shims.
3. **Objetivo de los upgrades:** cuando el usuario lo pida, actualizar las constraints de `composer.json` (`php`, `laravel/framework`, `symfony/dom-crawler`, `doctrine/dbal`, etc.) para soportar versiones más nuevas de Laravel y PHP, y parchear el código fuente donde haga falta (deprecations, cambios de firma del framework, etc.). Probablemente incremental, a medida que sullair-click vaya subiendo de versión — no asumir que hay que saltar directo a la última versión de Laravel sin que el usuario lo confirme.
4. **Validar siempre contra el consumidor real**, nunca en el vacío — los cambios de este fork solo importan si sullair-click sigue funcionando. Flujo de prueba típico: cambiar algo acá → pushear a `master` → en sullair-click, `composer update encore/laravel-admin --with-dependencies` → `php artisan serve` → chequear home pública + `/cms/auth/login` + al menos un CRUD real logueado. (sullair-click corre localmente con MySQL 8.4 en Docker — si algo da 500 apenas arranca, lo primero a chequear es si el contenedor está levantado, no el código del fork.)
5. **Releases/versionado** (decidido con el usuario, oct-2026): semver continuando la línea 1.x de upstream, tags `vMAJOR.MINOR.PATCH`. Primer release del fork: **`v1.9.0`** (soporte Laravel 9–13); último de la 1.x al salir la 2.0: `v1.10.4`. **`v2.0.0`** = migración de UI (rompe el HTML de los consumidores, ver [UPGRADE-2.0.md](UPGRADE-2.0.md)). Nada de sufijos tipo `-sullair.1` (Composer los trata como pre-release). Proceso: subir `Admin::VERSION` en [src/Admin.php](src/Admin.php) en un commit en la rama de release (`master` para `v2+`, `1.x` para `v1.*`) → pushear → con CI verde, pushear el tag `vX.Y.Z` → [.github/workflows/release.yml](.github/workflows/release.yml) valida que el tag coincida con `Admin::VERSION` y esté en su rama de release, corre toda la matriz y publica el GitHub Release con notas auto-generadas. Crear/pushear tags solo cuando el usuario lo pida.
6. **Nunca tocar `z-song/laravel-admin`** (el repo original). Este fork es independiente; cualquier sync con upstream sería un merge manual y deliberado que el usuario debe pedir explícitamente, nunca algo a automatizar por iniciativa propia.

## Mapa del codebase

Estado de partida: `master` = último commit upstream (`67c441eb`, 2023-02-03), ~3170 commits de historia heredada. `origin` apunta a `git@github.com:AgenciaEgo/laravel-admin.git`. `Admin::VERSION` en [src/Admin.php](src/Admin.php) es la fuente de verdad de la versión (upstream lo había dejado en `1.8.17`; el release workflow exige que coincida con el tag).

### Dependencias (`composer.json`)

- **Runtime:** `php ^8.0.2`, `laravel/framework ^9.0|^10.0|^11.0|^12.0|^13.0`, `symfony/dom-crawler ^5.4|^6.0|^7.0|^8.0`. `doctrine/dbal` salió del `require` (pasó a `suggest`): solo lo usan los generadores en Laravel 9/10; en 11+ se usa `Schema::getColumns()`.
- **Opcionales (`suggest`):** `intervention/image ~2.3` (manipulación de imágenes en uploads — `Form\Field\Image`/`ImageField`), `spatie/eloquent-sortable` (grids ordenables, `Grid\Displayers\Orderable`).
- **Dev:** `laravel/laravel`, `fzaninotto/faker` (abandonado), `laravel/browser-kit-testing ^6.0`, `spatie/phpunit-watcher`.
- **Auto-discovery:** registra `Encore\Admin\AdminServiceProvider` y el alias `Admin` → `Encore\Admin\Facades\Admin`.
- **Frontend (2.x):** no hay build (sin npm/webpack). Todos los assets vienen vendoreados en [resources/assets/](resources/assets/) (bajados de npm verificando el shasum del registry) y se publican a `public/vendor/laravel-admin`: AdminLTE 4.10 (`AdminLTE4/`, trae Bootstrap 5.3 compilado) + `bootstrap5/` (bundle JS 5.3.8), Font Awesome 6.7.2 + v4-shims, jQuery 3.7.1 + jquery-migrate (en `AdminLTE/`, junto con input-mask, ion.rangeSlider y slimScroll) + jquery-pjax, select2 4.1, bootstrap-fileinput 5.5, flatpickr 4.6 (detrás de un adaptador con las opciones de eonasdan), Coloris, bootstrap4-duallistbox, fontawesome-iconpicker, nestable, nprogress, sweetalert2, toastr, number-input y Nunito (`google-fonts/`). CSS/JS propios en `laravel-admin/` (accent color `admin.accent_color` → `--la-accent`, base 15px, drawer de filtros, switch, sorter). En la 1.x eran AdminLTE 2.3 + Bootstrap 3.4.1 + FA4.

Versiones efectivamente instaladas en sullair-click (composer.lock, nov-2026): encore/laravel-admin v1.10.1, laravel/framework v13.40.0, doctrine/dbal 3.10.6 (pedido por sullair-click), symfony/dom-crawler v7.4.22, intervention/image 2.7.2, maatwebsite/excel 3.1. PHP local 8.3.

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
- [tests/smoke/ui/](tests/smoke/ui/) — arnés de UI: `setup-ui.sh <app>` instala un showcase (todos los campos, grid, show, widgets) en una app de `setup-app.sh`; `interactions.mjs <app> [port]` lo maneja con puppeteer como un usuario (plugins, grid, filtros, inline edit, round-trips a la DB, 0 errores JS) y `screenshots.mjs` saca capturas. Requiere `npm install` en esa carpeta y Chrome.
- [tests/smoke/](tests/smoke/) — **arnés de prueba real del fork** (lo que usa el CI): `setup-app.sh <laravel-major> <dir>` crea una app Laravel limpia que instala este working copy como path repository (SQLite, `admin:install`, copia el test); `AdminSmokeTest.php` es un feature test end-to-end que falla ante cualquier error o deprecation; `http-smoke.sh <dir> [port]` levanta el server real y recorre login/CSRF, sesión persistida + toastr, páginas, export CSV (que hace `exit()` y no se puede probar en proceso) y que todo `<script>`/`<link>` local del login y del layout devuelva 200. Correr localmente: `tests/smoke/setup-app.sh 13 /tmp/app && (cd /tmp/app && vendor/bin/phpunit --filter AdminSmokeTest) && tests/smoke/http-smoke.sh /tmp/app` (para otra versión de PHP, anteponer su `bin` al `PATH`).
- [.github/workflows/](.github/workflows/) — `tests.yml` (push a master/PR: lint de `src/` en PHP 8.0 y 8.5 fallando ante deprecations + matriz smoke Laravel 9–13 × PHP 8.3, Laravel 11–13 × PHP 8.4, Laravel 12–13 × PHP 8.5, más el job `UI interactions` que corre `tests/smoke/ui`) y `release.yml` (ver instrucción 5).
- [tests/](tests/) (resto) — suite heredada de upstream (PHPUnit + browser-kit-testing contra MySQL); no corre en Laravel nuevo (browser-kit ^6 = Laravel ≤8), excluida del CI; se conserva como referencia.
- [.gitattributes](.gitattributes) — `export-ignore` de tests/docs/CI/CLAUDE.md: el zip de cada tag (lo que instala Composer) solo lleva `src`, `config`, `database`, `resources` y metadatos.
- [docs/en/](docs/en/) — documentación de uso de upstream, conservada como referencia offline (se borró `docs/zh`).

### Compatibilidad verificada (oct-2026)

Probado con un test de humo (login, dashboard, CRUD de usuarios con upload, roles, permisos, menú/árbol + reorder, logs, setting, filtros/orden/búsqueda, pjax, `_handle_action_`, `admin:make`/`admin:form`) más un recorrido HTTP real con `artisan serve` (sesión persistida, toastr, export CSV, assets), en apps Laravel limpias con SQLite: **Laravel 9.52, 10.50, 11.57, 12.70 y 13.39 sobre PHP 8.3, y Laravel 12/13 sobre PHP 8.5** (localmente no hay binario de 8.4; 8.5 es superconjunto de sus deprecations, y el CI cubre 8.4). Resultado: todo OK y 0 deprecations.

Cambios hechos para llegar ahí:
- PHP 8.4: 37 parámetros con nullable implícito (`Tipo $x = null` → `?Tipo $x = null`); propiedades dinámicas declaradas (`Tree::$path`, `Grid\Column::$cast`); `null` a funciones internas (`array_filter(..., 'strlen')` en Select/MultipleSelect/Tags, `config()` nulo en `admin_path()`/`ensureHttps()`, `admin:form` sin `--title`, driver de export nulo en `Grid\Exporter`); `fputcsv()` con `$escape` explícito (el mismo `\` de siempre) en `CsvExporter`.
- Laravel 11+: `ResourceGenerator` usa `Schema::getColumns()` y cae a Doctrine solo si no existe.
- Laravel 13: la sesión serializa en JSON por defecto, así que los flashes del paquete (`admin_toastr`, `admin_success/error/warning/info`, `Exception\Handler::error`) guardan arrays planos en vez de `MessageBag`; `partials/toastr` y `partials/alerts` aceptan ambos formatos.
- Symfony 7.4 deprecó (y 8 elimina) `Request::get()`: reemplazado por `input()` en controllers/acciones/grid.
- Ramas muertas de `version_compare(app()->version(), ...)` eliminadas (mínimo Laravel 9).
- Frontend (1.10.0): Bootstrap 3.3.4/3.3.5 → 3.4.1 y jQuery 2.1.4 → 3.7.1 por XSS conocidos, sin tocar markup. jquery-migrate (el `.min` silencia sus avisos) cubre las APIs removidas que usan plugins (`.size()` en bootstrap3-editable). El datetimepicker 4.17.37 no se mostraba con jQuery 3 (llama `.show()` antes de insertar el widget) → 4.17.49; además `bootstrap-editable.css` oculta `.datepicker > div` y con jQuery 3 el calendario quedaba vacío → override en `laravel-admin.css`. Validado con Chrome headless comparando contra los assets viejos: mismos widgets inicializados, 0 errores JS. Los archivos vendoreados se bajan de npm verificando el shasum del registry.

Pendientes conocidos:
- **Assets publicados en consumidores:** sullair-click commitea `public/vendor/laravel-admin`. Cuando un cambio del fork toca [resources/assets/](resources/assets/), el consumidor tiene que correr `php artisan vendor:publish --tag=laravel-admin-assets --force` (si el release borró archivos, antes `rm -rf public/vendor/laravel-admin`) y commitear el resultado; si no, el layout pide archivos que no existen (ej. sin jQuery el panel entero deja de funcionar).
- **Consumidores con `laravel-admin-ext/helpers`**: usa `Request::get()` → rompe con Symfony 8 y emite deprecations con 7.4. No es parte de este fork (sullair-click ya no lo usa).
- **`intervention/image ~2.3`** sigue siendo la API soportada por `Image`/`ImageField`/`MultipleImage`; v3 rompe.
- **`ExcelExporter`** requiere `maatwebsite/excel` (no declarado ni en `suggest`; sullair-click lo pide por su cuenta).
- **Tests/dev deps** de upstream (`fzaninotto/faker`, `browser-kit-testing ^6`) siguen sin correr en Laravel nuevo; la validación real es contra apps reales.
