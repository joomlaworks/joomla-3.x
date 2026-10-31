"""Re-indents the HTML of PHP template files: one tab per nesting level of HTML elements (<html>'s children not indented),
PHP control lines (if/foreach/...) at the level of the HTML around them and their contents not indented further, tags
spread over lines continued one level in, raw text (script, style, comments) moved with its opening line, <pre> and
<textarea> untouched, multi-line PHP blocks moved with their first line. Void elements closed XHTML style. <!DOCTYPE> on a
line of its own (AGENTS.md, "Template files' markup").

Usage: python3 docs/format/template.py [--write] files...    (lists warnings and the files it would change; --write
changes them). Not for files of PHP functions writing HTML in strings (module chromes' modules.php, pagination.php) or
for Rookwood's block layouts (compact HTML on purpose). Read every warning: branches of an if ending at different depths,
and whitespace after a line ending in ?> appearing or vanishing (PHP eats that newline, so the next line's indentation
is output: harmless between block elements, not in inline text). Check a run by comparing the pages' HTML before and
after with whitespace collapsed."""
import re
import sys

VOID = 'area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr'
VOID_SET = set(VOID.split('|'))
RAW_SHIFT = ('script', 'style')
RAW_KEEP = ('pre', 'textarea')
CONTROL_OPEN = re.compile(r'^\s*(if|foreach|for|while|switch)\s*\(.*\)\s*:(?!:)', re.S)
CONTROL_MID = re.compile(r'^\s*(else\s*if|elseif|else)\b.*:\s*;?\s*$|^\s*else\s*:?\s*;?\s*$', re.S)
CONTROL_END = re.compile(r'^\s*(endif|endforeach|endfor|endwhile|endswitch)\s*;?\s*$')
BRACE_OPEN = re.compile(r'^\s*(if|foreach|for|while|switch)\b.*\{\s*$', re.S)
BRACE_MID = re.compile(r'^\s*\}\s*else(\s*if\b.*)?\s*\{\s*$', re.S)
BRACE_END = re.compile(r'^\s*\}\s*$')
PHP = re.compile(r'<\?(?:php\b|=).*?(?:\?>|$)', re.S)


def mask(line):
    """The line with its PHP blocks replaced by \x01n\x02, the blocks, and whether a block is left open at the end"""
    blocks = []
    out = ''
    pos = 0
    open_end = False

    while True:
        m = re.compile(r'<\?(?:php\b|=)').search(line, pos)

        if not m:
            out += line[pos:]
            break

        out += line[pos:m.start()]
        end = line.find('?>', m.end())

        if end < 0:
            blocks.append(line[m.start():])
            out += '\x01%d\x02' % (len(blocks) - 1)
            open_end = True
            break

        blocks.append(line[m.start():end + 2])
        out += '\x01%d\x02' % (len(blocks) - 1)
        pos = end + 2

    return out, blocks, open_end


def unmask(text, blocks):
    return re.sub('\x01(\\d+)\x02', lambda m: blocks[int(m.group(1))], text)


def xhtml(masked):
    def close(m):
        body = m.group(2)

        if body.rstrip().endswith('/'):
            return m.group(0)

        return '<' + m.group(1) + body.rstrip() + ' />'

    return re.sub(r'<(' + VOID + r')\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>', close, masked, flags=re.I)


def tags(masked):
    """Opening and closing tags of complete tags in a masked line: list of (kind, name, start)"""
    found = []

    for m in re.finditer(r'<(/?)([a-zA-Z][\w:.-]*)((?:[^>"\']|"[^"]*(?:"|$)|\'[^\']*(?:\'|$))*)(>|$)', masked):
        closing, name, attrs, end = m.group(1), m.group(2).lower(), m.group(3), m.group(4)

        if closing:
            found.append(('close', name, m.start(), end == '>'))
        elif name in VOID_SET or attrs.rstrip().endswith('/'):
            found.append(('void', name, m.start(), end == '>'))
        else:
            found.append(('open', name, m.start(), end == '>'))

    return found


def control(blocks, masked):
    """'open', 'mid', 'end' when the line is a single PHP control statement, else None"""
    if not re.fullmatch(r'\s*\x010\x02\s*', masked) or len(blocks) != 1:
        return None

    code = re.sub(r'^<\?php\s*|\s*\?>$', '', blocks[0].strip(), flags=re.S)

    if CONTROL_END.match(code) or BRACE_END.match(code):
        return 'end'

    if CONTROL_MID.match(code) or BRACE_MID.match(code):
        return 'mid'

    if CONTROL_OPEN.match(code) or BRACE_OPEN.match(code):
        return 'open'

    return None


def shift(line, delta):
    if not line.strip():
        return ''

    if delta > 0:
        return '\t' * delta + line

    if delta < 0:
        n = 0

        while n < -delta and line[n:n + 1] == '\t':
            n += 1

        return line[n:]

    return line


def lead(line):
    return len(line) - len(line.lstrip('\t'))


