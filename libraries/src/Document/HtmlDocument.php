<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Document;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

jimport('joomla.utilities.utility');

/**
 * HtmlDocument class, provides an easy interface to parse and display a HTML document
 *
 * @since  1.7.0
 */
class HtmlDocument extends Document
{
    /**
     * Submenu modules for the current admin page, set directly by several core admin templates
     * and view template overrides (e.g. templates/isis/index.php, com_config's application view).
     * Declared here (rather than left as a dynamic property) to avoid the PHP 8.2+ deprecation
     * warning.
     *
     * @var    array
     * @since  3.16.0
     */
    public $submenumodules;

    /**
     * Array of Header `<link>` tags
     *
     * @var    array
     * @since  1.7.0
     */
    public $_links = array();

    /**
     * Array of custom tags
     *
     * @var    array
     * @since  1.7.0
     */
    public $_custom = array();

    /**
     * Whether resource hints (preconnect, dns-prefetch, preload, modulepreload links) are also sent as HTTP `Link:` headers
     *
     * @var    boolean
     * @since  3.18.0
     */
    protected $linkHeaders = true;

    /**
     * Name of the template
     *
     * @var    string
     * @since  1.7.0
     */
    public $template = null;

    /**
     * Base url
     *
     * @var    string
     * @since  1.7.0
     */
    public $baseurl = null;

    /**
     * Array of template parameters
     *
     * @var    array
     * @since  1.7.0
     */
    public $params = null;

    /**
     * File name
     *
     * @var    array
     * @since  1.7.0
     */
    public $_file = null;

    /**
     * String holding parsed template
     *
     * @var    string
     * @since  1.7.0
     */
    protected $_template = '';

    /**
     * Array of parsed template JDoc tags
     *
     * @var    array
     * @since  1.7.0
     */
    protected $_template_tags = array();

    /**
     * Whether the template has a <jdoc:include type="footer" />, and the scripts the head left for it
     *
     * @var    boolean
     * @since  3.18.0
     */
    protected $hasFooter = false;

    /**
     * The scripts going to the footer (their addresses), decided once by getFooterScripts()
     *
     * @var    array|null
     * @since  3.18.0
     */
    protected $footerScripts;

    /**
     * The script files the head wrote (their addresses), so that the footer writes those added after it
     *
     * @var    array
     * @since  3.18.0
     */
    protected $headScripts = array();

    /**
     * Script files every page may load in the head as before, whatever is moved to the footer: jQuery (and noConflict,
     * Migrate), jQuery UI, Bootstrap, MooTools
     *
     * @var    string
     * @since  3.18.0
     */
    public const FRAMEWORK_SCRIPTS = '#/media/(?:system|jui)/js/(?:jquery(?:-noconflict|-migrate)?|jquery\\.ui\\.[a-z.]+|bootstrap|mootools-(?:core|more))(?:-uncompressed|\\.min)?\\.js$#';

    /**
     * The shared script (global.js in the administrator, frontend.js on the site): moved to the footer only on a page with
     * nothing else in the head which could use it
     *
     * @var    string
     * @since  3.18.0
     */
    public const SHARED_SCRIPTS = '#/media/system/js/(?:global|frontend)(?:\\.min)?\\.js$#';

    /**
     * Integer with caching setting
     *
     * @var    integer
     * @since  1.7.0
     */
    protected $_caching = null;

    /**
     * Set to true when the document should be output as HTML5
     *
     * @var    boolean
     * @since  3.0.0
     *
     * @note  4.0  Will be replaced by $html5 and the default value will be true.
     */
    private $_html5 = null;

    /**
     * Class constructor
     *
     * @param   array  $options  Associative array of options
     *
     * @since   1.7.0
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        // Set document type
        $this->_type = 'html';

        // Set default mime type and document metadata (metadata syncs with mime type by default)
        $this->setMimeEncoding('text/html');
    }

    /**
     * Get the HTML document head data
     *
     * @return  array  The document head data in array form
     *
     * @since   1.7.0
     */
    public function getHeadData()
    {
        $data = array();
        $data['title']         = $this->title;
        $data['description']   = $this->description;
        $data['link']          = $this->link;
        $data['metaTags']      = $this->_metaTags;
        $data['links']         = $this->_links;
        $data['styleSheets']   = $this->_styleSheets;
        $data['style']         = $this->_style;
        $data['scripts']       = $this->_scripts;
        $data['script']        = $this->_script;
        $data['custom']        = $this->_custom;

        // This is for b.c. and can be safely removed in future
        $data['scriptText']    = \JText::getScriptStrings();

        $data['scriptOptions'] = $this->scriptOptions;
        $data['readyScripts']  = $this->readyScripts;

        return $data;
    }

