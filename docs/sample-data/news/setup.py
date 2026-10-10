"""Builds the News set's structure on its build site: the Hammond template's settings, menus, info pages and modules. Re-runnable: it
deletes what it created before (modules in the template's positions) and creates them again. Usage: setup.py <site> <q.php>"""
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
    r = subprocess.run([PHP, Q, D, *queries], cwd=D, capture_output=True, text=True)
    return r.stdout

def created_id(d):
    data = d.get('data', {})
    for key in ('id', 'item', 'module', 'article', 'category', 'menuItem'):
        v = data.get(key)
        if isinstance(v, dict) and 'id' in v:
            return int(v['id'])
        if isinstance(v, int):
            return v
    return int(data.get('id', 0) or 0)

# --- Template --------------------------------------------------------------------------------------------------------
if 'hammond' not in sql("SELECT element FROM #__extensions WHERE type='template' AND element='hammond'"):
    cli('extension:discover')
    for row in cli('extension:discover:list').get('data', {}).get('extensions', []):
        if row.get('element') == 'hammond':
            cli('extension:discover:install', '--eid=%s' % row.get('extension_id', row.get('id')))
params = json.dumps({
    'siteName': 'Hammond News',
    'tagline': 'Independent journalism on politics, the economy, technology and the ideas shaping our world.',
    'footerText': 'Hammond News is a placeholder publication made for demonstrating Joomla 3.x UTD. Hammond Media Ltd., 10 Example Street, Exampletown. '
                  'Registered in Exampleland, company no. 0000000. All articles, people and figures on this site are fictional.',
    'pagesMenu': 'companymenu',
    'social_facebook': '#', 'social_x': '#', 'social_instagram': '#', 'social_youtube': '#', 'social_linkedin': '#', 'social_rss': '',
})
sql("UPDATE #__template_styles SET home = '0' WHERE client_id = 0",
    "UPDATE #__template_styles SET home = '1', title = 'Hammond - Default', params = '%s' WHERE client_id = 0 AND template = 'hammond'" % params.replace("'", "''"))

# --- Menus -----------------------------------------------------------------------------------------------------------
if 'companymenu' not in sql("SELECT menutype FROM #__menu_types WHERE menutype='companymenu'"):
    sql("INSERT INTO #__menu_types (asset_id, menutype, title, description, client_id) VALUES (0, 'companymenu', 'Company Menu', 'About, contact and legal pages', 0)")

existing = sql("SELECT id || ':' || menutype || ':' || alias FROM #__menu WHERE client_id = 0 AND published >= 0 AND menutype IN ('mainmenu','companymenu')")
have = {line.split(':')[2]: int(line.split(':')[0]) for line in existing.splitlines() if line.count(':') >= 2}

blog = {
    'show_intro': '0', 'num_leading_articles': '1', 'num_intro_articles': '14', 'num_columns': '1', 'num_links': '0',
    'orderby_pri': 'none', 'orderby_sec': 'rdate', 'order_date': 'published', 'show_pagination': '2', 'show_pagination_results': '0',
    'show_description': '1', 'show_description_image': '0', 'show_subcategory_content': '0', 'show_category_title': '1',
}
cats = ['politics', 'world', 'finance', 'technology', 'energy', 'education', 'health', 'sports', 'culture', 'opinion']
titles = {'politics': 'Politics', 'world': 'World', 'finance': 'Finance', 'technology': 'Technology', 'energy': 'Energy', 'education': 'Education',
          'health': 'Health', 'sports': 'Sports', 'culture': 'Culture', 'opinion': 'Opinion'}
sql("UPDATE #__menu SET params = '%s' WHERE home = 1 AND client_id = 0" % json.dumps({'show_intro': '0', 'show_page_heading': '0'}).replace("'", "''"))
for alias in cats:
    if alias not in have:
        cli('menu:item:create', '--state=published', '--menu=mainmenu', '--title=' + titles[alias], '--alias=' + alias, '--category-blog=' + alias, '--params=' + json.dumps(blog))

