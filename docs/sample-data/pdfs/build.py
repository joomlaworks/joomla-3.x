"""Writes the sample sets' PDF files, one per set, each shown by an article of its set with the {pdf} shortcode:

    images/sampledata/pdf/city-budget-2027.pdf     (News, Hammond)
    images/sampledata/pdf/reading-list.pdf         (Blog, Finch)
    images/sampledata/pdf/studio-profile.pdf     (Studio, Rookwood)

Every text is this project's own (fictional places, people and figures; the reading list's books are in the public domain), the
pictures are the sets' own CC0 images, so the files carry nobody else's copyright. Each is an HTML page printed by headless Chrome:
several pages, a picture, a table and a landscape page, which is what the viewer shows off. Fonts: Noto Sans and Noto Serif (SIL
Open Font License, embedded as subsets), which the machine building them needs. Usage: python3 docs/sample-data/pdfs/build.py
(Pillow, google-chrome)"""
import base64, io, os, subprocess, sys, tempfile

from PIL import Image

HERE = os.path.dirname(os.path.abspath(__file__))
REPO = os.path.abspath(os.path.join(HERE, '..', '..', '..'))
CHROME = os.environ.get('CHROME', 'google-chrome')


def picture(path, width, quality=72):
    """A set image as a JPEG data: address, scaled to width pixels (twice its printed size at most)"""
    im = Image.open(os.path.join(REPO, path)).convert('RGB')
    im = im.resize((width, round(im.height * width / im.width)), Image.LANCZOS)
    out = io.BytesIO()
    im.save(out, 'JPEG', quality=quality, optimize=True, progressive=False)
    return 'data:image/jpeg;base64,' + base64.b64encode(out.getvalue()).decode()


BASE_CSS = """
@page {size:A4;margin:18mm 18mm 20mm;}
@page wide {size:A4 landscape;margin:16mm 18mm;}
* {box-sizing:border-box;}
html {-webkit-print-color-adjust:exact;print-color-adjust:exact;}
body {margin:0;font:10.5pt/1.55 "Noto Sans", sans-serif;color:#1d1d1f;}
h1, h2, h3 {line-height:1.2;margin:0 0 0.4em;}
p {margin:0 0 0.8em;}
.page {break-after:page;}
.page:last-child {break-after:auto;}
.wide {page:wide;}
table {width:100%;border-collapse:collapse;font-size:9.5pt;}
th, td {text-align:left;padding:6px 8px;border-bottom:1px solid #d8d8dc;vertical-align:top;}
th {font-weight:700;}
td.n, th.n {text-align:right;font-variant-numeric:tabular-nums;}
.note {font-size:8pt;color:#6e6e73;margin-top:1.5em;}
img {display:block;max-width:100%;}
"""

# --- News: a city's budget at a glance --------------------------------------------------------------------------------

BUDGET = [
    ('Schools and learning', 412.6, 388.1),
    ('Health and social care', 356.2, 341.9),
    ('Transport and roads', 228.4, 236.0),
    ('Housing', 174.9, 142.3),
    ('Parks, culture and libraries', 88.5, 84.2),
    ('Police, fire and safety', 152.7, 149.8),
    ('Waste and recycling', 61.3, 59.6),
    ('Administration', 47.1, 51.4),
]
QUARTERS = [
    ('Housing', ['Plans approved for 600 homes', '220 homes started', 'Two estates refurbished', '180 homes handed over']),
    ('Transport', ['Night bus trial begins', 'River bridge repairs', 'Twelve new bus lanes', 'Cycle network, phase two']),
    ('Schools', ['Free breakfast clubs', 'Three schools rebuilt', 'Teaching assistants hired', 'New sixth-form college opens']),
    ('Parks and culture', ['Library hours extended', 'Riverside park opens', 'Summer festival funded', 'Two pools reopened']),
]


