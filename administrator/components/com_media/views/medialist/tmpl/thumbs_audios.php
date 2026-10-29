<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

$params     = new Registry();
$dispatcher = JEventDispatcher::getInstance();
?>
<?php foreach ($this->audios as $i => $audio) : ?>
	<?php $dispatcher->trigger('onContentBeforeDisplay', array('com_media.file', &$audio, &$params, 0)); ?>
	<?php $cover = $audio->cover ? $this->escape(COM_MEDIA_BASEURL . '/' . str_replace('%2F', '/', rawurlencode((string) $audio->cover))) : ''; ?>
	<li class="imgOutline thumbnail height-80 width-80 center">
		<?php if ($this->canDelete) : ?>
			<a class="close delete-item" target="_top" href="index.php?option=com_media&amp;task=file.delete&amp;tmpl=index&amp;<?php echo JSession::getFormToken(); ?>=1&amp;folder=<?php echo rawurlencode((string) $this->state->folder); ?>&amp;rm[]=<?php echo $this->escape($audio->name); ?>" rel="<?php echo $this->escape($audio->name); ?>" title="<?php echo JText::_('JACTION_DELETE'); ?>">&#215;</a>
			<div class="pull-left">
				<?php echo JHtml::_('grid.id', $i, $this->escape($audio->name), false, 'rm', 'cb-audio'); ?>
			</div>
			<div class="clearfix"></div>
		<?php endif; ?>

		<div class="height-50">
			<?php if ($cover) : ?>
				<img src="<?php echo $cover; ?>" alt="<?php echo $this->escape($audio->title); ?>" loading="lazy" style="max-width:60px;max-height:50px;" />
			<?php else : ?>
				<?php echo MediaHelper::typeIcon($audio->icon, 32, $audio->name); ?>
			<?php endif; ?>
		</div>

		<div class="small">
			<a class="audio-preview"<?php echo $cover ? ' data-cover="' . $cover . '"' : ''; ?> href="<?php echo COM_MEDIA_BASEURL, '/', str_replace('%2F', '/', rawurlencode((string) $audio->path_relative)); ?>" title="<?php echo $this->escape($audio->name); ?>">
				<?php echo $this->escape($audio->name); ?>
			</a>
		</div>
	</li>
	<?php $dispatcher->trigger('onContentAfterDisplay', array('com_media.file', &$audio, &$params, 0)); ?>
<?php endforeach; ?>
