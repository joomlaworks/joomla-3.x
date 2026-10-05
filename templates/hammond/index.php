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

require_once __DIR__ . '/helper.php';

$app      = JFactory::getApplication();
$input    = $app->input;
$params   = $this->params;
$menu     = $app->getMenu();
$active   = $menu->getActive();
$option   = $input->getCmd('option', '');
$view     = $input->getCmd('view', '');
// The home page is made of the frontpage module grid, whatever its menu item shows (without modules there, e.g. on a new
// site without sample data, it shows the component). Joomla also makes the home item active on pages without a menu item
// of their own (tags, search), so the request must be the home item's own link.
$homeLink = $active ? $active->query : array();
$isHome   = $active && $active->home && $this->countModules('frontpage')
	&& (isset($homeLink['option']) ? $homeLink['option'] : '') === $option && (isset($homeLink['view']) ? $homeLink['view'] : '') === $view
	&& (!isset($homeLink['id']) || (int) $homeLink['id'] === $input->getInt('id'));
$isPage   = HammondHelper::isPage();
$isList   = in_array($option . '.' . $view, array('com_content.category', 'com_content.archive', 'com_content.featured', 'com_tags.tag', 'com_search.search'), true) && !$isHome;
$hasSide  = !$isHome && !$isPage && $this->countModules('sidebar');
$siteName = $params->get('siteName') ?: $app->get('sitename');
$tpl      = $this->baseurl . '/templates/' . $this->template;
$hasMenu  = $this->countModules('search') || $this->countModules('megamenu') || $this->countModules('megamenu-aside');

$this->setHtml5(true);
$this->setGenerator('');
$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$this->setMetaData('theme-color', '#ffffff');
$this->addStyleSheet($tpl . '/css/template.css?t=' . date('Ymd_Hi', filemtime(__DIR__ . '/css/template.css')));
$this->addScript($tpl . '/js/template.js?t=' . date('Ymd_Hi', filemtime(__DIR__ . '/js/template.js')), array(), array('defer' => true));
$this->addHeadLink($tpl . '/images/favicon.svg', 'icon', 'rel', array('type' => 'image/svg+xml'));

$bodyClass = array(
	'site', $isHome ? 'isFrontpage' : 'isInner', $isPage ? 'isPage' : '', $isList ? 'isList' : '', $hasSide ? 'hasSidebar' : '',
	'option-' . str_replace('com_', '', $option), 'view-' . $view,
	$active ? 'itemid-' . (int) $active->id : '', $active ? trim((string) $active->getParams()->get('pageclass_sfx')) : '',
);

$social = array();

foreach (array('facebook' => 'Facebook', 'x' => 'X', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'rss' => 'RSS') as $network => $label)
{
	$url = trim((string) $params->get('social_' . $network, ''));

	// The site's feed unless another one is set
	if ($network === 'rss' && $url === '')
	{
		$url = JUri::base(true) . '/index.php?format=feed&type=rss';
	}

	if ($url !== '')
	{
		$social[$network] = array('url' => $url, 'label' => $label);
	}
}

$socialLinks = function ($class) use ($social)
{
	$html = '<ul class="' . $class . '">';

	foreach ($social as $network => $link)
	{
		$html .= '<li><a href="' . HammondHelper::e($link['url']) . '" aria-label="' . HammondHelper::e($link['label']) . '"' . ($network !== 'rss' ? ' rel="noopener" target="_blank"' : '') . '>'
			. HammondHelper::icon($network) . '</a></li>';
	}

	return $html . '</ul>';
};

$logo = function ($class) use ($siteName)
{
	return '<a class="' . $class . '" href="' . JUri::base(true) . '/" aria-label="' . HammondHelper::e($siteName) . '">'
		. '<svg class="logoMark" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="9" fill="currentColor"/>'
		. '<path d="M12 10.5v19M28 10.5v19M12 20h16" fill="none" stroke="#fff" stroke-width="4.6" stroke-linecap="round"/>'
		. '<circle cx="31.5" cy="8.5" r="2.6" fill="var(--c-accent)"/></svg>'
		. '<span class="logoText"><strong>' . HammondHelper::e(strtok($siteName, ' ')) . '</strong>'
		. (strpos($siteName, ' ') !== false ? ' <span>' . HammondHelper::e(substr($siteName, strpos($siteName, ' ') + 1)) . '</span>' : '') . '</span></a>';
};

