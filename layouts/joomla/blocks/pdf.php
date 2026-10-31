<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];
$file = isset($data['file']) && is_string($data['file']) ? trim($data['file']) : '';

// A PDF of the site, as "Content - PDF" shows it: a path ending in .pdf, without a scheme or ".."
if (!preg_match('#^[^\x00\\\\{}<>"]+\.pdf$#i', $file) || preg_match('#^[a-z][a-z0-9+.-]*:#i', $file) || in_array('..', explode('/', $file), true)) {
    return;
}

?>
<p>{pdf <?php echo $r->e($file); ?>}</p>
