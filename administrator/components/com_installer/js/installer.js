/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

(() => {
	'use strict';

	const Joomla = window.Joomla = window.Joomla || {};
	const overlay = () => document.getElementById('loading');

	window.JoomlaInstaller = {
		getLoadingOverlay: overlay,
		showLoading() {
			if (overlay()) {
				overlay().style.display = 'block';
			}
		},
		hideLoading() {
			if (overlay()) {
				overlay().style.display = 'none';
			}
		},
	};

	const install = (type, fieldName, message, empty) => {
		const form = document.getElementById('adminForm');
		const field = fieldName ? form.elements[fieldName] : null;

		if (field && (empty || ['']).indexOf(field.value.trim()) !== -1) {
			window.alert(message);

			return;
		}

		window.JoomlaInstaller.showLoading();
		form.installtype.value = type;
		form.submit();
	};

	Joomla.submitbutton4 = () => install('url', 'install_url', Joomla.JText._('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), ['', 'http://', 'https://']);

	// The URL and folder install plugins' old names
	Joomla.submitbuttonurl = Joomla.submitbutton4;
	Joomla.submitbuttonfolder = () => install('folder', 'install_directory', Joomla.JText._('PLG_INSTALLER_FOLDERINSTALLER_NO_INSTALL_PATH'));

	document.addEventListener('click', (event) => {
		const button = event.target.closest ? event.target.closest('[data-install-type]') : null;

		if (button) {
			event.preventDefault();
			install(button.getAttribute('data-install-type'), button.getAttribute('data-install-field'), button.getAttribute('data-install-message'),
				button.getAttribute('data-install-type') === 'url' ? ['', 'http://', 'https://'] : null);
		}
	});

	const start = () => {
		const outer = document.getElementById('installer-install');
		const layer = overlay();

		if (outer && layer) {
			Object.assign(layer.style, {
				top: outer.getBoundingClientRect().top + 'px', left: '0', width: '100%', height: '100%', display: 'none', marginTop: '-10px',
			});
		}

		// Tab state opens the tab used last; otherwise the first
		setTimeout(() => {
			const tabs = document.getElementById('myTabTabs');

			if (tabs && !tabs.querySelector('li.active') && tabs.querySelector('li a')) {
				tabs.querySelector('li a').click();
			}
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
