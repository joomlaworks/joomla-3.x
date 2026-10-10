<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_version
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_version
 *
 * @since  1.6
 */
abstract class ModVersionHelper
{
	/**
	 * Get the member items of the submenu.
	 *
	 * @param   \Joomla\Registry\Registry  &$params  The parameters object.
	 *
	 * @return  string  String containing the current Joomla version based on the selected format.
	 */
	public static function getVersion(&$params)
	{
		$version     = new JVersion;
		$versionText = $version->getShortVersion();
		$product     = $params->get('product', 1);

		if ($params->get('format', 'short') === 'long')
		{
			$versionText = str_replace($version::PRODUCT . ' ', '', $version->getLongVersion());
		}

		// The distribution's name, e.g. "Joomla 3.x UTD v3.17.0" (Version::PRODUCT, "Joomla!", stays for update checks)
		if (!empty($product))
		{
			$versionText = $version::DISTRIBUTION . ' v' . $versionText;
		}

		return $versionText;
	}
}
