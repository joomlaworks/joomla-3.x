<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentError $this */
$app      = JFactory::getApplication();
$params   = $app->getTemplate(true)->params;
$siteName = $params->get('siteName') ?: $app->get('sitename');
$code     = (int) $this->error->getCode();
$tpl      = $this->baseurl . '/templates/' . $this->template;
$e        = function ($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo $code . ' - ' . $e($this->title); ?></title>
	<link href="<?php echo $tpl; ?>/css/template.css?t=<?php echo date('Ymd_Hi', filemtime(__DIR__ . '/css/template.css')); ?>" rel="stylesheet" />
	<link href="<?php echo $tpl; ?>/images/favicon.svg" rel="icon" type="image/svg+xml" />
</head>
<body class="site isError">
	<?php echo file_get_contents(__DIR__ . '/images/icons.svg'); ?>
	<main class="siteMain errorPage">
		<div class="container containerPost">
			<a class="logo" href="<?php echo $this->baseurl; ?>/">
				<svg class="logoMark" viewBox="0 0 48 48" aria-hidden="true"><use href="#fi-finch"></use></svg>
				<span class="logoText"><?php echo $e($siteName); ?></span>
			</a>
			<p class="errorCode"><?php echo $code; ?></p>
			<h1 class="pageTitle"><?php echo $e($this->error->getMessage()); ?></h1>
			<p><a class="btn" href="<?php echo $this->baseurl; ?>/"><?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
			<?php if ($this->debug) : ?>
			<pre><?php echo $e($this->error->getTraceAsString()); ?></pre>
			<?php endif; ?>
		</div>
	</main>
</body>
</html>
