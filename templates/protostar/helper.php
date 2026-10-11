<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

if (!function_exists('protostarOptionColors'))
{
	/**
	 * The Template Colour option, and Background Colour with it: as custom properties for css/template-options.css, which applies
	 * them after the site's user.css. Values which could end the CSS they go into are ignored.
	 *
	 * @param   Joomla\Registry\Registry  $params  The template style's options
	 *
	 * @return  array  'properties' (the declarations for :root, empty without a Template Colour) and 'classes' (for <body>)
	 */
	function protostarOptionColors($params)
	{
		$properties = '';
		$classes    = '';
		$safe       = function ($color)
		{
			$color = trim((string) $color);

			return $color !== '' && preg_match('/^[#\w(),.%\s-]+$/', $color) ? $color : '';
		};

		if ($template = $safe($params->get('templateColor')))
		{
			$properties = '--protostar-template-color:' . $template . ';';
			$classes    = ' protostar-template-color';

			if ($background = $safe($params->get('templateBackgroundColor')))
			{
				$properties .= '--protostar-background-color:' . $background . ';';
				$classes    .= ' protostar-background-color';
			}
		}

		return array('properties' => $properties, 'classes' => $classes);
	}
}