def news():
    total, previous = sum(b[1] for b in BUDGET), sum(b[2] for b in BUDGET)
    top = max(b[1] for b in BUDGET)
    bars = ''.join(
        '<div class="bar"><span class="label">%s</span><span class="track"><span style="width:%.1f%%"></span></span>'
        '<span class="value">%.1f</span></div>' % (name, 100 * now / top, now) for name, now, _ in BUDGET)
    rows = ''.join(
        '<tr><td>%s</td><td class="n">%.1f</td><td class="n">%.1f</td><td class="n">%+.1f%%</td><td class="n">%.1f%%</td></tr>'
        % (name, now, before, 100 * (now - before) / before, 100 * now / total) for name, now, before in BUDGET)
    plan = ''.join('<tr><th>%s</th>%s</tr>' % (area, ''.join('<td>%s</td>' % s for s in steps)) for area, steps in QUARTERS)
    css = BASE_CSS + """
h1, h2 {font-family:"Noto Serif", serif;}
.kicker {font:700 8.5pt/1 "Noto Sans", sans-serif;letter-spacing:0.12em;text-transform:uppercase;color:#c8102e;margin-bottom:10px;}
h1 {font-size:30pt;margin-bottom:10px;}
.lead {font-size:12.5pt;color:#3a3a3c;}
.cover img {width:100%;height:78mm;object-fit:cover;margin:6mm 0;}
.figures {display:grid;grid-template-columns:repeat(3, 1fr);gap:6mm;margin:4mm 0 6mm;}
.figures div {border-top:3px solid #c8102e;padding-top:3mm;}
.figures strong {display:block;font:700 22pt/1.1 "Noto Serif", serif;}
.figures span {font-size:9pt;color:#6e6e73;}
h2 {font-size:17pt;margin-top:2mm;}
.bar {display:grid;grid-template-columns:52mm 1fr 14mm;align-items:center;gap:3mm;margin:2.2mm 0;font-size:9.5pt;}
.track {height:5mm;background:#f0f0f2;}
.track span {display:block;height:100%;background:#c8102e;}
.value {text-align:right;font-variant-numeric:tabular-nums;}
table.plan th {width:30mm;}
table.plan td {width:25%;}
table.plan thead th {border-bottom:2px solid #1d1d1f;}
tfoot td {font-weight:700;border-bottom:0;border-top:2px solid #1d1d1f;}
"""
    body = """
<section class="page cover">
<div class="kicker">Hammond News · Explainer</div>
<h1>Riverton's 2027 budget at a glance</h1>
<p class="lead">Where the council plans to spend %(total).1f million next year, what changes from this year, and what residents should
see built, opened and repaired quarter by quarter.</p>
<img src="%(image)s" alt="" />
<div class="figures">
<div><strong>%(total_m)sm</strong><span>planned spending in 2027</span></div>
<div><strong>%(change)+.1f%%</strong><span>compared with 2026 (%(previous_m)sm)</span></div>
<div><strong>+%(housing).0f%%</strong><span>for housing, the largest rise</span></div>
</div>
<p>The council approved the budget after six weeks of public consultation, in which more than four thousand residents took part. Lorem
ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat
cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
</section>
<section class="page">
<h2>Spending by area</h2>
<p>Millions, 2027 plan.</p>
%(bars)s
<h2 style="margin-top:8mm">Compared with this year</h2>
<table>
<thead><tr><th>Area</th><th class="n">2027 (m)</th><th class="n">2026 (m)</th><th class="n">Change</th><th class="n">Share</th></tr></thead>
<tbody>%(rows)s</tbody>
<tfoot><tr><td>Total</td><td class="n">%(total).1f</td><td class="n">%(previous).1f</td><td class="n">%(change)+.1f%%</td><td class="n">100%%</td></tr></tfoot>
</table>
<p class="note">Riverton, its council and these figures are fictional: sample content for demonstrating Joomla 3.x UTD.</p>
</section>
<section class="page wide">
<h2>What to expect, quarter by quarter</h2>
<p>The council's own timetable for the projects the budget pays for.</p>
<table class="plan">
<thead><tr><th>Area</th><th>January to March</th><th>April to June</th><th>July to September</th><th>October to December</th></tr></thead>
<tbody>%(plan)s</tbody>
</table>
<p style="margin-top:6mm">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem
aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem
quia voluptas sit aspernatur aut odit aut fugit.</p>
<p class="note">Riverton, its council and these figures are fictional: sample content for demonstrating Joomla 3.x UTD.</p>
</section>
""" % {'total': total, 'previous': previous, 'total_m': '{:,.1f}'.format(total), 'previous_m': '{:,.1f}'.format(previous), 'change': 100 * (total - previous) / previous,
       'housing': 100 * (BUDGET[3][1] - BUDGET[3][2]) / BUDGET[3][2], 'bars': bars, 'rows': rows, 'plan': plan,
       'image': picture('images/sampledata/news/finance/finance-05.webp', 1100)}
    return 'Riverton\'s 2027 budget at a glance', 'Hammond News', css, body


