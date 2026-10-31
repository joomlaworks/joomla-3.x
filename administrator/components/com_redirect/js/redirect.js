/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * Redirects: Errors' "Create Redirect" button opens the form of the page's #redirect-create-form template in CMS.modal,
 * filled with the error's address, and posts it to errors.createRedirect. Needs global.js.
 */
(() => {
	'use strict';

	const text = (key) => window.CMS.text._(key);

	const open = (button) => {
		const template = document.getElementById('redirect-create-form');

		if (!template) {
			return;
		}

		const root = document.createElement('div');
		root.className = 'redirect-create';
		root.appendChild(template.content.cloneNode(true));

		const form = root.querySelector('form');
		const internal = root.querySelector('.redirect-create-internal');
		const external = root.querySelector('.redirect-create-external');

		form.querySelector('.redirect-create-source').value = button.getAttribute('data-url');
		form.elements.error_id.value = button.getAttribute('data-id');

		form.addEventListener('change', (event) => {
			if (event.target.name !== 'target_type') {
				return;
			}

			const isExternal = event.target.value === 'external';

			internal.hidden = isExternal;
			external.hidden = !isExternal;
			(isExternal ? form.elements.new_url_external : form.elements.new_url).focus();
		});

		const save = () => {
			const isExternal = form.querySelector('input[name="target_type"]:checked').value === 'external';
			const field = isExternal ? form.elements.new_url_external : form.elements.new_url;

			if (field.value.trim() === '') {
				field.focus();

				return;
			}

			form.submit();
		};

		form.addEventListener('submit', (event) => {
			event.preventDefault();
			save();
		});

		window.CMS.modal.open(root, {
			handler: 'adopt',
			title: text('COM_REDIRECT_CREATE_REDIRECT'),
			size: { x: Math.min(820, window.innerWidth - 40), y: Math.min(320, window.innerHeight - 80) },
			buttons: [
				{ text: text('JCANCEL'), className: 'btn', onClick: () => window.CMS.modal.close() },
				{ text: text('JAPPLY'), className: 'btn btn-primary', onClick: save },
			],
		});

		form.elements.new_url.focus();
	};

	document.addEventListener('click', (event) => {
		const button = event.target.closest('.redirect-create');

		if (button) {
			event.preventDefault();
			open(button);
		}
	});
})();
