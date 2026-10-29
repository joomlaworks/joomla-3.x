/**
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

/**
 * The statistics message: sends the statistics when the site's choice says so, or asks (always, once, never) in the system
 * messages. The plugin answers only requests with X-Requested-With (com_ajax, group system).
 *
 * @since  3.5.0
 */
document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('system-message-container');
	const ask = (plugin) => fetch('index.php?' + new URLSearchParams({ option: 'com_ajax', group: 'system', plugin, format: 'raw' }).toString(), {
		credentials: 'same-origin',
		headers: { 'X-Requested-With': 'XMLHttpRequest' },
	}).then((response) => response.json());

	if (!container) {
		return;
	}

	ask('sendStats')
		.then((response) => {
			if (!response || !response.html) {
				return;
			}

			container.insertAdjacentHTML('beforeend', response.html);

			const alert = container.querySelector('.js-pstats-alert');
			const details = container.querySelector('.js-pstats-data-details');
			const answer = (plugin) => {
				alert.style.display = 'none';

				if (details) {
					details.remove();
				}

				ask(plugin).catch(() => {});
			};

			if (!alert) {
				return;
			}

			alert.style.display = '';

			alert.addEventListener('click', (event) => {
				const target = event.target.closest ? event.target.closest('a, button') : null;
				const choices = { 'js-pstats-btn-allow-always': 'sendAlways', 'js-pstats-btn-allow-once': 'sendOnce', 'js-pstats-btn-allow-never': 'sendNever' };

				if (!target) {
					return;
				}

				if (target.classList.contains('js-pstats-btn-details')) {
					event.preventDefault();

					if (details) {
						details.style.display = details.style.display === 'none' ? '' : 'none';
					}

					return;
				}

				// The close button (Bootstrap's alert plugin did this): the question comes back next time
				if (target.classList.contains('close')) {
					event.preventDefault();
					alert.remove();

					return;
				}

				Object.keys(choices).forEach((name) => {
					if (target.classList.contains(name)) {
						event.preventDefault();
						answer(choices[name]);
					}
				});
			});
		})
		.catch(() => {});
});
