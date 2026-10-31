<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$params = $displayData['params'];

// @deprecated  4.0  The legacy icon flag will be removed from this layout in 4.0
$legacy = $displayData['legacy'];

?>
<?php if ($params->get('show_icons')) : ?>
	<?php if ($legacy) : ?>
		<?php echo JHtml::_('icons.svg', 'file-earmark-plus', JText::_('JNEW'), array('legacy' => 'system/new.png')); ?>
	<?php else : ?>
		<span class="icon-plus" aria-hidden="true"></span>
		<?php echo JText::_('JNEW'); ?>
	<?php endif; ?>
<?php else : ?>
	<?php echo JText::_('JNEW') . '&#160;'; ?>
<?php endif; ?>
