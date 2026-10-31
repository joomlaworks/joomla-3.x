<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.redirect
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\String\StringHelper;

/**
 * Plugin class for redirect handling: sends visitors of the Redirects component's addresses on, and collects the addresses
 * which gave an error when the component's options ask for it.
 *
 * @since  1.6
 */
class PlgSystemRedirect extends JPlugin
{
    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  3.4
     */
    protected $autoloadLanguage = false;

    /**
     * The exception handler before this plugin's.
     *
     * @var    callable|null
     * @since  3.6.0
     */
    private static $previousExceptionHandler;

    /**
     * Redirects the request when an enabled entry has its address, and starts collecting errors. Runs early (ordering
     * -95, after Little WAF), so cached pages are redirected too.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function onAfterInitialise()
    {
        $app = JFactory::getApplication();

        if (!$app->isClient('site') || !JComponentHelper::isEnabled('com_redirect')) {
            return;
        }

        JLoader::register('RedirectHelper', JPATH_ADMINISTRATOR . '/components/com_redirect/helpers/redirect.php');

        if (!class_exists('RedirectHelper')) {
            return;
        }

        if (JComponentHelper::getParams('com_redirect')->get('collect_errors', 0)) {
            JError::setErrorHandling(E_ERROR, 'callback', array('PlgSystemRedirect', 'handleError'));
            self::$previousExceptionHandler = set_exception_handler(array('PlgSystemRedirect', 'handleException'));
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }

        try {
            if (!RedirectHelper::hasEnabledRedirects()) {
                return;
            }

            $this->redirect($app);
        } catch (RuntimeException $e) {
            // Without the database the page fails anyway; nothing to redirect
        }
    }

    /**
     * Sends the request on when an enabled entry has its address: an entry with the request's query first, then one for
     * its path alone, which passes the query on.
     *
     * @param   JApplicationCms  $app  The application
     *
     * @return  void
     *
     * @since   3.18.0
     */
    protected function redirect($app)
    {
        $query      = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
        $candidates = RedirectHelper::getCandidates(JUri::getInstance()->getPath() . ($query === '' ? '' : '?' . $query));

        if (!$candidates) {
            return;
        }

        $db     = JFactory::getDbo();
        $select = $db->getQuery(true)
            ->select($db->quoteName(array('id', 'old_url_hash', 'new_url', 'header')))
            ->from($db->quoteName('#__redirect_links'))
            ->where($db->quoteName('old_url_hash') . ' IN (' . implode(',', array_map(array($db, 'quote'), array_keys($candidates))) . ')')
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('id'));
        $rows = $db->setQuery($select)->loadObjectList();

        if (!$rows) {
            return;
        }

        $redirect = null;

        foreach ($rows as $row) {
            if ((string) $row->new_url === '') {
                continue;
            }

            if ($redirect === null || ($candidates[$row->old_url_hash] && !$candidates[$redirect->old_url_hash])) {
                $redirect = $row;
            }
        }

        if ($redirect === null) {
            return;
        }

        $update = $db->getQuery(true)
            ->update($db->quoteName('#__redirect_links'))
            ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
            ->where($db->quoteName('id') . ' = ' . (int) $redirect->id);

        try {
            $db->setQuery($update)->execute();
        } catch (RuntimeException $e) {
            // The count isn't worth a failed redirect
        }

        $destination = RedirectHelper::getDestination($redirect->new_url, $candidates[$redirect->old_url_hash] ? '' : $query);

        $app->redirect($destination, RedirectHelper::getCode($redirect->header));
    }

    /**
     * JError's errors (JError::raiseError()): recorded, then Joomla's error page.
     *
     * @param   JException  $error  The error
     *
     * @return  void
     *
     * @since   1.6
     */
    public static function handleError($error)
    {
        self::doErrorHandling($error);
    }

    /**
     * Uncaught exceptions: recorded, then the handler before this one.
     *
     * @param   Exception|Throwable  $exception  The exception
     *
     * @return  void
     *
     * @since   3.5
     */
    public static function handleException($exception)
    {
        // If this isn't a Throwable then bail out
        if (!($exception instanceof Throwable) && !($exception instanceof Exception)) {
            throw new InvalidArgumentException(
                sprintf('The error handler requires an Exception or Throwable object, a "%s" object was given instead.', get_class($exception))
            );
        }

        self::doErrorHandling($exception);
    }

    /**
     * Records the error and shows the error page.
     *
     * @param   Exception|Throwable  $error  The error
     *
     * @return  void
     *
     * @since   3.5
     */
    private static function doErrorHandling($error)
    {
        try {
            self::recordError($error);
        } catch (Throwable $e) {
            // An error which comes from the database (or a failing log) can't be recorded
        }

        // Proxy to the previous exception handler if available, otherwise just render the error page
        if (self::$previousExceptionHandler) {
            call_user_func_array(self::$previousExceptionHandler, array($error));
        } else {
            JErrorPage::render($error);
        }
    }

    /**
     * Counts the error against its address in the collected errors (a new entry for an address not seen before), unless
     * the address is on the ignore list. One new entry in a hundred also deletes what the component's limits leave out.
     *
     * @param   Exception|Throwable  $error  The error
     *
     * @return  void
     *
     * @since   3.18.0
     */
    private static function recordError($error)
    {
        $code = (int) $error->getCode();

        if ($code === 0) {
            $code = 500;
        }

        if ($code < 400 || $code > 599 || $error instanceof JDatabaseExceptionConnecting) {
            return;
        }

        $query = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
        $url   = RedirectHelper::normaliseSource(JUri::getInstance()->getPath() . ($query === '' ? '' : '?' . $query));

        if ($url === false || RedirectHelper::isIgnored('/' . $url, RedirectHelper::getIgnoreList())) {
            return;
        }

        $db      = JFactory::getDbo();
        $hash    = RedirectHelper::hash($url);
        $now     = JFactory::getDate()->toSql();
        $referer = StringHelper::substr(isset($_SERVER['HTTP_REFERER']) ? trim((string) $_SERVER['HTTP_REFERER']) : '', 0, 2048);
        $update  = $db->getQuery(true)
            ->update($db->quoteName('#__redirect_errors'))
            ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
            ->set($db->quoteName('code') . ' = ' . $code)
            ->set($db->quoteName('last_hit') . ' = ' . $db->quote($now))
            ->where($db->quoteName('url_hash') . ' = ' . $db->quote($hash));

        if ($referer !== '') {
            $update->set($db->quoteName('referer') . ' = ' . $db->quote($referer));
        }

        $db->setQuery($update)->execute();

        if ($db->getAffectedRows() > 0) {
            return;
        }

        $row = (object) array(
            'url'          => StringHelper::substr($url, 0, 2048),
            'url_hash'     => $hash,
            'code'         => $code,
            'referer'      => $referer,
            'hits'         => 1,
            'created_date' => $now,
            'last_hit'     => $now,
        );

        try {
            $db->insertObject('#__redirect_errors', $row);
        } catch (RuntimeException $e) {
            // Another request inserted the same address in between
            return;
        }

        if (mt_rand(1, 100) === 1) {
            RedirectHelper::purgeErrors();
        }
    }
}
