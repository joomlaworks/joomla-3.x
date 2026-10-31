/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The module form (#module-form). Opened in a frame from a menu item's Modules tab (the parent has viewLevels and menuId),
 * a save also updates the module's row there: title, position, access, published state and whether it shows on that menu
 * item. The module's ID is the com_modules.edit script option.
 */
(() => {
	'use strict';

	const Joomla = window.Joomla;
	const form = document.getElementById('module-form');

	if (!Joomla || !form) {
		return;
	}

	const id = ((Joomla.getOptions && Joomla.getOptions('com_modules.edit')) || {}).id || 0;
	const value = (fieldId) => {
		const field = document.getElementById(fieldId);

		return field ? field.value : '';
	};
	const label = (element, type, text) => {
		if (element) {
			const span = document.createElement('span');

			span.className = 'label label-' + type;
			span.textContent = Joomla.JText._(text);
			element.replaceChildren(span);
		}
	};

	// The menu item's list of modules, in the parent window
	const updateParent = () => {
		const parent = window.parent;
		const doc = parent.document;
		const row = doc.getElementById('tr-' + id);
		const menus = doc.getElementById('menus-' + id);
		const status = doc.getElementById('status-' + id);
		const assignment = value('jform_assignment');
		const boxes = Array.prototype.slice.call(document.querySelectorAll('input[name="jform[assigned][]"]'));
		const toggle = (name, on) => {
			if (row) {
				row.classList.toggle(name, on);
			}
		};

		// The menu items it shows on: the chosen ones, or every other one ("on all pages except")
		parent.inMenus = [];
		parent.numMenus = boxes.length;
		boxes.forEach((box) => {
			if ((assignment > 0 && box.checked) || (assignment < 0 && !box.checked)) {
				parent.inMenus.push(parseInt(box.value, 10));
			}
		});

		if (assignment === '0') {
			label(menus, 'info', 'JALL');
			toggle('no', false);
		} else if (assignment === '-') {
			label(menus, 'important', 'JNO');
			toggle('no', true);
		} else if (parent.inMenus.indexOf(parent.menuId) >= 0) {
			label(menus, parent.numMenus === parent.inMenus.length ? 'info' : 'success', parent.numMenus === parent.inMenus.length ? 'JALL' : 'JYES');
			toggle('no', false);
		} else {
			label(menus, 'important', 'JNO');
			toggle('no', true);
		}

		const published = value('jform_published');

		if (published === '1') {
			label(status, 'success', 'JYES');
			toggle('unpublished', false);
		} else if (published === '0') {
			label(status, 'important', 'JNO');
			toggle('unpublished', true);
		} else if (published === '-2') {
			label(status, 'default', 'JTRASHED');
			toggle('unpublished', true);
		}

		const set = (cellId, text) => {
			const cell = doc.getElementById(cellId);

			if (cell) {
				cell.textContent = text;
			}
		};

		set('title-' + id, value('jform_title'));
		set('position-' + id, value('jform_position'));
		set('access-' + id, parent.viewLevels[value('jform_access')] || '');
	};

	const submit = Joomla.submitbutton;

	Joomla.submitbutton = (task) => {
		form.task.value = '';
		submit(task);

		// Not sent: validation stopped it
		if (form.task.value !== task || task === 'module.cancel') {
			return;
		}

		if (window.self !== window.top && window.parent.viewLevels) {
			updateParent();
		}

		// The parent's modal around this frame (the Menus pages' module modals)
		if (task !== 'module.apply' && window.parent !== window && window.parent.CMS && window.parent.CMS.bootstrap) {
			window.parent.CMS.bootstrap.call('modal', '#module' + (id ? 'Edit' + id : 'Add') + 'Modal', 'hide');
		}
	};
})();
