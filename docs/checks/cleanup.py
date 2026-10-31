"""Checks the update's cleanup list (deleteUnexistingFiles() in administrator/components/com_admin/script.php) against the
repository's history.

1. Shipped files the cleanup would delete: entries of $files equal to a file the package ships, or entries of $folders
   holding one. Updates extract the package first and clean up after, so these files would vanish on every update.
2. Leftovers: files the package had in any commit since the Joomla 3.10.20 import (dc6dc311, which is also stock
   3.10.20) that it no longer ships and that no entry covers. Every push to main is a release (the rolling build),
   so development builds count too.
3. Entries under images/, which holds the site's own content: listed for review. Files gone from images/ (old sample
   images, stock Joomla's banners and headers) are never cleaned up, as content may use them: counted, not listed.

The package is every tracked file minus docs/, .github/, *.md and .gitignore (.github/workflows/rolling-release.yml).
installation/ is left out of 2 (Joomla Update deletes the folder). Files deleted only under a condition (Beez 3 while it's
installed, Weblinks' while Weblinks isn't) count as covered.

Usage: python3 docs/checks/cleanup.py    (from the repository's root; exits with 1 when 1 or 2 finds something)"""
import re
import subprocess
import sys

SCRIPT = 'administrator/components/com_admin/script.php'
IMPORT = 'dc6dc311'


def git(*args):
    return subprocess.run(['git'] + list(args), capture_output=True, text=True, check=True).stdout


def packaged(path):
    return not (path.startswith(('docs/', '.github/')) or path.endswith('.md') or path == '.gitignore')


def entries(source, name):
    start = source.index('$%s = array(' % name)
    end = source.index('\n        );', start)
    body = re.sub(r'//[^\n]*', '', source[start:end])

    return [m.strip('/') for m in re.findall(r"'([^']+)'", body)]


source = open(SCRIPT, encoding='utf-8').read()
method = source[source.index('function deleteUnexistingFiles'):]
method = method[:method.index('\n    }\n') + 7]
files = set(entries(method, 'files'))
folders = set(entries(method, 'folders'))
conditional = {m.strip('/') for m in re.findall(r"\$files\[\] = '([^']+)'", method)}
files |= conditional

shipped = {p for p in git('ls-files', '-c', '-o', '--exclude-standard').split('\n') if p and packaged(p)}
history = set(git('ls-tree', '-r', '--name-only', IMPORT).split('\n'))
history |= set(git('log', '--diff-filter=A', '--name-only', '--format=', IMPORT + '..HEAD').split('\n'))
history = {p for p in history if p and packaged(p) and not p.startswith('installation/')}


def covered(path):
    return path in files or any(path.startswith(folder + '/') for folder in folders)


problems = 0

wrong = sorted(f for f in files if f in shipped)
wrong += sorted('%s/ (holds %s)' % (d, next(p for p in sorted(shipped) if p.startswith(d + '/')))
                for d in folders if any(p.startswith(d + '/') for p in shipped))
print('1. Shipped files the cleanup would delete: %d' % len(wrong))

for line in wrong:
    print('   ' + line)

gone = sorted(p for p in history - shipped if not covered(p))
kept = [p for p in gone if p.startswith('images/')]
leftovers = [p for p in gone if not p.startswith('images/')]
print('2. Leftovers (gone from the package, not on the cleanup list): %d' % len(leftovers))

for line in leftovers:
    print('   ' + line)

print('   (and %d files of images/, kept on purpose)' % len(kept))

images = sorted(e for e in files | folders if e.startswith('images/'))
print('3. Entries under images/ (site content; review): %d' % len(images))

for line in images:
    print('   ' + line)

sys.exit(1 if wrong or leftovers else 0)
