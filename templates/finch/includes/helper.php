<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

/**
 * Shared helpers of the Finch template: one way to show a post's image, category, author, date and excerpt in every list,
 * module layout and post.
 *
 * @since  3.17.0
 */
abstract class FinchHelper
{
    /**
     * The template's parameters
     *
     * @var    \Joomla\Registry\Registry
     * @since  3.17.0
     */
    protected static $params;

    /**
     * The script giving modules with the class "wide" the window's width without its scrollbar, which 100vw includes. It runs in
     * the head, before the page is drawn.
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function wideScript()
    {
        return "(function(r){var w=0,s=function(){if(r.clientWidth!==w){w=r.clientWidth;r.style.setProperty('--vw',w+'px');}};s();"
            . "if(window.ResizeObserver){new ResizeObserver(s).observe(r);}else{window.addEventListener('resize',s);}})(document.documentElement);";
    }

    /**
     * @return  \Joomla\Registry\Registry
     *
     * @since   3.17.0
     */
    public static function params()
    {
        if (static::$params === null) {
            static::$params = JFactory::getApplication()->getTemplate(true)->params;
        }

        return static::$params;
    }

    /**
     * The Font options (stacks of Joomla\CMS\Helper\FontStackHelper) as the custom properties of the template's fonts;
     * empty when they keep the template's own. Never throws: the error page asks too.
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function fonts()
    {
        $css = '';

        try {
            $params = static::params();

            foreach (array('fontMain' => '--fh', 'fontText' => '--fs') as $name => $property) {
                $key = (string) $params->get($name, '');

                if ($key !== '' && isset(\Joomla\CMS\Helper\FontStackHelper::STACKS[$key])) {
                    $css .= $property . ':' . \Joomla\CMS\Helper\FontStackHelper::STACKS[$key][1] . ';';
                }
            }
        } catch (Throwable $e) {
            return '';
        }

        return $css;
    }

    /**
     * Prepare a page of the template and tell its markup what it needs to know. Sets up the document's head (HTML5, meta
     * tags, the stylesheets, the icon) for index.php, component.php (print and popup views) and offline.php alike; for
     * index.php also the script and the kind of page.
     *
     * @param   JDocumentHtml  $document  The document ($this in the template's files)
     * @param   string         $layout    index, component or offline
     *
     * @return  stdClass  params, siteName; for index also isHome, isPost, isPage, isList, hasSidebar, hasSearch, bodyClass
     *
     * @since   3.17.0
     */
    public static function prepare($document, $layout = 'index')
    {
        $base           = JUri::root(true) . '/templates/' . basename(dirname(__DIR__));
        $page           = new stdClass();
        $page->params   = static::params();
        $page->siteName = static::siteName();

        $document->setHtml5(true);
        $document->setGenerator('');
        $document->setMetaData('viewport', 'width=device-width, initial-scale=1');
        $page->scheme   = static::scheme();

        // The browser's own parts (scroll bars, fields) and its bar match the page's colours
        $document->setMetaData('color-scheme', $page->scheme === 'auto' ? 'light dark' : $page->scheme);

        if ($page->scheme === 'auto') {
            $document->addCustomTag('<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />');
            $document->addCustomTag('<meta name="theme-color" content="#1b1022" media="(prefers-color-scheme: dark)" />');
        } else {
            $document->setMetaData('theme-color', $page->scheme === 'dark' ? '#1b1022' : '#ffffff');
        }

        foreach (static::stylesheets() as $stylesheet) {
            $document->addStyleSheet($stylesheet);
            static::preload($stylesheet, 'style');
        }

        // The Font options, over the template's own stacks
        if (($fonts = static::fonts()) !== '') {
            $document->addStyleDeclaration(':root {' . $fonts . '}');
        }

        $document->addHeadLink($base . '/images/svg/favicon.svg', 'icon', 'rel', array('type' => 'image/svg+xml'));

        if ($layout !== 'index') {
            return $page;
        }

        $script = $base . '/js/template.js?t=' . date('Ymd_Hi', filemtime(dirname(__DIR__) . '/js/template.js'));
        $document->addScript($script, array('position' => 'footer'), array('defer' => true));
        static::preload($script, 'script');

        $app    = JFactory::getApplication();
        $input  = $app->input;
        $active = $app->getMenu()->getActive();
        $option = $input->getCmd('option', '');
        $view   = $input->getCmd('view', '');

        // Joomla makes the home item active on pages without a menu item of their own (tags, search): the home page is its own link
        $home             = $active ? $active->query : array();
        $page->isHome     = $active && $active->home && (isset($home['option']) ? $home['option'] : '') === $option
            && (isset($home['view']) ? $home['view'] : '') === $view && (!isset($home['id']) || (int) $home['id'] === $input->getInt('id'));
        $page->isPage     = static::isPage();
        $page->isPost     = $option === 'com_content' && $view === 'article' && !$page->isPage;
        $page->isList     = in_array($option . '.' . $view, array('com_content.featured', 'com_content.category', 'com_content.archive', 'com_tags.tag', 'com_search.search'), true);
        $page->hasSidebar = $page->isList && $document->countModules('sidebar');
        $page->hasSearch  = (bool) $document->countModules('search');
        $page->bodyClass  = implode(' ', array_filter(array(
            'site', $page->isHome ? 'isHome' : '', $page->isPost ? 'isPost' : '', $page->isPage ? 'isPage' : '', $page->isList ? 'isList' : '',
            $page->hasSidebar ? 'hasSidebar' : '', 'option-' . str_replace('com_', '', $option), 'view-' . $view,
            $active ? 'itemid-' . (int) $active->id : '', $active ? trim((string) $active->getParams()->get('pageclass_sfx')) : '',
        )));

        return $page;
    }

