<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Document\Renderer\Html;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Document\HtmlDocument;

/**
 * `<jdoc:include type="footer" />`, at the end of the page: the script files marked for it ('position' => 'footer'), the
 * shared script (global.js, frontend.js) on pages where nothing else uses it while loading, and the code of
 * addReadyScript(). Without this tag in the template, all of it stays in the head (HtmlDocument::getFooterScripts()).
 *
 * @since  3.18.0
 */
class FooterRenderer extends HeadRenderer
{
    /**
     * Renders the footer's scripts
     *
     * @param   string  $name     (unused)
     * @param   array   $params   Associative array of values
     * @param   string  $content  (unused)
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function render($name, $params = array(), $content = null)
    {
        $document = $this->_doc;

        if (!$document instanceof HtmlDocument || !$document->hasFooter()) {
            return '';
        }

        $buffer       = '';
        $mediaVersion = $document->getMediaVersion();

        foreach ($document->getFooterScripts() as $src) {
            if (isset($document->_scripts[$src])) {
                $buffer .= $this->renderScript($document, $src, $document->_scripts[$src], $mediaVersion, true);
            }
        }

        // Script files added after the head was written (by what renders between the two)
        $written = array_merge($document->getHeadScripts(), $document->getFooterScripts());

        foreach ($document->_scripts as $src => $attribs) {
            if (!in_array($src, $written, true)) {
                $buffer .= $this->renderScript($document, $src, $attribs, $mediaVersion);
            }
        }

        $buffer .= $this->renderReadyScripts($document);

        return ltrim($buffer, $document->_getTab());
    }
}
