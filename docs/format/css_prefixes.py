"""Removes obsolete vendor prefixes from stylesheets in the project's CSS style (maintainer, 3.18: only -webkit- properties
Chrome supports that aren't standard stay). Prefixed declarations Chrome treats as aliases (-webkit-border-radius,
-webkit-box-shadow, -webkit-appearance, -webkit-mask, -webkit-backdrop-filter, ...) become the standard property, with
the value Chrome used (the last of the pair), at the last one's place; -moz- and -o- declarations and values, prefixed
gradients followed by another value of the property, -webkit-overflow-scrolling and -webkit-margin-top-collapse go;
rules for -moz- pseudo-classes and elements and @-moz-document blocks go whole; ::-webkit-input-placeholder becomes
::placeholder (an earlier rule identical to a later one is then dropped). Kept: -webkit-font-smoothing,
-webkit-text-size-adjust, -webkit-line-clamp with display:-webkit-box and -webkit-box-orient, -webkit-focus-ring-color and
the ::-webkit-scrollbar, search, calendar, details and colour swatch pseudo-elements.

Usage: python3 docs/format/css_prefixes.py [--write] files...    (run docs/format/css.py first; lists the files it would
change, --write changes them). Only for our own stylesheets and Joomla's. Check by comparing the rules Chrome keeps
before and after (AGENTS.md, "CSS style")."""
import re
import sys

sys.path.insert(0, __file__.rsplit('/', 1)[0])
import css  # noqa: E402

ALIAS = {
    '-webkit-border-radius': 'border-radius',
    '-webkit-border-top-left-radius': 'border-top-left-radius',
    '-webkit-border-top-right-radius': 'border-top-right-radius',
    '-webkit-border-bottom-left-radius': 'border-bottom-left-radius',
    '-webkit-border-bottom-right-radius': 'border-bottom-right-radius',
    '-webkit-box-sizing': 'box-sizing',
    '-webkit-box-shadow': 'box-shadow',
    '-webkit-transform': 'transform',
    '-webkit-background-clip': 'background-clip',
    '-webkit-user-select': 'user-select',
    '-webkit-mask': 'mask',
    '-webkit-mask-image': 'mask-image',
    '-webkit-backdrop-filter': 'backdrop-filter',
    '-webkit-appearance': 'appearance',
}
DROP = ('-webkit-overflow-scrolling', '-webkit-margin-top-collapse')
OLD_VALUE = re.compile(r'-(moz|o)-[a-z]|-webkit-(linear-|radial-)?gradient')


def fix_declarations(decls):
    decls = [(p, v) for p, v in decls if not p.startswith(('-moz-', '-o-')) and p not in DROP]
    kept = []
    for i, (p, v) in enumerate(decls):
        if OLD_VALUE.search(v) and (re.search(r'-(moz|o)-', v) or any(q == p for q, w in decls[i + 1:])):
            continue
        kept.append((p, v))
    out = []
    for i, (p, v) in enumerate(kept):
        name = ALIAS.get(p, p)
        if name in ALIAS.values() and any(ALIAS.get(q, q) == name for q, w in kept[i + 1:]):
            continue
        out.append((name, v))
    return out


def process(text):
    entries = []
    for item in css.items(text):
        sep = '\n' * item[-1] if item[-1] else ' '
        if item[0] in ('comment', 'stmt'):
            entries.append([sep, item[1], None])
            continue
        prelude, body = item[1], item[2]
        comments, sel = css.selector(prelude)
        if sel.startswith('@-moz-document'):
            continue
        if sel.startswith('@') and css.has_block(body):
            entries.append([sep, prelude.rstrip() + ' {' + process(body) + '\n}', None])
            continue
        if re.search(r'::?-moz-', sel):
            continue
        new = sel.replace('::-webkit-input-placeholder', '::placeholder')
        if new == sel and not re.search(r'-(webkit|moz|o)-', body):
            entries.append([sep, prelude.rstrip() + ' {' + body + '}', None])
            continue
        inner, decls = css.declarations(body)
        decls = fix_declarations(decls)
        if not decls:
            continue
        head = ''.join(c + '\n' for c in comments) + (new if new != sel else prelude.rstrip())
        rule = head + ' {' + ''.join(c + ' ' for c in inner) + ''.join('%s:%s;' % d for d in decls) + '}'
        entries.append([sep, rule, (new, tuple(decls))])
    for i, entry in enumerate(entries):
        if entry[2] and any(other[2] == entry[2] for other in entries[i + 1:]):
            entry[1] = None
    return ''.join(sep + rule for sep, rule, key in entries if rule is not None)


def strip(src):
    text = src.replace('\r\n', '\n')
    head = ''
    if text.lstrip().startswith('/*'):
        j = text.find('*/') + 2
        head, text = text[:j], text[j:]
    return css.format_css(head + process(text))


if __name__ == '__main__':
    write = '--write' in sys.argv

    for path in [a for a in sys.argv[1:] if not a.startswith('--')]:
        src = open(path, encoding='utf-8').read()
        res = strip(src)
        if res != src:
            if write:
                open(path, 'w', encoding='utf-8').write(res)
            print('changed' if write else 'would change', path)
