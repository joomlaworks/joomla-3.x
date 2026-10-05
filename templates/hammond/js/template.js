/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/* Hammond: the mega menu (with search), the sticky header, share popups, copy link, X posts */
((document, window) => {
	'use strict';

	const body = document.body;

	// The mega menu (with the search form): every toggle shows its state
	const menu = document.getElementById('megaMenu');
	const nav = document.querySelector('.mainNav');
	const toggles = document.querySelectorAll('[data-toggle="megaMenu"]');
	let opener = null;

	const setMenu = (open) => {
		if (!menu) {
			return;
		}

		// From the masthead it opens at the navigation bar's top line (over the bar), from the stuck bar right below it;
		// either way it scrolls on its own down to the window's bottom
		if (open && nav) {
			menu.style.top = `${nav.offsetTop + (opener && nav.contains(opener) ? nav.offsetHeight : 0)}px`;
		}

		menu.hidden = !open;

		if (open) {
			menu.style.maxHeight = `${Math.max(240, window.innerHeight - menu.getBoundingClientRect().top)}px`;
		}
		body.classList.toggle('megaOpen', open);
		toggles.forEach((button) => button.setAttribute('aria-expanded', open ? 'true' : 'false'));

		// Ready to type a search, except on touch screens, where the keyboard would cover the menu
		const field = open ? menu.querySelector('input[type="search"]') : null;

		if (field && window.matchMedia('(pointer: fine)').matches) {
			field.focus({ preventScroll: true });
		}
	};

	document.addEventListener('click', (event) => {
		const target = event.target instanceof Element ? event.target : null;
		const toggle = target ? target.closest('[data-toggle="megaMenu"]') : null;

		if (toggle) {
			opener = toggle;
			setMenu(menu ? menu.hidden : false);

			return;
		}

		// A click outside the open menu (on the dimmed page) closes it
		if (menu && !menu.hidden && target && !target.closest('.megaMenu')) {
			setMenu(false);
		}

		// Share links: a popup window, centered on the browser window (a new tab when popups are blocked)
		const share = target ? target.closest('a.sharePopup') : null;

		if (share) {
			const width = 720;
			const height = 620;
			const left = Math.max(0, Math.round(window.screenX + (window.outerWidth - width) / 2));
			const top = Math.max(0, Math.round(window.screenY + (window.outerHeight - height) / 3));
			const popup = window.open(share.href, 'hammondShare', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);

			if (popup) {
				popup.opener = null;
				popup.focus();
				event.preventDefault();
			}

			return;
		}

		const copy = target ? target.closest('.copyLink') : null;

		if (copy && navigator.clipboard) {
			navigator.clipboard.writeText(copy.getAttribute('data-url')).then(() => {
				copy.classList.add('copied');
				copy.setAttribute('title', copy.getAttribute('data-copied'));
				setTimeout(() => copy.classList.remove('copied'), 2000);
			});
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && menu && !menu.hidden) {
			setMenu(false);
			if (opener) {
				opener.focus({ preventScroll: true });
			}
		}
	});

	// Sticky header: once the masthead scrolls away, the navigation bar shows the small logo and the toggles
	const masthead = document.querySelector('.masthead');

	if (nav && 'ResizeObserver' in window) {
		new ResizeObserver(() => {
			document.documentElement.style.setProperty('--stick-offset', `${nav.offsetTop}px`);
		}).observe(document.querySelector('.siteHeader'));
	}

	if (masthead && 'IntersectionObserver' in window) {
		// 1px in: once stuck, the masthead's bottom edge still touches the window's top, which counts as intersecting
		new IntersectionObserver(([entry]) => {
			body.classList.toggle('isStuck', !entry.isIntersecting);
		}, { rootMargin: '-1px 0px 0px 0px' }).observe(masthead);
	}

	// X posts: load X's script only when the page has one
	if (document.querySelector('blockquote.twitter-tweet')) {
		const script = document.createElement('script');

		script.src = 'https://platform.twitter.com/widgets.js';
		script.async = true;
		script.charset = 'utf-8';
		document.body.appendChild(script);
	}
})(document, window);
