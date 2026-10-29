<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.easymde
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * EasyMDE: a Markdown editor. Content is still stored as HTML (what the site, modules, feeds and search read): the editor
 * turns it into Markdown when it opens and back into HTML when the form is saved, keeping whatever Markdown can't express as
 * HTML inside the Markdown. Files dropped or pasted into it are uploaded through the Media Manager.
 *
 * @since  3.18.0
 */
class PlgEditorEasymde extends JPlugin
{
    /**
     * Where EasyMDE, its libraries and the plugin's scripts are
     *
     * @var    string
     * @since  3.18.0
     */
    public const MEDIA = 'plugins/editors/easymde';

    /**
     * @var    boolean
     * @since  3.18.0
     */
    protected $autoloadLanguage = true;

    /**
     * @var    JApplicationCms
     * @since  3.18.0
     */
    protected $app = null;

    /**
     * Loads EasyMDE, the converters and the Joomla integration.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function onInit()
    {
        $min = JDEBUG ? '' : '.min';

        JHtml::_('behavior.core');
        JHtml::_('stylesheet', self::MEDIA . '/vendor/easymde/easymde.min.css', array('version' => 'auto'));
        JHtml::_('stylesheet', self::MEDIA . '/css/easymde' . $min . '.css', array('version' => 'auto'));
        JHtml::_('script', self::MEDIA . '/vendor/easymde/easymde.min.js', array('version' => 'auto'));
        JHtml::_('script', self::MEDIA . '/vendor/marked/marked.umd.js', array('version' => 'auto'));
        JHtml::_('script', self::MEDIA . '/vendor/turndown/turndown.browser.umd' . $min . '.js', array('version' => 'auto'));
        JHtml::_('script', self::MEDIA . '/vendor/turndown-plugin-gfm/turndown-plugin-gfm' . $min . '.js', array('version' => 'auto'));
        JHtml::_('script', self::MEDIA . '/js/convert' . $min . '.js', array('version' => 'auto'));
        JHtml::_('script', self::MEDIA . '/js/easymde' . $min . '.js', array('version' => 'auto'));

        foreach (array('BOLD', 'ITALIC', 'STRIKETHROUGH', 'HEADING', 'QUOTE', 'UNORDERED_LIST', 'ORDERED_LIST', 'LINK', 'IMAGE', 'UPLOAD',
            'TABLE', 'CODE', 'HORIZONTAL_RULE', 'PREVIEW', 'SIDE_BY_SIDE', 'FULLSCREEN', 'UNDO', 'REDO', 'GUIDE', 'UPLOADING', 'UPLOAD_FAILED',
            'UPLOAD_HINT', 'WORDS', 'LINES', 'SIDE_BY_SIDE_LABEL') as $key) {
            JText::script('PLG_EDITORS_EASYMDE_' . $key);
        }
    }

    /**
     * @param   string  $id  The editor's id
     *
     * @return  string  JavaScript which gets the content (HTML)
     *
     * @since   3.18.0
     */
    public function onGetContent($id)
    {
        return 'Joomla.editors.instances[' . json_encode($id) . '].getValue();';
    }

    /**
     * @param   string  $id    The editor's id
     * @param   string  $html  The content (HTML)
     *
     * @return  string  JavaScript which sets the content
     *
     * @since   3.18.0
     */
    public function onSetContent($id, $html)
    {
        return 'Joomla.editors.instances[' . json_encode($id) . '].setValue(' . json_encode($html) . ');';
    }

    /**
     * Writes the editor's content, as HTML, into the form's field (views call it before submitting).
     *
     * @param   string  $id  The editor's id
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function onSave($id)
    {
        return 'if (Joomla.editors.instances[' . json_encode($id) . ']) { Joomla.editors.instances[' . json_encode($id) . '].onSave(); }';
    }

    /**
     * @param   string  $name  The editor's name
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function onGetInsertMethod($name)
    {
    }

    /**
     * Display the editor: the form's textarea (which keeps the HTML the form posts), hidden once the editor has started, and
     * the editor buttons below it.
     *
     * @param   string   $name     The name of the editor area
     * @param   string   $content  The content
     * @param   string   $width    The width of the editor area
     * @param   string   $height   The height of the editor area
     * @param   int      $col      The number of columns
     * @param   int      $row      The number of rows
     * @param   boolean  $buttons  True, false or a list of editor buttons to leave out
     * @param   string   $id       The textarea's id; the name when empty
     * @param   string   $asset    The asset of the item
     * @param   object   $author   The author of the item
     * @param   array    $params   Editor parameters (e.g. readonly)
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function onDisplay(
        $name,
        $content,
        $width,
        $height,
        $col,
        $row,
        $buttons = true,
        $id = null,
        $asset = null,
        $author = null,
        $params = array()
    ) {
        $id      = preg_replace('/(\s|[^A-Za-z0-9_])+/', '_', empty($id) ? $name : $id);
        $options = array(
            'height'      => $this->cssSize($this->params->get('height', '500px'), '500px'),
            'spellcheck'  => (bool) $this->params->get('spellcheck', 1),
            'sideBySide'  => (bool) $this->params->get('side_by_side', 1),
            'readonly'    => !empty($params['readonly']),
            'guide'       => 'https://www.markdownguide.org/basic-syntax/',
        );

        // Files dropped, pasted or chosen with the upload button go to the images folder through the Media Manager
        if ($this->params->get('upload', 1) && JFactory::getUser()->authorise('core.create', 'com_media')) {
            $folder = trim(str_replace('\\', '/', (string) $this->params->get('folder', 'uploads')), '/');

            $options['upload'] = array(
                'url'    => JUri::base(true) . '/index.php?option=com_media&task=file.upload&format=json',
                'token'  => JSession::getFormToken(),
                'folder' => preg_match('#(^|/)\.\.(/|$)|[^A-Za-z0-9_/-]#', $folder) ? '' : $folder,
                'root'   => JUri::root(true),
            );
        }

        // The editor buttons (editors-xtd plugins) below the editor, as with Joomla's other editors
        $xtd = is_array($buttons) || $buttons === true
            ? JLayoutHelper::render('joomla.editors.buttons', $this->_subject->getButtons($id, $buttons, $asset, $author)) : '';

        return '<div class="js-editor-easymde">'
            . '<textarea name="' . htmlspecialchars($name, ENT_COMPAT, 'UTF-8') . '" id="' . $id . '" cols="' . (int) $col . '" rows="' . (int) $row
            . '" class="joomla-editor-easymde" style="width:100%;height:' . htmlspecialchars($options['height'], ENT_COMPAT, 'UTF-8') . ';"'
            . ' data-easymde="' . htmlspecialchars(json_encode($options), ENT_COMPAT, 'UTF-8') . '"'
            . (!empty($params['readonly']) ? ' readonly="readonly"' : '') . '>'
            // Joomla's editor field passes the content escaped already
            . $content
            . '</textarea>'
            . $xtd
            . '</div>';
    }

    /**
     * A CSS size from the options: a number (pixels) or a number with px, em, rem, vh or %.
     *
     * @param   string  $value     The option
     * @param   string  $fallback  When it's neither
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected function cssSize($value, $fallback)
    {
        $value = trim((string) $value);

        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }

        return preg_match('/^\d+(\.\d+)?(px|em|rem|vh|%)$/', $value) ? $value : $fallback;
    }
}
