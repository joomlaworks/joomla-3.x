<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', '#jform_catid', null, array('disable_search_threshold' => 0 ));
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_banners&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" data-submit-validate id="banner-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_BANNERS_BANNER_DETAILS')); ?>
		<div class="row-fluid">
			<div class="span9">
				<?php echo $this->form->renderField('type'); ?>
				<div id="image" data-showon="<?php echo htmlspecialchars(json_encode(JFormHelper::parseShowOnConditions('type:0', 'jform')), ENT_QUOTES, 'UTF-8'); ?>">
					<?php echo $this->form->renderFieldset('image'); ?>
				</div>
				<div id="custom" data-showon="<?php echo htmlspecialchars(json_encode(JFormHelper::parseShowOnConditions('type:1', 'jform')), ENT_QUOTES, 'UTF-8'); ?>">
					<?php echo $this->form->renderField('custombannercode'); ?>
				</div>
				<?php
                echo $this->form->renderField('clickurl');
echo $this->form->renderField('description');
?>
			</div>
			<div class="span3">
				<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'otherparams', JText::_('COM_BANNERS_GROUP_LABEL_BANNER_DETAILS')); ?>
		<?php echo $this->form->renderFieldset('otherparams'); ?>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span6">
				<?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
			</div>
			<div class="span6">
				<?php echo $this->form->renderFieldset('metadata'); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
