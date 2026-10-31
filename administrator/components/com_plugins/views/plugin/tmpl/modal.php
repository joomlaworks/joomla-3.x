<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_plugins
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// This code is needed for proper check out in case of modal close
JFactory::getDocument()->addScriptDeclaration('
	(function (parent) {
		var modal = parent.document.getElementById("plugin' . (int) $this->item->extension_id . 'Modal");
		var close = function () {
			var button = document.getElementById("closeBtn");

			if (button) {
				button.click();
			}
		};

		if (modal && parent.jQuery) {
			parent.jQuery(modal).one("hidden", close);
		} else if (modal) {
			modal.addEventListener("cms:modal-hidden", close, {once: true});
		}
	})(window.parent);
');
?>
<button id="applyBtn" type="button" class="hidden" onclick="Joomla.submitbutton('plugin.apply');"></button>
<button id="saveBtn" type="button" class="hidden" onclick="Joomla.submitbutton('plugin.save');"></button>
<button id="closeBtn" type="button" class="hidden" onclick="Joomla.submitbutton('plugin.cancel');"></button>

<div class="container-popup">
	<?php $this->setLayout('edit'); ?>
	<?php echo $this->loadTemplate(); ?>
</div>
