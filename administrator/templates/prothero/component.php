<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.prothero
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

$page = require __DIR__ . '/includes/helper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-scheme="<?php echo $page->scheme; ?>">
<head>
	<style>@view-transition {navigation:auto;}</style>
	<jdoc:include type="header" />
</head>
<body class="contentpane component">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
	<jdoc:include type="footer" />
</body>
</html>
