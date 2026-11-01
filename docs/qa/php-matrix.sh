#!/bin/sh
# Builds a site from the release package (what git archive of HEAD ships), installs it with the News sample data, moves it
# to MariaDB (or MySQL), then serves it with each PHP version in turn (official php:<version>-cli Docker images plus mysqli/pdo_mysql)
# and crawls the administrator and the site with docs/crawl/crawl.mjs. PHP's messages of each version are kept in
# <work>/site/logs/php-<version>.log (E_ALL); the summary lists the crawl's findings and the log lines per version.
#
# Usage: sh docs/qa/php-matrix.sh [versions...]     (from the repository's root; default 7.1 7.4 8.2 8.5)
#   WORK=<folder>    where the site and logs go (default: a new folder in /tmp); an existing site there is reused
#                    (the administrator's password, user admin, is in <work>/password)
#   PORT=<number>    the first port (default 8921; one per version, on 127.0.0.1)
#   KEEP=1           leave the containers running afterwards (the database server, the sites' servers)
#   DB_IMAGE=<image> the database server (default mariadb:11.4; e.g. mysql:8.4, whose caching_sha2_password PHP 7.1's
#                    mysqlnd in these images can't complete: use MariaDB, or MySQL with mysql_native_password, for 7.1)
# Needs Docker, Node 22+ and Chrome (the crawler). The installer needs PHP 7.4+ (SQLite), so it runs on 8.5; PHP 7.1
# serves the site once it is on MySQL. Containers and the network are named j3qa-*.
set -e

REPO=$(pwd)
VERSIONS=${*:-7.1 7.4 8.2 8.5}
WORK=${WORK:-$(mktemp -d /tmp/j3qa-XXXXXX)}
PORT=${PORT:-8921}
NET=j3qa
DB_IMAGE=${DB_IMAGE:-mariadb:11.4}
DB=j3qa-db-$(echo "$DB_IMAGE" | tr -c 'a-z0-9\n' '-')
USER_ID="$(id -u):$(id -g)"

[ -f libraries/src/Version.php ] || { echo "Run it from the repository's root"; exit 2; }
echo "Work folder: $WORK"
mkdir -p "$WORK/images"

# The administrator's password: made once per work folder and kept there, so a reused site keeps it
if [ ! -s "$WORK/password" ]; then
    (umask 077 && printf 'Qa-%s' "$(head -c 18 /dev/urandom | base64 | tr -d '/+=')" > "$WORK/password")
fi
PASSWORD=$(cat "$WORK/password")

image() {
    if ! docker image inspect "j3qa-php:$1" >/dev/null 2>&1; then
        printf 'FROM php:%s-cli\nRUN docker-php-ext-install mysqli pdo_mysql >/dev/null\n' "$1" > "$WORK/images/Dockerfile-$1"
        docker build -q -t "j3qa-php:$1" -f "$WORK/images/Dockerfile-$1" "$WORK/images" >/dev/null
    fi
}

php() {
    version=$1
    shift
    docker run --rm --network $NET -u "$USER_ID" -v "$WORK/site:/site" -w /site "j3qa-php:$version" php "$@"
}

docker network inspect $NET >/dev/null 2>&1 || docker network create $NET >/dev/null

if ! docker container inspect $DB >/dev/null 2>&1; then
    docker run -d --name $DB --network $NET -e MYSQL_ROOT_PASSWORD=rootpass -e MYSQL_USER=joomla -e MYSQL_PASSWORD=joomla \
        -e MARIADB_ROOT_PASSWORD=rootpass -e MARIADB_USER=joomla -e MARIADB_PASSWORD=joomla "$DB_IMAGE" >/dev/null
fi

# The client is mariadb in MariaDB's images, mysql in MySQL's
sql() {
    docker exec $DB sh -c 'if command -v mariadb >/dev/null; then mariadb "$@"; else mysql "$@"; fi' sh -uroot -prootpass "$@"
}

docker start $DB >/dev/null
until sql -e 'SELECT 1' >/dev/null 2>&1; do sleep 2; done

for version in $VERSIONS 8.5; do
    image "$version"
done

if [ ! -f "$WORK/site/configuration.php" ]; then
    mkdir -p "$WORK/site"
    git archive --format=tar HEAD -- ':(exclude)docs' ':(exclude).github' ':(exclude)*.md' ':(exclude).gitignore' | tar x -C "$WORK/site"
    php 8.5 cli/joomla.php core:install -n --site-name="QA PHP matrix" --admin-email=qa@example.com --admin-username=admin \
        --admin-password="$PASSWORD" --sample-data=news
    sleep 5
    sql -e "DROP DATABASE IF EXISTS j3qa; CREATE DATABASE j3qa CHARACTER SET utf8mb4; GRANT ALL ON j3qa.* TO 'joomla'@'%';" 2>/dev/null
    php 8.5 cli/joomla.php database:convert -n --to=mysqli --host=$DB --user=joomla --password=joomla --database=j3qa
fi

mkdir -p "$WORK/site/logs"
port=$PORT
summary=""

for version in $VERSIONS; do
    name="j3qa-php-$(echo "$version" | tr . -)"
    log="logs/php-$version.log"
    docker rm -f "$name" >/dev/null 2>&1 || true
    : > "$WORK/site/$log"
    docker run -d --name "$name" --network $NET -u "$USER_ID" -p "127.0.0.1:$port:8080" -v "$WORK/site:/site" -w /site \
        -e PHP_CLI_SERVER_WORKERS=4 "j3qa-php:$version" php -d error_reporting=-1 -d display_errors=0 -d log_errors=1 \
        -d error_log="/site/$log" -d memory_limit=512M -S 0.0.0.0:8080 -t /site >/dev/null
    sleep 2
    echo "== PHP $version on port $port"
    crawl=$(node "$REPO/docs/crawl/crawl.mjs" --base="http://127.0.0.1:$port" --user=admin --password="$PASSWORD" 2>&1 || true)
    echo "$crawl"
    lines=$(grep -c . "$WORK/site/$log" || true)
    summary="$summary
PHP $version: $(echo "$crawl" | tail -1); $lines lines in $log"

    if [ -z "$KEEP" ]; then
        docker rm -f "$name" >/dev/null
    fi

    port=$((port + 1))
done

[ -n "$KEEP" ] || docker stop $DB >/dev/null
echo "$summary"
echo "Logs: $WORK/site/logs; administrator: admin, password in $WORK/password"
