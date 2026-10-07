"""Builds the Studio set's structure on its build site: the Rookwood template's settings, menus, pages and modules.
Usage: setup.py <site> <q.php>"""
import json, os, subprocess, sys
PHP = os.environ.get('PHP', 'php')

D = sys.argv[1]
Q = sys.argv[2]   # q.php helper (SQL through Joomla)
IMG = 'images/sampledata/studio/'

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
    return int(sql("SELECT id FROM #__categories WHERE extension='com_content' AND alias='%s'" % alias).split('\n')[0].strip())

def picture(name, alt, cls, eager=False, sizes='100vw'):
    # The 1920px image and its 960px copy (template's img() does the same for articles)
    return ('<img class="%s" src="%s%s.webp" srcset="%s%s-960.webp 960w, %s%s.webp 1920w" sizes="%s" width="1920" height="1080" alt="%s" %s />'
            % (cls, IMG, name, IMG, name, IMG, name, sizes, alt, 'fetchpriority="high"' if eager else 'loading="lazy" decoding="async"'))

# --- Menus -----------------------------------------------------------------------------------------------------------
sql("INSERT INTO #__menu_types (asset_id, menutype, title, description, client_id) VALUES (0, 'footermenu', 'Footer Menu', 'Privacy and accessibility pages', 0)")
# Menus of buttons, shown by the template's header-action position and inside Custom modules ({loadposition}): their items are
# aliases of the main menu's, so the links follow the pages wherever they are (no menu item IDs in modules or template options)
for menutype, title, description in (('headeraction', 'Header Button', 'The button in the header'),
                                     ('heroactions', 'Hero Buttons', 'The buttons of the home page\'s hero'),
                                     ('studiolink', 'Studio Button', 'The button of the home page\'s studio section')):
    sql("INSERT INTO #__menu_types (asset_id, menutype, title, description, client_id) VALUES (0, '%s', '%s', '%s', 0)"
        % (menutype, title, description.replace("'", "''")))

# The home page shows the frontpage modules; its menu item stays "Featured Articles" (what a site without them shows)
sql("UPDATE #__menu SET params = '%s' WHERE home = 1 AND client_id = 0" % json.dumps({
    'num_leading_articles': '0', 'num_intro_articles': '9', 'num_columns': '3', 'num_links': '0', 'orderby_sec': 'rdate',
    'order_date': 'published', 'show_pagination': '2', 'show_pagination_results': '0', 'show_page_heading': '0'}).replace("'", "''"))

# Pages: a category of their own; each with a menu item, which shows it as a page (title band, no byline)
cli('category:create', '--title=Pages', '--alias=pages', '--description=<p>Studio, contact, privacy and accessibility.</p>', '--state=published')
lorem = ('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim '
         'veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>\n'
         '<h2>Lorem ipsum dolor</h2>\n<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint '
         'occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>')
team = [('Mara Ellis', 'Founder, creative director'), ('Theo Brandt', 'Technical director'), ('Ines Kovač', 'Design lead'), ('Sam Okoye', 'Engineering lead'),
        ('Lena Park', 'Brand designer'), ('Jonah Reyes', 'Motion designer'), ('Priya Nair', 'Front-end engineer'), ('Arlo Finch', 'Producer')]
team_html = ''.join('<li><span class="avatar" style="--hue:%d">%s</span><strong>%s</strong><span>%s</span></li>'
                    % ((i * 47 + 250) % 360, ''.join(w[0] for w in name.split()), name, role) for i, (name, role) in enumerate(team))
studio = ('<p>Rookwood Studio is a team of designers and engineers who make brands, websites and products for people who care about the details. '
          'We started in a converted foundry in 2012, and we still sketch on the walls.</p>\n'
          '<h2>How we work</h2>\n<ul class="values wide">'
          '<li><strong>Small teams, senior people</strong>The people who pitch your project are the ones who design and build it.</li>'
          '<li><strong>Prototypes over decks</strong>We show working things early, so decisions are made with real screens and real content.</li>'
          '<li><strong>Built to last</strong>Fast, accessible and easy to maintain: we hand over systems your team can grow, not just files.</li></ul>\n'
          '<h2>The team</h2>\n<ul class="team wide">' + team_html + '</ul>\n'
          '<h2>Services</h2>\n<p>Brand identity, web design, product design, engineering, motion, e-commerce, campaigns and packaging. '
          'Most projects mix several of them; all of them start with a conversation.</p>\n' + lorem)
