<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Layout variables
 * ---------------------
 *
 * @var  string   $selector       The id of the field
 * @var  string   $minTermLength  The minimum number of characters for the tag
 * @var  boolean  $allowCustom    Can we insert custom tags?
 */

extract($displayData);


// Tags field ajax
$chosenAjaxSettings = new Registry(
    array(
        'selector'      => $selector,
        'type'          => 'GET',
        'url'           => JUri::root() . 'index.php?option=com_tags&task=tags.searchAjax',
        'dataType'      => 'json',
        'jsonTermKey'   => 'like',
        'minTermLength' => $minTermLength
    )
);

JHtml::_('formbehavior.ajaxchosen', $chosenAjaxSettings);

// Typed tags (Enter or a comma): an existing tag of that name, else a new one, posted as #new#<name> (global.js: CMS.chosen)
if ($allowCustom) {
    JFactory::getDocument()->addScriptOptions('system.chosentags', array($selector => array('prefix' => '#new#', 'minLength' => (int) $minTermLength)));
}
