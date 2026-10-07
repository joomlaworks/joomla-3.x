"""Writes the Studio set's content.json: the work (case studies) and journal of Rookwood Studio, a fictional design and technology
studio. The case studies and every visible text are written out; the journal's longer texts are placeholder (lorem ipsum), as in the
other sets. Images: the set's own (images/sampledata/studio, 1920 px) for most case studies, the News set's (images/sampledata/news)
for the journal. Usage: python3 generate_content.py"""
import datetime, json, os, random

HERE = os.path.dirname(os.path.abspath(__file__))
random.seed(20261007)

STUDIO = 'images/sampledata/studio/'
NEWS = 'images/sampledata/news/'

categories = [
    {'alias': 'work', 'title': 'Work', 'description': '<p>Brands, websites and products we have designed and built with our clients.</p>'},
    {'alias': 'journal', 'title': 'Journal', 'description': '<p>Notes from the studio on design, engineering and the craft in between.</p>'},
]

AUTHORS = ['Mara Ellis', 'Theo Brandt', 'Ines Kovač', 'Sam Okoye']

LOREM = [
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
    'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    'Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris. Integer in mauris eu nibh euismod gravida.',
    'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.',
    'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet.',
    'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.',
]

# --- Work: case studies ------------------------------------------------------------------------------------------------
# (title, client, image, alt, services (tags), intro, brief, approach, results [(figure, label)], featured)
work = [
    ('A city of light for Northwind Architects', 'Northwind Architects', STUDIO + 'orange-towers.webp', 'Orange glass towers seen from below',
     ['Brand identity', 'Web design'],
     'A new identity and portfolio site for an architecture practice whose buildings change colour with the hour.',
     'Northwind had outgrown a template website that showed its buildings as small, grey thumbnails. They wanted a home that felt like walking through their work.',
     'We built a modular identity around the warm glass of their towers, and a website where every project opens full-screen, with drawings, materials and a timeline of the build.',
     [('3×', 'longer visits'), ('41%', 'more enquiries'), ('8', 'weeks from kickoff to launch')], True),
    ('Seeing the grid with Halden Energy', 'Halden Energy', NEWS + 'energy/energy-17.webp', 'Offshore wind turbines at sea',
     ['Product design', 'Engineering'],
     'A live dashboard that turns two hundred wind turbines into one clear picture for the people who run them.',
     'Halden\'s operators watched six different tools to keep an offshore wind farm running. Alarms were missed, and new staff took months to learn the systems.',
     'We spent two weeks in the control room, then designed and built one dashboard: a map of the farm, the turbines that need attention first, and the weather that will matter next.',
     [('62%', 'faster response to alarms'), ('1', 'screen instead of six'), ('99.98%', 'uptime since launch')], True),
    ('The Aperture museum after dark', 'Aperture Museum of Light', STUDIO + 'light-painting.webp', 'Red and blue streaks of light over water at night',
     ['Web design', 'Motion'],
     'An exhibition website for a museum of light, built to feel as playful as the rooms it describes.',
     'The museum\'s night programme sold out in person but barely existed online. They needed a site that could sell tickets and carry the atmosphere of the galleries.',
     'Every exhibition gets a page of moving light made with plain CSS and a few lines of script, so it loads fast on a phone in a queue outside. Tickets take three taps.',
     [('2.1 s', 'largest contentful paint on 4G'), ('+54%', 'online ticket sales'), ('0', 'third-party scripts')], True),
    ('Vela Towers, from launch to living room', 'Vela Residences', STUDIO + 'twisting-tower.webp', 'A twisting tower lit at night',
     ['Brand identity', 'Campaign'],
     'A launch campaign for two residential towers, from the hoarding on the street to the welcome pack on the kitchen table.',
     'Vela had a remarkable building and a crowded market. The first homes had to sell before the second tower topped out.',
     'We named the towers, designed the identity around their twist, and ran one campaign across print, street and a small, quick website with floor plans that work on any screen.',
     [('78%', 'of homes reserved before completion'), ('12', 'weeks of campaign'), ('5', 'design awards')], False),
    ('Outdoor gear for Aurora Outfitters', 'Aurora Outfitters', STUDIO + 'polar-lights.webp', 'Green and red polar lights over a forest',
     ['E-commerce', 'Web design'],
     'A new shop for an outdoor brand whose customers buy on the move, often on a weak signal at the edge of the map.',
     'Aurora\'s old shop was slow, and half of their customers left before the product page had loaded. Returns were high because sizes were hard to judge.',
     'We rebuilt the shop around speed and clarity: small pages, images sized for each screen, a size guide drawn from real returns, and a checkout that survives a dropped connection.',
     [('−38%', 'returns'), ('+27%', 'mobile conversion'), ('1.4 s', 'average page load')], True),
    ('An open data platform for the city', 'Open City Data', STUDIO + 'code-screens.webp', 'Two screens with code on a dark desk',
     ['Engineering', 'Product design'],
     'A developer platform that publishes a city\'s open data with documentation people actually enjoy reading.',
     'The city released hundreds of data sets a year, but few developers used them: formats changed without notice and the documentation was a set of PDFs.',
     'We designed a versioned API, a catalogue with search and previews, and documentation written alongside the code, so every example is tested before it is published.',
     [('340', 'data sets published'), ('5×', 'more API keys issued'), ('100%', 'of examples tested')], False),
    ('A furniture brand with holes in all the right places', 'Perfora', STUDIO + 'perforated-facade.webp', 'A white perforated building against a teal sky',
     ['Brand identity', 'Packaging'],
     'An identity for a young furniture maker, drawn from the perforated steel at the heart of its first collection.',
     'Perfora had one beautiful collection, a tiny budget and no name customers could remember.',
     'We named the company after its material and built the whole identity from a grid of round holes: the logo, a typeface of dots, flat-pack boxes and a one-page shop.',
     [('1', 'grid behind everything'), ('3', 'retailers signed in the first month'), ('0', 'plastic in the packaging')], False),
    ('Spiral Records, around and around', 'Spiral Records', STUDIO + 'spiral-stairs.webp', 'A white and yellow spiral staircase from above',
     ['Brand identity', 'Web design', 'Motion'],
     'A refreshed identity and a listening-first website for an independent record label celebrating twenty years.',
     'After twenty years and four hundred releases, Spiral\'s catalogue was scattered across platforms and its own site had not changed in a decade.',
     'We gave the logo a quarter turn and built a site where the whole catalogue plays in the browser, release by release, with liner notes written by the artists.',
     [('400', 'releases online'), ('+65%', 'direct sales'), ('20', 'years celebrated')], False),
    ('Quiet infrastructure for Cirrus Hosting', 'Cirrus Hosting', NEWS + 'technology/technology-06.webp', 'Rows of servers lit blue in a data centre',
     ['Product design', 'Engineering'],
     'A control panel for a hosting company that wanted its customers to forget their servers were there.',
     'Cirrus had fast, reliable servers and a control panel that generated more support tickets than any outage.',
     'We mapped the twenty tasks customers do most, designed one clear path for each, and rebuilt the panel as a set of small, accessible components the Cirrus team now owns.',
     [('−45%', 'support tickets'), ('20', 'tasks redesigned'), ('AA', 'WCAG level met')], False),
]

