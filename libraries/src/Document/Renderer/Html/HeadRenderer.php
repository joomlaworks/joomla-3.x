<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Document\Renderer\Html;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Document\DocumentRenderer;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Uri\Uri;

/**
 * HTML document renderer for the document `<head>` element
 *
 * @since  3.5
 */
class HeadRenderer extends DocumentRenderer
{
	/**
	 * Renders the document head and returns the results as a string
	 *
	 * @param   string  $head     (unused)
	 * @param   array   $params   Associative array of values
	 * @param   string  $content  The script
	 *
	 * @return  string  The output of the script
	 *
	 * @since   3.5
	 */
	public function render($head, $params = array(), $content = null)
	{
		return $this->fetchHead($this->_doc);
	}

	/**
	 * Generates the head HTML and return the results as a string
	 *
	 * @param   JDocumentHtml  $document  The document for which the head will be created
	 *
	 * @return  string  The head hTML
	 *
	 * @since   3.5
	 * @deprecated  4.0  Method code will be moved into the render method
	 */
	public function fetchHead($document)
	{
		// Convert the tagids to titles
		if (isset($document->_metaTags['name']['tags']))
		{
			$tagsHelper = new TagsHelper;
			$document->_metaTags['name']['tags'] = implode(', ', $tagsHelper->getTagNames($document->_metaTags['name']['tags']));
		}

		if ($document->getScriptOptions())
		{
			\JHtml::_('behavior.core');
		}

		// Trigger the onBeforeCompileHead event
		$app = \JFactory::getApplication();
		$app->triggerEvent('onBeforeCompileHead');

		// Get line endings
		$lnEnd        = $document->_getLineEnd();
		$tab          = $document->_getTab();
		$tagEnd       = ' />';
		$buffer       = '';
		$mediaVersion = $document->getMediaVersion();

		// Generate charset when using HTML5 (should happen first)
		if ($document->isHtml5())
		{
			$buffer .= $tab . '<meta charset="' . $document->getCharset() . '" />' . $lnEnd;
		}

		// Generate base tag (need to happen early)
		$base = $document->getBase();

		if (!empty($base))
		{
			$buffer .= $tab . '<base href="' . $base . '" />' . $lnEnd;
		}

		// Generate META tags (needs to happen as early as possible in the head)
		foreach ($document->_metaTags as $type => $tag)
		{
			foreach ($tag as $name => $content)
			{
				if ($type == 'http-equiv' && !($document->isHtml5() && $name == 'content-type'))
				{
					$buffer .= $tab . '<meta http-equiv="' . $name . '" content="' . htmlspecialchars((string) $content, ENT_COMPAT, 'UTF-8') . '" />' . $lnEnd;
				}
				elseif ($type != 'http-equiv' && !empty($content))
				{
					if (is_array($content))
					{
						foreach ($content as $value)
						{
							$buffer .= $tab . '<meta ' . $type . '="' . $name . '" content="' . htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8') . '" />' . $lnEnd;
						}
					}
					else
					{
						$buffer .= $tab . '<meta ' . $type . '="' . $name . '" content="' . htmlspecialchars((string) $content, ENT_COMPAT, 'UTF-8') . '" />' . $lnEnd;
					}
				}
			}
		}

		// Don't add empty descriptions
		$documentDescription = $document->getDescription();

		if ($documentDescription)
		{
			$buffer .= $tab . '<meta name="description" content="' . htmlspecialchars((string) $documentDescription, ENT_COMPAT, 'UTF-8') . '" />' . $lnEnd;
		}

		// Don't add empty generators
		$generator = $document->getGenerator();

		if ($generator)
		{
			$buffer .= $tab . '<meta name="generator" content="' . htmlspecialchars((string) $generator, ENT_COMPAT, 'UTF-8') . '" />' . $lnEnd;
		}

		$buffer .= $tab . '<title>' . htmlspecialchars((string) $document->getTitle(), ENT_COMPAT, 'UTF-8') . '</title>' . $lnEnd;

		// Generate link declarations: resource hints first (every preconnect, then every dns-prefetch, then preloads), then the
		// rest, each group in the order they were added
		$links = $this->sortLinks($document->_links);

		foreach ($links as $link)
		{
			$buffer .= $tab . '<link href="' . $this->escapeAttribute($link['href']) . '" ' . $link['relType'] . '="'
				. $this->escapeAttribute($link['relation']) . '"' . $this->linkAttributes($link['attribs']) . ' />' . $lnEnd;
		}

		if ($document instanceof HtmlDocument && $document->getLinkHeaders())
		{
			$this->sendLinkHeaders($links);
		}

		$defaultCssMimes = array('text/css');

		// Generate stylesheet links
		foreach ($document->_styleSheets as $src => $attribs)
		{
			// Check if stylesheet uses IE conditional statements.
			$conditional = isset($attribs['options']) && isset($attribs['options']['conditional']) ? $attribs['options']['conditional'] : null;

			// Check if script uses media version.
			if (isset($attribs['options']['version']) && $attribs['options']['version'] && strpos((string) $src, '?') === false
				&& ($mediaVersion || $attribs['options']['version'] !== 'auto'))
			{
				$src .= '?' . ($attribs['options']['version'] === 'auto' ? $mediaVersion : $attribs['options']['version']);
			}

			$buffer .= $tab;

			// This is for IE conditional statements support.
			if (!is_null($conditional))
			{
				$buffer .= '<!--[if ' . $conditional . ']>';
			}

			$relation = isset($attribs['rel']) ? $attribs['rel'] : 'stylesheet';
			$buffer .= '<link href="' . $src . '" rel="' . $relation . '"';

			// Add script tag attributes.
			foreach ($attribs as $attrib => $value)
			{
				// Don't add the 'options' attribute. This attribute is for internal use (version, conditional, etc).
				if ($attrib === 'options')
				{
					continue;
				}

				// Don't add type attribute if document is HTML5 and it's a default mime type. 'mime' is for B/C.
				if (in_array($attrib, array('type', 'mime')) && $document->isHtml5() && in_array($value, $defaultCssMimes))
				{
					continue;
				}

				// Don't add type attribute if document is HTML5 and it's a default mime type. 'mime' is for B/C.
				if ($attrib === 'mime')
				{
					$attrib = 'type';
				}

				// Add attribute to script tag output.
				$buffer .= ' ' . htmlspecialchars((string) $attrib, ENT_COMPAT, 'UTF-8');

				// Json encode value if it's an array.
				$value = !is_scalar($value) ? json_encode($value) : $value;

				$buffer .= '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"';
			}

			$buffer .= $tagEnd;

			// This is for IE conditional statements support.
			if (!is_null($conditional))
			{
				$buffer .= '<![endif]-->';
			}

			$buffer .= $lnEnd;
		}

		// Generate stylesheet declarations
		foreach ($document->_style as $type => $content)
		{
			$buffer .= $tab . '<style';

			if (!is_null($type) && (!$document->isHtml5() || !in_array($type, $defaultCssMimes)))
			{
				$buffer .= ' type="' . $type . '"';
			}

			$buffer .= '>' . $lnEnd;

			// This is for full XHTML support.
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '/*<![CDATA[*/' . $lnEnd;
			}

			$buffer .= $content . $lnEnd;

			// See above note
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '/*]]>*/' . $lnEnd;
			}

