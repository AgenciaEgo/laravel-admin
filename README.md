# laravel-admin (fork de AgenciaEgo)

Fork de [z-song/laravel-admin](https://github.com/z-song/laravel-admin) (paquete `encore/laravel-admin`), un constructor de paneles admin/CRUD para Laravel que su autor original abandonó.

Lo mantenemos para seguir usándolo en proyectos propios (principalmente **sullair-click**) mientras subimos de versión PHP y Laravel, en lugar de reescribir esos paneles con otra herramienta.

## Compatibilidad

Laravel 9, 10, 11, 12 y 13 · PHP 8.0.2 o superior (probado en 8.3 y 8.5).

## Uso

El nombre del paquete sigue siendo `encore/laravel-admin`. En el `composer.json` del proyecto que lo usa:

```json
"repositories": [
  { "type": "vcs", "url": "https://github.com/AgenciaEgo/laravel-admin" }
],
"require": {
  "encore/laravel-admin": "dev-master as 1.8.19"
}
```

El alias `as 1.8.19` mantiene compatibles a extensiones que piden una versión `1.x` (por ejemplo, `laravel-admin-ext/helpers`).

Para traer cambios nuevos del fork:

```bash
composer update encore/laravel-admin --with-dependencies
```

## Documentación

La documentación de uso de upstream está en [docs/en](docs/en). Las notas internas del fork (contexto, estructura del código, puntos sensibles para los upgrades) están en [CLAUDE.md](CLAUDE.md).

## Licencia

MIT, igual que el original. Ver [LICENSE](LICENSE).
