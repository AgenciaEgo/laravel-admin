# UI upgrade: Bootstrap 3 + AdminLTE 2 → Bootstrap 5 + AdminLTE 4

Rama: `ui-upgrade` (separada de `master` a propósito). Estado: **fases 1 y 2 hechas, fase 3 en curso: markup base del form hecho; siguen los plugins, uno por commit (ver sección 9)**.

## 1. Punto de partida

`master` (v1.10.0) trae Bootstrap 3.4.1, AdminLTE 2.3, jQuery 3.7.1 con jquery-migrate y Font Awesome 4. El salto 3.3 → 3.4.1 solo se hizo para cerrar XSS conocidos, sin tocar markup. Bootstrap 3 y AdminLTE 2 ya no tienen soporte.

**Novedad respecto de lo que dice CLAUDE.md:** sullair-click ya está en Laravel 13 (commits `41f017f`…`48dd750`), pide `encore/laravel-admin: ^1.10.0` y **ya no usa `laravel-admin-ext/helpers`**. El único motivo para no salir de 1.x era esa dependencia (`~1.6`), así que ya no hay nada que impida sacar esta migración como **2.0.0**.

## 2. Arnés de comparación visual (ya armado en esta rama)

Todo vive en [tests/smoke/ui/](../tests/smoke/ui/), que es `export-ignore` y por lo tanto no llega a los consumidores:

| Archivo | Qué hace |
|---|---|
| `setup-ui.sh <app>` | Instala en una app creada con `tests/smoke/setup-app.sh` un CRUD **Showcase**: todos los campos de form (~50), displayers, filtros, quick create, selector, row/batch actions, show y hasMany. Suma una página **Widgets** con todos los widgets, menú anidado, disco de uploads y datos de ejemplo. |
| `screenshots.mjs <app> <out>` | Levanta el server, se loguea y saca **45 capturas**: pantallas completas más estados abiertos (dropdowns, select2, datetimepicker, colorpicker, iconpicker, editable, modales, sweetalert, validación) y vista mobile. Deja en `report.json`, por captura, los errores JS, los requests fallidos y si la interacción funcionó. |
| `bs3-inventory.py <dirs…>` | Cuenta los patrones de Bootstrap 3, AdminLTE 2, FA4 y plugins por directorio, con `--files` para listar los archivos. Sirve para dimensionar el trabajo y para medir el avance: el objetivo es que quede en 0. |

Línea base: `tests/smoke/ui/shots/bs3-master/` (ignorada por git, se regenera cuando haga falta). **45/45 capturas sin errores JS ni requests fallidos.**

```bash
tests/smoke/setup-app.sh 13 /tmp/ui-app && tests/smoke/ui/setup-ui.sh /tmp/ui-app
(cd tests/smoke/ui && npm install)
node tests/smoke/ui/screenshots.mjs /tmp/ui-app tests/smoke/ui/shots/<nombre>
```

Algo que se puede sumar al final: correr `screenshots.mjs` en CI y fallar ante errores JS o interacciones rotas. Las capturas se subirían como artefacto, sin comparación de píxeles.

## 3. Inventario

Hits/archivos. *Fork* = `resources/views` + `src` + `resources/assets/laravel-admin`. *Consumidor* = sullair-click `app/Admin` + `resources/views/admin` + `public/admin`.

