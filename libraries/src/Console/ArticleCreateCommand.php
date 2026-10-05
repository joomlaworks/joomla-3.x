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
 * Creates an article.
 *
 * @since  3.17.0
 */
class ArticleCreateCommand extends AbstractArticleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'article:create';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Create an article';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Creates an article, saved as Content: Articles saves it (Joomla\'s text filters, content plugins, versions and Smart Search '
		. 'apply). It\'s unpublished unless --state=published is given, so it can be checked first. --title and --category are required; the '
		. 'alias is made from the title. The author is the acting user (--as) unless --author is given.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addFieldOptions();
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
		$io->title('Create Article');

		if ((string) $io->getOption('title') === '' || (string) $io->getOption('category') === '')
		{
			$io->error('--title and --category are required.');

			return self::INVALID;
		}

		$model = $this->getModel('com_content', 'Article', 'ContentModel');
		$data  = array(
			'id'          => 0,
			'alias'       => '',
			'state'       => 0,
			'featured'    => 0,
			'access'      => (int) Factory::getConfig()->get('access', 1),
			'language'    => '*',
			'articletext' => '',
			'created_by'  => (int) $this->user->id,
			'images'      => array(),
			'tags'        => array(),
		);

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		if (!$this->user->authorise('core.create', 'com_content.category.' . (int) $data['catid']))
		{
			$io->error(sprintf('The user "%s" may not create articles in this category.', $this->user->username));

			return self::REFUSED;
		}

		// Without the permission the form would quietly drop these, so the article wouldn't be what was asked for
		if (((int) $data['state'] !== 0 || (int) $data['featured'] !== 0) && !$this->user->authorise('core.edit.state', 'com_content.category.' . (int) $data['catid']))
		{
			$io->error(sprintf('The user "%s" may not publish or feature articles in this category; create it unpublished and not featured.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$this->planChanges($io, sprintf('Create the article "%s"', $data['title']), array_diff_key($data, array('id' => 0, 'images' => 0, 'tags' => 0)));

			return self::SUCCESS;
		}

		$article = $this->describeArticle($this->loadArticle($io, $this->getModel('com_content', 'Article', 'ContentModel'), $id));

		foreach ($article as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Article "%s" created with ID %d (%s).', $article['title'], $id, $article['state']));

		return self::SUCCESS;
	}
}
