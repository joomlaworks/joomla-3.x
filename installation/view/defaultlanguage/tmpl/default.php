<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewDefaultlanguageHtml $this */
?>
<?php echo JHtml::_('InstallationHtml.helper.stepbarlanguages'); ?>

<form action="index.php" method="post" id="adminForm" class="view" novalidate="novalidate">
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_STEP_DEFAULTLANGUAGE_LABEL'); ?></p>
			<h1><?php echo JText::_('INSTL_DEFAULTLANGUAGE_MULTILANGUAGE_TITLE'); ?></h1>
		</div>
		<div class="actions">
			<button type="button" class="btn btn-ghost" data-goto="languages"><?php echo JHtml::_('InstallationHtml.helper.icon', 'previous'); ?> <span><?php echo JText::_('JPREVIOUS'); ?></span></button>
<?php if ($this->items->administrator) : ?>
			<button type="button" class="btn btn-primary" data-action="next"><span><?php echo JText::_('JNEXT'); ?></span> <?php echo JHtml::_('InstallationHtml.helper.icon', 'next'); ?></button>
<?php endif; ?>
		</div>
	</header>
	<section class="panel">
		<p class="lead"><?php echo JText::_('INSTL_DEFAULTLANGUAGE_MULTILANGUAGE_DESC'); ?></p>
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'activateMultilanguage', 'INSTL_DEFAULTLANGUAGE_ACTIVATE_MULTILANGUAGE_DESC', '', null, 2); ?>

		<div id="multilanguageOptions">
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'installLocalisedContent', 'INSTL_DEFAULTLANGUAGE_INSTALL_LOCALISED_CONTENT_DESC', 'id="installLocalisedContent" data-show-when="activateMultilanguage=1"', null, 3); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'activatePluginLanguageCode', 'INSTL_DEFAULTLANGUAGE_ACTIVATE_LANGUAGE_CODE_PLUGIN_DESC', 'id="activatePluginLanguageCode" data-show-when="activateMultilanguage=1"', null, 3); ?>

		</div>
	</section>
	<div class="grid-2">
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_DEFAULTLANGUAGE_ADMINISTRATOR'); ?></h2>
			<p class="lead"><?php echo JText::_('INSTL_DEFAULTLANGUAGE_DESC'); ?></p>
			<div class="table-wrap">
				<table class="table table-select">
					<thead>
						<tr>
							<th><?php echo JText::_('INSTL_DEFAULTLANGUAGE_COLUMN_HEADER_SELECT'); ?></th>
							<th><?php echo JText::_('INSTL_DEFAULTLANGUAGE_COLUMN_HEADER_LANGUAGE'); ?></th>
							<th><?php echo JText::_('INSTL_DEFAULTLANGUAGE_COLUMN_HEADER_TAG'); ?></th>
						</tr>
					</thead>
					<tbody>
<?php foreach ($this->items->administrator as $i => $lang) : ?>
						<tr>
							<td><input id="admin-language-cb<?php echo $i; ?>" type="radio" name="administratorlang" value="<?php echo JHtml::_('InstallationHtml.helper.escape', $lang->language); ?>"<?php echo $lang->published ? ' checked="checked"' : ''; ?> /></td>
							<td><label for="admin-language-cb<?php echo $i; ?>"><?php echo JHtml::_('InstallationHtml.helper.escape', $lang->name); ?></label></td>
							<td><code><?php echo JHtml::_('InstallationHtml.helper.escape', $lang->language); ?></code></td>
						</tr>
<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_DEFAULTLANGUAGE_FRONTEND'); ?></h2>
			<p class="lead"><?php echo JText::_('INSTL_DEFAULTLANGUAGE_DESC_FRONTEND'); ?></p>
			<div class="table-wrap">
				<table class="table table-select">
					<thead>
						<tr>
							<th><?php echo JText::_('INSTL_DEFAULTLANGUAGE_COLUMN_HEADER_SELECT'); ?></th>
							<th><?php echo JText::_('INSTL_DEFAULTLANGUAGE_COLUMN_HEADER_LANGUAGE'); ?></th>
							<th><?php echo JText::_('INSTL_DEFAULTLANGUAGE_COLUMN_HEADER_TAG'); ?></th>
						</tr>
					</thead>
					<tbody>
<?php foreach ($this->items->frontend as $i => $lang) : ?>
						<tr>
							<td><input id="site-language-cb<?php echo $i; ?>" type="radio" name="frontendlang" value="<?php echo JHtml::_('InstallationHtml.helper.escape', $lang->language); ?>"<?php echo $lang->published ? ' checked="checked"' : ''; ?> /></td>
							<td><label for="site-language-cb<?php echo $i; ?>"><?php echo JHtml::_('InstallationHtml.helper.escape', $lang->name); ?></label></td>
							<td><code><?php echo JHtml::_('InstallationHtml.helper.escape', $lang->language); ?></code></td>
						</tr>
<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
	<footer class="view-footer">
		<div class="actions">
			<button type="button" class="btn btn-ghost" data-goto="languages"><?php echo JHtml::_('InstallationHtml.helper.icon', 'previous'); ?> <span><?php echo JText::_('JPREVIOUS'); ?></span></button>
<?php if ($this->items->administrator) : ?>
			<button type="button" class="btn btn-primary" data-action="next"><span><?php echo JText::_('JNEXT'); ?></span> <?php echo JHtml::_('InstallationHtml.helper.icon', 'next'); ?></button>
<?php endif; ?>
		</div>
	</footer>
	<input type="hidden" name="task" value="setdefaultlanguage" />
	<?php echo JHtml::_('form.token'); ?>

</form>