| Área | Fork | Consumidor | Cambio en BS5 / AdminLTE 4 |
|---|---|---|---|
| `box*` (AdminLTE) | 198 / 41 | 117 / 9 | `card*` |
| `form-group`, `control-label`, `help-block`, `has-error` | ~200 / ~120 | – | `mb-3`, `form-label`, `form-text`, `is-invalid` |
| `input-group-addon` / `-btn` | 21 / 16 | – | `input-group-text` / botones directos |
| `checkbox`/`radio` wrappers + iCheck | 86 / 34 | 8 / 2 | `form-check` nativo |
| `data-toggle/target/dismiss…` | 66 / 35 | 8 / 6 | `data-bs-*` |
| `pull-*`, `hidden-*`, `text-left/right`, `sr-only` | ~90 / ~55 | 14 / 10 | `float-start/end`, `d-none`, `text-start/end`, `visually-hidden` |
| `btn-default`, `btn-xs`, `btn-flat`, `btn-box-tool` | 51 / 35 | 9 / 3 | `btn-secondary`, `btn-sm`, `btn-tool` |
| `panel*`, `well`, `thumbnail`, `caret`, `divider`, `close`, `glyphicon` | ~110 / ~45 | 10 / 4 | `card`, `btn-close`, etc. |
| Layout (`content-wrapper`, `main-sidebar`, `treeview`, skins, `pushMenu`) | 56 / 21 | – | `app-wrapper`, `app-sidebar`, AdminLTE 4 JS |
| Íconos `fa fa-*` (FA4) | 152 / 79 | 14 / 9 | FA6/7 (`fa-solid`, renombres) |

Tamaño total: 138 vistas Blade, 68 archivos de `src/` con HTML/JS en heredocs, 75 con `<script>`/`Admin::script`, `laravel-admin.js` (198 líneas) y `laravel-admin.css` (90).

Lo que tiene sullair-click propio y habrá que adaptar:
- **12 vistas** en `resources/views/admin`: grids de fotos y videos, annual winners, dashboard, tools de filtro, orden y links. Usan sobre todo `box*`, `pull-*`, `btn-default`, `data-toggle`, `panel`.
- **5 archivos PHP** en `app/Admin` con HTML: `thumbnail`, `fa fa-*` en acciones, `data-toggle` en `AnnualWinnersController`.
- `public/admin/css/overrides.css` (2,8 KB de overrides propios), `dashboard.css` y `dashboard-report.js` (77 `box*`), y `photo-editor.js`.
- **18 `->switch()`** (8 con `->states()`), **8 `->date()`** (uno con `->format('YYYY')`) y **10 `->file()`**.

## 4. Stack destino (versiones de npm al 2026-11-04)

| Componente | Hoy | Destino recomendado | Notas |
|---|---|---|---|
| Bootstrap | 3.4.1 | **5.3.8** | La 6.0 está en alpha.1: no apuntar ahí. |
| AdminLTE | 2.3 | **4.10.0** | Pide `bootstrap ^5.3.8`; su JS no usa jQuery. Cambian el layout y las clases (`app-*`). |
| jQuery | 3.7.1 + migrate | **3.7.1** (sin cambios) | Ya salió jQuery 4.0.0, pero mezclarlo con esta migración suma riesgo. Mejor un paso aparte, después. Bootstrap 5 registra sus plugins en jQuery si lo detecta, así que `$(...).modal()` sigue andando. |
| Font Awesome | 4.7 | **6.x free + `v4-shims`** (o 7.x) | `admin_menu.icon` guarda nombres FA4 en la base de cada consumidor, y el seeder también. Con los shims esos íconos siguen funcionando sin migrar datos. |
| pjax, nestable, toastr, sweetalert2, inputmask, ion.rangeSlider, moment | – | Se mantienen (algunos se actualizan) | No dependen de Bootstrap. |

## 5. Plugins que dependen de Bootstrap 3