    /**
     * The site's name as the template shows it: the "Name in the Logo" option, else the site's name.
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function siteName()
    {
        return static::params()->get('siteName') ?: JFactory::getApplication()->get('sitename');
    }

    /**
     * The links to the blog's social profiles (the template's options), and its feed.
     *
     * @param   string  $class  Classes of the list
     *
     * @return  string  Empty when there are none
     *
     * @since   3.17.0
     */
    public static function socialLinks($class = 'socialLinks')
    {
        $items = '';

        foreach (array('x' => 'X', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'rss' => 'RSS') as $network => $label) {
            $url = trim((string) static::params()->get('social_' . $network, ''));

            // The site's feed unless another one is set
            if ($network === 'rss' && $url === '') {
                $url = JUri::base(true) . '/index.php?format=feed&type=rss';
            }

            // Web addresses (or paths) only: never javascript: and the like, however it's spelt (browsers ignore tabs and newlines in it)
            if ($url !== '' && (!preg_match('/^[a-z][a-z0-9+.\-]*:/i', preg_replace('/[\x00-\x20]+/', '', $url), $scheme)
                || preg_match('/^https?:$/i', $scheme[0]))) {
                $items .= '<li><a href="' . static::e($url) . '" aria-label="' . static::e($label) . '"' . ($network !== 'rss' ? ' rel="noopener" target="_blank"' : '')
                    . '>' . static::icon($network) . '</a></li>';
            }
        }

        return $items === '' ? '' : '<ul class="' . $class . '">' . $items . '</ul>';
    }

    /**
     * Nothing since 3.18: each icon is its own file (images/icons), written in place by icon(). Kept for copies of this
     * template whose pages still call it.
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function sprite()
    {
        return '';
    }

    /**
     * The offline page's message, as Global Configuration says: its own text, the language's standard one, or none.
     *
     * @return  string  HTML (Global Configuration's text is the administrator's)
     *
     * @since   3.17.0
     */
    public static function offlineMessage()
    {
        $app  = JFactory::getApplication();
        $mode = (int) $app->get('display_offline_message', 1);

        if ($mode === 1 && trim((string) $app->get('offline_message')) !== '') {
            return (string) $app->get('offline_message');
        }

        return $mode === 2 ? JText::_('JOFFLINE_MESSAGE') : '';
    }

    /**
     * The offline page's image (Global Configuration), when the file exists.
     *
     * @return  string  Its URL, or empty
     *
     * @since   3.17.0
     */
    public static function offlineImage()
    {
        $image = preg_replace('/#.*$/', '', (string) JFactory::getApplication()->get('offline_image'));

        return $image !== '' && is_file(JPATH_ROOT . '/' . ltrim($image, '/')) ? JUri::root(true) . '/' . ltrim($image, '/') : '';
    }

