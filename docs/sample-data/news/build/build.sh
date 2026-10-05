#!/bin/sh
# Rebuilds the News sample data from content.json: installation/sql/{mysql,postgresql,sqlazure}/sample_news.sql and the
# Sample Data plugin's plugins/sampledata/blog/data/news.json. It installs a throwaway site from this repository (SQLite,
# with the command line), adds the content with the command line, then dumps it.
# Usage: docs/sample-data/news/build/build.sh <empty work folder>   (PHP 7.4+ CLI with pdo_sqlite, Python 3, rsync; PHP=php8.5 to choose)
set -e
HERE=$(cd "$(dirname "$0")" && pwd)
REPO=$(cd "$HERE/../../../.." && pwd)
PHP=${PHP:-php}; export PHP
B="$1/newsbuild"
rm -rf "$B"
rsync -a --exclude .git --exclude docs --exclude .github --exclude .claude "$REPO/" "$B/"
rm -f "$B/configuration.php"
(cd "$B" && $PHP cli/joomla.php core:install --site-name="Hammond News" --admin-email=admin@example.com --admin-username=admin --no-interaction)
python3 "$HERE/import_news.py" "$B" "$HERE/q.php"
python3 "$HERE/setup_site.py" "$B" "$HERE/q.php"
# Two archived articles, for the archive view
IDS=$($PHP "$HERE/q.php" "$B" "SELECT id FROM #__content WHERE alias IN ('monsoon-rains-bring-relief-to-parched-farmland', 'two-nations-reopen-land-border-after-long-closure')" | grep -v -- '--')
(cd "$B" && $PHP cli/joomla.php article:archive $IDS)
sh "$HERE/fix_dates.sh" "$B"
$PHP "$HERE/dump_news.php" "$B" "$REPO"
$PHP "$HERE/export_plugin_data.php" "$B" "$REPO"
