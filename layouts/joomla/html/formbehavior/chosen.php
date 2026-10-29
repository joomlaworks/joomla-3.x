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
 * @var  string   $selector       The id of the field
 * @var  array    $options        The options array
 * @var  boolean  $debug          Are we in debug mode?
 */

extract($displayData);

// The list is drawn by global.js (CMS.chosen), in Chosen's markup and styles, from these options (by selector)
JHtml::_('behavior.cms');
JHtml::_('stylesheet', 'system/chosen.css', array('version' => 'auto', 'relative' => true));
JFactory::getDocument()->addScriptOptions('system.chosen', array($selector => (object) $options));
