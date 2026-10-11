<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.isis
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

if (!function_exists('isisOptionColors'))
{
	/**
	 * The template options' colours (Template, Header, Sidebar and Link Colour): those set, as custom properties for
	 * css/template-options.css, which applies them after the site's custom.css. Values which could end the CSS they go into are
	 * ignored.
	 *
	 * @param   array  $colors  Option (navbar, header, sidebar, link) => colour
	 *
	 * @return  array  'properties' (the declarations for :root, empty when no option is set) and 'classes' (for <body>, one per option)
	 */
	function isisOptionColors(array $colors)
	{
		$properties = '';
		$classes    = '';

		foreach ($colors as $name => $color)
		{
			$color = trim((string) $color);

			if ($color !== '' && preg_match('/^[#\w(),.%\s-]+$/', $color))
			{
				$properties .= '--isis-' . $name . '-color:' . $color . ';';
				$classes    .= ' isis-' . $name . '-color';
			}
		}

		return array('properties' => $properties, 'classes' => $classes);
	}
}
