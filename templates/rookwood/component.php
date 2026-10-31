<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('RookwoodHelper', false) || require_once __DIR__ . '/includes/helper.php';

/** @var JDocumentHtml $this */
$page = RookwoodHelper::prepare($this, 'component');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-theme="<?php echo $page->theme === 'light' ? 'light' : 'dark'; ?>">
<head>
	<jdoc:include type="header" />
	<?php // The visitor's choice (or the template's default) before anything is drawn, so the page never flashes the other theme; written here, not through the document, as it depends on nothing?>
	<script><?php echo RookwoodHelper::themeScript(); ?></script>
</head>
<body class="contentpane modal">
	<div class="container">
		<jdoc:include type="message" />
		<jdoc:include type="component" />
	</div>
	<jdoc:include type="footer" />
</body>
</html>
