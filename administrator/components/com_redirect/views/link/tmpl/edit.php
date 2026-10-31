<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('stylesheet', 'administrator/components/com_redirect/css/redirect.css', array('version' => 'auto'));

?>

<form action="<?php echo JRoute::_('index.php?option=com_redirect&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" data-submit-validate id="link-form" class="form-validate form-horizontal">
	<div class="row-fluid">
		<div class="span9">
			<fieldset class="adminform">
				<?php echo $this->form->renderField('old_url'); ?>
				<?php echo $this->form->renderField('target_type'); ?>
				<?php echo $this->form->renderField('new_url'); ?>
				<?php echo $this->form->renderField('new_url_external'); ?>
				<?php echo $this->form->renderField('header'); ?>
				<?php echo $this->form->renderField('comment'); ?>
			</fieldset>
		</div>
		<div class="span3">
			<fieldset class="form-vertical">
				<?php echo $this->form->renderField('published'); ?>
				<?php if ($this->item->id) : ?>
					<?php echo $this->form->renderField('hits'); ?>
					<?php echo $this->form->renderField('created_date'); ?>
					<?php echo $this->form->renderField('modified_date'); ?>
					<?php echo $this->form->renderField('id'); ?>
				<?php endif; ?>
			</fieldset>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
