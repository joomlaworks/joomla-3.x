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

JHtml::_('bootstrap.tooltip');

$params     = new Registry;
$dispatcher = JEventDispatcher::getInstance();
?>

<?php foreach ($this->audios as $i => $audio) : ?>
	<?php $dispatcher->trigger('onContentBeforeDisplay', array('com_media.file', &$audio, &$params, 0)); ?>
	<?php $cover = $audio->cover ? $this->escape(COM_MEDIA_BASEURL . '/' . str_replace('%2F', '/', rawurlencode((string) $audio->cover))) : ''; ?>
	<tr>
		<?php if ($this->canDelete) : ?>
			<td>
				<?php echo JHtml::_('grid.id', $i, $this->escape($audio->name), false, 'rm', 'cb-audio'); ?>
			</td>
		<?php endif; ?>

		<td>
			<a class="audio-preview"<?php echo $cover ? ' data-cover="' . $cover . '"' : ''; ?> href="<?php echo COM_MEDIA_BASEURL, '/', str_replace('%2F', '/', rawurlencode((string) $audio->path_relative)); ?>" title="<?php echo $this->escape($audio->title); ?>">
				<?php echo MediaHelper::typeIcon($audio->icon, 16, $audio->name); ?>
			</a>
		</td>

		<td class="description">
			<a class="audio-preview"<?php echo $cover ? ' data-cover="' . $cover . '"' : ''; ?> href="<?php echo COM_MEDIA_BASEURL, '/', str_replace('%2F', '/', rawurlencode((string) $audio->path_relative)); ?>" title="<?php echo $this->escape($audio->name); ?>">
				<?php echo $this->escape($audio->name); ?>
			</a>
		</td>

		<td class="dimensions"></td>

		<td class="filesize">
			<?php echo JHtml::_('number.bytes', $audio->size); ?>
		</td>

		<?php if ($this->canDelete) : ?>
			<td>
				<a class="delete-item" target="_top" href="index.php?option=com_media&amp;task=file.delete&amp;tmpl=index&amp;<?php echo JSession::getFormToken(); ?>=1&amp;folder=<?php echo rawurlencode((string) $this->state->folder); ?>&amp;rm[]=<?php echo $this->escape($audio->name); ?>" rel="<?php echo $this->escape($audio->name); ?>">
					<span class="icon-remove hasTooltip" title="<?php echo JHtml::tooltipText('JACTION_DELETE'); ?>"></span>
				</a>
			</td>
		<?php endif; ?>
	</tr>

	<?php $dispatcher->trigger('onContentAfterDisplay', array('com_media.file', &$audio, &$params, 0)); ?>
<?php endforeach; ?>
