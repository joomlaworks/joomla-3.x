/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 *
 * Cloudflare Turnstile: renders the widget; a form sent before its token is there waits for it, then goes on.
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
		const data    = element.dataset;
		const form    = element.closest('form');
		let waiting   = false;
		let submitter = null;

		const widget = window.turnstile.render(element, {
			sitekey: data.sitekey,
			theme: data.theme,
			size: data.size,
			appearance: data.appearance,
			language: data.language,
			callback: () => {
				if (waiting && form) {
					waiting = false;
					sendOn(form, submitter);
				}
			},
			// The form isn't left waiting: the site says the CAPTCHA is missing
			'error-callback': () => {
				if (waiting && form) {
					waiting = false;
					sendOn(form, submitter);
				}
			},
		});

		if (!form) {
			return;
		}

		form.addEventListener('submit', (event) => {
			if (passed.has(form) || event.defaultPrevented || window.turnstile.getResponse(widget)) {
				return;
			}

			event.preventDefault();
			waiting   = true;
			submitter = event.submitter || null;

			// An expired token is renewed; a widget still working finishes on its own
			if (window.turnstile.isExpired(widget)) {
				window.turnstile.reset(widget);
			}
		});
	};

	// Called by Cloudflare's script once it has loaded (its "onload" parameter)
	window.JoomlaInitTurnstile = () => {
		const start = () => {
			const elements = document.querySelectorAll('.joomla-turnstile:not([data-turnstile-ready])');

			for (let i = 0; i < elements.length; i++) {
				elements[i].setAttribute('data-turnstile-ready', '');
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
