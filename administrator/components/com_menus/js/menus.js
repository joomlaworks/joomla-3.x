/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * Menus: Manage (com_menus.menus): the page reloads when a module modal closes; exporting a menu's XML resets the task.
 * The menu item form's Modules tab (com_menus.modules): its filters, and the module form in a modal.
 * The menu item form (#item-form):
 * - choosing another menu reloads the list of parent items (task item.getParentItem);
 * - the menu type picker sends item.setType / item.setMenuType with the chosen type;
 * - when validation stops a save, the item pickers' shown names are marked invalid with their hidden values;
 * - the Login Form type's redirects keep only the field shown (a URL or a menu item), for login and logout.
 * Chosen announces its changes through jQuery only: they're listened to through jQuery when the page has it.
 */
(() => {
	'use strict';

	const Joomla = window.Joomla;

	if (!Joomla) {
		return;
	}

	/* --- Menus: Manage (script options com_menus.menus) --- */

	const menusList = () => {
		if (!Joomla.getOptions('com_menus.menus')) {
			return;
		}

		// A menu module edited in a modal: the page shows its new state once the modal is closed
		const reload = (event) => {
			if (event.target.classList && event.target.classList.contains('modal')) {
				window.setTimeout(() => window.location.reload(), 1000);
			}
		};

		if (window.jQuery) {
			window.jQuery(document).on('hidden', reload);
		} else {
			document.addEventListener('cms:modal-hidden', reload);
		}

		// Exporting a menu's XML downloads a file: the list's form must be sent without that task afterwards
		const submitform = Joomla.submitform;

		Joomla.submitform = (task, form, validate) => {
			submitform(task, form, validate);

			if (task === 'menu.exportXml') {
				document.adminForm.task.value = '';
			}
		};
	};

	/* --- The menu item form's Modules tab (script options com_menus.modules) --- */

	const modulesTab = () => {
		const options = Joomla.getOptions('com_menus.modules');

		if (!options) {
			return;
		}

		// What the module form in the modal updates here (com_modules' js/modules.js reads them from its parent)
		window.viewLevels = options.levels || {};
		window.menuId = parseInt(options.menuId, 10);

		const rows = (type, shown) => {
			document.querySelectorAll('.table tr.' + type).forEach((row) => {
				row.style.display = shown ? '' : 'none';
			});
		};

		document.addEventListener('click', (event) => {
			const target = event.target;

			if (!target || !target.closest) {
				return;
			}

			const radio = target.closest('input[type="radio"]');
			const filter = radio && /^jform_toggle_modules_(assigned|published)([01])/.exec(radio.id);

			if (filter) {
				rows(filter[1] === 'assigned' ? 'no' : 'unpublished', filter[2] === '0');
			}

			const link = target.closest('.module-edit-link');
			const modal = link ? document.getElementById('moduleEditModal') : null;
			const body = modal ? modal.querySelector('.modal-body') : null;

			if (body) {
				const frame = document.createElement('iframe');

				frame.className = 'iframe jviewport-height70';
				frame.src = options.editUrl + encodeURIComponent(link.getAttribute('data-module-id'));
				body.textContent = '';
				body.appendChild(frame);
				CMS.bootstrap.call('modal', modal);
			}
		});
	};

	/* --- The menu item form (#item-form) --- */

	const itemForm = (form) => {
		const field = (name) => form.querySelector('[name="' + name + '"]');

		const submit = Joomla.submitbutton;

		Joomla.submitbutton = (task, type) => {
			if (task === 'item.setType' || task === 'item.setMenuType') {
				if (task === 'item.setType') {
					field('jform[type]').value = type;
					document.getElementById('fieldtype').value = 'type';
				} else {
					field('jform[menutype]').value = type;
				}

				Joomla.submitform('item.setType', form);

				return;
			}

			submit(task);

			// A picker's value is hidden: its name field shows that it's missing (jform_request_id_id -> jform_request_id_name)
			form.querySelectorAll('.modal-value.invalid').forEach((value) => {
				const name = document.getElementById(value.id.replace(/_[^_]*$/, '_name'));

				if (name) {
					name.classList.add('invalid');
				}
			});
		};

		const parents = (menutype) => {
			const list = document.getElementById('jform_parent_id');

			if (!list) {
				return;
			}

			fetch('index.php?option=com_menus&task=item.getParentItem&menutype=' + encodeURIComponent(menutype), { credentials: 'same-origin' })
				.then((response) => response.json())
				.then((items) => {
					Array.prototype.slice.call(list.options).forEach((option) => {
						if (option.value !== '1') {
							option.remove();
						}
					});

					(items || []).forEach((item) => {
						list.appendChild(new Option(item.title, item.id));
					});

					list.dispatchEvent(new CustomEvent('liszt:updated'));
				})
				.catch(() => {});
		};

		const start = () => {
			const menutype = document.getElementById('jform_menutype');

			if (menutype) {
				if (window.jQuery) {
					window.jQuery(menutype).on('change', () => parents(menutype.value));
				} else {
					menutype.addEventListener('change', () => parents(menutype.value));
				}
			}
		};

		const shown = (id) => {
			const element = document.getElementById(id);
			const group = element ? element.closest('.control-group') : null;

			return !!group && window.getComputedStyle(group).display === 'block';
		};

		const clear = (id) => {
			const element = document.getElementById(id);

			if (element) {
				element.value = '';
			}
		};

		form.addEventListener('submit', () => {
			if (!document.getElementById('jform_params_login_redirect_url') || !document.getElementById('jform_params_logout_redirect_url')) {
				return;
			}

			if (shown('jform_params_login_redirect_url')) {
				clear('jform_params_login_redirect_menuitem_id');
			}

			if (shown('jform_params_login_redirect_menuitem_name')) {
				clear('jform_params_login_redirect_url');
			}

			if (shown('jform_params_logout_redirect_url')) {
				clear('jform_params_logout_redirect_menuitem_id');
			}

			if (shown('jform_params_logout_redirect_menuitem_id')) {
				clear('jform_params_logout_redirect_url');
			}
		});

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', start);
		} else {
			start();
		}
	};

	const form = document.getElementById('item-form');

	if (form) {
		itemForm(form);
	}

	menusList();
	modulesTab();
})();
