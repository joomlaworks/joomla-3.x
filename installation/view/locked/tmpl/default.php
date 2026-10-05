<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewLockedHtml $this */

$lang  = JFactory::getLanguage();
$title = $lang->hasKey('INSTL_LOCKED_TITLE') ? JText::_('INSTL_LOCKED_TITLE') : 'Joomla! is already installed';
$desc  = $lang->hasKey('INSTL_LOCKED_DESC') ? JText::sprintf('INSTL_LOCKED_DESC', 'installation')
	: 'For security, the installer only continues in the browser which installed this site. Continue to the site or its administrator, and the "installation" folder will be removed.';
?>
<form action="index.php" method="post" id="adminForm" class="view">
	<div class="alert alert-error" id="theDefaultError" hidden="hidden">
		<h2 class="alert-heading"><?php echo JText::_('JERROR'); ?></h2>
		<p id="theDefaultErrorMessage"></p>
	</div>
	<header class="hero">
		<span class="hero-badge"><?php echo JHtml::_('InstallationHtml.helper.icon', 'lock'); ?></span>
		<h1><?php echo JHtml::_('InstallationHtml.helper.escape', $title); ?></h1>
	</header>
	<section class="panel">
		<div class="callout callout-info">
			<p><?php echo JHtml::_('InstallationHtml.helper.icon', 'lock'); ?> <span><?php echo JHtml::_('InstallationHtml.helper.escape', $desc); ?></span></p>
		</div>
		<div class="actions">
			<a class="btn btn-ghost" data-action="leave" href="<?php echo JUri::root(); ?>"><?php echo JHtml::_('InstallationHtml.helper.icon', 'home'); ?> <span><?php echo JText::_('JSITE'); ?></span></a>
			<a class="btn btn-primary" data-action="leave" href="<?php echo JUri::root(); ?>administrator/"><?php echo JHtml::_('InstallationHtml.helper.icon', 'lock'); ?> <span><?php echo JText::_('JADMINISTRATOR'); ?></span></a>
		</div>
	</section>
	<?php echo JHtml::_('form.token'); ?>

</form>
