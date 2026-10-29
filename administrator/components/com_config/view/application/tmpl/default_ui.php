<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$this->name = JText::_('COM_CONFIG_UI_SETTINGS');
$this->fieldsname = 'ui';
echo JLayoutHelper::render('joomla.content.options_default', $this);
