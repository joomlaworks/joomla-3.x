<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.isis
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

$app  = JFactory::getApplication();
$lang = JFactory::getLanguage();

// Output as HTML5
$this->setHtml5(true);

// No legacy markup: no scripts and stylesheets for old Internet Explorer versions
require_once __DIR__ . '/helper.php';

isisDropConditionalAssets($this);

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');

// Add template js
JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true));

// Add Stylesheets
JHtml::_('stylesheet', 'template.css', array('version' => 'auto', 'relative' => true));

if ($this->direction === 'rtl') {
    JHtml::_('stylesheet', 'template-rtl.css', array('version' => 'auto', 'relative' => true));
}

// Load optional RTL Bootstrap CSS
JHtml::_('bootstrap.loadCss', false, $this->direction);

// Load specific language related CSS
JHtml::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));

// Load custom.css
JHtml::_('stylesheet', 'custom.css', array('version' => 'auto', 'relative' => true));

// The Link Colour option, as a custom property which css/template-options.css applies after custom.css
require_once __DIR__ . '/helper.php';

$optionColors = isisOptionColors(array('link' => $this->params->get('linkColor')));

if ($optionColors['properties'] !== '') {
    JHtml::_('stylesheet', 'template-options.css', array('version' => 'auto', 'relative' => true));
    $this->addStyleDeclaration(':root {' . $optionColors['properties'] . '}');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body class="contentpane component<?php echo $optionColors['classes']; ?>">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
</body>
</html>
