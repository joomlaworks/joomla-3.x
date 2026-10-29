<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_multilangstatus
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// The modal below goes to the end of the page (the status bar it's in would clip it)
JFactory::getDocument()->addScriptDeclaration("
	document.addEventListener('DOMContentLoaded', function () {
		var modal = document.getElementById('multiLangModal');

		if (modal) {
			document.body.appendChild(modal);
		}
	});
");
?>

<div class="btn-group multilanguage">
	<a data-toggle="modal"
		href="#multiLangModal"
		title="<?php echo JText::_('MOD_MULTILANGSTATUS'); ?>"
		role="button">
		<span class="icon-comment" aria-hidden="true"></span><?php echo JText::_('MOD_MULTILANGSTATUS'); ?>
	</a>
	<span class="btn-group separator"></span>
</div>

<?php echo JHtml::_(
    'bootstrap.renderModal',
    'multiLangModal',
    array(
        'title'       => JText::_('MOD_MULTILANGSTATUS'),
        'url'         => JRoute::_('index.php?option=com_languages&view=multilangstatus&tmpl=component'),
        'height'      => '400px',
        'width'       => '800px',
        'bodyHeight'  => '70',
        'modalWidth'  => '80',
        'footer'      => '<button type="button" class="btn" data-dismiss="modal">'
                . JText::_('JTOOLBAR_CLOSE') . '</button>',
    )
);