    /**
     * Adds a preload hint. Joomla 3.x UTD (3.18+) writes it before the stylesheets and also sends it as an HTTP Link header,
     * which reaches the browser before the page (and which CDNs such as Cloudflare turn into 103 Early Hints); on Joomla
     * versions without addPreload() it does nothing.
     *
     * @param   string  $href     The resource
     * @param   string  $as       What it is (style, script, image...)
     * @param   array   $attribs  More attributes
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public static function preload($href, $as, $attribs = array())
    {
        $document = JFactory::getDocument();

        if (method_exists($document, 'addPreload')) {
            $document->addPreload($href, $as, $attribs);
        }
    }

    /**
     * Preloads the article's main image, which the article view shows at the top (fetchpriority high).
     *
     * @param   array  $image  image()'s result
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public static function preloadImage(array $image)
    {
        if ($image['src'] === '') {
            return;
        }

        // With the srcset the image gets, so the browser preloads the size it will use
        $attribs = array('fetchpriority' => 'high');
        $srcset  = static::srcsetFor($image['src']);

        if ($srcset !== '') {
            $attribs['imagesrcset'] = $srcset;
            $attribs['imagesizes']  = static::SIZES_MAIN;
        }

        static::preload($image['src'], 'image', $attribs);
    }

    /**
     * Resource hints for what an article's text embeds. X posts make template.js load X's script at once, so the connection
     * to it is opened early; YouTube videos load lazily, often below the first screen, so only their host name is resolved
     * (a preconnect would mostly go unused).
     *
     * @param   string  $text  The article's text
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public static function embedHints($text)
    {
        $document = JFactory::getDocument();
        $text     = (string) $text;

        if (!method_exists($document, 'addPreconnect')) {
            return;
        }

        if (strpos($text, 'twitter-tweet') !== false) {
            $document->addPreconnect('https://platform.twitter.com');
        }

        foreach (array('https://www.youtube-nocookie.com/embed/', 'https://www.youtube.com/embed/') as $embed) {
            if (strpos($text, $embed) !== false) {
                $document->addDnsPrefetch($embed);
            }
        }
    }

    /**
     * The template's stylesheets: css/template.css, then css/custom.css when the site has one. custom.css is the site's own CSS:
     * it isn't one of the template's files, so no update (Joomla Update, an upload of the files or the command line) touches it.
     * Each URL carries the file's modification time, so browsers fetch a changed file.
     *
     * @return  string[]
     *
     * @since   3.17.0
     */
    public static function stylesheets()
    {
        $base = JUri::root(true) . '/templates/' . basename(dirname(__DIR__)) . '/css/';
        $urls = array();

        foreach (array('template.css', 'custom.css') as $file) {
            if (is_file(dirname(__DIR__) . '/css/' . $file)) {
                $urls[] = $base . $file . '?t=' . date('Ymd_Hi', filemtime(dirname(__DIR__) . '/css/' . $file));
            }
        }

        return $urls;
    }

    /**
     * Escape for HTML.
     *
     * @param   string  $value  The text
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * The footer text option, escaped, with "Joomla 3.x UTD" linked to the distribution's site. The option is plain text: the
     * link is added to the escaped text, which has no markup of its own.
     *
     * @param   string  $text  The text
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function footerText($text)
    {
        return str_replace('Joomla 3.x UTD', '<a href="https://j3xutd.joomlaworks.net">Joomla 3.x UTD</a>', static::e($text));
    }

    /**
     * An SVG icon from the sprite in index.php.
     *
     * @param   string  $name   The icon
     * @param   string  $class  Extra classes
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function icon($name, $class = '')
    {
        list($viewBox, $body) = static::iconFile($name);

        return '<svg class="icon icon-' . $name . ($class ? ' ' . $class : '') . '" viewBox="' . $viewBox . '" aria-hidden="true" focusable="false">'
            . $body . '</svg>';
    }

    /**
     * An icon's file (images/svg/<name>.svg), read once per request
     *
     * @param   string  $name  The icon's name
     *
     * @return  array  Its viewBox and its contents ('' when there's no such icon)
     *
     * @since   3.18.0
     */
    protected static function iconFile($name)
    {
        static $icons = array();

        if (!isset($icons[$name])) {
            $file = dirname(__DIR__) . '/images/svg/' . preg_replace('/[^a-z0-9-]/', '', (string) $name) . '.svg';

            $icons[$name] = is_file($file) && preg_match('#<svg\b[^>]*\sviewBox="([^"]*)"[^>]*>(.*)</svg>#s', (string) file_get_contents($file), $svg)
                ? array($svg[1], trim($svg[2]))
                : array('0 0 24 24', '');
        }

        return $icons[$name];
    }

