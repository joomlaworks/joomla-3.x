/**
 * @copyright  (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The toolbar builder of the TinyMCE (legacy) options: menus and buttons are dragged from the source panel into a set's
 * bars (copied), reordered within a bar, or dragged out of it to take them away. Each item in a set's bar carries a
 * hidden input with its name. Plain JavaScript with the browser's drag and drop (it was jQuery UI's sortable).
 */
(() => {
	'use strict';

	/**
	 * Fake TinyMCE object to allow to use TinyMCE translation for the button labels
	 *
	 * @since  3.7.0
	 */
	window.tinymce = {
		langCode: 'en',
		langStrings: {},
		addI18n(code, strings) {
			this.langCode    = code;
			this.langStrings = strings || {};
		},
		translate(string) {
			return this.langStrings[string] ? this.langStrings[string] : string;
		},
	};

	class JoomlaTinyMCEBuilder {
		/**
		 * @param {HTMLElement} container
		 * @param {Object}      options
		 *
		 * @since  3.7.0
		 */
		constructor(container, options) {
			this.container = container;
			this.options   = options;
			this.dragged   = null;

			// The line where a dragged item will go (jQuery UI's placeholder class, which the layout styles)
			this.placeholder = document.createElement('div');
			this.placeholder.className = 'mce-btn ui-state-highlight';

			container.querySelectorAll('.tinymce-builder-menu.source').forEach((bar) => this.renderBar(bar, 'menu'));
			container.querySelectorAll('.tinymce-builder-toolbar.source').forEach((bar) => this.renderBar(bar, 'toolbar'));
			container.querySelectorAll('.tinymce-builder-menu.target').forEach((bar) => this.renderBar(bar, 'menu', null, true));
			container.querySelectorAll('.tinymce-builder-toolbar.target').forEach((bar) => this.renderBar(bar, 'toolbar', null, true));

			container.addEventListener('dragstart', (event) => this.onDragStart(event));
			document.addEventListener('dragover', (event) => this.onDragOver(event));
			document.addEventListener('drop', (event) => this.onDrop(event));
			container.addEventListener('dragend', () => this.onDragEnd());

			// Actions buttons
			container.addEventListener('click', (event) => {
				const button = event.target.closest('.button-action');

				if (!button) {
					return;
				}

				const action = button.getAttribute('data-action');

				if (!this[action]) {
					throw new Error('Unsupported action ' + action);
				}

				this[action]({ set: button.getAttribute('data-set'), preset: button.getAttribute('data-preset') });
			});
		}

		/**
		 * Render the toolbar/menubar
		 *
		 * @param {HTMLElement} container  The toolbar container
		 * @param {String}      type       The type toolbar or menu
		 * @param {Array|null}  value      The value
		 * @param {Boolean}     withInput  Whether append input
		 *
		 * @since  3.7.0
		 */
		renderBar(container, type, value, withInput) {
			const items = type === 'menu' ? this.options.menus : this.options.buttons;

			if (!value) {
				try {
					value = JSON.parse(container.getAttribute('data-value') || '[]');
				} catch (error) {
					value = [];
				}
			}

			[].concat(value || []).forEach((name) => {
				if (!items[name]) {
					return;
				}

				const button = this.createButton(name, items[name], type);

				container.appendChild(button);

				if (withInput) {
					this.appendInput(button, container.getAttribute('data-group'), container.getAttribute('data-set'));
				}
			});
		}

		/**
		 * Create the element needed for renderBar()
		 *
		 * @param {String} name
		 * @param {Object} info
		 * @param {String} type
		 *
		 * @return {HTMLElement}
		 *
		 * @since  3.7.0
		 */
		createButton(name, info, type) {
			const element = document.createElement('div');
			const button = document.createElement('button');

			element.className = 'mce-btn';
			element.setAttribute('data-name', name);
			element.setAttribute('data-toggle', 'tooltip');
			element.title = tinymce.translate(info.label);
			element.draggable = true;
			button.type = 'button';
			element.appendChild(button);

			if (type === 'menu') {
				button.innerHTML = '<span class="mce-txt"></span> <i class="mce-caret"></i>';
				button.firstChild.textContent = tinymce.translate(info.label);
			} else {
				element.classList.add('mce-btn-small');

				if (info.text) {
					button.textContent = tinymce.translate(info.text);
				} else {
					button.innerHTML = '<i class="mce-ico"></i>';
					button.firstChild.classList.add('mce-i-' + name);
				}
			}

			// Bootstrap's tooltip, where the administrator template has it
			if (window.jQuery && window.jQuery.fn.tooltip) {
				window.jQuery(element).tooltip({ trigger: 'hover' });
			}

			return element;
		}

		/**
		 * Append input to the button item
		 *
		 * @param {HTMLElement} element
		 * @param {String}      group
		 * @param {String}      set
		 *
		 * @since  3.7.0
		 */
		appendInput(element, group, set) {
			const input = document.createElement('input');

			input.type  = 'hidden';
			input.name  = this.options.formControl + '[' + set + '][' + group + '][]';
			input.value = element.getAttribute('data-name');
			element.appendChild(input);
		}

		// The bar a dragged item may go into: a set's bar of its type; items of a set's bar stay in their bar
		accepts(bar) {
			if (!bar || !this.dragged || !bar.classList.contains('target')) {
				return false;
			}

			return this.fromSource ? bar.classList.contains(this.type) : bar === this.origin;
		}

		onDragStart(event) {
			const item = event.target.closest ? event.target.closest('.mce-btn') : null;
			const bar = item ? item.parentNode : null;

			if (!item || !bar || !this.container.contains(bar)) {
				return;
			}

			this.dragged    = item;
			this.origin     = bar;
			this.fromSource = bar.classList.contains('source');
			this.type       = bar.classList.contains('tinymce-builder-menu') ? 'tinymce-builder-menu' : 'tinymce-builder-toolbar';
			this.dropped    = false;

			if (window.jQuery && window.jQuery.fn.tooltip) {
				window.jQuery(item).tooltip('hide');
			}

			event.dataTransfer.effectAllowed = this.fromSource ? 'copy' : 'move';
			event.dataTransfer.setData('text/plain', item.getAttribute('data-name'));

			if (this.fromSource) {
				this.container.querySelectorAll('.' + this.type + '.target').forEach((target) => target.classList.add('drop-area-highlight'));
			}
		}

		onDragOver(event) {
			if (!this.dragged) {
				return;
			}

			const bar = event.target.closest ? event.target.closest('.tinymce-builder-menu, .tinymce-builder-toolbar') : null;

			// Outside a bar that takes it, there's no place for it (an item of a set is then taken away)
			if (!this.accepts(bar)) {
				this.placeholder.remove();

				return;
			}

			event.preventDefault();
			event.dataTransfer.dropEffect = this.fromSource ? 'copy' : 'move';

			// Before the first item below the pointer's row, or right of the pointer in its row
			const before = Array.prototype.find.call(bar.children, (item) => {
				if (item === this.placeholder || item === this.dragged) {
					return false;
				}

				const box = item.getBoundingClientRect();

				return event.clientY < box.top || (event.clientY <= box.bottom && event.clientX < box.left + box.width / 2);
			});

			if (this.dragged.parentNode === bar) {
				this.dragged.style.display = 'none';
			}

			bar.insertBefore(this.placeholder, before || null);
		}

		onDrop(event) {
			if (!this.dragged || !this.placeholder.parentNode) {
				return;
			}

			const bar = this.placeholder.parentNode;

			event.preventDefault();
			this.dropped = true;

			if (this.fromSource) {
				const name = this.dragged.getAttribute('data-name');
				const menu = this.type === 'tinymce-builder-menu';
				const item = this.createButton(name, (menu ? this.options.menus : this.options.buttons)[name], menu ? 'menu' : 'toolbar');

				this.appendInput(item, bar.getAttribute('data-group'), bar.getAttribute('data-set'));
				bar.insertBefore(item, this.placeholder);
			} else {
				this.dragged.style.display = '';
				bar.insertBefore(this.dragged, this.placeholder);
			}

			this.placeholder.remove();
		}

		onDragEnd() {
			if (!this.dragged) {
				return;
			}

			if (!this.fromSource && !this.dropped) {
				if (window.jQuery && window.jQuery.fn.tooltip) {
					window.jQuery(this.dragged).tooltip('destroy');
				}

				this.dragged.remove();
			}

			this.dragged.style.display = '';
			this.placeholder.remove();
			this.container.querySelectorAll('.drop-area-highlight').forEach((bar) => bar.classList.remove('drop-area-highlight'));
			this.dragged = null;
		}

		/**
		 * Set Selected preset to specific set
		 *
		 * @param {Object} options Options {set: 1, preset: 'presetName'}
		 */
		setPreset(options) {
			const preset = this.options.toolbarPreset[options.preset] || null;

			if (!preset) {
				throw new Error('Unknown Preset "' + options.preset + '"');
			}

			Object.keys(preset).forEach((group) => {
				const type = group === 'menu' ? 'menu' : 'toolbar';
				const bar = this.container.querySelector('.tinymce-builder-' + type + '.target[data-group="' + group + '"][data-set="' + options.set + '"]');

				if (bar) {
					bar.innerHTML = '';
					this.renderBar(bar, type, preset[group], true);
				}
			});
		}

		/**
		 * Clear the pane for specific set
		 *
		 * @param {Object} options Options {set: 1}
		 */
		clearPane(options) {
			this.container.querySelectorAll('.target[data-set="' + options.set + '"]').forEach((bar) => {
				bar.innerHTML = '';
			});
		}
	}

	window.JoomlaTinyMCEBuilder = JoomlaTinyMCEBuilder;

	const start = () => {
		const container = document.getElementById('joomla-tinymce-builder');

		if (!container) {
			return;
		}

		new JoomlaTinyMCEBuilder(container, Joomla.getOptions('plg_editors_tinymce_builder', {}));

		// The sets' tabs
		document.querySelectorAll('#set-tabs a').forEach((link) => {
			link.addEventListener('click', (event) => {
				const pane = document.querySelector(link.getAttribute('href'));

				event.preventDefault();

				if (!pane) {
					return;
				}

				link.closest('ul').querySelectorAll('li').forEach((item) => item.classList.toggle('active', item.contains(link)));
				Array.prototype.forEach.call(pane.parentNode.children, (item) => item.classList.toggle('active', item === pane));
			});
		});

		// Allow to select the group only once per the set
		const selects = container.querySelectorAll('.access-select');
		const toggleAvailableOption = () => {
			selects.forEach((select) => select.querySelectorAll('option[disabled]').forEach((option) => {
				option.disabled = false;
			}));

			// Disable already selected options
			selects.forEach((select) => {
				const values = Array.prototype.filter.call(select.options, (option) => option.selected && option.value).map((option) => option.value);

				selects.forEach((other) => {
					if (other !== select) {
						Array.prototype.forEach.call(other.options, (option) => {
							if (values.indexOf(option.value) > -1) {
								option.disabled = true;
							}
						});
					}
				});
			});

			// Update Chosen
			selects.forEach((select) => {
				if (window.jQuery) {
					window.jQuery(select).trigger('liszt:updated');
				} else {
					select.dispatchEvent(new CustomEvent('liszt:updated'));
				}
			});
		};

		toggleAvailableOption();
		selects.forEach((select) => {
			if (window.jQuery) {
				window.jQuery(select).on('change', toggleAvailableOption);
			} else {
				select.addEventListener('change', toggleAvailableOption);
			}
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
