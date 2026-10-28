"""Builds the SVG icon sprites of core extensions from Bootstrap Icons (https://icons.getbootstrap.com, MIT): one <symbol>
per icon, named as in Bootstrap Icons, without colours (the page draws them in the text colour: <svg fill="currentColor">
<use href="icons.svg#name"></use></svg>).

Usage: build.py <bootstrap-icons package folder> <site root>    (npm bootstrap-icons 1.13.2)
Rebuild rather than edit the sprites; add an icon to SPRITES and run this again."""
import re
import sys

SPRITES = {
    # The Media Manager's file types (administrator/components/com_media/models/list.php)
    'media/com_media/images/icons.svg': [
        'file-earmark', 'file-earmark-code', 'file-earmark-excel', 'file-earmark-image', 'file-earmark-music',
        'file-earmark-pdf', 'file-earmark-play', 'file-earmark-ppt', 'file-earmark-richtext', 'file-earmark-spreadsheet',
        'file-earmark-text', 'file-earmark-word', 'file-earmark-zip',
    ],
    # Contacts' markers (components/com_contact/views/contact/view.html.php)
    'media/com_contact/images/icons.svg': ['envelope', 'geo-alt', 'info-circle', 'phone', 'printer', 'telephone'],
}

package, root = sys.argv[1], sys.argv[2]
notice = open(package + '/LICENSE').read().strip()
notice = notice.replace('--', '- -')

for target, names in SPRITES.items():
    symbols = []

    for name in sorted(names):
        svg = open('%s/icons/%s.svg' % (package, name)).read()
        view_box = re.search(r'viewBox="([^"]+)"', svg).group(1)
        body = re.sub(r'\s*\n\s*', '', re.search(r'<svg[^>]*>(.*)</svg>', svg, re.S).group(1))
        symbols.append('<symbol id="%s" viewBox="%s">%s</symbol>' % (name, view_box, body))

    out = ('<svg xmlns="http://www.w3.org/2000/svg">\n<!-- Bootstrap Icons 1.13.2, https://icons.getbootstrap.com\n'
           + notice + '\n-->\n' + '\n'.join(symbols) + '\n</svg>\n')
    open(root + '/' + target, 'w').write(out)
    print(target, len(names), 'icons', len(out), 'bytes')