    /**
     * The logo: the finch and the site's name.
     *
     * @param   string  $name   The site's name
     * @param   string  $class  Classes of the link
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function logo($name, $class = 'logo')
    {
        return '<a class="' . $class . '" href="' . JUri::base(true) . '/">'
            . '<svg class="logoMark" viewBox="0 0 48 48" aria-hidden="true">' . static::iconFile('finch')[1] . '</svg>'
            . '<span class="logoText">' . static::e($name) . '</span></a>';
    }

    /**
     * The post's image: the intro image, else the full article image, else the first image of the text.
     *
     * @param   object   $item  The post
     * @param   boolean  $full  Prefer the full article image
     *
     * @return  array  src, alt, caption (empty src when there is none)
     *
     * @since   3.17.0
     */
    public static function image($item, $full = false)
    {
        $images = isset($item->images) && is_string($item->images) ? json_decode($item->images) : (isset($item->images) ? (object) $item->images : null);

        foreach ($full ? array('fulltext', 'intro') : array('intro', 'fulltext') as $type) {
            $src = isset($images->{'image_' . $type}) ? (string) $images->{'image_' . $type} : '';

            if ($src !== '') {
                return array(
                    'src'     => static::url($src),
                    'alt'     => isset($images->{'image_' . $type . '_alt'}) ? $images->{'image_' . $type . '_alt'} : '',
                    'caption' => isset($images->{'image_' . $type . '_caption'}) ? $images->{'image_' . $type . '_caption'} : '',
                );
            }
        }

        if (preg_match('/<img[^>]+src="([^"]+)"/i', (string) (isset($item->introtext) ? $item->introtext : ''), $match)) {
            return array('src' => static::url($match[1]), 'alt' => '', 'caption' => '');
        }

        return array('src' => '', 'alt' => '', 'caption' => '');
    }

    /**
     * An image URL: absolute ones as they are, others from the site's root.
     *
     * @param   string  $src  The image
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function url($src)
    {
        // Images of the media manager carry their size after a #
        $src = preg_replace('/#.*$/', '', (string) $src);

        return preg_match('#^(https?:)?//#i', $src) ? $src : JUri::root(true) . '/' . ltrim($src, '/');
    }

    /**
     * The image, linked to the post (or nothing).
     *
     * @param   object   $item   The post
     * @param   string   $link   The post's link
     * @param   string   $class  Classes of the wrapper
     * @param   boolean  $lazy   Load lazily
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function figure($item, $link, $class = 'postImage', $lazy = true)
    {
        $image = static::image($item);

        if ($image['src'] === '') {
            return '';
        }

        return '<div class="' . $class . '"><a href="' . $link . '" tabindex="-1" aria-hidden="true"><img src="' . static::e($image['src'])
            . '"' . static::srcsetAttributes($image['src'], static::SIZES_CARD)
            . ' alt="' . static::e($image['alt']) . '" width="1280" height="720"' . ($lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high"')
            . ' /></a></div>';
    }

    /**
     * The post's link.
     *
     * @param   object  $item  The post
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function link($item)
    {
        if (!empty($item->link)) {
            return $item->link;
        }

        $slug    = isset($item->slug) ? $item->slug : $item->id . ':' . (isset($item->alias) ? $item->alias : '');
        $catslug = isset($item->catslug) ? $item->catslug : $item->catid;

        return JRoute::_(ContentHelperRoute::getArticleRoute($slug, $catslug, isset($item->language) ? $item->language : '*'));
    }

    /**
     * A link to edit the article on the site, for users who may edit it (Joomla's own layouts show an Edit icon there), or a
     * note while another user has it checked out. Plain markup: JHtml's icon.edit would load Bootstrap's tooltips.
     *
     * @param   object  $item  The article
     *
     * @return  string  Empty for everyone else
     *
     * @since   3.18.0
     */
    public static function editLink($item)
    {
        if (empty($item->id) || !isset($item->params) || !($item->params instanceof \Joomla\Registry\Registry)
            || !$item->params->get('access-edit') || (isset($item->state) && $item->state < 0)
            || JFactory::getApplication()->input->getBool('print')) {
            return '';
        }

        if (!empty($item->checked_out) && (int) $item->checked_out !== (int) JFactory::getUser()->get('id')) {
            $title = JText::_('JLIB_HTML_CHECKED_OUT') . ': ' . JFactory::getUser($item->checked_out)->name;

            return '<span class="editLink isLocked" title="' . static::e($title) . '">' . static::icon('lock')
                . '<span>' . JText::_('JLIB_HTML_CHECKED_OUT') . '</span></span>';
        }

        $slug  = $item->slug ?? ($item->id . ':' . ($item->alias ?? ''));
        $url   = ContentHelperRoute::getArticleRoute($slug, $item->catid, $item->language ?? '*')
            . '&task=article.edit&a_id=' . (int) $item->id . '&return=' . base64_encode((string) JUri::getInstance());
        $title = JText::_('JGLOBAL_EDIT_TITLE') . (isset($item->state) && (int) $item->state === 0 ? ' (' . JText::_('JUNPUBLISHED') . ')' : '');

        return '<a class="editLink" href="' . JRoute::_($url) . '" title="' . static::e($title) . '" rel="nofollow">' . static::icon('edit')
            . '<span>' . JText::_('JGLOBAL_EDIT') . '</span></a>';
    }

