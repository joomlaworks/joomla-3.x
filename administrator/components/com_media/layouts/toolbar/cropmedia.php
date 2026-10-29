<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Enabled by media.js while one image of a type that can be cropped is selected in the list
$title = JText::_('COM_MEDIA_CROP');
?>
<button type="button" id="media-crop" class="btn btn-small" disabled="disabled" title="<?php echo $this->escape(JText::_('COM_MEDIA_CROP_TIP')); ?>">
	<span class="icon-scissors" aria-hidden="true"></span> <?php echo $title; ?>
</button>