| Plugin (uso) | Opciones | Recomendación | Impacto en consumidores |
|---|---|---|---|
| **bootstrap-switch** (`->switch()`, `SwitchDisplay`, `SwitchGroup`) | `form-switch` nativo | **Nativo.** | `->states()` sigue aceptado, pero se pierden los textos ON/OFF dentro del switch (pasan a ser un label al costado). Afecta a sullair-click: 18 usos. |
| **eonasdan datetimepicker 4** (`date`, `datetime`, `time`, `month`, `year`, rangos, filtros, inline edit) | flatpickr (ya vendoreado), tempus-dominus 6 | **flatpickr.** No depende de Bootstrap ni de moment y ya está en el paquete. | `->format()` y `->options()` reciben tokens de moment (`YYYY-MM-DD`). Hace falta una capa que traduzca moment → flatpickr para no romper código existente. |
| **x-editable** (`->editable()`) | fork `x-editable-bootstrap5` (un solo mantenedor, git propio), reimplementar | **Reimplementar `editable()` sobre los displayers inline del paquete** (`Input`, `Select`, `Datetime`, `Textarea`…), que ya usan su propio popover. Se borra x-editable. | La API `->editable('select', [...])` se mantiene. |
| **iCheck** (radio, checkbox, row selector) | nativo | **`form-check` nativo.** | El JS que escucha eventos `ifChecked`/`ifChanged` deja de funcionar: hay que cambiarlo por `change`. sullair-click no los usa. |
| **select2 4.0** | select2 4.1 + `select2-bootstrap-5-theme`, tom-select | **select2 4.1 + theme.** | tom-select rompería `->config()`/`->ajax()` y el JS de los consumidores. |
| **bootstrap-duallistbox** (`listbox`, permisos de roles) | `bootstrap4-duallistbox`, `dual-listbox` (vanilla) | Probar `bootstrap4-duallistbox`; si se ve mal, `dual-listbox`. | Bajo: sullair-click no lo usa. |
| **bootstrap-fileinput 4** (`file`, `image`, `multiple*`) | v5.5.4 con tema BS5 | **v5.** | Hay que revisar las opciones que se pasan con `->options()`. |
| **bootstrap-colorpicker 2** (`color`) | `@melloware/coloris`, `<input type=color>` | **Coloris.** | `->options()` cambia. |
| **fontawesome-iconpicker** (`icon`, menú) | v3.2 (FA5) con lista FA6, picker propio | Decidirlo cuando esté elegido FA6 o FA7. | Bajo. |
| **slimScroll** | OverlayScrollbars (lo usa AdminLTE 4), CSS nativo | Lo que traiga AdminLTE 4. | Ninguno. |
| `bootstrap-slider` (vendoreado, sin uso) | – | Borrar. | – |

## 6. Decisiones (tomadas con el usuario el 2026-11-04)

1. **Versión:** sale como **2.0.0**.
2. **Sin capa de compatibilidad con Bootstrap 3.** El HTML propio de sullair-click (12 vistas, 5 PHP, CSS/JS en `public/admin`) se adapta en sullair-click.
3. **Font Awesome 6 free + `v4-shims`.**
4. **Aspecto:** AdminLTE 4 "de fábrica", con el skin más parecido al actual. Todo se trabaja en **ramas paralelas**, para armar una instancia de prueba y mostrarla sin pisar nada productivo:
   - fork: rama `ui-upgrade`. `master` queda en 1.x con Bootstrap 3.
   - sullair-click: rama `ui-upgrade` propia, que consume la rama del fork (`"encore/laravel-admin": "dev-ui-upgrade as 2.0.0"`, o un path repository local mientras la rama no esté pusheada). Su `master` sigue en `^1.10`.
5. **Switch:** sin texto ON/OFF adentro. `->states()` sigue aceptado y se ignoran los textos.

## 7. Fases propuestas

Cada fase termina con `screenshots.mjs` sin errores JS y sin interacciones rotas, el smoke test (`AdminSmokeTest` + `http-smoke.sh`) en verde y el inventario bajando.