    /**
     * Reset the HTML document head data
     *
     * @param   mixed  $types  type or types of the heads elements to reset
     *
     * @return  HtmlDocument  instance of $this to allow chaining
     *
     * @since   3.7.0
     */
    public function resetHeadData($types = null)
    {
        if (is_null($types)) {
            $this->title         = '';
            $this->description   = '';
            $this->link          = '';
            $this->_metaTags     = array();
            $this->_links        = array();
            $this->_styleSheets  = array();
            $this->_style        = array();
            $this->_scripts      = array();
            $this->_script       = array();
            $this->_custom       = array();
            $this->scriptOptions = array();
            $this->readyScripts  = array();
        }

        if (is_array($types)) {
            foreach ($types as $type) {
                $this->resetHeadDatum($type);
            }
        }

        if (is_string($types)) {
            $this->resetHeadDatum($types);
        }

        return $this;
    }

    /**
     * Reset a part the HTML document head data
     *
     * @param   string  $type  type of the heads elements to reset
     *
     * @return  void
     *
     * @since   3.7.0
     */
    private function resetHeadDatum($type)
    {
        switch ($type) {
            case 'title':
            case 'description':
            case 'link':
                $this->{$type} = '';
                break;

            case 'metaTags':
            case 'links':
            case 'styleSheets':
            case 'style':
            case 'scripts':
            case 'script':
            case 'custom':
                $realType = '_' . $type;
                $this->{$realType} = array();
                break;

            case 'scriptOptions':
            case 'readyScripts':
                $this->{$type} = array();
                break;
        }
    }

    /**
     * Set the HTML document head data
     *
     * @param   array  $data  The document head data in array form
     *
     * @return  HtmlDocument|null instance of $this to allow chaining or null for empty input data
     *
     * @since   1.7.0
     */
    public function setHeadData($data)
    {
        if (empty($data) || !is_array($data)) {
            return null;
        }

        $this->title         = (isset($data['title']) && !empty($data['title'])) ? $data['title'] : $this->title;
        $this->description   = (isset($data['description']) && !empty($data['description'])) ? $data['description'] : $this->description;
        $this->link          = (isset($data['link']) && !empty($data['link'])) ? $data['link'] : $this->link;
        $this->_metaTags     = (isset($data['metaTags']) && !empty($data['metaTags'])) ? $data['metaTags'] : $this->_metaTags;
        $this->_links        = (isset($data['links']) && !empty($data['links'])) ? $data['links'] : $this->_links;
        $this->_styleSheets  = (isset($data['styleSheets']) && !empty($data['styleSheets'])) ? $data['styleSheets'] : $this->_styleSheets;
        $this->_style        = (isset($data['style']) && !empty($data['style'])) ? $data['style'] : $this->_style;
        $this->_scripts      = (isset($data['scripts']) && !empty($data['scripts'])) ? $data['scripts'] : $this->_scripts;
        $this->_script       = (isset($data['script']) && !empty($data['script'])) ? $data['script'] : $this->_script;
        $this->_custom       = (isset($data['custom']) && !empty($data['custom'])) ? $data['custom'] : $this->_custom;
        $this->scriptOptions = (isset($data['scriptOptions']) && !empty($data['scriptOptions'])) ? $data['scriptOptions'] : $this->scriptOptions;
        $this->readyScripts  = (isset($data['readyScripts']) && !empty($data['readyScripts'])) ? $data['readyScripts'] : $this->readyScripts;

        return $this;
    }

