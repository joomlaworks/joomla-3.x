/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/* Finch: the search panel, share popups, copy link, X posts */
((document, window) => {
	'use strict';

	const panel = document.getElementById('searchPanel');
	const toggle = document.querySelector('.searchToggle');

	const setSearch = (open) => {
		if (!panel || !toggle) {
			return;
		}

		panel.hidden = !open;
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

		const field = open ? panel.querySelector('input[type="search"], input[type="text"]') : null;

		if (field) {
			field.focus({ preventScroll: true });
		}
	};

	document.addEventListener('click', (event) => {
		const target = event.target instanceof Element ? event.target : null;

		if (!target) {
			return;
		}

		if (target.closest('.searchToggle')) {
			setSearch(panel ? panel.hidden : false);

			return;
		}

		// A click outside the open search panel closes it
		if (panel && !panel.hidden && !target.closest('.searchPanel')) {
			setSearch(false);
		}

		// Share links: a popup window, centered on the browser window (a new tab when popups are blocked)
		const share = target.closest('a.sharePopup');

		if (share) {
			const width = 720;
			const height = 620;
			const left = Math.max(0, Math.round(window.screenX + (window.outerWidth - width) / 2));
			const top = Math.max(0, Math.round(window.screenY + (window.outerHeight - height) / 3));
			const popup = window.open(share.href, 'finchShare', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);

			if (popup) {
				popup.opener = null;
				popup.focus();
				event.preventDefault();
			}

			return;
		}

		const copy = target.closest('.copyLink');

		if (copy && navigator.clipboard) {
			navigator.clipboard.writeText(copy.getAttribute('data-url')).then(() => {
				copy.classList.add('copied');
				copy.setAttribute('title', copy.getAttribute('data-copied'));
				setTimeout(() => copy.classList.remove('copied'), 2000);
			});
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && panel && !panel.hidden) {
			setSearch(false);
			toggle.focus({ preventScroll: true });
		}
	});

	// X posts: load X's script only when the page has one
	if (document.querySelector('blockquote.twitter-tweet')) {
		const script = document.createElement('script');

		script.src = 'https://platform.twitter.com/widgets.js';
		script.async = true;
		script.charset = 'utf-8';
		document.body.appendChild(script);
	}
})(document, window);
