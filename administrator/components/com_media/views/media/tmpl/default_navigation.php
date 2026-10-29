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
?>

<div class="media btn-group ventral-space">
	<a href="#" id="thumbs" onclick="MediaManager.setViewType('thumbs')" class="btn <?php echo ($style == 'thumbs') ? 'active' : ''; ?>">
	<span class="icon-grid-view-2"></span> <?php echo JText::_('COM_MEDIA_THUMBNAIL_VIEW'); ?></a>
	<a href="#" id="details" onclick="MediaManager.setViewType('details')" class="btn <?php echo ($style == 'details') ? 'active' : ''; ?>">
	<span class="icon-list-view"></span> <?php echo JText::_('COM_MEDIA_DETAIL_VIEW'); ?></a>
</div>
<?php // Thumbnail sizes (media.js keeps the chosen one for the session); shown with the thumbnails
$icon = function ($name) {
    return JHtml::_('icons.svg', $name, '', array('path' => 'media/com_media/images/icons', 'size' => 16));
};
?>
<div class="media-tile-size btn-group ventral-space" role="group" aria-label="<?php echo JText::_('COM_MEDIA_THUMBNAIL_SIZE'); ?>" data-sizes="136,150,163,180,196" data-default="163"<?php echo $style === 'thumbs' ? '' : ' hidden="hidden"'; ?>>
	<button type="button" class="btn" data-tile-size="-1" title="<?php echo JText::_('COM_MEDIA_THUMBNAIL_SMALLER'); ?>" aria-label="<?php echo JText::_('COM_MEDIA_THUMBNAIL_SMALLER'); ?>"><?php echo $icon('zoom-out'); ?></button>
	<button type="button" class="btn" data-tile-size="1" title="<?php echo JText::_('COM_MEDIA_THUMBNAIL_LARGER'); ?>" aria-label="<?php echo JText::_('COM_MEDIA_THUMBNAIL_LARGER'); ?>"><?php echo $icon('zoom-in'); ?></button>
	<button type="button" class="btn" data-tile-size="0"><?php echo JText::_('COM_MEDIA_THUMBNAIL_RESET'); ?></button>
</div>
