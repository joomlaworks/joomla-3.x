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
 * Base class of the tag commands: loading tags, finding them by ID, path, alias or title, and the fields tag:create and
 * tag:update share. Tags have no permissions of their own: the Tags component's apply.
 *
 * @since  3.17.0
 */
abstract class AbstractTagCommand extends AbstractContentCommand
{
	/**
	 * The tag model.
	 *
	 * @return  \TagsModelTag
	 *
	 * @since   3.17.0
	 */
	protected function getTagModel()
	{
		return $this->getModel('com_tags', 'Tag', 'TagsModel');
	}

	/**
	 * Add the options of the tag's fields.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function addFieldOptions()
	{
		$this->addOption('title', null, self::OPTION_REQUIRED, 'The title');
		$this->addOption('alias', null, self::OPTION_REQUIRED, 'The URL alias (made from the title when empty)');
		$this->addOption('parent', null, self::OPTION_REQUIRED, 'The parent tag (ID, path, alias or title; "root" for none)');
		$this->addOption('description', null, self::OPTION_REQUIRED, 'The description (HTML)');
		$this->addOption('description-file', null, self::OPTION_REQUIRED, 'Read the description from this file ("-" for the standard input)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished, archived or trashed');
		$this->addOption('access', null, self::OPTION_REQUIRED, 'The access level: ID or title');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'The language tag (e.g. en-GB), or * for all');
		$this->addOption('metadesc', null, self::OPTION_REQUIRED, 'The meta description');
		$this->addOption('metakey', null, self::OPTION_REQUIRED, 'The meta keywords');
		$this->addOption('note', null, self::OPTION_REQUIRED, 'An administrator note');
	}

	/**
	 * Apply the given options to the tag's data.
	 *
	 * @param   CommandIO  $io    The input values and the output
	 * @param   array      $data  The tag's data, changed in place
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
			elseif (($data['parent_id'] = $this->findTag($io, $value)) === false)
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

		return true;
	}

	/**
	 * Find a tag by ID, path (e.g. sports/football), alias or title.
	 *
	 * @param   CommandIO  $io     The input values and the output
	 * @param   string     $value  The tag
	 *
	 * @return  integer|false
	 *
	 * @since   3.17.0
	 */
	protected function findTag(CommandIO $io, $value)
	{
		$db    = Factory::getDbo();
		$value = trim((string) $value);
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'path', 'title')))
			->from($db->quoteName('#__tags'))
			->where($db->quoteName('id') . ' > 1');

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
			$io->error(sprintf('There is no tag "%s". tag:list shows them.', $value));

			return false;
		}

		$io->error(sprintf('More than one tag matches "%s": %s. Give its ID or path.', $value, implode(', ', array_map(function ($row)
		{
			return $row->id . ' (' . $row->path . ')';
		}, $rows))));

		return false;
	}

	/**
	 * Load a tag as the tag form has it.
	 *
	 * @param   CommandIO      $io     The input values and the output
	 * @param   \TagsModelTag  $model  The model
	 * @param   string         $id     The tag ID
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	protected function loadTag(CommandIO $io, $model, $id)
	{
		// The root of the tag tree (ID 1) isn't a tag
		if (!ctype_digit((string) $id) || (int) $id <= 1)
		{
			$io->error(sprintf('"%s" is not a tag ID.', $id));

			return false;
		}

		$item = $model->getItem((int) $id);

		if (!$item || empty($item->id))
		{
			$io->error(sprintf('There is no tag with ID %d.', $id));

			return false;
		}

		$data = get_object_vars($item);

		// The model gives the last change in the site's time zone, for the form; the save sets it anyway
		unset($data['modified_time']);

		return $data;
	}

	/**
	 * The tag as shown by the commands.
	 *
	 * @param   array  $data  The tag's data
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function describeTag(array $data)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('path', 'level')))
			->from($db->quoteName('#__tags'))
			->where($db->quoteName('id') . ' = ' . (int) $data['id']);
		$row = $db->setQuery($query)->loadObject();

		$query = $db->getQuery(true)
			->select($db->quoteName('path'))
			->from($db->quoteName('#__tags'))
			->where($db->quoteName('id') . ' = ' . (int) $data['parent_id']);
		$parent = (string) $db->setQuery($query)->loadResult();

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('tag_id') . ' = ' . (int) $data['id']);
		$items = (int) $db->setQuery($query)->loadResult();

		return array(
			'id'          => (int) $data['id'],
			'title'       => $data['title'],
			'alias'       => $data['alias'],
			'path'        => $row ? $row->path : null,
			'level'       => $row ? (int) $row->level : null,
			'parent'      => (int) $data['parent_id'] <= 1 ? null : $parent,
			'parentId'    => (int) $data['parent_id'],
			'state'       => $this->stateName($data['published']),
			'access'      => (int) $data['access'],
			'language'    => $data['language'],
			'items'       => $items,
			'metadesc'    => $data['metadesc'],
			'metakey'     => $data['metakey'],
			'note'        => $data['note'],
			'description' => $data['description'],
		);
	}
}
