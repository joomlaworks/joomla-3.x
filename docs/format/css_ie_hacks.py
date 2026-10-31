"""Removes Internet Explorer hacks from stylesheets written one rule per line (the project's CSS style). Declarations:
*prop, _prop, -ms- properties and values, filter:alpha()/progid:, -ms-filter, behavior, expression(), \\9 and \\0 values,
zoom:1. Rules for IE-only pseudo-classes and elements (-ms-), which make their whole selector list invalid in other
browsers, go whole; `* html` selectors (matching nothing elsewhere) leave their lists. @-ms-viewport and IE-only @media
blocks (\\0screen, -ms-high-contrast) go. Kept declarations keep their text.

Usage: python3 docs/format/css_ie_hacks.py [--write] files...    (run docs/format/css.py first; lists the number of
hacks per file, --write removes them). Check by comparing the rules Chrome keeps before and after (AGENTS.md, "No
Internet Explorer hacks")."""
import re
import sys

sys.path.insert(0, __file__.rsplit('/', 1)[0])
from css import split_top  # noqa: E402

RULE = re.compile(r'^(\s*)([^{}@/][^{}]*?) \{(.*)\}\s*$')


def ie_declaration(decl):
    if ':' not in decl:
        return False
    name, value = decl.split(':', 1)
    name, value, low = name.strip(), value.strip(), value.strip().lower()
    if name[:1] in '*_' or name.startswith('-ms-') or name == 'behavior':
        return True
    if name == 'filter' and ('alpha(' in low or 'progid:' in low):
        return True
    if 'expression(' in low or 'progid:' in low or low.startswith('-ms-'):
        return True
    if re.search(r'\\9(\s*!important)?$|\\0/?(\s*!important)?$', value):
        return True
    return name == 'zoom' and low == '1'


def ie_comment(line):
    return bool(re.search(r'\bIE\b|\bIE\d|Internet Explorer|hasLayout', line))


def strip(text):
    lines = text.split('\n')
    out, removed, i = [], 0, 0
    while i < len(lines):
        line = lines[i]
        s = line.strip()
        if s.startswith('@-ms-viewport'):
            removed += 1
            i += 1
            continue
        if s.startswith('@media') and ('\\0screen' in s or '-ms-high-contrast' in s):
            depth = line.count('{') - line.count('}')
            i += 1
            while depth > 0 and i < len(lines):
                depth += lines[i].count('{') - lines[i].count('}')
                i += 1
            removed += 1
            continue
        m = RULE.match(line)
        if m:
            indent, sel, body = m.groups()
            selectors = [x.strip() for x in split_top(sel, ',')]
            drop = any('-ms-' in x for x in selectors)
            kept_sel = [x for x in selectors if not re.match(r'^\*\s*(\+\s*)?html\b|^\*:first-child\+html\b', x)]
            decls = [d.strip() for d in split_top(body, ';') if d.strip()]
            kept = [d for d in decls if not ie_declaration(d)]
            if drop or not kept_sel or (decls and not kept):
                removed += 1
                if out and out[-1].strip().startswith('/*') and out[-1].strip().endswith('*/') and ie_comment(out[-1]):
                    out.pop()
                i += 1
                continue
            if kept != decls or kept_sel != selectors:
                removed += 1
                line = indent + ','.join(kept_sel) + ' {' + ';'.join(kept) + ';}'
        out.append(line)
        i += 1
    # an at-rule block left empty
    text = '\n'.join(out)
    text = re.sub(r'\n[ \t]*@media[^\n{]*\{\n[ \t]*\}\n', '\n', text)
    return text, removed


if __name__ == '__main__':
    write = '--write' in sys.argv

    for path in [a for a in sys.argv[1:] if not a.startswith('--')]:
        src = open(path, encoding='utf-8').read()
        res, n = strip(src)
        if res != src:
            if write:
                open(path, 'w', encoding='utf-8').write(res)
            print('%4d %s' % (n, path))
