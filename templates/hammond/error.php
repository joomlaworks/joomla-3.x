<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('HammondHelper', false) || require_once __DIR__ . '/helper.php';

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
<html lang="<?php echo htmlspecialchars($this->language, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo htmlspecialchars($this->direction, ENT_QUOTES, 'UTF-8'); ?>">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo $code . ' - ' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8'); ?></title>
	<?php foreach (HammondHelper::stylesheets() as $stylesheet) : ?>
	<link href="<?php echo htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" />
	<?php endforeach; ?>
	<link href="<?php echo htmlspecialchars($tpl, ENT_QUOTES, 'UTF-8'); ?>/images/favicon.svg" rel="icon" type="image/svg+xml" />
</head>
<body class="site isInner isError">
	<main class="siteMain errorPage">
		<div class="container containerNarrow">
			<?php echo HammondHelper::logo(); ?>
			<p class="errorCode"><?php echo $code; ?></p>
			<h1 class="pageTitle"><?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></h1>
			<p><a class="btn" href="<?php echo htmlspecialchars($this->baseurl, ENT_QUOTES, 'UTF-8'); ?>/"><?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a></p>
			<?php if ($this->debug) : ?>
			<pre><?php echo htmlspecialchars($this->error->getTraceAsString(), ENT_QUOTES, 'UTF-8'); ?></pre>
			<?php endif; ?>
		</div>
	</main>
</body>
</html>
