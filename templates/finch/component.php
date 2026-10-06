<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('FinchHelper', false) || require_once __DIR__ . '/helper.php';

/** @var JDocumentHtml $this */
FinchHelper::prepare($this, 'component');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body class="contentpane modal">
	<?php echo FinchHelper::sprite(); ?>
	<div class="container">
		<jdoc:include type="message" />
		<jdoc:include type="component" />
	</div>
</body>
</html>
