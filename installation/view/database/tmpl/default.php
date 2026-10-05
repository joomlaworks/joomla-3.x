<?php
/**
 * @package     Joomla.Installation
 * @subpackage  View
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewDefault $this */

$sqlite = 'data-sqlite-label="' . htmlspecialchars(JText::_('INSTL_DATABASE_SQLITE_FILE_LABEL'), ENT_COMPAT, 'UTF-8') . '"'
	. ' data-sqlite-desc="' . htmlspecialchars(JText::_('INSTL_DATABASE_SQLITE_FILE_DESC'), ENT_COMPAT, 'UTF-8') . '"';
?>
<?php echo JHtml::_('InstallationHtml.helper.stepbar'); ?>

<form action="index.php" method="post" id="adminForm" class="view" novalidate="novalidate" <?php echo $sqlite; ?>>
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_STEP_DATABASE_LABEL'); ?></p>
			<h1><?php echo JText::_('INSTL_DATABASE'); ?></h1>
		</div>
<?php echo JHtml::_('InstallationHtml.helper.nav', 'site', 'JNEXT', 2); ?>

	</header>
	<section class="panel">
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'db_type', 'INSTL_DATABASE_TYPE_DESC', '', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'db_host', 'INSTL_DATABASE_HOST_DESC', 'data-sqlite="hide"', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'db_user', 'INSTL_DATABASE_USER_DESC', 'data-sqlite="hide"', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'db_pass', 'INSTL_DATABASE_PASSWORD_DESC', 'data-sqlite="hide"', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'db_name', 'INSTL_DATABASE_NAME_DESC', '', 'db_name_desc', 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'db_prefix', 'INSTL_DATABASE_PREFIX_DESC', '', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'db_old', 'INSTL_DATABASE_OLD_PROCESS_DESC', '', null, 2); ?>

	</section>
	<footer class="view-footer">
<?php echo JHtml::_('InstallationHtml.helper.nav', 'site', 'JNEXT', 2); ?>

	</footer>
	<input type="hidden" name="task" value="database" />
	<?php echo JHtml::_('form.token'); ?>

</form>
