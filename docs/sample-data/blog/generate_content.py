"""Writes the Blog set's content.json from the News set's opinion articles (../news/content.json): 20 of them as the posts of a
personal blog, in four topics, by one (fictional) author, a post every few days, with the opinion images of
images/sampledata/news (no images of its own). Usage: python3 generate_content.py"""
import datetime, json, os, random

HERE = os.path.dirname(os.path.abspath(__file__))
news = json.load(open(os.path.join(HERE, '..', 'news', 'content.json')))
opinion = [a for a in news['articles'] if a['category'] == 'opinion']
random.seed(20261006)

AUTHOR = 'Finch Hartley'
categories = [
    {'alias': 'places', 'title': 'Places', 'description': '<p>Cities, towns and the places we share.</p>'},
    {'alias': 'ideas', 'title': 'Ideas', 'description': '<p>Learning, public life and how we talk to each other.</p>'},
    {'alias': 'technology', 'title': 'Technology', 'description': '<p>The tools we live with, and the ones we could do without.</p>'},
    {'alias': 'work-life', 'title': 'Work & Life', 'description': '<p>Work, energy, money and the years ahead.</p>'},
]
# Opinion article (by title) -> topic and tags; the two left out don't suit an essay blog ("Budgets are moral documents…",
# "Sport still has the power…")
plan = [
    ('Why our cities need fewer cars and more trees', 'places', ['Cities', 'Climate']),
    ('The quiet revolution in how we learn', 'ideas', ['Education']),
    ('We should talk about the cost of convenience', 'technology', ['Technology']),
    ('In praise of the local newspaper', 'technology', ['Media']),
    ('The four-day week is closer than you think', 'work-life', ['Work']),
    ('Our energy future will be built on rooftops', 'work-life', ['Climate']),
    ('What the pandemic taught us about trust', 'technology', ['Society']),
    ('The case for slower technology', 'technology', ['Technology']),
    ('Housing is the defining issue of this decade', 'places', ['Cities', 'Housing']),
    ("Let's stop pretending exams measure talent", 'ideas', ['Education']),
    ('Small towns deserve big ambitions', 'places', ['Cities']),
    ('Why I still believe in public service', 'ideas', ['Society']),
    ('The art of disagreeing well', 'ideas', ['Society']),
    ('Our data, our rules', 'technology', ['Technology', 'Media']),
    ('A greener economy needs patient money', 'work-life', ['Climate', 'Work']),
    ('Libraries are the last truly public places', 'places', ['Education', 'Cities']),
    ('The next generation will not wait', 'work-life', ['Climate']),
    ('Politics needs more listening, less shouting', 'ideas', ['Society']),
    ('Culture is infrastructure too', 'places', ['Culture']),
    ('Notes on a decade of change', 'work-life', ['Society']),
]
by_title = {a['title']: a for a in opinion}

# The newest post first; one every two to four days before it, at varied times
when = datetime.datetime(2026, 10, 5, 9, 20)
articles = []
for title, category, tags in plan:
    a = by_title[title]
    articles.append({
        'category': category, 'title': title, 'intro': a['intro'], 'full': a['full'],
        'image': a['image'], 'image_alt': a['image_alt'], 'author': AUTHOR,
        'publish_up': when.strftime('%Y-%m-%d %H:%M'), 'featured': True, 'tags': tags,
        'hits': random.randint(180, 2600),
    })
    when -= datetime.timedelta(days=random.randint(2, 4), hours=random.randint(-5, 5), minutes=random.randint(0, 59))

json.dump({'categories': categories, 'articles': articles}, open(os.path.join(HERE, 'content.json'), 'w'), indent=1, ensure_ascii=False)
print(len(articles), 'posts,', articles[-1]['publish_up'], 'to', articles[0]['publish_up'])