    /**
     * Merge the HTML document head data
     *
     * @param   array  $data  The document head data in array form
     *
     * @return  HtmlDocument|null instance of $this to allow chaining or null for empty input data
     *
     * @since   1.7.0
     */
    public function mergeHeadData($data)
    {
        if (empty($data) || !is_array($data)) {
            return;
        }

        $this->title = (isset($data['title']) && !empty($data['title']) && !stristr($this->title, $data['title']))
            ? $this->title . $data['title']
            : $this->title;
        $this->description = (isset($data['description']) && !empty($data['description']) && !stristr($this->description, $data['description']))
            ? $this->description . $data['description']
            : $this->description;
        $this->link = (isset($data['link'])) ? $data['link'] : $this->link;

        if (isset($data['metaTags'])) {
            foreach ($data['metaTags'] as $type1 => $data1) {
                $booldog = $type1 == 'http-equiv' ? true : false;

                foreach ($data1 as $name2 => $data2) {
                    $this->setMetaData($name2, $data2, $booldog);
                }
            }
        }

        $this->_links = (isset($data['links']) && !empty($data['links']) && is_array($data['links']))
            ? array_unique(array_merge($this->_links, $data['links']), SORT_REGULAR)
            : $this->_links;
        $this->_styleSheets = (isset($data['styleSheets']) && !empty($data['styleSheets']) && is_array($data['styleSheets']))
            ? array_merge($this->_styleSheets, $data['styleSheets'])
            : $this->_styleSheets;

        if (isset($data['style'])) {
            foreach ($data['style'] as $type => $stdata) {
                if (!isset($this->_style[strtolower((string) $type)]) || !stristr((string) $stdata, $this->_style[strtolower((string) $type)])) {
                    $this->addStyleDeclaration($stdata, $type);
                }
            }
        }

        $this->_scripts = (isset($data['scripts']) && !empty($data['scripts']) && is_array($data['scripts']))
            ? array_merge($this->_scripts, $data['scripts'])
            : $this->_scripts;

        if (isset($data['script'])) {
            foreach ($data['script'] as $type => $sdata) {
                if (!isset($this->_script[strtolower((string) $type)]) || !stristr((string) $sdata, $this->_script[strtolower((string) $type)])) {
                    $this->addScriptDeclaration($sdata, $type);
                }
            }
        }

        $this->_custom = (isset($data['custom']) && !empty($data['custom']) && is_array($data['custom']))
            ? array_unique(array_merge($this->_custom, $data['custom']))
            : $this->_custom;

        if (!empty($data['scriptOptions'])) {
            foreach ($data['scriptOptions'] as $key => $scriptOptions) {
                $this->addScriptOptions($key, $scriptOptions, true);
            }
        }

        if (!empty($data['readyScripts']) && is_array($data['readyScripts'])) {
            foreach ($data['readyScripts'] as $content) {
                $this->addReadyScript($content);
            }
        }

        return $this;
    }

    /**
     * Adds `<link>` tags to the head of the document
     *
     * $relType defaults to 'rel' as it is the most common relation type used.
     * ('rev' refers to reverse relation, 'rel' indicates normal, forward relation.)
     * Typical tag: `<link href="index.php" rel="Start">`
     *
     * @param   string  $href      The link that is being related.
     * @param   string  $relation  Relation of link.
     * @param   string  $relType   Relation type attribute.  Either rel or rev (default: 'rel').
     * @param   array   $attribs   Associative array of remaining attributes.
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   1.7.0
     */
    public function addHeadLink($href, $relation, $relType = 'rel', $attribs = array())
    {
        $href = (string) $href;
        $key  = $href;

        // Joomla 3.x UTD: one link per address and relation (it was one per address, so e.g. a preconnect and a dns-prefetch
        // for the same origin replaced each other). The first relation keeps the address as its key, as code reading $_links
        // expects; further ones get a key of their own, and every entry carries its address.
        foreach ($this->_links as $existingKey => $link) {
            $existingHref = isset($link['href']) ? $link['href'] : (string) $existingKey;

            if ($existingHref === $href) {
                $key = $existingKey;

                if (strtolower((string) $link['relation']) === strtolower((string) $relation)
                    && strtolower((string) $link['relType']) === strtolower((string) $relType)) {
                    break;
                }

                $key = $href . "\0" . strtolower((string) $relType) . '=' . strtolower((string) $relation);
            }
        }

        $this->_links[$key] = array('href' => $href, 'relation' => $relation, 'relType' => $relType, 'attribs' => $attribs);

        return $this;
    }

    /**
     * Adds a preconnect resource hint for the origin of an address (scheme, host and port), followed by a dns-prefetch one
     * for browsers without preconnect. The head writes every preconnect first, then every dns-prefetch, before the other
     * links; both are also sent as HTTP `Link:` headers (see setLinkHeaders()).
     *
     * @param   string          $href         An address on the origin, e.g. https://fonts.gstatic.com
     * @param   boolean|string  $crossOrigin  true or 'anonymous' (needed for fonts and other CORS requests), 'use-credentials',
     *                                        false for none
     * @param   boolean         $dnsPrefetch  Whether to add the dns-prefetch hint too
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   3.18.0
     */
    public function addPreconnect($href, $crossOrigin = false, $dnsPrefetch = true)
    {
        $origin  = $this->getOrigin($href);
        $attribs = array();

        if ($crossOrigin) {
            $attribs['crossorigin'] = $crossOrigin === 'use-credentials' ? 'use-credentials' : 'anonymous';
        }

        $this->addHeadLink($origin, 'preconnect', 'rel', $attribs);

        if ($dnsPrefetch) {
            $this->addDnsPrefetch($origin);
        }

        return $this;
    }

