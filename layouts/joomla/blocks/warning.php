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
 * A note to the reader: data.title, data.message
 *
 * Override in a template: html/layouts/joomla/blocks/warning.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$title   = $r->inline(isset($data['title']) ? $data['title'] : '');
$message = $r->inline(isset($data['message']) ? $data['message'] : '');

if (trim(strip_tags($title . $message)) === '') {
    return;
}
?>
<div class="alert alert-warning block-warning" role="note"><?php echo $title !== '' ? '<strong>' . $title . '</strong> ' : ''; ?><?php echo $message; ?></div>
