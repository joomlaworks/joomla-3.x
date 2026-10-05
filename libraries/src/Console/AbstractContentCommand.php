<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;

/**
 * Base class of the content commands (articles, categories, menus, modules). They act as a real user account, so items get an
 * author, Joomla's text filters apply as for that account, and its permissions are checked: by default the first active Super
 * User, or the account given with --as (e.g. an Editor, for an AI assistant which should only edit content).
 *
 * @since  3.17.0
 */
abstract class AbstractContentCommand extends AbstractCommand
{
	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * The account the command acts as
	 *
	 * @var    User|null
	 * @since  3.17.0
	 */
	protected $user;

	/**
	 * State names => values
	 *
	 * @var    integer[]
	 * @since  3.17.0
	 */
	const STATES = array('published' => 1, 'unpublished' => 0, 'archived' => 2, 'trashed' => -2);

	/**
	 * Form field types a browser always posts, as an empty string when empty
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const TEXT_FIELDS = array('text', 'textarea', 'editor', 'hidden', 'email', 'url', 'tel', 'calendar', 'media', 'number', 'color');

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('as', null, self::OPTION_REQUIRED, 'Act as this user account (default: the first active Super User); its permissions apply');
	}

	/**
	 * Run the command as the acting account.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public function run(CommandIO $io)
	{
		$this->user = $this->getActingUser($io);

		if (!$this->user)
		{
			return self::NOT_FOUND;
		}

		$session  = Factory::getSession();
		$previous = $session->get('user');
		$session->set('user', $this->user);

		// Models read the task (e.g. to make an alias from the title) and the component from the request
		$input = Factory::getApplication()->input;
		$input->set('task', 'save');

		try
		{
			return parent::run($io);
		}
		finally
		{
			$session->set('user', $previous);
		}
	}

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  User|null
	 *
	 * @since   3.17.0
	 */
	private function getActingUser(CommandIO $io)
	{
		$db       = Factory::getDbo();
		$username = (string) $io->getOption('as');

		if ($username !== '')
		{
			$query = $db->getQuery(true)
				->select($db->quoteName(array('id', 'block')))
				->from($db->quoteName('#__users'))
				->where($db->quoteName('username') . ' = ' . $db->quote($username));
			$row = $db->setQuery($query)->loadObject();

			if (!$row)
			{
				$io->error(sprintf('The user "%s" does not exist.', $username));

				return null;
			}

			if ((int) $row->block)
			{
				$io->error(sprintf('The user "%s" is blocked.', $username));

				return null;
			}

			return User::getInstance((int) $row->id);
		}

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__usergroups'));
		$groups = array_filter(array_map('intval', $db->setQuery($query)->loadColumn()), function ($groupId)
		{
			return (bool) Access::checkGroup($groupId, 'core.admin');
		});

		if ($groups)
		{
			$query = $db->getQuery(true)
				->select('MIN(' . $db->quoteName('u.id') . ')')
				->from($db->quoteName('#__users', 'u'))
				->join('INNER', $db->quoteName('#__user_usergroup_map', 'm') . ' ON ' . $db->quoteName('m.user_id') . ' = ' . $db->quoteName('u.id'))
				->where($db->quoteName('m.group_id') . ' IN (' . implode(',', $groups) . ')')
				->where($db->quoteName('u.block') . ' = 0');
			$id = (int) $db->setQuery($query)->loadResult();

			if ($id)
			{
				return User::getInstance($id);
			}
		}

		$io->error('There is no active Super User to act as; give an account with --as.');

		return null;
	}

	/**
	 * Load an administrator model of a component, with its language, forms and tables.
	 *
	 * @param   string  $component  e.g. com_content
	 * @param   string  $name       e.g. Article
	 * @param   string  $prefix     e.g. ContentModel
	 *
	 * @return  \JModelLegacy
	 *
	 * @since   3.17.0
	 */
	protected function getModel($component, $name, $prefix)
	{
		$base = JPATH_ADMINISTRATOR . '/components/' . $component;

		// Form models expect the constants of a component request; this method adds the paths of each component it loads
		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', $base);
		}

