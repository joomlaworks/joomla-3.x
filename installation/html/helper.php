<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * HTML utility class for the installation application. The markup is XHTML-style HTML5 (void elements closed, boolean
 * attributes with values), with each method's output indented for the place the views put it.
 *
 * @since  1.6
 */
class InstallationHtmlHelper
{
	/**
	 * Method to generate the step bar of the installation.
	 *
	 * @return  string  Markup for the step bar.
	 *
	 * @since   1.6
	 */
	public static function stepbar($level = 0)
	{
		return static::indent(static::renderSteps(array('site', 'database', 'summary')), $level);
	}

	/**
	 * Method to generate the step bar of the language installation.
	 *
	 * @return  string  Markup for the step bar.
	 *
	 * @since   3.1
	 */
	public static function stepbarlanguages($level = 0)
	{
		return static::indent(static::renderSteps(array('languages', 'defaultlanguage', 'complete')), $level);
	}

	/**
	 * An inline SVG icon (they inherit the text colour and mirror in right-to-left languages where they point a direction).
	 *
	 * @param   string  $name  next, previous, refresh, check, folder, home, lock, trash, globe, info, sparkle
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function icon($name)
	{
		$paths = array(
			'next'     => '<path d="M5 12h14M13 6l6 6-6 6" />',
			'previous' => '<path d="M19 12H5M11 6l-6 6 6 6" />',
			'refresh'  => '<path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7" />',
			'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5" />',
			'folder'   => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />',
			'home'     => '<path d="M4 11l8-7 8 7M6 9.5V20h12V9.5" />',
			'lock'     => '<rect x="5" y="11" width="14" height="9" rx="2" /><path d="M8 11V8a4 4 0 0 1 8 0v3" />',
			'trash'    => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13" />',
			'globe'    => '<circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />',
			'info'     => '<circle cx="12" cy="12" r="9" /><path d="M12 11v6M12 7.5v.5" />',
			'sparkle'  => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5L18 18M6 18l2.5-2.5M15.5 8.5L18 6" />',
		);

		$class = in_array($name, array('next', 'previous'), true) ? 'icon icon-directional' : 'icon';

		return '<svg class="' . $class . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor"'
			. ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . (isset($paths[$name]) ? $paths[$name] : '') . '</svg>';
	}

	/**
	 * A form field with its label and help text.
	 *
	 * @param   JForm   $form        The form
	 * @param   string  $name        The field name
	 * @param   string  $help        The language key of the help text
	 * @param   string  $attributes  More attributes of the field's container, e.g. data-sqlite="hide"
	 * @param   string  $helpId      An id for the help text
	 * @param   integer $level       The indentation of the markup, in tabs
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function field($form, $name, $help = null, $attributes = '', $helpId = null, $level = 0)
	{
		$lines = array(
			'<div class="field"' . ($attributes ? ' ' . $attributes : '') . '>',
			"\t" . '<div class="field-label">' . static::normalize($form->getLabel($name)) . '</div>',
			"\t" . '<div class="field-control">',
			static::indent(static::normalize($form->getInput($name)), 2),
		);

		if ($help)
		{
			$lines[] = "\t\t" . '<p class="help"' . ($helpId ? ' id="' . $helpId . '"' : '') . '>' . JText::_($help) . '</p>';
		}

		$lines[] = "\t" . '</div>';
		$lines[] = '</div>';

		return static::indent(implode("\n", $lines), $level);
	}

	/**
	 * The "Previous" and "Next" buttons of a step.
	 *
	 * @param   string|null  $previous  The view of the previous step (none for the first)
	 * @param   string       $nextText  The language key of the "Next" button
	 * @param   integer      $level     The indentation of the markup, in tabs
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function nav($previous = null, $nextText = 'JNEXT', $level = 0)
	{
		$lines = array('<div class="actions">');

		if ($previous)
		{
			$lines[] = "\t" . '<button type="button" class="btn btn-ghost" data-goto="' . static::escape($previous) . '">'
				. static::icon('previous') . ' <span>' . JText::_('JPREVIOUS') . '</span></button>';
		}

		if ($nextText)
		{
			$lines[] = "\t" . '<button type="button" class="btn btn-primary" data-action="next"><span>' . JText::_($nextText) . '</span> '
				. static::icon('next') . '</button>';
		}

		$lines[] = '</div>';

		return static::indent(implode("\n", $lines), $level);
	}

	/**
	 * Escape a value for HTML.
	 *
	 * @param   mixed  $value  The value
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function escape($value)
	{
		return htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * A coloured badge.
	 *
	 * @param   string  $text   The text (HTML)
	 * @param   string  $state  success, danger, warning or empty
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function badge($text, $state = '')
	{
		return '<span class="badge' . ($state ? ' badge-' . $state : '') . '">' . $text . '</span>';
	}

	/**
	 * The table of required PHP and server features, with their state.
	 *
	 * @param   array  $options  The checks (label, state, notice)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function phpOptions($options, $level = 0)
	{
		$lines = array('<div class="table-wrap">', "\t<table class=\"table\">", "\t\t<tbody>");

		foreach ($options as $option)
		{
			$lines[] = "\t\t\t<tr>";
			$lines[] = "\t\t\t\t" . '<td class="item">' . $option->label . '</td>';
			$lines[] = "\t\t\t\t" . '<td>' . static::badge(JText::_($option->state ? 'JYES' : 'JNO'), $option->state ? 'success' : 'danger')
				. ($option->notice ? '<p class="help">' . $option->notice . '</p>' : '') . '</td>';
			$lines[] = "\t\t\t</tr>";
		}

		return static::indent(implode("\n", array_merge($lines, array("\t\t</tbody>", "\t</table>", '</div>'))), $level);
	}

	/**
	 * The table of recommended PHP settings: recommended and actual value.
	 *
	 * @param   array  $settings  The settings (label, state, recommended)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function phpSettings($settings, $level = 0)
	{
		$lines = array(
			'<div class="table-wrap">',
			"\t<table class=\"table\">",
			"\t\t<thead>",
			"\t\t\t<tr>",
			"\t\t\t\t<th>" . JText::_('INSTL_PRECHECK_DIRECTIVE') . '</th>',
			"\t\t\t\t<th>" . JText::_('INSTL_PRECHECK_RECOMMENDED') . '</th>',
			"\t\t\t\t<th>" . JText::_('INSTL_PRECHECK_ACTUAL') . '</th>',
			"\t\t\t</tr>",
			"\t\t</thead>",
			"\t\t<tbody>",
		);

		foreach ($settings as $setting)
		{
			$lines[] = "\t\t\t<tr>";
			$lines[] = "\t\t\t\t<td>" . $setting->label . '</td>';
			$lines[] = "\t\t\t\t<td>" . static::badge(JText::_($setting->recommended ? 'JON' : 'JOFF')) . '</td>';
			$lines[] = "\t\t\t\t<td>" . static::badge(JText::_($setting->state ? 'JON' : 'JOFF'), $setting->state === $setting->recommended ? 'success' : 'warning') . '</td>';
			$lines[] = "\t\t\t</tr>";
		}

		return static::indent(implode("\n", array_merge($lines, array("\t\t</tbody>", "\t</table>", '</div>'))), $level);
	}

	/**
	 * Tidy the indentation of markup from Joomla's field layouts: inner lines one tab under the first and last line.
	 *
	 * @param   string  $html  The markup
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function normalize($html)
	{
		// Named entities (e.g. &agrave; from Joomla's select lists) as characters, so the markup is also well-formed XML
		$html = preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]*);/u', function ($match) {
			return in_array($match[1], array('amp', 'lt', 'gt', 'quot', 'apos'), true)
				? $match[0]
				: html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}, trim((string) $html));

		// XHTML style: boolean attributes with a value (required="required") and void elements closed (<input ... />)
		$html = preg_replace_callback('#<([a-zA-Z][a-zA-Z0-9]*)\b((?:"[^"]*"|\'[^\']*\'|[^\'">])*)>#u', function ($match) {
			$parts = preg_split('/("[^"]*"|\'[^\']*\')/u', $match[2], -1, PREG_SPLIT_DELIM_CAPTURE);

			foreach ($parts as $i => &$part)
			{
				// Only outside the quoted attribute values
				if ($i % 2 === 0)
				{
					$part = preg_replace('/(?<=[ \t\r\n\f])(required|disabled|readonly|checked|selected|multiple|autofocus|novalidate|hidden)(?=[ \t\r\n\f]|\/|$)(?![ \t\r\n\f]*=)/iu', '$1="$1"', $part);
				}
			}

			$attributes = rtrim(implode('', $parts));
			$void       = in_array(strtolower($match[1]), array('input', 'img', 'br', 'hr', 'meta', 'link', 'col', 'source', 'wbr'), true);

			if ($void && substr($attributes, -1) !== '/')
			{
				$attributes .= ' /';
			}

			return '<' . $match[1] . $attributes . '>';
		}, $html);

		if (preg_match('/<(textarea|pre)\b/iu', $html))
		{
			return $html;
		}

		// One element per line: whitespace collapsed, tags tidied, then a line for each input, option and closing list tag
		$html = preg_replace('/[ \t\r\n\f]+/u', ' ', $html);
		$html = preg_replace(array('#[ \t\r\n\f]+>#u', '#[ \t\r\n\f]*/>#u', '#(<label\b[^>]*>)[ \t\r\n\f]+#u', '#[ \t\r\n\f]+</label>#u', '#>[ \t\r\n\f]+<#u'), array('>', ' />', '$1', '</label>', '><'), $html);
		$html = preg_replace('#(?<!^)(<(?:input|option)\b|</(?:select|fieldset)>)#u', "\n$1", $html);