    /**
     * The category, linked to its page.
     *
     * @param   object  $item  The post
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function category($item)
    {
        $title = isset($item->category_title) ? (string) $item->category_title : '';

        if ($title === '') {
            return '';
        }

        return '<a class="postCategory" href="' . JRoute::_(ContentHelperRoute::getCategoryRoute($item->catid, isset($item->language) ? $item->language : '*')) . '">'
            . static::e($title) . '</a>';
    }

    /**
     * The author's name: the alias, else the user's name.
     *
     * @param   object  $item  The post
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function author($item)
    {
        foreach (array('created_by_alias', 'author', 'displayAuthorName') as $field) {
            if (!empty($item->$field)) {
                return (string) $item->$field;
            }
        }

        return '';
    }

    /**
     * Two initials for an author's avatar.
     *
     * @param   string  $name  The name
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function initials($name)
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        if (!$parts) {
            return '';
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    }

    /**
     * The publishing date.
     *
     * @param   object  $item  The post
     *
     * @return  string  A time element
     *
     * @since   3.17.0
     */
    public static function date($item)
    {
        $value = isset($item->publish_up) ? $item->publish_up : (isset($item->created) ? $item->created : '');

        if (!$value || $value === JFactory::getDbo()->getNullDate()) {
            return '';
        }

        return '<time class="postDate" datetime="' . JFactory::getDate($value)->format('c') . '">' . static::e(JHtml::_('date', $value, JText::_('DATE_FORMAT_LC3'))) . '</time>';
    }

    /**
     * Plain text of the intro, cut at a word.
     *
     * @param   object   $item    The post
     * @param   integer  $length  At most this many characters (0: all)
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function excerpt($item, $length = 0)
    {
        // Without the core plugins' shortcodes ({pdf ...}, {loadposition ...}): the intro text isn't prepared here
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) preg_replace('/\{(?:pdf|loadposition|loadmodule|loadmoduleid|emailcloak)\b[^{}]*\}/i', ' ', (string) (isset($item->introtext) ? $item->introtext : ''))), ENT_QUOTES, 'UTF-8')));

        if ($length && mb_strlen($text) > $length) {
            $text = rtrim(mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, $length), ' ') ?: $length), ' ,.;:') . '…';
        }

        return static::e($text);
    }

    /**
     * Minutes to read the post.
     *
     * @param   object  $item  The post
     *
     * @return  integer
     *
     * @since   3.17.0
     */
    public static function readingTime($item)
    {
        $text = (isset($item->introtext) ? $item->introtext : '') . ' ' . (isset($item->fulltext) ? $item->fulltext : (isset($item->text) ? $item->text : ''));

        return max(1, (int) round(str_word_count(strip_tags($text)) / 230));
    }

    /**
     * A standalone page (e.g. About): an article shown through a menu item of its own, rather than a post opened from a list.
     *
     * @return  boolean
     *
     * @since   3.17.0
     */
    public static function isPage()
    {
        $app    = JFactory::getApplication();
        $active = $app->getMenu()->getActive();

        return $active && isset($active->query['option'], $active->query['view'], $active->query['id'])
            && $active->query['option'] === 'com_content' && $active->query['view'] === 'article'
            && $app->input->get('option') === 'com_content' && $app->input->get('view') === 'article'
            && (int) $active->query['id'] === $app->input->getInt('id');
    }

    /**
     * A tag view's item (com_tags, core_* fields) as a post-like object for the helpers.
     *
     * @param   object  $item  The tagged item
     *
     * @return  object
     *
     * @since   3.17.0
     */
    public static function fromTagItem($item)
    {
        JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');

        $post                 = new stdClass();
        $post->id             = (int) $item->content_item_id;
        $post->title          = $item->core_title;
        $post->introtext      = $item->core_body;
        $post->images         = $item->core_images;
        $post->publish_up     = $item->core_publish_up;
        $post->catid          = (int) $item->core_catid;
        $post->language       = $item->core_language;
        $post->author         = isset($item->author) ? $item->author : '';
        $post->link           = JRoute::_(TagsHelperRoute::getItemRoute(
            $item->content_item_id,
            $item->core_alias,
            $item->core_catid,
            $item->core_language,
            $item->type_alias,
            $item->router
        ));
        $category             = $post->catid ? JCategories::getInstance('Content')->get($post->catid) : null;
        $post->category_title = $category ? $category->title : '';

        return $post;
    }

    /**
     * The template's five tones, by name: a topic's colour, given as a class in its menu item's Page Class (e.g. tone-mint)
     *
     * @var    string[]
     * @since  3.17.0
     */
    public const TONES = array('lavender', 'sunflower', 'coral', 'mint', 'sky');

