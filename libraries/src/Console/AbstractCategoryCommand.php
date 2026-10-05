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
 * Base class of the category commands: the component the categories belong to, loading categories, and the fields
 * category:create and category:update share.
 *
 * @since  3.17.0
 */
abstract class AbstractCategoryCommand extends AbstractContentCommand
{
	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('extension', null, self::OPTION_REQUIRED, 'The component the categories belong to (e.g. com_contact)', 'com_content');
	}

	/**
	 * Run with the component in the request, where the category model reads it.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public function run(CommandIO $io)
	{
		$extension = (string) $io->getOption('extension');

		if (!preg_match('/^com_[a-z0-9_]+$/', $extension))
		{
			$io->error('--extension must be a component, e.g. com_content.');

			return self::INVALID;
		}

		Factory::getApplication()->input->set('extension', $extension);

		return parent::run($io);
	}

	/**
	 * Load the category model, set to the component of the categories (its request state isn't populated here).
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  \CategoriesModelCategory
	 *
	 * @since   3.17.0
	 */
	protected function getCategoryModel(CommandIO $io)
	{
		return $this->primeCategoryModel($this->getModel('com_categories', 'Category', 'CategoriesModel'), (string) $io->getOption('extension'));
	}

	/**
	 * Add the options of the category's fields.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function addFieldOptions()
	{
		$this->addOption('title', null, self::OPTION_REQUIRED, 'The title');
		$this->addOption('alias', null, self::OPTION_REQUIRED, 'The URL alias (made from the title when empty)');
		$this->addOption('parent', null, self::OPTION_REQUIRED, 'The parent category (ID, path, alias or title; "root" for none)');
		$this->addOption('description', null, self::OPTION_REQUIRED, 'The description (HTML)');
		$this->addOption('description-file', null, self::OPTION_REQUIRED, 'Read the description from this file ("-" for the standard input)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished, archived or trashed');
		$this->addOption('access', null, self::OPTION_REQUIRED, 'The access level: ID or title');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'The language tag (e.g. en-GB), or * for all');
		$this->addOption('tags', null, self::OPTION_REQUIRED, 'Tags (IDs or titles, separated by commas; replaces the current ones)');
		$this->addOption('image', null, self::OPTION_REQUIRED, 'The category image (e.g. images/headers/raindrops.jpg)');
		$this->addOption('metadesc', null, self::OPTION_REQUIRED, 'The meta description');
		$this->addOption('metakey', null, self::OPTION_REQUIRED, 'The meta keywords');
		$this->addOption('note', null, self::OPTION_REQUIRED, 'An administrator note');
	}

	/**
	 * Apply the given options to the category's data.
	 *
	 * @param   CommandIO  $io    The input values and the output
	 * @param   array      $data  The category's data, changed in place
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected function applyFieldOptions(CommandIO $io, array &$data)
	{
		foreach (array('title', 'alias', 'metadesc', 'metakey', 'note') as $field)
		{
			if ($io->getOption($field) !== null)
			{
				$data[$field] = (string) $io->getOption($field);
			}
		}

		$text = $this->getText($io, 'description');

		if ($text === false)
		{
			return false;
		}

		if ($text !== null)
		{
			$data['description'] = $text;
		}

		$value = $io->getOption('parent');

		if ($value !== null)
		{
			if (strtolower($value) === 'root')
			{
				$data['parent_id'] = 1;
			}
			elseif (($data['parent_id'] = $this->findCategory($io, $value, $data['extension'])) === false)
			{
				return false;
			}
		}

		$value = $io->getOption('state');

		if ($value !== null && ($data['published'] = $this->parseState($value)) === null)
		{
			$io->error('--state must be published, unpublished, archived or trashed.');

			return false;
		}

		$value = $io->getOption('access');

		if ($value !== null && ($data['access'] = $this->findAccessLevel($io, $value)) === false)
		{
			return false;
		}

		$value = $io->getOption('language');

		if ($value !== null && ($data['language'] = $this->findLanguage($io, $value)) === false)
		{
			return false;
		}

		$value = $io->getOption('tags');

		if ($value !== null && ($data['tags'] = $this->findTags($io, $value)) === false)
		{
			return false;
		}

		if ($io->getOption('image') !== null)
		{
			$data['params']['image'] = (string) $io->getOption('image');
		}

		return true;
	}

	/**
	 * Load a category as the category form has it.
	 *
	 * @param   CommandIO                $io     The input values and the output
	 * @param   \CategoriesModelCategory  $model  The model
	 * @param   string                   $id     The category ID
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	protected function loadCategory(CommandIO $io, $model, $id)
	{
		$extension = (string) $io->getOption('extension');

		if (!ctype_digit((string) $id) || !(int) $id)
		{
			$io->error(sprintf('"%s" is not a category ID.', $id));

			return false;
		}

		$item = $model->getItem((int) $id);

		if (!$item || empty($item->id) || $item->extension !== $extension)
		{
			$io->error(sprintf('There is no %s category with ID %d.', $extension, $id));

			return false;
		}

		$data         = get_object_vars($item);
		$data['tags'] = isset($item->tags) && is_object($item->tags) && $item->tags->tags !== '' ? array_map('strval', array_map('intval', explode(',', $item->tags->tags))) : array();

		return $data;
	}

	/**
	 * The category as shown by the commands.
	 *
	 * @param   array  $data  The category's data
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function describeCategory(array $data)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('path', 'level')))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('id') . ' = ' . (int) $data['id']);
		$row = $db->setQuery($query)->loadObject();

		$query = $db->getQuery(true)
			->select($db->quoteName('path'))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('id') . ' = ' . (int) $data['parent_id']);
		$parent = (string) $db->setQuery($query)->loadResult();

		return array(
			'id'          => (int) $data['id'],
			'title'       => $data['title'],
			'alias'       => $data['alias'],
			'path'        => $row ? $row->path : null,
			'level'       => $row ? (int) $row->level : null,
			'parent'      => (int) $data['parent_id'] === 1 ? null : $parent,
			'parentId'    => (int) $data['parent_id'],
			'extension'   => $data['extension'],
			'state'       => $this->stateName($data['published']),
			'access'      => (int) $data['access'],
			'language'    => $data['language'],
			'metadesc'    => $data['metadesc'],
			'metakey'     => $data['metakey'],
			'note'        => $data['note'],
			'image'       => isset($data['params']['image']) ? $data['params']['image'] : null,
			'description' => $data['description'],
		);
	}
}
