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
 * Shows an article, with its text.
 *
 * @since  3.17.0
 */
class ArticleGetCommand extends AbstractArticleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'article:get';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show an article';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows an article: its fields and its text (HTML; <hr id="system-readmore" /> marks where the intro ends).';

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
		parent::configure();
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The article ID');
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
		$data = $this->loadArticle($io, $this->getModel('com_content', 'Article', 'ContentModel'), $io->getArgument('id'));

		if ($data === false)
		{
			return self::NOT_FOUND;
		}

		$article = $this->describeArticle($data);
		$text    = (string) $data['articletext'];

		$io->title('Article ' . $article['id']);

		$list = $article;
		unset($list['images']);
		$list['tags'] = implode(', ', $article['tags']);
		$io->definitionList($list);
		$io->setData('images', $article['images']);
		$io->setData('tags', $article['tags']);
		$io->setData('text', $text);
		$io->writeln();
		$io->writeln($text);

		return self::SUCCESS;
	}
}
