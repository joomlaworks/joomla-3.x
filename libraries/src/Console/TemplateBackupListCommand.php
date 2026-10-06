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
 * Lists the template backups.
 *
 * @since  3.17.0
 */
class TemplateBackupListCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:backup:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List the template backups';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the backups template:backup made (of every template, or the given one), newest first.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('template', self::ARGUMENT_OPTIONAL, 'Only the backups of this template');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site, administrator or all', 'all');
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
		$client = (string) $io->getOption('client');

		if (!in_array($client, array('site', 'administrator', 'all'), true))
		{
			$io->error('--client must be site, administrator or all.');

			return self::INVALID;
		}

		$io->title('Template backups');

		$rows = array();

		foreach (static::listBackups((string) $io->getArgument('template'), $client) as $backup)
		{
			$rows[] = array(
				'backup'   => $backup['name'],
				'template' => $backup['template'],
				'client'   => $backup['client'],
				'created'  => $backup['created'],
				'size'     => $backup['size'],
			);
		}

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No template backups. template:backup makes one.');

			return self::SUCCESS;
		}

		$io->table(array('backup' => 'Backup', 'template' => 'Template', 'client' => 'Client', 'created' => 'Created (UTC)', 'size' => 'Size'), $rows);

		return self::SUCCESS;
	}
}