# Info pages: a category of their own and a menu
pages = [
    ('about', 'About us', 'Hammond News is an independent newsroom.'),
    ('advertise', 'Advertise', 'Reach our readers on every device.'),
    ('careers', 'Careers', 'Join the newsroom.'),
    ('contact', 'Contact', 'How to reach the newsroom.'),
    ('privacy-policy', 'Privacy Policy', 'How we handle your data.'),
    ('terms-of-use', 'Terms of Use', 'The rules for using this site.'),
    ('cookie-policy', 'Cookie Policy', 'Which cookies we use and why.'),
]
if 'company' not in sql("SELECT alias FROM #__categories WHERE extension='com_content' AND alias='company'"):
    cli('category:create', '--title=Company', '--alias=company', '--description=<p>About the publication, contact and legal pages.</p>')
lorem = ('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim '
         'veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>\n'
         '<h2>Lorem ipsum dolor</h2>\n<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint '
         'occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>\n<ul>\n<li>Curabitur pretium tincidunt lacus.</li>\n'
         '<li>Nulla gravida orci a odio, nullam varius turpis et commodo pharetra.</li>\n<li>Integer in mauris eu nibh euismod gravida.</li>\n</ul>\n'
         '<h2>Sed ut perspiciatis</h2>\n<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, '
         'eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>')
for alias, title, intro in pages:
    if alias in have:
        continue
    found = sql("SELECT id FROM #__content WHERE alias = '%s'" % alias).split('\n')[0].strip()
    if not found.isdigit():
        found = created_id(cli('article:create', '--state=published', '--title=' + title, '--alias=' + alias, '--category=company', '--text-file=-', stdin='<p>%s</p>\n%s' % (intro, lorem)))
    cli('menu:item:create', '--state=published', '--menu=companymenu', '--title=' + title, '--alias=' + alias, '--article=%s' % found,
        '--params=' + json.dumps({'show_title': '1', 'show_intro': '1', 'show_category': '0', 'show_author': '0', 'show_create_date': '0', 'show_publish_date': '0',
                                  'show_hits': '0', 'show_tags': '0', 'show_print_icon': '0', 'show_email_icon': '0', 'show_vote': '0'}))

# --- Modules ---------------------------------------------------------------------------------------------------------
positions = "('trending','navigation','search','megamenu','megamenu-aside','frontpage','above-content','sidebar','below-content','footer')"
sql("DELETE FROM #__modules_menu WHERE moduleid IN (SELECT id FROM #__modules WHERE client_id = 0 AND position IN %s)" % positions,
    "DELETE FROM #__modules WHERE client_id = 0 AND position IN %s" % positions)

def cat_id(alias):
    return sql("SELECT id FROM #__categories WHERE extension='com_content' AND alias='%s'" % alias).split('\n')[0].strip()

def articles(title, position, layout, sfx, count, cats=None, featured='show', ordering='publish_up', show_title='yes', mode='normal', note=''):
    p = {'mode': mode, 'count': str(count), 'show_front': featured, 'article_ordering': ordering, 'article_ordering_direction': 'DESC',
         'catid': [cat_id(c) for c in cats] if cats else [], 'category_filtering_type': '1', 'show_child_category_articles': '0',
         'show_date': '1', 'show_date_field': 'publish_up', 'show_category': '1', 'show_author': '1', 'show_introtext': '0',
         'layout': 'hammond:' + layout, 'moduleclass_sfx': sfx, 'owncache': '1', 'cache_time': '900', 'show_on_article_page': '1'}
    if not cats:
        p['catid'] = [cat_id(c) for c in cats_news]
    return cli('module:create', '--type=mod_articles_category', '--title=' + title, '--position=' + position, '--show-title=' + show_title,
               '--pages=all', '--state=published', '--params=' + json.dumps(p), '--note=' + note)

def ad(title, position, size, sfx, label):
    w, h = {'leaderboard': (970, 250), 'skyscraper': (300, 600), 'rectangle': (300, 250)}[size]
    return cli('module:create', '--type=mod_custom', '--title=' + title, '--position=' + position, '--show-title=no', '--pages=all', '--state=published',
               '--content=<div class="adSlot %s">Advertisement<span>%d × %d</span></div>' % (size, w, h),
               '--params=' + json.dumps({'moduleclass_sfx': 'ad ' + sfx, 'prepare_content': '0'}), '--note=Placeholder: ' + label)

