# Joomla 3.x UTD: adding commands to the command line (for extension developers)

Joomla 3.x UTD has a command line interface, `php cli/joomla.php`, with the command names of Joomla 4 and later and many more. Its commands are also offered to AI assistants by its MCP server (`mcp:serve`). Extensions can add commands of their own. Those commands appear in `list` and `help`, support `--format=json`, `--dry-run` and the User Actions Log like the core ones, and become MCP tools without any extra work.

This guide is for developers of extensions (components, plugins, modules) who want to add such commands. For using the command line and connecting an assistant, see [AI-GUIDE.md](AI-GUIDE.md).

## CONTENTS

- [How it works](#how-it-works)
- [A complete example](#a-complete-example)
- [Writing a command](#writing-a-command)
- [Declaring what a command does](#declaring-what-a-command-does)
- [Output](#output)
- [Commands as MCP tools](#commands-as-mcp-tools)
- [Working with Joomla inside a command](#working-with-joomla-inside-a-command)
- [Compatibility with stock Joomla 3, Joomla 4 and later](#compatibility-with-stock-joomla-3-joomla-4-and-later)
- [Testing checklist](#testing-checklist)
- [Reference](#reference)

## HOW IT WORKS

- Commands come from plugins in the **`console`** group. When the command line starts, it loads those plugins and triggers **`onGetConsoleCommands`**. A plugin returns an array of command objects.
- A command is a class extending **`Joomla\CMS\Console\AbstractCommand`**, the base class of every core command. It declares its name, arguments and options, and what it does (reads only, supports dry runs, writes code...). It does its work in `doExecute()`, writing its output through a `CommandIO` object.
- **Names are first come, first served.** Core commands are registered first, so a plugin can't replace one. A plugin command whose name is taken (by core or by another plugin) is skipped, with a message on the error output. Use a prefix of your own, e.g. `k2:item:list`.
- A plugin which fails while giving its commands doesn't stop the command line: the core commands still work. Plugins aren't loaded before the site is installed (`core:install`).

## A COMPLETE EXAMPLE

A console plugin with two commands: one which only reads (`acme:article:count`), and one which changes articles and supports `--dry-run` (`acme:article:feature`). Three files in `plugins/console/acme/`:

**`acme.xml`**, the manifest:

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension version="3.9" type="plugin" group="console" method="upgrade">
	<name>plg_console_acme</name>
	<author>Acme</author>
	<version>1.0.0</version>
	<description>Command line commands of the Acme extension</description>
	<files>
		<filename plugin="acme">acme.php</filename>
		<filename>commands.php</filename>
	</files>
</extension>
```

**`acme.php`**, the plugin. It loads the commands only when the command line asks for them, so the plugin stays harmless where `AbstractCommand` doesn't exist (stock Joomla 3, see [Compatibility](#compatibility-with-stock-joomla-3-joomla-4-and-later)):

```php
<?php
defined('_JEXEC') or die;

class PlgConsoleAcme extends JPlugin
{
	/**
	 * @param   object  $app  The console application
	 *
	 * @return  array  The commands
	 */
	public function onGetConsoleCommands($app)
	{
		if (!class_exists('Joomla\\CMS\\Console\\AbstractCommand'))
		{
			return array();
		}

		require_once __DIR__ . '/commands.php';

		return array(new AcmeArticleCountCommand, new AcmeArticleFeatureCommand);
	}
}
```

**`commands.php`**, the commands:

```php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Console\AbstractCommand;
use Joomla\CMS\Console\CommandIO;

/**
 * Counts articles by state: it only reads.
 */
class AcmeArticleCountCommand extends AbstractCommand
{
	protected $name = 'acme:article:count';
	protected $description = 'Count articles by state';
	protected $help = 'Counts the articles in each state (1 published, 0 unpublished, 2 archived, -2 trashed). '
		. 'With --category, only those of one category.';
	protected $readOnly = true;

	protected function configure()
	{
		$this->addOption('category', null, self::OPTION_REQUIRED, 'Only articles of this category ID');
	}

	protected function doExecute(CommandIO $io)
	{
		$io->title('Article Count');

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('state') . ', COUNT(*) AS ' . $db->quoteName('total'))
			->from($db->quoteName('#__content'))
			->group($db->quoteName('state'))
			->order($db->quoteName('state'));

		if ($io->getOption('category') !== null)
		{
			if (!ctype_digit((string) $io->getOption('category')))
			{
				$io->error('--category must be a category ID.');

				return self::INVALID;
			}

			$query->where($db->quoteName('catid') . ' = ' . (int) $io->getOption('category'));
		}

		$io->table(array('state' => 'State', 'total' => 'Articles'), $db->setQuery($query)->loadAssocList(), 'states');

		return self::SUCCESS;
	}
}

/**
 * Features an article: it changes the site, and supports --dry-run.
 */
class AcmeArticleFeatureCommand extends AbstractCommand
{
	protected $name = 'acme:article:feature';
	protected $description = 'Feature an article';
	protected $help = 'Marks an article as featured, so it shows on the Featured Articles pages.';
	protected $dryRun = true;
	protected $superUser = true;

	protected function configure()
	{
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The article ID');
	}

	protected function doExecute(CommandIO $io)
	{
		$io->title('Feature Article');

		$table = JTable::getInstance('Content');

		if (!ctype_digit((string) $io->getArgument('id')) || !$table->load((int) $io->getArgument('id')))
		{
			$io->error(sprintf('Article %s not found.', $io->getArgument('id')));

			return self::NOT_FOUND;
		}

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Feature article %d "%s"', $table->id, $table->title), array('action' => 'feature', 'id' => (int) $table->id));

			return self::SUCCESS;
		}

		$model = $this->getAdministratorModel('com_content', 'Article', 'ContentModel');
		$ids   = array((int) $table->id);

		if (!$model->featured($ids, 1))
		{
			$io->error($model->getError() ?: 'The article was not featured.');

			return self::FAILURE;
		}

		$io->setData('id', (int) $table->id);
		$io->success(sprintf('Article %d "%s" featured.', $table->id, $table->title));

		return self::SUCCESS;
	}
}
```

Install it like any plugin (a package, or Extensions: Discover; Joomla's discovery skips manifests named `example.xml`), enable it, and the commands are there:

```bash
php cli/joomla.php list acme
php cli/joomla.php help acme:article:feature
php cli/joomla.php acme:article:count --format=json
php cli/joomla.php acme:article:feature 12 --dry-run
php cli/joomla.php acme:article:feature 12
```

In your own extension, ship the console plugin in your package next to your component. The command classes can live wherever suits you (e.g. in the component's folder), as long as the plugin loads them only inside its event handler.

## WRITING A COMMAND

A command sets a few properties, declares its arguments and options in `configure()`, and does its work in `doExecute(CommandIO $io)`, returning an exit code.

**Properties:**

| Property | Default | Meaning |
|---|---|---|
| `$name` | (required) | The command's name, `prefix:noun:verb`, e.g. `k2:item:list`. Lowercase, colons between the parts |
| `$description` | `''` | One line, shown by `list` and as the start of the MCP tool's description |
| `$help` | `''` | Longer text, shown by `help <command>` and added to the MCP tool's description. Say what the command changes, and what each run needs |
| `$readOnly` | `false` | The command changes neither the site nor its files. See [Declaring what a command does](#declaring-what-a-command-does) |
| `$dryRun` | `false` | The command supports `--dry-run` |
| `$superUser` | `false` | The command runs as a Super User (for code with permission checks, e.g. models that check `core.edit`) |
| `$logged` | `true` | Successful runs that may change something are recorded in the User Actions Log. Set `false` for routine housekeeping run by cron jobs |
| `$hidden` | `false` | Leave the command out of `list` (and out of MCP) |
| `$serverPathOptions` | `array()` | Options which name a file or folder on the server (see [Commands as MCP tools](#commands-as-mcp-tools)) |

**Arguments and options**, in `configure()`:

```php
protected function configure()
{
	// name, mode, description, default
	$this->addArgument('ids', self::ARGUMENT_REQUIRED | self::ARGUMENT_ARRAY, 'Item IDs');

	// name, shortcut, mode, description, default
	$this->addOption('category', null, self::OPTION_REQUIRED, 'Only items of this category ID');
	$this->addOption('force', 'f', self::OPTION_NONE, 'Also items which are checked out');
}
```

- Argument modes: `ARGUMENT_REQUIRED`, `ARGUMENT_OPTIONAL`, and `ARGUMENT_ARRAY` (several values, combined with one of the others).
- Option modes: `OPTION_NONE` (a flag), `OPTION_REQUIRED` (needs a value) and `OPTION_OPTIONAL` (a value may follow).
- Read them in `doExecute()` with `$io->getArgument('ids')` and `$io->getOption('category')`. An option that wasn't given is `null`, or its default; a flag is `true` or `false`.
- Don't declare the global options yourself. Every command has `--help`, `--quiet`, `--no-interaction`, `--format=json`, `--verbose`, `--live-site` and, if it supports them, `--dry-run`.
- Validate values yourself, and return `INVALID` with a message when they're wrong.

**Exit codes** (constants of `AbstractCommand`):

| Code | Constant | When |
|---|---|---|
| 0 | `SUCCESS` | Done |
| 1 | `FAILURE` | The action failed |
| 2 | `INVALID` | Invalid input (a wrong value, a missing argument, an ambiguous name) |
| 3 | `NOT_FOUND` | What was asked for doesn't exist |
| 4 | `REFUSED` | Not allowed (a protected item, a permission) |

An exception thrown from `doExecute()` is caught: its message is shown as an error and the command exits with `FAILURE`. Prefer explicit messages and codes for errors you expect.

## DECLARING WHAT A COMMAND DOES

These declarations are what keep a command safe: they're how `--dry-run` works, what the MCP server checks before letting an assistant run a command, and what decides which runs go into the User Actions Log. Get them right.

- **`$readOnly = true`** for commands which change nothing: no database writes, no files, no cache tables, nothing sent anywhere. A read-only MCP server (the default) offers only these, and dry runs.
  - Refreshing a cache table counts as a change (core's `update:extensions:check` isn't read-only for that reason).
  - When it depends on the run (e.g. a command which changes things only with `--fix`), override `isReadOnly($options, $arguments)` to answer for a given run, and `hasReadOnlyRuns()` to return `true`.
- **`$dryRun = true`** for commands which change things and can tell what they would change. Under `--dry-run`, `$io->isDryRun()` is `true`: report each change with `$io->plan($text, $item)` and change nothing. Don't print success messages ("... saved.") in a dry run.
  - A dry run must not reach the network either (download nothing; plan the download instead): over MCP it's all a read-only server allows.
- **Neither:** a command which changes things but can't do a dry run is refused under `--dry-run`, and a read-only MCP server doesn't offer it at all. Prefer adding dry-run support.
- **`writesCode()`**: override it to return `true` (for the runs that do) if the command writes code the server runs or reads as configuration: PHP, JavaScript, `.htaccess`, `configuration.php`, or a package it installs. The MCP server refuses those runs without `--allow-code`, even when it allows changes.
- **`isSecret($name)`**: option and argument values whose names look like secrets (`password`, `secret`, `token`, `api_key`, `download_id`, `key`...) are masked in the User Actions Log. Override it to add your own names.
- **`$superUser`**: commands which call code with permission checks (models, `User::save()`) need it, since whoever runs the command line can change the site anyway. If a command should act as a particular account and respect its permissions instead, check them yourself (as core's content commands do with `--as`).

## OUTPUT

Write through `$io`, never with `echo`. The same calls give readable text in a terminal and structured data with `--format=json`, and output is redirected correctly when the command runs as an MCP tool.

| Method | Text | JSON |
|---|---|---|
| `title($text)` | A heading | (left out) |
| `text($text)`, `writeln($text)` | A line | (left out) |
| `success($text)`, `warning($text)`, `error($text)` | `[OK]`, `[WARNING]`, `[ERROR]` lines | In `messages` (an error also sets `error` with the exit code's name) |
| `table($columns, $rows, $key = 'items')` | A table | `data.<key>`: the rows, with the given columns |
| `definitionList($pairs, $key = null)` | Label/value lines | `data.<key>`, or merged into `data` |
| `setData($key, $value)` | (nothing) | `data.<key>` |
| `plan($text, $item)` | `[DRY RUN] Would: ...` | `data.plan[]` |
| `confirm($question, $default)`, `ask($question, $default)` | Asks in a terminal | Return the default (also with `--no-interaction` or without a terminal: `$io->isInteractive()` is `false`) |

A JSON run prints one object:

```json
{"command": "acme:article:count", "success": true, "exitCode": 0, "data": {"states": [{"state": "1", "total": "42"}]}, "messages": []}
```

Keep `data` stable: scripts and assistants read it. Use plain values and arrays, with keys in camelCase or snake_case, consistently. Values read from the database come as strings (as above); cast those which are numbers or booleans before `setData()` if your data promises them.

## COMMANDS AS MCP TOOLS

Every command not `$hidden` becomes a tool of `php cli/joomla.php mcp:serve`, with no work on your side:

- **The tool's name** is the command's, colons replaced: `acme:article:count` → `acme_article_count`.
- **Its description** is `$description` followed by `$help`. Assistants choose tools by these, so say plainly what the command does and changes.
- **Its parameters:** arguments (strings, arrays for `ARGUMENT_ARRAY`; required ones required), options (booleans for flags, strings otherwise, with their defaults), and `dry_run` for commands which support dry runs.
- **What runs:**

  | | Read-only commands | Changing commands with dry runs | Changing commands without |
  |---|---|---|---|
  | Default server | Run | Only `dry_run: true` | Not offered |
  | `--allow-write` | Run | Run | Run |
  | Runs where `writesCode()` | — | Only `dry_run: true` without `--allow-code` | Refused without `--allow-code` |

- **Server paths stay on the server:** options which name a file or folder there are never offered to assistants. Options whose names end in `-file` are recognised automatically; list the others in `$serverPathOptions` (e.g. `array('folder', 'zip')`).
- **The protocol owns standard output:** anything you `echo` or `print` would corrupt it. Write through `$io` only.

## WORKING WITH JOOMLA INSIDE A COMMAND

- **The application:** while a command runs, `JFactory::getApplication()` returns the console application, which stands in for the site/administrator application for the methods core code commonly calls (`enqueueMessage()`, `getUserState()`, `isClient()`...). Messages queued with `enqueueMessage()`, e.g. by models, are shown as the command's messages (errors as errors, warnings and notices as warnings).
- **The database:** `JFactory::getDbo()`, as everywhere. Commands run on every database Joomla 3.x UTD supports (MySQL/MariaDB, PostgreSQL, SQLite, SQL Server); use the query builder and `quoteName()`.
- **Administrator models:** `$this->getAdministratorModel('com_example', 'Item', 'ExampleModel')` gives a model of any component's administrator part, yours too, as in the example: it loads the component's language, registers its `models` and `tables` folders and creates the model with `ignore_request`.
  - Models loaded this way skip `populateState()`: set the state they need (`$model->setState(...)`).
  - `publish()`, `delete()` and similar take the IDs by reference: pass a copy.
  - Admin models don't check permissions (controllers do): check them in the command if it acts for an account.
- **Language:** `getAdministratorModel()` loads the component's administrator language; load others yourself (`JFactory::getLanguage()->load('plg_example', JPATH_ADMINISTRATOR)`) if you show their strings.
- **Caches:** after changing content, clean the cache groups it affects (`$this->cleanCacheGroup('com_example')`).
- **PHP:** Joomla 3.x UTD runs on PHP 7.1 to 8.5. If your extension supports PHP 7.1, use no newer syntax (arrow functions, typed properties, `match`, `??=`...).

## COMPATIBILITY WITH STOCK JOOMLA 3, JOOMLA 4 AND LATER

- **Stock Joomla 3** has no `cli/joomla.php`, no `AbstractCommand` and no `onGetConsoleCommands` event. A console plugin is harmless there as long as the command classes are loaded only inside the handler (the example's `class_exists()` check and `require_once`): the handler never runs, and nothing references the missing classes.
- **Joomla 4 and later** have a console of their own (Symfony Console), with a different API: commands extend `Joomla\Console\Command\AbstractCommand` and are added through a subscriber to `ApplicationEvents::BEFORE_EXECUTE`. They don't trigger `onGetConsoleCommands`, so the same plugin does nothing there. If your extension also supports Joomla 4/5, keep the logic in classes of your own, and write a thin command class for each console.
- **Command names:** where an extension also has Joomla 4/5 commands, use the same names in both, so scripts work on either.

## TESTING CHECKLIST

- [ ] `php cli/joomla.php list` shows the commands, `help <command>` their arguments, options and help text.
- [ ] Each command with good and bad input: the exit codes (`echo $?`) and messages are right.
- [ ] `--format=json`: valid JSON, with the data you expect and `error` on failures.
- [ ] `--dry-run` changes nothing (check the database), and plans each change.
- [ ] Read-only commands really change nothing.
- [ ] No PHP messages with `error_reporting` at `E_ALL` (`php -d error_reporting=-1 cli/joomla.php ...`), on the oldest and newest PHP you support.
- [ ] As an MCP tool: start `php cli/joomla.php mcp:serve` and send `tools/list` and `tools/call` over standard input (JSON-RPC, one message per line), or connect an MCP client. Check the read-only server's refusals, and `dry_run`.
- [ ] Your plugin on stock Joomla 3 (no errors) and, if you support it, on Joomla 4/5.
- [ ] If the command writes files or code: `writesCode()`, and `$serverPathOptions` for options naming server paths.

## REFERENCE

**`AbstractCommand`** (`libraries/src/Console/AbstractCommand.php`): methods a command overrides or calls.

| Method | Purpose |
|---|---|
| `configure()` | Declare arguments and options |
| `doExecute(CommandIO $io)` | Do the work; return an exit code |
| `addArgument($name, $mode, $description, $default = null)` | Declare an argument |
| `addOption($name, $shortcut, $mode, $description, $default = null)` | Declare an option |
| `isReadOnly($options = null, $arguments = null)` | Whether a run only reads (default: `$readOnly`) |
| `hasReadOnlyRuns()` | Whether some runs only read (default: `$readOnly`) |
| `writesCode($options = null, $arguments = null)` | Whether a run writes code (default: `false`) |
| `isSecret($name)` | Whether a value is masked in the User Actions Log |
| `getAdministratorModel($component, $name, $prefix)` | An administrator model of any component, `ignore_request` (its language, models and tables loaded) |
| `cleanCacheGroup($group)` | Clean a cache group, site and administrator |

**`CommandIO`** (`libraries/src/Console/CommandIO.php`): `getArgument()`, `getOption()`, `isJson()`, `isQuiet()`, `isInteractive()`, `isDryRun()`, `plan()`, `title()`, `text()`, `writeln()`, `success()`, `warning()`, `error()`, `table()`, `definitionList()`, `setData()`, `ask()`, `confirm()`.

The core commands in `libraries/src/Console/` are the best examples: e.g. `ExtensionListCommand.php` (read-only, filters, a table), `ExtensionRemoveCommand.php` (a change with a dry run and a confirmation) and `MaintenanceDatabaseCommand.php` (read-only unless `--fix`).
