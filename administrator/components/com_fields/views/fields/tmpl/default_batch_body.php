<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fields
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */
defined('_JEXEC') or die;

$context   = $this->escape($this->state->get('filter.context'));
?>

<div class="container-fluid">
	<div class="row-fluid">
		<div class="control-group span6">
			<div class="controls">
				<?php echo JLayoutHelper::render('joomla.html.batch.language', array()); ?>
			</div>
		</div>
		<div class="control-group span6">
			<div class="controls">
				<?php echo JLayoutHelper::render('joomla.html.batch.access', array()); ?>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="control-group span6">
			<div class="controls">
				<?php $options = array(
                    JHtml::_('select.option', 'c', JText::_('JLIB_HTML_BATCH_COPY')),
                    JHtml::_('select.option', 'm', JText::_('JLIB_HTML_BATCH_MOVE'))
                );
?>
				<label id="batch-choose-action-lbl" for="batch-choose-action"><?php echo JText::_('COM_FIELDS_BATCH_GROUP_LABEL'); ?></label>
				<div id="batch-choose-action" class="control-group">
					<select name="batch[group_id]" class="inputbox" id="batch-group-id">
						<option value=""><?php echo JText::_('JLIB_HTML_BATCH_NO_CATEGORY'); ?></option>
						<option value="nogroup"><?php echo JText::_('COM_FIELDS_BATCH_GROUP_OPTION_NONE'); ?></option>
						<?php echo JHtml::_('select.options', $this->get('Groups'), 'value', 'text'); ?>
					</select>
				</div>
				<div id="batch-copy-move" class="control-group radio" data-showon="<?php echo htmlspecialchars(json_encode(array(array('field' => 'batch[group_id]', 'values' => array(''), 'sign' => '!=', 'op' => ''))), ENT_QUOTES, 'UTF-8'); ?>">
					<?php echo JText::_('JLIB_HTML_BATCH_MOVE_QUESTION'); ?>
					<?php echo JHtml::_('select.radiolist', $options, 'batch[move_copy]', '', 'value', 'text', 'm'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
