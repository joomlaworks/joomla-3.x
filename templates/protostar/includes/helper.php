<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\FontStackHelper;

if (!class_exists('ProtostarHelper', false)) {
    /**
     * Protostar's code: what its page files set up before their markup (prepare()) and the helpers they share.
     *
     * @since  3.18.0
     */
    abstract class ProtostarHelper
    {
        /**
         * Sets the page up and gives what its markup needs: the page is the file being rendered (index, component, offline,
         * error)
         *
         * @param   JDocument  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        public static function prepare($document)
        {
            switch (basename((string) $document->_file, '.php')) {
                case 'component':
                    return static::component($document);

                case 'offline':
                    return static::offline($document);

                case 'error':
                    return static::error($document);

                default:
                    return static::index($document);
            }
        }

        /**
         * The site's pages
         *
         * @param   JDocumentHtml  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function index($document)
        {
            $app  = JFactory::getApplication();
            $page = static::page($app->getTemplate(true)->params, $document);

            // Output as HTML5
            $document->setHtml5(true);

            // No legacy markup: no scripts and stylesheets for old Internet Explorer versions
            static::dropConditionalAssets($document);

            // Add JavaScript Frameworks
            JHtml::_('bootstrap.framework');

            // Add template js
            JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true));

            // Add Stylesheets
            JHtml::_('stylesheet', 'template.css', array('version' => 'auto', 'relative' => true));

            // Check for a custom CSS file
            JHtml::_('stylesheet', 'user.css', array('version' => 'auto', 'relative' => true));

            static::options($document, $page);

            // Check for a custom js file
            JHtml::_('script', 'user.js', array('version' => 'auto', 'relative' => true));

            // Load optional RTL Bootstrap CSS
            JHtml::_('bootstrap.loadCss', false, $document->direction);

            // Adjusting content width
            $page->position7ModuleCount = $document->countModules('position-7');
            $page->position8ModuleCount = $document->countModules('position-8');

            if ($page->position7ModuleCount && $page->position8ModuleCount) {
                $page->span = 'span6';
            } elseif ($page->position7ModuleCount || $page->position8ModuleCount) {
                $page->span = 'span9';
            } else {
                $page->span = 'span12';
            }

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
            $page = static::page(JFactory::getApplication()->getTemplate(true)->params, $document);

            // Output as HTML5
            $document->setHtml5(true);

            // No legacy markup: no scripts and stylesheets for old Internet Explorer versions
            static::dropConditionalAssets($document);

            // Add JavaScript Frameworks
            JHtml::_('bootstrap.framework');

            // Add template js
            JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true));

            // Add Stylesheets
            JHtml::_('stylesheet', 'template.css', array('version' => 'auto', 'relative' => true));

            // The Font option, as on the other pages
            $document->addStyleDeclaration(':root {--protostar-font:' . $page->font . ';}');

            // Load optional rtl Bootstrap css and Bootstrap bugfixes
            JHtml::_('bootstrap.loadCss', false, $document->direction);

            return $page;
        }

        /**
         * The page shown while the site is offline
         *
         * @param   JDocumentHtml  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function offline($document)
        {
            $page = static::page($document->params, $document);

            $page->twofactormethods = JAuthenticationHelper::getTwoFactorMethods();

            // Output as HTML5
            $document->setHtml5(true);

            // No legacy markup: no scripts and stylesheets for old Internet Explorer versions
            static::dropConditionalAssets($document);

            // Add JavaScript Frameworks
            JHtml::_('bootstrap.framework');

            // Add template js
            JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true));

            // Add Stylesheets
            JHtml::_('stylesheet', 'template.css', array('version' => 'auto', 'relative' => true));
            JHtml::_('stylesheet', 'offline.css', array('version' => 'auto', 'relative' => true));

            // Check for a custom CSS file
            JHtml::_('stylesheet', 'user.css', array('version' => 'auto', 'relative' => true));

            static::options($document, $page);

            // Check for a custom js file
            JHtml::_('script', 'user.js', array('version' => 'auto', 'relative' => true));

            // Load optional RTL Bootstrap CSS
            JHtml::_('bootstrap.loadCss', false, $document->direction);

            return $page;
        }

        /**
         * The error page, which writes its head itself (no document API: something may have failed early)
         *
         * @param   JDocumentError  $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function error($document)
        {
            $page = static::page(JFactory::getApplication()->getTemplate(true)->params, $document);

            $page->format = $page->app->input->getCmd('format', 'html');

            // Add JavaScript Frameworks
            JHtml::_('bootstrap.framework');

            return $page;
        }

        /**
         * What every page's markup uses: the application, the template's options, the request, the site's name, the logo,
         * the classes of <body>, the option colours and the font
         *
         * @param   Joomla\Registry\Registry  $params    The template style's options
         * @param   JDocument                 $document  The page
         *
         * @return  stdClass
         *
         * @since   3.18.0
         */
        protected static function page($params, $document)
        {
            $app   = JFactory::getApplication();
            $input = $app->input;
            $page  = new stdClass();

            $page->app      = $app;
            $page->params   = $params;
            $page->user     = JFactory::getUser();
            $page->option   = $input->getCmd('option', '');
            $page->view     = $input->getCmd('view', '');
            $page->layout   = $input->getCmd('layout', '');
            $page->task     = $input->getCmd('task', '');
            $page->itemid   = $input->getCmd('Itemid', '');
            $page->sitename = htmlspecialchars((string) $app->get('sitename'), ENT_QUOTES, 'UTF-8');

            // Logo file or site title param
            if ($params->get('logoFile')) {
                $page->logo = '<img src="' . htmlspecialchars(JUri::root() . $params->get('logoFile'), ENT_QUOTES, 'UTF-8') . '" alt="' . $page->sitename . '" />';
            } elseif ($params->get('sitetitle')) {
                $page->logo = '<span class="site-title" title="' . $page->sitename . '">' . htmlspecialchars((string) $params->get('sitetitle'), ENT_COMPAT, 'UTF-8') . '</span>';
            } else {
                $page->logo = '<span class="site-title" title="' . $page->sitename . '">' . $page->sitename . '</span>';
            }

            $page->optionColors = static::optionColors($params);
            $page->font         = FontStackHelper::get($params->get('fontStack', FontStackHelper::DEFAULT_STACK));

            $page->bodyClass = 'site ' . $page->option
                . ' view-' . $page->view
                . ($page->layout ? ' layout-' . $page->layout : ' no-layout')
                . ($page->task ? ' task-' . $page->task : ' no-task')
                . ($page->itemid ? ' itemid-' . $page->itemid : '')
                . ($params->get('fluidContainer') ? ' fluid' : '')
                . ($document->direction === 'rtl' ? ' rtl' : '')
                . $page->optionColors['classes'];

            return $page;
        }

        /**
         * The template options' colours (css/template-options.css, after user.css) and the font, as custom properties
         *
         * @param   JDocumentHtml  $document  The page
         * @param   stdClass       $page      What prepare() gives
         *
         * @return  void
         *
         * @since   3.18.0
         */
        protected static function options($document, $page)
        {
            if ($page->optionColors['properties'] !== '') {
                JHtml::_('stylesheet', 'template-options.css', array('version' => 'auto', 'relative' => true));
            }

            $document->addStyleDeclaration(':root {--protostar-font:' . $page->font . ';' . $page->optionColors['properties'] . '}');
        }

        /**
         * The Template Colour option, and Background Colour with it: as custom properties for css/template-options.css,
         * which applies them after the site's user.css. Values which could end the CSS they go into are ignored.
         *
         * @param   Joomla\Registry\Registry  $params  The template style's options
         *
         * @return  array  'properties' (the declarations for :root, empty without a Template Colour) and 'classes' (for <body>)
         *
         * @since   3.18.0
         */
        public static function optionColors($params)
        {
            $properties = '';
            $classes    = '';
            $safe       = function ($color) {
                $color = trim((string) $color);

                return $color !== '' && preg_match('/^[#\w(),.%\s-]+$/', $color) ? $color : '';
            };

            if ($template = $safe($params->get('templateColor'))) {
                $properties = '--protostar-template-color:' . $template . ';';
                $classes    = ' protostar-template-color';

                if ($background = $safe($params->get('templateBackgroundColor'))) {
                    $properties .= '--protostar-background-color:' . $background . ';';
                    $classes    .= ' protostar-background-color';
                }
            }

            return array('properties' => $properties, 'classes' => $classes);
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
return isset($this) && $this instanceof JDocument ? ProtostarHelper::prepare($this) : null;
