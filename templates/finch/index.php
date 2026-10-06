<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

/** @var JDocumentHtml $this */
$page = FinchHelper::prepare($this);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body id="top" class="<?php echo FinchHelper::e($page->bodyClass); ?>">
	<?php echo FinchHelper::sprite(); ?>
	<a class="skipLink" href="#content"><?php echo JText::_('TPL_FINCH_SKIP_TO_CONTENT'); ?></a>

	<header class="siteHeader">
		<div class="container masthead">
			<?php echo FinchHelper::logo($page->siteName); ?>
			<?php if ($page->params->get('tagline')) : ?>
			<p class="tagline"><?php echo FinchHelper::e($page->params->get('tagline')); ?></p>
			<?php endif; ?>
		</div>

		<nav class="mainNav" aria-label="<?php echo JText::_('TPL_FINCH_MAIN_NAVIGATION'); ?>">
			<div class="container">
				<jdoc:include type="modules" name="navigation" style="none" />
				<?php if ($page->hasSearch) : ?>
				<button type="button" class="iconButton searchToggle" aria-expanded="false" aria-controls="searchPanel" aria-label="<?php echo JText::_('TPL_FINCH_SEARCH'); ?>">
					<?php echo FinchHelper::icon('search') . FinchHelper::icon('close'); ?>
				</button>
				<?php endif; ?>
			</div>
		</nav>

		<?php // The search form opens over the page, under the navigation: it never moves the content (no layout shift) ?>
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

		<?php if ($this->countModules('above-content')) : ?>
		<div class="container aboveContent"><jdoc:include type="modules" name="above-content" style="finch" /></div>
		<?php endif; ?>

		<?php if ($page->hasSidebar) : ?>
		<div class="container layoutSidebar">
			<div class="layoutMain"><jdoc:include type="component" /></div>
			<aside class="sidebar"><jdoc:include type="modules" name="sidebar" style="finch" /></aside>
		</div>
		<?php else : ?>
		<div class="container<?php echo $page->isPost || $page->isPage ? ' containerPost' : ''; ?>">
			<jdoc:include type="component" />
		</div>
		<?php endif; ?>

		<?php // After a post (e.g. "Read next") ?>
		<?php if ($page->isPost && $this->countModules('below-content')) : ?>
		<div class="container belowContent"><jdoc:include type="modules" name="below-content" style="finch" /></div>
		<?php endif; ?>
	</main>

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
