<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_customblocks
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// The blocks as the editor rendered them when the module was saved (with the site template's block layouts), the text
// filters of Global Configuration applied
echo $module->content;
