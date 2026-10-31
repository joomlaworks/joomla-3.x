"""Copies the Bootstrap Icons (https://icons.getbootstrap.com, MIT) core extensions use into their icon folders, one SVG
file each, unchanged; JHtml::_('icons.svg', $name, $label, array('path' => $folder)) writes one into the page, drawn in
the text colour. Their licence is on System Information's Third-party Software list (docs/thirdparty/build.php, which
reads the version from the usage line below). Also writes the few icons stylesheets use as backgrounds, in a fixed colour.

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
    # administrator menu's language item, Isis' and Prothero's status bar (envelope, person: CSS masks)
    'media/system/images/icons': [
        'calendar3', 'chevron-left', 'chevron-right', 'envelope', 'file-earmark-plus', 'info-circle-fill', 'lock-fill', 'pencil-square', 'person',
        'printer', 'rss-fill', 'star', 'star-fill', 'translate', 'x-lg',
        # The PDF viewer's toolbar (media/system/js/pdf-viewer.js, through JHtml behavior.pdf's script options)
        'zoom-in', 'zoom-out', 'arrow-left-right', 'fullscreen', 'fullscreen-exit', 'download',
    ],
    # The Media Manager's file types (administrator/components/com_media/models/list.php), renaming and thumbnail sizes
    'media/com_media/images/icons': [
        'arrow-left', 'check-lg', 'pencil', 'x-lg', 'zoom-in', 'zoom-out', 'folder-fill', 'file-earmark', 'file-earmark-code',
        'file-earmark-excel', 'file-earmark-image', 'file-earmark-music',
        'file-earmark-pdf', 'file-earmark-play', 'file-earmark-ppt', 'file-earmark-richtext', 'file-earmark-spreadsheet',
        'file-earmark-text', 'file-earmark-word', 'file-earmark-zip',
    ],
    # The editors' PDF button (plugins/editors-xtd/pdf)
    'plugins/editors-xtd/pdf/images': ['file-earmark-pdf-fill'],
    # Contacts' markers (components/com_contact/views/contact/view.html.php)
    'media/com_contact/images/icons': ['envelope', 'geo-alt', 'info-circle', 'phone', 'printer', 'telephone'],
}

# Icons in a fixed colour, for stylesheets' backgrounds (a background can't take the text colour)
COLOURED = {
    'media/system/images/icons/notice-info.svg': ('info-circle-fill', '#2a6fb5'),
    'media/system/images/icons/notice-alert.svg': ('dash-circle-fill', '#c9302c'),
    'media/system/images/icons/notice-note.svg': ('exclamation-circle-fill', '#e09000'),
    'media/com_finder/images/file-pdf.svg': ('file-earmark-pdf-fill', '#c62828'),
    # The legacy administrator images (JHtml::_('image', 'admin/<name>.png', ..., true): JHtmlGrid, extensions; 32 PNGs
    # in Isis' images/admin until 3.18), asked for as .png and found as .svg (HTMLHelper::MOVED_IMAGE_TYPES)
    'media/system/images/admin/checked_out.svg': ('lock-fill', '#6c757d'),
    'media/system/images/admin/collapseall.svg': ('dash-square', '#3070a8'),
    'media/system/images/admin/disabled.svg': ('slash-circle', '#555555'),
    'media/system/images/admin/downarrow.svg': ('arrow-down-circle-fill', '#2a6fb5'),
    'media/system/images/admin/downarrow0.svg': ('arrow-down-circle-fill', '#2a6fb5'),
    'media/system/images/admin/downarrow-1.svg': ('caret-down-fill', '#c9302c'),
    'media/system/images/admin/expandall.svg': ('plus-square', '#3070a8'),
    'media/system/images/admin/featured.svg': ('star-fill', '#333333'),
    'media/system/images/admin/filesave.svg': ('floppy-fill', '#555555'),
    'media/system/images/admin/filter_16.svg': ('funnel-fill', '#333333'),
    'media/system/images/admin/icon-16-add.svg': ('plus-circle-fill', '#f0a020'),
    'media/system/images/admin/icon-16-allow.svg': ('check-lg', '#46a546'),
    'media/system/images/admin/icon-16-allowinactive.svg': ('check-lg', '#a6d39b'),
    'media/system/images/admin/icon-16-deny.svg': ('dash-circle-fill', '#c9302c'),
    'media/system/images/admin/icon-16-denyinactive.svg': ('dash-circle-fill', '#e8a3a1'),
    'media/system/images/admin/icon-16-links.svg': ('link-45deg', '#2a6fb5'),
    'media/system/images/admin/icon-16-notice-note.svg': ('exclamation-circle-fill', '#e09000'),
    'media/system/images/admin/icon-16-protected.svg': ('lock-fill', '#46a546'),
    'media/system/images/admin/note_add_16.svg': ('journal-plus', '#e09000'),
    'media/system/images/admin/publish_g.svg': ('check-circle-fill', '#46a546'),
    'media/system/images/admin/publish_r.svg': ('x-circle-fill', '#c9302c'),
    'media/system/images/admin/publish_x.svg': ('stop-circle-fill', '#c9302c'),
    'media/system/images/admin/publish_y.svg': ('exclamation-circle-fill', '#e09000'),
    'media/system/images/admin/sort_asc.svg': ('caret-up-fill', '#888888'),
    'media/system/images/admin/sort_desc.svg': ('caret-down-fill', '#888888'),
    'media/system/images/admin/tick.svg': ('check-circle', '#333333'),
    'media/system/images/admin/trash.svg': ('trash-fill', '#3a87ad'),
    'media/system/images/admin/uparrow.svg': ('arrow-up-circle-fill', '#2a6fb5'),
    'media/system/images/admin/uparrow0.svg': ('arrow-up-circle-fill', '#2a6fb5'),
    'media/system/images/admin/uparrow-1.svg': ('caret-up-fill', '#c9302c'),
    # The tree's folder and document icons (behavior.tree, CMS.tree in global.js; MooTree's sprite until 3.18)
    'media/system/images/tree/_closed.svg': ('folder-fill', '#e0b04e'),
    'media/system/images/tree/_open.svg': ('folder2-open', '#d4a03c'),
    'media/system/images/tree/_doc.svg': ('file-earmark-text', '#8a8a8a'),
    # Searchable lists (Chosen's styles in media/system/css/global.css; its sprite until 3.18)
    'media/system/images/chosen/caret-down.svg': ('caret-down-fill', '#888888'),
    'media/system/images/chosen/caret-up.svg': ('caret-up-fill', '#888888'),
    'media/system/images/chosen/x.svg': ('x-lg', '#888888'),
    'media/system/images/chosen/x-dark.svg': ('x-lg', '#444444'),
    'media/system/images/chosen/search.svg': ('search', '#888888'),
}

# The tree's lines and expand/collapse boxes, 18 pixels square as MooTree's sprite drew them: a vertical line through the
# middle (up: above the box, down: below it), a line to the right, a box with a minus or a plus
TREE_LINE = '#c8c8c8'


def tree_icon(up=False, down=False, right=False, box=None):
    parts = []
    top, bottom = (4 if box else 9), (14 if box else 9)
    if up:
        parts.append('<path fill="%s" d="M8 0h1v%dH8z"/>' % (TREE_LINE, top))
    if down:
        parts.append('<path fill="%s" d="M8 %dh1v%dH8z"/>' % (TREE_LINE, bottom, 18 - bottom))
    if right:
        parts.append('<path fill="%s" d="M%d 8h%dv1H%dz"/>' % (TREE_LINE, 13 if box else 9, 5 if box else 9, 13 if box else 9))
    if box:
        parts.append('<rect x="4" y="4" width="9" height="9" rx="1.5" fill="#9a9a9a"/>')
        parts.append('<path fill="#ffffff" d="M6 8h5v1H6z%s"/>' % ('M8 6h1v5H8z' if box == 'plus' else ''))
    return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">%s</svg>' % ''.join(parts)


TREE = {
    'I': tree_icon(up=True, down=True), 'L': tree_icon(up=True, right=True), 'T': tree_icon(up=True, down=True, right=True),
    'Lminus': tree_icon(up=True, right=True, box='minus'), 'Lplus': tree_icon(up=True, right=True, box='plus'),
    'Rminus': tree_icon(right=True, box='minus'), 'Rplus': tree_icon(right=True, box='plus'),
    'Tminus': tree_icon(up=True, down=True, right=True, box='minus'), 'Tplus': tree_icon(up=True, down=True, right=True, box='plus'),
    'minus': tree_icon(box='minus'), 'plus': tree_icon(box='plus'),
}

# Drawn here, not Bootstrap Icons: the legacy set's empty image and menu divider, the shared empty image, the tree's lines
DRAWN = {
    'media/system/images/blank.svg': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"/>',
    'media/system/images/admin/blank.svg': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"/>',
    'media/system/images/admin/menu_divider.svg': '<svg xmlns="http://www.w3.org/2000/svg" width="2" height="28" viewBox="0 0 2 28"><path fill="#cccccc" d="M0 0h1v28H0z"/><path fill="#ffffff" d="M1 0h1v28H1z"/></svg>',
}

package, root = sys.argv[1], sys.argv[2]
written = []
config = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'svgo.config.mjs')

for folder, names in FOLDERS.items():
    os.makedirs(root + '/' + folder, exist_ok=True)

    for name in names:
        shutil.copyfile('%s/icons/%s.svg' % (package, name), '%s/%s/%s.svg' % (root, folder, name))
        written.append('%s/%s/%s.svg' % (root, folder, name))

    print(folder, len(names), 'icons')

for target, (name, colour) in COLOURED.items():
    os.makedirs(os.path.dirname(root + '/' + target), exist_ok=True)
    svg = open('%s/icons/%s.svg' % (package, name)).read()
    svg = re.sub(r'\sclass="[^"]*"', '', svg.replace('fill="currentColor"', 'fill="%s"' % colour))
    open(root + '/' + target, 'w').write(svg)
    written.append(root + '/' + target)
    print(target, name)

DRAWN.update({'media/system/images/tree/%s.svg' % name: svg for name, svg in TREE.items()})

for target, svg in DRAWN.items():
    os.makedirs(os.path.dirname(root + '/' + target), exist_ok=True)
    open(root + '/' + target, 'w').write(svg + '\n')
    written.append(root + '/' + target)

subprocess.run(['svgo', '--quiet', '--config', config] + written, check=True)
print(len(written), 'files optimised')
