"""Formats CSS in the project's style: one rule per line, `sel {prop:value;prop:value;}`, a list of selectors one selector
per line (`.a,` / `.b {...}`, maintainer, 3.18; at-rule preludes and one-line at-rules stay on one line), rules inside
@media/@supports/@keyframes indented 4 spaces, :root variable lists one per line aligned, comments kept (comments inside
a rule's block move above the rule), a blank line before section headings (/* --- Name --- */); otherwise blank lines,
comments at the end of a rule's line, one-line at-rules and the alignment of variable lists stay as written.

Usage: python3 docs/format/css.py [--write] files...    (lists the files it would change; --write changes them)
Only for our own stylesheets and Joomla's: third-party ones stay as their releases ship them (AGENTS.md, "CSS style").
Check a run by comparing the rules Chrome keeps (document.styleSheets) before and after."""
import re
import sys

NESTED_AT = ('@media', '@supports', '@document', '@layer', '@container', '@keyframes', '@-webkit-keyframes',
             '@-moz-keyframes', '@-o-keyframes', '@-ms-keyframes')


def scan(text, i, stop):
    """Index of the first character of `stop` at depth 0 from i (outside strings, comments, parens, brackets), or len."""
    depth = 0
    n = len(text)
    while i < n:
        c = text[i]
        if c in '"\'':
            q = c
            i += 1
            while i < n and text[i] != q:
                i += 2 if text[i] == '\\' else 1
        elif text.startswith('/*', i):
            j = text.find('*/', i + 2)
            i = n if j < 0 else j + 1
        elif c in '([':
            depth += 1
        elif c in ')]':
            depth -= 1
        elif depth == 0 and c in stop:
            return i
        i += 1
    return n


def block_end(text, i):
    """Index of the } closing the block whose { is at i."""
    depth = 0
    n = len(text)
    while i < n:
        c = text[i]
        if c in '"\'':
            q = c
            i += 1
            while i < n and text[i] != q:
                i += 2 if text[i] == '\\' else 1
        elif text.startswith('/*', i):
            j = text.find('*/', i + 2)
            i = n if j < 0 else j + 1
        elif c == '{':
            depth += 1
        elif c == '}':
            depth -= 1
            if depth == 0:
                return i
        i += 1
    raise ValueError('unclosed block')


def collapse(s):
    """Whitespace runs to one space outside strings; trimmed."""
    out, i, n = [], 0, len(s)
    while i < n:
        c = s[i]
        if c in '"\'':
            j = i + 1
            while j < n and s[j] != c:
                j += 2 if s[j] == '\\' else 1
            out.append(s[i:j + 1])
            i = j + 1
        elif c.isspace():
            while i < n and s[i].isspace():
                i += 1
            out.append(' ')
        else:
            out.append(c)
            i += 1
    return ''.join(out).strip()


def split_top(s, sep):
    parts, i = [], 0
    while True:
        j = scan(s, i, sep)
        parts.append(s[i:j])
        if j >= len(s):
            return parts
        i = j + 1


def take_comments(s):
    """(comments, rest): comments found at depth 0 outside strings, and the text without them."""
    comments, out, i, n = [], [], 0, len(s)
    while i < n:
        c = s[i]
        if c in '"\'':
            j = i + 1
            while j < n and s[j] != c:
                j += 2 if s[j] == '\\' else 1
            out.append(s[i:j + 1])
            i = j + 1
        elif s.startswith('/*', i):
            j = s.find('*/', i + 2)
            j = n if j < 0 else j + 2
            comments.append(s[i:j])
            out.append(' ')
            i = j
        else:
            out.append(c)
            i += 1
    return comments, ''.join(out)


def declarations(body):
    comments, body = take_comments(body)
    decls = []
    for part in split_top(body, ';'):
        part = part.strip()
        if not part:
            continue
        k = part.find(':')
        if k < 0:
            raise ValueError('not a declaration: %r' % part)
        decls.append((collapse(part[:k]), collapse(part[k + 1:])))
    return comments, decls


def selector(s):
    comments, s = take_comments(s)
    return comments, ','.join(collapse(p) for p in split_top(s, ','))


def has_block(body):
    return scan(body, 0, '{') < len(body)


def items(text):
    """Top-level items of a stylesheet or block: ('comment', text), ('stmt', text), ('rule', prelude, body), each followed
    by the number of line breaks before it (0: on the line the previous item ended on)."""
    out, i, n = [], 0, len(text)
    gap = 0
    while i < n:
        if text[i].isspace():
            gap += text[i] == '\n'
            i += 1
            continue
        if text.startswith('/*', i):
            j = text.find('*/', i + 2) + 2
            out.append(('comment', text[i:j], gap))
            i, gap = j, 0
            continue
        j = scan(text, i, '{;')
        if j >= n:
            rest = text[i:].strip()
            if rest:
                raise ValueError('trailing text: %r' % rest[:80])
            break
        if text[j] == ';':
            out.append(('stmt', collapse(text[i:j]) + ';', gap))
            i, gap = j + 1, 0
            continue
        k = block_end(text, j)
        out.append(('rule', text[i:j], text[j + 1:k], gap))
        i, gap = k + 1, 0
    return out


