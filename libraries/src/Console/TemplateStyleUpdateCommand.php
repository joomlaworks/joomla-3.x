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
 * Changes a template style: its options, its title, or makes it the default.
 *
 * @since  3.17.0
 */
class TemplateStyleUpdateCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:style:update';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Change a template style\'s options or title, or make it the default';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Changes the given parts of a template style and leaves the others as they are. --params is merged into the style\'s '
		. 'options (a null value removes an option, so the template\'s default applies); template:info shows the options and their '
		. 'values. --default makes the style the default of its client (site or administrator). The style\'s menu item assignments '
		. 'stay as they are.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = true;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('style', self::ARGUMENT_REQUIRED, 'The style ID');
		$this->addOption('params', null, self::OPTION_REQUIRED, 'Options as a JSON object, merged into the current ones (e.g. {"siteName":"My News"})');
		$this->addOption('title', null, self::OPTION_REQUIRED, 'The style\'s title');
		$this->addOption('default', null, self::OPTION_NONE, 'Make it the default style of its client');
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
		if (!ctype_digit((string) $io->getArgument('style')))
		{
			$io->error('Give the style\'s ID. template:list shows them.');

			return self::INVALID;
		}

		$style = $this->findStyle($io, $io->getArgument('style'), 'site');

		if (!is_object($style))
		{
			return $style;
		}

		$params  = json_decode((string) $style->params, true);
		$params  = is_array($params) ? $params : array();
		$current = array('title' => $style->title, 'params' => $params);
		$changes = array();

		if ($io->getOption('params') !== null)
		{
			$given = json_decode((string) $io->getOption('params'), true);

			if (!is_array($given) || ($given && array_keys($given) === range(0, count($given) - 1)))
			{
				$io->error('--params must be a JSON object, e.g. {"siteName":"My News"}.');

				return self::INVALID;
			}

			foreach ($given as $name => $value)
			{
				if ($value === null)
				{
					unset($params[$name]);
				}
				else
				{
					$params[$name] = is_bool($value) ? (int) $value : $value;
				}
			}

			if ($params != $current['params'])
			{
				$changes['params'] = $params;
			}
		}

		if ($io->getOption('title') !== null)
		{
			$title = trim((string) $io->getOption('title'));

			if ($title === '')
			{
				$io->error('--title can\'t be empty.');

				return self::INVALID;
			}

			if ($title !== $style->title)
			{
				$changes['title'] = $title;
			}
		}

		$makeDefault = $io->getOption('default') && $style->home !== '1';

		if (!$changes && !$makeDefault)
		{
			$io->success(sprintf('Nothing to change: the style %d "%s" is as given.', $style->id, $style->title));

			return self::SUCCESS;
		}

		$client = (int) $style->client_id === 0 ? 'site' : 'administrator';

		if ($io->isDryRun())
		{
			foreach ($changes as $field => $value)
			{
				$io->plan(sprintf('Change the %s of the style %d "%s": %s', $field, $style->id, $style->title,
					$field === 'params' ? $this->describeParams($current['params'], $value) : json_encode($value, JSON_UNESCAPED_UNICODE)),
					array('action' => 'update', 'field' => $field, 'value' => $value));
			}

			if ($makeDefault)
			{
				$io->plan(sprintf('Make the style %d "%s" the %s\'s default', $style->id, $style->title, $client), array('action' => 'default'));
			}

			return self::SUCCESS;
		}

		$model = $this->getAdministratorModel('com_templates', 'Style', 'TemplatesModel');

		if ($changes)
		{
			// The table, not the model's save(), which would also reset the style's menu item assignments
			$table = $model->getTable();
			$table->load((int) $style->id);
			$table->title  = isset($changes['title']) ? $changes['title'] : $table->title;
			$table->params = json_encode(isset($changes['params']) ? $changes['params'] : $current['params']);

			if (!$table->check() || !$table->store())
			{
				$io->error(sprintf('The style %d could not be saved: %s', $style->id, $table->getError()));

				return self::FAILURE;
			}
		}

		if ($makeDefault && !$model->setHome((int) $style->id))
		{
			$io->error(sprintf('The style %d could not be made the default: %s', $style->id, \JText::_($model->getError())));

			return self::FAILURE;
		}

		$this->cleanCacheGroup('com_templates');
		$this->cleanCacheGroup('_system');

		$io->setData('id', (int) $style->id);
		$io->setData('changed', array_keys($changes));
		$io->setData('default', $makeDefault || $style->home === '1');
		$io->success(sprintf('The style %d "%s" was updated%s.', $style->id, isset($changes['title']) ? $changes['title'] : $style->title,
			$makeDefault ? ' and is now the ' . $client . '\'s default' : ''));

		return self::SUCCESS;
	}

	/**
	 * The options a change sets, adds or removes, for a dry run.
	 *
	 * @param   array  $old  The current options
	 * @param   array  $new  The new options
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function describeParams(array $old, array $new)
	{
		$parts = array();

		foreach ($new as $name => $value)
		{
			if (!array_key_exists($name, $old) || $old[$name] != $value)
			{
				$parts[] = $name . ' = ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
		}

		foreach (array_diff_key($old, $new) as $name => $value)
		{
			$parts[] = $name . ' removed (the default applies)';
		}

		return implode(', ', $parts);
	}
}
