/**
 * @copyright  (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * Global Configuration's "Send Test Mail" button: sends the mail settings as they are in the form (not yet saved) and shows
 * the answer as system messages. Needs global.js (CMS.request, which sends the form token as a header).
 */
(() => {
	'use strict';

	const value = (selector) => {
		const field = document.querySelector(selector);

		return field ? field.value : '';
	};

	const send = (button) => {
		const data = new URLSearchParams({
			smtpauth: value('input[name="jform[smtpauth]"]:checked'),
			smtpuser: value('input[name="jform[smtpuser]"]'),
			smtphost: value('input[name="jform[smtphost]"]'),
			smtpsecure: value('select[name="jform[smtpsecure]"]'),
			smtpport: value('input[name="jform[smtpport]"]'),
			mailfrom: value('input[name="jform[mailfrom]"]'),
			fromname: value('input[name="jform[fromname]"]'),
			mailer: value('select[name="jform[mailer]"]'),
			mailonline: value('input[name="jform[mailonline]"]:checked'),
		});
		const password = document.querySelector('input[name="jform[smtppass]"]');

		if (password && !password.disabled) {
			data.set('smtppass', password.value);
		}

		CMS.messages.remove();

		CMS.request({
			url: button.getAttribute('data-ajaxuri'),
			method: 'POST',
			data: data.toString(),
			onSuccess(text) {
				let response;

				try {
					response = JSON.parse(text);
				} catch (error) {
					CMS.messages.render(CMS.messages.fromAjaxError({ responseText: text, status: 200 }, 'parsererror'));
					window.scrollTo(0, 0);

					return;
				}

				if (response.messages && typeof response.messages === 'object') {
					CMS.messages.render(response.messages);
					window.scrollTo(0, 0);
				}
			},
			onError(xhr) {
				CMS.messages.render(CMS.messages.fromAjaxError(xhr, ''));
				window.scrollTo(0, 0);
			},
		});
	};

	document.addEventListener('click', (event) => {
		const button = event.target.closest ? event.target.closest('#sendtestmail') : null;

		if (button) {
			send(button);
		}
	});
})();
