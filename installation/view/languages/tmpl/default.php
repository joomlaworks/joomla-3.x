<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewLanguagesHtml $this */

// Get version of Joomla! to compare it with the version of the language package
$version             = new JVersion;
$currentShortVersion = preg_replace('#^([0-9\.]+)(|.*)$#', '$1', $version->getShortVersion());
$minorVersion        = $version::MAJOR_VERSION . '.' . $version::MINOR_VERSION;
?>
<?php echo JHtml::_('InstallationHtml.helper.stepbarlanguages'); ?>

<form action="index.php" method="post" id="adminForm" class="view" novalidate="novalidate">
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_STEP_LANGUAGES_LABEL'); ?></p>
			<h1><?php echo JText::_('INSTL_LANGUAGES'); ?></h1>
		</div>
		<div class="actions">
			<button type="button" class="btn btn-ghost" data-goto="remove"><?php echo JHtml::_('InstallationHtml.helper.icon', 'previous'); ?> <span><?php echo JText::_('JPREVIOUS'); ?></span></button>
<?php if ($this->items) : ?>
			<button type="button" class="btn btn-primary" data-action="install-languages"><span><?php echo JText::_('JNEXT'); ?></span> <?php echo JHtml::_('InstallationHtml.helper.icon', 'next'); ?></button>
<?php endif; ?>
		</div>
	</header>
<?php if (!$this->items) : ?>
	<div class="alert alert-warning">
		<p><?php echo JText::_('INSTL_LANGUAGES_WARNING_NO_INTERNET'); ?></p>
		<p><button type="button" class="btn btn-primary" data-goto="remove"><?php echo JHtml::_('InstallationHtml.helper.icon', 'previous'); ?> <span><?php echo JText::_('INSTL_LANGUAGES_WARNING_BACK_BUTTON'); ?></span></button></p>
		<p><?php echo JText::_('INSTL_LANGUAGES_WARNING_NO_INTERNET2'); ?></p>
	</div>
<?php else : ?>
	<section class="panel">
		<p class="lead" data-languages-desc="data-languages-desc"><?php echo JText::_('INSTL_LANGUAGES_DESC'); ?></p>
		<div class="alert alert-info" data-languages-wait="data-languages-wait" hidden="hidden"><p><?php echo JText::_('INSTL_LANGUAGES_MESSAGE_PLEASE_WAIT'); ?></p></div>
		<div class="table-wrap">
			<table class="table table-select">
				<thead>
					<tr>
						<th><span class="visually-hidden"><?php echo JText::_('INSTL_LANGUAGES_COLUMN_HEADER_LANGUAGE'); ?></span></th>
						<th><?php echo JText::_('INSTL_LANGUAGES_COLUMN_HEADER_LANGUAGE'); ?></th>
						<th><?php echo JText::_('INSTL_LANGUAGES_COLUMN_HEADER_LANGUAGE_TAG'); ?></th>
						<th><?php echo JText::_('INSTL_LANGUAGES_COLUMN_HEADER_VERSION'); ?></th>
					</tr>
				</thead>
				<tbody>
<?php foreach ($this->items as $i => $language) : ?>
<?php preg_match('#^pkg_([a-z]{2,3}-[A-Z]{2})$#', (string) $language->element, $element); ?>
<?php $code = isset($element[1]) ? $element[1] : ''; ?>
<?php $match = strpos((string) $language->version, $minorVersion) === 0 && strpos((string) $language->version, $currentShortVersion) === 0; ?>
					<tr>
						<td><input type="checkbox" id="cb<?php echo $i; ?>" name="cid[]" value="<?php echo (int) $language->update_id; ?>" /></td>
						<td><label for="cb<?php echo $i; ?>"><?php echo JHtml::_('InstallationHtml.helper.escape', $language->name); ?></label></td>
						<td><code><?php echo JHtml::_('InstallationHtml.helper.escape', $code); ?></code></td>
						<td><span class="badge badge-<?php echo $match ? 'success' : 'warning'; ?>"<?php echo $match ? '' : ' title="' . JHtml::_('InstallationHtml.helper.escape', JText::_('JGLOBAL_LANGUAGE_VERSION_NOT_PLATFORM')) . '"'; ?>><?php echo JHtml::_('InstallationHtml.helper.escape', $language->version); ?></span></td>
					</tr>
<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
	<input type="hidden" name="task" value="InstallLanguages" />
	<?php echo JHtml::_('form.token'); ?>

<?php endif; ?>
	<footer class="view-footer">
		<div class="actions">
			<button type="button" class="btn btn-ghost" data-goto="remove"><?php echo JHtml::_('InstallationHtml.helper.icon', 'previous'); ?> <span><?php echo JText::_('JPREVIOUS'); ?></span></button>
<?php if ($this->items) : ?>
			<button type="button" class="btn btn-primary" data-action="install-languages"><span><?php echo JText::_('JNEXT'); ?></span> <?php echo JHtml::_('InstallationHtml.helper.icon', 'next'); ?></button>
<?php endif; ?>
		</div>
	</footer>
</form>