# --- Blog: a reading list ---------------------------------------------------------------------------------------------

BOOKS = [
    ('Walden', 'Henry David Thoreau', 1854, 'Two years in a cabin by a pond, and a long argument for wanting less. Read a chapter a week.'),
    ('Meditations', 'Marcus Aurelius', 'c. 180', 'An emperor\'s private notes to himself. Short enough for a bus ride, deep enough for a decade.'),
    ('Essays', 'Michel de Montaigne', 1580, 'The book that invented the essay: a man trying to find out what he thinks by writing it down.'),
    ('The Wind in the Willows', 'Kenneth Grahame', 1908, 'Messing about in boats, for grown-ups who have forgotten how.'),
    ('A Room of One\'s Own', 'Virginia Woolf', 1929, 'On money, time and a door that locks: what anyone needs to make anything.'),
    ('Middlemarch', 'George Eliot', 1871, 'A whole town, its hopes and its gossip. Slow, and worth every page.'),
    ('The Compleat Angler', 'Izaak Walton', 1653, 'Ostensibly about fishing; actually about patience, rivers and good company.'),
    ('Leaves of Grass', 'Walt Whitman', 1855, 'Poems to read out loud, outdoors, preferably at full volume.'),
]


def blog():
    rows = ''.join('<tr><td class="box"></td><td><strong>%s</strong><br /><span>%s, %s</span></td><td>%s</td></tr>'
                   % (title, author, year, why) for title, author, year, why in BOOKS)
    css = BASE_CSS + """
body {font-family:"Noto Serif", serif;font-size:11pt;color:#2b2233;}
.cover {display:grid;grid-template-rows:auto 1fr;min-height:255mm;}
.cover img {width:100%;height:120mm;object-fit:cover;border-radius:0;}
.masthead {font:700 9pt/1 "Noto Sans", sans-serif;letter-spacing:0.14em;text-transform:uppercase;color:#7a4fb5;margin:8mm 0 4mm;}
h1 {font-size:34pt;font-weight:700;}
.lead {font-size:13pt;font-style:italic;color:#5b4a6b;}
h2 {font-size:16pt;}
table {font-size:10pt;}
td span {font-family:"Noto Sans", sans-serif;font-size:8.5pt;color:#6e6377;}
td.box {width:9mm;}
td.box::before {content:"";display:block;width:4.5mm;height:4.5mm;border:1.2px solid #2b2233;margin-top:1mm;}
.wide .grid {display:grid;grid-template-columns:repeat(4, 1fr);gap:6mm;margin-top:6mm;}
.wide .grid div {border-top:3px solid #f2c94c;padding-top:3mm;min-height:52mm;}
.wide .grid strong {font-family:"Noto Sans", sans-serif;font-size:9pt;letter-spacing:0.1em;text-transform:uppercase;}
.lines {margin-top:3mm;height:36mm;background:repeating-linear-gradient(to bottom, transparent 0 8.5mm, #d9d2df 8.5mm 9mm);}
"""
    body = """
<section class="page cover">
<img src="%(image)s" alt="" />
<div>
<div class="masthead">Field Notes · Printable</div>
<h1>A reading list for slower days</h1>
<p class="lead">Eight old books, all in the public domain and free to read, chosen for long evenings, train journeys and the kind of
attention our phones keep taking away.</p>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim
ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Print it, tick the boxes, and
take your time.</p>
<p>— Finch Hartley</p>
</div>
</section>
<section class="page">
<h2>The list</h2>
<table>
<thead><tr><th></th><th>Book</th><th>Why read it</th></tr></thead>
<tbody>%(rows)s</tbody>
</table>
<p class="note">All eight books are in the public domain; free editions are easy to find at any library. Field Notes and its author
are fictional: sample content for demonstrating Joomla 3.x UTD.</p>
</section>
<section class="page wide">
<h2>A season of reading</h2>
<p>Two books a season, and room for your own notes.</p>
<div class="grid">
<div><strong>Winter</strong><p>Middlemarch<br />Meditations</p><div class="lines"></div></div>
<div><strong>Spring</strong><p>Walden<br />The Compleat Angler</p><div class="lines"></div></div>
<div><strong>Summer</strong><p>The Wind in the Willows<br />Leaves of Grass</p><div class="lines"></div></div>
<div><strong>Autumn</strong><p>Essays<br />A Room of One's Own</p><div class="lines"></div></div>
</div>
</section>
""" % {'rows': rows, 'image': picture('images/sampledata/news/opinion/opinion-21.webp', 1100)}
    return 'A reading list for slower days', 'Field Notes', css, body


