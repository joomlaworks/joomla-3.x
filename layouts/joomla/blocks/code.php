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
 * Code, shown as text: data.code
 *
 * Override in a template: html/layouts/joomla/blocks/code.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$code = isset($data['code']) ? (string) $data['code'] : '';

if (trim($code) === '') {
    return;
}
?>
<pre class="block-code"><code><?php echo $r->e($code); ?></code></pre>
