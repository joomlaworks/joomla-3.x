/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * Smart Search's search page and module: the Advanced Search button (data-finder-toggle, the panel's id) shows and hides
 * its panel; lists left on their empty choice stay out of the search's address.
 */
(() => {
	'use strict';

	document.addEventListener('click', (event) => {
		const toggle = event.target.closest ? event.target.closest('[data-finder-toggle]') : null;
		const panel = toggle ? document.getElementById(toggle.getAttribute('data-finder-toggle')) : null;

		if (panel) {
			panel.hidden = !panel.hidden;
			panel.classList.toggle('in', !panel.hidden);
			toggle.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
		}
	});

	document.addEventListener('submit', (event) => {
		event.target.querySelectorAll('#finder-search #advancedSearch select, [id^="mod-finder-searchform"] [id^="mod-finder-advanced"] select').forEach((select) => {
			if (!select.value) {
				select.disabled = true;
			}
		});
	});
})();
