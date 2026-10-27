/**
 * @copyright  (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 *
 * Google reCAPTCHA: renders the v2 checkbox, and runs the v2 invisible and v3 checks when their form is sent (a token is
 * only valid for two minutes, so it's asked for then, not when the page loads), sending the form on once it's there.
 */
(() => {
	'use strict';

	// The site's callbacks are names of global functions
	const callback = (name, ...values) => {
		if (name && typeof window[name] === 'function') {
			window[name](...values);
		}
	};

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

		// Sending may still be stopped (validation): the next attempt asks again
		passed.delete(form);
	};

	// Runs check() on submit, unless another listener (form validation) already stopped it
	const onSubmit = (form, check) => {
		form.addEventListener('submit', (event) => {
			if (passed.has(form) || event.defaultPrevented) {
				return;
			}

			event.preventDefault();
			check(event.submitter || null);
		});
	};

	// The widget's own settings, those the element has
	const options = (element) => {
		const settings = {};

		['sitekey', 'theme', 'size', 'badge', 'tabindex'].forEach((name) => {
			if (element.dataset[name] !== undefined) {
				settings[name] = element.dataset[name];
			}
		});

		return settings;
	};

	const setUp = (element) => {
		const data = element.dataset;
		const form = element.closest('form');

		if (data.joomlaType === 'score') {
			const input = element.querySelector('input[name="g-recaptcha-response"]');
			const check = (submitter) => {
				window.grecaptcha.ready(() => {
					// Without a token (Google unreachable) the form still goes, and the site says what's missing. Google's promise
					// is its own, without catch()
					Promise.resolve(window.grecaptcha.execute(data.sitekey, { action: data.action })).then((token) => {
						input.value = token;
					}).catch(() => {}).then(() => {
						if (form) {
							sendOn(form, submitter);
						}
					});
				});
			};

			if (form) {
				onSubmit(form, check);
			} else {
				check(null);
			}

			return;
		}

		const settings = options(element);
		let submitter  = null;
		let widget     = null;

		settings['expired-callback'] = () => callback(data.expiredCallback);
		settings['error-callback']   = () => {
			callback(data.errorCallback);

			// The form isn't left waiting: the site says the CAPTCHA is missing
			if (data.joomlaType === 'invisible' && form) {
				sendOn(form, submitter);
			}
		};
		settings.callback = (token) => {
			callback(data.callback, token);

			if (data.joomlaType === 'invisible' && form) {
				sendOn(form, submitter);
			}
		};

		widget = window.grecaptcha.render(element, settings);
		element.setAttribute('data-recaptcha-widget-id', widget);

		if (data.joomlaType !== 'invisible') {
			return;
		}

		if (!form) {
			window.grecaptcha.execute(widget);

			return;
		}

		onSubmit(form, (button) => {
			submitter = button;
			window.grecaptcha.reset(widget);
			window.grecaptcha.execute(widget);
		});
	};

	// Called by Google's script once it has loaded (its "onload" parameter)
	window.JoomlaInitReCaptcha2 = () => {
		const start = () => {
			const elements = document.querySelectorAll('.joomla-recaptcha:not([data-recaptcha-ready])');

			for (let i = 0; i < elements.length; i++) {
				elements[i].setAttribute('data-recaptcha-ready', '');
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
