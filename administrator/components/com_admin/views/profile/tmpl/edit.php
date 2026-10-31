<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_admin
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Load chosen.css

// Fieldsets to not automatically render by /layouts/joomla/edit/params.php
$this->ignore_fieldsets = array('user_details');
?>

<form action="<?php echo JRoute::_('index.php?option=com_admin&view=profile&layout=edit&id=' . $this->item->id); ?>" method="post" name="adminForm" data-submit-validate id="profile-form" class="form-validate form-horizontal" enctype="multipart/form-data">
	<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'account')); ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'account', JText::_('COM_ADMIN_USER_ACCOUNT_DETAILS')); ?>
	<?php foreach ($this->form->getFieldset('user_details') as $field) : ?>
		<?php if ($field->fieldname === 'password2') : ?>
			<?php // Disables autocomplete?>
			<input type="password" style="display:none">
		<?php endif; ?>
		<?php echo $field->renderField(); ?>
	<?php endforeach; ?>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php if (count($this->twofactormethods) > 1 && !empty($this->twofactorform)) : ?>
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'twofactorauth', JText::_('COM_USERS_USER_TWO_FACTOR_AUTH')); ?>
		<fieldset>
			<div class="control-group">
				<div class="control-label">
					<label id="jform_twofactor_method-lbl" for="jform_twofactor_method" class="hasTooltip"
						title="<?php echo '<strong>' . JText::_('COM_USERS_USER_FIELD_TWOFACTOR_LABEL') . '</strong><br />' . JText::_('COM_USERS_USER_FIELD_TWOFACTOR_DESC'); ?>">
						<?php echo JText::_('COM_USERS_USER_FIELD_TWOFACTOR_LABEL'); ?>
					</label>
				</div>
				<div class="controls">
					<?php echo JHtml::_('select.genericlist', $this->twofactormethods, 'jform[twofactor][method]', array(), 'value', 'text', $this->otpConfig->method, 'jform_twofactor_method', false); ?>
				</div>
			</div>
			<div id="com_admin_twofactor_forms_container">
				<?php foreach ($this->twofactorform as $form) : ?>
					<?php $style = $form['method'] == $this->otpConfig->method ? 'display: block' : 'display: none'; ?>
					<div id="com_admin_twofactor_<?php echo $form['method']; ?>" style="<?php echo $style; ?>" data-showon="<?php echo htmlspecialchars(json_encode(JFormHelper::parseShowOnConditions('method:' . $form['method'], 'jform', 'twofactor')), ENT_QUOTES, 'UTF-8'); ?>">
						<?php echo $form['form']; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<fieldset>
			<legend>
				<?php echo JText::_('COM_USERS_USER_OTEPS'); ?>
			</legend>
			<div class="alert alert-info">
				<?php echo JText::_('COM_USERS_USER_OTEPS_DESC'); ?>
			</div>
			<?php if (empty($this->otpConfig->otep)) : ?>
				<div class="alert alert-warning">
					<?php echo JText::_('COM_USERS_USER_OTEPS_WAIT_DESC'); ?>
				</div>
			<?php else : ?>
				<?php foreach ($this->otpConfig->otep as $otep) : ?>
					<span class="span3">
						<?php echo substr((string) $otep, 0, 4); ?>-<?php echo substr((string) $otep, 4, 4); ?>-<?php echo substr((string) $otep, 8, 4); ?>-<?php echo substr((string) $otep, 12, 4); ?>
					</span>
				<?php endforeach; ?>
				<div class="clearfix"></div>
			<?php endif; ?>
		</fieldset>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php endif; ?>
	<?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>
	<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
