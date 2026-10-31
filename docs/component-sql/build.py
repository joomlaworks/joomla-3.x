# build.py: writes the install and uninstall SQL of the removable core components which own tables, for each database, from
# installation/sql/<dialect>/joomla.sql (the tables' definitions there are the source). Run it after changing one of these tables:
#     python3 docs/component-sql/build.py
import json, re, subprocess, sys, os
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(os.path.dirname(HERE))
FINDER = ['finder_filters', 'finder_links'] + ['finder_links_terms%x' % i for i in range(16)] + ['finder_taxonomy', 'finder_taxonomy_map', 'finder_terms', 'finder_terms_common', 'finder_tokens', 'finder_tokens_aggregate', 'finder_types']
# component => (its own tables, databases to write files for, file name suffix). Banners, Contacts, News Feeds and Smart Search
# have had MySQL and PostgreSQL files since 3.16 (kept as they are); only their SQL Server files are written here.
COMPONENTS = {
    'com_content':        (['content', 'content_frontpage', 'content_rating'], ('mysql', 'postgresql', 'sqlsrv'), '.utf8'),
    'com_messages':       (['messages', 'messages_cfg'], ('mysql', 'postgresql', 'sqlsrv'), '.utf8'),
    'com_privacy':        (['privacy_requests', 'privacy_consents'], ('mysql', 'postgresql', 'sqlsrv'), '.utf8'),
    'com_redirect':       (['redirect_links', 'redirect_errors'], ('mysql', 'postgresql', 'sqlsrv'), '.utf8'),
    'com_tags':           (['tags', 'contentitem_tag_map'], ('mysql', 'postgresql', 'sqlsrv'), '.utf8'),
    'com_contenthistory': (['ucm_history'], ('mysql', 'postgresql', 'sqlsrv'), '.utf8'),
    'com_fields':         (['fields', 'fields_categories', 'fields_groups', 'fields_values'], ('mysql', 'postgresql', 'sqlsrv'), '.utf8'),
    'com_banners':        (['banners', 'banner_clients', 'banner_tracks'], ('sqlsrv',), '.utf8'),
    'com_contact':        (['contact_details'], ('sqlsrv',), '.utf8'),
    'com_newsfeeds':      (['newsfeeds'], ('sqlsrv',), '.utf8'),
    'com_finder':         (FINDER, ('sqlsrv',), ''),
}
SOURCE = {'mysql': 'mysql', 'postgresql': 'postgresql', 'sqlsrv': 'sqlazure'}
TARGET = re.compile(r'^(?:CREATE TABLE(?: IF NOT EXISTS)?|CREATE (?:UNIQUE )?(?:NONCLUSTERED |CLUSTERED )?INDEX \S+ ON|COMMENT ON (?:TABLE|COLUMN)|SET IDENTITY_INSERT|INSERT INTO|SELECT setval\()\s*[`"\']?#__(\w+?)[`"\'.]', re.I)
RERAISE = 'BEGIN CATCH IF ERROR_NUMBER() NOT IN (%s) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;'

def statements(dialect):
    out = subprocess.run(['php', HERE + '/split.php', ROOT, ROOT + '/installation/sql/%s/joomla.sql' % SOURCE[dialect]], capture_output=True, text=True, check=True).stdout
    result = []
    for s in json.loads(out):
        s = re.sub(r'^(\s*--[^\n]*\n|\s*\n)+', '', s).strip()
        m = TARGET.match(s)
        if m:
            result.append((m.group(1), s))
            if s.upper().startswith('CREATE TABLE'):
                ALL_TABLES.add(m.group(1))
    return result

ALL_TABLES = set()

def table_of(name, tables):
    # setval sequence names are <table>_<column>_seq: the longest table of the whole schema they start with
    for t in sorted(ALL_TABLES | set(tables), key=len, reverse=True):
        if name == t or name.startswith(t + '_'):
            return t if t in tables else None

def install(dialect, tables, stmts):
    q = '`' if dialect == 'mysql' else '"'
    out = []
    for name, s in stmts:
        t = table_of(name, tables)
        if t is None or (name != t and not s.startswith('SELECT setval')) :
            continue
        if dialect == 'mysql':
            s = re.sub(r'^INSERT INTO', 'INSERT IGNORE INTO', s)
        elif dialect == 'postgresql':
            s = re.sub(r'^CREATE TABLE "', 'CREATE TABLE IF NOT EXISTS "', s)
            m = re.match(r'^CREATE (?:UNIQUE )?INDEX ("[^"]+")', s)
            if m:
                out.append('DROP INDEX IF EXISTS %s;' % m.group(1))
            m = re.match(r'^INSERT INTO ("#__\w+") (\([^)]*\)) VALUES\s*\((.*)\);$', s, re.S)
            if m:
                first_col = m.group(2)[1:].split(',')[0].strip()
                first_val = m.group(3).split(',')[0].strip()
                s = 'INSERT INTO %s %s\nSELECT %s\nWHERE NOT EXISTS (SELECT 1 FROM %s WHERE %s = %s);' % (m.group(1), m.group(2), m.group(3), m.group(1), first_col, first_val)
            m = re.match(r"^SELECT setval\('(#__\w+)', (\d+), false\);$", s)
            if m:
                col = m.group(1)[len('#__' + t) + 1:-len('_seq')]
                s = "SELECT setval('%s', GREATEST(COALESCE(MAX(\"%s\"), 0) + 1, %s), false) FROM \"#__%s\";" % (m.group(1), col, m.group(2), t)
        else:
            if re.match(r'^CREATE TABLE', s):
                s = 'BEGIN TRY\n%s\nEND TRY\n%s' % (s.rstrip(';'), RERAISE % '2714')
            elif re.match(r'^CREATE .*INDEX', s):
                s = 'BEGIN TRY\n%s\nEND TRY\n%s' % (s.rstrip(';'), RERAISE % '1913')
            elif s.startswith('INSERT INTO'):
                s = 'IF NOT EXISTS (SELECT 1 FROM "#__%s")\nBEGIN\n%s\nEND;' % (t, s.rstrip(';'))
        out.append(s)
    return out

def uninstall(dialect, tables):
    if dialect == 'mysql':
        return ['DROP TABLE IF EXISTS `#__%s`;' % t for t in tables]
    if dialect == 'postgresql':
        return ['DROP TABLE IF EXISTS "#__%s";' % t for t in tables]
    return ['BEGIN TRY\nDROP TABLE "#__%s"\nEND TRY\n%s' % (t, RERAISE % '3701') for t in tables]

HEAD = {
    'install': '-- %s: its tables, created only where they don\'t exist yet (a restore may find them kept)\n\n',
    'uninstall': '-- %s: its own tables, dropped when it\'s uninstalled\n\n',
}
cache = {}
for comp, (tables, dialects, suffix) in COMPONENTS.items():
    folder = ROOT + '/administrator/components/%s/sql' % comp
    os.makedirs(folder, exist_ok=True)
    for d in dialects:
        if d not in cache:
            cache[d] = statements(d)
        ins = install(d, tables, cache[d])
        created = {table_of(n, tables) for n, s in cache[d] if table_of(n, tables) == n and re.match(r'^CREATE TABLE', s)}
        missing = set(tables) - created
        assert not missing, (comp, d, missing)
        for kind, body in (('install', ins), ('uninstall', uninstall(d, tables))):
            with open('%s/%s.%s%s.sql' % (folder, kind, d, suffix), 'w') as f:
                f.write(HEAD[kind] % comp + '\n\n'.join(body) + '\n')
        print(comp, d, len(ins), 'install statements')