    /**
     * The colour of a topic: the tone in the Page Class of the menu item showing the category (Category Blog or List), else
     * one picked by the category's ID (consecutive IDs get different tones). A post keeps its topic's colour everywhere: lists,
     * its page, "Read next".
     *
     * @param   object  $item  The post (its catid) or the category (its id)
     *
     * @return  string  A class: tone-lavender, tone-sunflower, tone-coral, tone-mint or tone-sky
     *
     * @since   3.17.0
     */
    public static function tone($item)
    {
        static $chosen = null;

        if ($chosen === null) {
            $chosen = array();

            foreach ((array) JFactory::getApplication()->getMenu()->getItems('component', 'com_content') as $menuItem) {
                if (isset($menuItem->query['view'], $menuItem->query['id']) && $menuItem->query['view'] === 'category'
                    && preg_match('/(?:^|\s)tone-(' . implode('|', static::TONES) . ')(?:\s|$)/', (string) $menuItem->getParams()->get('pageclass_sfx'), $match)) {
                    $chosen[(int) $menuItem->query['id']] = 'tone-' . $match[1];
                }
            }
        }

        $id = isset($item->catid) ? (int) $item->catid : (isset($item->id) ? (int) $item->id : 0);

        return isset($chosen[$id]) ? $chosen[$id] : 'tone-' . static::TONES[$id % 5];
    }

    /**
     * The colour scheme: the template's option, light, dark, or the device's setting (auto).
     *
     * @return  string  auto, light or dark
     *
     * @since   3.17.0
     */
    public static function scheme()
    {
        // The error page calls it too, when something may have failed already: never let the option break that page
        try {
            $scheme = (string) static::params()->get('colorScheme', 'auto');
        } catch (Exception $e) {
            $scheme = 'auto';
        }

        return in_array($scheme, array('light', 'dark'), true) ? $scheme : 'auto';
    }

    /**
     * The publishing date as a stamp: the day large, the month and year under it.
     *
     * @param   object  $item  The post
     *
     * @return  string  Empty without a date
     *
     * @since   3.17.0
     */
    public static function stamp($item)
    {
        $value = isset($item->publish_up) ? $item->publish_up : (isset($item->created) ? $item->created : '');

        if (!$value || $value === JFactory::getDbo()->getNullDate()) {
            return '';
        }

        return '<time class="postStamp" datetime="' . JFactory::getDate($value)->format('c') . '"><span class="stampDay">'
            . static::e(JHtml::_('date', $value, 'j')) . '</span><span class="stampMonth">' . static::e(JHtml::_('date', $value, 'M Y')) . '</span></time>';
    }

    /**
     * A post in a list: the date stamp, its topic, title, excerpt and reading time, and its image on a block of the topic's
     * colour. The lead post (the newest, on a list's first page) is a band of that colour across the page.
     *
     * @param   object   $item   The post
     * @param   boolean  $lead   The lead post
     * @param   boolean  $eager  Its image is at the top of the page: load it at once
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function card($item, $lead = false, $eager = false)
    {
        $link    = static::link($item);
        $minutes = isset($item->fulltext) || isset($item->text) ? JText::sprintf('TPL_FINCH_READING_TIME', static::readingTime($item)) : '';
        $edit    = static::editLink($item);
        $h       = $lead ? 2 : 3;

        return '<article class="post ' . static::tone($item) . ($lead ? ' postLead' : '') . '"><div class="postInner">'
            . static::stamp($item)
            . '<div class="postBody">' . static::category($item)
            . '<h' . $h . ' class="postTitle"><a href="' . $link . '">' . static::e($item->title) . '</a></h' . $h . '>'
            . '<p class="postExcerpt">' . static::excerpt($item, $lead ? 260 : 190) . '</p>'
            . ($minutes !== '' || $edit !== '' ? '<p class="postMeta">' . ($minutes !== '' ? '<span class="postReading">' . $minutes . '</span>' : '') . $edit . '</p>' : '')
            . '</div>'
            . static::figure($item, $link, 'postImage', !$lead && !$eager)
            . '</div></article>';
    }

    /**
     * A list of posts, one after the other: the newest with a wide image on the first page of a list, the others with a small
     * one beside their text. The first two images load at once, the others lazily.
     *
     * @param   object[]  $items  The posts
     * @param   boolean   $lead   Show the first post large
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function postList(array $items, $lead = true)
    {
        $html = '';

        foreach (array_values($items) as $i => $item) {
            $html .= static::card($item, $lead && !$i, $i < 2);
        }

        return '<div class="postList">' . $html . '</div>';
    }

    /**
     * An HTML attribute as browsers read it in a tag: its name (group 1), then optionally "=" and a double-quoted,
     * single-quoted or unquoted value
     *
     * @var    string
     * @since  3.17.0
     */
    /**
     * How wide images are shown, for srcset: in the article text, in lists, at the top of an article
     */
    public const SIZES_TEXT = '(max-width: 800px) 100vw, 800px';
    public const SIZES_CARD = '(max-width: 767px) 100vw, 600px';
    public const SIZES_MAIN = '(max-width: 900px) 100vw, 900px';

