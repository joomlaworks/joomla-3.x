/**
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

document.addEventListener('DOMContentLoaded', () => {
	const byId = (id) => document.getElementById(id);
	const reference = byId('reference-association');
	const target = byId('target-association');
	const switcher = byId('jform_itemlanguage');
	const form = byId('adminForm');

	if (!reference || !target || !form) {
		return;
	}

	const code = (language) => String(language).replace(/-/, '_');
	const show = (id, shown) => {
		const element = byId(id);

		if (element) {
			element.style.display = shown ? '' : 'none';
		}
	};
	const frameDocument = (frame) => {
		try {
			return frame.contentDocument;
		} catch (error) {
			return null;
		}
	};

	// A field of a frame: its value, a change event, and Chosen's redraw
	const setField = (frame, id, value, extra) => {
		const doc = frameDocument(frame);
		const field = doc ? doc.getElementById(id) : null;
		const jq = frame.contentWindow && frame.contentWindow.jQuery;

		if (!field) {
			return null;
		}

		if (extra && extra.option && field.tagName === 'SELECT' && !Array.prototype.some.call(field.options, (option) => option.value === String(value))) {
			const option = doc.createElement('option');

			option.value = value;
			option.textContent = extra.option === true ? '' : extra.option;
			field.appendChild(option);
		}

		if (value !== undefined) {
			field.value = value;
		}

		if (extra && extra.disabled !== undefined) {
			field.disabled = extra.disabled;
		}

		if (extra && extra.change) {
			field.dispatchEvent(new Event('change', { bubbles: true }));
		}

		if (jq && field.tagName === 'SELECT') {
			jq(field).trigger('liszt:updated');
		}

		return field;
	};

	// The association of a language in a frame: the modal field (_id, _name) or the list (menus)
	const associate = (frame, language, id, title) => {
		setField(frame, 'jform_associations_' + language + '_id', id);

		if (title !== undefined) {
			setField(frame, 'jform_associations_' + language + '_name', title);
		}

		setField(frame, 'jform_associations_' + language, id, { option: id === '' ? undefined : (title === undefined ? true : title), change: true });
	};

	const languages = () => (switcher ? Array.prototype.map.call(switcher.options, (option) => option.value.split(':')) : []);
	const removeButtons = (doc, selector) => doc.querySelectorAll(selector).forEach((button) => button.remove());

	show('toolbar-target', false);
	show('toolbar-copy', false);

	// The toolbar's buttons
	Joomla.submitbutton = (task) => {
		const view = form.getAttribute('data-associatedview');

		if (task === 'association.cancel') {
			Joomla.submitform(task);
		} else if (task === 'copy') {
			// The copy takes the target's language
			Joomla.loadingLayer('show');
			setField(reference, 'jform_language', target.getAttribute('data-language'), { disabled: false });
			reference.contentWindow.Joomla.submitbutton(view + '.save2copy');
		} else if (task === 'undo-association') {
			const referenceLanguage = code(reference.getAttribute('data-language'));
			const targetLanguage = code(target.getAttribute('data-language'));

			associate(reference, targetLanguage, '', '');
			languages().forEach((parts) => {
				if (parts[0]) {
					associate(target, code(parts[0].split('|')[0]), '');
				}
			});
			associate(target, referenceLanguage, '', '');

			// The switcher's entry becomes "add" again
			const current = switcher.querySelector('option[value="' + switcher.value.replace(/["\\]/g, '\\$&') + '"]');

			if (current) {
				current.value = target.getAttribute('data-language') + ':0:add';
			}

			switcher.value = '';
			switcher.dispatchEvent(new Event('change', { bubbles: true }));

			if (window.jQuery) {
				window.jQuery(switcher).trigger('liszt:updated');
			}

			// Saving one of the items confirms it
			Joomla.submitbutton('reference');
		} else {
			// Saving the target or the reference: their language field must be enabled to be posted
			const frame = byId(task + '-association');

			setField(frame, 'jform_language', undefined, { disabled: false });
			frame.contentWindow.Joomla.submitbutton(view + '.apply');
		}

		return false;
	};

	Joomla.loadingLayer('load');

	// Hiding and showing the reference
	document.addEventListener('click', (event) => {
		const button = event.target.closest ? event.target.closest('#toogle-left-panel') : null;

		if (!button) {
			return;
		}

		const hide = button.getAttribute('data-hide-reference');

		button.textContent = button.textContent.trim() === hide ? button.getAttribute('data-show-reference') : hide;
		show('left-panel', byId('left-panel').style.display === 'none');
		byId('right-panel').classList.toggle('full-width');
	});

	// The target language chosen: its item (or a new one) in the target frame
	const chosen = () => {
		const selected = switcher.value;

		if (selected) {
			const parts = selected.split(':');

			target.setAttribute('data-action', parts[2]);
			target.setAttribute('data-id', parts[1]);
			target.setAttribute('data-language', parts[0]);
			Joomla.loadingLayer('show');
			target.src = target.getAttribute('data-editurl') + '&task=' + target.getAttribute('data-item') + '.' + parts[2] + '&id=' + parts[1];
		} else {
			show('toolbar-target', false);
			show('toolbar-copy', false);
			byId('select-change').classList.add('hidden');
			byId('remove-assoc').classList.add('hidden');
			target.setAttribute('data-action', '');
			target.setAttribute('data-id', '0');
			target.setAttribute('data-language', '');
			target.src = '';
		}
	};

	// Chosen sends its changes through jQuery only
	if (switcher) {
		if (window.jQuery) {
			window.jQuery(switcher).on('change', chosen);
		} else {
			switcher.addEventListener('change', chosen);
		}
	}

	reference.addEventListener('load', () => {
		const doc = frameDocument(reference);
		let targetUrl = Joomla.getOptions('targetSrc', false);

		// The target loads after the reference (both check items out in the same session)
		if (targetUrl) {
			target.setAttribute('src', targetUrl.split('&amp;').join('&'));
			Joomla.loadOptions({ targetSrc: false });

			return;
		}

		target.setAttribute('src', target.getAttribute('src'));

		if (!doc) {
			return;
		}

		// After a copy the reference shows the copy: the copy goes to the target, the reference back to itself
		const loadedId = doc.getElementById('jform_id') ? doc.getElementById('jform_id').value : '';

		if (loadedId !== reference.getAttribute('data-id')) {
			target.src = target.getAttribute('data-editurl') + '&task=' + target.getAttribute('data-item') + '.edit&id=' + loadedId;
			reference.src = reference.getAttribute('data-editurl') + '&task=' + reference.getAttribute('data-item') + '.edit&id=' + reference.getAttribute('data-id');
		}

		setField(reference, 'jform_language', undefined, { disabled: true });
		removeButtons(doc, '#associations .btn');

		languages().forEach((parts) => {
			const language = parts[0] ? code(parts[0]) : '';
			const id = language ? doc.getElementById('jform_associations_' + language + '_id') : null;

			if (id && id.value === '') {
				setField(reference, 'jform_associations_' + language + '_name', reference.getAttribute('data-no-assoc'));
			}
		});

		Joomla.loadingLayer('hide');
	});

	target.addEventListener('load', () => {
		const doc = frameDocument(target);

		// A blank frame (no language chosen)
		if (!target.getAttribute('src') || !doc) {
			return;
		}

		const targetLanguage = target.getAttribute('data-language');
		const targetId = target.getAttribute('data-id');
		const loadedId = (doc.getElementById('jform_id') || {}).value || '0';
		const tab = doc.querySelector('a[href="#associations"]');

		show('toolbar-target', true);
		show('toolbar-copy', true);
		byId('select-change').classList.remove('hidden');

		if (tab) {
			removeButtons(tab.parentNode, '.btn');
		}

		removeButtons(doc, '#associations .btn');

		// The first tab, not Associations
		const pane = doc.getElementById('associations');

		if (pane && pane.classList.contains('active')) {
			tab.parentNode.classList.remove('active');
			pane.classList.remove('active');

			const firstTab = doc.querySelector('.nav-tabs li');
			const firstPane = doc.querySelector('.tab-content .tab-pane');

			if (firstTab) {
				firstTab.classList.add('active');
			}

			if (firstPane) {
				firstPane.classList.add('active');
			}
		}

		setField(target, 'jform_language', targetLanguage, { change: true, disabled: true });

		if (loadedId === '0') {
			byId('select-change-text').innerHTML = byId('select-change').getAttribute('data-select');
		} else {
			byId('select-change-text').innerHTML = byId('select-change').getAttribute('data-change');
			byId('remove-assoc').classList.remove('hidden');
			show('toolbar-copy', false);

			// Checked in when the editor closes
			const ids = byId('target-id');

			ids.value = ids.value === '' ? loadedId : ids.value + ',' + loadedId;

			// Saved for the first time: the switcher's entry and the frame know its id now
			if (loadedId !== targetId) {
				const option = switcher.querySelector('option[value^="' + targetLanguage + ':' + targetId + ':add"]');

				if (option) {
					option.value = targetLanguage + ':' + loadedId + ':edit';
				}

				target.setAttribute('data-id', loadedId);
				target.setAttribute('data-action', 'edit');
			}

			// The reference's associations tab
			const title = (doc.getElementById('jform_' + reference.getAttribute('data-title')) || {}).value || '';

			associate(reference, code(targetLanguage), loadedId, title);
		}

		// The target's associations tab: the reference and the languages already associated
		associate(target, code(reference.getAttribute('data-language')), reference.getAttribute('data-id'), reference.getAttribute('data-title-value'));

		languages().forEach((parts) => {
			if (parts[1] !== undefined && parts[1] !== '0') {
				associate(target, code(parts[0]), parts[1]);
			}
		});

		Joomla.loadingLayer('hide');
	});
});

/*
 * The list of items to associate (layout modal, in a frame): a chosen item goes to the function of the page that opened the
 * list, named by the form's data-function.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var link = event.target.closest ? event.target.closest('.select-link') : null;
		var form = document.getElementById('adminForm');
		var name = form ? form.getAttribute('data-function') : '';

		if (link && name && window.self !== window.top && typeof window.parent[name] === 'function') {
			window.parent[name](link.getAttribute('data-id'));
		}
	});
})();
