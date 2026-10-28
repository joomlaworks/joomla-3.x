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
$legacy = $displayData['legacy'];

?>
<?php if ($params->get('show_icons')) : ?>
	<?php if ($legacy) : ?>
		<?php // A template's own system/printButton.png is used when it has one ?>
		<?php echo JHtml::_('icons.svg', 'printer', JText::_('JGLOBAL_PRINT'), array('legacy' => 'system/printButton.png')); ?>
	<?php else : ?>
		<span class="icon-print" aria-hidden="true"></span>
		<?php echo JText::_('JGLOBAL_PRINT'); ?>
	<?php endif; ?>
<?php else : ?>
	<?php echo JText::_('JGLOBAL_PRINT'); ?>
<?php endif; ?>
