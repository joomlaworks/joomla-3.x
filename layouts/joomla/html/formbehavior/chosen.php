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

// The list is drawn by global.js (CMS.chosen), in Chosen's markup, from these options (by selector); its styles are in
// global.css, which behavior.cms loads
JHtml::_('behavior.cms');
JFactory::getDocument()->addScriptOptions('system.chosen', array($selector => (object) $options));
