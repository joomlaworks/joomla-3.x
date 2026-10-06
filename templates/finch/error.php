<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

/**
 * The error page writes its head itself (no document API: it may be shown when something failed early), with the same
 * stylesheets as the other pages.
 *
 * @var JDocumentError $this
 */
$code = (int) $this->error->getCode();
$tpl  = $this->baseurl . '/templates/' . $this->template;
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo $code . ' - ' . FinchHelper::e($this->title); ?></title>
	<?php foreach (FinchHelper::stylesheets() as $stylesheet) : ?>
	<link href="<?php echo htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" />
	<?php endforeach; ?>
	<link href="<?php echo $tpl; ?>/images/favicon.svg" rel="icon" type="image/svg+xml" />
</head>
<body class="site isError">
	<?php echo FinchHelper::sprite(); ?>
	<main class="siteMain errorPage">
		<div class="container containerPost">
			<?php echo FinchHelper::logo(FinchHelper::siteName()); ?>
			<p class="errorCode"><?php echo $code; ?></p>
			<h1 class="pageTitle"><?php echo FinchHelper::e($this->error->getMessage()); ?></h1>
			<p><a class="btn" href="<?php echo $this->baseurl; ?>/"><?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
			<?php if ($this->debug) : ?>
			<pre><?php echo FinchHelper::e($this->error->getTraceAsString()); ?></pre>
			<?php endif; ?>
		</div>
	</main>
</body>
</html>
