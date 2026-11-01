<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Blocks;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;

/**
 * Turns blocks (Editor.js' JSON: a list of {type, data}) into HTML, one layout per block type: layouts/joomla/blocks/<type>.php,
 * which a site template overrides as html/layouts/joomla/blocks/<type>.php. A type without a layout writes nothing.
 *
 * Layouts get the block's data and these helpers: inline() for text which may hold inline formatting (bold, italic, links...),
 * url() for addresses, e() for plain text. Values are never written without one of them.
 *
 * @since  3.18.0
 */
class BlockRenderer
{
    /**
     * Inline elements text may keep, with the attributes they may keep
     *
     * @var    array
     * @since  3.18.0
     */
    public const INLINE = array(
        'a'      => array('href'),
        'b'      => array(),
        'strong' => array(),
        'i'      => array(),
        'em'     => array(),
        'u'      => array('class'),
        's'      => array(),
        'mark'   => array('class'),
        'code'   => array('class'),
        'sub'    => array(),
        'sup'    => array(),
        'br'     => array(),
    );

    /**
     * The site template whose overrides apply
     *
     * @var    string
     * @since  3.18.0
     */
    protected $template;

    /**
     * @param   string  $template  The site template (its folder name); the site's default template when empty
     *
     * @since   3.18.0
     */
    public function __construct($template = '')
    {
        $this->template = $template !== '' ? $template : static::getSiteTemplate();
    }

    /**
     * The HTML of a list of blocks.
     *
     * @param   array  $blocks  The blocks: arrays with "type" and "data"
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function render(array $blocks)
    {
        $html = array();

        foreach ($blocks as $block) {
            $block = (array) $block;
            $type  = isset($block['type']) ? (string) $block['type'] : '';

            if (!preg_match('/^[a-z][a-z0-9_-]{0,49}$/', $type)) {
                continue;
            }

            $output = $this->renderBlock($type, isset($block['data']) && is_array($block['data']) ? $block['data'] : array(), $block);

            if ($output !== '') {
                $html[] = $output;
            }
        }

        return implode("\n", $html);
    }

    /**
     * The HTML of one block, through its layout.
     *
     * @param   string  $type   The block type
     * @param   array   $data   Its data
     * @param   array   $block  The whole block (id, tunes)
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected function renderBlock($type, array $data, array $block)
    {
        if ($type === 'template') {
            return $this->renderTemplateBlock($data, $block);
        }

        $layout = new FileLayout('joomla.blocks.' . $type, null, array('client' => 0, 'component' => 'none'));
        $paths  = array(JPATH_ROOT . '/layouts');

        if ($this->template !== '' && preg_match('/^[A-Za-z0-9_.-]+$/', $this->template)) {
            array_unshift($paths, JPATH_ROOT . '/templates/' . $this->template . '/html/layouts');
        }

        $layout->setIncludePaths($paths);

        // Nothing when no layout of that name exists
        return trim($layout->render(array('type' => $type, 'data' => $data, 'block' => $block, 'renderer' => $this)));
    }

    /**
     * A whole page showing rendered blocks as the site template shows content, for the editor's preview: the layout
     * joomla.blocks.preview (the template's html/layouts/joomla/blocks/preview.php, else the core one, which loads the template's
     * css/template.css and css/custom.css). Its displayData: html, template, params (the default style's), base (the site's
     * address), language, direction, renderer.
     *
     * @param   string  $html  The rendered blocks
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function preview($html)
    {
        $layout = new FileLayout('joomla.blocks.preview', null, array('client' => 0, 'component' => 'none'));
        $paths  = array(JPATH_ROOT . '/layouts');

        if ($this->template !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $this->template)) {
            array_unshift($paths, JPATH_ROOT . '/templates/' . $this->template . '/html/layouts');
        }

        $layout->setIncludePaths($paths);

        // PDF files ({pdf ...}) in the viewer, as the site shows them: the page gets its files (head)
        $head = '';

        if (strpos((string) $html, '{pdf') !== false && \JPluginHelper::isEnabled('content', 'pdf')) {
            \JLoader::register('PlgContentPdf', JPATH_PLUGINS . '/content/pdf/pdf.php');
            $plugin = \JPluginHelper::getPlugin('content', 'pdf');
            $html   = \PlgContentPdf::replace((string) $html, false, new \Joomla\Registry\Registry($plugin ? $plugin->params : ''));
            $head   = static::pdfViewerHead();
        }

        return $layout->render(array(
            'html'      => (string) $html,
            'head'      => $head,
            'template'  => $this->template,
            'params'    => static::getSiteTemplateParams(),
            'base'      => rtrim(Uri::root(), '/') . '/',
            'language'  => Factory::getLanguage()->getTag(),
            'direction' => Factory::getLanguage()->isRtl() ? 'rtl' : 'ltr',
            'renderer'  => $this,
        ));
    }

    /**
     * What a preview page showing PDF files needs in its head: the viewer's stylesheet and scripts (frontend.js for its options
     * and strings) and the options JHtml behavior.pdf set
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected static function pdfViewerHead()
    {
        $document = Factory::getDocument();
        $root     = Uri::root();
        $version  = '?' . $document->getMediaVersion();
        $options  = array('system.pdfviewer' => $document->getScriptOptions('system.pdfviewer'), 'joomla.jtext' => \JText::script());
        $url      = function ($path) use ($root, $version) {
            return htmlspecialchars($root . $path . $version, ENT_QUOTES, 'UTF-8');
        };

        return '<link rel="stylesheet" href="' . $url('media/system/css/pdf-viewer.min.css') . '" />' . "\n"
            . '<script type="application/json" class="joomla-script-options new">'
            . json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) . '</script>' . "\n"
            . '<script src="' . $url('media/system/js/frontend.min.js') . '"></script>' . "\n"
            . '<script src="' . $url('media/system/js/pdf-viewer.min.js') . '" defer></script>' . "\n";
    }

    /**
     * The blocks editor's hidden element (the whole document's blocks, signed) in stored text, as it writes it
     *
     * @var    string
     * @since  3.18.0
     */
    public const MARKER = '#<div class="joomla-blocks" hidden="hidden" data-version="1" data-hash="[0-9a-f]{1,8}" data-signature="[0-9a-f]{64}"'
        . ' data-blocks="[A-Za-z0-9+/=]+">(?:[ \t\r\n]|&nbsp;|&\#160;|\xC2\xA0)*</div>\n?#';