# --- Journal -----------------------------------------------------------------------------------------------------------
# (title, image, alt, tags, intro, featured)
journal = [
    ('Dark mode is a design system, not a filter', NEWS + 'technology/technology-17.webp', 'A laptop and cables on a dark desk', ['Design', 'Accessibility'],
     'Inverting colours gives you a dark page, not a dark design. What changes when you treat both themes as first-class citizens.', True),
    ('What a fast website feels like', NEWS + 'technology/technology-08.webp', 'A laptop keyboard in blue light', ['Performance', 'Engineering'],
     'Speed is not a number on a report. It is the moment a page answers before you have finished wondering whether it will.', True),
    ('The case for boring technology', NEWS + 'technology/technology-15.webp', 'A processor on a circuit board', ['Engineering'],
     'Every new tool is a loan against your future attention. Here is how we decide which ones are worth the interest.', True),
    ('Designing for the edge of the map', NEWS + 'energy/energy-20.webp', 'Wind turbines on hills at dusk', ['Design', 'Performance'],
     'Lessons from building a shop for customers who buy on one bar of signal, far from the nearest town.', False),
    ('Type that carries a brand', NEWS + 'opinion/opinion-22.webp', 'A red typewriter on a black leather seat', ['Typography', 'Design'],
     'Why we start most identities with letters, not logos, and what system fonts can do when you treat them well.', True),
    ('Accessibility is a craft, not a checklist', NEWS + 'opinion/opinion-13.webp', 'A notebook and pen beside a laptop', ['Accessibility'],
     'Passing an audit is the floor. The real work is noticing who still struggles once every box is ticked.', False),
    ('Notes from a control room', NEWS + 'energy/energy-17.webp', 'Offshore wind turbines at sea', ['Product design', 'Studio life'],
     'Two weeks beside the people who keep an offshore wind farm running taught us more than any brief could.', False),
    ('Small components, long lives', NEWS + 'technology/technology-22.webp', 'Code on a laptop screen beside a stack of books', ['Engineering', 'Product design'],
     'The parts of a product that last are the small, dull, well-named ones. A few habits that keep them that way.', False),
    ('Colour with confidence', NEWS + 'culture/culture-22.webp', 'Stage lights in blue and orange over a crowd', ['Design'],
     'Vibrant palettes are easy to like and hard to live with. How we build colour systems that stay bright and readable.', True),
    ('Writing documentation people read', NEWS + 'opinion/opinion-14.webp', 'Coffee beside a laptop showing code', ['Engineering', 'Writing'],
     'The best documentation is written next to the code, tested with it and kept as short as the reader\'s patience.', False),
    ('Motion that means something', NEWS + 'culture/culture-11.webp', 'A microphone in warm, blurred light', ['Motion', 'Design'],
     'Animation should explain, not decorate. Three questions we ask before anything on a page is allowed to move.', False),
    ('The studio at ten', NEWS + 'opinion/opinion-10.webp', 'A laptop, coffee and sticky notes on a desk', ['Studio life'],
     'Ten years, four people, a hundred and twenty projects and one rule we have never broken.', False),
    ('Images, sized honestly', NEWS + 'technology/technology-13.webp', 'Laptop keys in warm light', ['Performance'],
     'Most slow pages are slow because of pictures. The simple rules we follow for every image we ship.', False),
    ('Why we still sketch on paper', NEWS + 'opinion/opinion-11.webp', 'The pages of an open book fanned out', ['Design', 'Studio life'],
     'Screens make ideas look finished too soon. A pencil keeps them honest for a little longer.', False),
    ('Sustainable by default', NEWS + 'energy/energy-12.webp', 'Solar panels among trees', ['Sustainability', 'Engineering'],
     'Lighter pages use less energy, on our servers and on yours. What a lower-carbon website looks like in practice.', True),
    ('Sound design for the web', NEWS + 'culture/culture-21.webp', 'A concert hall seen from the stage', ['Motion', 'Design'],
     'Most websites are silent, and most should stay that way. When sound helps, and how to add it without startling anyone.', False),
]


