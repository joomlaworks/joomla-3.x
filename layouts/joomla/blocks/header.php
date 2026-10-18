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
 * A heading: data.text, data.level (1-6)
 *
 * Override in a template: html/layouts/joomla/blocks/header.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$level = isset($data['level']) ? max(1, min(6, (int) $data['level'])) : 2;
$text  = $r->inline(isset($data['text']) ? $data['text'] : '');

if (trim(strip_tags($text)) === '')
{
	return;
}
?>
<h<?php echo $level; ?>><?php echo $text; ?></h<?php echo $level; ?>>
