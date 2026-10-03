<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_random_image
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;
?>
<div class="random-image<?php echo $moduleclass_sfx; ?>">
<?php if ($link) : ?>
<a href="<?php echo htmlspecialchars((string) $link, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
	<?php echo JHtml::_('image', $image->folder . '/' . htmlspecialchars((string) $image->name, ENT_COMPAT, 'UTF-8'), htmlspecialchars((string) $image->name, ENT_COMPAT, 'UTF-8'), array('width' => $image->width, 'height' => $image->height)); ?>
<?php if ($link) : ?>
</a>
<?php endif; ?>
</div>
