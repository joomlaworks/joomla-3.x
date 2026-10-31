<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.prothero
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 * @since       3.0
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

$page = require __DIR__ . '/includes/helper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-scheme="<?php echo $page->scheme; ?>" data-navbar="<?php echo $page->navbar; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="header" />
</head>
<body class="admin <?php echo $page->option . ' view-' . $page->view . ' layout-' . $page->layout . ' task-' . $page->task . ' itemid-' . $page->itemid; ?>" data-basepath="<?php echo JURI::root(true); ?>">
	<!-- Top Navigation -->
	<nav class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container-fluid">
				<?php if ($this->params->get('admin_menus') != '0') : ?>
				<a href="#" class="btn btn-navbar collapsed" data-toggle="collapse" data-target=".nav-collapse">
					<span class="element-invisible"><?php echo JTEXT::_('TPL_PROTHERO_TOGGLE_MENU'); ?></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
				<?php endif; ?>

				<!-- skip to content -->
				<a class="element-invisible" href="#skiptarget"><?php echo JText::_('TPL_PROTHERO_SKIP_TO_MAIN_CONTENT'); ?></a>

				<a class="admin-logo <?php echo($page->hidden ? 'disabled' : ''); ?>" <?php echo($page->hidden ? '' : 'href="' . $this->baseurl . '/index.php"'); ?>>
					<span class="icon-joomla"></span>
					<div class="element-invisible">
						<?php echo JText::_('TPL_PROTHERO_CONTROL_PANEL'); ?>
					</div>
				</a>

				<a class="brand hidden-desktop hidden-tablet" href="<?php echo $page->mainPageUri; ?>" title="<?php echo JText::sprintf('TPL_PROTHERO_PREVIEW', $page->sitename); ?>" target="_blank"><?php echo JHtml::_('string.truncate', $page->sitename, 14, false, false); ?>
					<span class="icon-out-2 small"></span></a>

				<div<?php echo ($this->params->get('admin_menus') != '0') ? ' class="nav-collapse collapse"' : ''; ?>>
					<jdoc:include type="modules" name="menu" style="none" />
					<ul class="nav nav-user<?php echo ($this->direction == 'rtl') ? ' pull-left' : ' pull-right'; ?>">
						<li class="dropdown">
							<a class="<?php echo($page->hidden ? ' disabled' : 'dropdown-toggle'); ?>" data-toggle="<?php echo($page->hidden ? '' : 'dropdown'); ?>" <?php echo($page->hidden ? '' : 'href="#"'); ?>><span class="icon-user"></span>
								<span class="caret"></span>
								<div class="element-invisible">
									<?php echo JText::_('TPL_PROTHERO_USERMENU'); ?>
								</div>
							</a>
							<ul class="dropdown-menu">
								<?php if (!$page->hidden) : ?>
								<li>
									<span>
										<span class="icon-user"></span>
										<strong><?php echo htmlspecialchars((string) $page->user->name, ENT_QUOTES, 'UTF-8'); ?></strong>
									</span>
								</li>
								<li class="divider"></li>
								<li>
									<a href="index.php?option=com_admin&amp;task=profile.edit&amp;id=<?php echo $page->user->id; ?>"><?php echo JText::_('TPL_PROTHERO_EDIT_ACCOUNT'); ?></a>
								</li>
								<li class="divider"></li>
								<li class="">
									<a href="<?php echo JRoute::_('index.php?option=com_login&task=logout&' . JSession::getFormToken() . '=1'); ?>"><?php echo JText::_('TPL_PROTHERO_LOGOUT'); ?></a>
								</li>
								<?php endif; ?>
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
	<?php if ($page->displayHeader) : ?>
	<header class="header">
		<div class="container-logo">
			<?php if ($page->logo !== '') : ?>
			<img src="<?php echo $page->logo; ?>" class="logo" alt="<?php echo $page->sitename;?>" />
			<?php else : ?>
			<span class="logo"><?php echo ProtheroHelper::logo(); ?></span>
			<?php endif; ?>
		</div>
		<div class="container-title">
			<jdoc:include type="modules" name="title" />
		</div>
	</header>
	<?php endif; ?>
	<?php if (!$page->cpanel) : ?>
	<!-- Subheader -->
	<a class="btn btn-subhead" data-toggle="collapse" data-target=".subhead-collapse"><?php echo JText::_('TPL_PROTHERO_TOOLBAR'); ?>
		<span class="icon-wrench"></span></a>
	<div class="subhead-collapse collapse" id="isisJsData" data-tmpl-sticky="<?php echo $page->stickyBar; ?>" data-tmpl-offset="<?php echo $page->offset; ?>">
		<div class="subhead">
			<div class="container-fluid">
				<div id="container-collapse" class="container-collapse"></div>
				<div class="row-fluid">
					<div class="span12">
						<!-- target for skip to content link -->
						<a id="skiptarget" class="element-invisible"><?php echo JText::_('TPL_PROTHERO_SKIP_TO_MAIN_CONTENT_HERE'); ?></a>
						<jdoc:include type="modules" name="toolbar" style="no" />
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php else : ?>
	<div style="margin-bottom: 20px">
		<!-- target for skip to content link -->
		<a id="skiptarget" class="element-invisible"><?php echo JText::_('TPL_PROTHERO_SKIP_TO_MAIN_CONTENT_HERE'); ?></a>
	</div>
	<?php endif; ?>
	<!-- container-fluid -->
	<div class="container-fluid container-main">
		<section id="content">
			<!-- Begin Content -->
			<jdoc:include type="modules" name="top" style="xhtml" />
			<div class="row-fluid">
				<?php if ($page->showSubmenu) : ?>
				<div class="span2">
					<jdoc:include type="modules" name="submenu" style="none" />
				</div>
				<div class="span10">
				<?php else : ?>
				<div class="span12">
				<?php endif; ?>
					<jdoc:include type="message" />
					<jdoc:include type="component" />
				</div>
			</div>
			<?php if ($this->countModules('bottom')) : ?>
			<jdoc:include type="modules" name="bottom" style="xhtml" />
			<?php endif; ?>
			<!-- End Content -->
		</section>

		<?php if (!$this->countModules('status')) : ?>
		<footer class="footer">
			<p class="text-center">
				<jdoc:include type="modules" name="footer" style="no" />
				&copy; <?php echo $page->sitename; ?> <?php echo date('Y'); ?></p>
		</footer>
		<?php endif; ?>
	</div>
	<?php if ($this->countModules('status')) : ?>
	<!-- Begin Status Module -->
	<div id="status" class="navbar navbar-fixed-bottom hidden-phone">
		<div class="btn-toolbar">
			<div class="btn-group pull-right">
				<p>
					<jdoc:include type="modules" name="footer" style="no" />
					&copy; <?php echo date('Y'); ?> <?php echo $page->sitename; ?>
				</p>

			</div>
			<jdoc:include type="modules" name="status" style="no" />
		</div>
	</div>
	<!-- End Status Module -->
	<?php endif; ?>
	<jdoc:include type="modules" name="debug" style="none" />
	<jdoc:include type="footer" />
</body>
</html>
