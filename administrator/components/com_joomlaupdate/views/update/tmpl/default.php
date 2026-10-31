<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_joomlaupdate
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Include jQuery.
JHtml::_('jquery.framework');

// Load the scripts (global.js first: joomlaupdate.js reads the page's options)
JHtml::_('script', 'administrator/components/com_joomlaupdate/js/joomlaupdate.min.js', array('version' => 'auto'));

$password = JFactory::getApplication()->getUserState('com_joomlaupdate.password', null);
$filesize = JFactory::getApplication()->getUserState('com_joomlaupdate.filesize', null);
$ajaxUrl = JUri::base() . 'components/com_joomlaupdate/restore.php';
$returnUrl = 'index.php?option=com_joomlaupdate&task=update.finalise&' . JFactory::getSession()->getFormToken() . '=1';

// The extraction (js/joomlaupdate.js) starts when the page is ready
JFactory::getDocument()->addScriptOptions('com_joomlaupdate', array('update' => array(
    'password'  => (string) $password,
    'totalsize' => (string) $filesize,
    'ajax'      => $ajaxUrl,
    'return'    => $returnUrl,
)));
?>

<p class="nowarning"><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_UPDATE_INPROGRESS'); ?></p>

<div id="update-progress">
	<div id="extprogress">
		<div id="progress" class="progress progress-striped active">
			<div id="progress-bar" class="bar bar-success" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
		</div>
		<div class="extprogrow">
			<span class="extlabel"><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_UPDATE_PERCENT'); ?></span>
			<span class="extvalue" id="extpercent"></span>
		</div>
		<div class="extprogrow">
			<span class="extlabel"><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_UPDATE_BYTESREAD'); ?></span>
			<span class="extvalue" id="extbytesin"></span>
		</div>
		<div class="extprogrow">
			<span class="extlabel"><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_UPDATE_BYTESEXTRACTED'); ?></span>
			<span class="extvalue" id="extbytesout"></span>
		</div>
		<div class="extprogrow">
			<span class="extlabel"><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_UPDATE_FILESEXTRACTED'); ?></span>
			<span class="extvalue" id="extfiles"></span>
		</div>
	</div>
</div>
