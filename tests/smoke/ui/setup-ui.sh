#!/usr/bin/env bash
#
# Install the UI showcase (every form field, grid feature, show field and widget)
# into an app created by tests/smoke/setup-app.sh, for screenshots.mjs.
#
# usage: tests/smoke/ui/setup-ui.sh <app-dir>
#
set -euo pipefail

APP=$(cd "$1" && pwd)
UI=$(cd "$(dirname "$0")" && pwd)

cp -R "$UI/app/." "$APP/app/"
cp -R "$UI/database/." "$APP/database/"

cd "$APP"

# showcase routes inside the admin group
php -r '
$file = $argv[1];
$routes = file_get_contents($file);
if (strpos($routes, "ShowcaseController") === false) {
    $routes = str_replace(
        "\$router->get(\x27/\x27, \x27HomeController@index\x27)->name(\x27home\x27);",
        "\$router->get(\x27/\x27, \x27HomeController@index\x27)->name(\x27home\x27);\n    \$router->resource(\x27showcases\x27, ShowcaseController::class);\n    \$router->get(\x27showcase-widgets\x27, \x27ShowcaseController@widgets\x27);",
        $routes
    );
    file_put_contents($file, $routes);
}
' app/Admin/routes.php

# public upload disk with a sample image and file
php -r '
$file = $argv[1];
$config = file_get_contents($file);
if (strpos($config, "\x27admin\x27 =>") === false) {
    $disk = "\x27admin\x27 => [\n            \x27driver\x27 => \x27local\x27,\n            \x27root\x27 => public_path(\x27uploads\x27),\n            \x27url\x27 => \x27/uploads\x27,\n            \x27visibility\x27 => \x27public\x27,\n        ],\n\n        ";
    $config = preg_replace("/(\x27disks\x27 => \[\s*\n\s*)/", "$1$disk", $config, 1);
    file_put_contents($file, $config);
}
' config/filesystems.php
mkdir -p public/uploads/images public/uploads/files
cp public/vendor/laravel-admin/AdminLTE/dist/img/user2-160x160.jpg public/uploads/images/sample.jpg
echo "sample file" > public/uploads/files/sample.txt

php artisan migrate --force --quiet
php artisan db:seed --class='Database\Seeders\ShowcaseSeeder' --force --quiet

echo "UI showcase installed in $APP: /admin/showcases, /admin/showcase-widgets"
