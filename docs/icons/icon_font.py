"""Moves stylesheets from IcoMoon (Joomla 3's icon font) to Bootstrap Icons' font (media/system/fonts/bootstrap-icons.woff2
and .woff, npm bootstrap-icons 1.13.2, MIT): the @font-face, font-family IcoMoon, and the IcoMoon characters of the
.icon-* rules and of rules drawing with the font (content:"\\e011", content:'G') become Bootstrap Icons' characters through
MAP below (IcoMoon's character, as icomoon.css names it, => the Bootstrap Icons name). .icon-joomla, which Bootstrap Icons
lacks, is the Joomla logo's mark as a mask in the text's colour (media/system/images/logo/joomla-mark.svg). Other content
values are left alone. A character missing from MAP stops the run.

Usage: icon_font.py <bootstrap-icons package folder> [--write] files...    (lists what it would change; --write writes)
Run it on icomoon.css (and .less), and on stylesheets carrying their own copy of the icon rules (Isis, Prothero, Protostar)."""
import json
import os
import re
import sys

MAP = {
    0xe200: 'joomla',
    0xe005: 'chevron-up', 0xe006: 'chevron-right', 0xe007: 'chevron-down', 0xe008: 'chevron-left',
    0xe003: 'chevron-bar-left', 0xe004: 'chevron-bar-right',
    0xe009: 'arrow-up', 0xe00a: 'arrow-right', 0xe00b: 'arrow-down', 0xe00c: 'arrow-left',
    0xe00f: 'caret-up-fill', 0xe010: 'caret-right-fill', 0xe011: 'caret-down-fill', 0xe012: 'caret-left-fill',
    0xe00e: 'list', 0xe201: 'arrow-up-circle', 0xe202: 'arrow-right-circle', 0xe203: 'arrow-down-circle', 0xe204: 'arrow-left-circle',
    0x27: 'arrow-clockwise', 0x28: 'arrow-counterclockwise', 0xe205: 'forward-fill', 0xe206: 'reply-fill', 0x6c: 'arrow-repeat',
    0xe207: 'arrow-return-left', 0x7a: 'arrows-move', 0x66: 'arrows-angle-expand', 0x67: 'arrows-angle-contract',
    0x68: 'arrows-fullscreen', 0x69: 'fullscreen-exit',
    0xe208: 'play-fill', 0xe209: 'pause-fill', 0xe210: 'stop-fill', 0x7c: 'skip-backward-fill', 0x7b: 'skip-forward-fill',
    0x7d: 'skip-start-fill', 0xe000: 'skip-end-fill', 0xe00d: 'play-circle', 0xe211: 'pause-circle', 0xe212: 'stop-circle',
    0xe213: 'skip-backward-circle', 0xe214: 'skip-forward-circle', 0xe001: 'repeat', 0xe002: 'shuffle',
    0x53: 'search', 0x64: 'zoom-in', 0x65: 'zoom-out', 0x2b: 'pencil-square', 0x2c: 'pencil', 0x3b: 'brush',
    0x5d: 'plus-circle', 0x5e: 'dash-lg', 0x49: 'x-lg', 0x47: 'check-lg', 0x2a: 'plus-lg', 0xe215: 'plus-circle',
    0x4b: 'dash-circle', 0xe216: 'slash-circle', 0x4a: 'x-circle-fill', 0xe217: 'x-circle', 0xe218: 'check2',
    0xe219: 'check-circle', 0xe220: 'info-lg', 0xe221: 'info-circle', 0x45: 'question-circle-fill', 0xe222: 'question-circle',
    0xe223: 'exclamation-lg', 0xe224: 'exclamation-circle', 0x48: 'exclamation-triangle', 0xe225: 'exclamation-triangle-fill',
    0x3d: 'square', 0x3e: 'check-square', 0x3f: 'dash-square', 0xe226: 'square-fill', 0xe227: 'circle', 0xe228: 'record-circle',
    0xe229: 'circle-fill', 0xe230: 'clipboard', 0x58: 'grid', 0x59: 'grid-3x3-gap', 0x5a: 'list', 0x31: 'list-ul',
    0xe231: 'list-ol', 0xe232: 'three-dots-vertical', 0x2d: 'folder2-open', 0x2e: 'folder', 0xe234: 'folder-plus',
    0xe235: 'folder-minus', 0xe236: 'folder-fill', 0xe237: 'folder-plus', 0xe238: 'folder-x', 0xe016: 'file-earmark',
    0xe239: 'file-earmark-text', 0x29: 'file-earmark-plus', 0xe017: 'file-earmark-minus', 0xe240: 'file-earmark-check',
    0xe241: 'file-earmark-x', 0xe018: 'copy', 0xe242: 'stack', 0xe243: 'diagram-3', 0xe244: 'diagram-2',
    0xe246: 'text-left', 0xe247: 'text-center', 0xe248: 'text-right', 0xe249: 'justify', 0xe01c: 'display', 0xe01d: 'tablet',
    0xe01e: 'phone', 0x51: 'box-arrow-in-down', 0x52: 'box-arrow-up', 0xe021: 'download', 0xe022: 'upload', 0x21: 'house-fill',
    0xe250: 'house', 0xe024: 'box-arrow-up-right', 0xe251: 'arrow-up-right-square', 0xe252: 'link-45deg', 0x2f: 'image',
    0x30: 'images', 0xe014: 'palette', 0x55: 'camera-fill', 0xe015: 'camera-video-fill', 0x56: 'youtube', 0x57: 'music-note-beamed',
    0x22: 'person-fill', 0xe01f: 'people-fill', 0x6d: 'person-vcard', 0x70: 'person-rolodex', 0x26: 'box-arrow-up-right',
    0xe257: 'box-arrow-in-right', 0xe258: 'box-arrow-right', 0x24: 'chat-fill', 0x25: 'chat-dots-fill', 0x60: 'quote',
    0x61: 'quote', 0xe259: 'chat-quote-fill', 0xe260: 'telephone-fill', 0xe261: 'telephone', 0x4d: 'envelope-fill',
    0x4e: 'envelope-open', 0x4f: 'inbox', 0x50: 'archive-fill', 0xe020: 'briefcase-fill', 0xe262: 'tag-fill', 0xe263: 'tag',
    0xe264: 'tags-fill', 0xe265: 'tags', 0x38: 'gear-fill', 0x37: 'gear-wide-connected', 0x36: 'tools', 0x3a: 'wrench',
    0x39: 'sliders', 0x78: 'speedometer2', 0xe266: 'toggle-on', 0x54: 'funnel-fill', 0x4c: 'trash-fill', 0x23: 'lock-fill',
    0xe267: 'unlock-fill', 0x5f: 'key-fill', 0x46: 'life-preserver', 0x62: 'database-fill', 0xe268: 'scissors',
    0x6a: 'heart-pulse-fill', 0x6b: 'magic', 0x3c: 'eye-fill', 0xe269: 'eye-slash-fill', 0x6e: 'clock', 0x6f: 'compass',
    0xe01b: 'wifi', 0xe271: 'book', 0x79: 'lightning-fill', 0xe013: 'printer-fill', 0x71: 'rss-fill', 0x43: 'calendar3',
    0x44: 'calendar-event', 0xe273: 'calendar-date', 0x77: 'pie-chart-fill', 0x76: 'bar-chart-fill', 0x75: 'graph-up',
    0x32: 'plug-fill', 0x33: 'box', 0x34: 'puzzle-fill', 0x72: 'paperclip', 0x74: 'lamp-fill', 0x73: 'pin-angle-fill',
    0x63: 'geo-alt-fill', 0xe274: 'shield-fill', 0x35: 'flag-fill', 0xe275: 'flag', 0xe023: 'bookmark-fill', 0xe276: 'bookmark',
    0xe277: 'heart-fill', 0xe278: 'heart', 0x5b: 'hand-thumbs-up-fill', 0x5c: 'hand-thumbs-down-fill', 0x40: 'star',
    0x41: 'star-half', 0x42: 'star-fill', 0xe279: 'emoji-smile', 0xe280: 'emoji-smile-fill', 0xe281: 'emoji-frown',
    0xe282: 'emoji-frown-fill', 0xe283: 'emoji-neutral', 0xe284: 'emoji-neutral-fill', 0xe019: 'cart-fill', 0xe01a: 'basket-fill',
    0xe286: 'credit-card-fill', 0xe287: 'credit-card',
}

