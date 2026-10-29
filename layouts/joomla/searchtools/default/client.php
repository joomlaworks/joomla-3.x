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
 * The Site/Administrator choice of a list (its client_id filter field) as a row of tabs above the search tools: a hidden
 * field keeps the field's name, id and onchange (which clears the other filters where they differ per client and
 * submits), and global.js sets it from the tab clicked
 *
 * @var  array  $displayData  field: the client_id JFormField
 */
$field = $displayData['field'];

JHtml::_('behavior.core');

$onchange = (string) $field->getAttribute('onchange');

// The options as the field's own list writes them (translated); none chosen yet is the first, as the list showed it
preg_match_all('#<option value="([^"]*)"([^>]*)>(.*?)</option>#s', $field->input, $matches, PREG_SET_ORDER);

$value = (string) $field->value;

if ($matches && !in_array($value, array_map(function ($match) {
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}, $matches), true)) {
    $value = html_entity_decode($matches[0][1], ENT_QUOTES, 'UTF-8');
}
?>
<ul class="nav nav-tabs js-stools-client">
	<?php foreach ($matches as $match) : ?>
		<?php $optionValue = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'); ?>
		<li<?php echo $optionValue === $value ? ' class="active"' : ''; ?>>
			<a href="#" class="js-stools-client-tab" role="button" data-target="<?php echo $field->id; ?>" data-value="<?php echo htmlspecialchars($optionValue, ENT_COMPAT, 'UTF-8'); ?>"<?php
                echo $optionValue === $value ? ' aria-current="true"' : ''; ?>><?php echo trim($match[3]); ?></a>
		</li>
	<?php endforeach; ?>
</ul>
<input type="hidden" name="<?php echo $field->name; ?>" id="<?php echo $field->id; ?>" value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"<?php
    echo $onchange !== '' ? ' onchange="' . htmlspecialchars($onchange, ENT_COMPAT, 'UTF-8') . '"' : ''; ?> />
