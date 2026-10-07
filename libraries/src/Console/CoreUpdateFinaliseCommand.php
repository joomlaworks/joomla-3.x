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
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;

/**
 * The last step of core:update, run in its own PHP process after the new files are in place.
 *
 * @since  3.17.0
 */
class CoreUpdateFinaliseCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'core:update:finalise';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Finalise a Joomla update (internal step of core:update)';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Applies the database changes of the new version and runs its update script, after core:update has copied the new files. '
		. 'core:update runs this itself; run it by hand only to finish an update whose files are already in place.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $hidden = true;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = true;

	/**
	 * It runs the new version's update script and database changes.
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
		return true;
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('from-version', null, self::OPTION_REQUIRED, 'The version before the update');
		$this->addOption('restore-core', null, self::OPTION_NONE, 'Install again the core extensions which were uninstalled');
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
		CoreUpdateCommand::addLogger();

		$app        = Factory::getApplication();
		$oldVersion = (string) $io->getOption('from-version');

		/** @var \JoomlaupdateModelDefault $model */
		$model = $this->getAdministratorModel('com_joomlaupdate', 'Default', 'JoomlaupdateModel');

		$app->setUserState('com_joomlaupdate.restorecore', (bool) $io->getOption('restore-core'));

		if (!$model->finaliseUpgrade())
		{
			$io->error('The update could not be finalised.');

			return self::FAILURE;
		}

		// What the update script printed, e.g. files it couldn't delete
		$message = trim(strip_tags(str_replace(array('<br />', '<br/>', '<br>'), "\n", (string) \JInstaller::getInstance()->get('extension_message'))));

		foreach (array_filter(array_map('trim', explode("\n", $message))) as $line)
		{
			$io->warning($line);
		}

		Log::add(Text::_('COM_JOOMLAUPDATE_UPDATE_LOG_CLEANUP'), Log::INFO, 'Update');

		if (is_file(JPATH_ROOT . '/joomla.xml'))
		{
			@unlink(JPATH_ROOT . '/joomla.xml');
		}

		$model->purge();

		try
		{
			PluginHelper::importPlugin('actionlog');
			$app->triggerEvent('onJoomlaAfterUpdate', array($oldVersion));
		}
		catch (\Exception $e)
		{
			$io->warning('The update could not be added to the User Actions Log: ' . $e->getMessage());
		}

		$version = (new Version)->getShortVersion();

		Log::add(Text::sprintf('COM_JOOMLAUPDATE_UPDATE_LOG_COMPLETE', $version), Log::INFO, 'Update');

		$io->setData('version', $version);
		$io->success('The update was finalised.');

		return self::SUCCESS;
	}
}
