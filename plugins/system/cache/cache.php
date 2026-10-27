<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.cache
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Joomla! Page Cache Plugin.
 *
 * @since  1.5
 */
class PlgSystemCache extends JPlugin
{
	/**
	 * Cache instance.
	 *
	 * @var    JCache
	 * @since  1.5
	 */
	public $_cache;

	/**
	 * Cache key
	 *
	 * @var    string
	 * @since  3.0
	 */
	public $_cache_key;

	/**
	 * Whether the address has query parameters the page cache ignores (tracking parameters)
	 *
	 * @var    boolean
	 * @since  3.18.0
	 */
	protected $ignoredParameters = false;

	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.8.0
	 */
	protected $app;

	/**
	 * How long, in seconds, the home page and the other pages stay fresh, and stale pages may still be served (0 each when
	 * the plugin's times aren't set: the cache lifetime, as before)
	 *
	 * @var    integer[]
	 * @since  3.18.0
	 */
	protected $times = array('home' => 0, 'pages' => 0, 'stale' => 0);

	/**
	 * The exception handler in place before this plugin's
	 *
	 * @var    callable|null
	 * @since  3.18.0
	 */
	protected $previousExceptionHandler;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe.
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   1.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		// Get the application if not done by JPlugin.
		if (!isset($this->app))
		{
			$this->app = JFactory::getApplication();
		}

		// Set the cache options.
		$options = array(
			'defaultgroup' => 'page',
			'browsercache' => $this->params->get('browsercache', 0),
			'caching'      => false,
		);

		/*
		 * The page cache's own times (seconds; empty: Global Configuration's Cache Time). Each page is stored with the moment
		 * it stops being fresh, and kept for the longest fresh time plus the stale time.
		 */
		$global = max(1, (int) $this->app->get('cachetime', 15)) * 60;
		$home   = (int) $this->params->get('pagecache_home', 0);
		$pages  = (int) $this->params->get('pagecache_pages', 0);
		$stale  = max(0, (int) $this->params->get('pagecache_stale', 0));

		if ($home > 0 || $pages > 0 || $stale > 0)
		{
			$this->times = array('home' => $home > 0 ? $home : $global, 'pages' => $pages > 0 ? $pages : $global, 'stale' => $stale);

			$options['lifetime'] = (int) ceil((max($this->times['home'], $this->times['pages']) + $stale) / 60);
		}

		// Instantiate cache with previous options and create the cache key identifier.
		$this->_cache     = JCache::getInstance('page', $options);
		$this->_cache_key = JUri::getInstance()->toString();

