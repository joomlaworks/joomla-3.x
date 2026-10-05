"""Generates the "news" sample content: 10 categories, 22 articles each, placeholder (lorem ipsum) text with
category-matched media. Output: content.json, consumed by import_content.sh on the dev site and later by the
sample data SQL builder. Deterministic (fixed seed)."""
import json, random, datetime, html, sys

random.seed(20261005)
MEDIA = json.load(open('media.json'))
IMG_BASE = sys.argv[1] if len(sys.argv) > 1 else 'images/news'   # build/import.py turns images/news/... into the bundled WebP files

CATS = [
 ('politics', 'Politics', 'Parliament, government and the people who run them.'),
 ('world', 'World', 'News from every continent.'),
 ('finance', 'Finance', 'Markets, money, companies and the economy.'),
 ('technology', 'Technology', 'Gadgets, software, science and the internet.'),
 ('energy', 'Energy', 'Power, fuel, renewables and the grid.'),
 ('education', 'Education', 'Schools, universities and learning.'),
 ('health', 'Health', 'Medicine, wellbeing and public health.'),
 ('sports', 'Sports', 'Results, transfers and the stories behind the games.'),
 ('culture', 'Culture', 'Arts, books, film, music and ideas.'),
 ('opinion', 'Opinion', 'Columns and analysis from our writers.'),
]

