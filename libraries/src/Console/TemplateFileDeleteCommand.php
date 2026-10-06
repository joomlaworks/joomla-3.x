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
 * Deletes a file of a template, keeping it as the previous version.
 *
 * @since  3.17.0
 */
class TemplateFileDeleteCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:file:delete';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Delete a file of a template';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Deletes a file inside a template\'s folder (never outside it), e.g. an override in html/. The file is kept as its '
		. 'previous version (in the site\'s backup folder): template:file:set <template> <path> --restore brings it back. A template\'s '
		. 'index.php and templateDetails.xml, which it can\'t work without, are never deleted.';

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
		$this->addArgument('path', self::ARGUMENT_REQUIRED, 'The file, relative to the template\'s folder, e.g. html/com_content/article/default.php');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
	}

	/**
	 * Deleting PHP, XML or dot files changes the code the server runs.
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

		$file = $this->resolvePath($io, $template, $io->getArgument('path'));

		if (!is_array($file))
		{
			return $file;
		}

		if (in_array(strtolower($file['relative']), array('index.php', 'templatedetails.xml'), true))
		{
			$io->error(sprintf('%s is never deleted: the template can\'t work without it.', $file['relative']));

			return self::REFUSED;
		}

		$io->setData('path', $file['relative']);
		$io->setData('template', $template['name']);

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Delete %s from the %s template %s', $file['relative'], $template['client'], $template['name']),
				array('action' => 'delete', 'path' => $file['relative']));

			return self::SUCCESS;
		}

		if (!is_writable(dirname($file['full'])))
		{
			$io->error(sprintf('The folder of %s is not writable for the user running this command.', $file['relative']));

			return self::FAILURE;
		}

		if (!static::keepPreviousVersion($template, $file))
		{
			$io->error('The file could not be kept in the site\'s backup folder (backup/), so it was not deleted.');

			return self::FAILURE;
		}

		if (!@unlink($file['full']))
		{
			$io->error(sprintf('%s could not be deleted.', $file['relative']));

			return self::FAILURE;
		}

		if (function_exists('opcache_invalidate') && static::isCode($file['full']))
		{
			@opcache_invalidate($file['full'], true);
		}

		$io->success(sprintf('%s deleted from the %s template %s.', $file['relative'], $template['client'], $template['name']));
		$io->text('Bring it back with: template:file:set ' . $template['name'] . ' ' . $file['relative'] . ' --restore'
			. ($template['client'] === 'site' ? '' : ' --client=administrator'));

		return self::SUCCESS;
	}
}
