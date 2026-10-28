<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$form     = $displayData->getForm();
$options  = array(
	'formControl' => $form->getFormControl(),
	'hidden'      => (int) ($form->getValue('language', null, '*') === '*'),
);

// CMS.associations (global.js), started from these options
JHtml::_('behavior.cms');
JText::script('JGLOBAL_ASSOC_NOT_POSSIBLE');
JText::script('JGLOBAL_ASSOCIATIONS_RESET_WARNING');
JText::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');
JFactory::getDocument()->addScriptOptions('system.associations.edit', $options);

// JLayout for standard handling of associations fields in the administrator items edit screens.
echo $form->renderFieldset('item_associations');