1. **Assets base + layout:** Bootstrap 5.3, AdminLTE 4 y FA, más `index`, `partials/header|sidebar|menu|footer`, `login`, skins y pushMenu. Resultado: un panel navegable (los forms todavía rotos). **Acá se valida el aspecto con el cliente.**
2. **Widgets y layout de contenido:** `Box` → card, `InfoBox`, `Tab`, `Callout`, `Alert`, `Collapse`, `Table`, `Content`/`Row`/`Column`, alerts, toastr.
3. **Form:** `form-group` y compañía en las ~65 vistas de campos, input-groups, validación, fieldset, tabs, hasMany/embeds/table; reemplazo de plugins uno por uno (switch, iCheck, datetimepicker con capa de formato, colorpicker, fileinput, duallistbox, select2 theme, iconpicker).
4. **Grid:** tools (filter, export, column selector, batch, quick search/create, selector), filtros, displayers, row actions (dropdown/contextmenu), inline edit y reimplementación de `editable()`, paginación.
5. **Show, Tree (nestable), Actions** (modales/dialog con interactores), StepForm/MultipleSteps.
6. **JS propio:** `laravel-admin.js`, `data-bs-*` dentro de heredocs de `src/`, eventos.
7. **Limpieza:** borrar assets de Bootstrap 3 y plugins viejos, docs, guía `UPGRADE-2.0.md` para consumidores y CI.
8. **sullair-click:** adaptar las vistas, PHP y CSS/JS propios, `vendor:publish --force`, y recorrido manual completo.

## 8. Hallazgos colaterales (bugs en `master`, no son de UI)

El showcase cubre más código que el smoke test y destapó deprecations de PHP 8.x que **también existen en master**:
- `Grid\Displayers\Expand::getLoadUrl()` y `Modal::getLoadUrl()`: `str_replace()` recibe `null` cuando `expand()`/`modal()` se usan con closure, que es el uso más común.
- `Grid\Tools\QuickSearch` (vía `HasQuickSearch::$searchKey`): accede a una propiedad estática de trait a través del trait. Está deprecado desde PHP 8.1.

Conviene arreglarlas en `master` (patch 1.10.1) y extender `AdminSmokeTest` para que cubra estos casos. (Hecho en 1.10.2.)

- **pjax con jQuery 3** (desde 1.10.0, arreglado en `master` en 9de59ee3): jQuery 3 eliminó `$.fn.selector`, así que jquery-pjax mandaba `X-PJAX-Container: undefined`, el middleware `Pjax` no podía recortar la respuesta (`abort(422)`, tragado por el `catch`) y pjax caía a una recarga completa de la URL del request. Consecuencias: cada click era una recarga completa, y los submits de formularios se repetían como GET del action. Un form con errores de validación terminaba en el listado sin errores ni input, y "continuar editando" terminaba en la vista de detalle. Lo detectó la captura de validación del showcase.

## 9. Avance

### Fase 1: assets base + layout (2026-11-04)

- Vendoreados de npm, verificando shasum contra el registry y sin modificar: `AdminLTE4/` (adminlte 4.10.0: `adminlte.min.css`, que ya trae Bootstrap 5.3 compilado, más `adminlte-colors.min.css` para los colores extendidos, `adminlte-select2.min.css` para la fase 3 y `adminlte.min.js`), `bootstrap5/` (`bootstrap.bundle.min.js` 5.3.8, con Popper) y `fontawesome6/` (6.7.2 `all` + `v4-shims` + webfonts). Los assets de Bootstrap 3 siguen en el repo hasta la fase 7.
- Reescritos `index`, `content`, `login` y `partials/header|sidebar|menu|footer` con las clases de AdminLTE 4 (`app-wrapper`, `app-header`, `app-sidebar`, `app-main`, `app-content-header`, `nav-treeview`). `#pjax-container` pasa a ser el `<main class="app-main">`.
- `Admin::theme()` traduce `admin.skin` (nombres de AdminLTE 2) y `admin.layout` a clases de AdminLTE 4. Los configs 1.x publicados siguen funcionando: `skin-black` deja el header blanco con sidebar oscuro, `skin-blue-light` usa header azul con sidebar claro, etc.
- Nuevo `admin.color_mode` (`light` por defecto, `dark` o `auto`). Sin esto AdminLTE 4 sigue la preferencia del sistema operativo y el panel aparecía en oscuro.
- Buscador del menú: se usa el sidebar-search nativo de AdminLTE 4, que filtra el árbol en el lugar, en vez del dropdown propio.
- Navbar: los elementos propios (`refresh-btn`, `fullscreen`) ahora son `li.nav-item > a.nav-link`. **Los elementos que agreguen los consumidores con `Admin::navbar()` necesitan esas clases** (va a la guía de upgrade).
- Login: sin iCheck ni jQuery; `form-check` nativo e `input-group-text`.
- x-editable salió de los assets base antes de lo previsto: al cargarlo rompía todas las páginas (busca `$.fn.popover.Constructor` de BS3). Hasta la fase 4, `->editable()` en un grid tira un error JS que corta el resto de los scripts de esa página.
- Validación: `AdminSmokeTest` y `http-smoke.sh` en verde. `screenshots.mjs`: el layout se ve bien en escritorio y mobile. Las 14 advertencias restantes son del contenido que corresponde a fases posteriores (grid con `editable()` y select2 de tags).

