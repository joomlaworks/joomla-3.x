<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
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
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo $code . ' - ' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8'); ?></title>
	<link href="<?php echo $tpl; ?>/css/template.css?t=<?php echo date('Ymd_Hi', filemtime(__DIR__ . '/css/template.css')); ?>" rel="stylesheet" />
	<link href="<?php echo $tpl; ?>/images/favicon.svg" rel="icon" type="image/svg+xml" />
</head>
<body class="site isInner isError">
	<main class="siteMain errorPage">
		<div class="container containerNarrow">
			<a class="logo" href="<?php echo $this->baseurl; ?>/">
				<svg class="logoMark" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="9" fill="currentColor"/><path d="M12 10.5v19M28 10.5v19M12 20h16" fill="none" stroke="#fff" stroke-width="4.6" stroke-linecap="round"/><circle cx="31.5" cy="8.5" r="2.6" fill="var(--c-accent)"/></svg>
				<span class="logoText"><strong><?php echo htmlspecialchars(strtok($siteName, ' '), ENT_QUOTES, 'UTF-8'); ?></strong><?php echo strpos($siteName, ' ') !== false ? ' <span>' . htmlspecialchars(substr($siteName, strpos($siteName, ' ') + 1), ENT_QUOTES, 'UTF-8') . '</span>' : ''; ?></span>
			</a>
			<p class="errorCode"><?php echo $code; ?></p>
			<h1 class="pageTitle"><?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></h1>
			<p><a class="btn" href="<?php echo $this->baseurl; ?>/"><?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
			<?php if ($this->debug) : ?>
			<pre><?php echo htmlspecialchars($this->error->getTraceAsString(), ENT_QUOTES, 'UTF-8'); ?></pre>
			<?php endif; ?>
		</div>
	</main>
</body>
</html>