		$lines = explode("\n", $html);
		$last  = count($lines) - 1;

		foreach ($lines as $i => &$line)
		{
			$line = $i === 0 || ($i === $last && $last > 1 && strpos($line, '</') === 0) ? trim($line) : "\t" . trim($line);
		}

		return implode("\n", $lines);
	}

	/**
	 * Indent each line of a block of markup, so it nests in the page source.
	 *
	 * @param   string   $html   The markup
	 * @param   integer  $level  The number of tabs
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function indent($html, $level = 1)
	{
		$tabs   = str_repeat("\t", $level);
		$lines  = preg_split('/\r\n|\n|\r/', rtrim((string) $html));
		$inside = false;

		foreach ($lines as &$line)
		{
			// The content of a textarea or pre is its value: lines after the opening tag stay as they are
			if (!$inside)
			{
				$line = trim($line) === '' ? '' : $tabs . rtrim($line);
			}

			if (preg_match_all('#<(/?)(textarea|pre)\b#iu', $line, $tags))
			{
				$inside = end($tags[1]) === '';
			}
		}

		return implode("\n", $lines);
	}

	/**
	 * Render the steps: done steps link back, the next one submits the current step, later ones are plain.
	 *
	 * @param   array  $tabs  The steps
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private static function renderSteps(array $tabs)
	{
		// Language packs made before this string fall back to English
		$title = JFactory::getLanguage()->hasKey('INSTL_STEPS_TITLE') ? JText::_('INSTL_STEPS_TITLE') : 'Install Steps';

		$lines = array(
			'<nav class="stepbar" aria-labelledby="stepbar-title">',
			"\t" . '<p class="stepbar-title" id="stepbar-title">' . static::escape($title) . '</p>',
			"\t" . '<ol class="steps">',
		);

		foreach ($tabs as $tab)
		{
			$lines[] = "\t\t" . static::getTab($tab, $tabs);
		}

		$lines[] = "\t" . '</ol>';
		$lines[] = '</nav>';

		return implode("\n", $lines);
	}

	/**
	 * Method to generate the navigation tab.
	 *
	 * @param   string  $id    The container ID.
	 * @param   array   $tabs  The navigation tabs.
	 *
	 * @return  string  Markup for the tab.
	 *
	 * @since   3.1
	 */
	private static function getTab($id, $tabs)
	{
		$input = JFactory::getApplication()->input;
		$num   = static::getTabNumber($id, $tabs);
		$view  = static::getTabNumber($input->getWord('view'), $tabs);
		$state = $num === $view ? 'current' : ($num < $view ? 'done' : 'todo');
		$label = '<span class="step-number">' . ($state === 'done' ? static::icon('check') : $num) . '</span>'
			. '<span class="step-label">' . JText::_('INSTL_STEP_' . strtoupper($id) . '_LABEL') . '</span>';

		if ($state === 'current')
		{
			$tab = '<span aria-current="step">' . $label . '</span>';
		}
		elseif ($view + 1 === $num)
		{
			$tab = '<button type="button" data-action="next">' . $label . '</button>';
		}
		elseif ($state === 'done')
		{
			$tab = '<button type="button" data-goto="' . $id . '">' . $label . '</button>';
		}
		else
		{
			$tab = '<span>' . $label . '</span>';
		}

		return '<li class="step step-' . $state . '" id="' . $id . '">' . $tab . '</li>';
	}

	/**
	 * Method to determine the tab (step) number.
	 *
	 * @param   string  $id    The container ID.
	 * @param   array   $tabs  The navigation tabs.
	 *
	 * @return  integer  Tab number in navigation sequence.
	 *
	 * @since   3.1
	 */
	private static function getTabNumber($id, $tabs)
	{
		$num = (int) array_search($id, $tabs, true);
		$num++;

		return $num;
	}
}
