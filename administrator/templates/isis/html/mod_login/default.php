<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.isis
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The administrator login form, styled by the template's login.css (no Bootstrap, jQuery or Chosen). Field names and IDs are
 * those of mod_login's own layout. Keyboard order (tabindex): username, password and the button first, then the other fields,
 * the password toggle and the help links; the page's "Visit the site" link last (9).
 */

JHtml::_('behavior.keepalive');

$root = htmlspecialchars(JUri::root(), ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure', 0)); ?>" method="post" id="form-login" class="loginForm">
	<div class="loginField">
		<div class="loginField-head">
			<label for="mod-login-username"><?php echo JText::_('JGLOBAL_USERNAME'); ?></label>
			<a href="<?php echo $root; ?>index.php?option=com_users&amp;view=remind" tabindex="7"><?php echo JText::_('MOD_LOGIN_REMIND'); ?></a>
		</div>
		<input name="username" id="mod-login-username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" required="required" autofocus="autofocus" tabindex="1" />
	</div>
	<div class="loginField">
		<div class="loginField-head">
			<label for="mod-login-password"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
			<a href="<?php echo $root; ?>index.php?option=com_users&amp;view=reset" tabindex="8"><?php echo JText::_('MOD_LOGIN_RESET'); ?></a>
		</div>
		<div class="loginField-reveal">
			<input name="passwd" id="mod-login-password" type="password" autocomplete="current-password" required="required" tabindex="2" />
			<button type="button" class="loginReveal" tabindex="6" aria-controls="mod-login-password" aria-pressed="false" data-show="<?php echo JText::_('JSHOW'); ?>" data-hide="<?php echo JText::_('JHIDE'); ?>" hidden="hidden">
				<svg class="loginReveal-show" aria-hidden="true" viewBox="0 0 24 24" width="20" height="20"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
				<svg class="loginReveal-hide" aria-hidden="true" viewBox="0 0 24 24" width="20" height="20"><path d="M3 3l18 18M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.8 8.3 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span class="loginReveal-label"><?php echo JText::_('JSHOW'); ?></span>
			</button>
		</div>
	</div>
	<?php if (count($twofactormethods) > 1) : ?>
	<div class="loginField">
		<div class="loginField-head">
			<label for="mod-login-secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY'); ?></label>
		</div>
		<input name="secretkey" id="mod-login-secretkey" type="text" autocomplete="one-time-code" inputmode="numeric" tabindex="4" aria-describedby="mod-login-secretkey-help" />
		<p class="loginField-help" id="mod-login-secretkey-help"><?php echo JText::_('JGLOBAL_SECRETKEY_HELP'); ?></p>
	</div>
	<?php endif; ?>
	<?php if (!empty($langs)) : ?>
	<div class="loginField">
		<div class="loginField-head">
			<label for="lang"><?php echo JText::_('MOD_LOGIN_LANGUAGE'); ?></label>
		</div>
		<?php echo str_replace(' tabindex="4"', ' tabindex="5"', $langs); ?>
	</div>
	<?php endif; ?>
	<button type="submit" class="loginSubmit" tabindex="3"><?php echo JText::_('MOD_LOGIN_LOGIN'); ?></button>
	<input type="hidden" name="option" value="com_login" />
	<input type="hidden" name="task" value="login" />
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
