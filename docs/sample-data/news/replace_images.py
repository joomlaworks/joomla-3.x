import json, os, re, sys, time, urllib.request, urllib.parse, io
from PIL import Image
UA = {'User-Agent': 'Mozilla/5.0 (JoomlaWorks sample data builder)'}
OUT = sys.argv[1]
REPLACE = {
 'politics':   [12,19,23],
 'education':  [15],
 'sports':     [10,15],
}
Q = {
 'politics':   ['government building', 'capitol dome', 'parliament', 'ballot box vote', 'flags', 'conference microphone', 'city hall', 'courthouse columns', 'meeting room chairs'],
 'world':      ['city skyline', 'harbor ships', 'airport', 'old town street', 'bridge river', 'mountain village'],
 'finance':    ['stock market', 'money coins', 'financial district'],
 'technology': ['laptop computer', 'circuit board', 'smartphone'],
 'energy':     ['solar panels', 'wind turbines', 'power plant', 'electricity pylons'],
 'education':  ['classroom', 'library books', 'university campus', 'students studying', 'graduation', 'pencils notebook'],
 'health':     ['hospital', 'doctor stethoscope', 'laboratory', 'running fitness', 'yoga', 'healthy food'],
 'sports':     ['football stadium', 'soccer ball', 'tennis', 'basketball court', 'cycling', 'swimming'],
}
BAD = re.compile(r'stamp|postcard|map\b|logo|diagram|chart|graph|poster|flag of|coat of arms|drawing|engraving|illustration|portrait|svg|screenshot|icon'
                 r'|\b1[89]\d\d\b|\b19\d0s\b|president|senator|representative|minister|king|queen|prince|funeral|protest|sketch|painting|vintage|antique|historic'
                 r'|obama|biden|trump|carter|clinton|reagan|kennedy|lincoln|white house|congress(wo)?man|governor|mayor|pope|march|rally|riot|war\b|soldier|army|military|secretary|remarks|speech|delegation|wellington|children|kids|girl|boy', re.I)

def api(q, source):
    url = 'https://api.openverse.org/v1/images/?' + urllib.parse.urlencode({
        'q': q, 'license': 'cc0,pdm', 'source': source, 'aspect_ratio': 'wide', 'size': 'large', 'page_size': 20, 'mature': 'false'})
    for attempt in range(3):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=30) as r:
                return json.load(r).get('results', [])
        except Exception as e:
            print('  api retry', q, e); time.sleep(15)
    return []

def fetch(u):
    with urllib.request.urlopen(urllib.request.Request(u, headers=UA), timeout=60) as r:
        return r.read()

credits = json.load(open(os.path.join(OUT, 'credits.json')))
seen = set(c['source_url'] for c in credits.values())
seen_titles = set((c.get('title') or '').lower() for c in credits.values())
for cat, nums in REPLACE.items():
    pool = []
    for source in ('stocksnap', 'rawpixel'):
        for q in Q[cat]:
            for r in api(q, source):
                w, h = r.get('width') or 0, r.get('height') or 0
                t = (r.get('title') or '')
                if r['id'] in seen or t.lower() in seen_titles or w < 1200 or not h or not 1.3 <= w / h <= 2.1 or BAD.search(t) or BAD.search(' '.join(x.get('name', '') for x in (r.get('tags') or []))):
                    continue
                seen.add(r['id']); seen_titles.add(t.lower())
                pool.append((q, r))
            time.sleep(3.2)
        if len(pool) >= len(nums) * 2:
            break
    byq = {}
    for q, r in pool:
        byq.setdefault(q, []).append(r)
    order = []
    while any(byq.values()):
        for q in Q[cat]:
            if byq.get(q):
                order.append((q, byq[q].pop(0)))
    todo = list(nums)
    for q, r in order:
        if not todo:
            break
        try:
            img = Image.open(io.BytesIO(fetch(r['url']))).convert('RGB')
        except Exception as e:
            print('  skip', r['url'][:80], e); continue
        w, h = img.size
        tw, th = (w, round(w * 9 / 16)) if w / h < 16 / 9 else (round(h * 16 / 9), h)
        left, top = (w - tw) // 2, (h - th) // 2
        img = img.crop((left, top, left + tw, top + th))
        if img.width > 1280:
            img = img.resize((1280, 720), Image.LANCZOS)
        n = todo.pop(0)
        name = '%s/%s-%02d.jpg' % (cat, cat, n)
        img.save(os.path.join(OUT, name), 'JPEG', quality=80, optimize=True, progressive=True)
        credits[name] = {'title': r.get('title'), 'creator': r.get('creator'), 'license': r['license'], 'license_url': r.get('license_url'),
                         'source': r['source'], 'page': r.get('foreign_landing_url'), 'source_url': r['id'], 'query': q, 'size': list(img.size)}
        json.dump(credits, open(os.path.join(OUT, 'credits.json'), 'w'), indent=1)
        print(name, '<-', r['source'], (r.get('title') or '')[:70])
    if todo:
        print('MISSING', cat, todo)
