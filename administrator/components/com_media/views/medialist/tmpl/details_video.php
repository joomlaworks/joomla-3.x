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

JHtml::_('bootstrap.tooltip');

$user       = JFactory::getUser();
$params     = new Registry;
$dispatcher = JEventDispatcher::getInstance();
$dispatcher->trigger('onContentBeforeDisplay', array('com_media.file', &$this->_tmp_video, &$params, 0));
?>

<tr>
	<td>
		<a class="video-preview" href="<?php echo COM_MEDIA_BASEURL . '/' . str_replace('%2F', '/', rawurlencode((string) $this->_tmp_video->path_relative)); ?>" title="<?php echo $this->escape($this->_tmp_video->title); ?>"><?php MediaHelper::typeIcon($this->_tmp_video->icon, 16, $this->_tmp_video->name); ?></a>
	</td>
	<td class="description">
		<a class="video-preview" href="<?php echo COM_MEDIA_BASEURL . '/' . str_replace('%2F', '/', rawurlencode((string) $this->_tmp_video->path_relative)); ?>" title="<?php echo $this->escape($this->_tmp_video->name); ?>">
			<?php echo JHtml::_('string.truncate', $this->escape($this->_tmp_video->name), 10, false); ?>
		</a>
	</td>
	<td class="dimensions">
		<?php // Can we figure out the dimensions of the video? ?>
	</td>
	<td class="filesize">
		<?php echo JHtml::_('number.bytes', $this->_tmp_video->size); ?>
	</td>
	<?php if ($user->authorise('core.delete', 'com_media')):?>
		<td>
			<a class="delete-item" target="_top" href="index.php?option=com_media&amp;task=file.delete&amp;tmpl=index&amp;<?php echo JSession::getFormToken(); ?>=1&amp;folder=<?php echo rawurlencode((string) $this->state->folder); ?>&amp;rm[]=<?php echo $this->escape($this->_tmp_video->name); ?>" rel="<?php echo $this->escape($this->_tmp_video->name); ?>"><span class="icon-remove hasTooltip" title="<?php echo JHtml::_('tooltipText', 'JACTION_DELETE');?>"></span></a>
			<input type="checkbox" name="rm[]" value="<?php echo $this->escape($this->_tmp_video->name); ?>" />
		</td>
	<?php endif;?>
</tr>

<?php
$dispatcher->trigger('onContentAfterDisplay', array('com_media.file', &$this->_tmp_video, &$params, 0));
