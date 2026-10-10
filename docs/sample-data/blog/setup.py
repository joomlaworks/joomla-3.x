"""Builds the Blog set's structure on its build site: the Finch template's settings, menus, pages and modules.
Usage: setup.py <site> <q.php>"""
import json, os, subprocess, sys
PHP = os.environ.get('PHP', 'php')

D = sys.argv[1]
Q = sys.argv[2]   # q.php helper (SQL through Joomla)

def cli(*args, stdin=None):
    r = subprocess.run([PHP, 'cli/joomla.php', *args, '--format=json'], cwd=D, input=stdin, capture_output=True, text=True)
    try:
        d = json.loads(r.stdout)
    except Exception:
        d = {'success': False, 'error': r.stdout[-400:] + r.stderr[-400:]}
    if not d.get('success'):
        print('FAIL', args[:3], d.get('error'))
    return d

def sql(*queries):
    return subprocess.run([PHP, Q, D, *queries], cwd=D, capture_output=True, text=True).stdout

def created_id(d):
    data = d.get('data', {})
    return int(data.get('id', 0) or 0)

def cat_id(alias):
    return sql("SELECT id FROM #__categories WHERE extension='com_content' AND alias='%s'" % alias).split('\n')[0].strip()

topics = [('places', 'Places'), ('ideas', 'Ideas'), ('technology', 'Technology'), ('work-life', 'Work & Life')]
# Each topic's colour in Finch: a tone class in its menu item's Page Class
tones = {'places': 'tone-mint', 'ideas': 'tone-coral', 'technology': 'tone-sky', 'work-life': 'tone-sunflower'}

# --- Template --------------------------------------------------------------------------------------------------------
params = json.dumps({
    'siteName': 'Field Notes',
    'greeting': 'Hi, I\'m Finch.',
    'tagline': 'Essays on cities, ideas and the way we live.',
    'authorBio': 'Finch Hartley writes about cities, classrooms and the small technologies that shape everyday life. Finch has kept this notebook since 2016.',
    'footerText': 'Field Notes is a placeholder blog made for demonstrating Joomla 3.x UTD. Finch Hartley and everything written here are fictional.',
    'social_x': '#', 'social_instagram': '#', 'social_facebook': '#', 'social_linkedin': '#', 'social_rss': '',
})
sql("UPDATE #__template_styles SET home = '0' WHERE client_id = 0",
    "UPDATE #__template_styles SET home = '1', title = 'Finch - Default', params = '%s' WHERE client_id = 0 AND template = 'finch'" % params.replace("'", "''"))

# --- Menus -----------------------------------------------------------------------------------------------------------
sql("INSERT INTO #__menu_types (asset_id, menutype, title, description, client_id) VALUES (0, 'footermenu', 'Footer Menu', 'Contact and privacy pages', 0)")

lists = {
    'show_intro': '0', 'num_leading_articles': '1', 'num_intro_articles': '9', 'num_columns': '1', 'num_links': '0',
    'orderby_pri': 'none', 'orderby_sec': 'rdate', 'order_date': 'published', 'show_pagination': '2', 'show_pagination_results': '0',
    'show_description': '1', 'show_description_image': '0', 'show_subcategory_content': '0', 'show_category_title': '1',
}
sql("UPDATE #__menu SET params = '%s' WHERE home = 1 AND client_id = 0" % json.dumps(dict(lists, show_page_heading='0')).replace("'", "''"))
for alias, title in topics:
    cli('menu:item:create', '--state=published', '--menu=mainmenu', '--title=' + title, '--alias=' + alias, '--category-blog=' + alias,
        '--params=' + json.dumps(dict(lists, pageclass_sfx=' ' + tones[alias])))

# Pages: a category of their own; each with a menu item, which shows it as a page (no byline, no sidebar)
cli('category:create', '--title=Pages', '--alias=pages', '--description=<p>About, contact and privacy.</p>')
lorem = ('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim '
         'veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>\n'
         '<h2>Lorem ipsum dolor</h2>\n<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint '
         'occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>')
