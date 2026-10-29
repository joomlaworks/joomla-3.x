<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

$params     = new Registry();
$dispatcher = JEventDispatcher::getInstance();
?>

<?php foreach ($this->documents as $i => $doc) : ?>
	<?php $dispatcher->trigger('onContentBeforeDisplay', array('com_media.file', &$doc, &$params, 0)); ?>
	<li class="imgOutline thumbnail height-80 width-80 center">
		<?php if ($this->canDelete) : ?>
			<a class="close delete-item" target="_top" href="index.php?option=com_media&amp;task=file.delete&amp;tmpl=index&amp;<?php echo JSession::getFormToken(); ?>=1&amp;folder=<?php echo rawurlencode((string) $this->state->folder); ?>&amp;rm[]=<?php echo $this->escape($doc->name); ?>" rel="<?php echo $this->escape($doc->name); ?>" title="<?php echo JText::_('JACTION_DELETE'); ?>">&#215;</a>
			<div class="pull-left">
				<?php echo JHtml::_('grid.id', $i, $this->escape($doc->name), false, 'rm', 'cb-document'); ?>
			</div>
			<div class="clearfix"></div>
		<?php endif; ?>

		<div class="height-50">
			<a style="display: block; width: 100%; height: 100%" title="<?php echo $this->escape($doc->name); ?>" >
				<?php echo MediaHelper::typeIcon($doc->icon, 32, $doc->name); ?>
			</a>
		</div>

		<div class="small" title="<?php echo $this->escape($doc->name); ?>" >
			<?php echo $this->escape($doc->name); ?>
		</div>
	</li>
	<?php $dispatcher->trigger('onContentAfterDisplay', array('com_media.file', &$doc, &$params, 0)); ?>
<?php endforeach; ?>
