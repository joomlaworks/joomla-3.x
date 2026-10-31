<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

$page = require __DIR__ . '/includes/helper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="header" />
</head>
<body class="site<?php echo $page->optionColors['classes']; ?>">
	<div class="outer">
		<div class="middle">
			<div class="inner well">
				<div class="header">
					<?php if (!empty($page->logo)) : ?>
					<h1><?php echo $page->logo; ?></h1>
					<?php else : ?>
					<h1><?php echo $page->sitename; ?></h1>
					<?php endif; ?>
					<?php if ($page->app->get('offline_image') && file_exists($page->app->get('offline_image'))) : ?>
					<img src="<?php echo $page->app->get('offline_image'); ?>" alt="<?php echo $page->sitename; ?>" />
					<?php endif; ?>
					<?php if ($page->app->get('display_offline_message', 1) == 1 && str_replace(' ', '', $page->app->get('offline_message')) !== '') : ?>
					<p><?php echo $page->app->get('offline_message'); ?></p>
					<?php elseif ($page->app->get('display_offline_message', 1) == 2) : ?>
					<p><?php echo JText::_('JOFFLINE_MESSAGE'); ?></p>
					<?php endif; ?>
				</div>
				<jdoc:include type="message" />
				<form action="<?php echo JRoute::_('index.php', true); ?>" method="post" id="form-login">
					<fieldset>
						<label for="username"><?php echo JText::_('JGLOBAL_USERNAME'); ?></label>
						<input name="username" id="username" type="text" title="<?php echo JText::_('JGLOBAL_USERNAME'); ?>" />

						<label for="password"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
						<input type="password" name="password" id="password" title="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>" />

						<?php if (count($page->twofactormethods) > 1) : ?>
						<label for="secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?></label>
						<input type="text" name="secretkey" autocomplete="one-time-code" id="secretkey" title="<?php echo JText::_('JGLOBAL_SECRETKEY'); ?>" />
						<?php endif; ?>

						<input type="submit" name="Submit" class="btn btn-primary" value="<?php echo JText::_('JLOGIN'); ?>" />

						<input type="hidden" name="option" value="com_users" />
						<input type="hidden" name="task" value="user.login" />
						<input type="hidden" name="return" value="<?php echo base64_encode(JUri::base()); ?>" />
						<?php echo JHtml::_('form.token'); ?>
					</fieldset>
				</form>
			</div>
		</div>
	</div>
	<jdoc:include type="footer" />
</body>
</html>
