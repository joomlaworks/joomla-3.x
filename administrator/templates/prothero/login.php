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

$app  = JFactory::getApplication();
$lang = JFactory::getLanguage();

// Output as HTML5
$this->setHtml5(true);

// The site's home page: https when the whole site forces it, http when only the administrator does, otherwise as this request
$frontEndUri = JUri::getInstance(JUri::root());
$forceSsl    = (int) $app->get('force_ssl', 0);

if ($forceSsl === 2) {
    $frontEndUri->setScheme('https');
} elseif ($forceSsl === 1) {
    $frontEndUri->setScheme('http');
}

$siteUrl  = $frontEndUri->toString();
$siteHost = rtrim($frontEndUri->toString(array('host', 'port', 'path')), '/');
$sitename = (string) $app->get('sitename', '');

// Template parameters: a custom logo for the form
$loginLogoFile = (string) $this->params->get('loginLogoFile', '');

JHtml::_('stylesheet', 'login.css', array('version' => 'auto', 'relative' => true));
JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true), array('defer' => true));

// Language specific CSS and the site's own custom.css, as on every other page of the template
JHtml::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));
JHtml::_('stylesheet', 'custom.css', array('version' => 'auto', 'relative' => true));

$option = $app->input->getCmd('option', '');
$view   = $app->input->getCmd('view', '');

// No legacy markup: no scripts and stylesheets for old Internet Explorer versions
require_once __DIR__ . '/helper.php';

protheroDropConditionalAssets($this);

// The side panel's building blocks come in three colourways, shown one after another on each visit (a cookie remembers the last)
$cookie = $app->input->cookie;
$blocks = (int) $cookie->getInt('prothero_blocks', 0) % 3 + 1;
$cookie->set('prothero_blocks', $blocks, time() + 365 * 86400, JUri::base(true) . '/', '', JUri::getInstance()->isSsl(), true);

$escape = function ($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-scheme="<?php echo protheroScheme($this->params); ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="header" />
</head>
<body class="loginPage <?php echo $escape($option . ' view-' . $view); ?>">
	<div class="loginLayout">
		<aside class="loginBrand" data-blocks="<?php echo $blocks; ?>">
			<div class="loginBrand-inner">
				<p class="loginBrand-site"><?php echo $escape($sitename); ?></p>
				<p class="loginBrand-host"><?php echo $escape($siteHost); ?></p>
				<a class="loginBrand-visit" href="<?php echo $escape($siteUrl); ?>" tabindex="9">
					<span><?php echo JText::_('TPL_PROTHERO_LOGIN_VISIT_SITE'); ?></span>
					<svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</a>
			</div>
		</aside>
		<main class="loginMain">
			<div class="loginPanel">
				<div class="loginPanel-logo">
					<?php if ($loginLogoFile !== '') : ?>
						<img src="<?php echo $escape(JUri::root() . $loginLogoFile); ?>" alt="<?php echo $escape($sitename); ?>" />
					<?php else : ?>
						<?php echo protheroLogo(); ?>
					<?php endif; ?>
				</div>
				<h1 class="loginPanel-title"><?php echo JText::_('TPL_PROTHERO_LOGIN_HEADING'); ?></h1>
				<jdoc:include type="message" />
				<jdoc:include type="component" />
				<noscript>
					<p class="loginNotice"><?php echo JText::_('JGLOBAL_WARNJAVASCRIPT'); ?></p>
				</noscript>
			</div>
		</main>
	</div>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
