<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Layout variables
 * ---------------------
 *
 * @var  string   $tableId                 The id of the table
 * @var  string   $formId                  The id of the form
 * @var  string   $saveOrderingUrl         Save the ordering URL?
 * @var  string   $sortDir                 The direction of the order (asc/desc)
 * @var  string   $nestedList              Is it nested list?
 * @var  string   $proceedSaveOrderButton  Is there a button to initiate the ordering?
 */

extract($displayData);

// Started by global.js (CMS.sortableList) from these options; several lists on a page each get theirs
JHtml::_('behavior.cms');
JText::script('JLIB_APPLICATION_ERROR_REORDER_FAILED');

$document = JFactory::getDocument();
$lists    = (array) $document->getScriptOptions('system.sortablelist');
$lists[]  = array(
    'tableId'         => $tableId,
    'formId'          => $formId,
    'sortDir'         => strtolower((string) $sortDir) === 'desc' ? 'desc' : 'asc',
    'url'             => $saveOrderingUrl,
    'nested'          => (bool) $nestedList,
    'saveOrderButton' => (bool) $proceedSaveOrderButton,
);

$document->addScriptOptions('system.sortablelist', $lists, false);
