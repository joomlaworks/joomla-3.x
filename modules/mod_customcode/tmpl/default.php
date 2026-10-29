<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_customcode
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// The code as it was saved (the text filters of Global Configuration applied), not run through content plugins
echo $module->content;
