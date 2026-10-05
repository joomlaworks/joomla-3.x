<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var  string  $displayData  The editor's id */
$id = $displayData;

?>
<div class="toggle-editor btn-toolbar pull-right clearfix">
	<div class="btn-group">
		<button type="button" class="btn js-tinymce-latest-toggle" data-editor="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
			title="<?php echo JText::_('PLG_TINYMCE_LATEST_BUTTON_TOGGLE_EDITOR'); ?>">
			<span class="icon-eye" aria-hidden="true"></span> <?php echo JText::_('PLG_TINYMCE_LATEST_BUTTON_TOGGLE_EDITOR'); ?>
		</button>
	</div>
</div>
