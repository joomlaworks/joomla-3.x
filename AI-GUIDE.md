AI Assistants & the Command Line
================================

Joomla 3.x UTD comes with a command line interface, `cli/joomla.php`, and a built-in MCP (Model Context Protocol) server. Together they let you, your scripts and AI assistants such as Claude, ChatGPT/Codex, Gemini or Copilot check, maintain and build a site without opening the administrator.

This guide covers both: how to connect an AI assistant to your site, what it can and can't do, and how to use the command line yourself for pretty much anything.

---

## CONTENTS
- [How it fits together](#how-it-fits-together)
- [Before you start](#before-you-start)
- [Connect an AI assistant (MCP)](#connect-an-ai-assistant-mcp)
  - [Claude Code](#claude-code)
  - [Claude Desktop](#claude-desktop)
  - [Devin Desktop (formerly Windsurf)](#devin-desktop-formerly-windsurf)
  - [Cursor, VS Code, Gemini CLI, Codex CLI](#cursor-vs-code-gemini-cli-codex-cli)
  - [Any other MCP client](#any-other-mcp-client)
- [Decide what the assistant may do](#decide-what-the-assistant-may-do)
- [Working with an assistant](#working-with-an-assistant)
- [Safety and traceability](#safety-and-traceability)
- [Using the command line yourself](#using-the-command-line-yourself)
  - [The basics](#the-basics)
  - [Recipes](#recipes)
  - [Scripting with JSON](#scripting-with-json)
  - [Scheduled tasks (cron)](#scheduled-tasks-cron)
- [Troubleshooting](#troubleshooting)
- [Command reference](#command-reference)

---

## HOW IT FITS TOGETHER

- **The command line** (`php cli/joomla.php <command>`) has 100 commands: the command names of Joomla 4 and later (`core:update`, `config:set`, `user:add`, ...) plus many of its own for content, templates, databases, health checks and logs. Every command can answer in JSON (`--format=json`), and every command which changes something can show what it would change first (`--dry-run`).
- **The MCP server** (`php cli/joomla.php mcp:serve`) offers those commands to an AI assistant as tools (97 of them: all but `help`, `list` and `mcp:serve` itself), each described with its options. The assistant starts the server itself and talks to it over standard input and output, so there's no port to open and nothing to expose to the web.
- **Changes go through Joomla**, not around it: content is saved by the administrator's own models, with their validation and permission checks, and every change is recorded in the User Actions Log.

The MCP server is **read-only by default**: an assistant can look at everything and plan changes (as dry runs), but can't change anything until you allow it.


## BEFORE YOU START

- **Shell access to the server**, or a copy of the site on your own computer. The assistant runs `php` on the machine where the site's files are, directly or over SSH. Hosting without SSH can't run it.
- **The same PHP as the site**, from the command line: `php -v` should show the version the site uses (or call that PHP by its full path, e.g. `/usr/bin/php8.4`).
- **The same system user as the web server**, or one with the same file permissions, so files the commands write (cache, templates, backups, extension installs) stay readable and writable by the site. On many servers that's `www-data`: `sudo -u www-data php cli/joomla.php site:health`.
- **Check it works:**
  ```
  cd /path/to/site
  php cli/joomla.php site:health
  ```

Commands which build links (e.g. in emails) don't know the site's address from the command line: give it with `--live-site=https://www.example.com`, or set `$live_site` in `configuration.php`.


## CONNECT AN AI ASSISTANT (MCP)

Every client needs the same thing: start `php /path/to/site/cli/joomla.php mcp:serve` (add the options from [Decide what the assistant may do](#decide-what-the-assistant-may-do) at the end). Use absolute paths, since clients don't start servers in your site's folder.

### Claude Code

A site on the same machine:
```
claude mcp add joomla -- php /path/to/site/cli/joomla.php mcp:serve
```

A site on another server, over SSH (set up key-based login first, so no password prompt is needed):
```
claude mcp add joomla -- ssh user@example.com php /path/to/site/cli/joomla.php mcp:serve
```

To let it make changes, add the options at the end, e.g. `... mcp:serve --allow-write`. Add `--scope project` to `claude mcp add` to keep the server in the project's `.mcp.json` (shared with your team), or `--scope user` for all your projects. `claude mcp list` shows the servers and whether they connect.

### Claude Desktop

Settings → Developer → Edit Config opens `claude_desktop_config.json`. Add the server and restart Claude Desktop:
```json
{
  "mcpServers": {
    "joomla": {
      "command": "php",
      "args": ["/path/to/site/cli/joomla.php", "mcp:serve"]
    }
  }
}
```

Over SSH: `"command": "ssh"` and `"args": ["user@example.com", "php", "/path/to/site/cli/joomla.php", "mcp:serve"]`. Options go at the end of `args` (e.g. `"--allow-write"`).

### Devin Desktop (formerly Windsurf)

[Devin Desktop](https://devin.ai/desktop) is the new name for Windsurf. Its default agent, Devin Local, adds servers like Claude Code does:
```
devin mcp add joomla -- php /path/to/site/cli/joomla.php mcp:serve
```

Add `-s project` to share the server through the project's `.devin/mcp_config.json`, or `-s user` for all your projects (`~/.config/devin/mcp_config.json`; `%APPDATA%\devin\mcp_config.json` on Windows). The files take the same `mcpServers` block as Claude Desktop above. The legacy Cascade agent reads `~/.config/devin/mcp_config.json` too (Cascade panel → ... → Open MCP config file).

### Cursor, VS Code, Gemini CLI, Codex CLI

These clients use the same command and arguments, in their own configuration files (check your client's documentation for the current location):

- **Cursor** (`.cursor/mcp.json` in the project, or `~/.cursor/mcp.json`) and **Gemini CLI** (`~/.gemini/settings.json`): the same `mcpServers` block as Claude Desktop above.
- **VS Code** with GitHub Copilot (`.vscode/mcp.json`):
  ```json
  {
    "servers": {
      "joomla": {
        "type": "stdio",
        "command": "php",
        "args": ["/path/to/site/cli/joomla.php", "mcp:serve"]
      }
    }
  }
  ```
- **OpenAI Codex CLI** (`~/.codex/config.toml`):
  ```toml
  [mcp_servers.joomla]
  command = "php"
  args = ["/path/to/site/cli/joomla.php", "mcp:serve"]
  ```

### Any other MCP client

The server speaks MCP over **stdio** (standard input and output): configure the client to start the command `php` with the arguments `/path/to/site/cli/joomla.php` and `mcp:serve`. The server identifies itself as `joomla`, with the site's name and version, and gives the assistant short instructions on how to use its tools.

Several sites: add one server per site, each with its own name (`joomla-news`, `joomla-shop`, ...).


## DECIDE WHAT THE ASSISTANT MAY DO

You choose this where the server starts, so the assistant can't change it.

| Option | What it does |
|---|---|
| *(none)* | **Read-only.** Every command which changes nothing, and dry runs of the others: the assistant can look, check and plan, but not change. |
| `--allow-write` | Changes allowed too: content, modules, menus, users, configuration of extensions, a template's CSS, JavaScript and images, cache, ... |
| `--allow-code` | With `--allow-write`: also what puts code on the server. That's installing, reinstalling or updating extensions or Joomla, changing `configuration.php` (`config:set`, `database:convert`), a template's PHP, XML and dot files (e.g. `.htaccess`), and restoring a template backup. |
| `--allow="..."` | Only these commands, separated by commas, with wildcards: `--allow="article:*,category:*,tag:*,site:*"` |
| `--deny="..."` | Never these commands: `--deny="user:*,database:*,core:update"` |
| `--as=username` | Content commands act as this account, whatever the assistant asks, and **its permissions apply** (e.g. an Editor may write and edit articles, but not publish them). Without it they act as the first active Super User. |

Some setups:

| For | Options |
|---|---|
| Looking around, audits, health checks | *(none)* |
| A writing assistant | `--allow-write --allow="article:*,category:list,tag:*,site:info" --as=editor` |
| Building pages and the site's structure | `--allow-write --deny="user:*,database:*,core:*,extension:*"` |
| Template work (CSS, options, layouts) | `--allow-write --allow="template:*,module:*,site:info"` (add `--allow-code` for PHP overrides) |
| Full administration | `--allow-write --allow-code` (on a copy of the site first) |

`--allow` and `--deny` only narrow down what `--allow-write` and `--allow-code` allow; a read-only server stays read-only.


## WORKING WITH AN ASSISTANT

Ask in plain language; the assistant picks the tools. Some things to try:

- "Check the site's health and tell me what to fix first."
- "Which extensions have updates? Which of them are disabled anyway?"
- "Show the last errors in the logs since yesterday."
- "List the articles of the News category which have no intro image."
- "Draft an article about our autumn opening hours in the News category, unpublished, so I can review it."
- "Move all articles tagged 'events-2025' to the Archive."
- "Add a Custom module with our holiday notice above the content, on every page, until Monday."
- "Make the headings of the site a little darker and the body text larger." (it writes `css/custom.css`)
- "What did the command line or the assistant change this week?"

How the tools work, which is useful if you write your own prompts or agents:

- **Names:** each command is a tool with `_` instead of `:` (`article:list` → `article_list`, `menu:item:create` → `menu_item_create`), with the command's options as arguments.
- **Finding things:** `*_list` tools find items and their IDs, `*_get` show one; `site_info` and `site_health` are the place to start, and `template_info` shows a template style's positions, options and CSS design tokens.
- **Dry runs:** tools which change the site take `"dry_run": true` and answer with a plan (`data.plan`) of what they would change. On a read-only server, that's all they accept.
- **Answers:** every tool returns a JSON document with `success`, `data` and `messages`, and on failure `error` with a code: `invalid`, `not_found`, `refused` or `failed`.
- **Deleting:** items are trashed first; only the `*_delete` tools remove trashed items for good.


## SAFETY AND TRACEABILITY

- **Every change is logged** in the User Actions Log (Users → User Actions Log), as made through the command line or MCP, with the command and its options. Values of secret options (passwords, keys) are masked.
- **Secrets stay hidden:** `config:get`/`config:list` mask passwords and keys; the `--show-secrets` option isn't offered to assistants.
- **No server paths:** options which name files or folders on the server (`--text-file`, `--folder`, `--path`, ...) aren't offered to assistants. Database exports and imports through MCP use a folder inside the site's protected backup folder.
- **No network in dry runs:** a dry run through MCP never downloads anything (installing from a URL plans the download instead).
- **Templates:** before an assistant changes a template's files, have it run `template_backup` (`template:restore` puts a backup back), and every file it writes keeps its previous version (`template:file:set ... --restore` undoes the last change). Put the site's own CSS in `css/custom.css` (Hammond, Finch and Rookwood load it when it exists, and updates never touch it).
- **Permissions:** content commands check the acting account's permissions as the administrator would (with `--as`, the account you chose).
- **Try it on a copy first.** A SQLite copy of a site is quick to make: `php cli/joomla.php database:convert --to=sqlite` on a copy of the files (see the [README](README.md#sqlite-support)).


## USING THE COMMAND LINE YOURSELF

### The basics

Run commands from the site's folder (or give the full path of `cli/joomla.php`):

```
php cli/joomla.php list                  # every command, by group
php cli/joomla.php help article:create   # a command's options, with examples
php cli/joomla.php help article          # the commands of a group
```

Options for every command:

| Option | What it does |
|---|---|
| `--format=json` | Answer in JSON: `{"command", "success", "exitCode", "data", "messages"}`, plus `error` (`{"code", "message"}`) on failure |
| `--dry-run` | Show what would change, without changing anything (refused by commands which can't do a dry run, rather than run for real) |
| `-n`, `--no-interaction` | Never ask questions; use the defaults |
| `-q`, `--quiet` | Only show errors |
| `-v`, `--verbose` | Show the trace of unexpected errors |
| `--live-site=URL` | The site's address, for commands which build links |

Exit codes: `0` success, `1` failure, `2` invalid input, `3` not found, `4` refused (permissions or protection).

Items are given by ID, and in most places also by alias, path or title: `--category=journal`, `--category=blog/news`, `--tags="Design,Typography"`. Name filters (e.g. of `config:get`, `user:list`, `extension:list`) take the wildcards `*` and `?`; quote them so the shell doesn't expand them.

### Recipes

**The site**
```
php cli/joomla.php site:info                     # versions, database, paths, main settings
php cli/joomla.php site:health                   # one report: what's fine and what to do
php cli/joomla.php site:down                     # offline mode (site:up to come back)
php cli/joomla.php config:get caching            # one setting ('memcached_*' for several)
php cli/joomla.php config:set caching=2 cachetime=30
```

**Content**
```
php cli/joomla.php article:list --category=news --state=published --limit=10
php cli/joomla.php article:get 42
php cli/joomla.php article:create --title="Autumn opening hours" --category=news \
    --text="<p>From November, we open at 9:00.</p>" --state=unpublished
php cli/joomla.php article:update 42 --featured=yes --tags="Events,News"
php cli/joomla.php article:create --title="Long read" --category=news --text-file=article.html
php cli/joomla.php article:trash 42 43 44        # then article:delete to remove for good
php cli/joomla.php category:create --title="Events" --parent=news
php cli/joomla.php tag:create --title="Events"
```
Add `--as=username` to act as a particular account (its permissions apply), and `--dry-run` to see the change first.

**Modules and menus**
```
php cli/joomla.php module:list --position=sidebar
php cli/joomla.php module:create --type=mod_custom --title="Notice" --position=above-content \
    --content="<p>Closed on Monday.</p>" --pages=all --state=published
php cli/joomla.php module:update 95 --params='{"count":"5"}'
php cli/joomla.php menu:list
php cli/joomla.php menu:item:create --menu=mainmenu --title="Events" --category-blog=news/events
```

**Templates**
```
php cli/joomla.php template:list
php cli/joomla.php template:info 11              # positions, modules, options, CSS design tokens
php cli/joomla.php template:backup rookwood
php cli/joomla.php template:file:set rookwood css/custom.css --create --content="h1 {letter-spacing:-0.02em;}"
php cli/joomla.php template:file:set rookwood css/custom.css --restore    # undo the last change
php cli/joomla.php template:style:update 11 --params='{"defaultTheme":"light"}'
php cli/joomla.php template:restore rookwood      # put the latest backup back
```

**Users**
```
php cli/joomla.php user:list 'a*'
php cli/joomla.php user:add --username=jane --name="Jane Doe" --email=jane@example.com --usergroup=Editor
php cli/joomla.php user:reset-password --username=jane
php cli/joomla.php user:block --username=jane
```

**Extensions and updates**
```
php cli/joomla.php core:update:check
php cli/joomla.php core:update                   # download and apply the latest Joomla 3.x UTD
php cli/joomla.php update:extensions:check
php cli/joomla.php extension:update 10023
php cli/joomla.php extension:list --type=plugin 'littlewaf'
php cli/joomla.php extension:install --url=https://example.com/pkg_example.zip
php cli/joomla.php extension:reinstall 10023     # restore an extension's original files (e.g. after a hack)
```

**Database**
```
php cli/joomla.php maintenance:database          # check the table structure (--fix to fix it)
php cli/joomla.php database:export --folder=/path/to/backups --zip=mysite.zip
php cli/joomla.php database:optimize
php cli/joomla.php database:convert --to=sqlite  # or --to=postgresql / --to=mysqli with --host, --user, --password, --database
```

**Logs, cache and search**
```
php cli/joomla.php log:list
php cli/joomla.php log:tail error.php --since="-2 hours"
php cli/joomla.php actionlog:list --user=cli --since="-1 day"
php cli/joomla.php cache:clean
php cli/joomla.php finder:index                  # rebuild Smart Search's index
```

**New sites**
```
php cli/joomla.php core:install --site-name="My Site" --admin-email=me@example.com --admin-username=admin --sample-data=blog
```
Installs a new site on SQLite in about a second (PHP 7.4+ with `pdo_sqlite`), with the generated password shown at the end. See the [README](README.md#quick-install-from-the-command-line-sqlite).

### Scripting with JSON

Every command answers in JSON with `--format=json`, which suits scripts (here with [jq](https://jqlang.org/)):

```
# IDs and titles of unpublished articles
php cli/joomla.php article:list --state=unpublished --format=json | jq -r '.data.items[] | "\(.id)\t\(.title)"'

# fail a monitoring check when the site has errors
php cli/joomla.php site:health --strict --format=json > health.json || echo "Site needs attention"

# publish every article of a category
for id in $(php cli/joomla.php article:list --category=drafts --format=json | jq '.data.items[].id'); do
    php cli/joomla.php article:publish "$id"
done
```

Check the exit code (or `success`) rather than parsing messages.

### Scheduled tasks (cron)

The scripts already in `cli/` (e.g. `garbagecron.php`, `update_cron.php`, `finder_indexer.php`) still work as before and now run the new commands, so existing cron jobs need no changes. For new jobs, call the commands directly, as the web server's user:

```
# every night at 03:00: clean expired cache entries and sessions
0 3 * * * cd /path/to/site && php cli/joomla.php cache:clean expired -q && php cli/joomla.php session:gc -q
```

Housekeeping commands like these aren't recorded in the User Actions Log, so it doesn't fill up with routine runs.


## TROUBLESHOOTING

- **"Permission denied", or files the site can't write afterwards:** run the commands as the web server's user (`sudo -u www-data php cli/joomla.php ...`).
- **The assistant doesn't connect:** run the same command in a terminal; `mcp:serve` should wait silently for input (Ctrl+C to stop). Use absolute paths for `php` and `cli/joomla.php`, and for SSH make sure key-based login works without a prompt. Messages from PHP go to the client's MCP log (standard error), not to the assistant.
- **A different PHP than the site's:** `php -v` on the command line can differ from the web server's PHP; call the right one by its full path.
- **"This server is read-only":** the assistant asked for a change on a server without `--allow-write`; that's your choice to make in the client's configuration.
- **Links point to the wrong address:** give `--live-site=https://www.example.com`.
- **Windows and macOS** aren't tested (as for the rest of this distribution); WSL or a Linux server is the safe choice.


## COMMAND REFERENCE

`php cli/joomla.php help <command>` shows a command's options and examples.

| Group | Commands |
|---|---|
| Site | `site:info`, `site:health`, `site:down`, `site:up` |
| Configuration | `config:get`, `config:list`, `config:set` |
| Joomla | `core:install`, `core:update`, `core:update:check`, `core:update:channel`, `update:joomla:remove-old-files` |
| Articles | `article:list`, `article:get`, `article:create`, `article:update`, `article:publish`, `article:unpublish`, `article:archive`, `article:trash`, `article:delete` |
| Categories | `category:list`, `category:get`, `category:create`, `category:update`, `category:publish`, `category:unpublish`, `category:archive`, `category:trash`, `category:delete` |
| Tags | `tag:list`, `tag:get`, `tag:create`, `tag:update`, `tag:publish`, `tag:unpublish`, `tag:archive`, `tag:trash`, `tag:delete` |
| Modules | `module:list`, `module:get`, `module:create`, `module:update`, `module:publish`, `module:unpublish`, `module:trash`, `module:delete` |
| Menus | `menu:list`, `menu:item:list`, `menu:item:get`, `menu:item:create`, `menu:item:update`, `menu:item:publish`, `menu:item:unpublish`, `menu:item:trash`, `menu:item:delete` |
| Templates | `template:list`, `template:info`, `template:style:update`, `template:file:list`, `template:file:get`, `template:file:set`, `template:file:delete`, `template:backup`, `template:backup:list`, `template:restore` |
| Users | `user:list`, `user:add`, `user:delete`, `user:block`, `user:unblock`, `user:reset-password`, `user:addtogroup`, `user:removefromgroup` |
| Extensions | `extension:list`, `extension:install`, `extension:update`, `extension:reinstall`, `extension:remove`, `extension:enable`, `extension:disable`, `extension:discover`, `extension:discover:list`, `extension:discover:install`, `update:extensions:check` |
| Database | `maintenance:database`, `database:export`, `database:import`, `database:convert`, `database:optimize` |
| Logs | `log:list`, `log:tail`, `actionlog:list` |
| Cache, sessions, search | `cache:clean`, `session:gc`, `session:metadata:gc`, `finder:index` |
| AI assistants | `mcp:serve` |
| Help | `list`, `help` |

Extensions can add commands of their own; they appear in `list` and, as tools, in the MCP server.
