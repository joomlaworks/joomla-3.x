<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */
$this->setHtml5(true);
$this->setGenerator('');
$this->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/template.css?t=' . date('Ymd_Hi', filemtime(__DIR__ . '/css/template.css')));
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body class="contentpane modal">
	<?php echo file_get_contents(__DIR__ . '/images/icons.svg'); ?>
	<div class="container">
		<jdoc:include type="message" />
		<jdoc:include type="component" />
	</div>
</body>
</html>
