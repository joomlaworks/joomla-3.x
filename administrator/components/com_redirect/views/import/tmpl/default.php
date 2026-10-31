<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$separator   = $this->escape($this->separator);
$placeholder = implode("\n", array(
    'old-page' . $this->separator . 'new-page',
    'blog/2019/old-post?id=4' . $this->separator . 'blog/new-post',
    'shop/catalogue' . $this->separator . 'https://shop.example.com/',
));
?>
<form action="<?php echo JRoute::_('index.php?option=com_redirect&view=import'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div class="alert alert-info">
			<p><?php echo JText::_('COM_REDIRECT_IMPORT_INTRO'); ?></p>
			<ul>
				<li><?php echo JText::sprintf('COM_REDIRECT_IMPORT_HELP_FORMAT', '<code>' . $separator . '</code>', '<code>old-page' . $separator . 'new-page</code>'); ?></li>
				<li><?php echo JText::sprintf('COM_REDIRECT_IMPORT_HELP_SOURCE', '<code>' . $this->escape(JUri::root()) . '</code>'); ?></li>
				<li><?php echo JText::_('COM_REDIRECT_IMPORT_HELP_TARGET'); ?></li>
				<li><?php echo JText::_('COM_REDIRECT_IMPORT_HELP_QUERY'); ?></li>
				<li><?php echo JText::_('COM_REDIRECT_IMPORT_HELP_EXACT'); ?></li>
			</ul>
		</div>

		<?php if ($this->report && $this->report['skipped']) : ?>
			<table class="table table-striped">
				<caption><?php echo JText::_('COM_REDIRECT_IMPORT_SKIPPED'); ?></caption>
				<thead>
					<tr>
						<th width="1%" class="nowrap"><?php echo JText::_('COM_REDIRECT_IMPORT_HEADING_LINE'); ?></th>
						<th><?php echo JText::_('COM_REDIRECT_IMPORT_HEADING_TEXT'); ?></th>
						<th width="40%"><?php echo JText::_('COM_REDIRECT_IMPORT_HEADING_REASON'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($this->report['skipped'] as $skipped) : ?>
					<tr>
						<td><?php echo (int) $skipped['number']; ?></td>
						<td class="break-word"><code><?php echo $this->escape($skipped['line']); ?></code></td>
						<td><?php echo $this->escape($skipped['reason']); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<label for="redirects"><strong><?php echo JText::_('COM_REDIRECT_IMPORT_LABEL'); ?></strong></label>
		<textarea name="redirects" id="redirects" rows="16" class="span12" spellcheck="false" autocomplete="off" placeholder="<?php echo $this->escape($placeholder); ?>"><?php echo $this->escape($this->text); ?></textarea>

		<fieldset class="redirect-import-state">
			<legend><?php echo JText::_('COM_REDIRECT_IMPORT_STATE_LABEL'); ?></legend>
			<label class="radio inline"><input type="radio" name="state" value="1"<?php echo $this->state ? ' checked="checked"' : ''; ?> /> <?php echo JText::_('JENABLED'); ?></label>
			<label class="radio inline"><input type="radio" name="state" value="0"<?php echo $this->state ? '' : ' checked="checked"'; ?> /> <?php echo JText::_('JDISABLED'); ?></label>
		</fieldset>

		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