TITLES = {
 'politics': [
  'Coalition talks stall as parties trade new budget demands', 'Parliament passes sweeping reform of local councils',
  'Opposition leader unveils plan to cut red tape for small firms', 'Snap election speculation grows after cabinet reshuffle',
  'New transparency rules for lobbyists come into force', 'Lawmakers clash over proposed changes to voting districts',
  'Government survives confidence vote by narrow margin', 'Mayors call for more say over regional spending',
  'Senate committee opens inquiry into public contracts', 'Young voters register in record numbers ahead of polls',
  'Minister resigns after row over delayed housing plan', 'Cross-party group proposes ban on campaign text messages',
  'Polls tighten as campaign enters its final week', 'Reform bill heads back to committee after late amendments',
  'Capital braces for march over pension changes', 'Regional assembly votes to hold referendum next spring',
  'Leaders agree on framework for digital voting trial', 'Watchdog warns of rising cost of state consultants',
  'President signs law extending parental leave', 'Party conference closes with pledge on rural transport',
  'Debate night: five takeaways from the leaders\' clash', 'Budget office revises spending forecasts upward',
 ],
 'world': [
  'Floods displace thousands along the river delta', 'Port city reopens after week-long shipping strike',
  'Neighbouring states sign landmark water-sharing deal', 'Ceasefire holds as aid convoys reach border towns',
  'Island nation declares state of emergency over drought', 'Summit ends with joint statement on migration',
  'Ancient trade route revived with new rail link', 'Millions take to the streets for new year celebrations',
  'Wildfire season starts early across the southern coast', 'Diplomats return as embassy reopens after two years',
  'Earthquake drill puts mountain towns to the test', 'Record tourist numbers strain historic old town',
  'Election observers praise calm and orderly vote', 'Heatwave grips the continent for a third week',
  'Refugee camp opens first school for 2,000 children', 'Glacier retreat forces villages to rethink water supply',
  'Fishing fleets clash over disputed northern waters', 'Bridge collapse prompts nationwide safety review',
  'Capital unveils car-free centre after decade of debate', 'Volcano eruption grounds flights across the region',
  'Two nations reopen land border after long closure', 'Monsoon rains bring relief to parched farmland',
 ],
 'finance': [
  'Markets rally as inflation cools for a third month', 'Central bank holds rates steady, signals patience',
  'Tech shares lead gains on strong earnings season', 'Housing market slows as mortgage costs bite',
  'Retailers report best holiday quarter in five years', 'Currency slides after surprise trade deficit',
  'Start-up funding rebounds in the first half', 'Airline profits soar on record summer bookings',
  'Small businesses face late-payment crunch, survey finds', 'Gold hits new high as investors seek safety',
  'Carmaker announces new plant and 3,000 jobs', 'Pension funds shift billions into green bonds',
  'Unemployment falls to lowest level in a decade', 'Merger talks lift shares of regional banks',
  'Consumer confidence dips ahead of budget', 'Shipping costs ease as port backlogs clear',
  'Food prices climb after poor harvest', 'Regulators propose tougher rules for buy-now-pay-later',
  'Bond yields fall on hopes of rate cuts', 'Family firms turn to private equity for growth',
  'Exporters cheer new free-trade agreement', 'Quarterly growth beats forecasts on strong services',
 ],
 'technology': [
  'New smartphone chip promises two-day battery life', 'Open-source project hits ten million downloads',
  'Robots take over the night shift at city warehouse', 'Satellite network brings broadband to remote valleys',
  'Researchers build quantum sensor the size of a coin', 'Space agency tests reusable rocket stage',
  'Browser makers agree on new privacy standard', 'Electric scooters get smarter with built-in safety radar',
  'Data centres race to cut water use', 'Startup unveils translation earbuds for 40 languages',
  'Lunar mission returns first high-resolution images', 'Schools adopt coding lessons from age seven',
  'Cyber attack disrupts ticketing at major airports', 'Wearable sensor tracks hydration in real time',
  'Engineers print a house in under 48 hours', 'Video game studio opens tools to modders',
  'AI assistant helps doctors summarise patient notes', 'Repair rules force makers to sell spare parts',
  'Drone deliveries take off in rural pilot scheme', 'Tiny satellites map crop health from orbit',
  'Encrypted messaging app passes security audit', 'Home robots learn to fold laundry',
 ],
 'energy': [
  'Solar farm the size of 500 football pitches comes online', 'Offshore wind auction attracts record bids',
  'Grid operator warns of tight supply this winter', 'Households install heat pumps in record numbers',
  'Battery storage plant to power 100,000 homes', 'Oil prices fall as demand outlook weakens',
  'Hydropower dam upgrade boosts output by a fifth', 'New pylons spark debate in rural valley',
  'Electric vehicle sales overtake diesel for the first time', 'Coal plant closes after 60 years of service',
  'Hydrogen buses join city fleet', 'Energy bills set to fall from next month',
  'Rooftop solar scheme opens to renters', 'Geothermal project taps heat beneath old mine',
  'Smart meters help cut peak demand', 'Interconnector links two national grids',
  'Refinery to switch to renewable diesel', 'Community wind farm pays first dividend',
  'Nuclear plant extension approved for ten years', 'Winter fuel support scheme extended',
  'Tidal turbine breaks generation record', 'Gas storage sites full ahead of winter',
 ],
 'education': [
  'Schools trial four-day week for older pupils', 'University fees frozen for another year',
  'Record number of students choose engineering', 'Teachers welcome cut in admin workload',
  'Free school meals extended to all primary pupils', 'Libraries see surge in young members',
  'New curriculum puts climate science in every grade', 'Apprenticeships rise as firms struggle to hire',
  'Exam results show gap narrowing between regions', 'Campus housing shortage leaves students scrambling',
  'Language learning app partners with state schools', 'Head teachers call for smaller class sizes',
  'University opens first fully online degree', 'Outdoor classrooms boost attention, study finds',
  'Scholarship fund doubles places for rural students', 'Phones banned in classrooms from next term',
  'Adult learners return to college in record numbers', 'School buildings to get energy upgrades',
  'Reading scores climb after tutoring scheme', 'Universities agree on clearer admissions rules',
  'Teachers\' union accepts new pay deal', 'Science fair winners head to international final',
 ],
 'health': [
  'New clinic cuts waiting times for knee surgery', 'Study links daily walks to better sleep',
  'Flu vaccine campaign starts early this year', 'Hospitals hire hundreds of new nurses',
  'Researchers report progress on malaria vaccine', 'Mental health hotline extends opening hours',
  'Sugar tax credited with drop in tooth decay', 'Mobile clinics bring check-ups to rural towns',
  'Air pollution linked to rise in childhood asthma', 'Pharmacies to offer blood pressure checks',
  'Trial shows promise for new arthritis drug', 'Health app helps patients manage diabetes',
  'Heatwave prompts warnings for elderly people', 'Medical school expands to train more family doctors',
  'Cancer screening uptake reaches record high', 'Nutrition labels to show exercise needed to burn calories',
  'Volunteers help clear backlog of blood donations', 'Study finds music therapy eases stress in ICU',
  'Telemedicine visits double in two years', 'New guidelines on screen time for toddlers',
  'Hospital robot assists in first heart operation', 'Running clubs boom as more people take up exercise',
 ],
 'sports': [
  'Late winner sends hosts into the final', 'Marathon record falls in perfect conditions',
  'Teenage sensation wins first major title', 'Club confirms signing of star midfielder',
  'Cycling tour ends with dramatic sprint finish', 'Swimmers break three national records in one night',
  'Coach steps down after eight years in charge', 'Underdogs stun champions in cup upset',
  'Tennis veteran announces retirement', 'Basketball league expands to two new cities',
  'Olympic venue to become community sports park', 'Women\'s league attendance hits all-time high',
  'Rain delays final day of the championship', 'Goalkeeper\'s heroics earn a famous draw',
  'Sailing crew completes round-the-world race', 'New stadium opens with sold-out derby',
  'Athletics star returns from injury with a win', 'Fans vote for the goal of the season',
  'Youth academy produces another first-team starter', 'Rugby side seals the title with a game to spare',
  'Esports team qualifies for world championship', 'Mountain race draws runners from 40 countries',
 ],
 'culture': [
  'Museum reopens with dazzling new wing', 'Debut novel tops the bestseller list',
  'Film festival announces its opening night premiere', 'Orchestra takes concerts to small towns',
  'Street artists transform harbour warehouses', 'Theatre revival sells out in hours',
  'Lost painting found in attic goes on show', 'Music festival goes plastic-free',
  'Poetry prize shortlist celebrates new voices', 'Animated short wins international award',
  'Historic cinema saved by local campaign', 'Photography exhibition captures city at night',
  'Jazz club marks 50 years with week of shows', 'Architecture biennale opens to the public',
  'Comic book fair draws record crowds', 'Ballet company tours with new production',
  'Writers\' residency opens in lighthouse', 'Folk traditions revived by young musicians',
  'Design week showcases recycled materials', 'Classic film restored for its anniversary',
  'Community choir goes viral with rooftop concert', 'Library unveils rare manuscript collection',
 ],
 'opinion': [
  'Why our cities need fewer cars and more trees', 'The quiet revolution in how we learn',
  'We should talk about the cost of convenience', 'In praise of the local newspaper',
  'Budgets are moral documents. Read them that way', 'The four-day week is closer than you think',
  'Sport still has the power to bring us together', 'Our energy future will be built on rooftops',
  'What the pandemic taught us about trust', 'The case for slower technology',
  'Housing is the defining issue of this decade', 'Let\'s stop pretending exams measure talent',
  'Small towns deserve big ambitions', 'Why I still believe in public service',
  'The art of disagreeing well', 'Our data, our rules', 'A greener economy needs patient money',
  'Libraries are the last truly public places', 'The next generation will not wait',
  'Politics needs more listening, less shouting', 'Culture is infrastructure too', 'Notes on a decade of change',
 ],
}

