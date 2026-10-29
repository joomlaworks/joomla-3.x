<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The item pickers' field (Modal_Article, Modal_Category, Modal_Contact, Modal_Menu, Modal_Newsfeed): the chosen item's
 * title and buttons that open the list, or the item's form, in the CMS's modal (global.js: CMS.modalField). The ids
 * (<id>_name, <id>_select, <id>_new, <id>_edit, <id>_clear, <id>_propagate, <id>_id) are those of the old markup.
 *
 * @var  array  $displayData  id, name, value, required, title (the chosen item's, escaped), empty (the text without one),
 *                            function (what the list calls: jSelectArticle_<id>), urls (select, new, edit: {id} is
 *                            replaced with the value), titles and tips (select, new, edit), allow (select, new, edit,
 *                            clear, propagate), propagate (the callbacks' prefix), task (article), form (item-form),
 *                            idField and titleField (the form's fields with the saved item's id and title)
 */

extract($displayData);

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8');
};

// Texts the script writes as text: language strings may hold entities (Save &amp; Close)
$text = function ($value) {
    return html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
};

JHtml::_('behavior.cms');

if (!empty($allow['propagate'])) {
    JText::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');
}

$attributes = array(
    'data-modal-field' => $id,
    'data-function'    => $function,
    'data-task'        => $task,
    'data-form'        => $form,
    'data-id-field'    => $idField,
    'data-title-field' => $titleField,
    'data-propagate'   => isset($propagate) ? $propagate : '',
    'data-save'        => $text(JText::_('JSAVE')),
    'data-apply'       => $text(JText::_('JAPPLY')),
    'data-close'       => $text(JText::_('JLIB_HTML_BEHAVIOR_CLOSE')),
);

foreach (array('select', 'new', 'edit') as $action) {
    if (!empty($allow[$action])) {
        $attributes['data-url-' . $action]   = $urls[$action];
        $attributes['data-title-' . $action] = $text($titles[$action]);
    }
}

$buttons = array(
    'select' => array('icon-file', 'JSELECT', !$value),
    'new'    => array('icon-new', 'JACTION_CREATE', !$value),
    'edit'   => array('icon-edit', 'JACTION_EDIT', (bool) $value),
);

?>
<span class="input-append field-modal"<?php foreach ($attributes as $attribute => $content) : ?> <?php echo $attribute; ?>="<?php echo $escape($content); ?>"<?php endforeach; ?>>
	<input class="input-medium" id="<?php echo $id; ?>_name" type="text" value="<?php echo $title; ?>" disabled="disabled" size="35" />
	<?php foreach ($buttons as $action => $button) : ?>
		<?php if (!empty($allow[$action])) : ?>
			<button type="button" class="btn hasTooltip<?php echo $button[2] ? '' : ' hidden'; ?>" id="<?php echo $id . '_' . $action; ?>" data-modal-action="<?php echo $action; ?>" title="<?php echo JHtml::tooltipText($tips[$action]); ?>"><span class="<?php echo $button[0]; ?>" aria-hidden="true"></span> <?php echo JText::_($button[1]); ?></button>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php if (!empty($allow['clear'])) : ?>
		<button type="button" class="btn<?php echo $value ? '' : ' hidden'; ?>" id="<?php echo $id; ?>_clear" data-modal-action="clear"><span class="icon-remove" aria-hidden="true"></span><?php echo JText::_('JCLEAR'); ?></button>
	<?php endif; ?>
	<?php if (!empty($allow['propagate'])) : ?>
		<a class="btn hasTooltip<?php echo $value ? '' : ' hidden'; ?>" id="<?php echo $id; ?>_propagate" href="#" data-modal-action="propagate" title="<?php echo JHtml::tooltipText('JGLOBAL_ASSOCIATIONS_PROPAGATE_TIP'); ?>"><span class="icon-refresh" aria-hidden="true"></span><?php echo JText::_('JGLOBAL_ASSOCIATIONS_PROPAGATE_BUTTON'); ?></a>
	<?php endif; ?>
</span>
<input type="hidden" id="<?php echo $id; ?>_id"<?php echo $required ? ' class="required modal-value"' : ''; ?> data-required="<?php echo (int) $required; ?>" name="<?php echo $name; ?>" data-text="<?php echo $escape($empty); ?>" value="<?php echo $value; ?>" />
