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

if (!function_exists('protostarDropConditionalAssets'))
{
	/**
	 * No legacy markup: leave out the scripts and stylesheets for old Internet Explorer versions (conditional comments, e.g. the
	 * HTML5 fallback and polyfills of forms, or the keepalive's event polyfill). Done when the head is built, so assets added after
	 * the page file runs are covered too.
	 *
	 * @param   JDocument  $document  The page
	 *
	 * @return  void
	 */
	function protostarDropConditionalAssets($document)
	{
		JEventDispatcher::getInstance()->register('onBeforeCompileHead', function () use ($document)
		{
			if (!$document instanceof JDocumentHtml)
			{
				return;
			}

			foreach (array('_scripts', '_styleSheets') as $list)
			{
				foreach ($document->$list as $url => $asset)
				{
					if (!empty($asset['options']['conditional']) || !empty($asset['conditional']))
					{
						unset($document->{$list}[$url]);
					}
				}
			}
		});
	}
}
