<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Redirect component helper.
 *
 * @since  1.6
 */
class RedirectHelper
{
    public static $extension = 'com_redirect';

    /**
     * The ignore list of new sites: requests browsers, devices and scanners make for things a Joomla site never has (the
     * .well-known files, iOS icons, source maps, other software's admin and debug paths)
     *
     * @var    string
     * @since  3.18.0
     */
    public const DEFAULT_IGNORE = '.well-known/' . "\n"
        . 'apple-touch-icon' . "\n"
        . '/\\.(?:js|css)\\.map(?:\\?|$)/i' . "\n"
        . '/^\\/(?:vendor|actuator|_ignition|telescope|autodiscover|owa|boaform|hnap1|server-status)(?:\\/|$)/i';

    /**
     * Configure the Linkbar.
     *
     * @param   string  $vName  The name of the active view.
     *
     * @return  void
     *
     * @since   1.6
     */
    public static function addSubmenu($vName)
    {
        JHtmlSidebar::addEntry(JText::_('COM_REDIRECT_SUBMENU_LINKS'), 'index.php?option=com_redirect&view=links', $vName === 'links');
        JHtmlSidebar::addEntry(JText::_('COM_REDIRECT_SUBMENU_ERRORS'), 'index.php?option=com_redirect&view=errors', $vName === 'errors');

        if (JFactory::getUser()->authorise('core.admin', 'com_redirect')) {
            JHtmlSidebar::addEntry(JText::_('COM_REDIRECT_SUBMENU_IMPORT'), 'index.php?option=com_redirect&view=import', $vName === 'import');
        }
    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @return  JObject
     *
     * @deprecated  3.2  Use JHelperContent::getActions() instead
     */
    public static function getActions()
    {
        // Log usage of deprecated function
        try {
            JLog::add(
                sprintf('%s() is deprecated. Use JHelperContent::getActions() with new arguments order instead.', __METHOD__),
                JLog::WARNING,
                'deprecated'
            );
        } catch (RuntimeException $exception) {
            // Informational log only
        }

        // Get list of actions
        return JHelperContent::getActions('com_redirect');
    }

    /**
     * Returns an array of standard published state filter options.
     *
     * @return  array  An array containing the options
     *
     * @since   1.6
     */
    public static function publishedOptions()
    {
        // Build the active state filter options.
        $options   = array();
        $options[] = JHtml::_('select.option', '*', 'JALL');
        $options[] = JHtml::_('select.option', '1', 'JENABLED');
        $options[] = JHtml::_('select.option', '0', 'JDISABLED');
        $options[] = JHtml::_('select.option', '2', 'JARCHIVED');
        $options[] = JHtml::_('select.option', '-2', 'JTRASHED');

        return $options;
    }

    /**
     * Gets the redirect system plugin extension id.
     *
     * @return  integer  The redirect system plugin extension id.
     *
     * @since   3.6.0
     */
    public static function getRedirectPluginId()
    {
        $db     = JFactory::getDbo();
        $result = 0;
        $query  = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('redirect'));
        $db->setQuery($query);

        try {
            $result = (int) $db->loadResult();
        } catch (RuntimeException $e) {
            JError::raiseWarning(500, $e->getMessage());
        }

        return $result;
    }

    /**
     * Warns when the plugin which does the redirecting is disabled, with a link to it for users who may change it.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public static function warnIfPluginDisabled()
    {
        if (JPluginHelper::isEnabled('system', 'redirect')) {
            return;
        }

        $name = JText::_('COM_REDIRECT_SYSTEM_PLUGIN');
        $id   = static::getRedirectPluginId();

        if ($id && JFactory::getUser()->authorise('core.edit.state', 'com_plugins')) {
            $name = '<a class="alert-link" href="' . JRoute::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $id . '&return=' . urlencode(base64_encode(JUri::getInstance()->toString()))) . '">' . $name . '</a>';
        }

        JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_REDIRECT_PLUGIN_MODAL_DISABLED', $name), 'error');
    }

    /**
     * Checks whether errors are collected, for the output message
     *
     * @return  boolean
     *
     * @since   3.4
     */
    public static function collectUrlsEnabled()
    {
        return JPluginHelper::isEnabled('system', 'redirect') && (bool) JComponentHelper::getParams('com_redirect')->get('collect_errors', 0);
    }

