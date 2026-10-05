<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
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
$active   = $app->getMenu()->getActive();
$option   = $input->getCmd('option', '');
$view     = $input->getCmd('view', '');
// Joomla makes the home item active on pages without a menu item of their own (tags, search): the home page is its own link
$homeLink = $active ? $active->query : array();
$isHome   = $active && $active->home && (isset($homeLink['option']) ? $homeLink['option'] : '') === $option
	&& (isset($homeLink['view']) ? $homeLink['view'] : '') === $view && (!isset($homeLink['id']) || (int) $homeLink['id'] === $input->getInt('id'));
$isPage   = FinchHelper::isPage();
$isPost   = $option === 'com_content' && $view === 'article' && !$isPage;
$isList   = in_array($option . '.' . $view, array('com_content.featured', 'com_content.category', 'com_content.archive', 'com_tags.tag', 'com_search.search'), true);
$hasSide  = $isList && $this->countModules('sidebar');
$hasFind  = $this->countModules('search');
$siteName = $params->get('siteName') ?: $app->get('sitename');
$tpl      = $this->baseurl . '/templates/' . $this->template;

$this->setHtml5(true);
$this->setGenerator('');
$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$this->setMetaData('theme-color', '#fbf8f3');
$this->addStyleSheet($tpl . '/css/template.css?t=' . date('Ymd_Hi', filemtime(__DIR__ . '/css/template.css')));
$this->addScript($tpl . '/js/template.js?t=' . date('Ymd_Hi', filemtime(__DIR__ . '/js/template.js')), array(), array('defer' => true));
$this->addHeadLink($tpl . '/images/favicon.svg', 'icon', 'rel', array('type' => 'image/svg+xml'));

$bodyClass = array(
	'site', $isHome ? 'isHome' : '', $isPost ? 'isPost' : '', $isPage ? 'isPage' : '', $isList ? 'isList' : '', $hasSide ? 'hasSidebar' : '',
	'option-' . str_replace('com_', '', $option), 'view-' . $view,
	$active ? 'itemid-' . (int) $active->id : '', $active ? trim((string) $active->getParams()->get('pageclass_sfx')) : '',
);

$social = array();

foreach (array('x' => 'X', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'rss' => 'RSS') as $network => $label)
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

?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body id="top" class="<?php echo FinchHelper::e(implode(' ', array_filter($bodyClass))); ?>">
	<?php echo file_get_contents(__DIR__ . '/images/icons.svg'); ?>
	<a class="skipLink" href="#content"><?php echo JText::_('TPL_FINCH_SKIP_TO_CONTENT'); ?></a>

	<header class="siteHeader">
		<div class="container masthead">
			<?php echo FinchHelper::logo($siteName); ?>
			<?php if ($params->get('tagline')) : ?>
			<p class="tagline"><?php echo FinchHelper::e($params->get('tagline')); ?></p>
			<?php endif; ?>
		</div>

		<nav class="mainNav" aria-label="<?php echo JText::_('TPL_FINCH_MAIN_NAVIGATION'); ?>">
			<div class="container">
				<jdoc:include type="modules" name="navigation" style="none" />
				<?php if ($hasFind) : ?>
				<button type="button" class="iconButton searchToggle" aria-expanded="false" aria-controls="searchPanel" aria-label="<?php echo JText::_('TPL_FINCH_SEARCH'); ?>">
					<?php echo FinchHelper::icon('search') . FinchHelper::icon('close'); ?>
				</button>
				<?php endif; ?>
			</div>
		</nav>

		<?php // The search form opens over the page, under the navigation: it never moves the content (no layout shift) ?>
		<?php if ($hasFind) : ?>
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

		<?php if ($hasSide) : ?>
		<div class="container layoutSidebar">
			<div class="layoutMain"><jdoc:include type="component" /></div>
			<aside class="sidebar"><jdoc:include type="modules" name="sidebar" style="finch" /></aside>
		</div>
		<?php else : ?>
		<div class="container<?php echo $isPost || $isPage ? ' containerPost' : ''; ?>">
			<jdoc:include type="component" />
		</div>
		<?php endif; ?>

		<?php // After a post (e.g. "Read next") ?>
		<?php if ($isPost && $this->countModules('below-content')) : ?>
		<div class="container belowContent"><jdoc:include type="modules" name="below-content" style="finch" /></div>
		<?php endif; ?>
	</main>

	<footer class="siteFooter">
		<div class="container">
			<div class="footerTop">
				<div class="footerBrand">
					<?php echo FinchHelper::logo($siteName, 'logo logoFooter'); ?>
					<?php if ($params->get('tagline')) : ?>
					<p class="footerTagline"><?php echo FinchHelper::e($params->get('tagline')); ?></p>
					<?php endif; ?>
				</div>
				<div class="footerMenus"><jdoc:include type="modules" name="footer" style="finch" /></div>
				<?php if ($social) : ?>
				<ul class="socialLinks">
					<?php foreach ($social as $network => $link) : ?>
					<li><a href="<?php echo FinchHelper::e($link['url']); ?>" aria-label="<?php echo FinchHelper::e($link['label']); ?>"<?php echo $network !== 'rss' ? ' rel="noopener" target="_blank"' : ''; ?>><?php echo FinchHelper::icon($network); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
			<div class="footerBottom">
				<?php if ($params->get('footerText')) : ?>
				<p class="footerText"><?php echo FinchHelper::e($params->get('footerText')); ?></p>
				<?php endif; ?>
				<p class="copyright">&copy; <?php echo date('Y') . ' ' . FinchHelper::e($siteName); ?>. <?php echo JText::_('TPL_FINCH_ALL_RIGHTS_RESERVED'); ?></p>
				<a class="backToTop" href="#top"><?php echo FinchHelper::icon('arrow-up') . JText::_('TPL_FINCH_BACK_TO_TOP'); ?></a>
			</div>
		</div>
	</footer>
	<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
