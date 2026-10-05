<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewCompleteHtml $this */
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
	<div class="grid-2">
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_COMPLETE_ADMINISTRATION_LOGIN_DETAILS'); ?></h2>
			<div class="table-wrap">
				<table class="table">
					<tbody>
						<tr><td class="item"><?php echo JText::_('JEMAIL'); ?></td><td class="value"><code><?php echo JHtml::_('InstallationHtml.helper.escape', $this->options['admin_email']); ?></code></td></tr>
						<tr><td class="item"><?php echo JText::_('JUSERNAME'); ?></td><td class="value"><code><?php echo JHtml::_('InstallationHtml.helper.escape', $this->options['admin_user']); ?></code></td></tr>
					</tbody>
				</table>
			</div>
			<div class="actions">
				<a class="btn btn-ghost" href="<?php echo JUri::root(); ?>"><?php echo JHtml::_('InstallationHtml.helper.icon', 'home'); ?> <span><?php echo JText::_('JSITE'); ?></span></a>
				<a class="btn btn-primary" href="<?php echo JUri::root(); ?>administrator/"><?php echo JHtml::_('InstallationHtml.helper.icon', 'lock'); ?> <span><?php echo JText::_('JADMINISTRATOR'); ?></span></a>
			</div>
		</section>
		<section class="panel">
			<div class="callout">
				<p><?php echo JText::sprintf('INSTL_COMPLETE_REMOVE_INSTALLATION', 'installation'); ?></p>
				<button type="button" class="btn btn-warning" name="instDefault" data-action="remove-folder"><?php echo JHtml::_('InstallationHtml.helper.icon', 'trash'); ?> <span><?php echo JText::sprintf('INSTL_COMPLETE_REMOVE_FOLDER', 'installation'); ?></span></button>
			</div>
			<div id="languages" class="languages-callout">
				<h2 class="section-title"><?php echo JText::_('INSTL_COMPLETE_LANGUAGE_1'); ?></h2>
				<p><?php echo JText::sprintf('INSTL_COMPLETE_LANGUAGE_DESC', 'installation'); ?></p>
				<p class="help"><?php echo JText::_('INSTL_COMPLETE_LANGUAGE_DESC2'); ?></p>
				<button type="button" class="btn btn-ghost" id="instLangs" data-goto="languages"><?php echo JHtml::_('InstallationHtml.helper.icon', 'globe'); ?> <span><?php echo JText::_('INSTL_COMPLETE_INSTALL_LANGUAGES'); ?></span></button>
			</div>
		</section>
	</div>
<?php if ($this->config) : ?>
	<div class="alert alert-error">
		<h2 class="alert-heading"><?php echo JText::_('JNOTICE'); ?></h2>
		<p><?php echo JText::_('INSTL_CONFPROBLEM'); ?></p>
		<textarea class="config-code" rows="10" readonly="readonly" name="configcode" onfocus="this.select();"><?php echo JHtml::_('InstallationHtml.helper.escape', $this->config); ?></textarea>
	</div>
<?php endif; ?>
	<?php echo JHtml::_('form.token'); ?>

</form>
