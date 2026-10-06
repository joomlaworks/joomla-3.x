<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('HammondHelper', false) || require_once __DIR__ . '/helper.php';

/** @var JDocumentHtml $this */
$page = HammondHelper::prepare($this);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body id="top" class="<?php echo HammondHelper::e($page->bodyClass); ?>">
	<?php echo HammondHelper::sprite(); ?>
	<a class="skipLink" href="#content"><?php echo JText::_('TPL_HAMMOND_SKIP_TO_CONTENT'); ?></a>

	<header class="siteHeader">
		<div class="topBar">
			<div class="container">
				<?php if ($this->countModules('trending')) : ?>
				<div class="trending"><jdoc:include type="modules" name="trending" style="none" /></div>
				<?php endif; ?>
				<?php echo HammondHelper::socialLinks('socialLinks'); ?>
			</div>
		</div>

		<div class="masthead">
			<div class="container">
				<div class="mastheadToggles">
					<?php if ($page->hasMenu) : ?>
					<button type="button" class="iconButton menuToggle" aria-expanded="false" aria-controls="megaMenu" data-toggle="megaMenu">
						<?php echo HammondHelper::icon('menu-search') . HammondHelper::icon('close'); ?><span class="labelOpen"><?php echo JText::_('TPL_HAMMOND_MENU_SEARCH'); ?></span><span class="labelClose"><?php echo JText::_('TPL_HAMMOND_CLOSE'); ?></span>
					</button>
					<?php endif; ?>
				</div>
				<?php echo HammondHelper::logo('logo'); ?>
				<div class="mastheadAside">
					<time class="today" datetime="<?php echo JHtml::_('date', 'now', 'Y-m-d'); ?>">
						<strong><?php echo JHtml::_('date', 'now', 'l'); ?></strong> <?php echo JHtml::_('date', 'now', 'j F Y'); ?>
					</time>
					<?php if ($this->countModules('masthead')) : ?>
					<div class="mastheadModules"><jdoc:include type="modules" name="masthead" style="none" /></div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<nav class="mainNav" aria-label="<?php echo JText::_('TPL_HAMMOND_MAIN_NAVIGATION'); ?>">
			<div class="container">
				<?php echo HammondHelper::logo('logo logoSmall'); ?>
				<jdoc:include type="modules" name="navigation" style="none" />
				<?php if ($page->hasMenu) : ?>
				<button type="button" class="iconButton menuToggle stuckToggle" aria-expanded="false" aria-controls="megaMenu" data-toggle="megaMenu" aria-label="<?php echo JText::_('TPL_HAMMOND_MENU_SEARCH'); ?>">
					<?php echo HammondHelper::icon('menu-search') . HammondHelper::icon('close'); ?>
				</button>
				<?php endif; ?>
			</div>
		</nav>

		<?php // Menu and search share one panel over the page, opened from the navigation bar's line: it never moves the content (no layout shift) ?>
		<?php if ($page->hasMenu) : ?>
		<div class="megaMenu" id="megaMenu" hidden>
			<div class="container">
				<?php if ($this->countModules('search')) : ?>
				<div class="megaMenuSearch"><jdoc:include type="modules" name="search" style="none" /></div>
				<?php endif; ?>
				<div class="megaMenuSections">
					<jdoc:include type="modules" name="megamenu" style="hammond" />
				</div>
				<div class="megaMenuAside">
					<jdoc:include type="modules" name="megamenu-aside" style="hammond" />
					<?php echo HammondHelper::socialLinks('socialLinks socialLinksLarge'); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</header>

	<main id="content" class="siteMain">
		<jdoc:include type="message" />

		<?php if ($page->isHome) : ?>
		<div class="container">
			<div class="grid frontpage">
				<jdoc:include type="modules" name="frontpage" style="hammond" />
			</div>
		</div>
		<?php else : ?>
		<div class="container<?php echo $page->isPage ? ' containerNarrow' : ''; ?>">
			<?php if ($this->countModules('above-content')) : ?>
			<div class="aboveContent"><jdoc:include type="modules" name="above-content" style="hammond" /></div>
			<?php endif; ?>

			<div class="contentLayout<?php echo $page->hasSidebar ? ' withSidebar' : ''; ?>">
				<div class="contentMain">
					<jdoc:include type="component" />
				</div>
				<?php if ($page->hasSidebar) : ?>
				<aside class="contentSidebar">
					<div class="sidebarSticky"><jdoc:include type="modules" name="sidebar" style="hammond" /></div>
				</aside>
				<?php endif; ?>
			</div>

			<?php if (!$page->isPage && $this->countModules('below-content')) : ?>
			<div class="belowContent grid frontpage"><jdoc:include type="modules" name="below-content" style="hammond" /></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</main>

	<footer class="siteFooter">
		<div class="container">
			<div class="footerTop">
				<div class="footerBrand">
					<?php echo HammondHelper::logo('logo logoFooter'); ?>
					<?php if ($page->params->get('tagline')) : ?>
					<p class="footerTagline"><?php echo HammondHelper::e($page->params->get('tagline')); ?></p>
					<?php endif; ?>
					<?php echo HammondHelper::socialLinks('socialLinks'); ?>
				</div>
				<div class="footerMenus">
					<jdoc:include type="modules" name="footer" style="hammond" />
				</div>
			</div>
			<div class="footerBottom">
				<?php if ($page->params->get('footerText')) : ?>
				<p class="footerText"><?php echo HammondHelper::e($page->params->get('footerText')); ?></p>
				<?php endif; ?>
				<p class="copyright">&copy; <?php echo date('Y') . ' ' . HammondHelper::e($page->siteName); ?>. <?php echo JText::_('TPL_HAMMOND_ALL_RIGHTS_RESERVED'); ?></p>
				<a class="backToTop" href="#top"><?php echo HammondHelper::icon('arrow-up') . JText::_('TPL_HAMMOND_BACK_TO_TOP'); ?></a>
			</div>
		</div>
	</footer>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
