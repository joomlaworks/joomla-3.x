<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.prothero
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentError $this */

$page = require __DIR__ . '/includes/helper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-scheme="<?php echo $page->scheme; ?>" data-navbar="<?php echo $page->navbar; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo $this->title; ?> <?php echo htmlspecialchars((string) $this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></title>
	<?php if ($page->app->get('debug_lang', '0') == '1' || $page->app->get('debug', '0') == '1') : ?>
	<!-- Load additional CSS styles for debug mode-->
	<link href="<?php echo JUri::root(true); ?>/media/system/css/debug.css" rel="stylesheet" />
	<?php endif; ?>
	<?php // If Right-to-Left?>
	<?php if ($this->direction == 'rtl') : ?>
	<link href="<?php echo JUri::root(true); ?>/media/system/css/bootstrap-rtl.css" rel="stylesheet" />
	<?php endif; ?>
	<?php // Load specific language related CSS?>
	<?php $file = '/administrator/language/' . $page->lang->getTag() . '/' . $page->lang->getTag() . '.css'; ?>
	<?php if (is_file(JPATH_ROOT . $file)) : ?>
	<link href="<?php echo JUri::root(true) . $file; ?>" rel="stylesheet" />
	<?php endif; ?>
	<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template.css" rel="stylesheet" />
	<?php if ($this->direction === 'rtl') : ?>
	<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template-rtl.css" rel="stylesheet" />
	<?php endif; ?>
	<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
	<?php // The CMS's script (its dropdowns and collapse for the menu; no jQuery or Bootstrap's script)?>
	<script src="<?php echo JUri::root(true); ?>/media/system/js/global.min.js"></script>
	<script src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/template.js"></script>
</head>
<body class="admin <?php echo $page->option . ' view-' . $page->view . ' layout-' . $page->layout . ' task-' . $page->task; ?>">
	<!-- Top Navigation -->
	<nav class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container-fluid">
				<?php if ($page->params->get('admin_menus') != '0') : ?>
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="element-invisible"><?php echo JTEXT::_('TPL_PROTHERO_TOGGLE_MENU'); ?></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
				<?php endif; ?>
				<a class="admin-logo" href="<?php echo $this->baseurl; ?>"><span class="icon-joomla"></span></a>

				<a class="brand hidden-desktop hidden-tablet" href="<?php echo $page->mainPageUri; ?>" title="<?php echo JText::sprintf('TPL_PROTHERO_PREVIEW', $page->sitename); ?>" target="_blank"><?php echo JHtml::_('string.truncate', $page->sitename, 14, false, false); ?>
					<span class="icon-out-2 small"></span></a>

				<?php if ($page->params->get('admin_menus') != '0') : ?>
				<div class="nav-collapse">
				<?php else : ?>
				<div>
				<?php endif; ?>
					<?php // Display menu modules?>
					<?php $menumodules = JModuleHelper::getModules('menu'); ?>
					<?php foreach ($menumodules as $menumodule) : ?>
					<?php $output = JModuleHelper::renderModule($menumodule, array('style' => 'none')); ?>
					<?php echo $output; ?>
					<?php endforeach; ?>
					<ul class="nav nav-user<?php echo ($this->direction == 'rtl') ? ' pull-left' : ' pull-right'; ?>">
						<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="icon-cog"></span>
								<span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li>
									<span>
										<span class="icon-user"></span>
										<strong><?php echo htmlspecialchars((string) $page->user->name, ENT_QUOTES, 'UTF-8'); ?></strong>
									</span>
								</li>
								<li class="divider"></li>
								<li class="">
									<a href="index.php?option=com_admin&amp;task=profile.edit&amp;id=<?php echo $page->user->id; ?>"><?php echo JText::_('TPL_PROTHERO_EDIT_ACCOUNT'); ?></a>
								</li>
								<li class="divider"></li>
								<li class="">
									<a href="<?php echo JRoute::_('index.php?option=com_login&task=logout&' . JSession::getFormToken() . '=1'); ?>"><?php echo JText::_('TPL_PROTHERO_LOGOUT'); ?></a>
								</li>
							</ul>
						</li>
					</ul>
					<a class="brand visible-desktop visible-tablet" href="<?php echo $page->mainPageUri; ?>" title="<?php echo JText::sprintf('TPL_PROTHERO_PREVIEW', $page->sitename); ?>" target="_blank"><?php echo JHtml::_('string.truncate', $page->sitename, 14, false, false); ?>
						<span class="icon-out-2 small"></span></a>
				</div>
				<!--/.nav-collapse -->
			</div>
		</div>
	</nav>
	<!-- Header -->
	<header class="header">
		<?php if ($page->displayHeader) : ?>
		<div class="container-logo">
			<?php if ($page->logo !== '') : ?>
			<img src="<?php echo $page->logo; ?>" class="logo" alt="<?php echo $page->sitename; ?>" />
			<?php else : ?>
			<span class="logo"><?php echo ProtheroHelper::logo(); ?></span>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<div class="container-title">
			<h1 class="page-title"><?php echo JText::_('ERROR'); ?></h1>
		</div>
	</header>
	<div class="subhead-spacer" style="margin-bottom: 20px"></div>
	<!-- container-fluid -->
	<div class="container-fluid container-main">
		<section id="content">
			<!-- Begin Content -->
			<div class="row-fluid">
				<div class="span12">
					<!-- Begin Content -->
					<h1 class="page-header"><?php echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED'); ?></h1>
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
					<p><a href="<?php echo $this->baseurl; ?>" class="btn"><span class="icon-dashboard"></span> <?php echo JText::_('JGLOBAL_TPL_CPANEL_LINK_TEXT'); ?></a></p>
					<!-- End Content -->
				</div>
			</div>
			<!-- End Content -->
		</section>
		<hr />
	</div>
</body>
</html>
