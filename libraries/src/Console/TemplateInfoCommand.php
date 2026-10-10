<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;

/**
 * Shows a template style in detail: its module positions and what's in them, its options, its CSS design tokens and files.
 *
 * @since  3.17.0
 */
class TemplateInfoCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:info';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show a template style: positions, modules, options, CSS';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows what\'s needed to work on a template: its module positions (with their descriptions and the modules in each), '
		. 'the style\'s options with their current values, the CSS custom properties (design tokens such as colours) of its main stylesheet, '
		. 'its CSS files and overrides, and the file for the site\'s own CSS when the template loads one (css/custom.css in Hammond and '
		. 'Finch: create it with template:file:set). Without an argument it shows the site\'s default style; give a style ID, or a '
		. 'template name for that template\'s default style. Add a module to a position with module:create --position=...';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('style', self::ARGUMENT_OPTIONAL, 'A style ID or a template name (default: the site\'s default style)');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
	}

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$style = $this->findStyle($io, $io->getArgument('style'), (string) $io->getOption('client'));

		if (!is_object($style))
		{
			return $style;
		}

		$client   = (int) $style->client_id === 0 ? 'site' : 'administrator';
		$template = $this->findTemplate($io, $style->template, $client);

		if (!is_array($template))
		{
			return $template;
		}

		$manifest = is_file($template['path'] . '/templateDetails.xml') ? @simplexml_load_file($template['path'] . '/templateDetails.xml') : false;
		$language = Factory::getLanguage();
		$base     = $client === 'site' ? JPATH_SITE : JPATH_ADMINISTRATOR;

		foreach (array('tpl_' . $template['name'] . '.sys', 'tpl_' . $template['name']) as $extension)
		{
			$language->load($extension, $base) || $language->load($extension, $template['path']);
		}

		$params  = json_decode((string) $style->params, true);
		$params  = is_array($params) ? $params : array();
		$details = array(
			'id'          => (int) $style->id,
			'title'       => $style->title,
			'template'    => $template['name'],
			'client'      => $client,
			'default'     => $style->home === '1' ? 'yes' : ($style->home === '0' || $style->home === '' ? 'no' : $style->home),
			'enabled'     => $template['enabled'],
			'folder'      => substr($template['path'], strlen(realpath(JPATH_ROOT)) + 1),
			'version'     => $manifest ? (string) $manifest->version : null,
			'description' => $manifest ? $this->plain(\JText::_((string) $manifest->description)) : null,
			'customCss'   => $this->getCustomCss($template),
		);

		$io->title('Template style ' . $style->id . ': ' . $style->title);
		$io->definitionList($details, 'style');

		// Positions, with their modules (the trashed ones left out)
		$modules = $this->getModules($template['clientId']);
		$declared = array();

		foreach ($manifest && isset($manifest->positions->position) ? $manifest->positions->position : array() as $position)
		{
			$declared[] = (string) $position;
		}

		$positions = array();

		foreach (array_merge($declared, array_diff(array_keys($modules), $declared)) as $position)
		{
			$key         = strtoupper('TPL_' . $template['name'] . '_POSITION_' . $position);
			$positions[] = array(
				'position'    => $position,
				'description' => $language->hasKey($key) ? \JText::_($key) : null,
				// Modules in a position the template doesn't have aren't shown by it
				'inTemplate'  => in_array($position, $declared, true),
				'modules'     => isset($modules[$position]) ? $modules[$position] : array(),
			);
		}

		$io->writeln();
		$io->text('Positions (add a module with module:create --position=POSITION):');
		$io->setData('positions', $positions);

		if (!$io->isJson())
		{
			$io->table(array('position' => 'Position', 'description' => 'Description', 'modules' => 'Modules'), array_map(function ($position)
			{
				$names = array();

				foreach ($position['modules'] as $module)
				{
					$names[] = $module['id'] . ' ' . $module['title'] . ($module['state'] !== 'published' ? ' (' . $module['state'] . ')' : '');
				}

				return array(
					'position'    => $position['position'] . ($position['inTemplate'] ? '' : ' (not in the template)'),
					'description' => $position['description'],
					'modules'     => $names ? implode(', ', $names) : '-',
				);
			}, $positions));
		}

		// The options, as the style's form has them
		$options = array();

		foreach ($manifest ? $manifest->xpath('//config//fieldset') : array() as $fieldset)
		{
			foreach ($fieldset->field as $field)
			{
				$name = (string) $field['name'];

				if ($name === '' || in_array((string) $field['type'], array('note', 'spacer'), true))
				{
					continue;
				}

				$options[] = array(
					'name'        => $name,
					'type'        => (string) $field['type'],
					'fieldset'    => (string) $fieldset['name'],
					'label'       => $this->plain(\JText::_((string) $field['label'])),
					'description' => $this->plain(\JText::_((string) $field['description'])),
					'default'     => isset($field['default']) ? (string) $field['default'] : null,
					'value'       => array_key_exists($name, $params) ? $params[$name] : null,
				);
			}
		}

		$io->writeln();
		$io->text('Options (change them with template:style:update ' . $style->id . ' --params=\'{"name":"value"}\'):');

		if ($options)
		{
			$io->table(array('name' => 'Name', 'label' => 'Label', 'value' => 'Value', 'default' => 'Default'), $options, 'options');
		}
		else
		{
			$io->setData('options', array());
			$io->text('None.');
		}

		// The design tokens and the CSS files
		$tokens = $this->getCssTokens($template['path'] . '/css/template.css');
		$io->writeln();

		if ($tokens)
		{
			$io->text('CSS custom properties of css/template.css (override them in the site\'s own CSS):');
			$io->definitionList($tokens, 'cssTokens');
		}
		else
		{
			$io->setData('cssTokens', new \stdClass);
		}

		$css = array();

		foreach (is_dir($template['path'] . '/css') ? (array) glob($template['path'] . '/css/*.css') : array() as $file)
		{
			$css[] = 'css/' . basename($file);
		}

		// Overrides: html/<component>/<view>, html/<module>, html/layouts/..., html/<plugin layouts>
		$overrides = array();

		foreach (is_dir($template['path'] . '/html') ? (array) glob($template['path'] . '/html/*', GLOB_ONLYDIR) : array() as $folder)
		{
			$views = (array) glob($folder . '/*', GLOB_ONLYDIR);

			if (strpos(basename($folder), 'com_') === 0 && $views)
			{
				foreach ($views as $view)
				{
					$overrides[] = basename($folder) . '/' . basename($view);
				}
			}
			else
			{
				$overrides[] = basename($folder);
			}
		}

		if (!$io->isJson())
		{
			$io->writeln();
			$io->definitionList(array('CSS files' => implode(', ', $css) ?: '-', 'Overrides (html/)' => implode(', ', $overrides) ?: '-'));
		}

		$io->setData('cssFiles', $css);
		$io->setData('overrides', $overrides);

		return self::SUCCESS;
	}

	/**
	 * The modules of a client by position (without the trashed ones).
	 *
	 * @param   integer  $clientId  0 for the site, 1 for the administrator
	 *
	 * @return  array  position => modules (id, title, type, state)
	 *
	 * @since   3.17.0
	 */
	private function getModules($clientId)
	{
		$db      = Factory::getDbo();
		$modules = array();
		$states  = array(1 => 'published', 0 => 'unpublished', 2 => 'archived');
		$query   = $db->getQuery(true)
			->select($db->quoteName(array('id', 'title', 'module', 'position', 'published')))
			->from($db->quoteName('#__modules'))
			->where($db->quoteName('client_id') . ' = ' . (int) $clientId)
			->where($db->quoteName('published') . ' <> -2')
			->where($db->quoteName('position') . ' <> ' . $db->quote(''))
			->order($db->quoteName('position') . ', ' . $db->quoteName('ordering') . ', ' . $db->quoteName('id'));

		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			$modules[$row->position][] = array(
				'id'    => (int) $row->id,
				'title' => $row->title,
				'type'  => $row->module,
				'state' => isset($states[(int) $row->published]) ? $states[(int) $row->published] : (string) $row->published,
			);
		}

		return $modules;
	}

	/**
	 * The file for the site's own CSS which the template loads when it exists (Hammond, Finch and Rookwood: css/custom.css; Protostar
	 * and templates built on it: css/user.css), found in the template's PHP files.
	 *
	 * @param   array  $template  The template
	 *
	 * @return  array|null  file, exists; null when the template loads none
	 *
	 * @since   3.17.0
	 */
	private function getCustomCss(array $template)
	{
		$code = '';

		foreach ((array) glob($template['path'] . '/*.php') as $file)
		{
			$code .= (string) @file_get_contents($file);
		}

		foreach (array('custom.css', 'user.css') as $name)
		{
			if (strpos($code, $name) !== false)
			{
				return array('file' => 'css/' . $name, 'exists' => is_file($template['path'] . '/css/' . $name));
			}
		}

		return null;
	}

	/**
	 * The custom properties (e.g. --c-accent: #e3261c) of a stylesheet's :root rules, each with its first value.
	 *
	 * @param   string  $file  The stylesheet
	 *
	 * @return  array  name => value
	 *
	 * @since   3.17.0
	 */
	private function getCssTokens($file)
	{
		$css    = is_file($file) ? preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($file)) : '';
		$tokens = array();

		if (preg_match_all('/:root\s*\{([^}]*)\}/', $css, $rules))
		{
			foreach ($rules[1] as $rule)
			{
				if (preg_match_all('/(--[A-Za-z0-9_-]+)\s*:\s*([^;]+);/', $rule, $matches, PREG_SET_ORDER))
				{
					foreach ($matches as $match)
					{
						// The base value: later :root rules are usually inside media queries
						if (!isset($tokens[$match[1]]))
						{
							$tokens[$match[1]] = trim(preg_replace('/\s+/', ' ', $match[2]));
						}
					}
				}
			}
		}

		return $tokens;
	}

	/**
	 * Text without markup.
	 *
	 * @param   string  $text  The text
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function plain($text)
	{
		return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES, 'UTF-8')));
	}
}
