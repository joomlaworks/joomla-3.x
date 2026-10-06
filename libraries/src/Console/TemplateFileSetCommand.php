<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * Writes a file of a template: new contents, an addition at the end, or a find and replace; or puts back its previous version.
 *
 * @since  3.17.0
 */
class TemplateFileSetCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:file:set';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Write a file of a template (or undo the last change)';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Changes a file inside a template\'s folder (never outside it): --content (or --content-file) replaces its contents, '
		. '--append adds --content at the end, and --find with --replace replaces exact text (which must occur once, unless --all). '
		. 'A new file needs --create. PHP files are checked for syntax errors and XML files for well-formedness first, and aren\'t '
		. 'written when they fail. The previous version of the file is kept (in the site\'s backup folder), and --restore puts it back '
		. '(again to redo). --dry-run shows the change as a diff. For the site\'s own CSS, use the file the template loads for it '
		. '(css/custom.css in Hammond and Finch, see template:info), which updates never touch; changes to the template\'s own files are '
		. 'lost when the template is updated. template:backup saves the whole template first.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('template', self::ARGUMENT_REQUIRED, 'The template\'s name (its folder), e.g. hammond');
		$this->addArgument('path', self::ARGUMENT_REQUIRED, 'The file, relative to the template\'s folder, e.g. css/custom.css');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
		$this->addOption('content', null, self::OPTION_REQUIRED, 'The new contents (with --append: the text to add)');
		$this->addOption('content-file', null, self::OPTION_REQUIRED, 'Read the contents from this file ("-" for the standard input)');
		$this->addOption('encoding', null, self::OPTION_REQUIRED, 'The encoding of --content: text or base64 (for a binary file, e.g. an image)', 'text');
		$this->addOption('append', null, self::OPTION_NONE, 'Add the contents at the end of the file');
		$this->addOption('find', null, self::OPTION_REQUIRED, 'Exact text to replace (it must occur once, unless --all)');
		$this->addOption('replace', null, self::OPTION_REQUIRED, 'The text which replaces --find (empty removes it)');
		$this->addOption('all', null, self::OPTION_NONE, 'With --find: replace every occurrence');
		$this->addOption('create', null, self::OPTION_NONE, 'Create the file (and its folders) when it doesn\'t exist');
		$this->addOption('restore', null, self::OPTION_NONE, 'Put back the version before the last change');
	}

	/**
	 * Writing PHP, XML or dot files is writing code.
	 *
	 * @param   array|null  $options    The options of a run
	 * @param   array|null  $arguments  The arguments of a run
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function writesCode(?array $options = null, ?array $arguments = null)
	{
		return $arguments === null || static::isCode(isset($arguments['path']) ? $arguments['path'] : '');
	}

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$template = $this->findTemplate($io, $io->getArgument('template'), (string) $io->getOption('client'));

		if (!is_array($template))
		{
			return $template;
		}

		$restore = (bool) $io->getOption('restore');
		$find    = $io->getOption('find');
		$file    = $this->resolvePath($io, $template, $io->getArgument('path'), !$restore && !$io->getOption('create'));

		if (!is_array($file))
		{
			return $file;
		}

		$current = $file['exists'] ? file_get_contents($file['full']) : '';

		if ($current === false || strlen($current) > self::MAX_FILE_SIZE)
		{
			$io->error(sprintf('Cannot read %s, or it is larger than %d MB.', $file['relative'], self::MAX_FILE_SIZE / 1048576));

			return self::FAILURE;
		}

		// The new contents: null removes the file (only when restoring the state before it was created)
		if ($restore)
		{
			$new = $this->getPreviousVersion($io, $template, $file);

			if ($new === false)
			{
				return self::NOT_FOUND;
			}
		}
		else
		{
			$new = $this->getNewContents($io, $file, $current, $find);

			if (!is_string($new))
			{
				return $new;
			}
		}

		if ($new !== null && strlen($new) > self::MAX_FILE_SIZE)
		{
			$io->error(sprintf('The new contents are larger than %d MB.', self::MAX_FILE_SIZE / 1048576));

			return self::REFUSED;
		}

		if ($new === $current && $file['exists'])
		{
			$io->setData('path', $file['relative']);
			$io->setData('changed', false);
			$io->success(sprintf('%s is unchanged: it already has these contents.', $file['relative']));

			return self::SUCCESS;
		}

		$error = $new === null ? null : $this->check($file['relative'], $new);

		if ($error !== null)
		{
			$io->error(sprintf('%s was not written: %s', $file['relative'], $error));

			return self::INVALID;
		}

		$text = static::isText($current) && ($new === null || static::isText($new));
		$diff = $text ? static::diff($current, (string) $new, $file['relative']) : '';
		$what = $new === null ? 'Remove' : ($file['exists'] ? 'Change' : 'Create');

		$io->setData('path', $file['relative']);
		$io->setData('template', $template['name']);
		$io->setData('diff', $text ? $diff : null);

		if ($io->isDryRun())
		{
			$io->plan(sprintf('%s %s in the %s template %s', $what, $file['relative'], $template['client'], $template['name'])
				. ($text ? '' : sprintf(' (binary, %d bytes)', $new === null ? 0 : strlen($new))), array('action' => strtolower($what), 'path' => $file['relative']));

			if ($diff !== '' && !$io->isJson())
			{
				$io->writeln(rtrim($diff, "\n"));
			}

			return self::SUCCESS;
		}

		$folder = $file['exists'] ? dirname($file['full']) : $template['path'];

		if (!is_writable($file['exists'] ? $file['full'] : $folder))
		{
			$io->error(sprintf('%s is not writable for the user running this command.', $file['exists'] ? $file['relative'] : 'The template\'s folder'));

			return self::FAILURE;
		}

		if (!static::keepPreviousVersion($template, $file))
		{
			$io->error('The previous version could not be kept in the site\'s backup folder (backup/), so nothing was changed.');

			return self::FAILURE;
		}

		if ($new === null ? !@unlink($file['full']) : !static::writeFile($file['full'], $new))
		{
			$io->error(sprintf('%s could not be written.', $file['relative']));

			return self::FAILURE;
		}

		$io->setData('changed', true);
		$io->setData('size', $new === null ? null : strlen($new));
		$io->success(sprintf('%s %s in the %s template %s%s.', $file['relative'], $new === null ? 'removed' : ($file['exists'] ? 'changed' : 'created'),
			$template['client'], $template['name'], $restore ? ' (the previous version is back)' : ''));

		if ($diff !== '' && !$io->isJson())
		{
			$io->writeln(rtrim($diff, "\n"));
		}

		$io->text('Undo with: template:file:set ' . $template['name'] . ' ' . $file['relative'] . ' --restore'
			. ($template['client'] === 'site' ? '' : ' --client=administrator'));

		return self::SUCCESS;
	}

	/**
	 * The contents the options give.
	 *
	 * @param   CommandIO    $io       The input values and the output
	 * @param   array        $file     The file
	 * @param   string       $current  Its current contents
	 * @param   string|null  $find     The text to replace
	 *
	 * @return  string|integer  The contents, or an exit code
	 *
	 * @since   3.17.0
	 */
	private function getNewContents(CommandIO $io, array $file, $current, $find)
	{
		$content  = $io->getOption('content');
		$source   = (string) $io->getOption('content-file');
		$encoding = strtolower((string) $io->getOption('encoding'));

		if (!in_array($encoding, array('text', 'base64'), true))
		{
			$io->error('--encoding must be text or base64.');

			return self::INVALID;
		}

		if ($find !== null)
		{
			if ($content !== null || $source !== '' || $io->getOption('append'))
			{
				$io->error('--find works with --replace, not with --content, --content-file or --append.');

				return self::INVALID;
			}

			if ((string) $find === '' || $io->getOption('replace') === null)
			{
				$io->error('--find needs the text to replace and --replace the text to put instead (it may be empty).');

				return self::INVALID;
			}

			$count = substr_count($current, (string) $find);

			if ($count === 0)
			{
				$io->error(sprintf('%s does not contain the --find text (it must match exactly, spaces and line breaks included).', $file['relative']));

				return self::NOT_FOUND;
			}

			if ($count > 1 && !$io->getOption('all'))
			{
				$io->error(sprintf('The --find text occurs %d times in %s: give more of the surrounding text so it occurs once, or add --all.', $count, $file['relative']));

				return self::INVALID;
			}

			return str_replace((string) $find, (string) $io->getOption('replace'), $current);
		}

		if ($content !== null && $source !== '')
		{
			$io->error('Give either --content or --content-file, not both.');

			return self::INVALID;
		}

		if ($source !== '')
		{
			$content = $source === '-' ? stream_get_contents(STDIN) : (is_file($source) && is_readable($source) ? file_get_contents($source) : false);

			if ($content === false)
			{
				$io->error(sprintf('Cannot read %s.', $source));

				return self::INVALID;
			}
		}
		elseif ($content === null)
		{
			$io->error('Give the contents (--content or --content-file), --find and --replace, or --restore.');

			return self::INVALID;
		}
		elseif ($encoding === 'base64')
		{
			$content = base64_decode((string) $content, true);

			if ($content === false)
			{
				$io->error('--content is not valid base64.');

				return self::INVALID;
			}
		}

		$content = (string) $content;

		if ($io->getOption('append'))
		{
			return $current === '' || substr($current, -1) === "\n" ? $current . $content : $current . "\n" . $content;
		}

		return $content;
	}

	/**
	 * The version before the last change.
	 *
	 * @param   CommandIO  $io        The input values and the output
	 * @param   array      $template  The template
	 * @param   array      $file      The file
	 *
	 * @return  string|null|false  The contents; null when the file didn't exist before; false when there's no previous version
	 *
	 * @since   3.17.0
	 */
	private function getPreviousVersion(CommandIO $io, array $template, array $file)
	{
		$previous = static::getPreviousVersionFile($template, $file['relative'], false);

		if ($previous !== false && is_file($previous . '.new'))
		{
			if (!$file['exists'])
			{
				$io->error(sprintf('%s was created by the last change and is already gone.', $file['relative']));

				return false;
			}

			return null;
		}

		if ($previous === false || !is_file($previous))
		{
			$io->error(sprintf('There is no previous version of %s: it was not changed with these commands.', $file['relative']));

			return false;
		}

		return (string) file_get_contents($previous);
	}

	/**
	 * Check new contents: PHP for syntax errors, XML for well-formedness (a broken templateDetails.xml breaks the template).
	 *
	 * @param   string  $path     The file
	 * @param   string  $content  The new contents
	 *
	 * @return  string|null  What's wrong, or null
	 *
	 * @since   3.17.0
	 */
	private function check($path, $content)
	{
		if (preg_match('/\.(php[0-9]*|phtml|inc)$/i', $path))
		{
			$error = static::checkPhpSyntax($content);

			return $error === null ? null : 'PHP syntax error: ' . $error;
		}

		if (preg_match('/\.xml$/i', $path))
		{
			$previous = libxml_use_internal_errors(true);
			$document = simplexml_load_string($content);
			$errors   = libxml_get_errors();
			libxml_clear_errors();
			libxml_use_internal_errors($previous);

			if ($document === false)
			{
				return 'invalid XML' . ($errors ? sprintf(' (%s on line %d)', trim($errors[0]->message), $errors[0]->line) : '');
			}
		}

		return null;
	}
}