def menu(title, position, menutype, sfx='', show_title='no', end=1):
    return cli('module:create', '--type=mod_menu', '--title=' + title, '--position=' + position, '--show-title=' + show_title, '--pages=all', '--state=published',
               '--params=' + json.dumps({'menutype': menutype, 'startLevel': '1', 'endLevel': str(end), 'showAllChildren': '1', 'class_sfx': sfx,
                                         'layout': '_:default', 'moduleclass_sfx': '', 'cache': '1', 'module_tag': 'div', 'style': '0'}))

cats_news = [c for c in cats if c != 'opinion']

# Header
menu('Main Menu', 'navigation', 'mainmenu')
cli('module:create', '--type=mod_search', '--title=Search', '--position=search', '--show-title=no', '--pages=all', '--state=published',
    '--params=' + json.dumps({'label': 'Search', 'width': '', 'text': 'Search the news…', 'button': '1', 'button_pos': 'right', 'button_text': 'Search', 'set_itemid': '0', 'opensearch': '0'}))
cli('module:create', '--type=mod_tags_popular', '--title=Trending', '--position=trending', '--show-title=no', '--pages=all', '--state=published',
    '--params=' + json.dumps({'maximum': '8', 'timeframe': 'alltime', 'order_value': 'count', 'order_direction': '1', 'display_count': '0', 'no_results_text': '0', 'layout': '_:default'}))
menu('Sections', 'megamenu', 'mainmenu', show_title='yes')
menu('Company', 'megamenu-aside', 'companymenu', show_title='yes')

# Frontpage grid (in this order)
articles('Main story', 'frontpage', 'hero', 'grid-col-span-8 hero', 1, featured='only', show_title='no')
articles('Latest news', 'frontpage', 'latest', 'grid-col-span-4 latest', 8, show_title='yes')
articles('Featured', 'frontpage', 'cards', 'grid-col-span-12 featured skip-1', 5, featured='only', show_title='no')
ad('Ad - Leaderboard (top)', 'frontpage', 'leaderboard', 'grid-col-span-12', 'leaderboard 970×250')
articles('Opinion', 'frontpage', 'opinion', 'grid-col-span-12 opinion', 4, cats=['opinion'])
articles('Politics', 'frontpage', 'section', 'grid-col-span-9 cat-politics', 5, cats=['politics'])
ad('Ad - Skyscraper', 'frontpage', 'skyscraper', 'grid-col-span-3 grid-row-span-2', 'skyscraper 300×600')
articles('Finance', 'frontpage', 'cards', 'grid-col-span-9 cat-finance', 3, cats=['finance'])
articles('Technology', 'frontpage', 'cards', 'grid-col-span-12 cat-technology lead-first', 5, cats=['technology'])
ad('Ad - Leaderboard (middle)', 'frontpage', 'leaderboard', 'grid-col-span-12', 'leaderboard 970×250')
articles('World', 'frontpage', 'section', 'grid-col-span-6 cat-world', 3, cats=['world'])
articles('Energy', 'frontpage', 'section', 'grid-col-span-6 cat-energy', 3, cats=['energy'])
articles('Health', 'frontpage', 'cards', 'grid-col-span-8 cat-health', 6, cats=['health'])
articles('Most read', 'frontpage', 'ranked', 'grid-col-span-4 mostread', 5, ordering='a.hits')
articles('Sports', 'frontpage', 'cards', 'grid-col-span-12 cat-sports lead-first', 5, cats=['sports'])
articles('Education', 'frontpage', 'section', 'grid-col-span-6 cat-education', 3, cats=['education'])
articles('Culture', 'frontpage', 'section', 'grid-col-span-6 cat-culture', 3, cats=['culture'])

# Inner pages
articles('Most read', 'sidebar', 'ranked', 'mostread', 5, ordering='a.hits')
ad('Ad - Sidebar rectangle', 'sidebar', 'rectangle', '', 'rectangle 300×250')
articles('Latest news', 'sidebar', 'latest', 'latest', 6)
articles('More news', 'below-content', 'cards', 'grid-col-span-12', 4, mode='dynamic', note='Articles of the category being viewed')

# Footer
menu('Sections', 'footer', 'mainmenu', show_title='yes')
menu('Company', 'footer', 'companymenu', show_title='yes')

print(sql("SELECT position, count(*) FROM #__modules WHERE client_id=0 AND published=1 AND position IN %s GROUP BY position" % positions))
