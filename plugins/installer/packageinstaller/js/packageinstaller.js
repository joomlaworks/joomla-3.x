/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * "Installer - Install from Upload": a package dropped on the area (or chosen with its button) is uploaded with progress to
 * com_installer's install.ajax_upload, with the form token; the page then shows the result. Joomla.submitbuttonpackage()
 * sends the form with the chosen file the classic way.
 */
(() => {
	'use strict';

	const Joomla = window.Joomla = window.Joomla || {};
	const text = (key) => Joomla.JText._(key);

	Joomla.submitbuttonpackage = () => {
		const form = document.getElementById('adminForm');

		if (form.install_package.value === '') {
			window.alert(text('PLG_INSTALLER_PACKAGEINSTALLER_NO_PACKAGE'));
		} else if (form.install_package.files[0].size > Number(form.max_upload_size.value)) {
			window.alert(text('COM_INSTALLER_MSG_WARNINGS_UPLOADFILETOOBIG'));
		} else {
			window.JoomlaInstaller.showLoading();
			form.installtype.value = 'upload';
			form.submit();
		}
	};

	const start = () => {
		const zone = document.getElementById('dragarea');

		if (!zone) {
			return;
		}

		const input = document.getElementById('install_package');
		const maxSize = Number(document.getElementById('max_upload_size').value);
		const returnUrl = document.getElementById('installer-return').value;
		const bar = zone.querySelector('.upload-progress .bar');
		const percentage = zone.querySelector('.uploading-number');
		let url = 'index.php?option=com_installer&task=install.ajax_upload';
		let uploading = false;

		if (returnUrl) {
			url += '&return=' + returnUrl;
		}

		document.getElementById('select-file-button').addEventListener('click', () => input.click());

		input.addEventListener('change', () => {
			if (!uploading) {
				Joomla.submitbuttonpackage();
			}
		});

		['dragenter', 'dragover'].forEach((type) => zone.addEventListener(type, (event) => {
			event.preventDefault();
			zone.classList.add('hover');
		}));

		zone.addEventListener('dragleave', (event) => {
			event.preventDefault();
			zone.classList.remove('hover');
		});

		const showError = (result) => {
			let message = text('PLG_INSTALLER_PACKAGEINSTALLER_UPLOAD_ERROR_UNKNOWN');

			zone.setAttribute('data-state', 'pending');

			if (result == null || result === '') {
				message = text('PLG_INSTALLER_PACKAGEINSTALLER_UPLOAD_ERROR_EMPTY');
			} else if (typeof result === 'string') {
				message = result.replace(/(<([^>]+)>|\s+)/g, ' ');
			} else if (result.message) {
				message = result.message;
			}

			Joomla.renderMessages({ error: [message] });
		};

		const progress = (number) => {
			bar.style.width = number + '%';
			bar.setAttribute('aria-valuenow', number);
			percentage.textContent = number;
		};

		zone.addEventListener('drop', (event) => {
			event.preventDefault();
			zone.classList.remove('hover');

			const files = event.dataTransfer ? event.dataTransfer.files : null;

			if (uploading || !files || !files.length) {
				return;
			}

			if (files[0].size > maxSize) {
				window.alert(text('COM_INSTALLER_MSG_WARNINGS_UPLOADFILETOOBIG'));

				return;
			}

			const data = new FormData();

			data.append('install_package', files[0]);
			data.append('installtype', 'upload');

			zone.setAttribute('data-state', 'uploading');
			uploading = true;
			progress(0);

			// XMLHttpRequest for the upload's progress (fetch() reports none)
			const xhr = new XMLHttpRequest();

			xhr.open('POST', url);
			xhr.setRequestHeader('X-CSRF-Token', Joomla.getOptions('csrf.token') || '');
			xhr.upload.addEventListener('progress', (progressEvent) => {
				if (progressEvent.lengthComputable) {
					const number = Math.round(progressEvent.loaded / progressEvent.total * 100);

					progress(number);

					if (number === 100) {
						zone.setAttribute('data-state', 'installing');
					}
				}
			});
			xhr.addEventListener('load', () => {
				let result;

				try {
					result = JSON.parse(xhr.responseText);
				} catch (error) {
					uploading = false;
					showError(xhr.status === 200 ? xhr.responseText : xhr.statusText);

					return;
				}

				if (xhr.status !== 200) {
					uploading = false;
					showError(xhr.statusText);

					return;
				}

				// An extension's fatal error
				if (!result || (!result.success && !result.data)) {
					uploading = false;
					showError(result);

					return;
				}

				// Always through a page that shows the session's messages
				window.location.href = result.data.redirect || 'index.php?option=com_installer&view=install';
			});
			xhr.addEventListener('error', () => {
				uploading = false;
				showError(xhr.statusText);
			});
			xhr.send(data);
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
