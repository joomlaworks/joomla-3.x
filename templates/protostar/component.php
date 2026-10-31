<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

$page = require __DIR__ . '/includes/helper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<jdoc:include type="header" />
</head>
<body class="contentpane modal<?php echo $this->direction === 'rtl' ? ' rtl' : ''; ?>">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
	<jdoc:include type="footer" />
</body>
</html>
