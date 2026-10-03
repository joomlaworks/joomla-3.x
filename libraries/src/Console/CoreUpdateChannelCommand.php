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
use Joomla\CMS\Table\Table;

/**
 * Shows or changes the update channel of Joomla Update.
 *
 * @since  3.17.0
 */
class CoreUpdateChannelCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'core:update:channel';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Manage the update channel for Joomla core updates';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Without an argument, shows the update channel of Joomla Update. With one, sets it: default, next, testing or custom '
		. '(with --url, the URL of the custom update feed).';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = true;

	/**
	 * The channels in Joomla Update's options
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	const CHANNELS = array('default', 'next', 'testing', 'custom');

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('channel', self::ARGUMENT_OPTIONAL, 'Name of the update channel [' . implode(', ', self::CHANNELS) . ']');
		$this->addOption('url', null, self::OPTION_REQUIRED, 'URL to update source. Only for custom update channel');
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
		$params  = ComponentHelper::getParams('com_joomlaupdate');
		$channel = (string) $io->getArgument('channel');

		if ($channel === '')
		{
			$current = $params->get('updatesource', 'default');

			$io->setData('channel', $current);
			$io->setData('url', $current === 'custom' ? $params->get('customurl') : null);

			if ($current === 'custom')
			{
				$io->text('You are on a "custom" update channel with the URL ' . $params->get('customurl') . '.');
			}
			else
			{
				$io->text('You are on the "' . $current . '" update channel.');
			}

			return self::SUCCESS;
		}

		if (!in_array($channel, self::CHANNELS, true))
		{
			$io->error('The given update channel is invalid. Please only choose from [' . implode(', ', self::CHANNELS) . '].');

			return self::INVALID;
		}

		if ($channel === 'custom')
		{
			$url = (string) $io->getOption('url');

			if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^(https?|ftps?)://#i', $url))
			{
				$io->error('When using the custom update channel, you have to provide a valid URL with --url.');

				return self::INVALID;
			}

			$params->set('customurl', $url);
		}

		$params->set('updatesource', $channel);

		$table = Table::getInstance('Extension');
		$table->load(array('type' => 'component', 'element' => 'com_joomlaupdate'));
		$table->params = $params->toString();

		if (!$table->store())
		{
			$io->error($table->getError());

			return self::FAILURE;
		}

		/** @var \JoomlaupdateModelDefault $model */
		$model = $this->getAdministratorModel('com_joomlaupdate', 'Default', 'JoomlaupdateModel');
		$model->applyUpdateSite();

		$io->setData('channel', $channel);
		$io->setData('url', $channel === 'custom' ? $params->get('customurl') : null);

		if ($channel === 'custom')
		{
			$io->success('The update channel for this site has been set to the custom url "' . $params->get('customurl') . '".');
		}
		else
		{
			$io->success('The update channel for this site has been set to "' . $channel . '".');
		}

		return self::SUCCESS;
	}
}