contact = ('<p>Tell us about your project, your idea or your problem. We answer every message within two working days.</p>\n'
           '<div class="contactCards wide">'
           '<div class="contactCard"><h2>New projects</h2><p>Mara Ellis</p><p><a href="mailto:hello@example.com">hello@example.com</a></p><p>+44 20 7946 0958</p></div>'
           '<div class="contactCard"><h2>Visit the studio</h2><p>Unit 4, The Old Foundry</p><p>27 Kiln Yard, Rookwood</p><p>Monday to Friday, 9:00 to 18:00</p></div>'
           '<div class="contactCard"><h2>Careers and press</h2><p><a href="mailto:jobs@example.com">jobs@example.com</a></p><p><a href="mailto:press@example.com">press@example.com</a></p><p>We hire twice a year.</p></div>'
           '</div>\n'
           '<h2>Getting here</h2>\n<p>The studio is a ten-minute walk from Rookwood Central. There are bicycle racks in the yard and step-free access through the side gate.</p>\n'
           '<p><em>Rookwood Studio, its people, clients and addresses are fictional: this is sample content for demonstrating Joomla.</em></p>')
pages = [
    ('studio', 'Studio', 'mainmenu', 'violet-desk', 'A desk lit in violet and blue in a dark studio', studio),
    ('contact', 'Contact', 'mainmenu', 'light-trails', 'Trails of car lights through a city at night', contact),
    ('privacy-policy', 'Privacy Policy', 'footermenu', '', '', '<p>This site collects as little as it can.</p>\n' + lorem),
    ('accessibility', 'Accessibility', 'footermenu', '', '', '<p>We want everyone to be able to use this site, whatever their device, browser or ability.</p>\n' + lorem),
]
page_params = json.dumps({'show_title': '1', 'show_intro': '1', 'show_category': '0', 'show_author': '0', 'show_create_date': '0',
                          'show_publish_date': '0', 'show_hits': '0', 'show_tags': '0', 'show_print_icon': '0', 'show_email_icon': '0'})
page_ids = {}
for alias, title, menutype, image, alt, text in pages:
    args = ['article:create', '--state=published', '--title=' + title, '--alias=' + alias, '--category=pages', '--author-alias=Rookwood Studio', '--text-file=-']
    if image:
        args += ['--image-full=' + IMG + image + '.webp', '--image-full-alt=' + alt]
    page_ids[alias] = created_id(cli(*args, stdin=text))

work_list = {'show_intro': '0', 'num_leading_articles': '0', 'num_intro_articles': '9', 'num_columns': '1', 'num_links': '0', 'orderby_pri': 'none',
             'orderby_sec': 'rdate', 'order_date': 'published', 'show_pagination': '2', 'show_pagination_results': '0', 'show_description': '1',
             'show_tags': '1', 'show_subcategory_content': '0'}
journal_list = dict(work_list, num_intro_articles='14', num_columns='4')
items = {}
items['studio'] = created_id(cli('menu:item:create', '--state=published', '--menu=mainmenu', '--title=Studio', '--alias=studio', '--article=%d' % page_ids['studio'], '--params=' + page_params))
items['work'] = created_id(cli('menu:item:create', '--state=published', '--menu=mainmenu', '--title=Work', '--alias=work',
                               '--link=index.php?option=com_content&view=category&layout=rookwood:showcase&id=%d' % cat_id('work'), '--params=' + json.dumps(work_list)))
items['journal'] = created_id(cli('menu:item:create', '--state=published', '--menu=mainmenu', '--title=Journal', '--alias=journal',
                                  '--link=index.php?option=com_content&view=category&layout=rookwood:mosaic&id=%d' % cat_id('journal'), '--params=' + json.dumps(journal_list)))
