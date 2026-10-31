<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;
$params = JComponentHelper::getParams('com_media');
$path   = 'file_path';

JHtml::_('behavior.core');
// The list's checkboxes and previews (the last part of media.js)
JHtml::_('script', 'com_media/media.min.js', array('version' => 'auto', 'relative' => true), array('defer' => true));
JHtml::_('stylesheet', 'com_media/media.min.css', array('version' => 'auto', 'relative' => true));
?>
<form target="_parent" action="index.php?option=com_media&amp;tmpl=index&amp;folder=<?php echo rawurlencode((string) $this->state->folder); ?>" method="post" id="mediamanager-form" name="mediamanager-form">
	<div class="muted breadcrumbs media-path">
		<?php // The folder above (in the thumbnails' place until 3.18)?>
		<?php if ($this->state->folder != '') : ?>
			<a class="btn btn-small media-back" href="index.php?option=com_media&amp;view=mediaList&amp;tmpl=component&amp;folder=<?php echo rawurlencode((string) $this->state->parent); ?>" target="folderframe">
				<?php echo JHtml::_('icons.svg', 'arrow-left', '', array('path' => 'media/com_media/images/icons', 'size' => 16)); ?>
				<?php echo JText::_('JTOOLBAR_BACK'); ?>
			</a>
		<?php endif; ?>
		<p>
			<span class="icon-folder"></span>
			<?php
                echo $params->get($path, 'images'),
($this->escape($this->state->folder) != '') ? '/' . $this->escape($this->state->folder) : '';
?>
		</p>
	</div>

	<ul class="manager thumbnails thumbnails-media">
		<?php
echo $this->loadTemplate('folders'),
$this->loadTemplate('docs'),
$this->loadTemplate('videos'),
$this->loadTemplate('audios'),
$this->loadTemplate('imgs');
?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="username" value="" />
		<input type="hidden" name="password" value="" />
		<input type="hidden" name="boxchecked" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</ul>
</form>
