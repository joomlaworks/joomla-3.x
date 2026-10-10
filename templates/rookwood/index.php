<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('RookwoodHelper', false) || require_once __DIR__ . '/helper.php';

/** @var JDocumentHtml $this */
$page = RookwoodHelper::prepare($this);
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-theme="<?php echo $page->theme === 'light' ? 'light' : 'dark'; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body id="top" class="<?php echo RookwoodHelper::e($page->bodyClass); ?>">
	<?php echo RookwoodHelper::sprite(); ?>
	<a class="skipLink" href="#content"><?php echo JText::_('TPL_ROOKWOOD_SKIP_TO_CONTENT'); ?></a>

	<header class="siteHeader">
		<div class="container headerInner">
			<?php echo RookwoodHelper::logo(); ?>
			<nav class="mainNav" id="mainNav" aria-label="<?php echo JText::_('TPL_ROOKWOOD_MAIN_NAVIGATION'); ?>">
				<jdoc:include type="modules" name="navigation" style="none" />
				<?php if ($this->countModules('header-action')) : ?>
				<div class="navAction"><jdoc:include type="modules" name="header-action" style="none" /></div>
				<?php endif; ?>
			</nav>
			<div class="headerTools">
				<?php if ($page->hasSearch) : ?>
				<button type="button" class="iconButton searchToggle" aria-expanded="false" aria-controls="searchPanel" aria-label="<?php echo JText::_('TPL_ROOKWOOD_SEARCH'); ?>">
					<?php echo RookwoodHelper::icon('search') . RookwoodHelper::icon('close'); ?>
				</button>
				<?php endif; ?>
				<button type="button" class="iconButton themeToggle" data-label-dark="<?php echo JText::_('TPL_ROOKWOOD_THEME_DARK'); ?>" data-label-light="<?php echo JText::_('TPL_ROOKWOOD_THEME_LIGHT'); ?>" aria-label="<?php echo JText::_('TPL_ROOKWOOD_THEME_SWITCH'); ?>">
					<?php echo RookwoodHelper::icon('sun') . RookwoodHelper::icon('moon'); ?>
				</button>
				<?php // The header's button (a menu of one item, e.g. "Start a project": its Link CSS Style "btn btnPrimary"), also shown in the phone menu ?>
				<?php if ($this->countModules('header-action')) : ?>
				<div class="headerAction"><jdoc:include type="modules" name="header-action" style="none" /></div>
				<?php endif; ?>
				<button type="button" class="iconButton menuToggle" aria-expanded="false" aria-controls="mainNav" aria-label="<?php echo JText::_('TPL_ROOKWOOD_MENU'); ?>">
					<?php echo RookwoodHelper::icon('menu') . RookwoodHelper::icon('close'); ?>
				</button>
			</div>
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
		<div class="container messages"><jdoc:include type="message" /></div>

		<?php if ($page->isHome) : ?>
		<jdoc:include type="modules" name="frontpage" style="rookwood" />
		<?php else : ?>
		<jdoc:include type="modules" name="above-content" style="rookwood" />
		<?php // The template's own layouts draw their full-width headers themselves; other components get the page's width ?>
		<div class="<?php echo $page->ownLayout ? 'componentArea' : 'componentArea container stockLayout'; ?>">
			<jdoc:include type="component" />
		</div>
		<jdoc:include type="modules" name="below-content" style="rookwood" />
		<?php endif; ?>
	</main>

	<footer class="siteFooter">
		<svg class="footerWave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0 80V40C240 0 480 0 720 30s480 50 720 10v40Z" fill="currentColor"/></svg>
		<div class="container">
			<div class="footerTop">
				<div class="footerLead">
					<?php if ($headline = $page->params->get('footerHeadline')) : ?>
					<p class="footerHeadline"><?php echo RookwoodHelper::e($headline); ?></p>
					<?php endif; ?>
					<?php if ($email = trim((string) $page->params->get('footerEmail'))) : ?>
					<a class="footerEmail" href="mailto:<?php echo RookwoodHelper::e($email); ?>"><?php echo RookwoodHelper::e($email) . RookwoodHelper::icon('arrow-right'); ?></a>
					<?php endif; ?>
				</div>
				<div class="footerMenus">
					<jdoc:include type="modules" name="footer" style="rookwoodfooter" />
				</div>
			</div>
			<div class="footerBottom">
				<?php echo RookwoodHelper::logo('logo logoFooter'); ?>
				<?php echo RookwoodHelper::socialLinks(); ?>
				<p class="copyright">&copy; <?php echo date('Y') . ' ' . RookwoodHelper::e($page->siteName); ?>.<?php if ($text = $page->params->get('footerText')) : ?> <?php echo RookwoodHelper::footerText($text); ?><?php endif; ?></p>
				<a class="backToTop" href="#top"><?php echo RookwoodHelper::icon('arrow-up') . JText::_('TPL_ROOKWOOD_BACK_TO_TOP'); ?></a>
			</div>
		</div>
	</footer>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