REPORTERS = ['Anna Kovač', 'Daniel Okafor', 'Lena Marsh', 'Tomás Rivera', 'Priya Natarajan', 'Jonas Berg', 'Maya Lindqvist',
             'Samuel Adeyemi', 'Clara Moretti', 'Felix Hartmann', 'Nora Delacroix', 'Ravi Mehta', 'Elena Petrova', 'Owen Gallagher',
             'Sofia Alvarez', 'Hugo Lambert']
COLUMNISTS = ['Margaret Ellison', 'Victor Hale', 'Amara Osei', 'Julian Crane', 'Ingrid Sørensen', 'Theo Marchetti']

TAGS = {  # featured tags: shown in the header's tags bar
 'politics': ['Elections'], 'world': ['Climate'], 'finance': ['Markets'], 'technology': ['AI', 'Space'],
 'energy': ['Climate', 'Renewables'], 'education': ['Schools'], 'health': ['Wellbeing'], 'sports': ['World Cup'],
 'culture': ['Festivals'], 'opinion': [],
}

WORDS = ('lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua '
         'enim ad minim veniam quis nostrud exercitation ullamco laboris nisi aliquip ex ea commodo consequat duis aute irure in '
         'reprehenderit voluptate velit esse cillum fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt culpa '
         'qui officia deserunt mollit anim id est laborum curabitur pretium tincidunt lacus nunc vulputate lectus sagittis vitae '
         'aliquet mauris integer fringilla viverra ornare morbi luctus felis eget porta tortor gravida facilisis').split()

def sentence(n_min=8, n_max=18):
    words = [random.choice(WORDS) for _ in range(random.randint(n_min, n_max))]
    s = ' '.join(words)
    if len(words) > 6 and random.random() < 0.3:
        i = random.randint(2, len(words) - 3)
        words[i] += ','
        s = ' '.join(words)
    return s[0].upper() + s[1:] + '.'

def paragraph(sentences=None):
    return ' '.join(sentence() for _ in range(sentences or random.randint(3, 6)))

