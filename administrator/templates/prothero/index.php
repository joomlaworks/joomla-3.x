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

$app   = JFactory::getApplication();
$lang  = JFactory::getLanguage();
$input = $app->input;
$user  = JFactory::getUser();

// Output as HTML5
$this->setHtml5(true);

// Gets the FrontEnd Main page Uri
$frontEndUri = JUri::getInstance(JUri::root());
$frontEndUri->setScheme(((int) $app->get('force_ssl', 0) === 2) ? 'https' : 'http');
$mainPageUri = $frontEndUri->toString();

// No legacy markup: no scripts and stylesheets for old Internet Explorer versions
require_once __DIR__ . '/helper.php';

protheroDropConditionalAssets($this);

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');

// Add template js
JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true, 'position' => 'footer'));

// For the lists' Reorder button on phones (js/template.js)
JText::script('TPL_PROTHERO_REORDER');
JText::script('TPL_PROTHERO_REORDER_DONE');

// Add Stylesheets
JHtml::_('stylesheet', 'template.css', array('version' => 'auto', 'relative' => true));

if ($this->direction === 'rtl') {
    JHtml::_('stylesheet', 'template-rtl.css', array('version' => 'auto', 'relative' => true));
}

// Load specific language related CSS
JHtml::_('stylesheet', 'administrator/language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', array('version' => 'auto'));

// Load custom.css
JHtml::_('stylesheet', 'custom.css', array('version' => 'auto', 'relative' => true));

JHtml::_('stylesheet', 'responsive.css', array('version' => 'auto', 'relative' => true));


// Detecting Active Variables
$option   = $input->get('option', '');
$view     = $input->get('view', '');
$layout   = $input->get('layout', '');
$task     = $input->get('task', '');
$itemid   = $input->get('Itemid', 0, 'int');
$sitename = htmlspecialchars((string) $app->get('sitename', ''), ENT_QUOTES, 'UTF-8');
$cpanel   = $option === 'com_cpanel';

$hidden = $app->input->get('hidemainmenu');

$showSubmenu          = false;
$this->submenumodules = JModuleHelper::getModules('submenu');

foreach ($this->submenumodules as $submenumodule) {
    $output = JModuleHelper::renderModule($submenumodule);

    if ($output !== '') {
        $showSubmenu = true;
        break;
    }
}

// Template Parameters
$displayHeader = $this->params->get('displayHeader', '1');
$stickyToolbar = $this->params->get('stickyToolbar', '1');

// The logo in the header: the site's own, else Joomla's, inline so its wordmark follows the colour scheme
$logo = $displayHeader && $this->params->get('logoFile') ? JUri::root() . htmlspecialchars((string) $this->params->get('logoFile'), ENT_QUOTES) : '';

// Pass some values to javascript
$offset = 34;

if ($displayHeader) {
    $offset = 44;
}

$stickyBar = 0;

if ($stickyToolbar) {
    $stickyBar = 'true';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-scheme="<?php echo protheroScheme($this->params); ?>" data-navbar="<?php echo protheroNavbarColor($this->params); ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<jdoc:include type="header" />
</head>
<body class="admin <?php echo $option . ' view-' . $view . ' layout-' . $layout . ' task-' . $task . ' itemid-' . $itemid; ?>" data-basepath="<?php echo JURI::root(true); ?>">
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

			<a class="admin-logo <?php echo($hidden ? 'disabled' : ''); ?>" <?php echo($hidden ? '' : 'href="' . $this->baseurl . '/index.php"'); ?>>
				<span class="icon-joomla"></span>
				<div class="element-invisible">
					<?php echo JText::_('TPL_PROTHERO_CONTROL_PANEL'); ?>
				</div>
			</a>

			<a class="brand hidden-desktop hidden-tablet" href="<?php echo $mainPageUri; ?>" title="<?php echo JText::sprintf('TPL_PROTHERO_PREVIEW', $sitename); ?>" target="_blank"><?php echo JHtml::_('string.truncate', $sitename, 14, false, false); ?>
				<span class="icon-out-2 small"></span></a>

			<div<?php echo ($this->params->get('admin_menus') != '0') ? ' class="nav-collapse collapse"' : ''; ?>>
				<jdoc:include type="modules" name="menu" style="none" />
				<ul class="nav nav-user<?php echo ($this->direction == 'rtl') ? ' pull-left' : ' pull-right'; ?>">
					<li class="dropdown">
						<a class="<?php echo($hidden ? ' disabled' : 'dropdown-toggle'); ?>" data-toggle="<?php echo($hidden ? '' : 'dropdown'); ?>" <?php echo($hidden ? '' : 'href="#"'); ?>><span class="icon-user"></span>
							<span class="caret"></span>
							<div class="element-invisible">
								<?php echo JText::_('TPL_PROTHERO_USERMENU'); ?>
							</div>
						</a>
						<ul class="dropdown-menu">
							<?php if (!$hidden) : ?>
								<li>
									<span>
										<span class="icon-user"></span>
										<strong><?php echo htmlspecialchars((string) $user->name, ENT_QUOTES, 'UTF-8'); ?></strong>
									</span>
								</li>
								<li class="divider"></li>
								<li>
									<a href="index.php?option=com_admin&amp;task=profile.edit&amp;id=<?php echo $user->id; ?>"><?php echo JText::_('TPL_PROTHERO_EDIT_ACCOUNT'); ?></a>
								</li>
								<li class="divider"></li>
								<li class="">
									<a href="<?php echo JRoute::_('index.php?option=com_login&task=logout&' . JSession::getFormToken() . '=1'); ?>"><?php echo JText::_('TPL_PROTHERO_LOGOUT'); ?></a>
								</li>
							<?php endif; ?>
						</ul>
					</li>
				</ul>
				<a class="brand visible-desktop visible-tablet" href="<?php echo $mainPageUri; ?>" title="<?php echo JText::sprintf('TPL_PROTHERO_PREVIEW', $sitename); ?>" target="_blank"><?php echo JHtml::_('string.truncate', $sitename, 14, false, false); ?>
					<span class="icon-out-2 small"></span></a>
			</div>
			<!--/.nav-collapse -->
		</div>
	</div>
</nav>
<!-- Header -->
<?php if ($displayHeader) : ?>
	<header class="header">
		<div class="container-logo">
			<?php if ($logo !== '') : ?>
				<img src="<?php echo $logo; ?>" class="logo" alt="<?php echo $sitename;?>" />
			<?php else : ?>
				<span class="logo"><?php echo protheroLogo(); ?></span>
			<?php endif; ?>
		</div>
		<div class="container-title">
			<jdoc:include type="modules" name="title" />
		</div>
	</header>
<?php endif; ?>
<?php if (!$cpanel) : ?>
	<!-- Subheader -->
	<a class="btn btn-subhead" data-toggle="collapse" data-target=".subhead-collapse"><?php echo JText::_('TPL_PROTHERO_TOOLBAR'); ?>
		<span class="icon-wrench"></span></a>
	<div class="subhead-collapse collapse" id="isisJsData" data-tmpl-sticky="<?php echo $stickyBar; ?>" data-tmpl-offset="<?php echo $offset; ?>">
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
			<?php if ($showSubmenu) : ?>
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
				&copy; <?php echo $sitename; ?> <?php echo date('Y'); ?></p>
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
					&copy; <?php echo date('Y'); ?> <?php echo $sitename; ?>
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