items['contact'] = created_id(cli('menu:item:create', '--state=published', '--menu=mainmenu', '--title=Contact', '--alias=contact', '--article=%d' % page_ids['contact'], '--params=' + page_params))
for alias, title in (('privacy-policy', 'Privacy Policy'), ('accessibility', 'Accessibility')):
    cli('menu:item:create', '--state=published', '--menu=footermenu', '--title=' + title, '--alias=' + alias, '--article=%d' % page_ids[alias], '--params=' + page_params)

# Home stays in the main menu (the footer's Explore shows it) but not in the header, where the logo leads home: Link CSS Style
# hideInNav, which the template hides in its navigation
home = json.loads(sql("SELECT params FROM #__menu WHERE home = 1 AND client_id = 0").split('\n')[0])
home['menu-anchor_css'] = 'hideInNav'
sql("UPDATE #__menu SET params = '%s' WHERE home = 1 AND client_id = 0" % json.dumps(home).replace("'", "''"))

# A button: a menu item alias of a main menu item, its link styled as a button (Link CSS Style)
def button(menutype, title, alias, target, css):
    cli('menu:item:create', '--state=published', '--menu=' + menutype, '--title=' + title, '--alias=' + alias, '--alias-of=%d' % items[target],
        '--params=' + json.dumps({'menu-anchor_css': css, 'menu_text': 1}))

button('headeraction', 'Start a project', 'header-start-a-project', 'contact', 'btn btnPrimary')
button('heroactions', 'See our work', 'hero-see-our-work', 'work', 'btn btnPrimary')
button('heroactions', 'Start a project', 'hero-start-a-project', 'contact', 'btn')
button('studiolink', 'Meet the studio', 'meet-the-studio', 'studio', 'btn btnLight')

# --- Template --------------------------------------------------------------------------------------------------------
params = json.dumps({
    'siteName': 'Rookwood Studio', 'defaultTheme': 'dark',
    'footerHeadline': 'Have a project in mind? Let\'s talk.', 'footerEmail': 'hello@example.com',
    'footerText': 'A fictional studio, made for demonstrating Joomla.',
    'social_x': '#', 'social_linkedin': '#', 'social_instagram': '#', 'social_github': '#', 'social_dribbble': '#', 'social_rss': '',
})
sql("UPDATE #__template_styles SET home = '0' WHERE client_id = 0",
    "UPDATE #__template_styles SET home = '1', title = 'Rookwood - Default', params = '%s' WHERE client_id = 0 AND template = 'rookwood'" % params.replace("'", "''"))

# --- Modules ---------------------------------------------------------------------------------------------------------
# joomla.sql's modules of a site without sample data are in Hammond's positions; the same names in Rookwood: replace them
positions = "('navigation','header-action','hero-actions','studio-link','search','above-content','sidebar','below-content','footer','trending','masthead','megamenu','megamenu-aside','frontpage')"
sql("DELETE FROM #__modules_menu WHERE moduleid IN (SELECT id FROM #__modules WHERE client_id = 0 AND position IN %s)" % positions,
    "DELETE FROM #__modules WHERE client_id = 0 AND position IN %s" % positions)

def custom(title, position, content, sfx='', show_title='no'):
    return cli('module:create', '--type=mod_custom', '--title=' + title, '--position=' + position, '--show-title=' + show_title, '--pages=all',
               '--state=published', '--content-file=-', '--params=' + json.dumps({'moduleclass_sfx': sfx, 'prepare_content': '1' if '{load' in content else '0'}), stdin=content)

def menu(title, position, menutype, show_title='no', css=''):
    return cli('module:create', '--type=mod_menu', '--title=' + title, '--position=' + position, '--show-title=' + show_title, '--pages=all', '--state=published',
               '--params=' + json.dumps({'menutype': menutype, 'startLevel': '1', 'endLevel': '1', 'showAllChildren': '1', 'layout': '_:default',
                                         'cache': '1', 'module_tag': 'div', 'style': '0', 'class_sfx': css}))

