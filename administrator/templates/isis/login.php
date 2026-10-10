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

// The site's home page: https when the whole site forces it, http when only the administrator does, otherwise as this request
$frontEndUri = JUri::getInstance(JUri::root());
$forceSsl    = (int) $app->get('force_ssl', 0);

if ($forceSsl === 2)
{
	$frontEndUri->setScheme('https');
}
elseif ($forceSsl === 1)
{
	$frontEndUri->setScheme('http');
}

$siteUrl  = $frontEndUri->toString();
$siteHost = rtrim($frontEndUri->toString(array('host', 'port', 'path')), '/');
$sitename = (string) $app->get('sitename', '');

// Template parameters: a custom logo for the form, and a colour for the side panel instead of its gradient
$loginLogoFile   = (string) $this->params->get('loginLogoFile', '');
$backgroundColor = (string) $this->params->get('loginBackgroundColor', '');

// The option's old default (#17568C, Joomla 3's login blue) is stored by every save of the style's options, so on most
// existing sites it isn't a choice: they get the gradient like new sites. Any other colour is used instead of it.
if (!preg_match('/^#[0-9a-f]{6}$/i', $backgroundColor) || strtolower($backgroundColor) === '#17568c')
{
	$backgroundColor = '';
}

$lightBackground = false;

if ($backgroundColor !== '')
{
	$yiq             = (hexdec(substr($backgroundColor, 1, 2)) * 299 + hexdec(substr($backgroundColor, 3, 2)) * 587 + hexdec(substr($backgroundColor, 5, 2)) * 114) / 1000;
	$lightBackground = $yiq >= 160;

	$this->addStyleDeclaration('.loginBrand { --brand-bg: ' . $backgroundColor . '; }');
}

JHtml::_('stylesheet', 'login.css', array('version' => 'auto', 'relative' => true));
JHtml::_('script', 'login.js', array('version' => 'auto', 'relative' => true), array('defer' => true));

// Language specific CSS and the site's own custom.css, as on every other page of the template
JHtml::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));
JHtml::_('stylesheet', 'custom.css', array('version' => 'auto', 'relative' => true));

$option = $app->input->getCmd('option', '');
$view   = $app->input->getCmd('view', '');

// No legacy markup: scripts and stylesheets for old Internet Explorer versions (conditional comments, e.g. the keepalive's
// event polyfill). Done when the head is built, so assets added after this file runs are covered too.
$document = $this;

JEventDispatcher::getInstance()->register('onBeforeCompileHead', function () use ($document)
{
	if (!$document instanceof JDocumentHtml)
	{
		return;
	}

	foreach (array('_scripts', '_styleSheets') as $list)
	{
		foreach ($document->$list as $url => $asset)
		{
			if (!empty($asset['options']['conditional']) || !empty($asset['conditional']))
			{
				unset($document->{$list}[$url]);
			}
		}
	}
});


// The Joomla! logo inline, its wordmark in the text colour (dark mode); the mark keeps Joomla's colours
$logo = (string) @file_get_contents(__DIR__ . '/images/joomla-logo.svg');
$logo = str_replace(array('fill="#3b3a40"', '<svg '), array('fill="currentColor"', '<svg role="img" aria-label="Joomla!" focusable="false" '), $logo);

$escape = function ($text)
{
	return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="color-scheme" content="light dark" />
	<jdoc:include type="head" />
</head>
<body class="loginPage <?php echo $escape($option . ' view-' . $view); ?>">
	<div class="loginLayout">
		<aside class="loginBrand<?php echo $backgroundColor !== '' ? ' loginBrand--custom' : ''; ?><?php echo $lightBackground ? ' loginBrand--light' : ''; ?>">
			<div class="loginBrand-inner">
				<p class="loginBrand-site"><?php echo $escape($sitename); ?></p>
				<p class="loginBrand-host"><?php echo $escape($siteHost); ?></p>
				<a class="loginBrand-visit" href="<?php echo $escape($siteUrl); ?>" tabindex="9">
					<span><?php echo JText::_('TPL_ISIS_LOGIN_VISIT_SITE'); ?></span>
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
						<?php echo trim($logo); ?>
					<?php endif; ?>
				</div>
				<h1 class="loginPanel-title"><?php echo JText::_('TPL_ISIS_LOGIN_HEADING'); ?></h1>
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
