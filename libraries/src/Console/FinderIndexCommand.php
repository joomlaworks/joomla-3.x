<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Builds the Smart Search index, optionally purging it first.
 *
 * @since  3.17.0
 */
class FinderIndexCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'finder:index';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Purge and rebuild the index';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Indexes the content for Smart Search. With the "purge" argument the index is emptied first and the search filters are kept. '
		. 'Between batches the indexer pauses to spare the server: by default for the batch time divided by --divisor, '
		. 'or for a fixed number of seconds given with --pause; batches faster than --minproctime seconds are not followed by a pause.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $logged = false;

	/**
	 * @var    CommandIO
	 * @since  3.17.0
	 */
	private $io;

	/**
	 * @var    float
	 * @since  3.17.0
	 */
	private $time;

	/**
	 * Filter ID => taxonomy titles of the filter, kept across a purge
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	private $filters = array();

	/**
	 * @var    string|integer
	 * @since  3.17.0
	 */
	private $pause = 'division';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	private $divisor = 5;

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	private $minimumBatchProcessingTime = 1;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('purge', self::ARGUMENT_OPTIONAL, 'Give "purge" to empty the index before rebuilding it');
		$this->addOption('minproctime', null, self::OPTION_REQUIRED, 'Minimum processing time in seconds, in order to apply a pause', 1);
		$this->addOption('pause', null, self::OPTION_REQUIRED, 'Pausing type ("division") or defined pause time in seconds', 'division');
		$this->addOption('divisor', null, self::OPTION_REQUIRED, 'The divisor of the division: batch-processing time / divisor', 5);
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
		$purge = (string) $io->getArgument('purge');

		if ($purge !== '' && $purge !== 'purge')
		{
			$io->error(sprintf('Unknown argument "%s". Give "purge" or nothing.', $purge));

			return self::INVALID;
		}

		$this->io   = $io;
		$this->time = microtime(true);

		$this->minimumBatchProcessingTime = (int) $io->getOption('minproctime');

		if ((string) $io->getOption('pause') === 'division')
		{
			$this->divisor = (int) $io->getOption('divisor');
		}
		else
		{
			$this->pause = (int) $io->getOption('pause');
		}

		$language = Factory::getLanguage();
		$language->load('finder_cli', JPATH_SITE, null, false, false) || $language->load('finder_cli', JPATH_SITE, null, true);
		$language->load('com_finder', JPATH_ADMINISTRATOR);

		$io->title(Text::_('FINDER_CLI'));

		if ($io->isDryRun())
		{
			if ($purge !== '')
			{
				$io->plan('Empty the Smart Search index (keeping the saved search filters)', array('action' => 'purge'));
			}

			foreach (\JPluginHelper::getPlugin('finder') as $plugin)
			{
				$io->plan(sprintf('Index the content of the "%s" Smart Search plugin', $plugin->name), array('action' => 'index', 'plugin' => $plugin->name));
			}

			return self::SUCCESS;
		}

		@set_time_limit(0);

		// The finder plugins expect the site application, with Smart Search as the active component
		$console = Factory::$application;
		$server  = $_SERVER;

		$this->simulateRequest();

		Factory::$application = CMSApplication::getInstance('site');

		try
		{
			if ($purge !== '')
			{
				// Taxonomy ids will change following a purge/index, so save filter information first
				$this->getFilters();

				if (!$this->purge())
				{
					return self::FAILURE;
				}

				$result = $this->index();
				$this->putFilters();
			}
			else
			{
				$result = $this->index();
			}
		}
		finally
		{
			Factory::$application = $console;
			$_SERVER              = $server;
			Uri::reset();

			// The site application registered the command line's in-memory session, e.g. for Who's Online; remove it again
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__session'))
				->where($db->quoteName('session_id') . ' = ' . $db->quote(Factory::getSession()->getId()));

			try
			{
				$db->setQuery($query)->execute();
			}
			catch (\RuntimeException $e)
			{
				// Not worth failing the indexing for
			}
		}

		$io->text(Text::sprintf('FINDER_CLI_PROCESS_COMPLETE', round(microtime(true) - $this->time, 3)));
		$io->text(Text::sprintf('FINDER_CLI_PEAK_MEMORY_USAGE', number_format(memory_get_peak_usage(true))));

		return $result;
	}

	/**
	 * Make the request look like one for the site's home page, as content and finder plugins (including third-party ones)
	 * often read the request's server variables, which the command line doesn't have, and build URLs from them.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function simulateRequest()
	{
		$liveSite = (string) Factory::getConfig()->get('live_site');
		$host     = (string) parse_url($liveSite, PHP_URL_HOST);
		$port     = parse_url($liveSite, PHP_URL_PORT);
		$path     = rtrim((string) parse_url($liveSite, PHP_URL_PATH), '/');

		if (!isset($_SERVER['HTTP_HOST']))
		{
			$_SERVER['HTTP_HOST'] = $host !== '' ? $host . ($port ? ':' . $port : '') : 'domain.com';
		}

		if (!isset($_SERVER['HTTPS']) && parse_url($liveSite, PHP_URL_SCHEME) === 'https')
		{
			$_SERVER['HTTPS'] = 'on';
		}

		// On the command line these name the CLI script, which would put "/cli" into the URLs built from them
		$_SERVER['SCRIPT_NAME']     = $path . '/index.php';
		$_SERVER['PHP_SELF']        = $path . '/index.php';
		$_SERVER['SCRIPT_FILENAME'] = JPATH_ROOT . '/index.php';
		$_SERVER['REQUEST_URI']     = $path . '/';
		$_SERVER['REQUEST_METHOD']  = 'GET';
		$_SERVER['QUERY_STRING']    = '';

		if (!isset($_SERVER['SERVER_NAME']))
		{
			$_SERVER['SERVER_NAME'] = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
		}

		if (!isset($_SERVER['REMOTE_ADDR']))
		{
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		}

		Uri::reset();
	}

	/**
	 * Run the indexer.
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	private function index()
	{
		\JLoader::register('FinderIndexer', JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/indexer.php');

		$config = Factory::getConfig();
		$config->set('caching', 0);
		$config->set('cache_handler', 'file');

		\FinderIndexer::resetState();

		PluginHelper::importPlugin('system');
		PluginHelper::importPlugin('finder');

		$this->io->text(Text::_('FINDER_CLI_STARTING_INDEXER'));
		\JEventDispatcher::getInstance()->trigger('onStartIndex');

		$state = \FinderIndexer::getState();

		$this->io->text(Text::_('FINDER_CLI_SETTING_UP_PLUGINS'));
		\JEventDispatcher::getInstance()->trigger('onBeforeIndex');

		$this->io->text(Text::sprintf('FINDER_CLI_SETUP_ITEMS', $state->totalItems, round(microtime(true) - $this->time, 3)));

		$batches = (int) ceil((int) $state->totalItems / $state->batchSize);
		$batches = $batches === 0 ? 1 : $batches;

		try
		{
			for ($i = 0; $i < $batches; $i++)
			{
				$batchTime          = microtime(true);
				$state->batchOffset = 0;

				\JEventDispatcher::getInstance()->trigger('onBuildIndex');

				$processingTime = round(microtime(true) - $batchTime, 3);
				$this->io->text(Text::sprintf('FINDER_CLI_BATCH_COMPLETE', $i + 1, $processingTime));

				if ($this->pause === 0)
				{
					continue;
				}

				$skip  = $processingTime < $this->minimumBatchProcessingTime;
				$pause = 0;

				if ($this->pause === 'division' && $this->divisor > 0)
				{
					$pause = $skip ? 1 : round($processingTime / $this->divisor);
				}
				elseif ($this->pause > 0)
				{
					$pause = $this->pause;
				}

				if ($pause > 0 && !$skip)
				{
					$this->io->text(Text::sprintf('FINDER_CLI_BATCH_PAUSING', $pause));
					sleep($pause);
					$this->io->text(Text::_('FINDER_CLI_BATCH_CONTINUING'));
				}

				if ($skip)
				{
					$this->io->text(
						Text::sprintf('FINDER_CLI_SKIPPING_PAUSE_LOW_BATCH_PROCESSING_TIME', $processingTime, $this->minimumBatchProcessingTime)
					);
				}
			}
		}
		catch (\Exception $e)
		{
			$this->io->error($e->getMessage());
			\FinderIndexer::resetState();

			return self::FAILURE;
		}

		\FinderIndexer::resetState();

		return self::SUCCESS;
	}

	/**
	 * Empty the index.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	private function purge()
	{
		$this->io->text(Text::_('FINDER_CLI_INDEX_PURGE'));

		/** @var \FinderModelIndex $model */
		$model = $this->getAdministratorModel('com_finder', 'Index', 'FinderModel');

		if (!$model->purge())
		{
			$this->io->error(Text::_('FINDER_CLI_INDEX_PURGE_FAILED') . ' ' . $model->getError());

			return false;
		}

		$this->io->text(Text::_('FINDER_CLI_INDEX_PURGE_SUCCESS'));

		return true;
	}

	/**
	 * Save the taxonomy titles of each search filter, since a purge changes the taxonomy IDs.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function getFilters()
	{
		$this->io->text(Text::_('FINDER_CLI_SAVE_FILTERS'));

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('filter_id', 'title', 'data')))
			->from($db->quoteName('#__finder_filters'));
		$filters = $db->setQuery($query)->loadObjectList();

		foreach ($filters as $filter)
		{
			$ids = array_filter(array_map('intval', explode(',', (string) $filter->data)));

			if (!$ids)
			{
				continue;
			}

			$query = $db->getQuery(true)
				->select('t.title, p.title AS parent')
				->from($db->quoteName('#__finder_taxonomy') . ' AS t')
				->leftJoin($db->quoteName('#__finder_taxonomy') . ' AS p ON p.id = t.parent_id')
				->where($db->quoteName('t.id') . ' IN (' . implode(',', $ids) . ')');

			foreach ($db->setQuery($query)->loadObjectList() as $taxonomy)
			{
				$this->filters[$filter->filter_id][] = array(
					'filter' => $filter->title,
					'title'  => $taxonomy->title,
					'parent' => $taxonomy->parent,
				);
			}
		}

		$this->io->text(Text::sprintf('FINDER_CLI_SAVE_FILTER_COMPLETED', count($filters)));
	}

	/**
	 * Point the saved search filters to the new taxonomy IDs.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function putFilters()
	{
		$this->io->text(Text::_('FINDER_CLI_RESTORE_FILTERS'));

		$db = Factory::getDbo();

		foreach ($this->filters as $filterId => $filter)
		{
			$ids = array();

			foreach ($filter as $element)
			{
				$query = $db->getQuery(true)
					->select('t.id')
					->from($db->quoteName('#__finder_taxonomy') . ' AS t')
					->leftJoin($db->quoteName('#__finder_taxonomy') . ' AS p ON p.id = t.parent_id')
					->where($db->quoteName('t.title') . ' = ' . $db->quote($element['title']))
					->where($db->quoteName('p.title') . ' = ' . $db->quote($element['parent']));
				$taxonomy = $db->setQuery($query)->loadResult();

				if ($taxonomy)
				{
					$ids[] = $taxonomy;
				}
				else
				{
					$this->io->warning(Text::sprintf('FINDER_CLI_FILTER_RESTORE_WARNING', $element['parent'], $element['title'], $element['filter']));
				}
			}

			$query = $db->getQuery(true)
				->update($db->quoteName('#__finder_filters'))
				->set($db->quoteName('data') . ' = ' . $db->quote(implode(',', $ids)))
				->where($db->quoteName('filter_id') . ' = ' . (int) $filterId);
			$db->setQuery($query)->execute();
		}

		$this->io->text(Text::sprintf('FINDER_CLI_RESTORE_FILTER_COMPLETED', count($this->filters)));
	}
}
