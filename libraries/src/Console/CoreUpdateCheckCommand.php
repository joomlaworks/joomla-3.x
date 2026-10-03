<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Version;

/**
 * Checks for a new Joomla version on the configured update channel.
 *
 * @since  3.17.0
 */
class CoreUpdateCheckCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'core:update:check';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Check for Joomla updates';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Fetches the update feed of the configured update channel and reports whether a newer Joomla version is available.';

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		/** @var \JoomlaupdateModelDefault $model */
		$model = $this->getAdministratorModel('com_joomlaupdate', 'Default', 'JoomlaupdateModel');
		$model->applyUpdateSite();
		$model->purge();
		$model->refreshUpdates(true);

		$data    = $model->getUpdateInformation();
		$params  = ComponentHelper::getParams('com_joomlaupdate');
		$channel = $params->get('updatesource', 'default');
		$current = (new Version)->getShortVersion();

		$io->title('Joomla! Update Status');

		if ($channel === 'custom')
		{
			$io->text('You are on a custom update channel with the URL ' . $params->get('customurl') . '.');
		}
		else
		{
			$io->text('You are on the ' . $channel . ' update channel.');
		}

		$io->text('Your current Joomla version is ' . $current . '.');

		$downloadUrl = null;

		if ($data['hasUpdate'] && is_object($data['object']) && isset($data['object']->downloadurl->_data))
		{
			$downloadUrl = $data['object']->downloadurl->_data;
		}

		$io->setData('channel', $channel);
		$io->setData('installed', $current);
		$io->setData('latest', $data['latest']);
		$io->setData('hasUpdate', (bool) $data['hasUpdate']);
		$io->setData('downloadUrl', $downloadUrl);

		if (!$data['hasUpdate'])
		{
			$io->success('You already have the latest Joomla version ' . $data['latest'] . '.');

			return self::SUCCESS;
		}

		$io->warning('New Joomla version ' . $data['latest'] . ' is available.');

		if ($downloadUrl === null)
		{
			$io->warning('We cannot find an update URL.');
		}

		return self::SUCCESS;
	}
}