		if ($stale > 0)
		{
			$this->_cache->setStaleTime($stale);

			// A page which fails to render gets its stale copy instead of the error page
			if ($this->app->isClient('site'))
			{
				$this->previousExceptionHandler = set_exception_handler(array($this, 'handleException'));
				JError::setErrorHandling(E_ERROR, 'callback', array($this, 'handleError'));
			}
		}
	}

	/**
	 * Serves the stale copy of the page instead of an error page, when this request was making it again and failed with a
	 * server error (not a 4xx: a deleted article must give its 404). The error is still written to PHP's error log.
	 *
	 * @param   Exception|Throwable  $error  The error
	 *
	 * @return  boolean  False when there's no stale copy to serve
	 *
	 * @since   3.18.0
	 */
	protected function serveStaleCopy($error)
	{
		$copy = $this->_cache->getStaleCopy();
		$code = (int) $error->getCode();

		if ($copy === null || ($code >= 400 && $code < 500))
		{
			return false;
		}

		error_log(sprintf('Page Cache served a stale copy of %s after an error: %s', $this->_cache_key, $error->getMessage()));

		while (ob_get_level())
		{
			ob_end_clean();
		}

		// The page as it was: a 200 with its stored headers (those set before rendering, e.g. security headers, stay)
		$this->app->setHeader('status', 200, true);
		$this->app->setBody(JCache::getWorkarounds($copy));
		$this->keepBrowserCacheTime();

		echo $this->app->toString((bool) $this->app->get('gzip'));

		$this->app->close();

		return true;
	}

	/**
	 * Uncaught exceptions: the stale copy, else the handler before this one.
	 *
	 * @param   Exception|Throwable  $exception  The exception
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public function handleException($exception)
	{
		if ($this->serveStaleCopy($exception))
		{
			return;
		}

		if ($this->previousExceptionHandler)
		{
			call_user_func($this->previousExceptionHandler, $exception);

			return;
		}

		Joomla\CMS\Exception\ExceptionHandler::handleException($exception);
	}

	/**
	 * JError's errors (JError::raiseError()): the stale copy, else Joomla's error page.
	 *
	 * @param   JException  $error  The error
	 *
	 * @return  mixed
	 *
	 * @since   3.18.0
	 */
	public function handleError($error)
	{
		if ($this->serveStaleCopy($error))
		{
			return null;
		}

		return JError::customErrorPage($error);
	}

	/**
	 * Get a cache key for the current page based on the url and possible other factors.
	 *
	 * @return  string
	 *
	 * @since   3.7
	 */
	protected function getCacheKey()
	{
		static $key;

		if (!$key)
		{
			JPluginHelper::importPlugin('pagecache');

			$parts = JEventDispatcher::getInstance()->trigger('onPageCacheGetKey');

			// Without the parameters links get from analytics and social services, which never change the page
			list($url, $this->ignoredParameters) = JCache::withoutIgnoredParameters(JUri::getInstance()->toString());

			$parts[] = $url;

			$key = md5(serialize($parts));
		}

		return $key;
	}

	/**
	 * After Initialise Event.
	 * Checks if URL exists in cache, if so dumps it directly and closes.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onAfterInitialise()
	{
		if ($this->app->isClient('administrator') || $this->app->get('offline', '0') || $this->app->getMessageQueue())
		{
			return;
		}

		// If any pagecache plugins return false for onPageCacheSetCaching, do not use the cache.
		JPluginHelper::importPlugin('pagecache');

		$results = JEventDispatcher::getInstance()->trigger('onPageCacheSetCaching');
		$caching = !in_array(false, $results, true);

		if ($caching && JFactory::getUser()->guest && $this->app->input->getMethod() === 'GET')
		{
			$this->_cache->setCaching(true);
		}

		$data = $this->_cache->get($this->getCacheKey());

		/*
		 * An address with tracking parameters gets the page of the address without them, but its own page is never stored:
		 * it may hold those parameters (in links built from the current address), which other visitors would then get
		 */
		if ($data === false && $this->ignoredParameters && $this->_cache->getCaching())
		{
			$this->_cache->unlock($this->getCacheKey(), 'page');
			$this->_cache->setCaching(false);
		}

		// If page exist in cache, show cached page.
		if ($data !== false)
		{
			// Set HTML page from cache.
			$this->app->setBody($data);

			// The browser cache time stored with the page, from now
			$this->keepBrowserCacheTime();

			// Dumps HTML page.
			echo $this->app->toString((bool) $this->app->get('gzip'));

			// Mark afterCache in debug and run debug onAfterRespond events.
			// e.g., show Joomla Debug Console if debug is active.
			if (JDEBUG)
			{
				JProfiler::getInstance('Application')->mark('afterCache');
				JEventDispatcher::getInstance()->trigger('onAfterRespond');
			}

			// Closes the application.
			$this->app->close();
		}
	}

	/**
	 * After Render Event.
	 * Verify if current page is not excluded from cache.
	 *
	 * @return   void
	 *
	 * @since   3.9.12
	 */
	public function onAfterRender()
	{
		if ($this->_cache->getCaching() === false)
		{
			return;
		}

		// We need to check if user is guest again here, because auto-login plugins have not been fired before the first aid check.
		// Page is excluded if excluded in plugin settings.
		if (!JFactory::getUser()->guest || $this->app->getMessageQueue() || $this->isExcluded() === true)
		{
			$this->_cache->setCaching(false);

			return;
		}

		// Disable compression before caching the page.
		$this->app->set('gzip', false);

		if ($this->times['home'] > 0)
		{
			$this->_cache->setFreshTime($this->isHomePage() ? $this->times['home'] : $this->times['pages']);
		}

		$this->setBrowserCacheTime();
	}

	/**
	 * Lets browsers keep the page for the time the options give the home page or the other pages. Stored with the page
	 * (headers set after the cache looked for it are), so a cache hit sends the same.
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	protected function setBrowserCacheTime()
	{
		if (!$this->params->get('browsercache_send', 0))
		{
			return;
		}

		$seconds = $this->isHomePage() ? (int) $this->params->get('browsercache_home', 60) : (int) $this->params->get('browsercache_pages', 180);

		if ($seconds <= 0)
		{
			return;
		}

		/*
		 * "private": browsers only. Shared caches (CDNs, proxies) would hand every visitor the page of one, with that
		 * visitor's form token in it. The Expires header is set first, so the application doesn't add its own 15 minutes.
		 */
		$this->app->allowCache(true);
		$this->app->setHeader('Cache-Control', 'private, max-age=' . $seconds, true);
		$this->app->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT', true);
	}

	/**
	 * On a cache hit: the browser cache time the page was stored with, its Expires header counted from now. Without one,
	 * the page goes out as before (not kept by browsers).
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	protected function keepBrowserCacheTime()
	{
		foreach ($this->app->getHeaders() as $header)
		{
			if (strtolower($header['name']) === 'cache-control' && preg_match('/^private, max-age=(\d+)$/', $header['value'], $match))
			{
				$this->app->allowCache(true);
				$this->app->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + (int) $match[1]) . ' GMT', true);

				return;
			}
		}
	}

	/**
	 * Whether the page is the home page: the home menu item is active and the request is its own link (Joomla also makes it
	 * active on pages without a menu item of their own, e.g. tags or search). A language's home page counts too.
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	protected function isHomePage()
	{
		$active = $this->app->getMenu()->getActive();

		if (!$active || !$active->home)
		{
			return false;
		}

		$input = $this->app->input;
		$query = $active->query;

		return (isset($query['option']) ? $query['option'] : '') === $input->getCmd('option', '')
			&& (isset($query['view']) ? $query['view'] : '') === $input->getCmd('view', '')
			&& (!isset($query['id']) || (int) $query['id'] === $input->getInt('id'));
	}

	/**
	 * Empties the page cache when this plugin's options are saved, so new browser cache times and exclusions apply at once
	 * (cached pages keep the headers they were stored with).
	 *
	 * @param   string  $context  The context
	 * @param   JTable  $table    The saved extension
	 * @param   boolean $isNew    Whether it is new
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public function onExtensionAfterSave($context, $table, $isNew)
	{
		if ($context !== 'com_plugins.plugin' || !isset($table->element, $table->folder) || $table->element !== 'cache'
			|| $table->folder !== 'system')
		{
			return;
		}

		try
		{
			$cache = new JCache(array('defaultgroup' => 'page'));
			$cache->clean('page');
		}
		catch (Exception $e)
		{
			// The cache empties itself when its entries expire
		}
	}

	/**
	 * After Respond Event.
	 * Stores page in cache.
	 *
	 * @return   void
	 *
	 * @since   1.5
	 */
	public function onAfterRespond()
	{
		if ($this->_cache->getCaching() === false)
		{
			return;
		}

		// Saves current page in cache.
		$this->_cache->store($this->app->getBody(), $this->getCacheKey());
	}

	/**
	 * Check if the page is excluded from the cache or not.
	 *
	 * @return   boolean  True if the page is excluded else false
	 *
	 * @since    3.5
	 */
	protected function isExcluded()
	{
		// Check if menu items have been excluded.
		if ($exclusions = $this->params->get('exclude_menu_items', array()))
		{
			// Get the current menu item.
			$active = $this->app->getMenu()->getActive();

			if ($active && $active->id && in_array((int) $active->id, (array) $exclusions))
			{
				return true;
			}
		}

		// Check if regular expressions are being used.
		if ($exclusions = $this->params->get('exclude', ''))
		{
			// Normalize line endings.
			$exclusions = str_replace(array("\r\n", "\r"), "\n", $exclusions);

			// Split them.
			$exclusions = explode("\n", $exclusions);

			// Gets internal URI.
			$internal_uri	= '/index.php?' . JUri::getInstance()->buildQuery($this->app->getRouter()->getVars());

			// Loop through each pattern.
			if ($exclusions)
			{
				foreach ($exclusions as $exclusion)
				{
					// Make sure the exclusion has some content
					if ($exclusion !== '')
					{
						// Test both external and internal URI
						if (preg_match('#' . $exclusion . '#i', $this->_cache_key . ' ' . $internal_uri, $match))
						{
							return true;
						}
					}
				}
			}
		}

		// If any pagecache plugins return true for onPageCacheIsExcluded, exclude.
		JPluginHelper::importPlugin('pagecache');

		$results = JEventDispatcher::getInstance()->trigger('onPageCacheIsExcluded');

		return in_array(true, $results, true);
	}
}