    /**
     * The code a redirect is sent with: its own (301 or 302), else the component's default.
     *
     * @param   integer  $code  The entry's code, 0 for the default
     *
     * @return  integer
     *
     * @since   3.18.0
     */
    public static function getCode($code)
    {
        $code = (int) $code;

        if ($code !== 301 && $code !== 302) {
            $code = (int) JComponentHelper::getParams('com_redirect')->get('default_code', 301);
        }

        return $code === 302 ? 302 : 301;
    }

    /**
     * The codes an entry may choose: the default (named), 301 and 302.
     *
     * @return  array  Options for JHtml select lists
     *
     * @since   3.18.0
     */
    public static function getCodeOptions()
    {
        return array(
            JHtml::_('select.option', '0', JText::sprintf('COM_REDIRECT_FIELD_CODE_DEFAULT', JText::_('COM_REDIRECT_CODE_' . static::getCode(0)))),
            JHtml::_('select.option', '301', JText::_('COM_REDIRECT_CODE_301')),
            JHtml::_('select.option', '302', JText::_('COM_REDIRECT_CODE_302')),
        );
    }

    /**
     * This site's host without "www.", lower case.
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected static function getSiteHost()
    {
        return preg_replace('/^www\./', '', strtolower(JUri::getInstance(JUri::root())->getHost()));
    }

    /**
     * Splits an address into its path relative to the site and its query: the site's own scheme and host (with or without
     * "www."), the site's folder, leading and trailing slashes, "index.php/" and the fragment are left out. Percent-encoded
     * characters are decoded, as the old entries stored them.
     *
     * @param   string  $url  The address, absolute or relative to the site
     *
     * @return  array|false  array(path, query), false for an address on another host
     *
     * @since   3.18.0
     */
    public static function splitSource($url)
    {
        $url = trim((string) $url);

        if (($hash = strpos($url, '#')) !== false) {
            $url = substr($url, 0, $hash);
        }

        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//([^/?\#]*)(.*)$#is', $url, $match)) {
            $host = strtolower(preg_replace('/:\d+$/', '', preg_replace('/^[^@]*@/', '', $match[1])));

            if (preg_replace('/^www\./', '', $host) !== static::getSiteHost()) {
                return false;
            }

            $url = $match[2];
        }

        $query = '';

        if (($mark = strpos($url, '?')) !== false) {
            $query = (string) substr($url, $mark + 1);
            $url   = substr($url, 0, $mark);
        }

        $path = rawurldecode($url);
        $root = JUri::root(true);

        if ($root !== '' && ($path === $root || strpos($path, $root . '/') === 0)) {
            $path = (string) substr($path, strlen($root));
        }

        $path = trim($path, '/');

        if ($path === 'index.php' || strpos($path, 'index.php/') === 0) {
            $path = trim(substr($path, 9), '/');
        }

