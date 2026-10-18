<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_BASE') or die;

/**
 * A template's block whose layout no installed template has (its template was removed): what was entered, written plainly
 * (texts as paragraphs, images, links, and each entry of a group in turn), so the content doesn't lose it.
 *
 * Override in a template: html/layouts/joomla/blocks/template.php. $displayData: template, name (the block), data (its fields),
 * block, renderer (Joomla\CMS\Blocks\BlockRenderer).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r = $displayData['renderer'];

$write = function ($fields, $depth) use (&$write, $r)
{
	$html = '';

	foreach ((array) $fields as $value)
	{
		if (is_array($value) && isset($value['url']))
		{
			$html .= '<p>' . $r->image($value) . '</p>';
		}
		elseif (is_array($value) && $depth < 2)
		{
			foreach ($value as $entry)
			{
				$html .= is_array($entry) ? '<div class="block-template-entry">' . $write($entry, $depth + 1) . '</div>' : '';
			}
		}
		elseif (is_string($value) && trim($value) !== '')
		{
			$url = $r->url($value);

			$html .= preg_match('#^(https?://|/|index\.php)#', $value) && $url !== '' && !preg_match('/\s/', $value)
				? '<p><a href="' . $r->e($url) . '">' . $r->e($value) . '</a></p>'
				: '<p>' . $r->inline($value) . '</p>';
		}
	}

	return $html;
};

$html = $write(isset($displayData['data']) ? $displayData['data'] : array(), 0);

if ($html === '')
{
	return;
}
?>
<div class="block-template block-template-<?php echo $r->e($displayData['template'] . '-' . $displayData['name']); ?>"><?php echo $html; ?></div>
