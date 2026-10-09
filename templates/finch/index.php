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
$page = FinchHelper::prepare($this);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-scheme="<?php echo FinchHelper::scheme(); ?>">
<head>
	<jdoc:include type="head" />
</head>
<body id="top" class="<?php echo FinchHelper::e($page->bodyClass); ?>">
	<?php echo FinchHelper::sprite(); ?>
	<a class="skipLink" href="#content"><?php echo JText::_('TPL_FINCH_SKIP_TO_CONTENT'); ?></a>

	<header class="siteHeader">
		<div class="container headerBar">
			<?php echo FinchHelper::logo($page->siteName); ?>
			<nav class="mainNav" aria-label="<?php echo JText::_('TPL_FINCH_MAIN_NAVIGATION'); ?>">
				<jdoc:include type="modules" name="navigation" style="none" />
			</nav>
			<?php if ($page->hasSearch) : ?>
			<button type="button" class="iconButton searchToggle" aria-expanded="false" aria-controls="searchPanel" aria-label="<?php echo JText::_('TPL_FINCH_SEARCH'); ?>">
				<?php echo FinchHelper::icon('search') . FinchHelper::icon('close'); ?>
			</button>
			<?php endif; ?>
		</div>

		<?php // The search form opens over the page, under the header: it never moves the content (no layout shift) ?>
		<?php if ($page->hasSearch) : ?>
		<div class="searchPanel" id="searchPanel" hidden>
			<div class="container">
				<jdoc:include type="modules" name="search" style="none" />
			</div>
		</div>
		<?php endif; ?>
	</header>

	<main id="content" class="siteMain">
		<jdoc:include type="message" />

		<?php // The home page opens with the greeting (else the blog's name) set across the page, and the tagline ?>
		<?php if ($page->isHome) : ?>
		<div class="container masthead">
			<h1 class="mastheadTitle"><?php echo FinchHelper::e(trim((string) $page->params->get('greeting')) ?: $page->siteName); ?></h1>
			<?php if ($page->params->get('tagline')) : ?>
			<p class="mastheadTagline"><?php echo FinchHelper::e($page->params->get('tagline')); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ($this->countModules('above-content')) : ?>
		<div class="container aboveContent"><jdoc:include type="modules" name="above-content" style="finch" /></div>
		<?php endif; ?>

		<div class="container<?php echo $page->isPost || $page->isPage ? ' containerPost' : ''; ?>">
			<jdoc:include type="component" />
		</div>

		<?php // After a post (e.g. "Read next") ?>
		<?php if ($page->isPost && $this->countModules('below-content')) : ?>
		<div class="container belowContent"><jdoc:include type="modules" name="below-content" style="finch" /></div>
		<?php endif; ?>
	</main>

	<?php // The sidebar's modules (about, popular posts, topics): a band across the page under the lists ?>
	<?php if ($page->hasSidebar) : ?>
	<aside class="sidebar">
		<div class="container sidebarInner"><jdoc:include type="modules" name="sidebar" style="finch" /></div>
	</aside>
	<?php endif; ?>

	<footer class="siteFooter">
		<div class="container">
			<div class="footerTop">
				<div class="footerBrand">
					<?php echo FinchHelper::logo($page->siteName, 'logo logoFooter'); ?>
					<?php if ($page->params->get('tagline')) : ?>
					<p class="footerTagline"><?php echo FinchHelper::e($page->params->get('tagline')); ?></p>
					<?php endif; ?>
				</div>
				<div class="footerMenus"><jdoc:include type="modules" name="footer" style="finch" /></div>
				<?php echo FinchHelper::socialLinks(); ?>
			</div>
			<div class="footerBottom">
				<?php if ($page->params->get('footerText')) : ?>
				<p class="footerText"><?php echo FinchHelper::e($page->params->get('footerText')); ?></p>
				<?php endif; ?>
				<p class="copyright">&copy; <?php echo date('Y') . ' ' . FinchHelper::e($page->siteName); ?>. <?php echo JText::_('TPL_FINCH_ALL_RIGHTS_RESERVED'); ?></p>
				<a class="backToTop" href="#top"><?php echo FinchHelper::icon('arrow-up') . JText::_('TPL_FINCH_BACK_TO_TOP'); ?></a>
			</div>
		</div>
	</footer>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
