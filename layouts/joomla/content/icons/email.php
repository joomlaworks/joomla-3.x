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
		<?php echo JHtml::_('icons.svg', 'envelope', JText::_('JGLOBAL_EMAIL'), array('legacy' => 'system/emailButton.png')); ?>
	<?php else : ?>
		<span class="icon-envelope" aria-hidden="true"></span>
		<?php echo JText::_('JGLOBAL_EMAIL'); ?>
	<?php endif; ?>
<?php else : ?>
	<?php echo JText::_('JGLOBAL_EMAIL'); ?>
<?php endif; ?>
