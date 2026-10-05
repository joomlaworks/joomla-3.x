# Sample data: build scripts

The distribution's two sample data sets, **News** (for the Hammond template) and **Blog** (for the Finch template), each exist
twice, made from the same site:

- `installation/sql/{mysql,postgresql,sqlazure}/sample_<set>.sql`: the installer's set (also `cli/joomla.php core:install
  --sample-data=<set>`), a dump of the tables it fills;
- `plugins/sampledata/blog/data/<set>.json`: the Sample Data plugin's set, which adds the same content to an existing site from the
  Sample Data module of the Control Panel (categories, tags, articles, menu items and modules by alias, so they get new IDs).

Both are generated: don't edit them by hand. Change a set's `content.json` (or its `setup.py`), then run

```sh
PHP=php8.5 docs/sample-data/build/build.sh news /path/to/an/empty/work/folder
PHP=php8.5 docs/sample-data/build/build.sh blog /path/to/an/empty/work/folder
```

## How a build works

`build/build.sh <set>` copies this repository to `<work folder>/<set>build`, installs it on SQLite with `core:install`, and then:

1. `build/import.py` adds the set's `content.json` with the command line: tags (`tag:create`), categories (`category:create`) and
   articles (`article:create`), then the articles' hits;
2. `<set>/setup.py` sets up the rest: the template's settings, menus, pages and modules;
3. the articles listed under `archive` in `<set>/set.json` are archived (for the archive view);
4. `build/fix_dates.sh` normalises the dates: every article created when published, the pages, categories and tags before them;
5. `build/dump.php` writes the three SQL files, and `build/export.php` the plugin's data.

`<set>/set.json` names the set's site, template, pages category and archived articles, and the menu types the plugin gives its menus
on an existing site (so they never mix with the site's own). The installer and the plugin move the dates forward when they install a
set, so that its latest article is 25 minutes old.

## The content

`news/generate_content.py` wrote `news/content.json`: 10 sections of 22 articles, with placeholder (lorem ipsum) text, fictional
authors and headlines, and, in some articles, a table, a list, a quote, an image, a YouTube video or an X post matching the section.
The videos and posts (`news/media.json`) are real and were checked to exist and allow embedding (`news/verify_media.py`, through the
oEmbed endpoints).

`blog/generate_content.py` writes `blog/content.json` from it: 20 of the News set's opinion articles as the posts of a personal blog
(one fictional author, four topics, a post every few days).

## The images

Both sets use the images bundled in `images/sampledata/news/<section>/<section>-NN.webp` (about 11 MB). They're all CC0 (public
domain), found through Openverse (`news/fetch_images.py`, then `news/replace_images.py` for the ones showing real people, events,
paintings or archive photos), cropped to 16:9 at 1280 × 720 and converted to WebP (`news/convert_images.py`). `news/CREDITS.md`
and `news/credits.json` credit each one.
