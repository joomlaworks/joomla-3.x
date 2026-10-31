<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentError $this */

$page = require __DIR__ . '/includes/helper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta charset="utf-8" />
	<title><?php echo $this->title; ?> <?php echo htmlspecialchars((string) $this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template.css" rel="stylesheet" />
	<style>:root {--protostar-font:<?php echo $page->font; ?>;}</style>
	<?php if ($page->app->get('debug_lang', '0') == '1' || $page->app->get('debug', '0') == '1') : ?>
	<link href="<?php echo JUri::root(true); ?>/media/system/css/debug.css" rel="stylesheet" />
	<?php endif; ?>
	<?php // If Right-to-Left?>
	<?php if ($this->direction === 'rtl') : ?>
	<link href="<?php echo JUri::root(true); ?>/media/system/css/bootstrap-rtl.css" rel="stylesheet" />
	<?php endif; ?>
	<?php if (file_exists('templates/' . $this->template . '/css/user.css')) : ?>
	<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/user.css" rel="stylesheet" />
	<?php endif; ?>
	<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
	<?php // The template options' colours, as on other pages?>
	<?php if ($page->optionColors['properties'] !== '') : ?>
	<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template-options.css" rel="stylesheet" />
	<style>:root {<?php echo $page->optionColors['properties']; ?>}</style>
	<?php endif; ?>
</head>
<body class="<?php echo $page->bodyClass; ?>">
	<!-- Body -->
	<div class="body" id="top">
		<div class="container<?php echo($page->params->get('fluidContainer') ? '-fluid' : ''); ?>">
			<!-- Header -->
			<header class="header" role="banner">
				<div class="header-inner clearfix">
					<a class="brand pull-left" href="<?php echo $this->baseurl; ?>/">
						<?php echo $page->logo; ?>
					</a>
					<?php if ($page->format === 'html') : ?>
					<div class="header-search pull-right">
						<?php // Display position-0 modules?>
						<?php echo $this->loadRenderer('modules')->render('position-0', array('style' => 'none')); ?>
					</div>
					<?php endif; ?>
				</div>
			</header>
			<?php if ($page->format === 'html') : ?>
			<nav class="navigation" role="navigation">
				<?php // Display position-1 modules?>
				<?php echo $this->loadRenderer('modules')->render('position-1', array('style' => 'none')); ?>
			</nav>
			<?php endif; ?>
			<!-- Banner -->
			<?php if ($page->format === 'html') : ?>
			<div class="banner">
				<?php echo $this->loadRenderer('modules')->render('banner', array('style' => 'xhtml')); ?>
			</div>
			<?php endif; ?>
			<div class="row-fluid">
				<main id="content" role="main" class="span12">
					<!-- Begin Content -->
					<h1 class="page-header"><?php echo JText::_('JERROR_LAYOUT_PAGE_NOT_FOUND'); ?></h1>
					<div class="well">
						<div class="row-fluid">
							<div class="span6">
								<p><strong><?php echo JText::_('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST'); ?></strong></p>
								<p><?php echo JText::_('JERROR_LAYOUT_NOT_ABLE_TO_VISIT'); ?></p>
								<ul>
									<li><?php echo JText::_('JERROR_LAYOUT_AN_OUT_OF_DATE_BOOKMARK_FAVOURITE'); ?></li>
									<li><?php echo JText::_('JERROR_LAYOUT_MIS_TYPED_ADDRESS'); ?></li>
									<li><?php echo JText::_('JERROR_LAYOUT_SEARCH_ENGINE_OUT_OF_DATE_LISTING'); ?></li>
									<li><?php echo JText::_('JERROR_LAYOUT_YOU_HAVE_NO_ACCESS_TO_THIS_PAGE'); ?></li>
								</ul>
							</div>
							<div class="span6">
								<?php if ($page->format === 'html' && JModuleHelper::getModule('mod_search')) : ?>
								<p><strong><?php echo JText::_('JERROR_LAYOUT_SEARCH'); ?></strong></p>
								<p><?php echo JText::_('JERROR_LAYOUT_SEARCH_PAGE'); ?></p>
								<?php $module = JModuleHelper::getModule('mod_search'); ?>
								<?php echo JModuleHelper::renderModule($module); ?>
								<?php endif; ?>
								<p><?php echo JText::_('JERROR_LAYOUT_GO_TO_THE_HOME_PAGE'); ?></p>
								<p><a href="<?php echo $this->baseurl; ?>/index.php" class="btn"><span class="icon-home" aria-hidden="true"></span> <?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
							</div>
						</div>
						<hr />
						<p><?php echo JText::_('JERROR_LAYOUT_PLEASE_CONTACT_THE_SYSTEM_ADMINISTRATOR'); ?></p>
						<blockquote>
							<span class="label label-inverse"><?php echo $this->error->getCode(); ?></span> <?php echo htmlspecialchars((string) $this->error->getMessage(), ENT_QUOTES, 'UTF-8');?>
							<?php if ($this->debug) : ?>
							<br/><?php echo htmlspecialchars((string) $this->error->getFile(), ENT_QUOTES, 'UTF-8');?>:<?php echo $this->error->getLine(); ?>
							<?php endif; ?>
						</blockquote>
						<?php if ($this->debug) : ?>
						<div>
							<?php echo $this->renderBacktrace(); ?>
							<?php // Check if there are more Exceptions and render their data as well?>
							<?php if ($this->error->getPrevious()) : ?>
							<?php $loop = true; ?>
							<?php // Reference $this->_error here and in the loop as setError() assigns errors to this property and we need this for the backtrace to work correctly?>
							<?php // Make the first assignment to setError() outside the loop so the loop does not skip Exceptions?>
							<?php $this->setError($this->_error->getPrevious()); ?>
							<?php while ($loop === true) : ?>
							<p><strong><?php echo JText::_('JERROR_LAYOUT_PREVIOUS_ERROR'); ?></strong></p>
							<p>
								<?php echo htmlspecialchars((string) $this->_error->getMessage(), ENT_QUOTES, 'UTF-8'); ?>
								<br/><?php echo htmlspecialchars((string) $this->_error->getFile(), ENT_QUOTES, 'UTF-8');?>:<?php echo $this->_error->getLine(); ?>
							</p>
							<?php echo $this->renderBacktrace(); ?>
							<?php $loop = $this->setError($this->_error->getPrevious()); ?>
							<?php endwhile; ?>
							<?php // Reset the main error object to the base error?>
							<?php $this->setError($this->error); ?>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>
					<!-- End Content -->
				</main>
			</div>
		</div>
	</div>
	<!-- Footer -->
	<footer class="footer" role="contentinfo">
		<div class="container<?php echo($page->params->get('fluidContainer') ? '-fluid' : ''); ?>">
			<hr />
			<?php if ($page->format === 'html') : ?>
			<?php echo $this->loadRenderer('modules')->render('footer', array('style' => 'none')); ?>
			<?php endif; ?>
			<p class="pull-right">
				<a href="#top" id="back-top">
					<?php echo JText::_('TPL_PROTOSTAR_BACKTOTOP'); ?>
				</a>
			</p>
			<p>
				&copy; <?php echo date('Y'); ?> <?php echo $page->sitename; ?>
			</p>
		</div>
	</footer>
	<?php if ($page->format === 'html') : ?>
	<?php echo $this->loadRenderer('modules')->render('debug', array('style' => 'none')); ?>
	<?php endif; ?>
</body>
</html>