RULE = re.compile(r'([^{}]*)\{([^{}]*)\}')
CONTENT = re.compile(r'content:(\s*)(["\'])((?:\\.|(?!\2).)*)\2')
FAMILY = re.compile(r'font-family:(\s*)(["\']?)IcoMoon\2', re.I)


def character(value):
    """IcoMoon's character of a content value ("\\e011", "\\0049", "G"), or None for other values"""
    escaped = re.fullmatch(r'\\([0-9a-fA-F]{1,6})\s?', value)
    if escaped:
        return int(escaped.group(1), 16)
    return ord(value) if len(value) == 1 else None


def convert(text, codepoints, fonts, images):
    missing = set()

    def rule(match):
        selector, body = match.group(1), match.group(2)
        multiline = '\n' in body
        if '@font-face' in selector and re.search(r'IcoMoon', body):
            declarations = ["font-family:\"bootstrap-icons\"", "src:url(\"%sbootstrap-icons.woff2\") format(\"woff2\"),url(\"%sbootstrap-icons.woff\") format(\"woff\")" % (fonts, fonts),
                            'font-weight:normal', 'font-style:normal', 'font-display:block']
            if multiline:
                indent = re.search(r'\n(\s*)\S', body).group(1)
                return selector + '{\n' + ''.join('%s%s;\n' % (indent, d.replace(':', ': ', 1)) for d in declarations) + '}'
            return selector + '{' + ''.join(d + ';' for d in declarations) + '}'
        icon_rule = re.search(r'\.icon-[a-z0-9-]+:before', selector)
        uses_font = FAMILY.search(body)
        names = re.findall(r'\.icon-([a-z0-9-]+):before', selector)
        if names == ['joomla']:
            mask = 'url("%slogo/joomla-mark.svg") center / contain no-repeat' % images
            declarations = ['content:""', 'display:inline-block', 'width:1em', 'height:1em', 'max-width:100%', 'max-height:100%', 'vertical-align:top', 'background-color:currentColor', 'mask:' + mask]
            if multiline:
                indent = re.search(r'\n(\s*)\S', body).group(1)
                return selector + '{\n' + ''.join('%s%s;\n' % (indent, d.replace(':', ': ', 1)) for d in declarations) + '}'
            return selector + '{' + ''.join(d + ';' for d in declarations) + '}'
        if not (icon_rule or uses_font):
            return match.group(0)

        def content(c):
            code = character(c.group(3))
            # Not a character, or Bootstrap Icons' already (its private use area starts at U+F100): the file was converted
            if code is None or code >= 0xf100:
                return c.group(0)
            if code not in MAP:
                missing.add(hex(code))
                return c.group(0)
            return 'content:%s%s\\%x%s' % (c.group(1), c.group(2), codepoints[MAP[code]], c.group(2))

        body = CONTENT.sub(content, body)
        body = FAMILY.sub(lambda f: 'font-family:%s%sbootstrap-icons%s' % (f.group(1), '"' if not f.group(2) else f.group(2), '"' if not f.group(2) else f.group(2)), body)
        return selector + '{' + body + '}'

    return RULE.sub(rule, text), missing


if __name__ == '__main__':
    package = sys.argv[1]
    write = '--write' in sys.argv
    codepoints = json.load(open(os.path.join(package, 'font', 'bootstrap-icons.json')))
    unknown = [name for name in MAP.values() if name != 'joomla' and name not in codepoints]
    if unknown:
        sys.exit('Not in Bootstrap Icons: ' + ', '.join(unknown))

    for path in [a for a in sys.argv[2:] if not a.startswith('--')]:
        source = open(path, encoding='utf-8').read()
        # The fonts and images of media/system, from the stylesheet's folder
        relative = os.path.relpath('media/system', os.path.dirname(path)).replace(os.sep, '/') + '/'
        result, missing = convert(source, codepoints, relative + 'fonts/', relative + 'images/')
        if missing:
            sys.exit('%s: IcoMoon characters without a match: %s' % (path, ', '.join(sorted(missing))))
        if result != source:
            if write:
                open(path, 'w', encoding='utf-8').write(result)
            print(('converted ' if write else 'would convert ') + path)
