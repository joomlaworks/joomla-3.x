#!/bin/sh
# Rebuilds a sample data set from its folder (docs/sample-data/<set>: content.json, setup.py, set.json):
# installation/sql/{mysql,postgresql,sqlazure}/sample_<set>.sql and the Sample Data plugin's plugins/sampledata/blog/data/<set>.json.
# It installs a throwaway site from this repository (SQLite, with the command line), adds the content with the command line,
# then dumps it.
# Usage: docs/sample-data/build/build.sh <news|blog> <empty work folder>   (PHP 7.4+ CLI with pdo_sqlite, Python 3, rsync; PHP=php8.5 to choose)
set -e
HERE=$(cd "$(dirname "$0")" && pwd)
SET=$1
DIR="$HERE/../$SET"
REPO=$(cd "$HERE/../../.." && pwd)
PHP=${PHP:-php}; export PHP
B="$2/${SET}build"
[ -f "$DIR/set.json" ] || { echo "No sample data set \"$SET\"" >&2; exit 2; }
SITE=$(python3 -c "import json,sys; print(json.load(open(sys.argv[1]))['siteName'])" "$DIR/set.json")
PAGES=$(python3 -c "import json,sys; print(json.load(open(sys.argv[1]))['pagesCategory'])" "$DIR/set.json")
ARCHIVE=$(python3 -c "import json,sys; print(','.join(\"'%s'\" % a for a in json.load(open(sys.argv[1])).get('archive', [])))" "$DIR/set.json")
rm -rf "$B"
rsync -a --exclude .git --exclude docs --exclude .github --exclude .claude "$REPO/" "$B/"
rm -f "$B/configuration.php"
(cd "$B" && $PHP cli/joomla.php core:install --site-name="$SITE" --admin-email=admin@example.com --admin-username=admin --no-interaction)
python3 "$HERE/import.py" "$B" "$HERE/q.php" "$DIR"
python3 "$DIR/setup.py" "$B" "$HERE/q.php"
# Archived articles, for the archive view
if [ -n "$ARCHIVE" ]; then
	IDS=$($PHP "$HERE/q.php" "$B" "SELECT id FROM #__content WHERE alias IN ($ARCHIVE)" | grep -v -- '--')
	(cd "$B" && $PHP cli/joomla.php article:archive $IDS)
fi
sh "$HERE/fix_dates.sh" "$B" "$PAGES"
$PHP "$HERE/dump.php" "$B" "$REPO" "$SET"
$PHP "$HERE/export.php" "$B" "$REPO" "$SET"
