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
 * A paragraph: data.text (inline formatting)
 *
 * Override in a template: html/layouts/joomla/blocks/paragraph.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$text = $r->inline(isset($data['text']) ? $data['text'] : '');

if (trim(strip_tags($text, '<br>')) === '')
{
	return;
}
?>
<p><?php echo $text; ?></p>
