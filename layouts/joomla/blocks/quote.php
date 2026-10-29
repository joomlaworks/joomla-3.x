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
 * A quote: data.text, data.caption (who said it), data.alignment (left, center)
 *
 * Override in a template: html/layouts/joomla/blocks/quote.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$text    = $r->inline(isset($data['text']) ? $data['text'] : '');
$caption = $r->inline(isset($data['caption']) ? $data['caption'] : '');
$center  = isset($data['alignment']) && $data['alignment'] === 'center';

if (trim(strip_tags($text)) === '') {
    return;
}
?>
<blockquote class="block-quote<?php echo $center ? ' is-centered' : ''; ?>"><p><?php echo $text; ?></p><?php echo $caption !== '' ? '<cite>' . $caption . '</cite>' : ''; ?></blockquote>
