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
  "encore/laravel-admin": "^1.9"
}
```

Composer toma las versiones de los tags de este repo (no hace falta Packagist). Para traer una versión nueva:

```bash
composer update encore/laravel-admin --with-dependencies
```

En Laravel 9/10, si el proyecto usa `admin:make` o migraciones con `renameColumn`/`change()`, tiene que pedir `doctrine/dbal` (`^3.0`) en su propio `composer.json`.

## Versiones

Semver, siguiendo la línea 1.x de upstream. Cada release es un tag `vX.Y.Z` con su [GitHub Release](https://github.com/AgenciaEgo/laravel-admin/releases).

- `1.9.0` — primer release del fork: soporte Laravel 9–13 y PHP 8.4+.

## Tests

El CI ([.github/workflows/tests.yml](.github/workflows/tests.yml)) crea apps Laravel limpias de cada versión soportada, instala el paquete y recorre el panel completo (test de feature + servidor HTTP real). Para correrlo localmente:

```bash
tests/smoke/setup-app.sh 13 /tmp/app
(cd /tmp/app && vendor/bin/phpunit --filter AdminSmokeTest)
tests/smoke/http-smoke.sh /tmp/app
```

## Documentación

La documentación de uso de upstream está en [docs/en](docs/en). Las notas internas del fork (contexto, estructura del código, puntos sensibles para los upgrades) están en [CLAUDE.md](CLAUDE.md).

## Licencia

MIT, igual que el original. Ver [LICENSE](LICENSE).
