<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
JHtml::_('behavior.caption');
// The subcategories' expand buttons (data-toggle="collapse", their labels in data-expand-label and data-collapse-label):
// global.js' collapse, or Bootstrap's when the template loads it
JHtml::_('behavior.cms');
?>
<div class="categories-list<?php echo $this->pageclass_sfx; ?>">
	<?php
        echo JLayoutHelper::render('joomla.content.categories_default', $this);
echo $this->loadTemplate('items');
?>
</div>
