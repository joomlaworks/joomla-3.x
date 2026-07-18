<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */
defined('_JEXEC') or die;

/**
 * Layout variables
 * -----------------
 * @var   array  $displayData  Array with all the given attributes for the image element.
 *                             Eg: src, class, alt, width, height, loading, decoding, style, data-*
 *                             All attribute values are escaped before output (CVE-2026-48953).
 */

if (isset($displayData['alt']) && $displayData['alt'] === false)
{
	unset($displayData['alt']);
}

foreach ($displayData as $attribute => $value)
{
	if (!is_array($value))
	{
		$displayData[$attribute] = $this->escape($value);
	}
}

echo '<img ' . JArrayHelper::toString($displayData) . '>';