def fmt(text, name):
    warnings = []
    text = text.replace('?><!DOCTYPE', '?>\n<!DOCTYPE')
    lines = text.split('\n')
    out = []
    i = 0

    # The file's opening PHP block (header, code) stays as it is
    if lines and lines[0].startswith('<?php'):
        while i < len(lines):
            out.append(lines[i])
            closed = '?>' in lines[i]
            i += 1

            if closed:
                break

    depth = 0
    stack = []
    raw = None          # (closing pattern, delta or None)
    php_delta = None    # inside a multi-line PHP block: its shift
    pending = None      # a tag continued on the next lines: (name, indent)

    while i < len(lines):
        line = lines[i]
        i += 1

        if php_delta is not None:
            out.append(shift(line, php_delta))

            if '?>' in line:
                php_delta = None
                rest, _, _ = mask(line[line.index('?>') + 2:])

                # The rest of a tag a PHP block wrote attributes into
                if pending is not None:
                    m = re.match(r'(?:[^>"\']|"[^"]*"|\'[^\']*\')*>', rest)

                    if m:
                        if pending[0] not in VOID_SET and not m.group(0).rstrip()[:-1].rstrip().endswith('/'):
                            depth += 1

                        pending = None
                        rest = rest[m.end():]

                for tkind, tname, _, complete in tags(rest):
                    if tname != 'html':
                        depth += {'open': 1, 'close': -1}.get(tkind, 0)

            continue

        if raw is not None:
            pattern, delta = raw
            out.append(line if delta is None else shift(line, delta))

            if re.search(pattern, line, re.I):
                raw = None

                # The element's closing tag (a comment has none)
                if pattern != r'-->':
                    depth -= 1

            continue

        stripped = line.strip()

        if not stripped:
            out.append('')
            continue

        masked, blocks, open_end = mask(stripped)

        if pending is not None:
            # Attributes of a tag begun on an earlier line
            pname, pindent = pending
            out.append('\t' * (pindent + 1) + stripped)

            if open_end:
                php_delta = (pindent + 1) - lead(line)

            m = re.match(r'(?:[^>"\']|"[^"]*"|\'[^\']*\')*>', masked)

            if m:
                if not m.group(0).rstrip()[:-1].rstrip().endswith('/') and pname not in VOID_SET:
                    depth += 1
                    if pname in RAW_SHIFT + RAW_KEEP and not re.search(r'</' + pname + r'>', masked, re.I):
                        raw = (r'</' + pname + r'>', None if pname in RAW_KEEP else (pindent - lead(line)))
                pending = None
                rest = masked[m.end():]

                for kind, tname, _, complete in tags(rest):
                    depth += {'open': 1, 'close': -1}.get(kind, 0)

            continue

        kind = control(blocks, masked)

        if kind == 'open':
            indent = depth
            stack.append({'start': depth, 'ends': []})
        elif kind == 'mid':
            if not stack:
                warnings.append('%s:%d: else without if' % (name, i))
                indent = depth
            else:
                stack[-1]['ends'].append(depth)
                indent = depth = stack[-1]['start']
        elif kind == 'end':
            if not stack:
                warnings.append('%s:%d: end without start' % (name, i))
                indent = depth
            else:
                frame = stack.pop()
                frame['ends'].append(depth)
                indent = frame['start']

                if len(set(frame['ends'])) > 1:
                    warnings.append('%s:%d: branches end at depths %s' % (name, i, frame['ends']))

                depth = frame['ends'][0]
        else:
            found = tags(masked)
            leading = 0

            for tkind, tname, start, complete in found:
                if tkind == 'close' and not re.sub(r'\x01\d+\x02', '', masked[:start]).strip():
                    leading += 1
                else:
                    break

            indent = max(0, depth - leading)

            for tkind, tname, start, complete in found:
                if tname == 'html':
                    continue

                if not complete:
                    pending = (tname, indent)
                    break

                if tkind == 'open':
                    depth += 1
                elif tkind == 'close':
                    depth -= 1

            # Raw text opened on this line and not closed on it
            for rname in RAW_SHIFT + RAW_KEEP:
                if re.search(r'<' + rname + r'\b[^>]*>', masked, re.I) and not re.search(r'</' + rname + r'>', masked, re.I):
                    raw = (r'</' + rname + r'>', None if rname in RAW_KEEP else (indent - lead(line)))

            if masked.count('<!--') > masked.count('-->'):
                raw = (r'-->', indent - lead(line))

            masked = xhtml(masked)

        if depth < 0:
            warnings.append('%s:%d: depth below zero' % (name, i))
            depth = 0

        new = '\t' * indent + unmask(masked, blocks)

        # After a line ending in ?> (PHP eats its newline) the indentation is output: it must not appear or vanish
        if kind is None and out and out[-1].rstrip().endswith('?>') and (lead(line) > 0 or line[:1] == ' ') != (indent > 0):
            warnings.append('%s:%d: whitespace after ?> would %s' % (name, i, 'vanish' if indent == 0 else 'appear'))

        out.append(new)

        if open_end:
            php_delta = indent - lead(line)

    if depth != 0 or stack:
        warnings.append('%s: ends at depth %d, %d open control blocks' % (name, depth, len(stack)))

    return '\n'.join(out), warnings


if __name__ == '__main__':
    write = '--write' in sys.argv

    for path in [a for a in sys.argv[1:] if not a.startswith('--')]:
        text = open(path).read()
        new, warnings = fmt(text, path)

        for w in warnings:
            print('WARN', w)

        if new != text:
            print('changed', path)

            if write:
                open(path, 'w').write(new)
