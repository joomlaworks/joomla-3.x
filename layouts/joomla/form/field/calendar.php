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
 * @var   boolean  $spellcheck      Spellcheck state for the form field.
 * @var   string   $validate        Validation rules to apply.
 * @var   string   $value           Value attribute of the field.
 * @var   array    $checkedOptions  Options that will be set as checked.
 * @var   boolean  $hasValue        Has this field a value assigned?
 * @var   array    $options         Options available for this field.
 *
 * Calendar Specific
 * @var   string   $format          The strftime format of the value (what the form posts)
 * @var   string   $minYear         The minimum year, that will be subtracted/added to current year
 * @var   string   $maxYear         The maximum year, that will be subtracted/added to current year
 * @var   integer  $todaybutton     The Today/Now button
 * @var   integer  $showtime        The time selector display
 *
 * Since 3.18 the browser's own date input ($weeknumbers, $filltable, $timeformat and $singleheader no longer apply; the
 * date's format on screen is the browser's): the value is posted by a hidden field in $format, as the calendar posted it
 */

// The value, read back from $format, as the browser's input holds it
$dateFormat = JHtml::strftimeToDateFormat($format);
$hasTime    = (bool) preg_match('/%[HIklMSpPRTrX]/', $format);
$hasDay     = (bool) preg_match('/%[dejaAuwDFxcsU]/', $format);
$hasYear    = (bool) preg_match('/%[YyCGgmbBhDFxcs]/', $format);
$seconds    = (bool) preg_match('/%[ST]/', $format) || ($hasTime && (bool) $showtime && !preg_match('/%[MR]/', $format));
$type       = !$hasYear && !$hasDay ? ($hasTime ? 'time' : 'date') : ($hasTime ? 'datetime-local' : ($hasDay || !preg_match('/%[mbBh]/', $format) ? 'date' : 'month'));
$native     = '';

if (strtoupper((string) $value) === 'NOW')
{
	$value = JFactory::getDate()->format($dateFormat);
}

if ((string) $value !== '' && $value !== '0000-00-00 00:00:00')
{
	$date = DateTime::createFromFormat('!' . $dateFormat, (string) $value, new DateTimeZone('UTC'));

	if (!$date && strtotime((string) $value) !== false)
	{
		$date = new DateTime('@' . strtotime((string) $value));
	}

	if ($date)
	{
		$nativeFormats = array(
			'date'           => 'Y-m-d',
			'month'          => 'Y-m',
			'time'           => $seconds ? 'H:i:s' : 'H:i',
			'datetime-local' => $seconds ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i',
		);
		$native = $date->format($nativeFormats[$type]);
	}
}
else
{
	$value = '';
}

// minyear/maxyear: years before/after this one (or a year itself)
$limits = array();

foreach (array('min' => isset($minYear) ? $minYear : '', 'max' => isset($maxYear) ? $maxYear : '') as $limit => $year)
{
	if (is_numeric($year) && $type !== 'time')
	{
		$year   = abs((int) $year) > 999 ? (int) $year : (int) date('Y') + (int) $year;
		$day    = $limit === 'min' ? '01-01' : '12-31';
		$values = array('date' => "$year-$day", 'month' => $year . ($limit === 'min' ? '-01' : '-12'), 'datetime-local' => "$year-$day" . ($limit === 'min' ? 'T00:00' : 'T23:59'));
		$limits[$limit] = sprintf('%04d', $year) . substr($values[$type], strlen((string) $year));
	}
}

$locked = $readonly || $disabled;

// Global Configuration's Date and Time Fields, or the user's own: the browser's input as it is, or the date as text in
// the language's format with the browser's picker behind a button
$user = JFactory::getUser();
$mode = $user->getParam('date_fields') ?: JFactory::getConfig()->get('date_fields', 'browser');
$text = $mode === 'language';

// The time zone the value is shown in (the field's filter), so Now is the user's now, not the browser's clock
$timezone = null;

switch (strtoupper(isset($filter) && is_string($filter) ? $filter : ''))
{
	case 'USER_UTC':
		$timezone = JFactory::getUser()->getTimezone();
		break;
	case 'SERVER_UTC':
		$timezone = new DateTimeZone(JFactory::getConfig()->get('offset') ?: 'UTC');
		break;
}

JHtml::_('behavior.core');
JHtml::_('behavior.styles');

$attributes = array(
	'type'      => $type,
	'id'        => $text ? null : $id,
	'class'     => trim('cms-date-input ' . ($text ? '' : $class)),
	'value'     => $native,
	'step'      => $seconds ? '1' : null,
	'min'       => isset($limits['min']) ? $limits['min'] : null,
	'max'       => isset($limits['max']) ? $limits['max'] : null,
	'onchange'  => !$text && !empty($onchange) ? $onchange : null,
	'tabindex'  => $text ? '-1' : null,
	'aria-hidden' => $text ? 'true' : null,
	'data-utc-offset' => $timezone ? (string) ((new DateTime('now', $timezone))->getOffset() / 60) : null,
);

