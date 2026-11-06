# Upgrade 1.x → 2.0

La 2.0 cambia la interfaz de Bootstrap 3 + AdminLTE 2 a **Bootstrap 5.3 + AdminLTE 4**, con **Font Awesome 6**. La API PHP (Grid, Form, Show, Tree, Widgets, Actions) no cambia, y la base de datos tampoco. Lo que se rompe es el **HTML y el JS propios** de cada aplicación: vistas Blade, `->display()` que devuelven markup, herramientas del grid, scripts y CSS que apunten a clases de Bootstrap 3 o AdminLTE 2.

Requisitos: los mismos que la 1.10 (PHP ^8.0.2, Laravel 9 a 13).

Tipografía: la 2.0 usa **Nunito** (vendoreada en `google-fonts/`, ya no se incluye Source Sans Pro) con un tamaño base de **15px**. Bootstrap mide casi todo en `rem`, así que espaciados, inputs y botones escalan en la misma proporción. Para cambiarla, redefiní `--bs-font-sans-serif` en `:root, [data-bs-theme]` y `font-size` en `html` desde tu CSS.

## 1. Actualizar el paquete y los assets

```bash
composer require "encore/laravel-admin:^2.0" --with-dependencies
rm -rf public/vendor/laravel-admin            # la 2.0 borró archivos; si no, quedan sueltos los viejos
php artisan vendor:publish --tag=laravel-admin-assets --force
php artisan view:clear
```

Si la aplicación commitea `public/vendor/laravel-admin`, commiteá el resultado completo, incluidas las bajas.

Si la aplicación publicó las vistas del paquete (`resources/views/vendor/admin`), hay que rehacerlas a partir de las nuevas. Las vistas de la 1.x no funcionan con la 2.0.

## 2. Configuración (`config/admin.php`)

- `skin`: se acepta el mismo valor. `skin-<color>` colorea el header y deja el sidebar oscuro; `skin-<color>-light` deja el sidebar claro; `black` deja el header blanco. Los colores se traducen a Bootstrap (`blue` → primary, `purple` → violet, etc.). Ya no se carga ningún CSS de skin.
- `layout`: `sidebar-mini`, `sidebar-collapse` y `fixed` siguen funcionando. **`layout-boxed` y `layout-top-nav` se ignoran** (AdminLTE 4 no los tiene).
- Nuevo `color_mode` (`ADMIN_COLOR_MODE`): `light` (por defecto), `dark` o `auto` (sigue al sistema operativo). Agregalo a tu `config/admin.php` publicado si querés cambiarlo.
- Nuevo `accent_color` (`ADMIN_ACCENT_COLOR`): un color CSS (ej. `'#008657'`) para el logo, los links del breadcrumb, la página activa del paginador, la barra de carga y el título del login. Los botones y demás elementos `primary` siguen con el azul de Bootstrap. Vacío: los colores de Bootstrap. En tu CSS está disponible como `var(--la-accent)`. Si lo ponés en `.env`, va entre comillas (`ADMIN_ACCENT_COLOR="#008657"`): sin comillas el `#` empieza un comentario.

## 3. Base de datos

No hace falta migrar nada.

- Los íconos guardados con nombres de Font Awesome 4 (`admin_menu.icon`, campos `icon()`) se siguen viendo gracias a los v4-shims de FA6. El iconpicker sigue guardando nombres de FA4, así que una base editada desde la 2.0 sigue siendo compatible con la 1.x.
- Los valores que guardan los campos no cambian. Switch, fechas con su formato, select, checkbox, tags y uploads guardan exactamente lo mismo que en la 1.x.

## 4. HTML propio: Bootstrap 3 / AdminLTE 2 → Bootstrap 5 / AdminLTE 4

Aplica a vistas Blade propias, a closures de `->display()`, a herramientas del grid (`Grid\Tools\AbstractTool`), a `Admin::navbar()` y a widgets armados a mano.

| 1.x | 2.0 |
|---|---|
| `box`, `box-header with-border`, `box-title`, `box-body`, `box-footer`, `box-tools` | `card`, `card-header`, `card-title`, `card-body`, `card-footer`, `card-tools` |
| `box box-primary` (borde de color) / `box-solid` | `card card-primary card-outline` / `card card-primary` |
| `label label-success` | `badge text-bg-success` |
| `badge bg-green` | `badge text-bg-success` |
| `pull-left` / `pull-right` | `float-start` / `float-end` (o mejor flex: `d-flex`, `ms-auto`) |
| `text-left` / `text-right` | `text-start` / `text-end` |
| `hidden`, `hide`, `hidden-xs` | `d-none`, `d-none d-sm-inline` |
| `sr-only` | `visually-hidden` |
| `btn-default` | `btn-outline-secondary` (o `btn-secondary`) |
| `btn-xs`, `btn-flat` | `btn-sm`, sin equivalente |
| `btn-twitter`, `btn-dropbox`, `btn-facebook`… (AdminLTE 2) | colores de Bootstrap (`btn-info`, `btn-primary`…) |
| `form-group` + `control-label` + `help-block` | `mb-3` (o `row mb-3`) + `form-label` / `col-form-label` + `form-text` |
| `has-error` en el grupo | `is-invalid` en el control + `invalid-feedback` |
| `input-group-addon`, `input-group-btn` | `input-group-text`, botones directo en `.input-group` |
| `checkbox`, `radio`, `checkbox-inline` | `form-check`, `form-check-inline`, `form-check-input`, `form-check-label` |
| `<span class="caret">` | nada (lo dibuja `.dropdown-toggle`) |
| `<li class="divider">` | `<li><hr class="dropdown-divider"></li>` |
| `<li><a href>` dentro de un `dropdown-menu` | `<li><a class="dropdown-item" href>` |
| `<button class="close">&times;</button>` | `<button class="btn-close"></button>` (va después del título en los modales) |
| `panel panel-default` / `well` | `card` |
| `img-responsive`, `img-circle` | `img-fluid`, `rounded-circle` |
| `glyphicon glyphicon-x` | `fa fa-x` |
| `.in` (estado abierto) | `.show` |

