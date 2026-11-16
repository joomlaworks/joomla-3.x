<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$input   = JFactory::getApplication()->input;
$fieldId = (string) $input->getCmd('fieldid', '');
$isMoo   = $input->getInt('ismoo', 1);
$editor  = $fieldId === '' && $this->pick === 'image';
$onClick = '';

// The field's old modal (SqueezeBox) and the editor buttons' insert from here; the image field's own modal listens to the buttons
if ($fieldId !== '') {
    if ($isMoo) {
        $onClick = "window.parent.jInsertFieldValue(document.getElementById('f_url').value, '" . $fieldId . "');window.parent.jModalClose();window.parent.CMS.bootstrap.call('modal', '.modal.in', 'hide');";
    }
} else {
    $onClick = 'ImageManager.onok();window.parent.jModalClose();';
}
?>
<div class="media-pick-panel">
	<div class="media-pick-row">
		<label class="media-pick-field media-pick-url" for="f_url">
			<span><?php echo JText::_($this->pick === 'pdf' ? 'COM_MEDIA_PDF_URL' : 'COM_MEDIA_IMAGE_URL'); ?></span>
			<input type="text" id="f_url" value="" />
		</label>
		<div class="media-pick-buttons">
			<button class="btn btn-success button-save-selected" type="button"<?php echo $onClick ? ' onclick="' . $onClick . '"' : ''; ?> data-dismiss="modal"><?php echo JText::_('COM_MEDIA_INSERT'); ?></button>
			<button class="btn button-cancel" type="button" onclick="window.parent.CMS.bootstrap.call('modal', '.modal.in', 'hide');<?php echo $onClick ? 'window.parent.jModalClose();' : ''; ?>" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
		</div>
	</div>
	<?php if ($editor) : ?>
	<div class="media-pick-row media-pick-details">
		<label class="media-pick-field" for="f_alt">
			<span><?php echo JText::_('COM_MEDIA_IMAGE_DESCRIPTION'); ?></span>
			<input type="text" id="f_alt" value="" />
		</label>
		<label class="media-pick-field" for="f_title">
			<span><?php echo JText::_('COM_MEDIA_TITLE'); ?></span>
			<input type="text" id="f_title" value="" />
		</label>
		<label class="media-pick-field" for="f_caption">
			<span><?php echo JText::_('COM_MEDIA_CAPTION'); ?></span>
			<input type="text" id="f_caption" value="" />
		</label>
		<label class="media-pick-field" for="f_caption_class" title="<?php echo JText::_('COM_MEDIA_CAPTION_CLASS_DESC'); ?>">
			<span><?php echo JText::_('COM_MEDIA_CAPTION_CLASS_LABEL'); ?></span>
			<input type="text" list="d_caption_class" id="f_caption_class" value="" />
			<datalist id="d_caption_class">
				<option value="text-left"></option>
				<option value="text-center"></option>
				<option value="text-right"></option>
			</datalist>
		</label>
		<label class="media-pick-field media-pick-align" for="f_align" title="<?php echo JText::_('COM_MEDIA_ALIGN_DESC'); ?>">
			<span><?php echo JText::_('COM_MEDIA_ALIGN'); ?></span>
			<select id="f_align" data-searchable="false">
				<option value="" selected="selected"><?php echo JText::_('COM_MEDIA_NOT_SET'); ?></option>
				<option value="left"><?php echo JText::_('JGLOBAL_LEFT'); ?></option>
				<option value="center"><?php echo JText::_('JGLOBAL_CENTER'); ?></option>
				<option value="right"><?php echo JText::_('JGLOBAL_RIGHT'); ?></option>
			</select>
		</label>
	</div>
	<?php endif; ?>
</div>
