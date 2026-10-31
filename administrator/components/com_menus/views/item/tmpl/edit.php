<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.core');
JHtml::_('behavior.tabstate');
JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', '#jform_request_filter_tag', null, array('placeholder_text_multiple' => JText::_('JGLOBAL_TYPE_OR_SELECT_SOME_TAGS')));
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

JText::script('ERROR');
JText::script('JGLOBAL_VALIDATION_FORM_FAILED');

$assoc = JLanguageAssociations::isEnabled();

// The form's script (js/menus.js): parent items by menu, the type picker, the Login Form's redirects
JHtml::_('script', 'administrator/components/com_menus/js/menus.min.js', array('version' => 'auto'), array('defer' => true));

$input = JFactory::getApplication()->input;

// In case of modal
$isModal  = $input->get('layout') == 'modal' ? true : false;
$layout   = $isModal ? 'modal' : 'edit';
$tmpl     = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
$clientId = $this->state->get('item.client_id', 0);
$lang     = JFactory::getLanguage()->getTag();

// Load mod_menu.ini file when client is administrator
if ($clientId === 1) {
    JFactory::getLanguage()->load('mod_menu', JPATH_ADMINISTRATOR, null, false, true);
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_menus&view=item&client_id=' . $clientId . '&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" data-submit-validate id="item-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<?php // Add the translation of the menu item title when client is administrator?>
	<?php if ($clientId === 1 && $this->item->id != 0) : ?>
		<div class="form-inline form-inline-header">
			<div class="control-group">
				<div class="control-label">
					<label><?php echo JText::sprintf('COM_MENUS_TITLE_TRANSLATION', $lang); ?></label>
				</div>
				<div class="controls">
					<input class="input-xlarge" value="<?php echo JText::_($this->item->title); ?>" readonly="readonly" type="text">
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_MENUS_ITEM_DETAILS')); ?>
		<div class="row-fluid">
			<div class="span9">
				<?php
                echo $this->form->renderField('type');

if ($this->item->type == 'alias') {
    echo $this->form->renderField('aliasoptions', 'params');
}

if ($this->item->type == 'separator') {
    echo $this->form->renderField('text_separator', 'params');
}

echo $this->form->renderFieldset('request');

if ($this->item->type == 'url') {
    $this->form->setFieldAttribute('link', 'readonly', 'false');
    $this->form->setFieldAttribute('link', 'required', 'true');
}

echo $this->form->renderField('link');

if ($this->item->type == 'alias') {
    echo $this->form->renderField('alias_redirect', 'params');
}

echo $this->form->renderField('browserNav');
echo $this->form->renderField('template_style_id');

if (!$isModal && $this->item->type == 'container') {
    echo $this->loadTemplate('container');
}
?>
			</div>
			<div class="span3">
				<?php
// Set main fields.
$this->fields = array(
    'id',
    'client_id',
    'menutype',
    'parent_id',
    'menuordering',
    'published',
    'home',
    'access',
    'language',
    'note',
);

if ($this->item->type != 'component') {
    $this->fields = array_diff($this->fields, array('home'));
}

echo JLayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php
        $this->fieldsets = array();
$this->ignore_fieldsets = array('aliasoptions', 'request', 'item_associations');
echo JLayoutHelper::render('joomla.edit.params', $this);
?>

		<?php if (!$isModal && $assoc && $this->state->get('item.client_id') != 1) : ?>
			<?php if ($this->item->type !== 'alias' && $this->item->type !== 'url'
        && $this->item->type !== 'separator' && $this->item->type !== 'heading') : ?>
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'associations', JText::_('JGLOBAL_FIELDSET_ASSOCIATIONS')); ?>
				<?php echo $this->loadTemplate('associations'); ?>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
			<?php endif; ?>
		<?php elseif ($isModal && $assoc && $this->state->get('item.client_id') != 1) : ?>
			<div class="hidden"><?php echo $this->loadTemplate('associations'); ?></div>
		<?php endif; ?>

		<?php if (!empty($this->modules)) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'modules', JText::_('COM_MENUS_ITEM_MODULE_ASSIGNMENT')); ?>
			<?php echo $this->loadTemplate('modules'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>

		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="forcedLanguage" value="<?php echo $input->get('forcedLanguage', '', 'cmd'); ?>" />
	<?php echo $this->form->getInput('component_id'); ?>
	<?php echo JHtml::_('form.token'); ?>
	<input type="hidden" id="fieldtype" name="fieldtype" value="" />
</form>
