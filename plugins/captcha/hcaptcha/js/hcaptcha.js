/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */
(() => {
	'use strict';

	// Sends the form on, past this script's own listener; the button that sent it first, when it's still in the form
	const passed = new WeakSet();

	const sendOn = (form, submitter) => {
		passed.add(form);

		if (typeof form.requestSubmit === 'function') {
			if (submitter && submitter.isConnected && submitter.form === form) {
				form.requestSubmit(submitter);
			} else {
				form.requestSubmit();
			}
		} else {
			form.submit();
		}

		passed.delete(form);
	};

	const setUp = (element) => {
		const data      = element.dataset;
		const form      = element.closest('form');
		const invisible = data.size === 'invisible';
		let submitter   = null;

		// The form isn't left waiting when the check fails: the site says the CAPTCHA is missing
		const goOn = () => {
			if (invisible && form) {
				sendOn(form, submitter);
			}
		};

		const widget = window.hcaptcha.render(element, {
			sitekey: data.sitekey,
			theme: data.theme,
			size: data.size,
			callback: goOn,
			'error-callback': goOn,
			'chalexpired-callback': goOn,
		});

		if (!invisible) {
			return;
		}

		if (!form) {
			window.hcaptcha.execute(widget);

			return;
		}

		form.addEventListener('submit', (event) => {
			if (passed.has(form) || event.defaultPrevented) {
				return;
			}

			event.preventDefault();
			submitter = event.submitter || null;
			window.hcaptcha.reset(widget);
			window.hcaptcha.execute(widget);
		});
	};

	// Called by hCaptcha's script once it has loaded (its "onload" parameter)
	window.JoomlaInitHcaptcha = () => {
		const start = () => {
			const elements = document.querySelectorAll('.joomla-hcaptcha:not([data-hcaptcha-ready])');

			for (let i = 0; i < elements.length; i++) {
				elements[i].setAttribute('data-hcaptcha-ready', '');
				setUp(elements[i]);
			}
		};

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', start);
		} else {
			start();
		}
	};
})();
