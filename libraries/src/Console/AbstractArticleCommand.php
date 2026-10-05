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
use Joomla\CMS\User\User;

/**
 * Base class of the article commands: loading articles, and the fields article:create and article:update share.
 *
 * @since  3.17.0
 */
abstract class AbstractArticleCommand extends AbstractContentCommand
{
	/**
	 * Add the options of the article's fields.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function addFieldOptions()
	{
		$this->addOption('title', null, self::OPTION_REQUIRED, 'The title');
		$this->addOption('alias', null, self::OPTION_REQUIRED, 'The URL alias (made from the title when empty)');
		$this->addOption('category', null, self::OPTION_REQUIRED, 'The category: ID, path (e.g. blog/news), alias or title');
		$this->addOption('text', null, self::OPTION_REQUIRED, 'The article text (HTML). <hr id="system-readmore" /> splits the intro from the rest');
		$this->addOption('text-file', null, self::OPTION_REQUIRED, 'Read the article text from this file ("-" for the standard input)');
		$this->addOption('state', null, self::OPTION_REQUIRED, 'published, unpublished, archived or trashed');
		$this->addOption('featured', null, self::OPTION_REQUIRED, 'yes or no');
		$this->addOption('access', null, self::OPTION_REQUIRED, 'The access level: ID or title (e.g. Public, Registered)');
		$this->addOption('language', null, self::OPTION_REQUIRED, 'The language tag (e.g. en-GB), or * for all');
		$this->addOption('tags', null, self::OPTION_REQUIRED, 'Tags (IDs or titles, separated by commas; replaces the current ones, empty removes them)');
		$this->addOption('author', null, self::OPTION_REQUIRED, 'The author\'s username');
		$this->addOption('author-alias', null, self::OPTION_REQUIRED, 'The author name shown instead of the author\'s');
		$this->addOption('publish-up', null, self::OPTION_REQUIRED, 'Start publishing (e.g. "2026-11-01 09:00", in the site\'s time zone)');
		$this->addOption('publish-down', null, self::OPTION_REQUIRED, 'Finish publishing (empty for never)');
		$this->addOption('image-intro', null, self::OPTION_REQUIRED, 'The intro image (e.g. images/headers/blue-flower.jpg)');
		$this->addOption('image-intro-alt', null, self::OPTION_REQUIRED, 'The intro image\'s alternative text');
		$this->addOption('image-full', null, self::OPTION_REQUIRED, 'The full article image');
		$this->addOption('image-full-alt', null, self::OPTION_REQUIRED, 'The full article image\'s alternative text');
		$this->addOption('metadesc', null, self::OPTION_REQUIRED, 'The meta description');
		$this->addOption('metakey', null, self::OPTION_REQUIRED, 'The meta keywords');
		$this->addOption('note', null, self::OPTION_REQUIRED, 'An administrator note');
	}

	/**
	 * Apply the given options to the article's data.
	 *
	 * @param   CommandIO  $io    The input values and the output
	 * @param   array      $data  The article's data, changed in place
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected function applyFieldOptions(CommandIO $io, array &$data)
	{
		foreach (array('title' => 'title', 'alias' => 'alias', 'author-alias' => 'created_by_alias', 'metadesc' => 'metadesc', 'metakey' => 'metakey',
			'note' => 'note', 'publish-up' => 'publish_up', 'publish-down' => 'publish_down') as $option => $field)
		{
			$value = $io->getOption($option);

			if ($value !== null && $value !== false)
			{
				$data[$field] = (string) $value;
			}
		}

		$text = $this->getText($io, 'text');

		if ($text === false)
		{
			return false;
		}

		if ($text !== null)
		{
			$data['articletext'] = $text;
		}

		$value = $io->getOption('category');

		if ($value !== null && ($data['catid'] = $this->findCategory($io, $value)) === false)
		{
			return false;
		}

		$value = $io->getOption('state');

		if ($value !== null && ($data['state'] = $this->parseState($value)) === null)
		{
			$io->error('--state must be published, unpublished, archived or trashed.');

			return false;
		}

		$value = $io->getOption('featured');

		if ($value !== null)
		{
			if (!in_array(strtolower($value), array('yes', 'no', '1', '0'), true))
			{
				$io->error('--featured must be yes or no.');

				return false;
			}

			$data['featured'] = in_array(strtolower($value), array('yes', '1'), true) ? 1 : 0;
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

		$value = $io->getOption('author');

		if ($value !== null && ($data['created_by'] = $this->findUser($io, $value)) === false)
		{
			return false;
		}

		foreach (array('image-intro' => 'image_intro', 'image-intro-alt' => 'image_intro_alt', 'image-full' => 'image_fulltext',
			'image-full-alt' => 'image_fulltext_alt') as $option => $field)
		{
			$value = $io->getOption($option);

			if ($value !== null)
			{
				$data['images'][$field] = (string) $value;
			}
		}

		return true;
	}

	/**
	 * Load an article as the article form has it.
	 *
	 * @param   CommandIO             $io     The input values and the output
	 * @param   \ContentModelArticle  $model  The model
	 * @param   string                $id     The article ID
	 *
	 * @return  array|false
	 *
	 * @since   3.17.0
	 */
	protected function loadArticle(CommandIO $io, $model, $id)
	{
		if (!ctype_digit((string) $id) || !(int) $id)
		{
			$io->error(sprintf('"%s" is not an article ID.', $id));

			return false;
		}

		$item = $model->getItem((int) $id);

		if (!$item || empty($item->id))
		{
			$io->error(sprintf('There is no article with ID %d.', $id));

			return false;
		}

		$data = get_object_vars($item);

		// The form takes the tags as IDs
		$data['tags'] = isset($item->tags) && is_object($item->tags) && $item->tags->tags !== '' ? array_map('strval', array_map('intval', explode(',', $item->tags->tags))) : array();

		return $data;
	}

	/**
	 * The article as shown by the commands: its fields, with names for IDs.
	 *
	 * @param   array  $data  The article's data
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function describeArticle(array $data)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('path'))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('id') . ' = ' . (int) $data['catid']);
		$category = (string) $db->setQuery($query)->loadResult();

		$tags = array();

		if (!empty($data['tags']))
		{
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__tags'))
				->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', (array) $data['tags'])) . ')');
			$tags = $db->setQuery($query)->loadColumn();
		}

		return array(
			'id'          => (int) $data['id'],
			'title'       => $data['title'],
			'alias'       => $data['alias'],
			'category'    => $category,
			'categoryId'  => (int) $data['catid'],
			'state'       => $this->stateName($data['state']),
			'featured'    => (bool) $data['featured'],
			'access'      => (int) $data['access'],
			'language'    => $data['language'],
			'tags'        => $tags,
			'author'      => (int) $data['created_by'] ? User::getInstance((int) $data['created_by'])->username : null,
			'authorAlias' => $data['created_by_alias'],
			'created'     => $data['created'],
			'modified'    => $data['modified'],
			'publishUp'   => $data['publish_up'],
			'publishDown' => $data['publish_down'],
			'hits'        => (int) $data['hits'],
			'version'     => isset($data['version']) ? (int) $data['version'] : null,
			'metadesc'    => $data['metadesc'],
			'metakey'     => $data['metakey'],
			'note'        => $data['note'],
			'images'      => isset($data['images']) ? (array) $data['images'] : array(),
			'link'        => 'index.php?option=com_content&view=article&id=' . (int) $data['id'] . '&catid=' . (int) $data['catid'],
			'checkedOut'  => (int) $data['checked_out'] ? User::getInstance((int) $data['checked_out'])->username : null,
		);
	}
}