			$buffer .= $tab . '</style>' . $lnEnd;
		}

		// Generate scripts options
		$scriptOptions = $document->getScriptOptions();

		if (!empty($scriptOptions))
		{
			$buffer .= $tab . '<script type="application/json" class="joomla-script-options new">';

			$prettyPrint = (JDEBUG && defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : false);
			$jsonOptions = json_encode($scriptOptions, $prettyPrint);
			$jsonOptions = $jsonOptions ? $jsonOptions : '{}';

			$buffer .= $jsonOptions;
			$buffer .= '</script>' . $lnEnd;
		}

		$defaultJsMimes         = array('text/javascript', 'application/javascript', 'text/x-javascript', 'application/x-javascript');
		$html5NoValueAttributes = array('defer', 'async');

		// Generate script file links
		foreach ($document->_scripts as $src => $attribs)
		{
			// Check if script uses IE conditional statements.
			$conditional = isset($attribs['options']) && isset($attribs['options']['conditional']) ? $attribs['options']['conditional'] : null;

			// Check if script uses media version.
			if (isset($attribs['options']['version']) && $attribs['options']['version'] && strpos((string) $src, '?') === false
				&& ($mediaVersion || $attribs['options']['version'] !== 'auto'))
			{
				$src .= '?' . ($attribs['options']['version'] === 'auto' ? $mediaVersion : $attribs['options']['version']);
			}

			$buffer .= $tab;

			// This is for IE conditional statements support.
			if (!is_null($conditional))
			{
				$buffer .= '<!--[if ' . $conditional . ']>';
			}

			$buffer .= '<script src="' . $src . '"';

			// Add script tag attributes.
			foreach ($attribs as $attrib => $value)
			{
				// Don't add the 'options' attribute. This attribute is for internal use (version, conditional, etc).
				if ($attrib === 'options')
				{
					continue;
				}

				// Don't add type attribute if document is HTML5 and it's a default mime type. 'mime' is for B/C.
				if (in_array($attrib, array('type', 'mime')) && $document->isHtml5() && in_array($value, $defaultJsMimes))
				{
					continue;
				}

				// B/C: If defer and async is false or empty don't render the attribute.
				if (in_array($attrib, array('defer', 'async')) && !$value)
				{
					continue;
				}

				// Don't add type attribute if document is HTML5 and it's a default mime type. 'mime' is for B/C.
				if ($attrib === 'mime')
				{
					$attrib = 'type';
				}
				// B/C defer and async can be set to yes when using the old method.
				elseif (in_array($attrib, array('defer', 'async')) && $value === true)
				{
					$value = $attrib;
				}

				// Add attribute to script tag output.
				$buffer .= ' ' . htmlspecialchars((string) $attrib, ENT_COMPAT, 'UTF-8');

				if (!($document->isHtml5() && in_array($attrib, $html5NoValueAttributes)))
				{
					// Json encode value if it's an array.
					$value = !is_scalar($value) ? json_encode($value) : $value;

					$buffer .= '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"';
				}
			}

			$buffer .= '></script>';

			// This is for IE conditional statements support.
			if (!is_null($conditional))
			{
				$buffer .= '<![endif]-->';
			}

			$buffer .= $lnEnd;
		}

		// Generate script declarations
		foreach ($document->_script as $type => $content)
		{
			$buffer .= $tab . '<script';

			if (!is_null($type) && (!$document->isHtml5() || !in_array($type, $defaultJsMimes)))
			{
				$buffer .= ' type="' . $type . '"';
			}

			$buffer .= '>' . $lnEnd;

			// This is for full XHTML support.
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//<![CDATA[' . $lnEnd;
			}

			$buffer .= $content . $lnEnd;

			// See above note
			if ($document->_mime != 'text/html')
			{
				$buffer .= $tab . $tab . '//]]>' . $lnEnd;
			}

			$buffer .= $tab . '</script>' . $lnEnd;
		}

		// Output the custom tags - array_unique makes sure that we don't output the same tags twice
		foreach (array_unique($document->_custom) as $custom)
		{
			$buffer .= $tab . $custom . $lnEnd;
		}

		return ltrim($buffer, $tab);
	}

	/**
	 * The document's links in output order: preconnect, dns-prefetch, preload/modulepreload, then the others, each group in
	 * the order the links were added. Each comes with its address and a valid relation type.
	 *
	 * @param   array  $links  The document's $_links
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	protected function sortLinks(array $links)
	{
		$groups = array(array(), array(), array(), array());

		foreach ($links as $key => $link)
		{
			if (!is_array($link) || !isset($link['relation']))
			{
				continue;
			}

			$link['href']    = isset($link['href']) ? (string) $link['href'] : (string) $key;
			$link['relType'] = isset($link['relType']) && strtolower((string) $link['relType']) === 'rev' ? 'rev' : 'rel';
			$link['attribs'] = isset($link['attribs']) && is_array($link['attribs']) ? $link['attribs'] : array();

			$groups[$this->hintRank($link)][] = $link;
		}

		return array_merge($groups[0], $groups[1], $groups[2], $groups[3]);
	}

	/**
	 * A link's group: 0 preconnect, 1 dns-prefetch, 2 preload or modulepreload, 3 anything else.
	 *
	 * @param   array  $link  A link (href, relation, relType, attribs)
	 *
	 * @return  integer
	 *
	 * @since   3.18.0
	 */
	protected function hintRank(array $link)
	{
		if ($link['relType'] !== 'rel')
		{
			return 3;
		}

		$ranks = array('preconnect' => 0, 'dns-prefetch' => 1, 'preload' => 2, 'modulepreload' => 2);
		$rel   = strtolower(trim((string) $link['relation']));

		return isset($ranks[$rel]) ? $ranks[$rel] : 3;
	}

	/**
	 * A link's other attributes as HTML, escaped. Names that aren't valid attribute names, and values that are null, false
	 * or not scalar, are left out; true writes an empty value (e.g. crossorigin="").
	 *
	 * @param   array  $attribs  The attributes
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function linkAttributes(array $attribs)
	{
		$html = '';

		foreach ($attribs as $name => $value)
		{
			if (!preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:.-]*$/', (string) $name) || $value === null || $value === false || !is_scalar($value))
			{
				continue;
			}

			$html .= ' ' . $name . '="' . ($value === true ? '' : $this->escapeAttribute($value)) . '"';
		}

		return $html;
	}

	/**
	 * Escapes an attribute value. Entities already in it are kept (no double encoding), as many callers escape addresses
	 * before adding them (e.g. a URL with &amp; from JRoute).
	 *
	 * @param   string  $value  The value
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function escapeAttribute($value)
	{
		return htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8', false);
	}

	/**
	 * Sends the resource hints among the links as HTTP `Link:` headers (one value each; the application joins them), at most
	 * 2,000 bytes of Link headers in all, as servers and proxies refuse responses with large headers (nginx answers 502).
	 * Addresses with characters a header can't carry, and hints the response already has, are left out.
	 *
	 * @param   array  $links  The links, from sortLinks()
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	protected function sendLinkHeaders(array $links)
	{
		$app = \JFactory::getApplication();

		if (!method_exists($app, 'setHeader') || !method_exists($app, 'getHeaders'))
		{
			return;
		}

		// What this response already has (e.g. the head rendered twice)
		$sent   = array();
		$length = 0;

		foreach ((array) $app->getHeaders() as $header)
		{
			if (isset($header['name'], $header['value']) && strtolower($header['name']) === 'link')
			{
				$sent[]  = (string) $header['value'];
				$length += strlen((string) $header['value']) + 2;
			}
		}

		foreach ($links as $link)
		{
			if ($this->hintRank($link) === 3)
			{
				continue;
			}

			$href = htmlspecialchars_decode($link['href'], ENT_QUOTES);

			// A header carries ASCII only, and <, > and whitespace would end the address
			if ($href === '' || !preg_match('/^[\x21-\x7e]+$/', $href) || strpbrk($href, '<>') !== false)
			{
				continue;
			}

			$value = '<' . $href . '>; rel=' . strtolower(trim((string) $link['relation']));

			foreach (array('as', 'type', 'crossorigin', 'media', 'fetchpriority', 'imagesrcset', 'imagesizes') as $name)
			{
				if (!isset($link['attribs'][$name]) || $link['attribs'][$name] === false || !is_scalar($link['attribs'][$name]))
				{
					continue;
				}

				$param = $link['attribs'][$name] === true ? '' : (string) $link['attribs'][$name];

				if ($param === '' && $name === 'crossorigin')
				{
					$value .= '; crossorigin';
				}
				elseif (preg_match('/^[a-zA-Z0-9!#$%&\'*+.^_`|~-]+$/', $param))
				{
					$value .= '; ' . $name . '=' . $param;
				}
				elseif (preg_match('/^[\x20-\x7e]*$/', $param))
				{
					$value .= '; ' . $name . '="' . addcslashes($param, '"\\') . '"';
				}
			}

			if (in_array($value, $sent, true) || $length + strlen($value) + 2 > 2000)
			{
				continue;
			}

			$app->setHeader('Link', $value);
			$sent[] = $value;
			$length += strlen($value) + 2;
		}
	}
}
