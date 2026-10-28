<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The editor switcher, above an editor which follows the user's choice (Joomla\CMS\Editor\Editor::display()).
 * Handled by CMS.editorSwitch (media/system/js/global.js); the choice lasts for the session.
 *
 * @var  array  $displayData  id (the editor's), current (its name), editors (name => label), token (the form token),
 *                            core (true for core components, whose edit forms have a reload task)
 */
$current = $displayData['current'];
$id      = htmlspecialchars($displayData['id'], ENT_QUOTES, 'UTF-8');
?>
<div class="editor-switch" data-editor-switch="<?php echo $id; ?>" data-token="<?php echo $displayData['token']; ?>"
	data-core="<?php echo $displayData['core'] ? '1' : '0'; ?>"
	data-confirm="<?php echo htmlspecialchars(JText::_('JLIB_EDITOR_SWITCH_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>">
	<details>
		<summary title="<?php echo htmlspecialchars(JText::_('JLIB_EDITOR_SWITCH_TITLE'), ENT_QUOTES, 'UTF-8'); ?>">
			<span class="editor-switch-label"><?php echo JText::_('JLIB_EDITOR_SWITCH_LABEL'); ?></span>
			<span class="editor-switch-current"><?php echo htmlspecialchars($displayData['editors'][$current], ENT_QUOTES, 'UTF-8'); ?></span>
		</summary>
		<ul>
			<?php foreach ($displayData['editors'] as $name => $label) : ?>
				<li>
					<button type="button" data-editor-switch-to="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $name === $current ? ' aria-current="true" disabled="disabled"' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></button>
				</li>
			<?php endforeach; ?>
		</ul>
	</details>
</div>
