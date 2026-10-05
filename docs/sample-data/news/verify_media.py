import json, urllib.request, urllib.parse
UA={'User-Agent':'Mozilla/5.0'}
yt={
 'technology':['_eeZQw9PBc0','aqfNDZlGrpc','ZcXvA5ECVjc','Nw6uUQTq2Uw'],
 'sports':['x-cpRHf4xd4','jcB7QA5N7CE','Ql03zcBjqvc','ZXvFKZM1nL4'],
 'world':['vZUmiPQcAuY','-wO3goJYy8M'],
 'politics':['-wO3goJYy8M','vZUmiPQcAuY','Uep8HSrPGiY'],
 'finance':['J9mltmK4EOI','kZdJdz908DU','AUdW3IbavYw'],
 'energy':['Ajpn4BBshgE','Sqr8ZLU1GSo','2BovWr5ZiWo','zUZqQClUU4A'],
 'education':['OxPlCkTKhzY','fPnwBITSmgU','jLOuMXnM5wk'],
 'health':['uYTfSPy_5kk','p7liQk45fFk','nZYN-1S-Sdo'],
 'culture':['aqz-KE-bpKQ','eRsGyueVLvQ','R6MlUcmOul8'],
 'opinion':['5duz42kHqPs','jLOuMXnM5wk'],
}
x={
 'technology':['NASA/status/2039387543544758736','NASA/status/2041199374827409801','NASA/status/2040393011616452726'],
 'sports':['FIFAWorldCup/status/2071672297728172300','FIFAWorldCup/status/2067309343117066589','FIFAWorldCup/status/2071350323029975545'],
 'world':['UN/status/1778332406774128734','UN/status/1989861475629048303','UNFCCC/status/1981744781966352654'],
 'politics':['WHO/status/2103488508396442075','UN/status/1989861475629048303'],
 'finance':['IMFNews/status/1848714673891275086','WorldBankGroup/status/2048552740607901959'],
 'energy':['IEA/status/1844479279985303863','IEA/status/2013296658335818094','IEA/status/1975448544640213246','IEA/status/1853483607429386672'],
 'education':['UNESCO/status/1960915345331359957','UNESCO/status/1882654622084432146','UNESCO/status/2042517295038124402','UNESCO/status/1995538811665650016'],
 'health':['WHO/status/2103488508396442075','WHO/status/1795952211014414822'],
 'culture':['UNESCO/status/1995538811665650016'],
 'opinion':['UN/status/1778332406774128734'],
}
def get(url):
    try:
        with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=20) as r: return json.load(r)
    except Exception as e: return {'error': str(e)}
out={'youtube':{}, 'x':{}}
cache={}
for cat,ids in yt.items():
    for v in ids:
        if v not in cache: cache[v]=get('https://www.youtube.com/oembed?format=json&url='+urllib.parse.quote('https://www.youtube.com/watch?v='+v))
        d=cache[v]
        if 'error' in d: print('YT FAIL',cat,v,d['error']); continue
        out['youtube'].setdefault(cat,[]).append({'id':v,'title':d['title'],'author':d['author_name']})
for cat,ids in x.items():
    for p in ids:
        if p not in cache: cache[p]=get('https://publish.twitter.com/oembed?omit_script=1&dnt=1&url='+urllib.parse.quote('https://twitter.com/'+p))
        d=cache[p]
        if 'error' in d or 'html' not in d: print('X FAIL',cat,p,d.get('error')); continue
        out['x'].setdefault(cat,[]).append({'url':d['url'],'author':d['author_name'],'html':d['html'].strip()})
json.dump(out, open('media.json','w'), indent=1)
for k in out:
    for cat,v in out[k].items(): print(k,cat,len(v), '|', '; '.join((i.get('title') or i['author'])[:40] + ' (' + (i.get('author','')) + ')' for i in v))
