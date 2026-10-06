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
 * Backs up a template's folder.
 *
 * @since  3.17.0
 */
class TemplateBackupCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:backup';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Back up a template\'s files';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Saves all the files of a template\'s folder in a ZIP file, in the site\'s backup folder: backup/<a folder with a '
		. 'name nobody can guess>/templates/ in the site\'s root, which updates never touch (rules there block web access on Apache '
		. 'and IIS; on other servers, such as nginx, the folder\'s name keeps it private). Back up before changing a template; '
		. 'template:restore puts a backup back, template:backup:list shows them.';

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
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
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

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Save the %d files of the %s template %s in a ZIP file in the site\'s backup folder', count(static::listFiles($template['path'])),
				$template['client'], $template['name']), array('action' => 'backup', 'template' => $template['name']));

			return self::SUCCESS;
		}

		$backup = static::createBackup($template);

		if (!is_array($backup))
		{
			$io->error($backup);

			return self::FAILURE;
		}

		$io->setData('backup', $backup['name']);
		$io->setData('path', substr($backup['file'], strlen(JPATH_ROOT) + 1));
		$io->setData('files', $backup['files']);
		$io->setData('size', (int) filesize($backup['file']));
		$io->success(sprintf('The %s template %s is backed up: %d files in %s.', $template['client'], $template['name'], $backup['files'],
			substr($backup['file'], strlen(JPATH_ROOT) + 1)));
		$io->text('Restore it with: template:restore ' . $template['name'] . ' ' . $backup['name']
			. ($template['client'] === 'site' ? '' : ' --client=administrator'));

		return self::SUCCESS;
	}
}
