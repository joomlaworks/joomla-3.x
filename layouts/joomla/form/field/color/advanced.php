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

$rgb = $validate !== 'color' && in_array($format, array('rgb', 'rgba'), true);

if ($rgb) {
    $placeholder = $format === 'rgba' ? 'rgba(0, 0, 0, 0.5)' : 'rgb(0, 0, 0)';
} else {
    $placeholder = '#rrggbb';
}

// The browser's picker (since 3.18; MiniColors before) shows #rrggbb: #rgb is spelled out, rgb()/rgba() converted, and
// anything else (empty, keywords) shows a crossed-out swatch
$value  = $color === 'none' && strpos((string) $keywords, 'none') === false ? '' : (string) $color;
$swatch = '';

if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $value, $match)) {
    $swatch = strtolower('#' . $match[1] . $match[1] . $match[2] . $match[2] . $match[3] . $match[3]);
} elseif (preg_match('/^#[0-9a-f]{6}$/i', $value)) {
    $swatch = strtolower($value);
} elseif (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*[\d.]+\s*)?\)$/i', $value, $match)
    && max($match[1], $match[2], $match[3]) <= 255) {
    $swatch = vsprintf('#%02x%02x%02x', array($match[1], $match[2], $match[3]));
}

$locked = $disabled || $readonly;

JHtml::_('behavior.core');
JHtml::_('behavior.styles');
JText::script('JLIB_FORM_FIELD_COLOR_CHOOSE');
JText::script('JCLEAR');

$attributes = array(
    'type'        => 'text',
    'name'        => $name,
    'id'          => $id,
    'value'       => $value,
    'class'       => trim('cms-color-value ' . $class),
    'placeholder' => strlen($hint) ? $hint : $placeholder,
    'data-format' => $format ?: null,
    'data-keywords' => $keywords ?: null,
    'data-validate' => $validate ?: null,
    'onchange'    => $onchange ?: null,
    'autocomplete' => $autocomplete ? null : 'off',
    // Colour values read left to right, also in right to left languages
    'dir'         => 'ltr',
);
?>
<span class="cms-color<?php echo $swatch ? '' : ' is-empty'; ?>">
	<input type="color" class="cms-color-swatch" value="<?php echo $swatch ?: '#000000'; ?>" aria-label="<?php echo $this->escape(JText::_('JLIB_FORM_FIELD_COLOR_CHOOSE')); ?>"<?php
        echo $locked ? ' disabled' : '', $format === 'rgba' && $rgb ? ' alpha' : ''; ?> />
	<input<?php
        foreach ($attributes as $attribute => $attributeValue) {
            if ($attributeValue !== null) {
                echo ' ' . $attribute . '="' . $this->escape($attributeValue) . '"';
            }
        }

echo $readonly ? ' readonly' : '', $disabled ? ' disabled' : '', $required ? ' required aria-required="true"' : '', $autofocus ? ' autofocus' : '';
?> />
	<?php if (!$locked) : ?>
		<button type="button" class="btn cms-color-clear" title="<?php echo $this->escape(JText::_('JCLEAR')); ?>" aria-label="<?php echo $this->escape(JText::_('JCLEAR')); ?>"><?php echo JHtml::_('icons.svg', 'x-lg', '', array('size' => 12)); ?></button>
	<?php endif; ?>
</span>
