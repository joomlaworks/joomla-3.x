<?php

/**
 * @package     FrameworkOnFramework
 * @subpackage  less
 * @copyright   Copyright (C) 2010-2016 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */
// Protect from unauthorized access
defined('FOF_INCLUDED') or die;

/**
 * This class is taken near verbatim (changes marked with **FOF** comment markers) from:
 *
 * lessphp v0.5.0
 * http://leafo.net/lessphp
 *
 * LESS CSS compiler, adapted from http://lesscss.org
 *
 * Copyright 2013, Leaf Corcoran <leafot@gmail.com>
 * Licensed under MIT or GPLv3, see LICENSE
 *
 * Joomla 3.x UTD: updated from lessphp v0.3.9 to the copy of v0.5.0 in libraries/vendor/leafo/lessphp, with its patches
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class FOFLessFormatterLessjs extends FOFLessFormatterClassic
{
    public $disableSingle = true;
    public $breakSelectors = true;
    public $assignSeparator = ": ";
    public $selectorSeparator = ",";
}
