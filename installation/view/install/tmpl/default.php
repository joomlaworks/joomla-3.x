<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewInstallHtml $this */
?>
<form action="index.php" method="post" id="adminForm" class="view">
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_STEP_SUMMARY_LABEL'); ?></p>
			<h1><?php echo JText::_('INSTL_INSTALLING'); ?></h1>
		</div>
	</header>
	<section class="panel install-panel">
		<div class="install-meter">
			<span class="install-percent" id="install_percent">0%</span>
			<div class="progress" id="install_progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-tasks="<?php echo htmlspecialchars(json_encode(array_values($this->tasks)), ENT_COMPAT, 'UTF-8'); ?>">
				<div class="progress-bar"></div>
			</div>
		</div>
		<ol class="tasks">
<?php foreach ($this->tasks as $task) : ?>
			<li class="task" id="install_<?php echo htmlspecialchars($task, ENT_COMPAT, 'UTF-8'); ?>"><?php if ($task === 'Email') : ?><?php echo JText::sprintf('INSTL_INSTALLING_EMAIL', '<code>' . JHtml::_('InstallationHtml.helper.escape', $this->options['admin_email']) . '</code>'); ?><?php else : ?><?php echo JText::_('INSTL_INSTALLING_' . strtoupper((string) $task)); ?><?php endif; ?></li>
<?php endforeach; ?>
		</ol>
	</section>
	<?php echo JHtml::_('form.token'); ?>

</form>
