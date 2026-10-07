/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/* Rookwood: the theme switch, the menu on small screens, the header's state once the page scrolls */
((document, window) => {
	'use strict';

	const root = document.documentElement;
	const body = document.body;

	// The theme: the head's script set it before the page was drawn; the switch changes it and the browser remembers the choice
	const themeToggle = document.querySelector('.themeToggle');

	const showTheme = () => {
		if (!themeToggle) {
			return;
		}

		const label = themeToggle.getAttribute(root.getAttribute('data-theme') === 'light' ? 'data-label-dark' : 'data-label-light');

		themeToggle.setAttribute('aria-label', label);
		themeToggle.setAttribute('title', label);
	};

	if (themeToggle) {
		showTheme();
		themeToggle.addEventListener('click', () => {
			const theme = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';

			root.setAttribute('data-theme', theme);
			showTheme();

			try {
				window.localStorage.setItem('rookwood-theme', theme);
			} catch (error) {
				// Private windows and blocked storage: the choice lasts for this page only
			}
		});
	}

	// The menu on small screens: a panel over the page, closed with the button, Escape or a link
	const menuToggle = document.querySelector('.menuToggle');
	const nav = document.getElementById('mainNav');

	const setMenu = (open) => {
		if (!menuToggle || !nav) {
			return;
		}

		body.classList.toggle('menuOpen', open);
		menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	};

	if (menuToggle && nav) {
		menuToggle.addEventListener('click', () => setMenu(!body.classList.contains('menuOpen')));
		nav.addEventListener('click', (event) => {
			if (event.target instanceof Element && event.target.closest('a')) {
				setMenu(false);
			}
		});
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && body.classList.contains('menuOpen')) {
				setMenu(false);
				menuToggle.focus();
			}
		});
		window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
			if (event.matches) {
				setMenu(false);
			}
		});
	}

	// The header gets a background once the page has scrolled: a sentinel at the top of the page tells when
	const header = document.querySelector('.siteHeader');

	if (header && 'IntersectionObserver' in window) {
		const sentinel = document.createElement('div');

		sentinel.className = 'scrollSentinel';
		sentinel.setAttribute('aria-hidden', 'true');
		body.insertBefore(sentinel, body.firstChild);
		new IntersectionObserver((entries) => {
			header.classList.toggle('isScrolled', !entries[0].isIntersecting);
		}).observe(sentinel);
	}
})(document, window);