### Fase 2: widgets y contenido (2026-11-04)

- `Box` → `card`. `style('primary')` da una card con borde de color (`card-primary card-outline`), `solid()` colorea el header y `default` no lleva color. `collapsable()`/`removable()` usan el card-widget de AdminLTE 4 (`data-lte-toggle="card-collapse|card-remove"`). `scrollable()` apunta a `.card-body`.
- `InfoBox` → `small-box text-bg-*`, `Tab` → card con `nav-tabs card-header-tabs`, `Alert` → `alert-dismissible` + `btn-close`, `Collapse` → `accordion`, `Carousel` → markup de BS5. `Callout` no cambia de clase.
- Nuevo `Encore\Admin\Support\Color::bootstrap()`: traduce los nombres de color de AdminLTE 2 (`aqua`, `light-blue`, `maroon`, `purple`…) a colores de BS5/AdminLTE 4. Lo usan InfoBox, Box, Alert y Callout, y lo van a usar los displayers del grid.
- `Widget::formatAttributes($append)` acepta clases extra sin mutar los atributos, así `render()` es idempotente.
- `Layout\Column` con ancho `['xs' => n]` genera `col-n`.
- Dashboard (environment / extensions / dependencies) y alerts de sesión (`partials/alerts`, `partials/exception`) convertidos.
- Validación: smoke y HTTP en verde. Página de widgets y dashboard OK en las capturas (`shots/bs5-phase2`).

### Fase 3: formularios (en curso)

