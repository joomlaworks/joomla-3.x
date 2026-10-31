<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.pdf
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Shows the PDF files {pdf images/folder/file.pdf} names in a viewer (JHtml behavior.pdf)
 *
 * @since  3.18.0
 */
class PlgContentPdf extends JPlugin
{
    /**
     * The text of a paragraph holding only a shortcode: the viewer
     *
     * @var    string
     * @since  3.18.0
     */
    public const BLOCK = '#^\s*(?:&nbsp;\s*)*\{pdf\s+([^{}<>"]+?)\s*\}\s*(?:&nbsp;\s*)*$#i';

    /**
     * A shortcode anywhere else in text: a link to the file
     *
     * @var    string
     * @since  3.18.0
     */
    public const INLINE = '#\{pdf\s+([^{}<>"]+?)\s*\}#i';

    /**
     * An attribute of a tag, as browsers read it (as the site templates' helpers read tags)
     *
     * @var    string
     * @since  3.18.0
     */
    public const HTML_ATTRIBUTE = '([^\t\n\f\r \/>][^\t\n\f\r \/>=]*)(?>[\t\n\f\r ]*=[\t\n\f\r ]*(?>"[^"]*"|\'[^\']*\'|[^\t\n\f\r >]+)?)?';

    /**
     * @param   string   $context  The context of the content
     * @param   object   $row      The content: its text in $row->text
     * @param   mixed    $params   The content's parameters
     * @param   integer  $page     The page number
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        if (!is_object($row) || !isset($row->text) || !is_string($row->text) || stripos($row->text, '{pdf') === false) {
            return;
        }

        // Feeds, other formats and Smart Search's index get links
        $links = $context === 'com_finder.indexer' || JFactory::getDocument()->getType() !== 'html';

        $row->text = static::replace($row->text, $links, $this->params);
    }

    /**
     * The text with its shortcodes replaced
     *
     * @param   string                     $text    The text
     * @param   boolean                    $links   Links only, no viewers
     * @param   Joomla\Registry\Registry   $params  The plugin's options (height, download)
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function replace($text, $links = false, $params = null)
    {
        $viewers = false;
        $tokens  = static::tokens((string) $text);
        $output  = '';

        // Shortcodes in text only: one inside a tag (an attribute's value), a comment or an element browsers read as text
        // stays as it is, as markup put there would change what the filtered HTML means
        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            list($kind, $html, $name) = $tokens[$i];

            if ($kind === 'tag' && $name === 'p' && $i + 2 < $count && $tokens[$i + 1][0] === 'text' && $tokens[$i + 2][0] === 'tag'
                && $tokens[$i + 2][2] === '/p' && preg_match(static::BLOCK, $tokens[$i + 1][1], $match)) {
                $file = static::file($match[1]);
                $i   += 2;

                if ($file === null) {
                    continue;
                }

                if ($links) {
                    $output .= '<p>' . static::link($file) . '</p>';
                } else {
                    $viewers = true;
                    $output .= static::viewer($file, $params);
                }

                continue;
            }

            if ($kind === 'text') {
                $html = preg_replace_callback(static::INLINE, function ($match) {
                    $file = static::file($match[1]);

                    return $file === null ? '' : static::link($file);
                }, $html);
            }

            $output .= $html;
        }

        if ($viewers) {
            JHtml::_('behavior.pdf');
        }

        return $output;
    }

    /**
     * HTML in pieces, as browsers read it: [kind, html, name] with kind 'tag' (name: lower case, "/p" for an end tag), 'text'
     * or 'other' (comments, doctypes, the content of elements read as text such as script and textarea, an unreadable rest)
     *
     * @param   string  $html  The HTML
     *
     * @return  array
     *
     * @since   3.18.0
     */
    protected static function tokens($html)
    {
        $tokens = array();
        $offset = 0;
        $text   = function ($from, $to) use ($html, &$tokens) {
            if ($to > $from) {
                $tokens[] = array('text', substr($html, $from, $to - $from), '');
            }
        };

        while (($start = strpos($html, '<', $offset)) !== false) {
            // A comment as browsers end it, or what they read as one ("<!...>", "<?...>", "</" not followed by a letter)
            if (preg_match('/\G(?:<!--(?:-?>|.*?--!?>)|<!(?!--)[^>]*>|<\?[^>]*>|<\/(?![A-Za-z])[^>]*>)/s', $html, $match, 0, $start)) {
                $text($offset, $start);
                $tokens[] = array('other', $match[0], '');
                $offset   = $start + strlen($match[0]);
                continue;
            }

            if (preg_match('/\G<(\/?[A-Za-z][^\t\n\f\r \/>]*)((?>[\t\n\f\r \/]+|' . static::HTML_ATTRIBUTE . ')*)>/', $html, $match, 0, $start)) {
                $name = strtolower($match[1]);

                $text($offset, $start);
                $tokens[] = array('tag', $match[0], $name);
                $offset   = $start + strlen($match[0]);

                // Elements whose content browsers read as text, up to their end tag (all the rest without one)
                if (in_array($name, array('script', 'style', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript', 'textarea', 'title', 'plaintext'), true)) {
                    if ($name === 'plaintext' || !preg_match('/<\/' . $name . '(?=[\t\n\f\r \/>])/i', $html, $end, PREG_OFFSET_CAPTURE, $offset)) {
                        $tokens[] = array('other', substr($html, $offset), '');

                        return $tokens;
                    }

                    $tokens[] = array('other', substr($html, $offset, $end[0][1] - $offset), '');
                    $offset   = $end[0][1];
                }

                continue;
            }

            // The start of a tag which can't be read (e.g. an unterminated quote): the rest stays as it is
            if (preg_match('/\G<[A-Za-z\/!?]/', $html, $match, 0, $start)) {
                $text($offset, $start);
                $tokens[] = array('other', substr($html, $start), '');

                return $tokens;
            }

            // A "<" in the text
            $text($offset, $start + 1);
            $offset = $start + 1;
        }

        $text($offset, strlen($html));

        return $tokens;
    }

    /**
     * A PDF of the site: its path (images/x.pdf), address and size, or null when the value doesn't name one
     *
     * @param   string  $value  What the shortcode names: a path of the site, or an address on it
     *
     * @return  array|null
     *
     * @since   3.18.0
     */
    public static function file($value)
    {
        $path = trim(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));

        foreach (array(JUri::root(), JUri::root(true) . '/') as $prefix) {
            if ($prefix !== '/' && stripos($path, $prefix) === 0) {
                $path = substr($path, strlen($prefix));
            }
        }

        $path = rawurldecode(ltrim($path, '/'));

        if ($path === '' || strpos($path, "\0") !== false || strpos($path, '\\') !== false || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)
            || !preg_match('#\.pdf$#i', $path) || in_array('..', explode('/', $path), true)) {
            return null;
        }

        $full = realpath(JPATH_ROOT . '/' . $path);
        $root = realpath(JPATH_ROOT);

        if ($full === false || $root === false || !is_file($full) || strpos($full, $root . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return array(
            'path' => $path,
            'url'  => JUri::root(true) . '/' . implode('/', array_map('rawurlencode', explode('/', $path))),
            'name' => basename($path),
            'size' => (int) filesize($full),
        );
    }

    /**
     * A link to the file, with its size
     *
     * @param   array  $file  What file() gives
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected static function link(array $file)
    {
        return '<a class="cms-pdf-link" href="' . htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8')
            . '</a> (PDF, ' . JHtml::_('number.bytes', $file['size']) . ')';
    }

    /**
     * The viewer's element (media/system/js/pdf-viewer.js builds the viewer in it), with the link for when it can't
     *
     * @param   array                     $file    What file() gives
     * @param   Joomla\Registry\Registry  $params  The plugin's options
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected static function viewer(array $file, $params = null)
    {
        $height   = $params ? (int) $params->get('height', 0) : 0;
        $download = $params ? (int) $params->get('download', 1) : 1;

        return '<figure class="cms-pdf" data-pdf="' . htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8') . '" data-name="'
            . htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') . '"' . ($download ? '' : ' data-download="0"')
            . ($height >= 200 && $height <= 3000 ? ' style="--cms-pdf-height:' . $height . 'px"' : '') . '>'
            . '<p class="cms-pdf-file">' . static::link($file) . '</p></figure>';
    }
}
