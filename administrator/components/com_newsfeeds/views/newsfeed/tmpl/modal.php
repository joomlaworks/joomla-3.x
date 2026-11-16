<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_newsfeeds
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));
?>
<?php echo JLayoutHelper::render('joomla.edit.modal', array('task' => 'newsfeed', 'form' => 'newsfeed-form', 'titleField' => 'jform_name', 'function' => 'jEditNewsfeed_' . (int) $this->item->id)); ?>

<div class="container-popup">
	<?php $this->setLayout('edit'); ?>
	<?php echo $this->loadTemplate(); ?>
</div>