# --- Studio: a studio profile -----------------------------------------------------------------------------------------

WORK = [
    ('Northwind Architects', 'Brand identity, web design', 'images/sampledata/studio/orange-towers.webp', '3×', 'longer visits'),
    ('Aperture Museum of Light', 'Web design, motion', 'images/sampledata/studio/light-painting.webp', '+54%', 'online ticket sales'),
    ('Aurora Outfitters', 'E-commerce, web design', 'images/sampledata/studio/polar-lights.webp', '-38%', 'returns'),
]
PHASES = [('Listen', 0, 2), ('Shape', 1, 3), ('Design', 2, 5), ('Build', 3, 7), ('Test with people', 4, 7), ('Launch', 7, 8)]


def studio():
    work = ''.join('<div class="work"><img src="%s" alt="" /><h3>%s</h3><p class="services">%s</p><p class="figure"><strong>%s</strong> %s</p></div>'
                   % (picture(image, 640), client, services, figure, label) for client, services, image, figure, label in WORK)
    weeks = ''.join('<span>Week %d</span>' % w for w in range(1, 9))
    gantt = ''.join('<div class="phase"><span class="name">%s</span><span class="track"><span style="grid-column:%d / %d"></span></span></div>'
                    % (name, start + 1, end + 1) for name, start, end in PHASES)
    css = BASE_CSS + """
@page cover {size:A4;margin:0;}
body {color:#16151a;}
.cover {page:cover;position:relative;height:297mm;background:#111014;color:#fff;overflow:hidden;}
.cover img {width:100%;height:190mm;object-fit:cover;}
.cover .text {padding:12mm 18mm;}
.cover .brand {font:700 10pt/1 "Noto Sans", sans-serif;letter-spacing:0.18em;text-transform:uppercase;color:#ff7a3d;margin-bottom:6mm;}
.cover h1 {font-size:36pt;font-weight:800;}
.cover p {font-size:12.5pt;color:#c9c6d2;max-width:150mm;}
h2 {font-size:20pt;font-weight:800;}
.services-list {display:grid;grid-template-columns:repeat(2, 1fr);gap:4mm 8mm;margin:6mm 0 8mm;}
.services-list div {border-left:3px solid #ff7a3d;padding-left:4mm;}
.services-list strong {display:block;}
.numbers {display:grid;grid-template-columns:repeat(4, 1fr);gap:4mm;margin-top:6mm;}
.numbers strong {display:block;font-size:22pt;font-weight:800;}
.numbers span {font-size:9pt;color:#6e6b78;}
.works {display:grid;grid-template-columns:repeat(3, 1fr);gap:6mm;margin-top:6mm;}
.work img {width:100%;height:48mm;object-fit:cover;margin-bottom:3mm;}
.work h3 {font-size:11.5pt;margin-bottom:1mm;}
.work .services {font-size:8.5pt;color:#6e6b78;margin-bottom:2mm;}
.work .figure strong {font-size:16pt;font-weight:800;color:#e85d1f;}
.weeks, .phase .track {display:grid;grid-template-columns:repeat(8, 1fr);}
.weeks {margin-left:45mm;font-size:8.5pt;color:#6e6b78;border-bottom:1px solid #d8d8dc;padding-bottom:2mm;}
.phase {display:grid;grid-template-columns:45mm 1fr;align-items:center;margin:3.5mm 0;}
.phase .track {height:9mm;background:repeating-linear-gradient(to right, #f3f2f6 0 calc(12.5% - 1px), #e2e0e8 calc(12.5% - 1px) 12.5%);}
.phase .track span {background:#ff7a3d;border-radius:2mm;}
"""
    body = """
<section class="page cover">
<img src="%(image)s" alt="" />
<div class="text">
<div class="brand">Rookwood Studio</div>
<h1>Studio profile 2026</h1>
<p>A small design and technology studio. We make brands, websites and products that are fast, accessible and built to last.</p>
</div>
</section>
<section class="page">
<h2>Who we are</h2>
<p>Four people, ten years and more than a hundred and twenty projects. We work closely with a few clients at a time, from the first
conversation to the day we hand over the keys. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua.</p>
<div class="services-list">
<div><strong>Brand identity</strong>Names, logos, type and the systems that hold them together.</div>
<div><strong>Web design</strong>Sites that load fast on any phone and read well on every screen.</div>
<div><strong>Product design</strong>Dashboards and tools for the people who use them all day.</div>
<div><strong>Engineering</strong>Small, well-named components your own team can keep.</div>
</div>
<div class="numbers">
<div><strong>10</strong><span>years</span></div>
<div><strong>120+</strong><span>projects</span></div>
<div><strong>4</strong><span>people</span></div>
<div><strong>AA</strong><span>WCAG level, every site</span></div>
</div>
<h2 style="margin-top:10mm">Selected work</h2>
<div class="works">%(work)s</div>
</section>
<section class="page wide">
<h2>How a project runs</h2>
<p>Eight weeks from the first conversation to launch, for a typical website. Phases overlap on purpose.</p>
<div class="weeks">%(weeks)s</div>
%(gantt)s
<p class="note">Rookwood Studio, its people, clients and figures are fictional: sample content for demonstrating Joomla 3.x UTD.
hello@example.com</p>
</section>
""" % {'image': picture('images/sampledata/studio/light-curves.webp', 1240), 'work': work, 'weeks': weeks, 'gantt': gantt}
    return 'Studio profile 2026', 'Rookwood Studio', css, body


SETS = {
    'news': ('images/sampledata/pdf/city-budget-2027.pdf', news),
    'blog': ('images/sampledata/pdf/reading-list.pdf', blog),
    'studio': ('images/sampledata/pdf/studio-profile.pdf', studio),
}


def build(name):
    target, make = SETS[name]
    title, author, css, body = make()
    page = ('<!DOCTYPE html>\n<html lang="en">\n<head>\n<meta charset="utf-8" />\n<title>%s</title>\n<meta name="author" content="%s" />\n'
            '<style>%s</style>\n</head>\n<body>%s</body>\n</html>\n') % (title, author, css, body)
    with tempfile.TemporaryDirectory() as tmp:
        source = os.path.join(tmp, name + '.html')
        open(source, 'w').write(page)
        out = os.path.join(REPO, target)
        os.makedirs(os.path.dirname(out), exist_ok=True)
        subprocess.run([CHROME, '--headless=new', '--disable-gpu', '--no-pdf-header-footer', '--user-data-dir=' + os.path.join(tmp, 'profile'),
                        '--print-to-pdf=' + out, 'file://' + source], check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    print(target, os.path.getsize(out), 'bytes')


for name in sys.argv[1:] or SETS:
    build(name)