def token_block(body):
    """A list of custom properties written one per line: its lines as (name, value, trailing comment), ('comment', text)
    or None for a blank line; None when it isn't written that way."""
    out = []
    for raw in body.strip('\n').split('\n'):
        line = raw.strip()
        if not line:
            out.append(None)
            continue
        if line.startswith('/*') and line.endswith('*/') and line.count('/*') == 1:
            out.append(('comment', line))
            continue
        m = re.match(r'^(-?-?[\w-]+)\s*:\s*(.*?);(\s*/\*.*\*/)?$', line)
        if not m or '/*' in m.group(2):
            return None
        out.append((m.group(1), collapse(m.group(2)), m.group(3) or ''))
    while out and out[0] is None:
        out.pop(0)
    return out


def token_width(body):
    """The column values start at in a list of custom properties, when every line has them at the same one"""
    columns = set()
    for line in body.split('\n'):
        m = re.match(r'^\s*(-?-?[\w-]+:\s*)\S', line)
        if m:
            columns.add(len(m.group(1)))
    return columns.pop() if len(columns) == 1 else None


def selector_lines(indent, sel, split):
    """A rule's selector list, one selector per line (an at-rule's prelude, or a one-line block, on one line)"""
    if not split or sel.startswith('@'):
        return indent + sel
    return ',\n'.join(indent + part for part in split_top(sel, ','))


def render(text, indent='', split=True):
    lines = []

    def blank():
        if lines and lines[-1] != '':
            lines.append('')

    first = True
    for item in items(text):
        gap = item[-1]
        # Blank lines are kept as written; a section heading (/* --- Name --- */) always gets one
        if not first and gap > 1:
            lines.extend([''] * (gap - 1))
        first = False
        if item[0] == 'comment':
            if indent == '' and item[1].startswith('/* ---'):
                blank()
            if gap == 0 and lines and lines[-1].strip():
                # A comment written at the end of a rule's line stays there
                lines[-1] += ' ' + item[1]
            else:
                # A comment over several lines keeps its other lines as written
                lines.append(indent + item[1])
            continue
        if item[0] == 'stmt':
            lines.append(indent + item[1])
            continue
        prelude, body = item[1], item[2]
        pre_comments, sel = selector(prelude)
        for c in pre_comments:
            lines.append(indent + c)
        if sel.startswith('@') and has_block(body):
            if '\n' not in body:
                # An at-rule written on one line stays on one line
                inner = [line.strip() for line in render(body, '', False) if line.strip()]
                lines.append(indent + sel + ' {' + ' '.join(inner) + '}')
                continue
            lines.append(indent + sel + ' {')
            lines.extend(render(body, indent + '    ', split))
            lines.append(indent + '}')
            continue
        if has_block(body):
            raise ValueError('unexpected nested block in %r' % sel)
        comments, decls = declarations(body)
        # A list of custom properties (others may be among them, as color-scheme) written one per line stays so
        tokens = len([p for p, v in decls if p.startswith('--')]) >= 4 and '\n' in body.strip()
        token_lines = token_block(body) if tokens else None
        if token_lines is not None:
            width = token_width(body) or max(len(p) for p, v in decls) + 2
            lines.append(selector_lines(indent, sel, split) + ' {')
            for entry in token_lines:
                if entry is None:
                    if lines[-1] != '':
                        lines.append('')
                elif entry[0] == 'comment':
                    lines.append(indent + '    ' + entry[1])
                else:
                    lines.append(indent + '    ' + (entry[0] + ':').ljust(width) + entry[1] + ';' + entry[2])
            if lines[-1] == '':
                lines.pop()
            lines.append(indent + '}')
            continue
        for c in comments:
            lines.append(indent + c)
        lines.append(selector_lines(indent, sel, split) + ' {' + ''.join('%s:%s;' % (p, v) for p, v in decls) + '}')
    return lines


def format_css(text):
    text = text.replace('\r\n', '\n')
    head = ''
    gap = '\n\n'
    stripped = text.lstrip()
    if stripped.startswith('/*'):
        j = stripped.find('*/') + 2
        head, text = stripped[:j], stripped[j:]
        rest = text.lstrip(' \t')
        gap = '\n' * max(1, len(rest) - len(rest.lstrip('\n')))
        text = text.lstrip()
    lines = render(text)
    while lines and lines[0] == '':
        lines.pop(0)
    while lines and lines[-1] == '':
        lines.pop()
    body = '\n'.join(lines)
    return head + (gap if head and body else '') + body + '\n'


if __name__ == '__main__':
    write = '--write' in sys.argv

    for path in [a for a in sys.argv[1:] if not a.startswith('--')]:
        src = open(path, encoding='utf-8').read()
        res = format_css(src)
        if res != src:
            if write:
                open(path, 'w', encoding='utf-8').write(res)
            print('formatted' if write else 'would format', path)