pages = [
    ('about', 'About', 'mainmenu', '<p>Hello, I\'m Finch Hartley. I write about cities, classrooms and the small technologies that shape everyday life, and '
     'this is the notebook where those pieces end up: one essay every few days, written slowly.</p>\n'
     '<figure><img src="images/sampledata/news/opinion/opinion-03.webp" alt="A notebook on a sunny lawn" width="1280" height="720" loading="lazy" /></figure>\n'
     + lorem),
    ('contact', 'Contact', 'footermenu', '<p>Questions, tips or a well-argued disagreement are all welcome. I read every message, though I can\'t always answer '
     'quickly.</p>\n<p>Write to <a href="mailto:hello@example.com">hello@example.com</a>.</p>\n' + lorem),
    ('privacy-policy', 'Privacy Policy', 'footermenu', '<p>This blog collects as little as it can.</p>\n' + lorem),
]
page_params = json.dumps({'show_title': '1', 'show_intro': '1', 'show_category': '0', 'show_author': '0', 'show_create_date': '0',
                          'show_publish_date': '0', 'show_hits': '0', 'show_tags': '0', 'show_print_icon': '0', 'show_email_icon': '0'})
for alias, title, menutype, text in pages:
    aid = created_id(cli('article:create', '--state=published', '--title=' + title, '--alias=' + alias, '--category=pages',
                         '--author-alias=Finch Hartley', '--text-file=-', stdin=text))
    cli('menu:item:create', '--state=published', '--menu=' + menutype, '--title=' + title, '--alias=' + alias, '--article=%d' % aid, '--params=' + page_params)

# --- Modules ---------------------------------------------------------------------------------------------------------
# joomla.sql's modules of a site without sample data are in Hammond's positions; the same names in Finch: replace them
positions = "('navigation','search','above-content','sidebar','below-content','footer','trending','megamenu','megamenu-aside','frontpage')"
sql("DELETE FROM #__modules_menu WHERE moduleid IN (SELECT id FROM #__modules WHERE client_id = 0 AND position IN %s)" % positions,
    "DELETE FROM #__modules WHERE client_id = 0 AND position IN %s" % positions)

catids = [cat_id(alias) for alias, title in topics]

def menu(title, position, menutype):
    return cli('module:create', '--type=mod_menu', '--title=' + title, '--position=' + position, '--show-title=no', '--pages=all', '--state=published',
               '--params=' + json.dumps({'menutype': menutype, 'startLevel': '1', 'endLevel': '1', 'showAllChildren': '1', 'layout': '_:default',
                                         'cache': '1', 'module_tag': 'div', 'style': '0'}))

def posts(title, position, layout, count, ordering, sfx='', cache='1'):
    return cli('module:create', '--type=mod_articles_category', '--title=' + title, '--position=' + position, '--show-title=yes', '--pages=all',
               '--state=published', '--params=' + json.dumps({
                   'mode': 'normal', 'count': str(count), 'show_front': 'show', 'article_ordering': ordering, 'article_ordering_direction': 'DESC',
                   'catid': catids, 'category_filtering_type': '1', 'show_child_category_articles': '0', 'show_date': '1',
                   'show_date_field': 'publish_up', 'show_category': '1', 'show_author': '1', 'show_introtext': '0', 'layout': 'finch:' + layout,
                   'moduleclass_sfx': sfx, 'owncache': cache, 'cache_time': '900', 'show_on_article_page': '1'}))

menu('Main Menu', 'navigation', 'mainmenu')
cli('module:create', '--type=mod_search', '--title=Search', '--position=search', '--show-title=no', '--pages=all', '--state=published',
    '--params=' + json.dumps({'label': 'Search', 'width': '', 'text': 'Search the blog…', 'button': '1', 'button_pos': 'right', 'button_text': 'Search',
                              'set_itemid': '0', 'opensearch': '0'}))
cli('module:create', '--type=mod_custom', '--title=About this blog', '--position=sidebar', '--show-title=yes', '--pages=all', '--state=published',
    '--content=<p>Hello, I\'m Finch. I write about cities, classrooms and the small technologies that shape everyday life, one essay every few days.</p>',
    '--params=' + json.dumps({'moduleclass_sfx': 'about', 'prepare_content': '0'}))
posts('Popular posts', 'sidebar', 'compact', 5, 'a.hits')
cli('module:create', '--type=mod_tags_popular', '--title=Topics', '--position=sidebar', '--show-title=yes', '--pages=all', '--state=published',
    '--params=' + json.dumps({'maximum': '12', 'timeframe': 'alltime', 'order_value': 'title', 'order_direction': '0', 'display_count': '0',
                              'no_results_text': '0', 'layout': '_:default'}))
posts('Read next', 'below-content', 'cards', 4, 'random', cache='0')
menu('Footer Menu', 'footer', 'footermenu')

print(sql("SELECT position, count(*) FROM #__modules WHERE client_id=0 AND published=1 AND position IN %s GROUP BY position" % positions))
