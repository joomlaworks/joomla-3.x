<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;
?>

<form action="<?php echo JRoute::_('index.php?option=com_users&view=mail'); ?>" name="adminForm" data-submit-validate method="post" id="adminForm" class="form-validate">
	<div class="row-fluid">
		<div class="span9">
			<fieldset class="adminform">
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('subject'); ?></div>
					<div class="controls"><?php echo JComponentHelper::getParams('com_users')->get('mailSubjectPrefix'); ?>
						<?php echo $this->form->getInput('subject'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('message'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('message'); ?><br>
						<?php echo JComponentHelper::getParams('com_users')->get('mailBodySuffix'); ?></div>
				</div>
			</fieldset>
			<input type="hidden" name="task" value="" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
		<div class="span3">
			<fieldset class="form-inline">
				<div class="control-group checkbox">
					<div class="controls"><?php echo $this->form->getInput('recurse'); ?> <?php echo $this->form->getLabel('recurse'); ?></div>
				</div>
				<div class="control-group checkbox">
					<div class="control-label"><?php echo $this->form->getInput('mode'); ?> <?php echo $this->form->getLabel('mode'); ?></div>
				</div>
				<div class="control-group checkbox">
					<div class="control-label"><?php echo $this->form->getInput('disabled'); ?> <?php echo $this->form->getLabel('disabled'); ?></div>
				</div>
				<div class="control-group checkbox">
					<div class="control-label"><?php echo $this->form->getInput('bcc'); ?> <?php echo $this->form->getLabel('bcc'); ?></div>
				</div>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('group'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('group'); ?></div>
				</div>
			</fieldset>
		</div>
	</div>
</form>
