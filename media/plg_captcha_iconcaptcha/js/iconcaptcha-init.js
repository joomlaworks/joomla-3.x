/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 *
 * IconCaptcha: fills in the form token (the page's "csrf.token" option, which the page cache sets for each visitor), picks
 * the light or dark theme ("auto" follows the page's colour scheme, else the device) and starts the widgets.
 */
(() => {
	'use strict';

	// Joomla's core.js may not be on the page (the site's own templates don't load it)
	const getOptions = (name) => {
		if (window.Joomla && typeof window.Joomla.getOptions === 'function') {
			return window.Joomla.getOptions(name);
		}

		const element = document.querySelector('script.joomla-script-options');

		try {
			return element ? JSON.parse(element.textContent)[name] : undefined;
		} catch (e) {
			return undefined;
		}
	};

	const isDark = () => {
		const root   = document.documentElement;
		const scheme = root.getAttribute('data-scheme') || root.getAttribute('data-theme');

		if (scheme === 'dark' || scheme === 'light') {
			return scheme === 'dark';
		}

		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
	};

	const start = () => {
		const options  = getOptions('plg_captcha_iconcaptcha');
		const elements = document.querySelectorAll('.joomla-iconcaptcha');

		if (!options || !elements.length || !window.IconCaptcha) {
			return;
		}

		const token = getOptions('csrf.token') || '';
		const tokens = document.querySelectorAll('input[name="_iconcaptcha-token"]');

		for (let i = 0; i < tokens.length; i++) {
			tokens[i].value = token;
		}

		for (let i = 0; i < elements.length; i++) {
			const theme = elements[i].getAttribute('data-joomla-theme');

			// The widget marks its hidden fields required, its honeypot (always empty) too, which Joomla's form validation
			// then refuses without a word: the site says whether the challenge was solved
			new MutationObserver(() => {
				const fields = elements[i].querySelectorAll('input[name^="ic-"][required]');

				for (let j = 0; j < fields.length; j++) {
					fields[j].removeAttribute('required');
				}
			}).observe(elements[i], { childList: true, subtree: true });

			elements[i].setAttribute('data-theme', theme === 'light' || theme === 'dark' ? theme : (isDark() ? 'dark' : 'light'));
		}

		// $init() takes the elements: init() runs them through Array.from, which MooTools replaces on some pages
		window.IconCaptcha.$init(Array.prototype.slice.call(elements), {
			general: {
				endpoint: options.endpoint,
				fontFamily: 'inherit',
				showCredits: options.showCredits,
			},
			locale: options.locale,
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