		Factory::getLanguage()->load($component, JPATH_ADMINISTRATOR);
		\JForm::addFormPath($base . '/models/forms');
		\JForm::addFieldPath($base . '/models/fields');
		\JForm::addFormPath($base . '/model/form');
		\JLoader::register(ucfirst(substr($component, 4)) . 'Helper', $base . '/helpers/' . substr($component, 4) . '.php');

		return $this->getAdministratorModel($component, $name, $prefix);
	}

	/**
	 * Set the category model to a component's categories, as its populateState() does from the request: the component (e.g.
	 * com_content) and its section, which load the component's own category fields.
	 *
	 * @param   \CategoriesModelCategory  $model      The model
	 * @param   string                    $extension  e.g. com_content, or com_users.notes
	 *
	 * @return  \CategoriesModelCategory
	 *
	 * @since   3.17.0
	 */
	protected function primeCategoryModel($model, $extension)
	{
		$parts = explode('.', (string) $extension);

		$model->setState('category.extension', $extension);
		$model->setState('category.component', $parts[0]);
		$model->setState('category.section', isset($parts[1]) ? $parts[1] : '');

		return $model;
	}

	/**
	 * Validate the data with the model's form (filters included, e.g. the text filters of Global Configuration) and save it.
	 *
	 * @param   CommandIO     $io     The input values and the output
	 * @param   \JModelAdmin  $model  The model
	 * @param   array         $data   The item's data
	 *
	 * @return  integer|false  The item's ID, or false (with the errors shown)
	 *
	 * @since   3.17.0
	 */
	protected function validateAndSave(CommandIO $io, $model, array $data)
	{
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$io->error('Cannot load the form: ' . $model->getError());

			return false;
		}

		$valid = $model->validate($form, $this->withFormDefaults($form, $data));

		if ($valid === false)
		{
			foreach ($model->getErrors() as $error)
			{
				$io->error($error instanceof \Exception ? $error->getMessage() : (string) $error);
			}

			return false;
		}

		// Values the form doesn't know (e.g. the menu item type) are kept, as the model's save expects them
		$valid = array_merge($data, $valid);

		if ($io->isDryRun())
		{
			return 0;
		}

		if (!$model->save($valid))
		{
			$io->error($model->getError() ?: 'The item was not saved.');

			return false;
		}

		$name = $model->getName();

		return (int) $model->getState($name . '.id');
	}

	/**
	 * Add the form's default values for the fields the data doesn't have, as the edit forms post every field: a module's layout
	 * option, which is validated, and empty text fields, which PostgreSQL needs (some NOT NULL text columns have no default).
	 *
	 * @param   \JForm  $form  The form
	 * @param   array   $data  The data
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function withFormDefaults($form, array $data)
	{
		// Every field, also those outside a fieldset (e.g. the article's meta keywords)
		foreach ($form->getFieldset() as $field)
		{
			$default = $field->getAttribute('default');
			$type    = strtolower((string) $field->getAttribute('type'));

			// A module's layout select posts its first choice, the default layout, and is validated
			if ($default === null && $type === 'modulelayout')
			{
				$default = '_:default';
			}

			// A browser posts empty text fields as empty strings
			if ($default === null && in_array($type, self::TEXT_FIELDS, true))
			{
				$default = '';
			}

			if ($default === null)
			{
				continue;
			}

			$group = (string) $field->group;
			$name  = (string) $field->fieldname;

			if ($group === '')
			{
				if (!array_key_exists($name, $data))
				{
					$data[$name] = $default;
				}
			}
			elseif (strpos($group, '.') === false)
			{
				$values = isset($data[$group]) ? $data[$group] : array();
				$values = is_string($values) ? (array) json_decode($values, true) : (array) $values;

				if (!array_key_exists($name, $values))
				{
					$values[$name] = $default;
				}

				$data[$group] = $values;
			}
		}

		return $data;
	}

	/**
	 * Refuse to change an item someone has open for editing, unless --force is given.
	 *
	 * @param   CommandIO  $io    The input values and the output
	 * @param   object     $item  The item, with checked_out
	 *
	 * @return  boolean  True when the item may be changed
	 *
	 * @since   3.17.0
	 */
	protected function checkNotCheckedOut(CommandIO $io, $item)
	{
		$by = isset($item->checked_out) ? (int) $item->checked_out : 0;

		if ($by === 0 || $by === (int) $this->user->id || $io->getOption('force'))
		{
			return true;
		}

		$io->error(sprintf('The item is open for editing by %s (checked out). Give --force to change it anyway.', User::getInstance($by)->username ?: 'user ' . $by));

		return false;
	}

	/**
	 * Parse a state name or value.
	 *
	 * @param   string  $value  published, unpublished, archived, trashed, or 1, 0, 2, -2
	 *
	 * @return  integer|null
	 *
	 * @since   3.17.0
	 */
	protected function parseState($value)
	{
		$value = strtolower(trim((string) $value));

		if (isset(self::STATES[$value]))
		{
			return self::STATES[$value];
		}

		return in_array($value, array('1', '0', '2', '-2'), true) ? (int) $value : null;
	}

	/**
	 * @param   integer  $state  A state value
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function stateName($state)
	{
		$name = array_search((int) $state, self::STATES, true);

		return $name === false ? (string) $state : $name;
	}

	/**
	 * Find a category by ID, path (e.g. blog/news), alias or title.
	 *
	 * @param   CommandIO  $io         The input values and the output
	 * @param   string     $value      The category
	 * @param   string     $extension  The component, e.g. com_content
	 *
	 * @return  integer|false
	 *
	 * @since   3.17.0
	 */
	protected function findCategory(CommandIO $io, $value, $extension = 'com_content')
	{
		$db    = Factory::getDbo();
		$value = trim((string) $value);
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'path', 'title')))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . ' = ' . $db->quote($extension));

		if (ctype_digit($value))
		{
			$query->where($db->quoteName('id') . ' = ' . (int) $value);
		}
		else
		{
			$query->where('(' . $db->quoteName('path') . ' = ' . $db->quote($value) . ' OR ' . $db->quoteName('alias') . ' = ' . $db->quote($value)
				. ' OR LOWER(' . $db->quoteName('title') . ') = ' . $db->quote(function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value)) . ')');
		}

		$rows = $db->setQuery($query)->loadObjectList();

		if (count($rows) === 1)
		{
			return (int) $rows[0]->id;
		}

		if (!$rows)
		{
			$io->error(sprintf('There is no %s category "%s". category:list shows them.', $extension, $value));

			return false;
		}

		$io->error(sprintf('More than one category matches "%s": %s. Give its ID or path.', $value, implode(', ', array_map(function ($row)
		{
			return $row->id . ' (' . $row->path . ')';
		}, $rows))));

		return false;
	}

	/**
	 * Find an access level by ID or title (e.g. Public, Registered).
	 *
	 * @param   CommandIO  $io     The input values and the output
	 * @param   string     $value  The access level
	 *
	 * @return  integer|false
	 *
	 * @since   3.17.0
	 */
	protected function findAccessLevel(CommandIO $io, $value)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__viewlevels'))
			->where(ctype_digit((string) $value) ? $db->quoteName('id') . ' = ' . (int) $value : 'LOWER(' . $db->quoteName('title') . ') = ' . $db->quote(strtolower($value)));
		$id = (int) $db->setQuery($query)->loadResult();

		if (!$id)
		{
			$io->error(sprintf('There is no access level "%s".', $value));

			return false;
		}

		return $id;
	}

	/**
	 * Check a language: "*" (all) or the tag of a content language (e.g. en-GB).
	 *
	 * @param   CommandIO  $io     The input values and the output
	 * @param   string     $value  The language
	 *
	 * @return  string|false
	 *
	 * @since   3.17.0
	 */
	protected function findLanguage(CommandIO $io, $value)
	{
		$value = trim((string) $value);

		if ($value === '*' || strtolower($value) === 'all')
		{
			return '*';
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('lang_code'))
			->from($db->quoteName('#__languages'))
			->where($db->quoteName('lang_code') . ' = ' . $db->quote($value));

		if ($db->setQuery($query)->loadResult() === null)
		{
			$io->error(sprintf('There is no content language "%s". Use "*" for all languages.', $value));

			return false;
		}

		return $value;
	}

	/**
	 * Find tags by ID or title, separated by commas.
	 *
	 * @param   CommandIO  $io     The input values and the output
	 * @param   string     $value  The tags
	 *
	 * @return  integer[]|false
	 *
	 * @since   3.17.0
	 */
	protected function findTags(CommandIO $io, $value)
	{
		$db  = Factory::getDbo();
		$ids = array();

		foreach (array_filter(array_map('trim', explode(',', (string) $value)), 'strlen') as $tag)
		{
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__tags'))
				->where($db->quoteName('id') . ' > 1')
				->where(ctype_digit($tag) ? $db->quoteName('id') . ' = ' . (int) $tag
					: '(LOWER(' . $db->quoteName('title') . ') = ' . $db->quote(strtolower($tag)) . ' OR ' . $db->quoteName('alias') . ' = ' . $db->quote($tag) . ')');
			$id = (int) $db->setQuery($query)->loadResult();

			if (!$id)
			{
				$io->error(sprintf('There is no tag "%s". Create it in Components: Tags first.', $tag));

				return false;
			}

			$ids[] = $id;
		}

		return array_values(array_unique($ids));
	}

	/**
	 * Find a user by username, for authors.
	 *
	 * @param   CommandIO  $io        The input values and the output
	 * @param   string     $username  The username
	 *
	 * @return  integer|false
	 *
	 * @since   3.17.0
	 */
	protected function findUser(CommandIO $io, $username)
	{
		$id = (int) \JUserHelper::getUserId((string) $username);

		if (!$id)
		{
			$io->error(sprintf('The user "%s" does not exist.', $username));

			return false;
		}

		return $id;
	}

	/**
	 * Get a text from an option, or from the file named by the option with a "-file" suffix ("-" reads the standard input).
	 *
	 * @param   CommandIO  $io      The input values and the output
	 * @param   string     $option  The option, e.g. "text" (and "text-file")
	 *
	 * @return  string|null|false  The text, null when neither option is given, false on error
	 *
	 * @since   3.17.0
	 */
	protected function getText(CommandIO $io, $option)
	{
		$text = $io->getOption($option);
		$file = (string) $io->getOption($option . '-file');

		if ($file === '')
		{
			return $text === null || $text === false ? null : (string) $text;
		}

		if ($text !== null && $text !== false)
		{
			$io->error(sprintf('Give either --%1$s or --%1$s-file, not both.', $option));

			return false;
		}

		$content = $file === '-' ? stream_get_contents(STDIN) : (is_file($file) && is_readable($file) ? file_get_contents($file) : false);

		if ($content === false)
		{
			$io->error(sprintf('Cannot read %s.', $file));

			return false;
		}

		return $content;
	}

	/**
	 * Report the changes a dry run would make to an item: the fields whose values differ.
	 *
	 * @param   CommandIO  $io       The input values and the output
	 * @param   string     $what     e.g. 'Update the article 12 "Welcome"'
	 * @param   array      $changes  Field => new value
	 * @param   array      $current  Field => current value (empty for a new item)
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function planChanges(CommandIO $io, $what, array $changes, array $current = array())
	{
		$fields = array();

		foreach ($changes as $field => $value)
		{
			if (!array_key_exists($field, $current) || $current[$field] != $value)
			{
				$fields[$field] = is_string($value) && strlen($value) > 200 ? substr($value, 0, 197) . '...' : $value;
			}
		}

		$io->plan($what . ($fields ? ': ' . implode(', ', array_keys($fields)) : ' (nothing changes)'), array('changes' => $fields));
	}
}
