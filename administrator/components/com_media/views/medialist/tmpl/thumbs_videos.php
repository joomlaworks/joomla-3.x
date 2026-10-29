<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

$params     = new Registry();
$dispatcher = JEventDispatcher::getInstance();
?>
<?php foreach ($this->videos as $i => $video) : ?>
	<?php $dispatcher->trigger('onContentBeforeDisplay', array('com_media.file', &$video, &$params, 0)); ?>
	<?php $cover = $video->cover ? $this->escape(COM_MEDIA_BASEURL . '/' . str_replace('%2F', '/', rawurlencode((string) $video->cover))) : ''; ?>
	<li class="imgOutline thumbnail height-80 width-80 center">
		<?php if ($this->canDelete) : ?>
			<a class="close delete-item" target="_top" href="index.php?option=com_media&amp;task=file.delete&amp;tmpl=index&amp;<?php echo JSession::getFormToken(); ?>=1&amp;folder=<?php echo rawurlencode((string) $this->state->folder); ?>&amp;rm[]=<?php echo $this->escape($video->name); ?>" rel="<?php echo $this->escape($video->name); ?>" title="<?php echo JText::_('JACTION_DELETE'); ?>">&#215;</a>
			<div class="pull-left">
				<?php echo JHtml::_('grid.id', $i, $this->escape($video->name), false, 'rm', 'cb-video'); ?>
			</div>
			<div class="clearfix"></div>
		<?php endif; ?>

		<div class="height-50">
			<?php if ($cover) : ?>
				<img src="<?php echo $cover; ?>" alt="<?php echo $this->escape($video->title); ?>" loading="lazy" style="max-width:60px;max-height:50px;" />
			<?php else : ?>
				<?php echo MediaHelper::typeIcon($video->icon, 32, $video->name); ?>
			<?php endif; ?>
		</div>

		<div class="small">
			<a class="video-preview"<?php echo $cover ? ' data-cover="' . $cover . '"' : ''; ?> href="<?php echo COM_MEDIA_BASEURL, '/', str_replace('%2F', '/', rawurlencode((string) $video->path_relative)); ?>" title="<?php echo $this->escape($video->name); ?>">
				<?php echo $this->escape($video->name); ?>
			</a>
		</div>
	</li>
	<?php $dispatcher->trigger('onContentAfterDisplay', array('com_media.file', &$video, &$params, 0)); ?>
<?php endforeach; ?>
