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
 * A table: data.content (rows of cells, inline formatting), data.withHeadings (the first row is a heading row)
 *
 * Override in a template: html/layouts/joomla/blocks/table.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$rows = isset($data['content']) && is_array($data['content']) ? array_values($data['content']) : array();

if (!$rows)
{
	return;
}

$headings = !empty($data['withHeadings']);
$html     = '<table class="block-table">';

foreach ($rows as $i => $row)
{
	$cell = $headings && $i === 0 ? 'th' : 'td';

	if ($headings && $i === 0)
	{
		$html .= '<thead>';
	}
	elseif ($i === ($headings ? 1 : 0))
	{
		$html .= '<tbody>';
	}

	$html .= '<tr>';

	foreach ((array) $row as $value)
	{
		$html .= '<' . $cell . '>' . $r->inline($value) . '</' . $cell . '>';
	}

	$html .= '</tr>' . ($headings && $i === 0 ? '</thead>' : '');
}

echo $html . (count($rows) > ($headings ? 1 : 0) ? '</tbody>' : '') . '</table>';
