/**
 * @copyright  (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
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

/*
 * Global Configuration's Text Filters: a group set to No Filtering while its parent or child groups filter (or the other
 * way round) gets a note. Lists drawn by Chosen change through jQuery: heard through it when the page has it.
 */
(() => {
	'use strict';

	const list = (attribute, value) => document.querySelector('#filter-config select[' + attribute + '="' + value + '"]');

	const check = (event) => {
		const select = event.target;

		if (!select.closest || !select.closest('#filter-config') || select.tagName !== 'SELECT') {
			return;
		}

		// No Filtering: the groups below must not filter; anything else: the groups above must
		const none = select.value === 'NONE';
		const attribute = none ? 'data-parent' : 'data-id';
		const next = none ? 'data-id' : 'data-parent';
		let other = list(attribute, select.getAttribute(next));

		while (other) {
			if (none === (other.value !== 'NONE')) {
				window.alert(window.Joomla.JText._('COM_CONFIG_TEXT_FILTERS_NOTE'));
				break;
			}

			other = list(attribute, other.getAttribute(next));
		}
	};

	const start = () => {
		if (!document.getElementById('filter-config')) {
			return;
		}

		if (window.jQuery) {
			window.jQuery(document).on('change', '#filter-config select', check);
		} else {
			document.addEventListener('change', check);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
