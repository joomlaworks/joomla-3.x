<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * Changes an article.
 *
 * @since  3.17.0
 */
class ArticleUpdateCommand extends AbstractArticleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'article:update';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Change an article';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Changes the given fields of an article and leaves the others as they are, saved as Content: Articles saves it (a new '
		. 'version is kept when versions are on). An article someone has open for editing is refused unless --force is given.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The article ID');
		$this->addFieldOptions();
		$this->addOption('force', null, self::OPTION_NONE, 'Change the article even when someone has it open for editing');
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
		$io->title('Change Article');

		$model = $this->getModel('com_content', 'Article', 'ContentModel');
		$data  = $this->loadArticle($io, $model, $io->getArgument('id'));

		if ($data === false)
		{
			return self::NOT_FOUND;
		}

		if (!$this->checkNotCheckedOut($io, (object) $data))
		{
			return self::REFUSED;
		}

		$current = $data;

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		$asset = 'com_content.article.' . (int) $data['id'];

		if (!$this->user->authorise('core.edit', $asset)
			&& !($this->user->authorise('core.edit.own', $asset) && (int) $current['created_by'] === (int) $this->user->id))
		{
			$io->error(sprintf('The user "%s" may not edit this article.', $this->user->username));

			return self::REFUSED;
		}

		if (((int) $data['state'] !== (int) $current['state'] || (int) $data['featured'] !== (int) $current['featured'])
			&& !$this->user->authorise('core.edit.state', $asset))
		{
			$io->error(sprintf('The user "%s" may not change the state of this article.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$changes = array_diff_key($data, array('images' => 0, 'tags' => 0));
			$changes['tags'] = implode(',', (array) $data['tags']);
			$current['tags'] = implode(',', (array) $current['tags']);
			$this->planChanges($io, sprintf('Change the article %d "%s"', $current['id'], $current['title']), $changes, $current);

			return self::SUCCESS;
		}

		$article = $this->describeArticle($this->loadArticle($io, $this->getModel('com_content', 'Article', 'ContentModel'), $id));

		foreach ($article as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Article %d "%s" saved.', $id, $article['title']));

		return self::SUCCESS;
	}
}
