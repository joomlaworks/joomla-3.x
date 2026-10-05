<?php
/**
 * @package	Joomla.Installation
 *
 * @copyright  (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license	GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

/*
 * The installer brings its own styles and script (template/css, template/js) and writes its own head: nothing is loaded from
 * the rest of Joomla (Bootstrap, jQuery, Chosen, core scripts), including what form fields would add to the document.
 */
$version = md5_file(__DIR__ . '/css/template.css') . md5_file(__DIR__ . '/js/installation.js');
$version = substr(md5($version), 0, 12);
$app     = JFactory::getApplication();
$lang    = JFactory::getLanguage();

$strings = array();

foreach (array('ERROR', 'WARNING', 'NOTICE', 'MESSAGE', 'INSTL_PROCESS_BUSY', 'INSTL_FTP_SETTINGS_CORRECT',
	'JLIB_DATABASE_ERROR_DATABASE_CONNECT', 'JLIB_JS_AJAX_ERROR_CONNECTION_ABORT', 'JLIB_JS_AJAX_ERROR_NO_CONTENT',
	'JLIB_JS_AJAX_ERROR_OTHER', 'JLIB_JS_AJAX_ERROR_PARSE', 'JLIB_JS_AJAX_ERROR_TIMEOUT') as $key)
{
	$strings[$key] = JText::_($key);
}

$options = array(
	'url'       => JRoute::_('index.php', false),
	'view'      => $app->input->getWord('view'),
	'keepalive' => max(60, (int) JFactory::getSession()->getExpire() - 60),
	'strings'   => $strings,
);

$messages = array();

foreach ($app->getMessageQueue() as $message)
{
	if (isset($message['type'], $message['message']))
	{
		$messages[$message['type']][] = $message['message'];
	}
}

$alertTypes = array('message' => 'success', 'notice' => 'info', 'warning' => 'warning', 'error' => 'error');

// The logo inline, its wordmark in the text colour (white on the sidebar); the mark keeps Joomla's colours
$logo = (string) file_get_contents(__DIR__ . '/images/joomla-logo.svg');
$logo = str_replace(array('fill="#3b3a40"', '<svg '), array('fill="currentColor"', '<svg class="logo-svg" role="img" aria-label="Joomla!" focusable="false" '), $logo);

// The step's markup, indented to its place in the page source
$this->setBuffer(JHtml::_('InstallationHtml.helper.indent', (string) $this->getBuffer('component'), 4), 'component');

// Fix wrong display of Joomla!® in RTL language
$joomla  = '<a href="https://www.joomla.org" target="_blank" rel="noopener noreferrer">Joomla!</a><sup>' . ($lang->isRtl() ? '&#x200E;' : '') . '</sup>';
$license = '<a href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html" target="_blank" rel="noopener noreferrer">' . JText::_('INSTL_GNU_GPL_LICENSE') . '</a>';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<meta name="color-scheme" content="light dark" />
	<meta name="theme-color" content="#0f2a5c" />
	<title><?php echo htmlspecialchars($this->getTitle(), ENT_COMPAT, 'UTF-8'); ?></title>
	<link rel="icon" href="<?php echo $this->baseurl; ?>/favicon.ico" sizes="any" />
	<link rel="icon" href="<?php echo $this->baseurl; ?>/template/images/joomla-logo-icon.svg" type="image/svg+xml" />
	<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/template/css/template.css?<?php echo $version; ?>" />
	<script type="application/json" id="installation-options"><?php echo json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?></script>
	<script src="<?php echo $this->baseurl; ?>/template/js/installation.js?<?php echo $version; ?>" defer="defer"></script>
</head>
<body>
	<aside class="sidebar">
		<div class="brand">
			<span class="logo"><?php echo trim($logo); ?></span>
		</div>
	</aside>
	<div class="main">
		<main class="content">
			<div id="system-message-container" aria-live="polite"><?php foreach ($messages as $type => $list) : ?>

				<div class="alert alert-<?php echo isset($alertTypes[$type]) ? $alertTypes[$type] : 'info'; ?>" role="alert">
<?php foreach ($list as $text) : ?>
					<p><?php echo $text; ?></p>
<?php endforeach; ?>
				</div>
<?php endforeach; ?></div>
			<noscript>
				<div class="alert alert-error"><p><?php echo JText::_('INSTL_WARNJAVASCRIPT'); ?></p></div>
			</noscript>
			<div id="container-installation">
<jdoc:include type="component" />
			</div>
		</main>
		<footer class="footer">
			<p><?php echo JText::sprintf('JGLOBAL_ISFREESOFTWARE', $joomla, $license); ?></p>
		</footer>
	</div>
	<div class="loading-layer" id="loading-layer" hidden="hidden">
		<div class="spinner" role="status" aria-label="<?php echo htmlspecialchars(JText::_('INSTL_PROCESS_BUSY'), ENT_COMPAT, 'UTF-8'); ?>"></div>
	</div>
</body>
</html>
