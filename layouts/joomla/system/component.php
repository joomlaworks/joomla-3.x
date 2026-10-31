<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

// Output as HTML5
$this->setHtml5(true);

// Styles (system messages, forms; what the system template's general.css was)
JHtml::_('stylesheet', 'system/frontend.min.css', array('version' => 'auto', 'relative' => true));

?>
<!DOCTYPE html>
	<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<jdoc:include type="header" />
	</head>
	<body class="contentpane tmpl-component">
		<jdoc:include type="message" />
		<jdoc:include type="component" />
		<jdoc:include type="footer" />
	</body>
</html>
