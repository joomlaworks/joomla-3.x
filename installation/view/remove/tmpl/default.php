<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewRemoveHtml $this */
?>
<form action="index.php" method="post" id="adminForm" class="view">
	<div class="alert alert-error" id="theDefaultError" hidden="hidden">
		<h2 class="alert-heading"><?php echo JText::_('JERROR'); ?></h2>
		<p id="theDefaultErrorMessage"></p>
	</div>
	<header class="hero">
		<span class="hero-badge"><?php echo JHtml::_('InstallationHtml.helper.icon', 'check'); ?></span>
		<h1><?php echo JText::_('INSTL_COMPLETE_TITLE'); ?></h1>
	</header>
	<section class="panel">
		<div class="callout">
			<p><?php echo JText::sprintf('INSTL_COMPLETE_REMOVE_INSTALLATION', 'installation'); ?></p>
			<button type="button" class="btn btn-warning" name="instDefault" data-action="remove-folder"><?php echo JHtml::_('InstallationHtml.helper.icon', 'trash'); ?> <span><?php echo JText::sprintf('INSTL_COMPLETE_REMOVE_FOLDER', 'installation'); ?></span></button>
		</div>
		<div class="actions">
			<a class="btn btn-ghost" href="<?php echo JUri::root(); ?>"><?php echo JHtml::_('InstallationHtml.helper.icon', 'home'); ?> <span><?php echo JText::_('JSITE'); ?></span></a>
			<a class="btn btn-primary" href="<?php echo JUri::root(); ?>administrator/"><?php echo JHtml::_('InstallationHtml.helper.icon', 'lock'); ?> <span><?php echo JText::_('JADMINISTRATOR'); ?></span></a>
		</div>
	</section>
	<?php echo JHtml::_('form.token'); ?>

</form>
