import json, os, re, sys, time, urllib.request, urllib.parse, io
from PIL import Image
UA = {'User-Agent': 'Mozilla/5.0 (JoomlaWorks sample data builder)'}
OUT = sys.argv[1]
PER = 24
Q = {
 'politics':   ['parliament building', 'government capitol', 'voting ballot election', 'flags government', 'press conference podium', 'protest rally street'],
 'world':      ['city skyline', 'harbor port ships', 'airport travel', 'crowd city street', 'bridge river city', 'village landscape'],
 'finance':    ['stock market', 'bank building', 'money coins', 'business office meeting', 'financial district skyscrapers', 'calculator accounting'],
 'technology': ['laptop computer', 'smartphone', 'circuit board electronics', 'data center servers', 'robot', 'rocket launch'],
 'energy':     ['solar panels', 'wind turbines', 'power plant', 'electricity pylons', 'hydroelectric dam', 'oil refinery'],
 'education':  ['classroom', 'library books', 'university campus', 'students studying', 'school children', 'graduation'],
 'health':     ['hospital', 'doctor medical', 'laboratory microscope', 'running fitness', 'healthy food vegetables', 'pharmacy medicine'],
 'sports':     ['football stadium', 'athlete running track', 'basketball', 'tennis', 'cycling race', 'swimming pool'],
 'culture':    ['museum gallery', 'concert stage', 'theater', 'art painting exhibition', 'music festival', 'cinema'],
 'opinion':    ['newspaper', 'typewriter', 'writing notebook pen', 'coffee laptop desk', 'microphone', 'books reading'],
}
BAD = re.compile(r'stamp|postcard|map\b|logo|diagram|chart|graph|poster|flag of|coat of arms|drawing|engraving|illustration|portrait|svg|screenshot|icon', re.I)

def api(q):
    url = 'https://api.openverse.org/v1/images/?' + urllib.parse.urlencode({
        'q': q, 'license': 'cc0,pdm', 'source': 'stocksnap,rawpixel,wikimedia', 'aspect_ratio': 'wide', 'size': 'large',
        'page_size': 20, 'mature': 'false'})
    for attempt in range(3):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=30) as r:
                return json.load(r).get('results', [])
        except Exception as e:
            print('  api retry', q, e); time.sleep(10)
    return []

def source_url(r):
    u = r['url']
    # Wikimedia: a 1600px thumbnail instead of the original
    m = re.match(r'https://upload\.wikimedia\.org/wikipedia/commons/(\w)/(\w\w)/([^/]+)$', u)
    if m and r.get('width', 0) > 1600 and not u.lower().endswith(('.tif', '.tiff')):
        return 'https://upload.wikimedia.org/wikipedia/commons/thumb/%s/%s/%s/1600px-%s' % (m.group(1), m.group(2), m.group(3), m.group(3))
    return u

def fetch(u):
    with urllib.request.urlopen(urllib.request.Request(u, headers=UA), timeout=60) as r:
        return r.read()

credits = json.load(open(os.path.join(OUT, 'credits.json'))) if os.path.exists(os.path.join(OUT, 'credits.json')) else {}
for cat, queries in Q.items():
    have = [k for k in credits if k.startswith(cat + '/')]
    if len(have) >= PER:
        continue
    seen, picked = set(c['source_url'] for c in credits.values()), []
    pool = []
    for q in queries:
        for r in api(q):
            w, h = r.get('width') or 0, r.get('height') or 0
            if r['id'] in seen or w < 1200 or not h or not 1.3 <= w / h <= 2.1 or BAD.search(r.get('title') or ''):
                continue
            seen.add(r['id'])
            pool.append((q, r))
        time.sleep(3.5)
    # Spread over the queries: round robin
    byq = {}
    for q, r in pool:
        byq.setdefault(q, []).append(r)
    order = []
    while any(byq.values()):
        for q in queries:
            if byq.get(q):
                order.append((q, byq[q].pop(0)))
    os.makedirs(os.path.join(OUT, cat), exist_ok=True)
    n = len(have)
    for q, r in order:
        if n >= PER:
            break
        try:
            img = Image.open(io.BytesIO(fetch(source_url(r)))).convert('RGB')
        except Exception as e:
            print('  skip', r['url'][:80], e); continue
        # 16:9, at most 1280 wide
        w, h = img.size
        tw, th = (w, round(w * 9 / 16)) if w / h < 16 / 9 else (round(h * 16 / 9), h)
        left, top = (w - tw) // 2, (h - th) // 2
        img = img.crop((left, top, left + tw, top + th))
        if img.width > 1280:
            img = img.resize((1280, 720), Image.LANCZOS)
        n += 1
        name = '%s/%s-%02d.jpg' % (cat, cat, n)
        img.save(os.path.join(OUT, name), 'JPEG', quality=80, optimize=True, progressive=True)
        credits[name] = {'title': r.get('title'), 'creator': r.get('creator'), 'license': r['license'], 'license_url': r.get('license_url'),
                         'source': r['source'], 'page': r.get('foreign_landing_url'), 'source_url': r['id'], 'query': q, 'size': list(img.size)}
        json.dump(credits, open(os.path.join(OUT, 'credits.json'), 'w'), indent=1)
    print(cat, n, 'images')