def table(cat):
    labels = {'finance': ('Index', 'Close', 'Change'), 'sports': ('Team', 'Played', 'Points'), 'energy': ('Source', 'Share', 'Change'),
              'politics': ('Party', 'Seats', 'Change'), 'education': ('Region', 'Pass rate', 'Change'), 'health': ('Region', 'Cases', 'Change'),
              'technology': ('Device', 'Battery', 'Score'), 'world': ('City', 'Population', 'Change'), 'culture': ('Title', 'Visitors', 'Rating'),
              'opinion': ('Item', 'Value', 'Change')}[cat]
    rows = []
    for _ in range(random.randint(4, 6)):
        name = ' '.join(w.capitalize() for w in random.sample(WORDS, 2))
        rows.append('<tr><td>%s</td><td>%s</td><td>%s</td></tr>' % (name, '{:,}'.format(random.randint(12, 98000)),
                    ('+' if random.random() < 0.6 else '-') + '%.1f%%' % random.uniform(0.1, 9.9)))
    return ('<table class="table">\n<caption>%s</caption>\n<thead><tr><th>%s</th><th>%s</th><th>%s</th></tr></thead>\n<tbody>\n%s\n</tbody>\n</table>'
            % (sentence(4, 7).rstrip('.'), labels[0], labels[1], labels[2], '\n'.join(rows)))

def blist():
    tag = random.choice(['ul', 'ol'])
    return '<%s>\n%s\n</%s>' % (tag, '\n'.join('<li>%s</li>' % sentence(5, 12) for _ in range(random.randint(3, 6))), tag)

def blockquote(cat):
    who = random.choice(REPORTERS + COLUMNISTS)
    return '<blockquote><p>%s</p><cite>%s</cite></blockquote>' % (sentence(14, 26), html.escape(who))

def youtube(cat):
    v = random.choice(MEDIA['youtube'][cat])
    return ('<figure class="embed video"><iframe width="1280" height="720" src="https://www.youtube-nocookie.com/embed/%s" title="%s" '
            'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
            'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></figure>' % (v['id'], html.escape(v['title'])))

def xpost(cat):
    return '<figure class="embed x-post">%s</figure>' % random.choice(MEDIA['x'][cat])['html']

def inline_image(cat, own):
    n = random.choice([i for i in range(1, 25) if i != own])
    return ('<figure class="image"><img src="%s/%s/%s-%02d.jpg" alt="%s" width="1280" height="720" loading="lazy" />'
            '<figcaption>%s</figcaption></figure>' % (IMG_BASE, cat, cat, n, html.escape(sentence(3, 6).rstrip('.')), sentence(6, 12)))

now = datetime.datetime(2026, 10, 5, 18, 0)
articles = []
for c, (alias, title, desc) in enumerate(CATS):
    for i, headline in enumerate(TITLES[alias]):
        n = i + 1
        # Spread over 21 days; the first of each category is the most recent
        when = now - datetime.timedelta(hours=i * 22 + c * 2 + random.randint(0, 10), minutes=random.randint(0, 59))
        intro = '<p>%s</p>' % ' '.join(sentence(10, 16) for _ in range(random.randint(2, 3)))
        parts = [paragraph(), paragraph()]
        body = ['<p>%s</p>' % p for p in parts]
        extras = []
        if random.random() < 0.45: extras.append(blockquote(alias))
        if random.random() < 0.35: extras.append(blist())
        if random.random() < 0.25: extras.append(table(alias))
        if random.random() < 0.25: extras.append(inline_image(alias, n))
        r = random.random()
        if r < 0.18: extras.append(youtube(alias))
        elif r < 0.33: extras.append(xpost(alias))
        random.shuffle(extras)
        for k, extra in enumerate(extras):
            if random.random() < 0.4:
                body.append('<h2>%s</h2>' % sentence(3, 6).rstrip('.'))
            body.append(extra)
            body.append('<p>%s</p>' % paragraph())
        body.append('<p>%s</p>' % paragraph(random.randint(2, 4)))
        tags = list(TAGS[alias]) if random.random() < 0.6 else []
        articles.append({
            'category': alias,
            'title': headline,
            'intro': intro,
            'full': '\n'.join(body),
            'image': '%s/%s/%s-%02d.jpg' % (IMG_BASE, alias, alias, n),
            'image_alt': headline,
            'author': COLUMNISTS[i % len(COLUMNISTS)] if alias == 'opinion' else random.choice(REPORTERS),
            'publish_up': when.strftime('%Y-%m-%d %H:%M'),
            # Featured: the newest two of the news categories (the hero and the featured row pick from these)
            'featured': alias != 'opinion' and i < 2 and c < 6,
            'tags': tags,
            'hits': random.randint(40, 9000),
            'has': [e.split(' ')[0].strip('<') for e in extras],
        })

json.dump({'categories': [{'alias': a, 'title': t, 'description': '<p>%s</p>' % d} for a, t, d in CATS], 'articles': articles},
          open('content.json', 'w'), indent=1, ensure_ascii=False)
from collections import Counter
print(len(articles), 'articles;', sum(a['featured'] for a in articles), 'featured;',
      Counter(h for a in articles for h in a['has']), Counter(t for a in articles for t in a['tags']))
