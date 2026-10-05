/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The toolbar builder of the TinyMCE editor's options: drag menus and buttons from the source panel into a set's bars,
 * reorder them, drag them out or use their remove button to take them away. Each item in a set's bar carries a hidden
 * input with its name, which is what the form saves.
 */

// TinyMCE's icons and translations register through these; the builder only needs their data, not the editor
window.tinymce = window.tinymce || {
	icons: {},
	strings: {},
	IconManager: {
		add(name, data) {
			window.tinymce.icons = Object.assign(window.tinymce.icons, data && data.icons ? data.icons : {});
		},
	},
	addI18n(code, strings) {
		window.tinymce.strings = Object.assign(window.tinymce.strings, strings || {});
	},
};

((window, document, Joomla) => {
	'use strict';

	// Toolbar button name => TinyMCE icon name
	const iconNames = {
		strikethrough: 'strike-through',
		alignleft: 'align-left',
		aligncenter: 'align-center',
		alignright: 'align-right',
		alignjustify: 'align-justify',
		lineheight: 'line-height',
		forecolor: 'text-color',
		backcolor: 'highlight-bg-color',
		bullist: 'unordered-list',
		numlist: 'ordered-list',
		blockquote: 'quote',
		pastetext: 'paste-text',
		removeformat: 'remove-formatting',
		anchor: 'bookmark',
		hr: 'horizontal-rule',
		code: 'sourcecode',
		codesample: 'code-sample',
		charmap: 'insert-character',
		nonbreaking: 'non-breaking',
		emoticons: 'emoji',
		media: 'embed',
		pagebreak: 'page-break',
		searchreplace: 'search',
		insertdatetime: 'insert-time',
		jtemplate: 'template',
	};

	const joomlaIcon = '<svg viewBox="0 0 32 32" width="20" height="20"><path d="M8.3 8.6c1-1 2.7-1 3.7 0l.3.3 3.1-3.2-.2-.2a7 7 0 0 0-6.6-1.9 4.3 4.3 0 1 0-4.9 4.8 7.3 7.3 0 0 0 1.8 7l7.1 7.2 3.2-3.2-7.1-7.1a2.6 2.6 0 0 1 0-3.7Zm23.7-4.3a4.3 4.3 0 0 0-8.5-.6 7.2 7.2 0 0 0-7 1.8l-7.1 7.1 3.1 3.2 7.2-7.1a2.6 2.6 0 0 1 3.7 3.7l-.3.3 3.2 3.1.2-.2a7 7 0 0 0 1.8-7 4.3 4.3 0 0 0 3.7-4.3Zm-3.7 19.2a7.2 7.2 0 0 0-1.9-6.7l-7.1-7.1-3.2 3.1 7.1 7.2a2.6 2.6 0 0 1-3.7 3.7l-.2-.3-3.2 3.2.3.2a7.2 7.2 0 0 0 7.1 1.8 4.3 4.3 0 1 0 4.8-5.1Zm-9.2-7-7.1 7.2a2.6 2.6 0 0 1-3.7-3.7l.2-.3-3.1-3.1-.3.2a7.2 7.2 0 0 0-1.8 6.8 4.3 4.3 0 1 0 5.2 5 7.2 7.2 0 0 0 6.7-1.8l7.1-7.1-3.2-3.2Z"/></svg>';

	const translate = (string) => window.tinymce.strings[string] || string;

	class TinyMCELatestBuilder {
		constructor(container, options) {
			this.container = container;
			this.options = options;
			this.dragged = null;
			this.fromSource = false;
			this.dropped = false;

			container.querySelectorAll('.tmb-bar').forEach((bar) => {
				this.render(bar, JSON.parse(bar.getAttribute('data-value') || '[]'));
			});

			container.addEventListener('dragstart', (event) => this.onDragStart(event));
			container.addEventListener('dragover', (event) => this.onDragOver(event));
			container.addEventListener('drop', (event) => this.onDrop(event));
			container.addEventListener('dragend', () => this.onDragEnd());
			container.addEventListener('click', (event) => this.onClick(event));
			container.addEventListener('keydown', (event) => this.onKeyDown(event));
		}

		// The bar's items: a source bar shows every item once, a set's bar the chosen ones with their inputs
		render(bar, names) {
			const items = bar.getAttribute('data-group') === 'menu' ? this.options.menus : this.options.buttons;

			names.forEach((name) => {
				if (items[name]) {
					bar.appendChild(this.createItem(name, items[name], bar));
				}
			});
		}

		createItem(name, info, bar) {
			const isMenu = bar.getAttribute('data-group') === 'menu';
			const item = document.createElement('div');
			const label = translate(info.label);

			item.className = `tmb-item${isMenu ? ' tmb-item-menu' : ''}${name === '|' ? ' tmb-item-separator' : ''}`;
			item.draggable = true;
			item.setAttribute('data-name', name);
			item.title = label;

			const content = document.createElement('span');
			const icon = window.tinymce.icons[iconNames[name] || name];

			content.className = 'tmb-item-content';

			if (name === 'jxtdbuttons') {
				content.innerHTML = joomlaIcon;
				content.appendChild(document.createTextNode(` ${label}`));
			} else if (isMenu || info.text || !icon) {
				content.textContent = info.text ? translate(info.text) : label;
			} else {
				content.innerHTML = icon;
			}

			item.appendChild(content);

			if (!bar.hasAttribute('data-source')) {
				this.addControls(item, bar);
			}

			return item;
		}

		// A set's item: its input for the form, and a button to take it away
		addControls(item, bar) {
			const input = document.createElement('input');
			const remove = document.createElement('button');

			input.type = 'hidden';
			input.name = `${this.options.formControl}[${bar.getAttribute('data-set')}][${bar.getAttribute('data-group')}][]`;
			input.value = item.getAttribute('data-name');

			remove.type = 'button';
			remove.className = 'tmb-remove';
			remove.setAttribute('data-action', 'removeItem');
			remove.setAttribute('aria-label', `${Joomla.JText._('PLG_TINYMCE_LATEST_SET_REMOVE_ITEM', 'Remove')}: ${item.title}`);
			remove.textContent = '×';

			item.appendChild(input);
			item.appendChild(remove);
			item.tabIndex = 0;
		}

		// Items only go to a set's bar of their kind: menus to the menu bar, buttons to the toolbars
		accepts(bar) {
			if (!bar || bar.hasAttribute('data-source') || !this.dragged) {
				return false;
			}

			const draggedMenu = this.dragged.classList.contains('tmb-item-menu');

			return draggedMenu === (bar.getAttribute('data-group') === 'menu');
		}

		onDragStart(event) {
			const item = event.target instanceof Element ? event.target.closest('.tmb-item') : null;

			if (!item) {
				return;
			}

			this.dragged = item;
			this.fromSource = item.parentElement.hasAttribute('data-source');
			this.dropped = false;
			event.dataTransfer.effectAllowed = this.fromSource ? 'copy' : 'move';
			event.dataTransfer.setData('text/plain', item.getAttribute('data-name'));
			item.classList.add('tmb-dragging');

			this.container.querySelectorAll('.tmb-bar:not([data-source])').forEach((bar) => {
				bar.classList.toggle('tmb-drop-target', this.accepts(bar));
			});
		}

		onDragOver(event) {
			const bar = event.target instanceof Element ? event.target.closest('.tmb-bar') : null;

			if (!this.accepts(bar)) {
				return;
			}

			event.preventDefault();
			event.dataTransfer.dropEffect = this.fromSource ? 'copy' : 'move';
		}

		onDrop(event) {
			const bar = event.target instanceof Element ? event.target.closest('.tmb-bar') : null;

			if (!this.accepts(bar)) {
				return;
			}

			event.preventDefault();

			const before = event.target.closest('.tmb-item');
			let item = this.dragged;

			if (this.fromSource) {
				const group = bar.getAttribute('data-group') === 'menu' ? this.options.menus : this.options.buttons;

				item = this.createItem(item.getAttribute('data-name'), group[item.getAttribute('data-name')], bar);
			} else if (item.parentElement !== bar) {
				// Moved to another bar of the set: its input belongs to that bar now
				const input = item.querySelector('input');

				input.name = `${this.options.formControl}[${bar.getAttribute('data-set')}][${bar.getAttribute('data-group')}][]`;
			}

			if (before && before !== item && before.parentElement === bar) {
				const box = before.getBoundingClientRect();

				bar.insertBefore(item, event.clientX > box.left + box.width / 2 ? before.nextSibling : before);
			} else if (!before) {
				bar.appendChild(item);
			}

			this.dropped = true;
		}

		onDragEnd() {
			// A set's item dropped outside the set's bars is taken away
			if (this.dragged && !this.fromSource && !this.dropped) {
				this.dragged.remove();
			}

			if (this.dragged) {
				this.dragged.classList.remove('tmb-dragging');
			}

			this.container.querySelectorAll('.tmb-drop-target').forEach((bar) => bar.classList.remove('tmb-drop-target'));
			this.dragged = null;
		}

		onClick(event) {
			const target = event.target instanceof Element ? event.target : null;
			const tab = target ? target.closest('.tmb-tab') : null;
			const action = target ? target.closest('[data-action]') : null;

			if (tab) {
				this.showSet(tab.getAttribute('data-set'));

				return;
			}

			if (!action) {
				return;
			}

			switch (action.getAttribute('data-action')) {
				case 'removeItem':
					action.closest('.tmb-item').remove();
					break;
				case 'setPreset':
					this.setPreset(action.getAttribute('data-set'), action.getAttribute('data-preset'));
					break;
				case 'clearPane':
					this.bars(action.getAttribute('data-set')).forEach((bar) => {
						bar.textContent = '';
					});
					break;
				default:
			}
		}

		// Delete or Backspace takes away the focused item of a set
		onKeyDown(event) {
			const item = event.target instanceof Element && event.target.matches('.tmb-item') ? event.target : null;

			if (item && (event.key === 'Delete' || event.key === 'Backspace') && !item.parentElement.hasAttribute('data-source')) {
				event.preventDefault();
				item.remove();
			}
		}

		bars(set) {
			return this.container.querySelectorAll(`.tmb-bar[data-set="${set}"]`);
		}

		setPreset(set, name) {
			const preset = this.options.toolbarPreset[name];

			if (!preset) {
				return;
			}

			this.bars(set).forEach((bar) => {
				bar.textContent = '';
				this.render(bar, preset[bar.getAttribute('data-group')] || []);
			});
		}

		showSet(set) {
			this.container.querySelectorAll('.tmb-tab').forEach((tab) => {
				tab.setAttribute('aria-selected', tab.getAttribute('data-set') === set ? 'true' : 'false');
			});
			this.container.querySelectorAll('.tmb-set').forEach((panel) => {
				panel.hidden = panel.id !== `tmb-set-${set}`;
			});
		}
	}

	// A user group can be assigned to one set only: its option is disabled in the other sets
	const linkAccessSelects = (container) => {
		const selects = Array.from(container.querySelectorAll('select.access-select'));

		const update = () => {
			const used = selects.map((select) => Array.from(select.selectedOptions).map((option) => option.value));

			selects.forEach((select, index) => {
				Array.from(select.options).forEach((option) => {
					option.disabled = used.some((values, other) => other !== index && values.includes(option.value));
				});
			});

			// Chosen draws these selects and only listens to jQuery's events
			if (window.jQuery) {
				window.jQuery(selects).trigger('liszt:updated');
			}
		};

		selects.forEach((select) => select.addEventListener('change', update));

		if (window.jQuery) {
			window.jQuery(selects).on('change', update);
		}

		update();
	};

	document.addEventListener('DOMContentLoaded', () => {
		const container = document.getElementById('joomla-tinymce-latest-builder');

		if (!container) {
			return;
		}

		new TinyMCELatestBuilder(container, Joomla.getOptions('plg_editors_tinymce_latest_builder', {})); // eslint-disable-line no-new
		linkAccessSelects(container);
	});
})(window, document, window.Joomla);
