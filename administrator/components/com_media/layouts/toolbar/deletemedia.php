<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$title = JText::_('JTOOLBAR_DELETE');

// deleteMedia() is the Media Manager's (media.js)
JText::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
?>
<button onclick="deleteMedia()" class="btn btn-small">
	<span class="icon-remove" title="<?php echo $title; ?>"></span> <?php echo $title; ?>
</button>
