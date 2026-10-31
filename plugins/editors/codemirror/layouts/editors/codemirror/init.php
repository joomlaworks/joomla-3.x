<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.codemirror
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

// No direct access
defined('_JEXEC') or die;

$params   = $displayData->params;
$basePath = $params->get('basePath', 'plugins/editors/codemirror/');
$modePath = $params->get('modePath', 'plugins/editors/codemirror/mode/%N/%N');
$extJS    = JDEBUG ? '.js' : '.min.js';
$extCSS   = JDEBUG ? '.css' : '.min.css';

JHtml::_('script', $basePath . 'lib/codemirror' . $extJS, array('version' => 'auto'));
JHtml::_('script', $basePath . 'lib/addons' . $extJS, array('version' => 'auto'));
JHtml::_('stylesheet', $basePath . 'lib/codemirror' . $extCSS, array('version' => 'auto'));
JHtml::_('stylesheet', $basePath . 'lib/addons' . $extCSS, array('version' => 'auto'));

$fskeys          = $params->get('fullScreenMod', array());
$fskeys[]        = $params->get('fullScreen', 'F10');
$fullScreenCombo = implode('-', $fskeys);
$modePath        = JUri::root(true) . '/' . $modePath . $extJS;

// The editors start in js/codemirror.js
JFactory::getDocument()->addScriptOptions('plg_editors_codemirror', array('fullScreen' => $fullScreenCombo, 'modeURL' => $modePath));
JHtml::_('script', 'plugins/editors/codemirror/js/codemirror' . $extJS, array('version' => 'auto', 'position' => 'footer'));