    /**
     * Text without the blocks editor's hidden element: for an article's intro shown to readers who may not see the rest, as
     * the element holds the whole document (articles saved before 3.18 had it in the intro)
     *
     * @param   string  $html  The text
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function stripMarker($html)
    {
        $html = (string) $html;

        return strpos($html, 'joomla-blocks') === false ? $html : preg_replace(static::MARKER, '', $html);
    }

    /**
     * The options of the site's default template style.
     *
     * @return  \Joomla\Registry\Registry
     *
     * @since   3.18.0
     */
    public static function getSiteTemplateParams()
    {
        try {
            $db = Factory::getDbo();

            return new \Joomla\Registry\Registry((string) $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('params'))
                    ->from($db->quoteName('#__template_styles'))
                    ->where($db->quoteName('client_id') . ' = 0')
                    ->where($db->quoteName('home') . ' = ' . $db->quote('1'))
            )->loadResult());
        } catch (\RuntimeException $e) {
            return new \Joomla\Registry\Registry();
        }
    }

    /**
     * A template's block (data.template: the folder its blocks are in, data.block: its name, data.fields: what was entered):
     * html/layouts/joomla/blocks/<template>/<block>.php of the site template (which may restyle another template's blocks),
     * else of the template which has it (installed, even when it isn't the default), else the core layout
     * joomla.blocks.template, which writes the fields plainly, so content never comes out empty.
     *
     * @param   array  $data   The block's data
     * @param   array  $block  The whole block
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected function renderTemplateBlock(array $data, array $block)
    {
        $namespace = isset($data['template']) ? (string) $data['template'] : '';
        $name      = isset($data['block']) ? (string) $data['block'] : '';
        $fields    = isset($data['fields']) && is_array($data['fields']) ? $data['fields'] : array();

        if (!preg_match('/^[a-z0-9_]{1,50}$/', $namespace) || !preg_match('/^[a-z0-9_-]{1,50}$/', $name)) {
            return '';
        }

        $displayData = array('type' => 'template', 'template' => $namespace, 'name' => $name, 'data' => $fields, 'block' => $block, 'renderer' => $this);
        $paths       = array();

        foreach (array_unique(array_merge(array($this->template, $namespace), static::getSiteTemplates())) as $template) {
            if ($template !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $template)
                && is_file(JPATH_ROOT . '/templates/' . $template . '/html/layouts/joomla/blocks/' . $namespace . '/' . $name . '.php')) {
                $paths[] = JPATH_ROOT . '/templates/' . $template . '/html/layouts';
            }
        }

        if ($paths) {
            $layout = new FileLayout('joomla.blocks.' . $namespace . '.' . $name, null, array('client' => 0, 'component' => 'none'));
            $layout->setIncludePaths($paths);

            return trim($layout->render($displayData));
        }

        $layout = new FileLayout('joomla.blocks.template', null, array('client' => 0, 'component' => 'none'));
        $layout->setIncludePaths(array(JPATH_ROOT . '/layouts'));

        return trim($layout->render($displayData));
    }

    /**
     * An image field (url, alt, width, height, srcset: a list of {location, width}, as the editor saves uploads) as an <img>,
     * with its srcset and size when it has them. Empty without an address.
     *
     * @param   mixed   $image    The field's value
     * @param   string  $sizes    The sizes attribute for a srcset (default: up to the image's own width)
     * @param   array   $attribs  More attributes (name => value), escaped here
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function image($image, $sizes = '', array $attribs = array())
    {
        $image = is_array($image) ? $image : array();
        $src   = $this->siteUrl(isset($image['url']) ? $image['url'] : '');

        if ($src === '') {
            return '';
        }

        $srcset = array();
        $widest = 0;

        foreach (isset($image['srcset']) && is_array($image['srcset']) ? $image['srcset'] : array() as $source) {
            $url   = $this->siteUrl(is_array($source) && isset($source['location']) ? $source['location'] : '');
            $width = is_array($source) && isset($source['width']) ? (int) $source['width'] : 0;

            if ($url !== '' && $width > 0 && !preg_match('/[\s,]/', $url)) {
                $srcset[] = $url . ' ' . $width . 'w';
                $widest   = max($widest, $width);
            }
        }

        $html = '<img src="' . $this->e($src) . '" alt="' . $this->e(isset($image['alt']) ? $image['alt'] : '') . '"';

        if (count($srcset) > 1) {
            $html .= ' srcset="' . $this->e(implode(', ', $srcset)) . '" sizes="' . $this->e($sizes !== '' ? $sizes : '(max-width: ' . $widest . 'px) 100vw, ' . $widest . 'px') . '"';
        }

        if (!empty($image['width']) && !empty($image['height'])) {
            $html .= ' width="' . (int) $image['width'] . '" height="' . (int) $image['height'] . '"';
        }

        foreach ($attribs as $name => $value) {
            if (preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
                $html .= ' ' . $name . '="' . $this->e($value) . '"';
            }
        }

        return $html . ' />';
    }

    /**
     * The blocks templates offer: html/layouts/joomla/blocks/<folder>/<name>.xml (with its <name>.php beside it) in the installed
     * site templates. The folder names the blocks (the template's own name, so a copied template's blocks are the same blocks).
     * Labels are translated with the template's language strings.
     *
     * @return  array  key (folder/name) => template (the folder), block, title, description, icon, note (the template's name),
     *                 menu (offered in the block menu: the site template's), fields
     *
     * @since   3.18.0
     */
    public static function getDefinitions()
    {
        $default     = static::getSiteTemplate();
        $definitions = array();
        $templates   = static::getSiteTemplates();

        // The site template's first, so its own definition wins when a copy has the same blocks
        usort($templates, function ($a, $b) use ($default) {
            return ($b === $default) - ($a === $default);
        });

        foreach ($templates as $template) {
            $base = JPATH_ROOT . '/templates/' . $template . '/html/layouts/joomla/blocks';

            if (!preg_match('/^[A-Za-z0-9_-]+$/', $template) || !is_dir($base)) {
                continue;
            }

            Factory::getLanguage()->load('tpl_' . $template, JPATH_SITE, null, false, true)
                || Factory::getLanguage()->load('tpl_' . $template, JPATH_SITE . '/templates/' . $template, null, false, true);

            foreach ((array) glob($base . '/*/*.xml') as $file) {
                $namespace = basename(dirname($file));
                $name      = basename($file, '.xml');
                $key       = $namespace . '/' . $name;

                if (isset($definitions[$key]) || !preg_match('/^[a-z0-9_]{1,50}$/', $namespace) || !preg_match('/^[a-z0-9_-]{1,50}$/', $name)
                    || !is_file(dirname($file) . '/' . $name . '.php')) {
                    continue;
                }

                $xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NONET);

                if (!$xml || $xml->getName() !== 'block') {
                    continue;
                }

                $definitions[$key] = array(
                    'template'    => $namespace,
                    'block'       => $name,
                    'title'       => \JText::_((string) $xml['title'] ?: $name),
                    'description' => (string) $xml['description'] !== '' ? \JText::_((string) $xml['description']) : '',
                    'icon'        => preg_match('/^[a-z-]{1,30}$/', (string) $xml['icon']) ? (string) $xml['icon'] : 'block',
                    'note'        => ucfirst($template),
                    'menu'        => $template === $default,
                    'fields'      => static::readFields($xml),
                );
            }
        }

        return $definitions;
    }

    /**
     * A block definition's fields: field (name, type, label, default, options for lists) and group (name, label, item: the label
     * of one entry, min, max, columns, fields) elements.
     *
     * @param   \SimpleXMLElement  $xml    The block (or group) element
     * @param   boolean            $group  Whether groups may be read (not inside a group)
     *
     * @return  array
     *
     * @since   3.18.0
     */
    protected static function readFields($xml, $group = true)
    {
        $fields = array();
        $types  = array('text', 'richtext', 'textarea', 'image', 'url', 'list', 'checkbox');

        foreach ($xml->children() as $child) {
            $name = (string) $child['name'];

            if (!preg_match('/^[a-z][a-z0-9_]{0,49}$/', $name)) {
                continue;
            }

            if ($child->getName() === 'group' && $group) {
                $min = max(0, min(20, (int) $child['min']));

                $fields[] = array(
                    'name'    => $name,
                    'type'    => 'group',
                    'label'   => \JText::_((string) $child['label'] ?: $name),
                    'item'    => \JText::_((string) $child['item'] ?: ((string) $child['label'] ?: $name)),
                    'min'     => $min,
                    'max'     => max(max(1, $min), min(20, (int) $child['max'] ?: 20)),
                    'columns' => max(1, min(4, (int) $child['columns'] ?: 1)),
                    'fields'  => static::readFields($child, false),
                );

                continue;
            }

            $type = (string) $child['type'];

            if ($child->getName() !== 'field' || !in_array($type, $types, true)) {
                continue;
            }

            $field = array(
                'name'    => $name,
                'type'    => $type,
                'label'   => \JText::_((string) $child['label'] ?: $name),
                'default' => (string) $child['default'],
            );

            if ($type === 'list') {
                $field['options'] = array();

                foreach ($child->option as $option) {
                    $field['options'][] = array('value' => (string) $option['value'], 'text' => \JText::_(trim((string) $option)));
                }
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * The installed site templates (their folders).
     *
     * @return  string[]
     *
     * @since   3.18.0
     */
    public static function getSiteTemplates()
    {
        static $templates = null;

        if ($templates === null) {
            try {
                $db        = Factory::getDbo();
                $templates = (array) $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('element'))
                        ->from($db->quoteName('#__extensions'))
                        ->where($db->quoteName('type') . ' = ' . $db->quote('template'))
                        ->where($db->quoteName('client_id') . ' = 0')
                )->loadColumn();
            } catch (\RuntimeException $e) {
                $templates = array();
            }
        }

        return $templates;
    }

    /**
     * Text which may hold inline formatting, with nothing but the elements and attributes of INLINE left, and links only to
     * http(s), mailto, tel, the site's own pages or anchors.
     *
     * @param   mixed  $html  The text
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function inline($html)
    {
        $html = (string) $html;

        if ($html === '') {
            return '';
        }

        $attributes = array();

        foreach (static::INLINE as $names) {
            $attributes = array_merge($attributes, $names);
        }

        $filter = InputFilter::getInstance(array_keys(static::INLINE), array_unique($attributes), 0, 0);
        $html   = $filter->clean($html, 'html');

        // Links to other schemes lose their address
        return preg_replace_callback('/\shref="([^"]*)"/i', function ($match) {
            $url = $this->url(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));

            return $url === '' ? '' : ' href="' . $this->e($url) . '"';
        }, $html);
    }

    /**
     * An address which may be written: http(s), mailto and tel addresses, paths of the site and anchors. Anything else is
     * empty.
     *
     * @param   mixed  $url  The address
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function url($url)
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\x00-\x1f\x7f]/', $url)) {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $url) || preg_match('#^(mailto|tel):#i', $url)) {
            return $url;
        }

        // A scheme of any other kind (javascript:, data:...)
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return '';
        }

        return $url;
    }

    /**
     * An address of the site written as editors store it: images/x.jpg rather than /subfolder/images/x.jpg.
     *
     * @param   mixed  $url  The address
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function siteUrl($url)
    {
        $url  = $this->url($url);
        $root = rtrim(Uri::root(true), '/') . '/';

        // /subfolder/images/x.jpg (or /images/x.jpg on a site at the domain's root), never //host/...
        if ($url !== '' && strpos($url, '//') !== 0 && strpos($url, $root) === 0) {
            return substr($url, strlen($root));
        }

        return $url;
    }

    /**
     * Plain text, escaped.
     *
     * @param   mixed  $text  The text
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function e($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * The plain text of text which may hold inline formatting (for alt and title attributes).
     *
     * @param   mixed  $html  The text
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function plain($html)
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), ' ', (string) $html)), ENT_QUOTES, 'UTF-8')));
    }

    /**
     * The site's default template (its folder name).
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function getSiteTemplate()
    {
        try {
            $db = Factory::getDbo();

            return (string) $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('template'))
                    ->from($db->quoteName('#__template_styles'))
                    ->where($db->quoteName('client_id') . ' = 0')
                    ->where($db->quoteName('home') . ' = ' . $db->quote('1'))
            )->loadResult();
        } catch (\RuntimeException $e) {
            return '';
        }
    }
}
