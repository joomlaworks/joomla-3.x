"""Checks that every extension manifest in the repository names files and folders that exist where the installer puts
them, and that companions name installed extensions.

Resolved as Joomla 3's installer adapters do:
- <files>: the extension's folder (components/com_x for a component's site files, administrator/components/com_x for
  <administration><files>, modules/mod_x or administrator/modules/mod_x, plugins/<group>/<element>,
  templates/<name> or administrator/templates/<name>, libraries/[<libraryname>/] for libraries, the site root for the
  files extension (joomla.xml), the language folder for languages).
- <media destination="x">: media/x/<entry> (media/<entry> without a destination).
- <languages>: language/<tag>/<file name> for the site side, administrator/language/<tag>/<file name> for
  <administration>, plugins and administrator modules and templates.
- A component's <scriptfile>, <sql><file> and <schemapath>: its administrator folder.
- <companions>: an extension of that element (and group, or client) with a manifest in the repository.
- Removable core extensions (script.php's $removableCoreExtensions): a manifest each, and nothing installed outside
  their folders that the update's cleanup of uninstalled ones misses (language files beyond the two standard ones,
  media missing from $removableCoreExtensionMedia).
Package manifests (pkg_*.xml) list archives, not files: skipped.

Usage: python3 docs/checks/manifests.py    (from the repository's root; exits with 1 when something is missing)"""
import glob
import os
import re
import sys
import xml.etree.ElementTree as ET


def text(node):
    return (node.text or '').strip() if node is not None else ''


def manifests():
    # A site-only component (Wrapper) keeps its manifest in its site folder
    patterns = ['administrator/components/*/*.xml', 'components/*/*.xml', 'modules/*/*.xml', 'administrator/modules/*/*.xml', 'plugins/*/*/*.xml',
                'templates/*/templateDetails.xml', 'administrator/templates/*/templateDetails.xml',
                'administrator/manifests/libraries/*.xml', 'administrator/manifests/files/*.xml', 'language/*/install.xml',
                'administrator/language/*/install.xml']

    for pattern in patterns:
        for path in sorted(glob.glob(pattern)):
            try:
                root = ET.parse(path).getroot()
            except ET.ParseError as error:
                yield path, None, str(error)
                continue

            if root.tag == 'extension' and root.get('type') != 'package':
                yield path, root, None


def entries(files_node):
    """The paths a <files>, <media> or <languages> node lists, relative to the folder its contents are copied into."""
    for child in files_node:
        value = text(child)

        if child.tag in ('filename', 'file', 'folder') and value and value != '/':
            yield child.tag, value


problems = []
installed = set()
found = []

for path, root, error in manifests():
    if error:
        problems.append('%s: not well-formed XML (%s)' % (path, error))
        continue

    kind = root.get('type')
    admin = path.startswith('administrator/') and kind in ('module', 'template', 'language')
    home = os.path.dirname(path)
    element = os.path.basename(home)
    found.append((path, root, kind, admin, home, element))

    if kind == 'plugin':
        installed.add(('plugin', root.get('group'), element))
    elif kind == 'module':
        installed.add(('module', 'administrator' if admin else 'site', element))
    elif kind == 'component':
        installed.add(('component', None, element))

for path, root, kind, admin, home, element in found:
    missing = []

    def need(target, what):
        target = target.replace('//', '/').rstrip('/')

        if not os.path.exists(target):
            missing.append('%s %s' % (what, target))

    def language_folder(admin_side):
        return 'administrator/language' if admin_side else 'language'

    if kind == 'component':
        site, back = 'components/' + element, 'administrator/components/' + element

        for node in root.findall('files'):
            for tag, value in entries(node):
                need(site + '/' + value, tag)

        administration = root.find('administration')

        if administration is not None:
            for node in administration.findall('files'):
                for tag, value in entries(node):
                    need(back + '/' + value, tag)

            for node in administration.findall('languages'):
                for language in node.findall('language'):
                    need('%s/%s/%s' % (language_folder(True), language.get('tag'), os.path.basename(text(language))), 'language')

        for node in root.findall('languages'):
            for language in node.findall('language'):
                need('%s/%s/%s' % (language_folder(False), language.get('tag'), os.path.basename(text(language))), 'language')

        script = text(root.find('scriptfile'))

        if script:
            need(back + '/' + script, 'scriptfile')

        for sql in root.findall('install/sql/file') + root.findall('uninstall/sql/file'):
            need(back + '/' + text(sql), 'sql')

        for schema in root.findall('update/schemas/schemapath'):
            need(back + '/' + text(schema), 'schemapath')

        for companion in root.findall('companions/*'):
            if companion.tag == 'plugin':
                key = ('plugin', companion.get('group'), companion.get('element'))
            else:
                key = ('module', companion.get('client') or 'site', companion.get('element'))

            if key not in installed:
                missing.append('companion %s %s' % (companion.tag, '/'.join(k for k in key[1:] if k)))
    elif kind == 'library':
        name = text(root.find('libraryname'))

        for node in root.findall('files'):
            for tag, value in entries(node):
                if not os.path.exists('libraries/' + value) and not os.path.exists('libraries/%s/%s' % (name, value)):
                    missing.append('%s libraries/[%s/]%s' % (tag, name, value))
    elif kind == 'file':
        for node in root.findall('fileset/files'):
            target = node.get('target', '')

            for tag, value in entries(node):
                need((target + '/' if target else '') + value, tag)
    else:
        for node in root.findall('files'):
            for tag, value in entries(node):
                need(home + '/' + value, tag)

        if kind != 'language':
            for node in root.findall('languages'):
                for language in node.findall('language'):
                    admin_side = admin or kind == 'plugin'
                    need('%s/%s/%s' % (language_folder(admin_side), language.get('tag'), os.path.basename(text(language))), 'language')

    for node in root.findall('media'):
        destination = node.get('destination', '')

        for tag, value in entries(node):
            need('media/' + (destination + '/' if destination else '') + value, 'media ' + tag)

    for line in missing:
        problems.append('%s: %s' % (path, line))

