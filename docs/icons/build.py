"""Copies the Bootstrap Icons (https://icons.getbootstrap.com, MIT) core extensions use into their icon folders, one SVG
file each, unchanged, with the licence beside them; JHtml::_('icons.svg', $name, $label, array('path' => $folder)) writes
one into the page, drawn in the text colour. Also writes the few icons stylesheets use as backgrounds, in a fixed colour.

Usage: build.py <bootstrap-icons package folder> <site root>    (npm bootstrap-icons 1.13.2; needs svgo)
Add an icon to FOLDERS and run this again rather than copying files by hand. Every file written goes through svgo
(docs/icons/svgo.config.mjs)."""
import os
import re
import shutil
import subprocess
import sys

FOLDERS = {
    # Shared icons (JHtml::_('icons.svg', ...)): breadcrumbs, content icons, vote, feeds, tooltips, mail, date fields, the
    # administrator menu's language item
    'media/system/images/icons': [
        'calendar3', 'chevron-left', 'chevron-right', 'envelope', 'file-earmark-plus', 'info-circle-fill', 'lock-fill', 'pencil-square',
        'printer', 'rss-fill', 'star', 'star-fill', 'translate', 'x-lg',
    ],
    # The Media Manager's file types (administrator/components/com_media/models/list.php)
    'media/com_media/images/icons': [
        'file-earmark', 'file-earmark-code', 'file-earmark-excel', 'file-earmark-image', 'file-earmark-music',
        'file-earmark-pdf', 'file-earmark-play', 'file-earmark-ppt', 'file-earmark-richtext', 'file-earmark-spreadsheet',
        'file-earmark-text', 'file-earmark-word', 'file-earmark-zip',
    ],
    # Contacts' markers (components/com_contact/views/contact/view.html.php)
    'media/com_contact/images/icons': ['envelope', 'geo-alt', 'info-circle', 'phone', 'printer', 'telephone'],
}

# Icons in a fixed colour, for stylesheets' backgrounds (a background can't take the text colour)
COLOURED = {
    'media/system/images/icons/notice-info.svg': ('info-circle-fill', '#2a6fb5'),
    'media/system/images/icons/notice-alert.svg': ('dash-circle-fill', '#c9302c'),
    'media/system/images/icons/notice-note.svg': ('exclamation-circle-fill', '#e09000'),
    'media/com_finder/images/file-pdf.svg': ('file-earmark-pdf-fill', '#c62828'),
}

package, root = sys.argv[1], sys.argv[2]
written = []
config = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'svgo.config.mjs')
licence = 'Bootstrap Icons 1.13.2, https://icons.getbootstrap.com\n\n' + open(package + '/LICENSE').read()

for folder, names in FOLDERS.items():
    os.makedirs(root + '/' + folder, exist_ok=True)

    for name in names:
        shutil.copyfile('%s/icons/%s.svg' % (package, name), '%s/%s/%s.svg' % (root, folder, name))
        written.append('%s/%s/%s.svg' % (root, folder, name))

    open(root + '/' + folder + '/license.txt', 'w').write(licence)
    print(folder, len(names), 'icons')

for target, (name, colour) in COLOURED.items():
    svg = open('%s/icons/%s.svg' % (package, name)).read()
    svg = re.sub(r'\sclass="[^"]*"', '', svg.replace('fill="currentColor"', 'fill="%s"' % colour))
    open(root + '/' + target, 'w').write(svg)
    written.append(root + '/' + target)
    print(target, name)

if os.path.exists(root + '/media/com_finder/images'):
    open(root + '/media/com_finder/images/license.txt', 'w').write(licence)

subprocess.run(['svgo', '--quiet', '--config', config] + written, check=True)
print(len(written), 'files optimised')