        return array($path, rawurldecode($query));
    }

    /**
     * An address in the form entries store their source in: relative to the site, decoded, "path" or "path?query".
     *
     * @param   string  $url  The address
     *
     * @return  string|false  False for an address on another host
     *
     * @since   3.18.0
     */
    public static function normaliseSource($url)
    {
        $parts = static::splitSource($url);

        if ($parts === false) {
            return false;
        }

        return $parts[1] === '' ? $parts[0] : $parts[0] . '?' . $parts[1];
    }

    /**
     * The key of a normalised source: lookups ignore case.
     *
     * @param   string  $source  The normalised source
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function hash($source)
    {
        return md5(Joomla\String\StringHelper::strtolower((string) $source));
    }

    /**
     * The keys an address matches: its path alone, which entries without a query match whatever the query, and, when it
     * has one, its path with the query.
     *
     * @param   string  $url  The address
     *
     * @return  array  Hash => true when it's the path with its query, false for the path alone
     *
     * @since   3.18.0
     */
    public static function getCandidates($url)
    {
        $parts = static::splitSource($url);

        if ($parts === false) {
            return array();
        }

        $candidates = array(static::hash($parts[0]) => false);

        if ($parts[1] !== '') {
            $candidates[static::hash($parts[0] . '?' . $parts[1])] = true;
        }

        return $candidates;
    }

    /**
     * A target as entries store it: an address on this site relative to it (its query and fragment kept as given), an
     * address elsewhere absolute, http(s) only.
     *
     * @param   string  $url  The address
     *
     * @return  string|false  False for an address that can't be a target
     *
     * @since   3.18.0
     */
    public static function normaliseTarget($url)
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        if (preg_match('#^([a-z][a-z0-9+.-]*:)?//([^/?\#]*)(.*)$#is', $url, $match)) {
            $scheme = strtolower($match[1]);

            if (($scheme !== '' && $scheme !== 'http:' && $scheme !== 'https:') || $match[2] === '') {
                return false;
            }

            $host = strtolower(preg_replace('/:\d+$/', '', preg_replace('/^[^@]*@/', '', $match[2])));

            if (preg_replace('/^www\./', '', $host) !== static::getSiteHost()) {
                return ($scheme === '' ? 'https:' : '') . $url;
            }

            $url = $match[3];
        } elseif (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
            // mailto:, javascript: and the like
            return false;
        }

        $root = JUri::root(true);

        if ($root !== '' && ($url === $root || strpos($url, $root . '/') === 0 || strpos($url, $root . '?') === 0 || strpos($url, $root . '#') === 0)) {
            $url = (string) substr($url, strlen($root));
        }

        return ltrim($url, '/');
    }

    /**
     * Whether a stored target is an address elsewhere.
     *
     * @param   string  $target  The stored target
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public static function isExternal($target)
    {
        return (bool) preg_match('#^https?://#i', (string) $target);
    }

    /**
     * The address a visitor is sent to: a target on this site from the site's root, and the request's query added when the
     * entry has none of its own.
     *
     * @param   string  $target  The stored target
     * @param   string  $query   The request's query to pass on, empty for none
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function getDestination($target, $query = '')
    {
        $url = static::isExternal($target) ? $target : JUri::root() . $target;

        if ($query === '') {
            return $url;
        }

        $fragment = '';

        if (($hash = strpos($url, '#')) !== false) {
            $fragment = substr($url, $hash);
            $url      = substr($url, 0, $hash);
        }

        return $url . (strpos($url, '?') === false ? '?' : '&') . $query . $fragment;
    }

    /**
     * Follows a target through the other entries (enabled or disabled) to find a loop back to the source or a chain of
     * redirects.
     *
     * @param   string   $source  The normalised source being saved
     * @param   string   $target  The normalised target
     * @param   integer  $id      The entry being saved, left out of the search
     *
     * @return  string  'loop', 'chain' or ''
     *
     * @since   3.18.0
     */
    public static function checkLoop($source, $target, $id = 0)
    {
        $db     = JFactory::getDbo();
        $start  = static::hash($source);
        $seen   = array($start => true);
        $result = '';

        for ($step = 0; $step < 20; $step++) {
            if (static::isExternal($target)) {
                break;
            }

            $candidates = static::getCandidates($target);

            if (isset($candidates[$start])) {
                return 'loop';
            }

            $query = $db->getQuery(true)
                ->select($db->quoteName(array('id', 'old_url', 'old_url_hash', 'new_url')))
                ->from($db->quoteName('#__redirect_links'))
                ->where($db->quoteName('old_url_hash') . ' IN (' . implode(',', array_map(array($db, 'quote'), array_keys($candidates))) . ')')
                ->where($db->quoteName('published') . ' IN (0, 1)')
                ->where($db->quoteName('id') . ' <> ' . (int) $id);
            $rows = $db->setQuery($query)->loadObjectList('old_url_hash');

            if (!$rows) {
                break;
            }

            $result = 'chain';

            // The entry with the query is more specific than the path alone
            krsort($candidates);
            $next = null;

            foreach ($candidates as $hash => $withQuery) {
                if (isset($rows[$hash])) {
                    $next = $rows[$hash];
                    break;
                }
            }

            if (isset($seen[$next->old_url_hash])) {
                return 'loop';
            }

            $seen[$next->old_url_hash] = true;
            $parts  = static::splitSource($target);
            $passOn = strpos($next->old_url, '?') === false && $parts !== false ? $parts[1] : '';
            $target = $passOn === '' ? $next->new_url : $next->new_url . (strpos($next->new_url, '?') === false ? '?' : '&') . $passOn;
        }

        return $result;
    }

    /**
     * Whether the site has an enabled redirect; kept in the cache, so pages don't ask the database while there are none.
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public static function hasEnabledRedirects()
    {
        $cache = static::getCache();
        $value = $cache->get('enabled');

        if ($value === '0' || $value === '1') {
            return $value === '1';
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('1')
            ->from($db->quoteName('#__redirect_links'))
            ->where($db->quoteName('published') . ' = 1');
        $value = $db->setQuery($query, 0, 1)->loadResult() ? '1' : '0';

        $cache->store($value, 'enabled');

        return $value === '1';
    }

    /**
     * Forgets whether the site has enabled redirects; called whenever entries change.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public static function cleanCache()
    {
        try {
            static::getCache()->clean('com_redirect');
        } catch (Exception $e) {
            // The flag expires by itself
        }
    }

    /**
     * The site's cache for this component, whatever Global Configuration's caching and the client.
     *
     * @return  JCache
     *
     * @since   3.18.0
     */
    protected static function getCache()
    {
        static $cache;

        if ($cache === null) {
            $cache = new JCache(array(
                'defaultgroup' => 'com_redirect',
                'caching'      => true,
                'lifetime'     => 1440,
                'cachebase'    => JFactory::getConfig()->get('cache_path') ?: JPATH_SITE . '/cache',
            ));
        }

        return $cache;
    }

    /**
     * The lines of the ignore list: plain text matching anywhere, * matching any characters, /.../ a regular expression.
     *
     * @param   string  $list  The option's text, null for the component's
     *
     * @return  array
     *
     * @since   3.18.0
     */
    public static function getIgnoreList($list = null)
    {
        if ($list === null) {
            // Registry::get() gives the default for an emptied list
            $params = JComponentHelper::getParams('com_redirect');
            $list   = $params->exists('ignore') ? (string) $params->toObject()->ignore : static::DEFAULT_IGNORE;
        }

        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $list)), 'strlen'));
    }

    /**
     * Whether a line of the ignore list is a regular expression, and a valid one.
     *
     * @param   string   $line   The line
     * @param   boolean  $valid  Set to whether the expression compiles
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public static function isPattern($line, &$valid = null)
    {
        if (strlen($line) < 3 || !preg_match('#^/.*/[imsxuU]*$#s', $line)) {
            $valid = true;

            return false;
        }

        $valid = @preg_match($line, '') !== false;

        return true;
    }

    /**
     * Whether an address matches the ignore list.
     *
     * @param   string  $url    The address
     * @param   array   $lines  The ignore list
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public static function isIgnored($url, array $lines)
    {
        foreach ($lines as $line) {
            if (static::isPattern($line, $valid)) {
                if ($valid && preg_match($line, $url)) {
                    return true;
                }
            } elseif (strpos($line, '*') !== false) {
                $pattern = '#' . str_replace('\\*', '.*', preg_quote($line, '#')) . '#i';

                if (preg_match($pattern, $url)) {
                    return true;
                }
            } elseif (stripos($url, $line) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes collected errors older than the component's limit (by their last hit) and all but the newest of them.
     *
     * @return  integer  The number of entries deleted
     *
     * @since   3.18.0
     */
    public static function purgeErrors()
    {
        $params  = JComponentHelper::getParams('com_redirect');
        $days    = (int) $params->get('errors_days', 30);
        $keep    = (int) $params->get('errors_keep', 10000);
        $db      = JFactory::getDbo();
        $deleted = 0;

        if ($days > 0) {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__redirect_errors'))
                ->where($db->quoteName('last_hit') . ' < ' . $db->quote(JFactory::getDate('-' . $days . ' days')->toSql()));
            $db->setQuery($query)->execute();
            $deleted += (int) $db->getAffectedRows();
        }

        if ($keep > 0) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__redirect_errors'));

            if ((int) $db->setQuery($query)->loadResult() > $keep) {
                $query = $db->getQuery(true)
                    ->select($db->quoteName('last_hit'))
                    ->from($db->quoteName('#__redirect_errors'))
                    ->order($db->quoteName('last_hit') . ' DESC, ' . $db->quoteName('id') . ' DESC');
                $oldest = $db->setQuery($query, $keep - 1, 1)->loadResult();

                if ($oldest) {
                    $query = $db->getQuery(true)
                        ->delete($db->quoteName('#__redirect_errors'))
                        ->where($db->quoteName('last_hit') . ' < ' . $db->quote($oldest));
                    $db->setQuery($query)->execute();
                    $deleted += (int) $db->getAffectedRows();
                }
            }
        }

        return $deleted;
    }
}
