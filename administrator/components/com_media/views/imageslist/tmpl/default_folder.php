<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$input = JFactory::getApplication()->input;
?>
<li class="imgOutline thumbnail height-80 width-80 center">
	<a href="index.php?option=com_media&amp;view=imagesList&amp;tmpl=component<?php echo $this->type === 'pdf' ? '&amp;type=pdf' : ''; ?>&amp;folder=<?php echo rawurlencode((string) $this->_tmp_folder->path_relative); ?>&amp;asset=<?php echo $input->getCmd('asset'); ?>&amp;author=<?php echo $input->getCmd('author'); ?>" target="imageframe">
		<div class="height-50">
			<?php echo JHtml::_('icons.svg', 'folder-fill', '', array('path' => 'media/com_media/images/icons', 'size' => 32)); ?>
		</div>
		<div class="small">
			<?php echo JHtml::_('string.truncate', $this->escape($this->_tmp_folder->name), 10, false); ?>
		</div>
	</a>
</li>