    /**
     * Adds a dns-prefetch resource hint for the origin of an address.
     *
     * @param   string  $href  An address on the origin, e.g. https://cdn.example.com
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   3.18.0
     */
    public function addDnsPrefetch($href)
    {
        return $this->addHeadLink($this->getOrigin($href), 'dns-prefetch');
    }

    /**
     * Adds a preload link: the browser fetches the resource early, as what $as names. Fonts are always fetched in CORS mode,
     * so they get crossorigin="anonymous" unless $attribs says otherwise. Preloads come after the preconnect and dns-prefetch
     * hints and before the other links, and are also sent as HTTP `Link:` headers (see setLinkHeaders()).
     *
     * @param   string  $href     The address of the resource
     * @param   string  $as       What it is: style, script, font, image, fetch, track, audio, video, document, worker ...
     * @param   array   $attribs  More attributes, e.g. array('type' => 'font/woff2'), array('media' => '(min-width: 768px)'),
     *                            array('fetchpriority' => 'high')
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   3.18.0
     */
    public function addPreload($href, $as, $attribs = array())
    {
        $attribs = array_merge(array('as' => (string) $as), (array) $attribs);

        if (strtolower((string) $as) === 'font' && !array_key_exists('crossorigin', $attribs)) {
            $attribs['crossorigin'] = 'anonymous';
        }

        return $this->addHeadLink($href, 'preload', 'rel', $attribs);
    }

    /**
     * Sets whether resource hints (preconnect, dns-prefetch, preload and modulepreload links) are also sent as HTTP `Link:`
     * headers, which reach the browser before the page (and which some servers and CDNs turn into 103 Early Hints). On by
     * default.
     *
     * @param   boolean  $enabled  Whether to send them
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   3.18.0
     */
    public function setLinkHeaders($enabled)
    {
        $this->linkHeaders = (bool) $enabled;

        return $this;
    }

    /**
     * Whether resource hints are also sent as HTTP `Link:` headers.
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function getLinkHeaders()
    {
        return $this->linkHeaders;
    }

    /**
     * The origin (scheme://host[:port]) of an address, which is what preconnect and dns-prefetch hints name. Addresses without
     * a host are returned as they are.
     *
     * @param   string  $href  The address
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected function getOrigin($href)
    {
        $href  = trim((string) $href);
        $parts = parse_url($href);

        if (!is_array($parts) || empty($parts['host'])) {
            return $href;
        }

        $origin = (isset($parts['scheme']) ? strtolower($parts['scheme']) . ':' : '') . '//' . strtolower($parts['host']);

        return isset($parts['port']) ? $origin . ':' . (int) $parts['port'] : $origin;
    }

    /**
     * Adds a shortcut icon (favicon)
     *
     * This adds a link to the icon shown in the favorites list or on
     * the left of the url in the address bar. Some browsers display
     * it on the tab, as well.
     *
     * @param   string  $href      The link that is being related.
     * @param   string  $type      File type
     * @param   string  $relation  Relation of link
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   1.7.0
     */
    public function addFavicon($href, $type = 'image/vnd.microsoft.icon', $relation = 'shortcut icon')
    {
        $href = str_replace('\\', '/', $href);
        $this->addHeadLink($href, $relation, 'rel', array('type' => $type));

        return $this;
    }

    /**
     * Adds a custom HTML string to the head block
     *
     * @param   string  $html  The HTML to add to the head
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   1.7.0
     */
    public function addCustomTag($html)
    {
        $html = trim((string) $html);

        // The same tag added again (e.g. from a loop) is kept once
        if (!in_array($html, $this->_custom, true)) {
            $this->_custom[] = $html;
        }

        return $this;
    }

    /**
     * Returns whether the document is set up to be output as HTML5
     *
     * @return  boolean true when HTML5 is used
     *
     * @since   3.0.0
     */
    public function isHtml5()
    {
        return $this->_html5;
    }

