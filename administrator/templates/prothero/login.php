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
?><!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-scheme="<?php echo $page->scheme; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="header" />
</head>
<body class="loginPage <?php echo ProtheroHelper::escape($page->option . ' view-' . $page->view); ?>">
	<div class="loginLayout">
		<aside class="loginBrand" data-blocks="<?php echo $page->blocks; ?>">
			<div class="loginBrand-inner">
				<p class="loginBrand-site"><?php echo ProtheroHelper::escape($page->sitename); ?></p>
				<p class="loginBrand-host"><?php echo ProtheroHelper::escape($page->siteHost); ?></p>
				<a class="loginBrand-visit" href="<?php echo ProtheroHelper::escape($page->siteUrl); ?>" tabindex="9">
					<span><?php echo JText::_('TPL_PROTHERO_LOGIN_VISIT_SITE'); ?></span>
					<svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</a>
			</div>
		</aside>
		<main class="loginMain">
			<div class="loginPanel">
				<div class="loginPanel-logo">
					<?php if ($page->loginLogoFile !== '') : ?>
						<img src="<?php echo ProtheroHelper::escape(JUri::root() . $page->loginLogoFile); ?>" alt="<?php echo ProtheroHelper::escape($page->sitename); ?>" />
					<?php else : ?>
						<?php echo ProtheroHelper::logo(); ?>
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
	<jdoc:include type="footer" />
</body>
</html>
