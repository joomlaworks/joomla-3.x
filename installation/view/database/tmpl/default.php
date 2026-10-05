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
?>
<?php echo JHtml::_('InstallationHtml.helper.stepbar'); ?>
<form action="index.php" method="post" id="adminForm" class="form-validate form-horizontal">
	<div class="btn-toolbar">
		<div class="btn-group pull-right">
			<a class="btn" href="#" onclick="return Install.goToPage('site');" rel="prev" title="<?php echo JText::_('JPREVIOUS'); ?>"><span class="icon-arrow-left"></span> <?php echo JText::_('JPREVIOUS'); ?></a>
			<a  class="btn btn-primary" href="#" onclick="Install.submitform();" rel="next" title="<?php echo JText::_('JNEXT'); ?>"><span class="icon-arrow-right icon-white"></span> <?php echo JText::_('JNEXT'); ?></a>
		</div>
	</div>
	<h3><?php echo JText::_('INSTL_DATABASE'); ?></h3>
	<hr class="hr-condensed" />
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('db_type'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('db_type'); ?>
			<p class="help-block">
				<?php echo JText::_('INSTL_DATABASE_TYPE_DESC'); ?>
			</p>
		</div>
	</div>
	<div class="control-group" data-sqlite="hide">
		<div class="control-label">
			<?php echo $this->form->getLabel('db_host'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('db_host'); ?>
			<p class="help-block">
				<?php echo JText::_('INSTL_DATABASE_HOST_DESC'); ?>
			</p>
		</div>
	</div>
	<div class="control-group" data-sqlite="hide">
		<div class="control-label">
			<?php echo $this->form->getLabel('db_user'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('db_user'); ?>
			<p class="help-block">
				<?php echo JText::_('INSTL_DATABASE_USER_DESC'); ?>
			</p>
		</div>
	</div>
	<div class="control-group" data-sqlite="hide">
		<div class="control-label">
			<?php echo $this->form->getLabel('db_pass'); ?>
		</div>
		<div class="controls">
			<?php // Disables autocomplete ?> <input type="password" style="display:none">
			<?php echo $this->form->getInput('db_pass'); ?>
			<p class="help-block">
				<?php echo JText::_('INSTL_DATABASE_PASSWORD_DESC'); ?>
			</p>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('db_name'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('db_name'); ?>
			<p class="help-block" id="db_name_desc">
				<?php echo JText::_('INSTL_DATABASE_NAME_DESC'); ?>
			</p>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('db_prefix'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('db_prefix'); ?>
			<p class="help-block">
				<?php echo JText::_('INSTL_DATABASE_PREFIX_DESC'); ?>
			</p>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('db_old'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('db_old'); ?>
			<p class="help-block">
				<?php echo JText::_('INSTL_DATABASE_OLD_PROCESS_DESC'); ?>
			</p>
		</div>
	</div>
	<div class="row-fluid">
		<div class="btn-toolbar">
			<div class="btn-group pull-right">
				<a class="btn" href="#" onclick="return Install.goToPage('site');" rel="prev" title="<?php echo JText::_('JPREVIOUS'); ?>"><span class="icon-arrow-left"></span> <?php echo JText::_('JPREVIOUS'); ?></a>
				<a  class="btn btn-primary" href="#" onclick="Install.submitform();" rel="next" title="<?php echo JText::_('JNEXT'); ?>"><span class="icon-arrow-right icon-white"></span> <?php echo JText::_('JNEXT'); ?></a>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="database" />
	<script>
		// The SQLite driver needs no server or user, and its "database name" is the database file
		(function () {
			var type   = document.getElementById('jform_db_type'),
				name   = document.getElementById('jform_db_name'),
				label  = document.getElementById('jform_db_name-lbl'),
				desc   = document.getElementById('db_name_desc'),
				groups = document.querySelectorAll('[data-sqlite="hide"]'),
				texts  = {
					label: label ? label.innerHTML : '',
					desc: desc ? desc.innerHTML : '',
					sqliteLabel: <?php echo json_encode(JText::_('INSTL_DATABASE_SQLITE_FILE_LABEL')); ?>,
					sqliteDesc: <?php echo json_encode(JText::_('INSTL_DATABASE_SQLITE_FILE_DESC')); ?>
				},
				serverName = '';

			if (!type || !name) {
				return;
			}

			function randomName() {
				var bytes = new Uint8Array(8), hex = '';

				(window.crypto || window.msCrypto).getRandomValues(bytes);

				for (var i = 0; i < bytes.length; i++) {
					hex += ('0' + bytes[i].toString(16)).slice(-2);
				}

				return 'database/joomla-' + hex + '.sqlite';
			}

			function update() {
				var sqlite = type.value === 'mysqlonsqlite';

				for (var i = 0; i < groups.length; i++) {
					groups[i].style.display = sqlite ? 'none' : '';
				}

				if (label) {
					label.innerHTML = sqlite ? texts.sqliteLabel : texts.label;
				}

				if (desc) {
					desc.innerHTML = sqlite ? texts.sqliteDesc : texts.desc;
				}

				// Suggest a file with a name nobody can guess, keeping a database name typed for the other types
				if (sqlite && !/[\\/.]/.test(name.value)) {
					serverName = name.value;
					name.value = randomName();
				} else if (!sqlite && /\.sqlite$/.test(name.value)) {
					name.value = serverName;
				}
			}

			type.addEventListener('change', update);
			update();
		})();
	</script>
	<?php echo JHtml::_('form.token'); ?>
</form>
