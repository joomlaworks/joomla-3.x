<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('RookwoodHelper', false) || require_once __DIR__ . '/helper.php';

/** @var JDocumentHtml $this */
$page = RookwoodHelper::prepare($this, 'offline');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-theme="<?php echo $page->theme === 'light' ? 'light' : 'dark'; ?>">
<head>
	<jdoc:include type="head" />
</head>
<body class="site isOffline">
	<?php echo RookwoodHelper::sprite(); ?>
	<main class="siteMain errorPage offlinePage">
		<div class="container containerNarrow">
			<h1 class="offlineTitle"><?php echo RookwoodHelper::logo(); ?></h1>
			<jdoc:include type="message" />
			<?php if ($image = RookwoodHelper::offlineImage()) : ?>
			<img class="offlineImage" src="<?php echo RookwoodHelper::e($image); ?>" alt="" fetchpriority="high" />
			<?php endif; ?>
			<?php if ($message = RookwoodHelper::offlineMessage()) : ?>
			<div class="offlineMessage"><?php echo $message; ?></div>
			<?php endif; ?>

			<form class="offlineLogin" action="<?php echo JRoute::_('index.php', true); ?>" method="post">
				<div class="control-group">
					<label class="control-label" for="username"><?php echo JText::_('JGLOBAL_USERNAME'); ?></label>
					<input type="text" name="username" id="username" autocomplete="username" autocapitalize="none" required />
				</div>
				<div class="control-group">
					<label class="control-label" for="password"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
					<input type="password" name="password" id="password" autocomplete="current-password" required />
				</div>
				<?php if (count(JAuthenticationHelper::getTwoFactorMethods()) > 1) : ?>
				<div class="control-group">
					<label class="control-label" for="secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?></label>
					<input type="text" name="secretkey" id="secretkey" autocomplete="one-time-code" />
				</div>
				<?php endif; ?>
				<button type="submit" class="btn btnPrimary"><?php echo JText::_('JLOGIN'); ?></button>
				<input type="hidden" name="option" value="com_users" />
				<input type="hidden" name="task" value="user.login" />
				<input type="hidden" name="return" value="<?php echo base64_encode(JUri::base()); ?>" />
				<?php echo JHtml::_('form.token'); ?>
			</form>
		</div>
	</main>
</body>
</html>
