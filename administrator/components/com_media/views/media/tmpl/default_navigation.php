<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;
$app   = JFactory::getApplication();
$style = $app->getUserStateFromRequest('media.list.layout', 'layout', 'thumbs', 'word');
$icon = function ($name) {
    return JHtml::_('icons.svg', $name, '', array('path' => 'media/com_media/images/icons', 'size' => 16));
};
$thumbs = $style === 'thumbs' ? '' : ' hidden="hidden"';
?>

<?php // One row at the right: selecting all, the thumbnails' sizes (kept for the session), the views; media.js drives them?>
<div class="media-bar ventral-space">
	<label class="checkbox btn media-check-all" hidden="hidden">
		<input type="checkbox" id="media-check-all" /> <?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>
	</label>
	<div class="media-tile-size btn-group" role="group" aria-label="<?php echo JText::_('COM_MEDIA_THUMBNAIL_SIZE'); ?>" data-sizes="136,150,163,180,196" data-default="163"<?php echo $thumbs; ?>>
		<button type="button" class="btn" data-tile-size="-1" title="<?php echo JText::_('COM_MEDIA_THUMBNAIL_SMALLER'); ?>" aria-label="<?php echo JText::_('COM_MEDIA_THUMBNAIL_SMALLER'); ?>"><?php echo $icon('zoom-out'); ?></button>
		<button type="button" class="btn" data-tile-size="1" title="<?php echo JText::_('COM_MEDIA_THUMBNAIL_LARGER'); ?>" aria-label="<?php echo JText::_('COM_MEDIA_THUMBNAIL_LARGER'); ?>"><?php echo $icon('zoom-in'); ?></button>
		<button type="button" class="btn" data-tile-size="0"><?php echo JText::_('COM_MEDIA_THUMBNAIL_RESET'); ?></button>
	</div>
	<div class="media btn-group">
		<a href="#" id="thumbs" onclick="MediaManager.setViewType('thumbs')" class="btn <?php echo ($style == 'thumbs') ? 'active' : ''; ?>">
		<span class="icon-grid-view-2"></span> <?php echo JText::_('COM_MEDIA_THUMBNAIL_VIEW'); ?></a>
		<a href="#" id="details" onclick="MediaManager.setViewType('details')" class="btn <?php echo ($style == 'details') ? 'active' : ''; ?>">
		<span class="icon-list-view"></span> <?php echo JText::_('COM_MEDIA_DETAIL_VIEW'); ?></a>
	</div>
</div>
