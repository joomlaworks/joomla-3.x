# News sample data: build scripts

The News sample data set exists twice in the distribution, made from the same site:

- `installation/sql/{mysql,postgresql,sqlazure}/sample_news.sql`: the installer's set (also `cli/joomla.php core:install --sample-data=news`),
  a dump of the tables it fills, like the other sample data files;
- `plugins/sampledata/blog/data/news.json`: the Sample Data plugin's set, which adds the same content to an existing site from the
  Sample Data module of the Control Panel (categories, tags, articles, menu items and modules by alias, so they get new IDs).

Both are generated: don't edit them by hand. Change `content.json` (or the scripts), then run

```sh
PHP=php8.5 docs/sample-data/news/build/build.sh /path/to/an/empty/work/folder
```

It copies this repository to `<work folder>/newsbuild`, installs it on SQLite with `core:install`, adds the content with the command
line (`import_news.py`: tags, categories, articles, with `tag:create`, `category:create` and `article:create`; `setup_site.py`: the Hammond template's settings, menus, info pages, modules),
archives two articles, normalises the dates (`fix_dates.sh`: every article created when published, the rest older), and writes the
three SQL files (`dump_news.php`) and the plugin's data (`export_plugin_data.php`). The installer and the plugin move the dates
forward when they install the set, so that the latest article is 25 minutes old.

## The content

`generate_content.py` wrote `content.json`: 10 sections of 22 articles, with placeholder (lorem ipsum) text, fictional authors and
headlines, and, in some articles, a table, a list, a quote, an image, a YouTube video or an X post matching the section. The videos
and posts (`media.json`) are real and were checked to exist and allow embedding (`verify_media.py`, through the oEmbed endpoints).

## The images

The images are bundled, in `images/sampledata/news/<section>/<section>-NN.webp` (about 11 MB). They're all CC0 (public
domain), found through Openverse (`fetch_images.py`, then `replace_images.py` for the ones showing real people, events, paintings or
archive photos), cropped to 16:9 at 1280 × 720 and converted to WebP (`convert_images.py`). `../CREDITS.md` and `../credits.json`
credit each one.
