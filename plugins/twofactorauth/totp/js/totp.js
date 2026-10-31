/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

(() => {
	'use strict';

	const draw = () => {
		if (typeof window.qrcode !== 'function') {
			return;
		}

		for (const element of document.querySelectorAll('[data-totp-qrcode]')) {
			if (!element.firstChild) {
				const qr = window.qrcode(0, 'H');

				qr.addData(element.getAttribute('data-totp-qrcode'));
				qr.make();
				element.innerHTML = qr.createImgTag(4);
			}
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', draw);
	} else {
		draw();
	}
})();
