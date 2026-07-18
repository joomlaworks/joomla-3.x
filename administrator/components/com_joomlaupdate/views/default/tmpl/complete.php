<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_joomlaupdate
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;
?>

<fieldset>
	<legend>
		<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_COMPLETE_HEADING'); ?>
	</legend>
	<p class="alert alert-success">
		<?php
		$newVersion = JFactory::getApplication()->getUserState('com_joomlaupdate.newversion', JVERSION);
		JFactory::getApplication()->setUserState('com_joomlaupdate.newversion', null);
		echo JText::sprintf('COM_JOOMLAUPDATE_VIEW_COMPLETE_MESSAGE', $newVersion);
		?>
	</p>
</fieldset>
<form action="<?php echo JRoute::_('index.php?option=com_joomlaupdate'); ?>" method="post" id="adminForm">
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