    /**
     * Sets whether the document should be output as HTML5
     *
     * @param   bool  $state  True when HTML5 should be output
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function setHtml5($state)
    {
        if (is_bool($state)) {
            $this->_html5 = $state;
        }
    }

    /**
     * Get the contents of a document include
     *
     * @param   string  $type     The type of renderer
     * @param   string  $name     The name of the element to render
     * @param   array   $attribs  Associative array of remaining attributes.
     *
     * @return  mixed|string The output of the renderer
     *
     * @since   1.7.0
     */
    public function getBuffer($type = null, $name = null, $attribs = array())
    {
        // If no type is specified, return the whole buffer
        if ($type === null) {
            return parent::$_buffer;
        }

        $title = (isset($attribs['title'])) ? $attribs['title'] : null;
        $bufferName = ($name === null) ? '' : $name;
        $bufferTitle = ($title === null) ? '' : $title;

        if (isset(parent::$_buffer[$type][$bufferName][$bufferTitle])) {
            return parent::$_buffer[$type][$bufferName][$bufferTitle];
        }

        $renderer = $this->loadRenderer($type);

        if ($this->_caching == true && $type == 'modules' && $name !== 'debug') {
            $cache  = Factory::getCache('com_modules', '');
            $itemId = (int) Factory::getApplication()->input->get('Itemid', 0, 'int');

            $hash = md5(
                serialize(
                    array(
                        $name,
                        $attribs,
                        \get_class($renderer),
                        $itemId
                    )
                )
            );
            $cbuffer = $cache->get('cbuffer_' . $type);

            if (isset($cbuffer[$hash])) {
                return Cache::getWorkarounds($cbuffer[$hash], array('mergehead' => 1));
            }

            $options = array();
            $options['nopathway'] = 1;
            $options['nomodules'] = 1;
            $options['modulemode'] = 1;

            $this->setBuffer($renderer->render($name, $attribs, null), $type, $name);
            $data = parent::$_buffer[$type][$bufferName][$bufferTitle];

            $tmpdata = Cache::setWorkarounds($data, $options);

            $cbuffer[$hash] = $tmpdata;

            $cache->store($cbuffer, 'cbuffer_' . $type);
        } else {
            $this->setBuffer($renderer->render($name, $attribs, null), $type, $name, $title);
        }

        return parent::$_buffer[$type][$bufferName][$bufferTitle];
    }

    /**
     * Set the contents a document includes
     *
     * @param   string  $content  The content to be set in the buffer.
     * @param   array   $options  Array of optional elements.
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   1.7.0
     */
    public function setBuffer($content, $options = array())
    {
        // The following code is just for backward compatibility.
        if (func_num_args() > 1 && !is_array($options)) {
            $args = func_get_args();
            $options = array();
            $options['type'] = $args[1];
            $options['name'] = (isset($args[2])) ? $args[2] : null;
            $options['title'] = (isset($args[3])) ? $args[3] : null;
        }

        $type = $options['type'] ?? '';
        $name = $options['name'] ?? '';
        $title = $options['title'] ?? '';

        parent::$_buffer[$type][$name][$title] = $content;

        return $this;
    }

    /**
     * Parses the template and populates the buffer
     *
     * @param   array  $params  Parameters for fetching the template
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   1.7.0
     */
    public function parse($params = array())
    {
        return $this->_fetchTemplate($params)->_parseTemplate();
    }

    /**
     * Outputs the template to the browser.
     *
     * @param   boolean  $caching  If true, cache the output
     * @param   array    $params   Associative array of attributes
     *
     * @return  string The rendered data
     *
     * @since   1.7.0
     */
    public function render($caching = false, $params = array())
    {
        $this->_caching = $caching;

        if (empty($this->_template)) {
            $this->parse($params);
        }

        $data = $this->_renderTemplate();
        parent::render($caching, $params);

        return $data;
    }