?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body id="top" class="<?php echo HammondHelper::e(implode(' ', array_filter($bodyClass))); ?>">
	<?php echo file_get_contents(__DIR__ . '/images/icons.svg'); ?>
	<a class="skipLink" href="#content"><?php echo JText::_('TPL_HAMMOND_SKIP_TO_CONTENT'); ?></a>

	<header class="siteHeader">
		<div class="topBar">
			<div class="container">
				<?php if ($this->countModules('trending')) : ?>
				<div class="trending"><jdoc:include type="modules" name="trending" style="none" /></div>
				<?php endif; ?>
				<?php echo $socialLinks('socialLinks'); ?>
			</div>
		</div>

		<div class="masthead">
			<div class="container">
				<div class="mastheadToggles">
					<?php if ($hasMenu) : ?>
					<button type="button" class="iconButton menuToggle" aria-expanded="false" aria-controls="megaMenu" data-toggle="megaMenu">
						<?php echo HammondHelper::icon('menu-search') . HammondHelper::icon('close'); ?><span class="labelOpen"><?php echo JText::_('TPL_HAMMOND_MENU_SEARCH'); ?></span><span class="labelClose"><?php echo JText::_('TPL_HAMMOND_CLOSE'); ?></span>
					</button>
					<?php endif; ?>
				</div>
				<?php echo $logo('logo'); ?>
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
				<?php echo $logo('logo logoSmall'); ?>
				<jdoc:include type="modules" name="navigation" style="none" />
				<?php if ($hasMenu) : ?>
				<button type="button" class="iconButton menuToggle stuckToggle" aria-expanded="false" aria-controls="megaMenu" data-toggle="megaMenu" aria-label="<?php echo JText::_('TPL_HAMMOND_MENU_SEARCH'); ?>">
					<?php echo HammondHelper::icon('menu-search') . HammondHelper::icon('close'); ?>
				</button>
				<?php endif; ?>
			</div>
		</nav>

		<?php // Menu and search share one panel over the page, opened from the navigation bar's line: it never moves the content (no layout shift) ?>
		<?php if ($hasMenu) : ?>
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
					<?php echo $socialLinks('socialLinks socialLinksLarge'); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</header>

	<main id="content" class="siteMain">
		<jdoc:include type="message" />

		<?php if ($isHome) : ?>
		<div class="container">
			<div class="grid frontpage">
				<jdoc:include type="modules" name="frontpage" style="hammond" />
			</div>
		</div>
		<?php else : ?>
		<div class="container<?php echo $isPage ? ' containerNarrow' : ''; ?>">
			<?php if ($this->countModules('above-content')) : ?>
			<div class="aboveContent"><jdoc:include type="modules" name="above-content" style="hammond" /></div>
			<?php endif; ?>

			<div class="contentLayout<?php echo $hasSide ? ' withSidebar' : ''; ?>">
				<div class="contentMain">
					<jdoc:include type="component" />
				</div>
				<?php if ($hasSide) : ?>
				<aside class="contentSidebar">
					<div class="sidebarSticky"><jdoc:include type="modules" name="sidebar" style="hammond" /></div>
				</aside>
				<?php endif; ?>
			</div>

			<?php if (!$isPage && $this->countModules('below-content')) : ?>
			<div class="belowContent grid frontpage"><jdoc:include type="modules" name="below-content" style="hammond" /></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</main>

	<footer class="siteFooter">
		<div class="container">
			<div class="footerTop">
				<div class="footerBrand">
					<?php echo $logo('logo logoFooter'); ?>
					<?php if ($params->get('tagline')) : ?>
					<p class="footerTagline"><?php echo HammondHelper::e($params->get('tagline')); ?></p>
					<?php endif; ?>
					<?php echo $socialLinks('socialLinks'); ?>
				</div>
				<div class="footerMenus">
					<jdoc:include type="modules" name="footer" style="hammond" />
				</div>
			</div>
			<div class="footerBottom">
				<?php if ($params->get('footerText')) : ?>
				<p class="footerText"><?php echo HammondHelper::e($params->get('footerText')); ?></p>
				<?php endif; ?>
				<p class="copyright">&copy; <?php echo date('Y') . ' ' . HammondHelper::e($siteName); ?>. <?php echo JText::_('TPL_HAMMOND_ALL_RIGHTS_RESERVED'); ?></p>
				<a class="backToTop" href="#top"><?php echo HammondHelper::icon('arrow-up') . JText::_('TPL_HAMMOND_BACK_TO_TOP'); ?></a>
			</div>
		</div>
	</footer>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