def posts(title, layout, category, count, sfx='', featured='show'):
    return cli('module:create', '--type=mod_articles_category', '--title=' + title, '--position=frontpage', '--show-title=yes', '--pages=all',
               '--state=published', '--params=' + json.dumps({
                   'mode': 'normal', 'count': str(count), 'show_front': featured, 'article_ordering': 'publish_up', 'article_ordering_direction': 'DESC',
                   'catid': [str(cat_id(category))], 'category_filtering_type': '1', 'show_child_category_articles': '0', 'show_date': '1',
                   'show_date_field': 'publish_up', 'show_category': '0', 'show_author': '1', 'show_introtext': '1', 'introtext_limit': '200',
                   'layout': 'rookwood:' + layout, 'moduleclass_sfx': sfx, 'owncache': '1', 'cache_time': '900', 'show_on_article_page': '1'}))

menu('Main Menu', 'navigation', 'mainmenu')
menu('Header Button', 'header-action', 'headeraction', css=' buttons')
menu('Hero Buttons', 'hero-actions', 'heroactions', css=' buttons')
menu('Studio Button', 'studio-link', 'studiolink', css=' buttons')

custom('Hero', 'frontpage', '<div class="hero">' + picture('light-curves', '', 'heroImage', True) + '<div class="container heroBody">'
       '<p class="eyebrow">Design &amp; technology studio</p>'
       '<h1 class="displayTitle">We design and build <span class="highlight">bold digital things.</span></h1>'
       '<p class="lead">Brands, websites and products for people who care about the details, made by a small team of designers and engineers.</p>'
       '<div class="actions">{loadposition hero-actions,none}</div></div></div>', 'section-full')
custom('Clients', 'frontpage', '<ul class="namesStrip"><li class="namesLabel">Trusted by teams at</li><li>Northwind</li><li>Halden Energy</li><li>Aperture</li>'
       '<li>Vela</li><li>Aurora</li><li>Open City</li><li>Perfora</li><li>Spiral Records</li></ul>', 'names')
custom('What we do', 'frontpage', '<ul class="services">'
       '<li class="service"><span class="serviceNumber">01</span><h3>Brand identity</h3><p>Names, logos, type and colour systems with a point of view, and the guidelines to keep them sharp.</p></li>'
       '<li class="service"><span class="serviceNumber">02</span><h3>Websites</h3><p>Fast, accessible sites on content management systems your team will actually enjoy using.</p></li>'
       '<li class="service"><span class="serviceNumber">03</span><h3>Products</h3><p>Research, interface design and prototypes for apps and tools, from first sketch to release.</p></li>'
       '<li class="service"><span class="serviceNumber">04</span><h3>Engineering</h3><p>Front-end and back-end development, design systems and the infrastructure to run them.</p></li>'
       '</ul>', '', 'yes')
posts('Selected work', 'work', 'work', 5, 'tone-alt divider-wave')
custom('Inside the studio', 'frontpage', '<div class="band">' + picture('violet-desk', '', 'bandImage') + '<div class="container bandBody">'
       '<h2>Small team. Big ideas.</h2><div><p>Twenty-four designers, engineers and producers in a converted foundry. Senior people on every project, '
       'from the first workshop to the launch and long after it.</p><div class="actions">{loadposition studio-link,none}</div></div></div></div>',
       'section-full')
custom('In numbers', 'frontpage', '<ul class="stats"><li><strong>14</strong><span>years in practice</span></li><li><strong>120+</strong><span>projects launched</span></li>'
       '<li><strong>24</strong><span>designers and engineers</span></li><li><strong>9</strong><span>design awards</span></li></ul>', 'tone-accent divider-wave')
custom('What clients say', 'frontpage', '<figure class="bigQuote"><blockquote><p>Rookwood understood what we were trying to say before we did, then built '
       'something faster and braver than we had asked for.</p></blockquote><figcaption><span class="avatar" style="--hue:320">EV</span>'
       '<span><strong>Elena Vasquez</strong>, Director, Aperture Museum</span></figcaption></figure>')
posts('From the journal', 'journal', 'journal', 3, 'tone-alt divider-wave')

menu('Explore', 'footer', 'mainmenu', 'yes')
custom('Visit', 'footer', '<p>Unit 4, The Old Foundry</p><p>27 Kiln Yard, Rookwood</p><p>+44 20 7946 0958</p>', '', 'yes')
menu('Legal', 'footer', 'footermenu', 'yes')

print(sql("SELECT position, count(*) FROM #__modules WHERE client_id=0 AND published=1 AND position IN %s GROUP BY position" % positions))
print(items)
