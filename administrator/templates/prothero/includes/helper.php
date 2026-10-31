<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.prothero
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

if (!class_exists('ProtheroHelper', false)) {
    /**
     * Prothero's code: what its page files set up before their markup (prepare()) and the helpers they share.
     *
     * @since  3.18.0
     */
    abstract class ProtheroHelper
    {
        /**
         * Sets the page up and gives what its markup needs: the page is the file being rendered (index, component, login,
         * error; the Control Panel's cpanel.php is index.php since 3.18)
         *
         * @param   JDocument  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        public static function prepare($document)
        {
            $file = basename((string) $document->_file, '.php');

            switch ($file) {
                case 'component':
                    return static::component($document);

                case 'login':
                    return static::login($document);

                case 'error':
                    return static::error($document);

                default:
                    return static::index($document);
            }
        }

        /**
         * The page with the menu, the toolbar and the status bar
         *
         * @param   JDocumentHtml  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function index($document)
        {
            $app   = JFactory::getApplication();
            $lang  = JFactory::getLanguage();
            $input = $app->input;
            $page  = new stdClass();

            $page->user = JFactory::getUser();

            // Output as HTML5
            $document->setHtml5(true);

            // Gets the FrontEnd Main page Uri
            $frontEndUri = JUri::getInstance(JUri::root());
            $frontEndUri->setScheme(((int) $app->get('force_ssl', 0) === 2) ? 'https' : 'http');
            $page->mainPageUri = $frontEndUri->toString();

            // No legacy markup: no scripts and stylesheets for old Internet Explorer versions
            static::dropConditionalAssets($document);

            // The CMS's script (global.js: Bootstrap's dropdowns, collapse, tabs, modals and alerts, without jQuery or Bootstrap's
            // own script, which pages and extensions still get by asking for them)
            JHtml::_('behavior.cms');

            // Add template js
            JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true, 'position' => 'footer'));

            // For the lists' Reorder button on phones (js/template.js)
            JText::script('TPL_PROTHERO_REORDER');
            JText::script('TPL_PROTHERO_REORDER_DONE');

            // Add Stylesheets
            JHtml::_('stylesheet', 'template.css', array('version' => 'auto', 'relative' => true));
            static::preloadIconFont($document);

            if ($document->direction === 'rtl') {
                JHtml::_('stylesheet', 'template-rtl.css', array('version' => 'auto', 'relative' => true));
            }

            // Load specific language related CSS
            JHtml::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));

            // Load custom.css
            JHtml::_('stylesheet', 'custom.css', array('version' => 'auto', 'relative' => true));

            // Detecting Active Variables
            $page->option   = $input->get('option', '');
            $page->view     = $input->get('view', '');
            $page->layout   = $input->get('layout', '');
            $page->task     = $input->get('task', '');
            $page->itemid   = $input->get('Itemid', 0, 'int');
            $page->sitename = htmlspecialchars((string) $app->get('sitename', ''), ENT_QUOTES, 'UTF-8');
            $page->cpanel   = $page->option === 'com_cpanel';
            $page->hidden   = $input->get('hidemainmenu');

            $page->showSubmenu        = false;
            $document->submenumodules = JModuleHelper::getModules('submenu');

            foreach ($document->submenumodules as $submenumodule) {
                if (JModuleHelper::renderModule($submenumodule) !== '') {
                    $page->showSubmenu = true;
                    break;
                }
            }

            // Template Parameters
            $page->displayHeader = $document->params->get('displayHeader', '1');
            $stickyToolbar       = $document->params->get('stickyToolbar', '1');

            // The logo in the header: the site's own, else Joomla's, inline so its wordmark follows the colour scheme
            $page->logo = $page->displayHeader && $document->params->get('logoFile')
                ? JUri::root() . htmlspecialchars((string) $document->params->get('logoFile'), ENT_QUOTES) : '';

            // Pass some values to javascript
            $page->offset    = $page->displayHeader ? 44 : 34;
            $page->stickyBar = $stickyToolbar ? 'true' : 0;

            $page->scheme = static::scheme($document->params);
            $page->navbar = static::navbarColor($document->params);

            return $page;
        }

        /**
         * A page in a modal or a window of its own (tmpl=component)
         *
         * @param   JDocumentHtml  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function component($document)
        {
            $lang = JFactory::getLanguage();
            $page = new stdClass();

            // Output as HTML5
            $document->setHtml5(true);

            // No legacy markup: no scripts and stylesheets for old Internet Explorer versions
            static::dropConditionalAssets($document);

            // The CMS's script (global.js: Bootstrap's dropdowns, collapse, tabs, modals and alerts, without jQuery or Bootstrap's
            // own script, which pages and extensions still get by asking for them)
            JHtml::_('behavior.cms');

            // Add template js
            JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true, 'position' => 'footer'));

            // Add Stylesheets
            JHtml::_('stylesheet', 'template.css', array('version' => 'auto', 'relative' => true));
            static::preloadIconFont($document);

            if ($document->direction === 'rtl') {
                JHtml::_('stylesheet', 'template-rtl.css', array('version' => 'auto', 'relative' => true));
            }

            // Load optional RTL Bootstrap CSS
            JHtml::_('bootstrap.loadCss', false, $document->direction);

            // Load specific language related CSS
            JHtml::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));

            // Load custom.css
            JHtml::_('stylesheet', 'custom.css', array('version' => 'auto', 'relative' => true));

            $page->scheme = static::scheme($document->params);

            return $page;
        }

        /**
         * The login page
         *
         * @param   JDocumentHtml  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function login($document)
        {
            $app  = JFactory::getApplication();
            $lang = JFactory::getLanguage();
            $page = new stdClass();

            // Output as HTML5
            $document->setHtml5(true);

            // The site's home page: https when the whole site forces it, http when only the administrator does, otherwise as this request
            $frontEndUri = JUri::getInstance(JUri::root());
            $forceSsl    = (int) $app->get('force_ssl', 0);

            if ($forceSsl === 2) {
                $frontEndUri->setScheme('https');
            } elseif ($forceSsl === 1) {
                $frontEndUri->setScheme('http');
            }

            $page->siteUrl  = $frontEndUri->toString();
            $page->siteHost = rtrim($frontEndUri->toString(array('host', 'port', 'path')), '/');
            $page->sitename = (string) $app->get('sitename', '');

            // Template parameters: a custom logo for the form
            $page->loginLogoFile = (string) $document->params->get('loginLogoFile', '');

            JHtml::_('stylesheet', 'login.css', array('version' => 'auto', 'relative' => true));
            JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true, 'position' => 'footer'), array('defer' => true));

            // Language specific CSS and the site's own custom.css, as on every other page of the template
            JHtml::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));
            JHtml::_('stylesheet', 'custom.css', array('version' => 'auto', 'relative' => true));

            $page->option = $app->input->getCmd('option', '');
            $page->view   = $app->input->getCmd('view', '');

            // No legacy markup: no scripts and stylesheets for old Internet Explorer versions
            static::dropConditionalAssets($document);

            // The side panel's building blocks come in three colourways, shown one after another on each visit (a cookie remembers the last)
            $cookie       = $app->input->cookie;
            $page->blocks = (int) $cookie->getInt('prothero_blocks', 0) % 3 + 1;
            $cookie->set('prothero_blocks', $page->blocks, time() + 365 * 86400, JUri::base(true) . '/', '', JUri::getInstance()->isSsl(), true);

            $page->scheme = static::scheme($document->params);

            return $page;
        }

        /**
         * The error page, which writes its head itself (no document API; the database or the language may have failed)
         *
         * @param   JDocumentError  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function error($document)
        {
            $app  = JFactory::getApplication();
            $page = new stdClass();

            // Getting params from template
            $page->params = $app->getTemplate(true)->params;

            $page->app   = $app;
            $page->lang  = JFactory::getLanguage();
            $page->user  = JFactory::getUser();
            $input       = $app->input;

            // Gets the FrontEnd Main page Uri
            $frontEndUri = JUri::getInstance(JUri::root());
            $frontEndUri->setScheme(((int) $app->get('force_ssl', 0) === 2) ? 'https' : 'http');
            $page->mainPageUri = $frontEndUri->toString();

            // Detecting Active Variables
            $page->option   = $input->get('option', '');
            $page->view     = $input->get('view', '');
            $page->layout   = $input->get('layout', '');
            $page->task     = $input->get('task', '');
            $page->sitename = htmlspecialchars((string) $app->get('sitename'), ENT_QUOTES, 'UTF-8');

            // The logo in the header: the site's own, else Joomla's, inline so its wordmark follows the colour scheme
            $page->logo = $page->params->get('logoFile') ? htmlspecialchars(JUri::root() . $page->params->get('logoFile'), ENT_QUOTES, 'UTF-8') : '';

            // Template Parameters
            $page->displayHeader = $page->params->get('displayHeader', '1');

            $page->scheme = static::scheme($page->params);
            $page->navbar = static::navbarColor($page->params);

            return $page;
        }

        /**
         * Text for HTML
         *
         * @param   mixed  $text  The text
         *
         * @return  string
         *
         * @since   3.18.0
         */
        public static function escape($text)
        {
            return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        }

        /**
         * The Colour Scheme option, for the data-scheme attribute of <html>: light, dark or auto (follows the device).
         *
         * @param   mixed  $params  The template style's options (a Registry), or null
         *
         * @return  string
         *
         * @since   3.18.0
         */
        public static function scheme($params)
        {
            // The error page calls it too, when something may have failed already: never let the option break that page
            try {
                $scheme = is_object($params) ? (string) $params->get('colorScheme', 'auto') : 'auto';
            } catch (Exception $e) {
                $scheme = 'auto';
            }

            return in_array($scheme, array('light', 'dark'), true) ? $scheme : 'auto';
        }

        /**
         * The Menu Bar Colour option, for the data-navbar attribute of <html> (css/template.css has a colour for each; black
         * has none).
         *
         * @param   mixed  $params  The template style's options (a Registry), or null
         *
         * @return  string
         *
         * @since   3.18.0
         */
        public static function navbarColor($params)
        {
            try {
                $color = is_object($params) ? (string) $params->get('navbarColor', 'black') : 'black';
            } catch (Exception $e) {
                $color = 'black';
            }

            return in_array($color, array('graphite', 'navy', 'blue', 'indigo', 'purple', 'wine', 'teal', 'forest'), true) ? $color : 'black';
        }

        /**
         * The Joomla! logo inline, its wordmark in the text colour, so it follows the colour scheme; the mark keeps Joomla's
         * colours.
         *
         * @return  string  The SVG markup
         *
         * @since   3.18.0
         */
        public static function logo()
        {
            $logo = (string) @file_get_contents(JPATH_ROOT . '/media/system/images/logo/joomla.svg');

            return trim(str_replace(array('fill="#3b3a40"', '<svg '), array('fill="currentColor"', '<svg role="img" aria-label="Joomla!" focusable="false" '), $logo));
        }

        /**
         * Preloads the icon font (css/template.css draws the .icon-* classes with it): without the hint the browser asks for
         * it only once the stylesheet is parsed and an icon is styled, and the icons appear a moment after the page. The
         * address must be the one the stylesheet's @font-face asks for, or the font is downloaded twice.
         *
         * @param   JDocument  $document  The page
         *
         * @return  void
         *
         * @since   3.18.0
         */
        public static function preloadIconFont($document)
        {
            if (method_exists($document, 'addPreload')) {
                $document->addPreload(JUri::root(true) . '/media/system/fonts/bootstrap-icons.woff2', 'font', array('type' => 'font/woff2'));
            }
        }

        /**
         * No legacy markup: leave out the scripts and stylesheets for old Internet Explorer versions (conditional comments,
         * e.g. the HTML5 fallback and polyfills of forms, or the keepalive's event polyfill). Done when the head is built, so
         * assets added after the page file runs are covered too.
         *
         * @param   JDocument  $document  The page
         *
         * @return  void
         *
         * @since   3.18.0
         */
        public static function dropConditionalAssets($document)
        {
            JEventDispatcher::getInstance()->register('onBeforeCompileHead', function () use ($document) {
                if (!$document instanceof JDocumentHtml) {
                    return;
                }

                foreach (array('_scripts', '_styleSheets') as $list) {
                    foreach ($document->$list as $url => $asset) {
                        if (!empty($asset['options']['conditional']) || !empty($asset['conditional'])) {
                            unset($document->{$list}[$url]);
                        }
                    }
                }
            });
        }
    }
}

/** @var JDocument $this */

// Included by a page file: its page set up, what its markup needs returned
return isset($this) && $this instanceof JDocument ? ProtheroHelper::prepare($this) : null;
