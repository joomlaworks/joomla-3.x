"""Imports a sample data set's content (<set folder>/content.json) into a site through the command line: tags, categories and
articles, with the images of images/sampledata/news (bundled), then the articles' hits. Usage: import.py <site> <q.php> <set folder>"""
import json, os, re, subprocess, sys
PHP = os.environ.get('PHP', 'php')
D, Q, SET = sys.argv[1], sys.argv[2], sys.argv[3]
BASE = 'images/sampledata/news/'

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

def img(path):
    # images/news/politics/politics-01.jpg -> the bundled WebP
    return re.sub(r'images/news/([a-z]+/[a-z]+-\d+)\.jpg', lambda m: BASE + m.group(1) + '.webp', path)

c = json.load(open(SET + '/content.json'))
tags = sorted({t for a in c['articles'] for t in a['tags']})
for tag in tags:
    cli('tag:create', '--title=' + tag)

for cat in c['categories']:
    cli('category:create', '--title=' + cat['title'], '--alias=' + cat['alias'], '--description=' + cat['description'], '--state=published')

hits = {}
for n, a in enumerate(c['articles'], 1):
    text = img(a['intro']) + '\n<hr id="system-readmore" />\n' + img(a['full'])
    t = a['tags']
    d = cli('article:create', '--state=published', '--title=' + a['title'], '--category=' + a['category'], '--text-file=-',
            '--author-alias=' + a['author'], '--publish-up=' + a['publish_up'], '--featured=' + ('yes' if a['featured'] else 'no'),
            '--image-intro=' + img(a['image']), '--image-intro-alt=' + a['image_alt'], '--image-full=' + img(a['image']),
            '--image-full-alt=' + a['image_alt'], '--tags=' + ','.join(t), stdin=text)
    aid = (d.get('data') or {}).get('id') or ((d.get('data') or {}).get('article') or {}).get('id')
    if aid:
        hits[int(aid)] = int(a['hits'])
    if n % 20 == 0:
        print(n, 'articles')

for aid, h in hits.items():
    sql('UPDATE #__content SET hits = %d WHERE id = %d' % (h, aid))
print('hits', len(hits))
