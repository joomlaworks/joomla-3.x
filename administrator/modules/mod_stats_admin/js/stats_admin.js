/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * System Information module: a link (a.js-revert) opens its page on the tab named after its # (written into the tab
 * state, as the page's own tabs keep it).
 */
(() => {
	'use strict';

	document.addEventListener('click', (event) => {
		const link = event.target.closest ? event.target.closest('a.js-revert') : null;

		if (!link) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		const href = link.href;

		try {
			localStorage.removeItem(href.replace(/&return=[a-zA-Z0-9%]+/, '').replace(/&[a-zA-Z-_]+=[0-9]+/, ''));
			localStorage.setItem(window.location.pathname + href.split('index.php')[1].split('#')[0], JSON.stringify(['#' + href.split('#')[1]]));
		} catch (error) {
			// Without storage the page opens on its first tab
		}

		window.location.href = href.split('#')[0];
	});
})();
