"""Converts the fetched images (JPEG, <images>/<section>/<section>-NN.jpg, with credits.json) to the hosted WebP files in this
folder's parent (docs/sample-data/news/), with credits.json and CREDITS.md. Usage: python3 convert_images.py <images folder>"""
import json, os, sys
from PIL import Image
SRC = sys.argv[1]
DST = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
credits = json.load(open(os.path.join(SRC, 'credits.json')))
out = {}
for name in sorted(credits):
    webp = name[:-4] + '.webp'
    os.makedirs(os.path.join(DST, os.path.dirname(webp)), exist_ok=True)
    Image.open(os.path.join(SRC, name)).convert('RGB').save(os.path.join(DST, webp), 'WEBP', quality=76, method=6)
    c = dict(credits[name])
    for k in ('query', 'source_url', 'size'):
        c.pop(k, None)
    out[webp] = c
json.dump(out, open(os.path.join(DST, 'credits.json'), 'w'), indent=1, ensure_ascii=False)
src = {'stocksnap': 'StockSnap', 'rawpixel': 'rawpixel', 'wikimedia': 'Wikimedia Commons'}
lines = open(os.path.join(DST, 'CREDITS.md')).read().split('| Image |')[0].rstrip('\n').split('\n') + ['', '| Image | Title | Creator | Source |', '|---|---|---|---|']
for name in sorted(out):
    r = out[name]
    lines.append('| `%s` | [%s](%s) | %s | %s |' % (name, (r.get('title') or '').replace('|', '/'), r.get('page') or '', (r.get('creator') or '').replace('|', '/'), src.get(r['source'], r['source'])))
open(os.path.join(DST, 'CREDITS.md'), 'w').write('\n'.join(lines) + '\n')
print(len(out), 'images')