// In the language's format: the value as text, a placeholder spelling the format out and a pattern the form validator
// checks (no escapes but for regular expression syntax: browsers compile patterns with the v flag)
$placeholder = strtr($format, array(
	'%Y' => 'YYYY', '%y' => 'YY', '%m' => 'MM', '%d' => 'DD', '%e' => 'D', '%H' => 'hh', '%k' => 'h', '%I' => 'hh', '%l' => 'h',
	'%M' => 'mm', '%S' => 'ss', '%p' => 'AM', '%P' => 'am', '%F' => 'YYYY-MM-DD', '%T' => 'hh:mm:ss', '%R' => 'hh:mm', '%%' => '%',
));
$pattern = preg_replace_callback('/%([a-zA-Z%])|([\\^$.*+?()[\]{}|\/])/', function ($match)
{
	if (!empty($match[2]))
	{
		return '\\' . $match[2];
	}

	$codes = array(
		'Y' => '\d{4}', 'y' => '\d{2}', 'C' => '\d{2}', 'm' => '\d{1,2}', 'd' => '\d{1,2}', 'H' => '\d{1,2}', 'I' => '\d{1,2}',
		'M' => '\d{1,2}', 'S' => '\d{1,2}', 'e' => ' ?\d{1,2}', 'k' => ' ?\d{1,2}', 'l' => ' ?\d{1,2}', 'p' => '[AaPp][Mm]',
		'P' => '[AaPp][Mm]', 'F' => '\d{4}-\d{1,2}-\d{1,2}', 'T' => '\d{1,2}:\d{1,2}:\d{1,2}', 'R' => '\d{1,2}:\d{1,2}', '%' => '%',
	);

	return isset($codes[$match[1]]) ? $codes[$match[1]] : '\\S+';
}, $format);
?>
<span class="cms-date field-calendar<?php echo $text ? ' is-text' : ''; ?>">
	<?php if ($text) : ?>
		<input type="text" id="<?php echo $id; ?>" class="<?php echo htmlspecialchars(trim('cms-date-value cms-date-text ' . $class), ENT_COMPAT, 'UTF-8'); ?>" name="<?php echo $name; ?>" value="<?php
			echo htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8'); ?>" data-format="<?php echo htmlspecialchars((string) $format, ENT_COMPAT, 'UTF-8'); ?>" placeholder="<?php
			echo htmlspecialchars($placeholder, ENT_COMPAT, 'UTF-8'); ?>" pattern="<?php echo htmlspecialchars($pattern, ENT_COMPAT, 'UTF-8'); ?>" size="<?php echo strlen($placeholder) + 1; ?>" autocomplete="off"<?php
			echo !empty($onchange) ? ' onchange="' . htmlspecialchars($onchange, ENT_COMPAT, 'UTF-8') . '"' : '',
				$readonly ? ' readonly' : '', $disabled ? ' disabled' : '', $required ? ' required aria-required="true"' : '', !empty($autofocus) ? ' autofocus' : ''; ?> />
	<?php endif; ?>
	<input<?php
		foreach ($attributes as $attribute => $attributeValue)
		{
			if ($attributeValue !== null)
			{
				echo ' ' . $attribute . '="' . htmlspecialchars((string) $attributeValue, ENT_COMPAT, 'UTF-8') . '"';
			}
		}

		if ($text)
		{
			echo $locked ? ' disabled' : '';
		}
		else
		{
			echo $readonly ? ' readonly' : '', $disabled ? ' disabled' : '', $required ? ' required aria-required="true"' : '', !empty($autofocus) ? ' autofocus' : '';
		}
	?> />
	<?php if (!$text) : ?>
		<input type="hidden" class="cms-date-value" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8'); ?>" data-format="<?php echo htmlspecialchars((string) $format, ENT_COMPAT, 'UTF-8'); ?>"<?php echo $disabled ? ' disabled' : ''; ?> />
	<?php endif; ?>
	<?php if (!$locked) : ?>
		<?php if ($text) : ?>
			<button type="button" class="btn cms-date-pick" title="<?php echo JText::_('JLIB_HTML_BEHAVIOR_OPEN_CALENDAR'); ?>" aria-label="<?php echo JText::_('JLIB_HTML_BEHAVIOR_OPEN_CALENDAR'); ?>"><?php echo JHtml::_('icons.svg', 'calendar3', '', array('size' => 14)); ?></button>
		<?php endif; ?>
		<?php if ($todaybutton) : ?>
			<button type="button" class="btn cms-date-now"><?php echo JText::_($type === 'date' || $type === 'month' ? 'JLIB_HTML_BEHAVIOR_TODAY' : 'JLIB_FORM_FIELD_CALENDAR_NOW'); ?></button>
		<?php endif; ?>
		<button type="button" class="btn cms-date-clear" title="<?php echo JText::_('JCLEAR'); ?>" aria-label="<?php echo JText::_('JCLEAR'); ?>"><?php echo JHtml::_('icons.svg', 'x-lg', '', array('size' => 12)); ?></button>
	<?php endif; ?>
</span>
