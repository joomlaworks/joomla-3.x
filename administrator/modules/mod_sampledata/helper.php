<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_sampledata
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_sampledata
 *
 * @since  3.8.0
 */
abstract class ModSampledataHelper
{
	/**
	 * Get a list of sampledata.
	 *
	 * @return  mixed  An array of sampledata, or false on error.
	 *
	 * @since  3.8.0
	 */
	public static function getList()
	{
		JPluginHelper::importPlugin('sampledata');
		$dispatcher = JEventDispatcher::getInstance();
		$items = array();

		// A plugin gives one set, or several (the Sample Data plugin: News and Blog)
		foreach ($dispatcher->trigger('onSampledataGetOverview', array('test', 'foo')) as $result)
		{
			foreach (is_array($result) ? $result : array($result) as $item)
			{
				if (is_object($item) && !empty($item->name))
				{
					$items[] = $item;
				}
			}
		}

		return $items;
	}
}
