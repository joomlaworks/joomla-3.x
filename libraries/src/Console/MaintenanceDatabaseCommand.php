<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Checks, and optionally fixes, the core database structure, like Extensions: Database.
 *
 * @since  3.17.0
 */
class MaintenanceDatabaseCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'maintenance:database';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Maintenance Database structure';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the problems of the core database structure, as Extensions: Database does. With --fix, fixes them like that screen\'s "Fix" button.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('fix', null, self::OPTION_NONE, 'Update Database structure');
	}

	/**
	 * Read-only without --fix.
	 *
	 * @param   array|null  $options    The options of a run, or null for the command in general
	 * @param   array|null  $arguments  The arguments of a run
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isReadOnly(?array $options = null, ?array $arguments = null)
	{
		return $options !== null && empty($options['fix']);
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function hasReadOnlyRuns()
	{
		return true;
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
		$io->title('Maintenance Database');

		$problems = static::getProblems();

		if ($problems && $io->getOption('fix') && $io->isDryRun())
		{
			foreach ($problems as $problem)
			{
				$io->plan('Fix: ' . $problem, array('action' => 'fix', 'problem' => $problem));
			}
		}
		elseif ($problems && $io->getOption('fix'))
		{
			/** @var \InstallerModelDatabase $model */
			$model = $this->getAdministratorModel('com_installer', 'Database', 'InstallerModel');
			$model->fix();

			/** @var \JoomlaupdateModelDefault $update */
			$update = $this->getAdministratorModel('com_joomlaupdate', 'Default', 'JoomlaupdateModel');
			$update->purge();
			Factory::getApplication()->flushAssets();

			$fixed    = count($problems);
			$problems = static::getProblems();
			$io->text(sprintf('%d problem(s) fixed.', $fixed - count($problems)));
		}

		$io->setData('problems', $problems);

		foreach ($problems as $problem)
		{
			$io->writeln(' * ' . $problem);
		}

		if ($problems)
		{
			$io->warning(Text::_('COM_INSTALLER_MSG_DATABASE_ERRORS'));

			return $io->getOption('fix') && !$io->isDryRun() ? self::FAILURE : self::SUCCESS;
		}

		$io->success(Text::_('COM_INSTALLER_MSG_DATABASE_OK'));

		return self::SUCCESS;
	}

	/**
	 * Get the problems Extensions: Database reports for the core.
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	public static function getProblems()
	{
		Factory::getLanguage()->load('com_installer', JPATH_ADMINISTRATOR);
		\JLoader::register('JoomlaInstallerScript', JPATH_ADMINISTRATOR . '/components/com_admin/script.php');
		\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models', 'InstallerModel');

		/** @var \InstallerModelDatabase $model */
		$model     = \JModelLegacy::getInstance('Database', 'InstallerModel', array('ignore_request' => true));
		$changeSet = $model->getItems();

		if (!$changeSet)
		{
			throw new \RuntimeException('The database structure could not be checked.');
		}

		$problems      = array();
		$schemaVersion = $model->getSchemaVersion();
		$updateVersion = $model->getUpdateVersion();

		// As Extensions: Database, read the version from the manifest, which can be newer than the loaded Version class
		$manifest   = @simplexml_load_file(JPATH_ADMINISTRATOR . '/manifests/files/joomla.xml');
		$cmsVersion = $manifest !== false && (string) $manifest->version !== '' ? (string) $manifest->version : (new Version)->getShortVersion();

		if (!$model->getDefaultTextFilters())
		{
			$problems[] = Text::_('COM_INSTALLER_MSG_DATABASE_FILTER_ERROR');
		}

		if ($schemaVersion != $changeSet->getSchema())
		{
			$problems[] = Text::sprintf('COM_INSTALLER_MSG_DATABASE_SCHEMA_ERROR', $schemaVersion ?: Text::_('JNONE'), $changeSet->getSchema());
		}

		if (version_compare((string) $updateVersion, $cmsVersion) != 0)
		{
			$problems[] = Text::sprintf('COM_INSTALLER_MSG_DATABASE_UPDATEVERSION_ERROR', $updateVersion ?: Text::_('JNONE'), $cmsVersion);
		}

		foreach ($changeSet->check() as $error)
		{
			$elements   = $error->msgElements;
			$problems[] = Text::sprintf(
				'COM_INSTALLER_MSG_DATABASE_' . $error->queryType,
				basename((string) $error->file),
				isset($elements[0]) ? $elements[0] : ' ',
				isset($elements[1]) ? $elements[1] : ' ',
				isset($elements[2]) ? $elements[2] : ' '
			);
		}

		return $problems;
	}
}