Atributos de datos: `data-toggle`, `data-target`, `data-dismiss`, `data-placement`, `data-content`… pasan a **`data-bs-toggle`, `data-bs-target`, `data-bs-dismiss`, `data-bs-placement`, `data-bs-content`…**. Bootstrap 5 ignora los nombres viejos. `data-toggle="buttons"` ya no existe: usá un botón común y alterná `active` desde tu propio JS.

Elementos agregados al navbar con `Admin::navbar()`: tienen que ser `<li class="nav-item"><a class="nav-link">…</a></li>`.

## 5. JavaScript

- **Bootstrap 5 sigue funcionando a través de jQuery** (`$(el).modal('show')`, `.tab('show')`, `.popover()`, `.tooltip()`, eventos `shown.bs.*`). Ojo: maneja los `data-bs-toggle` en la **fase de captura**, antes que los handlers de la página. Si tu handler tiene que preparar algo antes de que abra un collapse o un modal, abrilo vos con `bootstrap.Collapse.getOrCreateInstance(el).show()` y sacá el `data-bs-toggle`.
- `$.fn.button('loading' | 'reset')` ya no existe → **`$(btn).laLoading(true | false)`** (deshabilita el botón y muestra su `data-loading-text`).
- iCheck se fue: no hay `$.fn.iCheck` ni eventos `ifChecked`/`ifChanged`/`ifClicked`. Los checkbox y radio son nativos: escuchá `change`. Para marcar desde JS disparando `change`: **`$(input).laCheck(true | false)`** y **`$(input).laToggle()`**.
- **Fechas:** el datetimepicker de eonasdan y **moment** ya no se cargan. Los campos de fecha usan flatpickr detrás de `$.admin.datetimepicker(selector, opciones)` y `$.admin.datetimeRange(inicio, fin, opciones)`, que aceptan las opciones de la 1.x (`format` de moment, `locale`, `minDate`, `maxDate`, `stepping`, `daysOfWeekDisabled`, `disabledDates`, `enabledDates`). No hay `dp.change` ni `data('DateTimePicker')`: usá `input._flatpickr` o el evento `change`. Al abrir un campo vacío ya no se completa la fecha de hoy. Si tu JS usa moment, cargalo vos.
- **Grid `->editable()`:** la firma es la misma, pero ya no usa x-editable. Dibuja el inline-edit del paquete (texto, textarea, select, fechas) en un popover de BS5. No hay `<a class="grid-editable-…">` ni `$.fn.editable`.
- bootstrap-switch se fue: `->switch()` (form y grid) usa el switch nativo de BS5 y conserva los valores de `states()`. Los textos ON/OFF no se muestran. El color de "on" llena el switch y el de "off" tiñe el borde.
- El init global de popovers solo toma `data-bs-toggle="popover"`.
- **Filtro del grid:** el formulario se abre como dropdown del botón "Filtro" (antes era un panel arriba de la tabla). `expandFilter()` y los filtros aplicados ya no lo abren al cargar: marcan el botón como activo. Si una vista propia del grid no dibuja las herramientas del header (`renderHeaderTools()`) antes de `renderFilter()`, el filtro sigue saliendo como panel.

## 6. Plugins

| Plugin (1.x) | 2.0 | Diferencias visibles |
|---|---|---|
| select2 4.0.3 | select2 4.1.0 + tema de AdminLTE 4 | ninguna |
| bootstrap-switch | switch nativo de BS5 | sin textos ON/OFF |
| bootstrap-fileinput 4.5 | 5.5.4 (tema `fa6`) | los `.txt` muestran su contenido en la preview |
| eonasdan datetimepicker | flatpickr 4.6 (`$.admin.datetimepicker`) | solo año o mes: grilla de meses; no completa "hoy" al abrir |
| iCheck | `form-check` nativo | — |
| bootstrap-colorpicker | Coloris | mismo formato de salida (`hex()`, `rgb()`, `rgba()`, y por defecto hex si el color es opaco o rgba si es translúcido) |
| bootstrap-duallistbox 3 | bootstrap4-duallistbox 4 | botones con texto en vez de glyphicons |
| x-editable | inline-edit del paquete | popover de BS5 |
| fontawesome-iconpicker | el mismo | — |

## 7. Cómo encontrar lo que hay que adaptar

Desde la raíz de la aplicación:

```bash
grep -rnE "box-|class=\"box|label label-|pull-(left|right)|btn-default|btn-xs|hidden-xs|sr-only|input-group-addon|control-label|help-block|has-error|class=\"caret|class=\"divider|panel-|glyphicon|data-(toggle|target|dismiss|placement)=|\.button\(|iCheck|ifChecked|ifChanged|bootstrapSwitch|datetimepicker|DateTimePicker|dp\.change|moment\(|\.editable\(" app resources public/js --include='*.php' --include='*.js' --include='*.blade.php'
```

Después recorré cada pantalla del panel con la consola del navegador abierta. Los scripts de la página corren en un solo bloque, así que un error de JS corta los que vienen después.
