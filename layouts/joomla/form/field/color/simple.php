<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $autocomplete    Autocomplete attribute for the field.
 * @var   boolean  $autofocus       Is autofocus enabled?
 * @var   string   $class           Classes for the input.
 * @var   string   $description     Description of the field.
 * @var   boolean  $disabled        Is this field disabled?
 * @var   string   $group           Group the field belongs to. <fields> section in form XML.
 * @var   boolean  $hidden          Is this field hidden in the form?
 * @var   string   $hint            Placeholder for the field.
 * @var   string   $id              DOM id of the field.
 * @var   string   $label           Label of the field.
 * @var   string   $labelclass      Classes to apply to the label.
 * @var   boolean  $multiple        Does this field support multiple values?
 * @var   string   $name            Name of the input field.
 * @var   string   $onchange        Onchange attribute for the field.
 * @var   string   $onclick         Onclick attribute for the field.
 * @var   string   $pattern         Pattern (Reg Ex) of value of the form field.
 * @var   boolean  $readonly        Is this field read only?
 * @var   boolean  $repeat          Allows extensions to duplicate elements.
 * @var   boolean  $required        Is this field required?
 * @var   integer  $size            Size attribute of the input.
 * @var   boolean  $spellchec       Spellcheck state for the form field.
 * @var   string   $validate        Validation rules to apply.
 * @var   string   $value           Value attribute of the field.
 * @var   array    $checkedOptions  Options that will be set as checked.
 * @var   boolean  $hasValue        Has this field a value assigned?
 * @var   array    $options         Options available for this field.
 * @var   array    $checked         Is this field checked?
 * @var   array    $position        Is this field checked?
 * @var   array    $control         Is this field checked?
 */

// A row of swatches (since 3.18; a list Simple Colors turned into swatches before): radio buttons, each split starting a
// new row, as the list's separators did; "none" is a crossed-out swatch
JHtml::_('behavior.styles');

$locked = $disabled || $readonly;
?>
<div class="<?php echo $this->escape(trim('cms-color-simple ' . $class)); ?>" id="<?php echo $id; ?>" role="radiogroup"<?php
	echo $autofocus ? ' autofocus' : ''; ?>>
	<?php foreach ($colors as $i => $c) : ?>
		<?php $c = trim($c); ?>
		<?php $fill = preg_match('/^(#[0-9a-f]{3}|#[0-9a-f]{6}|rgba?\([\d\s.,]+\))$/i', $c) ? $c : ''; ?>
		<label class="cms-color-option" title="<?php echo $this->escape($c); ?>">
			<input type="radio" name="<?php echo $name; ?>" id="<?php echo $id . '_' . $i; ?>" value="<?php echo $this->escape($c); ?>"<?php
				echo $c == $color ? ' checked' : '',
					$locked && $c != $color ? ' disabled' : '',
					$required && $i === 0 ? ' required' : '',
					$onchange ? ' onchange="' . $this->escape($onchange) . '"' : ''; ?> />
			<span class="cms-color-chip<?php echo $fill ? '' : ' is-none'; ?>"<?php echo $fill ? ' style="background-color:' . $this->escape($fill) . '"' : ''; ?>></span>
			<span class="element-invisible"><?php echo $this->escape($c); ?></span>
		</label>
		<?php if (($i + 1) % $split == 0) : ?>
			<span class="cms-color-break"></span>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
