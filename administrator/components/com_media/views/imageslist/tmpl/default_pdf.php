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
$dispatcher->trigger('onContentBeforeDisplay', array('com_media.file', &$this->_tmp_img, &$params, 0));
$url        = $this->baseURL . '/' . str_replace('%2F', '/', rawurlencode((string) $this->_tmp_img->path_relative));
?>
<li class="imgOutline thumbnail height-80 width-80 center">
	<a class="img-preview pdf-picker-item" href="javascript:ImageManager.populateFields('<?php echo $this->escape($this->_tmp_img->path_relative); ?>')" title="<?php echo $this->escape($this->_tmp_img->name); ?>">
		<div class="height-50" data-pdf-thumb="<?php echo $this->escape($url); ?>" data-pdf-width="60">
			<?php echo MediaHelper::typeIcon('file-earmark-pdf', 32, $this->_tmp_img->name); ?>
		</div>
		<div class="small">
			<?php echo $this->escape($this->_tmp_img->name); ?>
		</div>
	</a>
</li>
<?php
$dispatcher->trigger('onContentAfterDisplay', array('com_media.file', &$this->_tmp_img, &$params, 0));
