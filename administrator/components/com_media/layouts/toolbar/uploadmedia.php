<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// The site (picking while editing there) has no administrator strings
$title = JText::_(JFactory::getLanguage()->hasKey('JTOOLBAR_UPLOAD') ? 'JTOOLBAR_UPLOAD' : 'COM_MEDIA_UPLOAD');
?>
<button data-toggle="collapse" data-target="#collapseUpload" class="btn btn-small btn-success">
	<span class="icon-plus icon-white" title="<?php echo $title; ?>"></span> <?php echo $title; ?>
</button>
