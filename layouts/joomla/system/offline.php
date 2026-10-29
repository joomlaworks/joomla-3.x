<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The offline page of a template without offline.php: the site's name, image and offline message beside a login form
 * (side by side on wide screens, one above the other on narrow ones). The form keeps the old page's names and ids.
 *
 * @var  JDocumentHtml  $this
 */

$app = JFactory::getApplication();

$this->setHtml5(true);

// Styles (scoped to body.tmpl-offline, with the system messages)
JHtml::_('stylesheet', 'system/frontend.min.css', array('version' => 'auto', 'relative' => true));

$twofactormethods = JAuthenticationHelper::getTwoFactorMethods();
$siteName         = htmlspecialchars((string) $app->get('sitename'), ENT_COMPAT, 'UTF-8');
$image            = $app->get('offline_image') && file_exists($app->get('offline_image')) ? $app->get('offline_image') : '';
$message          = '';

if ($app->get('display_offline_message', 1) == 1 && str_replace(' ', '', $app->get('offline_message')) !== '')
{
	$message = $app->get('offline_message');
}
elseif ($app->get('display_offline_message', 1) == 2 && str_replace(' ', '', JText::_('JOFFLINE_MESSAGE')) !== '')
{
	$message = JText::_('JOFFLINE_MESSAGE');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="head" />
</head>
<body class="tmpl-offline">
	<div class="sys-split">
		<aside class="sys-brand">
			<div class="sys-brand-inner">
				<?php if ($image) : ?>
					<img class="sys-brand-image" src="<?php echo htmlspecialchars(JUri::root(true) . '/' . ltrim($image, '/'), ENT_COMPAT, 'UTF-8'); ?>" alt="<?php echo $siteName; ?>" />
				<?php endif; ?>
				<p class="sys-brand-name"><?php echo $siteName; ?></p>
				<?php if ($message !== '') : ?>
					<p class="sys-brand-message"><?php echo $message; ?></p>
				<?php endif; ?>
			</div>
		</aside>
		<main class="sys-login">
			<div class="sys-card">
				<jdoc:include type="message" />
				<h1 class="sys-title"><?php echo JText::_('JLOGIN'); ?></h1>
				<form action="<?php echo JRoute::_('index.php', true); ?>" method="post" id="form-login">
					<p class="sys-field" id="form-login-username">
						<label for="username"><?php echo JText::_('JGLOBAL_USERNAME'); ?></label>
						<input name="username" id="username" type="text" class="inputbox" autocomplete="username" autocapitalize="none" spellcheck="false" required />
					</p>
					<p class="sys-field" id="form-login-password">
						<label for="passwd"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
						<input type="password" name="password" class="inputbox" id="passwd" autocomplete="current-password" required />
					</p>
					<?php if (count($twofactormethods) > 1) : ?>
						<p class="sys-field" id="form-login-secretkey">
							<label for="secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?></label>
							<input type="text" name="secretkey" autocomplete="one-time-code" inputmode="numeric" class="inputbox" id="secretkey" />
						</p>
					<?php endif; ?>
					<p class="sys-field" id="submit-buton">
						<button type="submit" name="Submit" class="sys-button sys-button-primary button login"><?php echo JText::_('JLOGIN'); ?></button>
					</p>
					<input type="hidden" name="option" value="com_users" />
					<input type="hidden" name="task" value="user.login" />
					<input type="hidden" name="return" value="<?php echo base64_encode(JUri::base()); ?>" />
					<?php echo JHtml::_('form.token'); ?>
				</form>
			</div>
		</main>
	</div>
</body>
</html>