    public const HTML_ATTRIBUTE = '([^\t\n\f\r \/>][^\t\n\f\r \/>=]*)(?>[\t\n\f\r ]*=[\t\n\f\r ]*(?>"[^"]*"|\'[^\']*\'|[^\t\n\f\r >]+)?)?';

    /**
     * The srcset of an image of the site which has a small screen copy next to it: the copy the Media Manager saves on upload
     * (photo-600.webp, its Small Screen Width) or the sample images' -960 one. Empty for other images, images elsewhere and
     * addresses a srcset can't hold (spaces, commas).
     *
     * @param   string  $src  The image's address, as in its src
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function srcsetFor($src)
    {
        static $mobile = null, $cache = array();

        $src = (string) $src;

        if (isset($cache[$src])) {
            return $cache[$src];
        }

        $cache[$src] = '';
        $path        = preg_replace('/[?#].*$/', '', $src);
        $root        = JUri::root(true);

        if (preg_match('#^(https?:)?//#i', $path)) {
            // The site's own address only
            $site = preg_replace('#^https?:#i', '', rtrim(JUri::root(), '/'));
            $path = preg_replace('#^https?:#i', '', $path);

            if (stripos($path, $site . '/') !== 0) {
                return '';
            }

            $path = substr($path, strlen($site));
        } elseif ($root !== '' && strpos($path, $root . '/') === 0) {
            $path = substr($path, strlen($root));
        }

        $path = rawurldecode(ltrim($path, '/'));

        if ($path === '' || preg_match('#(^|/)\.\.(/|$)|[\s,]#', $path . $src) || !preg_match('/^(.+)\.(webp|jpe?g|png|avif)$/i', $path, $parts)) {
            return '';
        }

        if ($mobile === null) {
            $mobile = (int) JComponentHelper::getParams('com_media')->get('optimise_mobile_width', 600);
        }

        foreach (array_unique(array_filter(array($mobile, 960))) as $width) {
            if (is_file(JPATH_ROOT . '/' . $parts[1] . '-' . $width . '.' . $parts[2]) && is_file(JPATH_ROOT . '/' . $path)) {
                $size  = @getimagesize(JPATH_ROOT . '/' . $path);
                $clean = preg_replace('/[?#].*$/', '', $src);
                $copy  = preg_replace('/(\.[A-Za-z0-9]+)$/', '-' . $width . '$1', $clean);

                return $cache[$src] = $size && $size[0] > $width ? $copy . ' ' . $width . 'w, ' . $clean . ' ' . (int) $size[0] . 'w' : '';
            }
        }

        return '';
    }

    /**
     * srcset and sizes attributes for an image (with a leading space), or nothing when it has no small screen copy.
     *
     * @param   string|null  $src    The image's address
     * @param   string       $sizes  How wide it's shown
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function srcsetAttributes($src, $sizes)
    {
        $srcset = $src === null ? '' : static::srcsetFor($src);

        return $srcset === '' ? '' : ' srcset="' . static::e($srcset) . '" sizes="' . static::e($sizes) . '"';
    }

    /**
     * The value of an attribute in a tag's attributes, as browsers read them (entities decoded), or null.
     *
     * @param   string  $attributes  The tag's attributes
     * @param   string  $name        The attribute
     *
     * @return  string|null
     *
     * @since   3.18.0
     */
    protected static function attributeValue($attributes, $name)
    {
        preg_match_all('/([^\t\n\f\r \/>][^\t\n\f\r \/>=]*)(?>[\t\n\f\r ]*=[\t\n\f\r ]*("[^"]*"|\'[^\']*\'|[^\t\n\f\r >]+)?)?/', (string) $attributes, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (strtolower($match[1]) === $name) {
                $value = isset($match[2]) ? $match[2] : '';
                $value = strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") ? substr($value, 1, -1) : $value;

                return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return null;
    }

    /**
     * Load the images and embedded frames of some HTML (an article's text, a module) only when they come near the screen:
     * loading=lazy for those which don't say how to load (an image with fetchpriority is meant to load at once).
     *
     * The HTML has been filtered already, so this must never change what it means: it reads the tags from the start, each
     * whole (a quoted attribute value may hold "<" and ">", e.g. an "<img" inside a title), leaves comments and the content
     * of the elements browsers don't read as HTML alone (script, style, iframe, textarea...), stops at the first tag it
     * can't read, and adds its attributes without quotes, so that even a misread could never close an attribute value.
     *
     * @param   string   $html        The HTML
     * @param   boolean  $eagerFirst  Leave the first image as it is (when it's the top of the page)
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function lazyImages($html, $eagerFirst = false)
    {
        $html   = (string) $html;
        $output = '';
        $offset = 0;

        while (($start = strpos($html, '<', $offset)) !== false) {
            $output .= substr($html, $offset, $start - $offset);

            // A comment, as browsers end it ("<!-->" and "<!--->" at once, otherwise at "-->" or "--!>"), or what they read as one
            // ("<!...>", "<?...>", "</" not followed by a letter): as it is
            if (preg_match('/\G(?:<!--(?:-?>|.*?--!?>)|<!(?!--)[^>]*>|<\?[^>]*>|<\/(?![A-Za-z])[^>]*>)/s', $html, $match, 0, $start)) {
                $output .= $match[0];
                $offset  = $start + strlen($match[0]);

                continue;
            }

            // A tag, read whole as browsers read it: its name runs up to a space, "/" or ">"; a value is quoted only when the quote
            // comes right after "=" (an unquoted value may hold quotes, and a quoted one "<" and ">")
            if (preg_match('/\G<(\/?[A-Za-z][^\t\n\f\r \/>]*)((?>[\t\n\f\r \/]+|' . self::HTML_ATTRIBUTE . ')*)>/', $html, $match, 0, $start)) {
                $tag  = $match[0];
                $name = strtolower($match[1]);

                preg_match_all('/' . self::HTML_ATTRIBUTE . '/', $match[2], $attributes);

                $names  = array_map('strtolower', $attributes[1]);
                $insert = '';

                if (($name === 'img' || $name === 'iframe') && !array_intersect($names, array('loading', 'fetchpriority'))) {
                    if ($name === 'img' && $eagerFirst) {
                        $eagerFirst = false;
                    } else {
                        $insert .= ' loading=lazy' . ($name === 'img' ? ' decoding=async' : '');
                    }
                }

                // The small screen copy the Media Manager saves next to uploaded images, for phones (srcset), on images without one
                if ($name === 'img' && !in_array('srcset', $names, true)) {
                    $insert .= static::srcsetAttributes(static::attributeValue($match[2], 'src'), static::SIZES_TEXT);
                }

                if ($insert !== '') {
                    $tag = '<' . $match[1] . $insert . ' ' . ltrim($match[2]) . '>';
                }

                $output .= $tag;
                $offset  = $start + strlen($match[0]);

                // Elements whose content browsers read as text, not HTML: as it is, up to their end tag (all the rest without one)
                if (in_array($name, array('script', 'style', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript', 'textarea', 'title', 'plaintext'), true)) {
                    // They end only at "</name" followed by a space, "/" or ">"
                    if ($name === 'plaintext' || !preg_match('/<\/' . $name . '(?=[\t\n\f\r \/>])/i', $html, $end, PREG_OFFSET_CAPTURE, $offset)) {
                        return $output . substr($html, $offset);
                    }

                    $output .= substr($html, $offset, $end[0][1] - $offset);
                    $offset  = $end[0][1];
                }

                continue;
            }

            // The start of a tag which can't be read (e.g. an unterminated quote): the rest stays as it is
            if (preg_match('/\G<[A-Za-z\/!?]/', $html, $match, 0, $start)) {
                return $output . substr($html, $start);
            }

            // A "<" in the text
            $output .= '<';
            $offset  = $start + 1;
        }

        return $output . substr($html, $offset);
    }

    /**
     * The page links of a list, from the pagination's data: getPagesLinks() would load Bootstrap's tooltips, and with them
     * jQuery, for the Start/Prev/Next/End links' titles.
     *
     * @param   JPagination  $pagination  The list's pagination
     *
     * @return  string
     *
     * @since   3.17.0
     */
    public static function pagination($pagination)
    {
        $data  = $pagination->getData();
        $items = array(array($data->start, 'pagination-start'), array($data->previous, 'pagination-prev'));

        foreach ($data->pages as $page) {
            $items[] = array($page, '');
        }

        $items[] = array($data->next, 'pagination-next');
        $items[] = array($data->end, 'pagination-end');
        $html    = '<ul>';

        foreach ($items as $entry) {
            list($item, $class) = $entry;

            if ($item->link !== null) {
                $html .= '<li' . ($class ? ' class="' . $class . '"' : '') . '><a href="' . $item->link . '" class="pagenav">' . $item->text . '</a></li>';
            } elseif (!empty($item->active)) {
                $html .= '<li class="' . trim($class . ' active') . '"><span class="pagenav" aria-current="page">' . $item->text . '</span></li>';
            } else {
                $html .= '<li class="' . trim($class . ' disabled') . '"><span class="pagenav">' . $item->text . '</span></li>';
            }
        }

        return $html . '</ul>';
    }
}