    /**
     * Count the modules based on the given condition
     *
     * @param   string  $condition  The condition to use
     *
     * @return  integer  Number of modules found
     *
     * @since   1.7.0
     */
    public function countModules($condition)
    {
        $operators = '(\+|\-|\*|\/|==|\!=|\<\>|\<|\>|\<=|\>=|and|or|xor)';
        $words = preg_split('# ' . $operators . ' #', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (count($words) === 1) {
            $name = strtolower($words[0]);
            $result = ((isset(parent::$_buffer['modules'][$name])) && (parent::$_buffer['modules'][$name] === false))
                ? 0 : count(ModuleHelper::getModules($name));

            return $result;
        }

        Log::add('Using an expression in HtmlDocument::countModules() is deprecated.', Log::WARNING, 'deprecated');

        for ($i = 0, $n = count($words); $i < $n; $i += 2) {
            // Odd parts (modules)
            $name = strtolower($words[$i]);
            $words[$i] = ((isset(parent::$_buffer['modules'][$name])) && (parent::$_buffer['modules'][$name] === false))
                ? 0
                : count(ModuleHelper::getModules($name));
        }

        // Evaluate the integer-and-operator expression without eval()
        $result = (int) $words[0];

        for ($j = 1, $n = count($words); $j < $n; $j += 2) {
            $op  = (string) $words[$j];
            $rhs = (int) $words[$j + 1];

            switch ($op) {
                case '+':   $result += $rhs;
                    break;
                case '-':   $result -= $rhs;
                    break;
                case '*':   $result *= $rhs;
                    break;
                case '/':   $result = $rhs !== 0 ? intdiv($result, $rhs) : 0;
                    break;
                case '==':  $result = (int) ($result == $rhs);
                    break;
                case '!=':
                case '<>':  $result = (int) ($result != $rhs);
                    break;
                case '<':   $result = (int) ($result < $rhs);
                    break;
                case '>':   $result = (int) ($result > $rhs);
                    break;
                case '<=':  $result = (int) ($result <= $rhs);
                    break;
                case '>=':  $result = (int) ($result >= $rhs);
                    break;
                case 'and': $result = (int) ($result && $rhs);
                    break;
                case 'or':  $result = (int) ($result || $rhs);
                    break;
                case 'xor': $result = (int) ((bool) $result xor (bool) $rhs);
                    break;
                default:    $result = 0;
            }
        }

        return $result;
    }

    /**
     * Count the number of child menu items of the current active menu item
     *
     * @return  integer  Number of child menu items
     *
     * @since   1.7.0
     */
    public function countMenuChildren()
    {
        static $children;

        if (!isset($children)) {
            $db = \JFactory::getDbo();
            $app = \JFactory::getApplication();
            $menu = $app->getMenu();
            $active = $menu->getActive();
            $children = 0;

            if ($active) {
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from('#__menu')
                    ->where('parent_id = ' . $active->id)
                    ->where('published = 1');
                $db->setQuery($query);
                $children = $db->loadResult();
            }
        }

        return $children;
    }

    /**
     * Load a template file
     *
     * @param   string  $directory  The name of the template
     * @param   string  $filename   The actual filename
     *
     * @return  string  The contents of the template
     *
     * @since   1.7.0
     */
    protected function _loadTemplate($directory, $filename)
    {
        $contents = '';

        // Check to see if we have a valid template file
        if (file_exists($directory . '/' . $filename)) {
            // Store the file path
            $this->_file = $directory . '/' . $filename;

            // Get the file content
            ob_start();
            require $directory . '/' . $filename;
            $contents = ob_get_contents();
            ob_end_clean();
        }

        // Try to find a favicon by checking the template and root folder
        $icon = '/favicon.ico';

        foreach (array($directory, JPATH_BASE) as $dir) {
            if (file_exists($dir . $icon)) {
                $path = str_replace(JPATH_BASE, '', $dir);
                $path = str_replace('\\', '/', $path);
                $this->addFavicon(Uri::base(true) . $path . $icon);
                break;
            }
        }

        return $contents;
    }

    /**
     * Fetch the template, and initialise the params
     *
     * @param   array  $params  Parameters to determine the template
     *
     * @return  HtmlDocument instance of $this to allow chaining
     *
     * @since   1.7.0
     */
    protected function _fetchTemplate($params = array())
    {
        // Check
        $directory = isset($params['directory']) ? $params['directory'] : 'templates';
        $filter = \JFilterInput::getInstance();
        $template = $filter->clean($params['template'], 'cmd');
        $file = $filter->clean($params['file'], 'cmd');

        // A page the template lacks: its override of core's layout, else core's (layouts/joomla/system/, the "system"
        // templates' until 3.18); without one of that name either, the template's own index.php (the Control Panel asks
        // for cpanel.php, which templates may leave out), else core's
        $path = $directory . '/' . $template;

        if (!file_exists($path . '/' . $file)) {
            $system = SystemLayout::path($path, $file);

            if (file_exists($system . '/' . $file)) {
                $path = $system;
            } else {
                $file = 'index.php';

                if (!file_exists($path . '/' . $file)) {
                    $path = SystemLayout::path($path, $file);
                }
            }
        }

        // Load the language file for the template
        $lang = \JFactory::getLanguage();

        // 1.5 or core then 1.6
        $lang->load('tpl_' . $template, JPATH_BASE, null, false, true)
        || $lang->load('tpl_' . $template, $directory . '/' . $template, null, false, true);

        // Assign the variables
        $this->template = $template;
        $this->baseurl = Uri::base(true);
        $this->params = isset($params['params']) ? $params['params'] : new Registry();

        // Load
        $this->_template = $this->_loadTemplate($path, $file);

        return $this;
    }

    /**
     * Whether the template writes a footer (<jdoc:include type="footer" />)
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function hasFooter()
    {
        return $this->hasFooter;
    }

    /**
     * The script files written in the footer, in their order, when the template has one (none otherwise). Framework files
     * (jQuery, Bootstrap, MooTools) always stay in the head. Files added with 'position' => 'footer' go down, without their
     * defer (the footer is the end of the page already). The shared script (global.js, frontend.js) goes down first, and
     * only when nothing else might use it while the page loads: no other script file without the mark, no script written
     * into the head (addScriptDeclaration(), a custom tag), and no inline script in the component's or the modules' HTML.
     * The code of addReadyScript() goes down with them.
     *
     * @return  array  The scripts' addresses (keys of $_scripts)
     *
     * @since   3.18.0
     */
    public function getFooterScripts()
    {
        if ($this->footerScripts !== null) {
            return $this->footerScripts;
        }

        if (!$this->hasFooter) {
            return $this->footerScripts = array();
        }

        $shared = array();
        $marked = array();
        $clean  = true;

        foreach ($this->_scripts as $src => $attribs) {
            $path = parse_url((string) $src, PHP_URL_PATH);

            if (isset($attribs['options']['position']) && $attribs['options']['position'] === 'footer') {
                $marked[] = $src;
            } elseif (preg_match(static::SHARED_SCRIPTS, (string) $path)) {
                $shared[] = $src;
            } elseif (!preg_match(static::FRAMEWORK_SCRIPTS, (string) $path)) {
                $clean = false;
            }
        }

        if ($clean) {
            $clean = !$this->hasInlineScripts();
        }

        return $this->footerScripts = array_merge($clean ? $shared : array(), $this->groupScripts($marked));
    }

    /**
     * Script files of the footer in groups by where they come from: core's media/system, then plugins, modules, components
     * and templates, then the rest (other media folders, other places), each group in the order the files were added. They
     * may be reordered as nothing a marked file holds is used while the page loads.
     *
     * @param   array  $scripts  Their addresses
     *
     * @return  array
     *
     * @since   3.18.0
     */
    protected function groupScripts(array $scripts)
    {
        $groups = array(
            '#/media/system/#'                  => 0,
            '#/plugins/|/media/plg_#'          => 1,
            '#/modules/|/media/mod_#'          => 2,
            '#/components/|/media/com_#'        => 3,
            '#/templates/#'                     => 4,
        );
        $sorted = array();

        foreach ($scripts as $position => $src) {
            $path  = (string) parse_url((string) $src, PHP_URL_PATH);
            $group = 5;

            foreach ($groups as $pattern => $number) {
                if (preg_match($pattern, $path)) {
                    $group = $number;
                    break;
                }
            }

            $sorted[] = array($group, $position, $src);
        }

        sort($sorted);

        return array_column($sorted, 2);
    }

    /**
     * Records the script files the head wrote
     *
     * @param   array  $scripts  Their addresses
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function setHeadScripts(array $scripts)
    {
        $this->headScripts = $scripts;
    }

    /**
     * The script files the head wrote
     *
     * @return  array  Their addresses
     *
     * @since   3.18.0
     */
    public function getHeadScripts()
    {
        return $this->headScripts;
    }

    /**
     * The HTML the component and the modules wrote (their buffers), in pieces
     *
     * @return  string[]
     *
     * @since   3.18.0
     */
    protected function getBufferedHtml()
    {
        $html = array();

        foreach ((array) parent::$_buffer as $type => $buffers) {
            if (in_array($type, array('head', 'header', 'footer'), true) || !is_array($buffers)) {
                continue;
            }

            array_walk_recursive($buffers, function ($value) use (&$html) {
                if (is_string($value)) {
                    $html[] = $value;
                }
            });
        }

        return $html;
    }

    /**
     * Behaviours the page's markup asks for, so views needn't call them (the classes Joomla always used, and data attributes,
     * which new markup should use: they show which elements have behaviour):
     *
     * - a form with the class form-validate or a data-validate attribute is validated (behavior.formvalidator)
     * - elements with the class hasTooltip or hasPopover, or a data-tooltip or data-popover attribute, get their tooltips and
     *   popovers from their title (bootstrap.tooltip, bootstrap.popover)
     * - a select with the class advancedSelect or a data-searchable attribute becomes searchable (formbehavior.chosen);
     *   data-searchable="false" (or Joomla's data-chosen="true") keeps one as it is, also where every list is searchable, as
     *   in the administrator
     * - a form with a data-keepalive attribute keeps the session alive while it's open (behavior.keepalive)
     *
     * Read from the HTML of the component and the modules when the head is written, as they're done by then; the calls do
     * nothing more when the page made them already.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function loadMarkupBehaviours()
    {
        $html = implode("\n", $this->getBufferedHtml());

        if ($html === '') {
            return;
        }

        $class = function ($name) {
            return '\\bclass\\s*=\\s*["\'][^"\']*\\b' . $name . '\\b';
        };
        $attribute = function ($name) {
            return '\\s' . $name . '(?:\\s*=|[\\s/>])';
        };

        if (preg_match('#<form\\b[^>]*(?:' . $class('form-validate') . '|' . $attribute('data-validate') . ')#i', $html)) {
            HTMLHelper::_('behavior.formvalidator');
        }

        if (preg_match('#<[a-z][^>]*(?:' . $class('hasTooltip') . '|' . $attribute('data-tooltip') . ')#', $html)) {
            HTMLHelper::_('bootstrap.tooltip');
        }

        if (preg_match('#<[a-z][^>]*(?:' . $class('hasPopover') . '|' . $attribute('data-popover') . ')#', $html)) {
            HTMLHelper::_('bootstrap.popover');
        }

        if (preg_match('#<select\\b[^>]*' . $class('advancedSelect') . '#i', $html)) {
            HTMLHelper::_('formbehavior.chosen');
        }

        if (preg_match('#<select\\b[^>]*' . $attribute('data-searchable') . '#i', $html)) {
            HTMLHelper::_('formbehavior.chosen', 'select[data-searchable]');
        }

        if (preg_match('#<form\\b[^>]*' . $attribute('data-keepalive') . '#i', $html)) {
            HTMLHelper::_('behavior.keepalive');
        }
    }

    /**
     * Whether something on the page might run code while it loads, using the shared script: a script declaration, a custom
     * tag with a script, or an inline script in the HTML of the component or a module (data scripts, JSON-LD for example,
     * don't count)
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    protected function hasInlineScripts()
    {
        $data = array('application/json', 'application/ld+json', 'importmap', 'text/template', 'text/html', 'text/x-template');

        foreach ($this->_script as $type => $content) {
            if (!in_array(strtolower((string) $type), $data, true) && trim((string) $content) !== '') {
                return true;
            }
        }

        foreach (array_merge($this->_custom, $this->getBufferedHtml()) as $chunk) {
            if (!preg_match_all('#<script\\b([^>]*)>#i', (string) $chunk, $tags)) {
                continue;
            }

            foreach ($tags[1] as $attributes) {
                if (preg_match('#\\bsrc\\s*=#i', $attributes)) {
                    continue;
                }

                if (preg_match('#\\btype\\s*=\\s*["\']?([^"\'\\s>]+)#i', $attributes, $type) && in_array(strtolower($type[1]), $data, true)) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Parse a document template
     *
     * @return  HtmlDocument  instance of $this to allow chaining
     *
     * @since   1.7.0
     */
    protected function _parseTemplate()
    {
        $matches = array();

        if (preg_match_all('#<jdoc:include\ type="([^"]+)"(.*)\/>#iU', $this->_template, $matches)) {
            $template_tags_first = array();
            $template_tags_last = array();

            // Step through the jdocs in reverse order.
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $type = $matches[1][$i];
                $attribs = empty($matches[2][$i]) ? array() : \JUtility::parseAttributes($matches[2][$i]);
                $name = isset($attribs['name']) ? $attribs['name'] : null;

                // Separate buffers to be executed first and last
                if ($type == 'module' || $type == 'modules') {
                    $template_tags_first[$matches[0][$i]] = array('type' => $type, 'name' => $name, 'attribs' => $attribs);
                } else {
                    $template_tags_last[$matches[0][$i]] = array('type' => $type, 'name' => $name, 'attribs' => $attribs);
                }
            }

            // Reverse the last array so the jdocs are in forward order.
            $template_tags_last = array_reverse($template_tags_last);

            $this->_template_tags = $template_tags_first + $template_tags_last;
            $this->hasFooter      = in_array('footer', array_map('strtolower', $matches[1]), true);
            $this->footerScripts  = null;
        }

        return $this;
    }

    /**
     * Render pre-parsed template
     *
     * @return string rendered template
     *
     * @since   1.7.0
     */
    protected function _renderTemplate()
    {
        $replace = array();
        $with = array();

        foreach ($this->_template_tags as $jdoc => $args) {
            $replace[] = $jdoc;
            $with[] = $this->getBuffer($args['type'], $args['name'], $args['attribs']);
        }

        return str_replace($replace, $with, $this->_template);
    }
}
