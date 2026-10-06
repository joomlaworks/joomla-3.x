/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/* Housekeeping (the administrator's status bar): Clean Cache, and the menu with Clean Everything and Global Check-in */
((document) => {
	'use strict';

	const SHOWN = 5000;

	const init = (box) => {
		const toggle = box.querySelector('.hkToggle');
		const menu = box.querySelector('.hkMenu');
		let busy = false;

		const closeMenu = () => {
			if (menu && !menu.hidden) {
				menu.hidden = true;
				toggle.setAttribute('aria-expanded', 'false');
			}
		};

		// A message over the page: green or red, announced to screen readers, gone after a few seconds or when clicked
		const toast = (text, ok) => {
			let bubble = document.querySelector('.hkToast');

			if (bubble) {
				bubble.remove();
			}

			bubble = document.createElement('div');
			bubble.className = `hkToast ${ok ? 'hkToastSuccess' : 'hkToastFailure'}`;
			bubble.setAttribute('role', ok ? 'status' : 'alert');
			bubble.textContent = text;

			const close = document.createElement('button');

			close.type = 'button';
			close.className = 'hkToastClose';
			close.setAttribute('aria-label', box.dataset.close || 'Close');
			close.textContent = '×';
			bubble.appendChild(close);

			// Opens away from the status bar, wherever the template put it
			if (box.closest('.status-top')) {
				bubble.classList.add('hkToastTop');
			}

			document.body.appendChild(bubble);
			requestAnimationFrame(() => bubble.classList.add('hkToastShown'));

			const hide = () => {
				bubble.classList.remove('hkToastShown');
				setTimeout(() => bubble.remove(), 300);
			};
			const timer = setTimeout(hide, ok ? SHOWN : SHOWN * 2);

			bubble.addEventListener('click', () => {
				clearTimeout(timer);
				hide();
			});
		};

		const run = (button) => {
			const action = button.dataset.housekeepingAction;

			if (busy || (button.dataset.confirm && !window.confirm(button.dataset.confirm))) {
				return;
			}

			busy = true;
			closeMenu();
			box.classList.add('hkBusy');

			const label = box.querySelector('[data-housekeeping-action="cache"] span');
			const text = label ? label.textContent : '';

			if (label) {
				label.textContent = box.dataset.working || text;
			}

			const body = new FormData();

			body.append('action', action);
			// The form token goes in the body, not the URL
			body.append(box.dataset.token, '1');

			fetch(box.dataset.housekeepingUrl, { method: 'POST', body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then((response) => response.json())
				.then((response) => {
					const ok = !!(response && response.success && response.data);

					toast(ok ? response.data.message : (response && response.message) || box.dataset.failed, ok);
				})
				.catch(() => toast(box.dataset.failed, false))
				.finally(() => {
					busy = false;
					box.classList.remove('hkBusy');

					if (label) {
						label.textContent = text;
					}
				});
		};

		box.addEventListener('click', (event) => {
			const button = event.target.closest('button');

			if (!button || !box.contains(button)) {
				return;
			}

			if (button === toggle) {
				const open = menu.hidden;

				menu.hidden = !open;
				toggle.setAttribute('aria-expanded', String(open));

				if (open) {
					menu.querySelector('button').focus();
				}

				return;
			}

			if (button.dataset.housekeepingAction) {
				run(button);
			}
		});

		document.addEventListener('click', (event) => {
			if (!box.contains(event.target)) {
				closeMenu();
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && menu && !menu.hidden) {
				closeMenu();
				toggle.focus();
			}
		});
	};

	document.querySelectorAll('[data-housekeeping-url]').forEach(init);
})(document);
