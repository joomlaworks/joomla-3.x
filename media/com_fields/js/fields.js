/**
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

Joomla = window.Joomla || {};

(function(Joomla) {
	Joomla.fieldIns = function(id, editor) {
		/** Use the API, if editor supports it **/
		if (window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances && window.parent.Joomla.editors.instances.hasOwnProperty(editor)) {
			window.parent.Joomla.editors.instances[editor].replaceSelection("{field " + id + "}")
		} else {
			window.parent.jInsertEditorText("{field " + id + "}", editor);
		}

		window.parent.jModalClose();
	};

	Joomla.fieldgroupIns = function(id, editor) {
		/** Use the API, if editor supports it **/
		if (window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances && window.parent.Joomla.editors.instances.hasOwnProperty(editor)) {
			window.parent.Joomla.editors.instances[editor].replaceSelection("{fieldgroup " + id + "}")
		} else {
			window.parent.jInsertEditorText("{fieldgroup " + id + "}", editor);
		}

		window.parent.jModalClose();
	};
})(Joomla);

/*
 * Forms with custom fields, from script options:
 * - com_fields.reload {field id: {task, unless}}: changing the field (the category, whose fields the form shows; a field's
 *   type) sends the form with that task to load it again, unless the new value is "unless";
 * - com_fields.context (a field id): choosing a context opens the list of its fields;
 * - com_fields.label {title, label}: a field's label follows its title while they're the same.
 * Changes Chosen makes reach jQuery's handlers only, so they're listened to through jQuery when the page has it.
 */
(() => {
	'use strict';

	const options = (name) => (window.Joomla && Joomla.getOptions ? Joomla.getOptions(name) : null);

	const onChange = (field, handler) => {
		if (window.jQuery) {
			window.jQuery(field).on('change', () => handler(field));
		} else {
			field.addEventListener('change', () => handler(field));
		}
	};

	const start = () => {
		const reload = options('com_fields.reload') || {};

		Object.keys(reload).forEach((id) => {
			const field = document.getElementById(id);
			const settings = reload[id] || {};

			if (!field || !field.form) {
				return;
			}

			onChange(field, () => {
				if (settings.unless !== undefined && field.value === String(settings.unless)) {
					return;
				}

				if (Joomla.loadingLayer) {
					Joomla.loadingLayer('show');
				}

				Joomla.submitform(settings.task, field.form);
			});
		});

		const context = document.getElementById(options('com_fields.context') || '');

		if (context) {
			onChange(context, () => {
				const url = new URL(window.location.href);

				if (context.value) {
					url.searchParams.set('context', context.value);
				} else {
					url.searchParams.delete('context');
				}

				window.location.href = url.toString();
			});
		}

		const label = options('com_fields.label');
		const title = label ? document.getElementById(label.title) : null;
		const target = label ? document.getElementById(label.label) : null;

		if (title && target) {
			let previous = title.value;

			title.addEventListener('change', () => {
				if (target.value === previous) {
					target.value = title.value;
				}

				previous = title.value;
			});
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
