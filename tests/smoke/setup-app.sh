#!/usr/bin/env bash
#
# Create a fresh Laravel app that installs this package from the working copy
# (path repository) and runs `admin:install`, ready for the smoke tests.
#
# usage: tests/smoke/setup-app.sh <laravel-major> <app-dir>
#
set -euo pipefail

LARAVEL=$1
APP=$2
PKG=$(cd "$(dirname "$0")/../.." && pwd)
VERSION=$(php -r 'preg_match("/const VERSION = \x27([^\x27]+)\x27/", file_get_contents($argv[1]), $m); echo $m[1];' "$PKG/src/Admin.php")

# Composer >= 2.9 refuses packages with security advisories, and every Laravel 9-11
# release has unpatched ones (EOL). This throwaway app only exists to test against them.
export COMPOSER_NO_BLOCKING=1 COMPOSER_NO_SECURITY_BLOCKING=1

rm -rf "$APP"
composer create-project "laravel/laravel:^$LARAVEL.0" "$APP" --prefer-dist --no-interaction --quiet
APP=$(cd "$APP" && pwd)
cd "$APP"

composer config repositories.fork "{\"type\":\"path\",\"url\":\"$PKG\",\"options\":{\"symlink\":true,\"versions\":{\"encore/laravel-admin\":\"$VERSION\"}}}"

# Laravel 9/10 apps still need doctrine/dbal for admin:make and column renames.
if [ "$LARAVEL" -le 10 ]; then
    composer require "encore/laravel-admin:$VERSION" "doctrine/dbal:^3.0" -W --no-interaction --quiet
else
    composer require "encore/laravel-admin:$VERSION" -W --no-interaction --quiet
fi

# sqlite database, deprecations to the log, in-memory sqlite for phpunit
touch database/database.sqlite
php -r '
$app = $argv[1];
$env = file_get_contents("$app/.env");
$set = [
    "DB_CONNECTION"            => "sqlite",
    "DB_DATABASE"              => "$app/database/database.sqlite",
    "LOG_DEPRECATIONS_CHANNEL" => "single",
    "LOG_LEVEL"                => "debug",
];
foreach ($set as $key => $value) {
    $line = "$key=$value";
    $env = preg_match("/^$key=.*$/m", $env)
        ? preg_replace("/^$key=.*$/m", $line, $env)
        : rtrim($env)."\n$line\n";
}
file_put_contents("$app/.env", $env);

$xml = file_get_contents("$app/phpunit.xml");
$xml = preg_replace("#<!--\s*(<env name=\"DB_(CONNECTION|DATABASE)\" value=\"[^\"]*\"/>)\s*-->#", "$1", $xml);
file_put_contents("$app/phpunit.xml", $xml);
' "$APP"

php artisan vendor:publish --provider="Encore\Admin\AdminServiceProvider" --quiet
php artisan admin:install --quiet
cp "$PKG/tests/smoke/AdminSmokeTest.php" tests/Feature/

echo "$(php artisan --version) ready at $APP (encore/laravel-admin $VERSION from $PKG)"
