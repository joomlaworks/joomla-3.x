<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewRemoveHtml $this */

$autoRemove = JFactory::getLanguage()->hasKey('INSTL_COMPLETE_AUTO_REMOVE') ? JText::sprintf('INSTL_COMPLETE_AUTO_REMOVE', 'installation') : 'For security, the "installation" folder will be removed automatically when you continue to your site or its administrator.';
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
		<div class="callout callout-info">
			<p><?php echo JHtml::_('InstallationHtml.helper.icon', 'lock'); ?> <span><?php echo JHtml::_('InstallationHtml.helper.escape', $autoRemove); ?></span></p>
		</div>
		<div class="actions">
			<a class="btn btn-ghost" data-action="leave" href="<?php echo JUri::root(); ?>"><?php echo JHtml::_('InstallationHtml.helper.icon', 'home'); ?> <span><?php echo JText::_('JSITE'); ?></span></a>
			<a class="btn btn-primary" data-action="leave" href="<?php echo JUri::root(); ?>administrator/"><?php echo JHtml::_('InstallationHtml.helper.icon', 'lock'); ?> <span><?php echo JText::_('JADMINISTRATOR'); ?></span></a>
		</div>
	</section>
	<?php echo JHtml::_('form.token'); ?>

</form>
