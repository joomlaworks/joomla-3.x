/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.isis
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 *
 * The administrator login page: show/hide the password, close messages, and mark the form busy while it's sent.
 */
(function () {
	'use strict';

	function init() {
		document.querySelectorAll('.loginReveal').forEach(function (button) {
			var input = document.getElementById(button.getAttribute('aria-controls'));
			var label = button.querySelector('.loginReveal-label');

			if (!input) {
				return;
			}

			button.hidden = false;

			button.addEventListener('click', function () {
				var show = input.type === 'password';

				input.type = show ? 'text' : 'password';
				button.setAttribute('aria-pressed', show ? 'true' : 'false');
				label.textContent = button.getAttribute(show ? 'data-hide' : 'data-show');
				input.focus();
			});
		});

		// The message layout's close buttons are meant for Bootstrap's script, which this page doesn't load
		document.addEventListener('click', function (event) {
			var close = event.target.closest('#system-message-container .close');

			if (close && close.parentNode) {
				close.parentNode.remove();
			}
		});

		var form = document.getElementById('form-login');

		if (form) {
			form.addEventListener('submit', function () {
				var submit = form.querySelector('.loginSubmit');

				// Never shown back after a successful login; a failed one reloads the page
				if (submit) {
					submit.setAttribute('aria-busy', 'true');
				}

				// The password stays hidden if the browser restores the page from its cache
				form.querySelectorAll('.loginReveal[aria-pressed="true"]').forEach(function (button) {
					button.click();
				});
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