**3a, markup base (2026-11-04).**
- Grupos de campo: `.form-group.row.mb-3`. Si el campo no es horizontal, `.form-group.mb-3`. Se conservan `form-group` y `has-error` como ganchos, porque los usan los scripts de los campos (cascade, tabs con errores, select2).
- Labels `col-form-label text-sm-end` o `form-label`, que salen de `Field::getViewElementClasses()`. Errores con `invalid-feedback d-block`, ayuda con `form-text`, `input-group-text` y botones directo en `.input-group`. Un par de reglas en `laravel-admin.css` marcan en rojo el label y el control cuando hay `.has-error`.
- Form → card. Footer con flex: reset a la izquierda, submit y checkboxes de "después de guardar" a la derecha (nativos, sin iCheck). Tabs del form y hasMany-tab con `nav-item`/`nav-link`. `hide` → `d-none` en cascade groups, selectable y belongsTo. Modal de belongsTo con markup de BS5.
- Checkbox/radio en modo botón o tarjeta: `btn-outline-secondary` y `card` en un contenedor `la-card-group` (el `.card-group` de BS5 une las cards).
- Showcase: `title` lleva `rules('required')` (`required()` solo pone el atributo HTML) y se ignora `password_virtual`. La captura de validación desactiva la validación HTML5 para que se vean los errores del servidor.
**3b, select2 4.0.3 → 4.1.0 (2026-11-04).** Vendoreado en `resources/assets/select2` (npm, shasum verificado), más `adminlte-select2.min.css` de AdminLTE 4 para que tome el look de `form-select`, incluido el modo oscuro. No hizo falta cambiar ninguna configuración JS. Los assets viejos de `AdminLTE/plugins/select2` quedan hasta la limpieza de la fase 7.
- Nuevo `tests/smoke/ui/interactions.mjs <app>`: maneja el showcase como un usuario, usando la UI de cada plugin, y verifica la DB. Cubre pjax (navegación sin recarga, submit con "continuar editando", errores de validación) y select2 (select, multipleSelect, tags nuevos, select en una fila de hasMany agregada después, valores en la edición, old input después de un error). Da **13/13 en `master` y en `ui-upgrade`**. Cada plugin va a sumar sus chequeos acá.
- Showcase: se agregaron las columnas que faltaban (`radio_button`, `radio_card`, `checkbox_button`, `checkbox_card`), porque el form nunca se había podido guardar. El hasMany en modo tabla usa su propia relación (`tableItems`), porque dos hasMany sobre `items` duplicaban `#has-many-items`. Los items tienen un `select('kind')`.
**3c, bootstrap-switch → `form-switch` nativo (2026-11-04).**
- Form: el checkbox visible sigue alimentando el input oculto con `on`/`off` y `prepare()` los mapea a los valores de `states()`, así que el contrato con el backend no cambia. El color de "on" llena el switch encendido y el de "off", si no es el gris por defecto, tiñe el borde del apagado. Por ejemplo, sullair-click usa `on = primary` y `off = danger` en los 18 switches. Los textos (SI/NO) no se muestran, como se decidió. `setSize()` se mantiene en la API, pero no tiene efecto.
- Grid (`->switch()`, `->switchGroup()`): switch nativo que guarda con el mismo PUT inline. Si el guardado falla, vuelve al estado anterior; antes usaba un AJAX síncrono para cancelar el cambio.
- `SwitchField::colorClasses($states)` lo comparten form y grid.
- `interactions.mjs`: 20/20. Cubre switch encendido/apagado en alta y edición, old input después de un error, switch en una fila de hasMany agregada después y switch del grid en los dos sentidos. Hasta la fase 4, el script reemplaza `$.fn.editable` por una función vacía en la página del grid, porque todos los scripts de una página corren dentro de un solo `$(function(){})` y el error de `editable()` cortaba los demás.
- Hallazgo colateral en `master` (83c282f2): `Grid\Displayers\Radio` con valor `null` hacía caer todo el grid con un 500 (`Arr::get()` con clave null devuelve el array entero). El smoke test no lo veía porque laravel-admin renderiza las excepciones dentro de una página 200. Ahora `check()` también falla en ese caso.
**3d, bootstrap-fileinput 4.5.2 → 5.5.4 (2026-11-04).** Se reemplazó en el mismo directorio, desde npm con shasum verificado, más el tema `fa6` (la v5 trae Bootstrap Icons por defecto). Salen `canvas-to-blob` y `purify`. Los botones son los mismos que en la 1.x: rotar, que es nuevo en la v5, queda apagado, y los archivos guardados solo muestran "borrar" con `removable()` (`initialPreviewShowDelete`). Los `.txt` muestran su contenido en vez de la URL.
- `interactions.mjs`: 30/30. Prueba que image, file, multipleImage y multipleFile lleguen al disco y a la DB, la preview en la edición, que el error de validación conserve el archivo guardado, el error de una regla `mimes` (como la de click), reemplazar un archivo y borrar con `removable()`. En `master` dan lo mismo los chequeos que no dependen del switch.
- Particularidad de la 1.x, que se mantiene: `MultipleFile::destroy()` no reindexa, así que después de borrar, la columna puede quedar como objeto JSON (`{"1": "..."}`). Reindexar rompería un segundo borrado sin recargar, porque el widget manda las claves con las que se dibujó.
- Pendiente para los plugins: fileinput, flatpickr (el datetimepicker viejo tira un error de posicionamiento con el markup nuevo), checkbox/radio sin iCheck, colorpicker, duallistbox, iconpicker. Plan de prueba por plugin: render, alta, edición, error de validación (old input), pjax y filas dinámicas de hasMany, más un chequeo de ida y vuelta contra la DB en el smoke.