# Removable core extensions: an update deletes the files of those a site uninstalled (deleteFilesOfRemovedCoreExtensions()
# in administrator/components/com_admin/script.php): their own folders, the two standard language files and the media of
# $removableCoreExtensionMedia. Whatever else their manifests install outside their folders would be left behind.

script = open('administrator/components/com_admin/script.php', encoding='utf-8').read()
block = script[script.index('protected $removableCoreExtensions = array('):]
block = block[:block.index('\n    );')]
media_block = script[script.index('protected $removableCoreExtensionMedia = array('):]
media_block = media_block[:media_block.index('\n    );')]
media_map = {}

for key, value in re.findall(r"'([a-z]+:\d:[^']*:[^']+)'\s*=>\s*(array\([^)]*\)|'[^']+')", media_block):
    media_map[key] = [v.strip('/') for v in re.findall(r"'([^']+)'", value)]

by_key = {}

for path, root, kind, admin, home, element in found:
    if kind == 'plugin':
        by_key['plugin:0:%s:%s' % (root.get('group'), element)] = (path, root, kind, admin)
    elif kind in ('module', 'template'):
        by_key['%s:%d::%s' % (kind, 1 if admin else 0, element)] = (path, root, kind, admin)
    elif kind == 'component':
        by_key['component:0::' + element] = (path, root, kind, admin)

removable = set()

for kind, element, folder, client in re.findall(r"array\('(component|module|plugin|template)', '([^']+)', '([^']*)'(?:, (\d))?\)", block):
    client = int(client or 0) if kind in ('module', 'template') else 0
    removable.add('%s:%d:%s:%s' % (kind, client, folder, element))

for key in sorted(removable):
    if key not in by_key:
        problems.append('removable %s: no manifest in the repository' % key)
        continue

    path, root, kind, admin = by_key[key]
    element = key.split(':')[3]
    prefix = {'component': element, 'module': element, 'template': 'tpl_' + element}.get(kind, 'plg_%s_%s' % (key.split(':')[2], element))
    admin_side = admin or kind == 'plugin'
    deleted = set()

    for side in ('language', 'administrator/language'):
        if kind == 'component' or (side == 'administrator/language') == admin_side:
            deleted |= {'%s/en-GB/en-GB.%s.ini' % (side, prefix), '%s/en-GB/en-GB.%s.sys.ini' % (side, prefix)}

    media = media_map.get(key, [])
    installs = []

    for node in root.findall('languages') + root.findall('administration/languages'):
        side_admin = admin_side or node in root.findall('administration/languages')

        for language in node.findall('language'):
            installs.append('%s/%s/%s' % ('administrator/language' if side_admin else 'language', language.get('tag'), os.path.basename(text(language))))

    for node in root.findall('media'):
        destination = node.get('destination', '')

        for tag, value in entries(node):
            target = 'media/' + (destination + '/' if destination else '') + value

            if not any(target == m or target.startswith(m + '/') for m in media):
                problems.append('removable %s: its media %s isn\'t in $removableCoreExtensionMedia (left behind when uninstalled)' % (key, target))

    for target in installs:
        if target not in deleted:
            problems.append('removable %s: its language file %s isn\'t deleted when uninstalled' % (key, target))

print('%d manifests checked, %d removable core extensions; %d problems' % (len(found), len(removable), len(problems)))

for line in problems:
    print('   ' + line)

sys.exit(1 if problems else 0)
