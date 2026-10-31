#!/usr/bin/env bash
#
# Walk the admin panel of an app created by setup-app.sh through a real HTTP
# server: login with CSRF, persisted session + flash messages, main pages,
# CSV export and published assets. Exits non-zero on any failure.
#
# usage: tests/smoke/http-smoke.sh <app-dir> [port]
#
set -uo pipefail

APP=$(cd "$1" && pwd)
PORT=${2:-8765}
BASE="http://127.0.0.1:$PORT"
JAR=$(mktemp)
FAILED=0

cd "$APP"
rm -f storage/logs/laravel.log

# same as `artisan serve`: the router resolves the public path from the working directory
(cd public && exec php -S "127.0.0.1:$PORT" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php >/dev/null 2>&1) &
SERVER=$!
trap 'kill $SERVER 2>/dev/null; wait $SERVER 2>/dev/null; rm -f "$JAR"' EXIT

for _ in $(seq 1 40); do
    curl -s -o /dev/null "$BASE" && break
    sleep 0.25
done

expect() {
    local expected=$1 label=$2
    shift 2
    local status
    status=$(curl -s -o /dev/null -w '%{http_code}' -c "$JAR" -b "$JAR" "$@")
    if [ "$status" = "$expected" ]; then
        echo "ok   $status $label"
    else
        echo "FAIL $status (expected $expected) $label"
        FAILED=1
    fi
}

TOKEN=$(curl -s -c "$JAR" -b "$JAR" "$BASE/admin/auth/login" | grep -oE 'name="_token" value="[^"]+"' | head -1 | sed -E 's/.*value="([^"]+)"/\1/')
if [ -z "$TOKEN" ]; then
    echo "FAIL could not read the CSRF token from the login page"
    FAILED=1
fi

expect 302 "login POST" -d "_token=$TOKEN&username=admin&password=admin" "$BASE/admin/auth/login"

# the toastr flashed on login must survive the session round trip (json serialization on Laravel 13)
DASHBOARD=$(curl -s -c "$JAR" -b "$JAR" "$BASE/admin")
if [[ "$DASHBOARD" == *"toastr.success('"* ]]; then
    echo "ok   login toastr rendered"
else
    echo "FAIL login toastr not rendered on /admin"
    FAILED=1
fi

for uri in /admin /admin/auth/users /admin/auth/users/create /admin/auth/users/1/edit /admin/auth/users/1 \
    /admin/auth/roles /admin/auth/permissions /admin/auth/menu /admin/auth/logs /admin/auth/setting \
    "/admin/auth/users?_export_=all"; do
    expect 200 "GET $uri" "$BASE$uri"
done

# every script and stylesheet the login page and the layout reference must be published
LOGIN=$(curl -s "$BASE/admin/auth/login")
ASSETS=$(printf '%s\n%s' "$LOGIN" "$DASHBOARD" | grep -oE '(src|href)="[^"]+\.(js|css)"' | sed -E 's/^(src|href)="//; s/"$//' | grep "^$BASE/" | sort -u)
if [ -z "$ASSETS" ]; then
    echo "FAIL no local assets found on the login page or the dashboard"
    FAILED=1
fi
for url in $ASSETS; do
    expect 200 "GET ${url#$BASE}" "$url"
done

if [ -f storage/logs/laravel.log ] && grep -qE '\.(ERROR|WARNING|CRITICAL)' storage/logs/laravel.log; then
    echo "FAIL errors or deprecations logged:"
    grep -E '\.(ERROR|WARNING|CRITICAL)' storage/logs/laravel.log | cut -c1-400 | head -20
    FAILED=1
fi

exit $FAILED