def case_study(client, brief, approach, results, services):
    figures = ''.join('<li><strong>%s</strong> %s</li>' % (n, label) for n, label in results)
    return ('<h2>The brief</h2>\n<p>%s</p>\n<h2>What we did</h2>\n<p>%s</p>\n<blockquote><p>%s</p></blockquote>\n'
            '<h2>Results</h2>\n<ul class="figures">%s</ul>\n<dl class="facts"><dt>Client</dt><dd>%s</dd><dt>Services</dt><dd>%s</dd></dl>'
            % (brief, approach, random.choice([
                'They listened first and designed second. It shows in every screen.',
                'The new work feels like us, only clearer. Our customers noticed within a week.',
                'Fast, thoughtful and calm under pressure. We would hire them again tomorrow.',
                'They turned a complicated problem into something our whole team understands.']),
               figures, client, ', '.join(services)))


def post_body():
    paragraphs = random.sample(LOREM, 4)
    body = '<p>%s</p>\n<h2>%s</h2>\n<p>%s</p>\n' % (paragraphs[0], random.choice(['Where we started', 'What we learned', 'The short version', 'In practice']),
                                                    paragraphs[1])
    if random.random() < 0.5:
        body += '<blockquote><p>%s</p></blockquote>\n' % random.choice(LOREM)[:140].rsplit(' ', 1)[0].rstrip(',') + '.'
    if random.random() < 0.5:
        body += '<ul><li>Lorem ipsum dolor sit amet</li><li>Consectetur adipiscing elit</li><li>Sed do eiusmod tempor incididunt</li></ul>\n'
    body += '<p>%s</p>\n<p>%s</p>' % (paragraphs[2], paragraphs[3])
    return body


articles = []
start = datetime.datetime(2026, 10, 5, 9, 30)

# Case studies: one every three weeks or so, newest first
for i, (title, client, image, alt, services, intro, brief, approach, results, featured) in enumerate(work):
    when = start - datetime.timedelta(days=21 * i + random.randint(0, 6), hours=random.randint(0, 6))
    articles.append({'category': 'work', 'title': title, 'intro': '<p>%s</p>' % intro, 'full': case_study(client, brief, approach, results, services),
                     'image': image, 'image_alt': alt, 'author': random.choice(AUTHORS), 'publish_up': when.strftime('%Y-%m-%d %H:%M'),
                     'featured': featured, 'tags': services, 'hits': random.randint(300, 4000)})

# Journal: a post every week or so, newest first
for i, (title, image, alt, tags, intro, featured) in enumerate(journal):
    when = start - datetime.timedelta(days=7 * i + random.randint(0, 3), hours=random.randint(1, 8))
    articles.append({'category': 'journal', 'title': title, 'intro': '<p>%s</p>' % intro, 'full': post_body(), 'image': image, 'image_alt': alt,
                     'author': AUTHORS[i % len(AUTHORS)], 'publish_up': when.strftime('%Y-%m-%d %H:%M'), 'featured': featured, 'tags': tags,
                     'hits': random.randint(150, 3000)})

json.dump({'categories': categories, 'articles': articles}, open(os.path.join(HERE, 'content.json'), 'w'), indent='\t', ensure_ascii=False)
print(len(articles), 'articles')
