"""Writes media/system/fonts/bootstrap-icons.woff2 and .woff: Bootstrap Icons' font (npm bootstrap-icons 1.13.2, MIT) cut
down to the glyphs our stylesheets draw (the .icon-* classes of icomoon.css and the templates, and rules drawing with the
font themselves), about a tenth of the full font's size. The characters are read from every stylesheet in the repository
that names the font (font-family "bootstrap-icons"): content:"\\f2ea" escapes, as docs/icons/icon_font.py writes them.

Usage: font_subset.py <bootstrap-icons package folder> <site root>    (needs fonttools and brotli: pip install fonttools brotli)
Run it again after icon_font.py, or after adding a rule that draws a glyph the font doesn't have yet."""
import os
import re
import subprocess
import sys

from fontTools import subset

package, root = sys.argv[1], sys.argv[2]
files = subprocess.run(['git', 'ls-files', '-c', '-o', '--exclude-standard', '*.css', '*.less'], cwd=root, capture_output=True,
                       text=True, check=True).stdout.split()
characters = set()

for name in files:
    if name.startswith('libraries/vendor/') or name.endswith('.min.css'):
        continue

    text = open(os.path.join(root, name), encoding='utf-8').read()

    if 'bootstrap-icons' in text:
        found = {int(m, 16) for m in re.findall(r'content:\s*["\']\\(f[0-9a-fA-F]{3})["\']', text)}
        characters |= found

        if found:
            print(name, len(found), 'glyphs')

print(len(characters), 'glyphs in all')

for flavor in ('woff2', 'woff'):
    options = subset.Options()
    options.flavor = flavor
    options.layout_features = []
    options.name_IDs = ['*']
    options.notdef_outline = True
    font = subset.load_font('%s/font/fonts/bootstrap-icons.woff2' % package, options)
    subsetter = subset.Subsetter(options)
    subsetter.populate(unicodes=sorted(characters))
    subsetter.subset(font)
    target = os.path.join(root, 'media/system/fonts/bootstrap-icons.' + flavor)
    subset.save_font(font, target, options)
    print(target, os.path.getsize(target), 'bytes')
