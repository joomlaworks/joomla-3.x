/**
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The site's slim version of global.js: the namespace window.CMS with only what pages for visitors use (options, strings,
 * forms, form validation, lists, messages, AJAX, keepalive, captions, highlighting) and the old Joomla.* names. JHtml behavior.core loads it
 * on the site; anything needing more (modals, tooltips, front-end editing, the media field) loads global.js, which has
 * all of this too and takes this file's place in the page. A second copy of the same code, kept by hand: change both
 * files together.
 */
((window, document) => {
	'use strict';

	if (window.CMS && (window.CMS.global || window.CMS.frontend)) {
		return;
	}

	// Scripts may have started the old namespace before this file: their editors and options stay
	const Joomla = window.Joomla = window.Joomla || {};

	const CMS = window.CMS = window.CMS || {};

	CMS.frontend = true;

	/* --- Helpers --- */

	CMS.ready = (callback) => {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback);
		} else {
			callback();
		}
	};

	// Own enumerable properties, as Joomla.extend() copied them
	CMS.extend = (destination, source) => Object.assign(destination, source);

	const byId = (element) => (typeof element === 'string' ? document.getElementById(element) : element);

	const list = (items) => {
		if (!items) {
			return [];
		}

		if (typeof items === 'string') {
			return Array.prototype.slice.call(document.querySelectorAll(items));
		}

		return typeof items.length === 'number' && !items.nodeType ? Array.prototype.slice.call(items) : [items];
	};

	// Options written by PHP as JavaScript (JHtml::getJSObject()): plain objects, also nested ones
	const merge = (target, source) => {
		Object.keys(source || {}).forEach((key) => {
			const value = source[key];

			if (value && typeof value === 'object' && !Array.isArray(value) && !value.nodeType
				&& target[key] && typeof target[key] === 'object') {
				target[key] = merge(Object.assign({}, target[key]), value);
			} else if (value !== undefined && value !== null) {
				target[key] = value;
			}
		});

		return target;
	};

	/* --- Options (Joomla.getOptions()) --- */

	CMS.options = {
		storage: Joomla.optionsStorage || null,

		get(key, def) {
			if (!CMS.options.storage) {
				CMS.options.load();
			}

			return CMS.options.storage[key] !== undefined ? CMS.options.storage[key] : def;
		},

		// From the page's script options elements, or the object given
		load(options) {
			if (!options) {
				let counter = 0;

				for (const element of document.querySelectorAll('.joomla-script-options.new')) {
					const option = JSON.parse(element.text || element.textContent);

					if (option) {
						CMS.options.load(option);
						counter += 1;
					}

					element.className = element.className.replace(' new', ' loaded');
				}

				if (counter) {
					return;
				}
			}

			if (!CMS.options.storage) {
				CMS.options.storage = options || {};
			} else if (options) {
				Object.keys(options).forEach((key) => {
					CMS.options.storage[key] = options[key];
				});
			}
		},
	};

	/* --- Language strings (Joomla.Text, JText::script()) --- */

	CMS.text = {
		strings: {},

		get(key, def) {
			const strings = CMS.options.get('joomla.jtext');

			if (strings) {
				CMS.text.load(strings);
				CMS.options.load({ 'joomla.jtext': null });
			}

			key = String(key).toUpperCase();

			return CMS.text.strings[key] !== undefined ? CMS.text.strings[key] : (def === undefined ? '' : def);
		},

		load(strings) {
			Object.keys(strings || {}).forEach((key) => {
				CMS.text.strings[key.toUpperCase()] = strings[key];
			});

			return CMS.text;
		},
	};

	CMS.text._ = CMS.text.get;

	/* --- Editors: each editor registers its instances (getValue, setValue, getSelection, replaceSelection) --- */

	CMS.editors = Joomla.editors = Joomla.editors || {};
	CMS.editors.instances = CMS.editors.instances || {};

	/* --- Forms --- */

	CMS.form = {
		// Joomla.submitform(): through a submit button, so the form's submit listeners (editors, CAPTCHAs) run
		submit(task, form, validate) {
			form = form || document.getElementById('adminForm');

			if (task) {
				form.task.value = task;
			}

			form.noValidate = !validate;

			if (!validate) {
				form.setAttribute('novalidate', '');
			} else if (form.hasAttribute('novalidate')) {
				form.removeAttribute('novalidate');
			}

			const button = document.createElement('input');
			button.style.display = 'none';
			button.type = 'submit';
			form.appendChild(button).click();
			form.removeChild(button);
		},

		// Joomla.submitbutton(), which pages replace with their own (validation first)
		button(task) {
			Joomla.submitform(task);
		},

		// Every form token on the page (the installer)
		replaceTokens(token) {
			if (!/^[0-9A-F]{32}$/i.test(token)) {
				return;
			}

			for (const input of document.getElementsByTagName('input')) {
				if (input.type === 'hidden' && input.value === '1' && input.name.length === 32) {
					input.name = token;
				}
			}
		},
	};

	/* --- Lists: the checkboxes cb0, cb1... and boxchecked of the managers' forms --- */

	CMS.list = {
		checkAll(checkbox, stub) {
			if (!checkbox.form) {
				return false;
			}

			stub = stub || 'cb';

			let count = 0;

			for (const element of checkbox.form.elements) {
				if (element.type === checkbox.type && element.id.indexOf(stub) === 0) {
					element.checked = checkbox.checked;
					count += element.checked ? 1 : 0;
				}
			}

			if (checkbox.form.boxchecked) {
				checkbox.form.boxchecked.value = count;
			}

			return true;
		},

		isChecked(checked, form) {
			form = form || document.getElementById('adminForm');
			form.boxchecked.value = parseInt(form.boxchecked.value, 10) + (checked ? 1 : -1);

			if (!form.elements['checkall-toggle']) {
				return;
			}

			let all = true;

			for (const element of form.elements) {
				if (element.type === 'checkbox' && element.name !== 'checkall-toggle' && !element.checked) {
					all = false;
					break;
				}
			}

			form.elements['checkall-toggle'].checked = all;
		},

		// One row's task: only its checkbox checked
		itemTask(id, task) {
			const form = document.adminForm;
			const checkbox = form[id];

			if (!checkbox) {
				return false;
			}

			for (let i = 0; form['cb' + i]; i += 1) {
				form['cb' + i].checked = false;
			}

			checkbox.checked = true;
			form.boxchecked.value = 1;
			Joomla.submitform(task);

			return false;
		},

		order(order, dir, task, form) {
			form = form || document.getElementById('adminForm');
			form.filter_order.value = order;
			form.filter_order_Dir.value = dir;
			Joomla.submitform(task, form);
		},
	};

	/* --- System messages (#system-message-container) --- */

	CMS.messages = {
		// The messages are HTML: never pass what a visitor typed
		render(messages) {
			CMS.messages.remove();

			const container = document.getElementById('system-message-container');

			Object.keys(messages).forEach((type) => {
				const box = document.createElement('div');
				const close = document.createElement('button');
				let alertClass = type === 'notice' ? 'alert-info' : 'alert-' + type;

				alertClass = type === 'message' ? 'alert-success' : alertClass;
				alertClass = type === 'error' ? 'alert-error alert-danger' : alertClass;
				box.className = 'alert ' + alertClass;

				close.setAttribute('type', 'button');
				close.setAttribute('data-dismiss', 'alert');
				close.className = 'close';
				close.innerHTML = '×';
				box.appendChild(close);

				const title = CMS.text.get(type);

				if (typeof title !== 'undefined') {
					const heading = document.createElement('h4');
					heading.className = 'alert-heading';
					heading.innerHTML = title;
					box.appendChild(heading);
				}

				for (let i = messages[type].length - 1; i >= 0; i -= 1) {
					const message = document.createElement('div');
					message.innerHTML = messages[type][i];
					box.appendChild(message);
				}

				container.appendChild(box);
			});
		},

		remove() {
			const container = document.getElementById('system-message-container');

			while (container.firstChild) {
				container.removeChild(container.firstChild);
			}

			// Chrome kept the old height otherwise
			container.style.display = 'none';
			container.offsetHeight; // eslint-disable-line no-unused-expressions
			container.style.display = '';
		},

		// The message for a failed AJAX request (jQuery's or a plain XMLHttpRequest)
		fromAjaxError(xhr, textStatus) {
			const other = (detail) => CMS.text.get('JLIB_JS_AJAX_ERROR_OTHER').replace('%s', xhr.status) + (detail ? ' <em>' + detail + '</em>' : '');
			let error;

			if (textStatus === 'parsererror') {
				let encoded = '';

				for (const character of xhr.responseText.trim()) {
					encoded += '&#' + character.charCodeAt(0) + ';';
				}

				error = CMS.text.get('JLIB_JS_AJAX_ERROR_PARSE').replace('%s', encoded);
			} else if (textStatus === 'nocontent') {
				error = CMS.text.get('JLIB_JS_AJAX_ERROR_NO_CONTENT');
			} else if (textStatus === 'timeout') {
				error = CMS.text.get('JLIB_JS_AJAX_ERROR_TIMEOUT');
			} else if (textStatus === 'abort') {
				error = CMS.text.get('JLIB_JS_AJAX_ERROR_CONNECTION_ABORT');
			} else if (xhr.responseJSON && xhr.responseJSON.message) {
				error = other(xhr.responseJSON.message);
			} else {
				error = other(xhr.statusText);
			}

			return { error: [error] };
		},
	};

	/* --- Windows and the loading layer --- */

	CMS.window = {
		// A window in the middle of the screen (the Help button)
		open(url, name, width, height, scroll) {
			const features = 'height=' + height + ',width=' + width + ',top=' + (screen.height - height) / 2
				+ ',left=' + (screen.width - width) / 2 + ',scrollbars=' + scroll + ',resizable';
			const opened = window.open(url, name, features);

			if (opened) {
				opened.focus();
			}

			return opened;
		},
	};

	CMS.loading = {
		layer(task, parent) {
			task = task || 'show';
			parent = parent || document.body;

			if (task === 'load') {
				const root = (CMS.options.get('system.paths') || {}).root || '';
				const layer = document.createElement('div');

				layer.id = 'loading-logo';
				layer.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;opacity:0.8;overflow:hidden;z-index:10000;'
					+ 'display:none;background:#fff url("' + root + '/media/system/images/ajax-loader.gif") center no-repeat fixed;';
				parent.appendChild(layer);
			} else {
				if (!document.getElementById('loading-logo')) {
					CMS.loading.layer('load', parent);
				}

				document.getElementById('loading-logo').style.display = task === 'show' ? 'block' : 'none';
			}

			return document.getElementById('loading-logo');
		},

		show(parent) {
			return CMS.loading.layer('show', parent);
		},

		hide(parent) {
			return CMS.loading.layer('hide', parent);
		},
	};

	/* --- AJAX (Joomla.request()) --- */

	CMS.request = (options) => {
		options = Object.assign({ url: '', method: 'GET', data: null, perform: true }, options);
		options.method = options.data ? 'POST' : options.method.toUpperCase();

		let xhr;

		try {
			xhr = new XMLHttpRequest();
			xhr.open(options.method, options.url, true);
			xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			xhr.setRequestHeader('X-Ajax-Engine', 'Joomla!');

			if (options.method === 'POST') {
				const token = CMS.options.get('csrf.token', '');

				if (token) {
					xhr.setRequestHeader('X-CSRF-Token', token);
				}

				if (typeof options.data === 'string' && (!options.headers || !options.headers['Content-Type'])) {
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				}
			}

			Object.keys(options.headers || {}).forEach((name) => xhr.setRequestHeader(name, options.headers[name]));

			xhr.onreadystatechange = () => {
				if (xhr.readyState !== 4) {
					return;
				}

				if (xhr.status === 200) {
					if (options.onSuccess) {
						options.onSuccess.call(window, xhr.responseText, xhr);
					}
				} else if (options.onError) {
					options.onError.call(window, xhr);
				}
			};

			if (options.perform) {
				if (options.onBefore && options.onBefore.call(window, xhr) === false) {
					return xhr;
				}

				xhr.send(options.data);
			}
		} catch (error) {
			window.console.log(error);

			return false;
		}

		return xhr;
	};

	/* --- Captions from the title of images (behavior.caption, deprecated: use <figure>) --- */

	CMS.caption = {
		// Wraps each image in a div of its width and float, with its title as a paragraph under it
		attach(selector) {
			const className = selector.replace('.', '_');

			for (const image of document.querySelectorAll(selector)) {
				const caption = image.getAttribute('title') || '';
				const align = image.getAttribute('align') || window.getComputedStyle(image).cssFloat || 'none';
				const container = document.createElement('div');

				container.className = className + ' ' + align;
				container.style.cssFloat = align;
				container.style.width = (image.getAttribute('width') || image.width) + 'px';
				image.parentNode.insertBefore(container, image);
				container.appendChild(image);

				if (caption !== '') {
					const text = document.createElement('p');

					text.className = className;
					text.textContent = caption;
					container.appendChild(text);
				}
			}
		},
	};

	/* --- Highlighting search words in the content (behavior.highlighter) --- */

	const escapeRegExp = (text) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

	CMS.highlighter = {
		/**
		 * @param  {Object}  options  startElement, endElement (the content is between them), elements, className, tag, onlyWords,
		 *                            caseSensitive, autoUnhighlight
		 *
		 * @return {Object}  highlight(words), unhighlight(words)
		 */
		create(options) {
			options = Object.assign({
				autoUnhighlight: true,
				caseSensitive: false,
				startElement: false,
				endElement: false,
				elements: [],
				className: 'highlight',
				onlyWords: true,
				tag: 'span',
			}, options || {});

			const found = {};
			const start = byId(options.startElement);
			const end = byId(options.endElement);

			options.elements = list(options.elements);

			for (let next = start ? start.nextElementSibling : null; next && next !== end; next = next.nextElementSibling) {
				options.elements.push(next);
			}

			const key = (word) => (options.caseSensitive ? word : word.toUpperCase());

			const recurse = (node, regex) => {
				if (node.nodeType === 3) {
					const match = node.nodeValue.match(regex);

					if (match) {
						const mark = document.createElement(options.tag);
						const word = node.splitText(match.index);

						word.splitText(match[0].length);
						mark.className = options.className;
						mark.appendChild(word.cloneNode(true));
						word.parentNode.replaceChild(mark, word);
						mark.setAttribute('rel', mark.textContent);
						(found[key(mark.textContent)] = found[key(mark.textContent)] || []).push(mark);

						return 1;
					}
				} else if (node.nodeType === 1 && node.childNodes && !/^(script|style|textarea|iframe)$/i.test(node.tagName)
					&& !(node.tagName === options.tag.toUpperCase() && node.className === options.className)) {
					for (let i = 0; i < node.childNodes.length; i += 1) {
						i += recurse(node.childNodes[i], regex);
					}
				}

				return 0;
			};

			const highlighter = {
				highlight(words) {
					words = [].concat(words).filter((word) => word !== '');

					if (!words.length) {
						return highlighter;
					}

					if (options.autoUnhighlight) {
						highlighter.unhighlight(words);
					}

					const pattern = words.map(escapeRegExp).join('|');
					const regex = new RegExp(options.onlyWords ? '\\b(' + pattern + ')\\b' : '(' + pattern + ')', options.caseSensitive ? '' : 'i');

					options.elements.forEach((element) => recurse(element, regex));

					return highlighter;
				},

				unhighlight(words) {
					[].concat(words).forEach((word) => {
						(found[key(word)] || []).forEach((mark) => {
							if (mark.parentNode) {
								mark.parentNode.replaceChild(document.createTextNode(mark.textContent), mark);
							}
						});
						delete found[key(word)];
					});

					return highlighter;
				},
			};

			return highlighter;
		},
	};

	/* --- The colour field (layout joomla.form.field.color: the browser's colour picker, the value as text, a Clear button) --- */

	CMS.colorField = {
		/**
		 * The colour as #rrggbb, for the picker: #rgb and #rrggbb, rgb() and rgba() (without their opacity); null for
		 * anything else (empty, keywords such as transparent)
		 *
		 * @param  {string}  value  The field's value
		 *
		 * @return  {string|null}
		 */
		toHex(value) {
			value = String(value || '').trim().toLowerCase();

			let match = value.match(/^#([0-9a-f])([0-9a-f])([0-9a-f])$/);

			if (match) {
				return '#' + match[1] + match[1] + match[2] + match[2] + match[3] + match[3];
			}

			if (/^#[0-9a-f]{6}$/.test(value)) {
				return value;
			}

			match = value.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*[\d.]+\s*)?\)$/);

			if (match && match.slice(1, 4).every((part) => part <= 255)) {
				return '#' + match.slice(1, 4).map((part) => Number(part).toString(16).padStart(2, '0')).join('');
			}

			return null;
		},

		/**
		 * The picker's colour written in the field's format (data-format: hex, rgb or rgba, whose opacity is kept)
		 *
		 * @param  {string}            hex    #rrggbb
		 * @param  {HTMLInputElement}  input  The field
		 *
		 * @return  {string}
		 */
		fromHex(hex, input) {
			const format = input.getAttribute('data-validate') === 'color' ? 'hex' : input.getAttribute('data-format') || 'hex';
			const rgb = [1, 3, 5].map((start) => parseInt(hex.substr(start, 2), 16)).join(', ');

			if (format === 'rgb') {
				return 'rgb(' + rgb + ')';
			}

			if (format === 'rgba') {
				const alpha = input.value.match(/^rgba\(.*,\s*([\d.]+)\s*\)$/);

				return 'rgba(' + rgb + ', ' + (alpha ? alpha[1] : '1') + ')';
			}

			return hex;
		},

		/**
		 * Shows the field's value in the picker (crossed out when it isn't a colour the picker can show)
		 *
		 * @param  {HTMLElement}  wrapper  .cms-color
		 */
		update(wrapper) {
			const input = wrapper.querySelector('.cms-color-value');
			const swatch = wrapper.querySelector('.cms-color-swatch');
			const hex = input ? CMS.colorField.toHex(input.value) : null;

			if (swatch && hex) {
				swatch.value = hex;
			}

			wrapper.classList.toggle('is-empty', !hex);
		},

		/**
		 * Gives a text field the picker and the Clear button (for fields an extension writes itself; the layout writes
		 * them already)
		 *
		 * @param  {HTMLInputElement}  input
		 */
		attach(input) {
			if (!input || input.closest('.cms-color') || input.type === 'hidden') {
				return;
			}

			const wrapper = document.createElement('span');
			const swatch = document.createElement('input');

			wrapper.className = 'cms-color';
			swatch.type = 'color';
			swatch.className = 'cms-color-swatch';
			swatch.disabled = input.disabled || input.readOnly;
			swatch.setAttribute('aria-label', CMS.text._('JLIB_FORM_FIELD_COLOR_CHOOSE', 'Choose a colour'));
			input.classList.add('cms-color-value');
			input.parentNode.insertBefore(wrapper, input);
			wrapper.appendChild(swatch);
			wrapper.appendChild(input);

			if (!swatch.disabled) {
				const clear = document.createElement('button');

				clear.type = 'button';
				clear.className = 'btn cms-color-clear';
				clear.textContent = '×';
				clear.title = CMS.text._('JCLEAR', 'Clear');
				clear.setAttribute('aria-label', clear.title);
				wrapper.appendChild(clear);
			}

			CMS.colorField.update(wrapper);
		},
	};

	// One set of listeners for every colour field, also those subforms add later
	if (!CMS.colorField.listening) {
		CMS.colorField.listening = true;

		const fieldOf = (element) => {
			const wrapper = element && element.closest ? element.closest('.cms-color') : null;

			return wrapper ? { wrapper, input: wrapper.querySelector('.cms-color-value') } : null;
		};

		document.addEventListener('input', (event) => {
			const field = fieldOf(event.target);

			if (!field || !field.input) {
				return;
			}

			if (event.target.classList.contains('cms-color-swatch')) {
				field.input.value = CMS.colorField.fromHex(event.target.value, field.input);
				field.wrapper.classList.remove('is-empty');
				field.input.dispatchEvent(new Event('input', { bubbles: true }));
			} else if (event.target === field.input) {
				CMS.colorField.update(field.wrapper);
			}
		});

		// The field's own change handlers (onchange) run when the colour is picked
		document.addEventListener('change', (event) => {
			const field = fieldOf(event.target);

			if (field && field.input && event.target.classList.contains('cms-color-swatch')) {
				field.input.dispatchEvent(new Event('change', { bubbles: true }));
			}
		});

		document.addEventListener('click', (event) => {
			const button = event.target.closest ? event.target.closest('.cms-color-clear') : null;
			const field = fieldOf(button);

			if (field && field.input) {
				field.input.value = '';
				CMS.colorField.update(field.wrapper);
				field.input.dispatchEvent(new Event('input', { bubbles: true }));
				field.input.dispatchEvent(new Event('change', { bubbles: true }));
				field.input.focus();
			}
		});
	}

	/* --- The date field (layout joomla.form.field.calendar: the browser's date input, Now and Clear buttons) --- */

	const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
	const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
	const pad = (number, size) => String(number).padStart(size || 2, '0');

	const jalaliCalendar = (jy) => {
		const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
		const gy = jy + 621;
		let leapJ = -14;
		let jp = breaks[0];
		let jump = 0;

		for (let i = 1; i < breaks.length; i++) {
			jump = breaks[i] - jp;

			if (jy < breaks[i]) {
				break;
			}

			leapJ += Math.trunc(jump / 33) * 8 + Math.trunc((jump % 33) / 4);
			jp = breaks[i];
		}

		let n = jy - jp;

		leapJ += Math.trunc(n / 33) * 8 + Math.trunc(((n % 33) + 3) / 4);

		if (jump % 33 === 4 && jump - n === 4) {
			leapJ += 1;
		}

		const leapG = Math.trunc(gy / 4) - Math.trunc((Math.trunc(gy / 100) + 1) * 3 / 4) - 150;

		if (jump - n < 6) {
			n = n - jump + Math.trunc((jump + 4) / 33) * 33;
		}

		const leap = (((n + 1) % 33) - 1) % 4;

		return { leap: leap === -1 ? 4 : leap, gy, march: 20 + leapJ - leapG };
	};
	const jalaliDayNumber = (gy, gm, gd) => {
		const d = Math.trunc((gy + Math.trunc((gm - 8) / 6) + 100100) * 1461 / 4) + Math.trunc((153 * ((gm + 9) % 12) + 2) / 5) + gd - 34840408;

		return d - Math.trunc(Math.trunc((gy + 100100 + Math.trunc((gm - 8) / 6)) / 100) * 3 / 4) + 752;
	};

	CMS.dateField = {
		/**
		 * Date parts as the value of a browser's input of that input's type
		 *
		 * @param  {Object}            parts  Y, m, d, H, M, S
		 * @param  {HTMLInputElement}  input
		 *
		 * @return  {string}
		 */
		nativeValue(parts, input) {
			const seconds = input.step === '1';

			return CMS.dateField.format(parts, {
				date: '%F', month: '%Y-%m', time: seconds ? '%T' : '%R', 'datetime-local': seconds ? '%FT%T' : '%FT%R',
			}[input.type] || '%F');
		},

		/**
		 * A Gregorian date as a Jalali one, and back (Borkowski's algorithm, as jalaali-js; the same as
		 * Joomla\CMS\Date\Jalali, checked against ICU's Persian calendar for 1900-2100)
		 *
		 * @param  {number}  year
		 * @param  {number}  month  1-12
		 * @param  {number}  day
		 *
		 * @return  {number[]}  year, month, day
		 */
		toJalali(year, month, day) {
			const dayNumber = jalaliDayNumber(year, month, day);
			let jy = year - 621;
			const calendar = jalaliCalendar(jy);
			let k = dayNumber - jalaliDayNumber(year, 3, calendar.march);

			if (k >= 0) {
				if (k <= 185) {
					return [jy, 1 + Math.trunc(k / 31), (k % 31) + 1];
				}

				k -= 186;
			} else {
				jy -= 1;
				k += calendar.leap === 1 ? 180 : 179;
			}

			return [jy, 7 + Math.trunc(k / 30), (k % 30) + 1];
		},

		toGregorian(year, month, day) {
			const calendar = jalaliCalendar(year);
			const dayNumber = jalaliDayNumber(calendar.gy, 3, calendar.march) + (month - 1) * 31 - Math.trunc(month / 7) * (month - 7) + day - 1;
			let j = 4 * dayNumber + 139361631;

			j += Math.trunc(Math.trunc((4 * dayNumber + 183187720) / 146097) * 3 / 4) * 4 - 3908;

			const i = Math.trunc((j % 1461) / 4) * 5 + 308;
			const gm = (Math.trunc(i / 153) % 12) + 1;

			return [Math.trunc(j / 1461) - 100100 + Math.trunc((8 - gm) / 6), gm, Math.trunc((i % 153) / 5) + 1];
		},

		/**
		 * A Jalali field's text (YYYY/MM/DD, with hh:mm[:ss]) written into the field's posted value, as a Gregorian date
		 *
		 * @param  {HTMLElement}  wrapper  .cms-date
		 */
		updateJalali(wrapper) {
			const text = wrapper.querySelector('.cms-date-jalali');
			const value = wrapper.querySelector('.cms-date-value');
			const match = text.value.trim().match(/^(\d{3,4})\/(\d{1,2})\/(\d{1,2})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/);

			if (!match || match[2] < 1 || match[2] > 12 || match[3] < 1 || match[3] > 31) {
				value.value = '';

				return;
			}

			const g = CMS.dateField.toGregorian(Number(match[1]), Number(match[2]), Number(match[3]));

			value.value = CMS.dateField.format({
				Y: g[0], m: g[1], d: g[2], H: Number(match[4] || 0), M: Number(match[5] || 0), S: Number(match[6] || 0),
			}, value.getAttribute('data-format') || '%Y-%m-%d');
		},

		/**
		 * The browser's input type for a strftime format: datetime-local, date, time or month
		 *
		 * @param  {string}  format  e.g. %Y-%m-%d %H:%M:%S
		 *
		 * @return  {string}
		 */
		typeOf(format) {
			const time = /%[HIklMSpPRTrX]/.test(format);
			const day = /%[dejaAuwDFxcsU]/.test(format);

			if (!/%[YyCGgmbBhDFxcs]/.test(format) && !day) {
				return time ? 'time' : 'date';
			}

			if (time) {
				return 'datetime-local';
			}

			return day || !/%[mbBh]/.test(format) ? 'date' : 'month';
		},

		/**
		 * The parts of a value of the browser's input (2026-08-24T15:45:10, 2026-08-24, 15:45, 2026-08)
		 *
		 * @param  {string}  value
		 *
		 * @return  {Object|null}  Y, m, d, H, M, S
		 */
		parse(value) {
			const match = String(value || '').match(/^(?:(\d{4,})-(\d{2})(?:-(\d{2}))?)?T?(?:(\d{2}):(\d{2})(?::(\d{2}))?)?/);

			if (!value || !match || (!match[1] && !match[4])) {
				return null;
			}

			const now = new Date();

			return {
				Y: match[1] ? Number(match[1]) : now.getFullYear(),
				m: match[2] ? Number(match[2]) : now.getMonth() + 1,
				d: match[3] ? Number(match[3]) : 1,
				H: match[4] ? Number(match[4]) : 0,
				M: match[5] ? Number(match[5]) : 0,
				S: match[6] ? Number(match[6]) : 0,
			};
		},

		/**
		 * Writes date parts in a strftime format, as PHP reads it back (English names)
		 *
		 * @param  {Object}  parts   Y, m, d, H, M, S
		 * @param  {string}  format
		 *
		 * @return  {string}
		 */
		format(parts, format) {
			const date = new Date(parts.Y, parts.m - 1, parts.d, parts.H, parts.M, parts.S);
			const hour12 = parts.H % 12 || 12;
			const codes = {
				Y: () => parts.Y, y: () => pad(parts.Y % 100), C: () => pad(Math.floor(parts.Y / 100)), m: () => pad(parts.m),
				d: () => pad(parts.d), e: () => String(parts.d).padStart(2, ' '), H: () => pad(parts.H), k: () => String(parts.H).padStart(2, ' '),
				I: () => pad(hour12), l: () => String(hour12).padStart(2, ' '), M: () => pad(parts.M), S: () => pad(parts.S),
				p: () => (parts.H < 12 ? 'AM' : 'PM'), P: () => (parts.H < 12 ? 'am' : 'pm'), B: () => MONTHS[parts.m - 1],
				b: () => MONTHS[parts.m - 1].slice(0, 3), h: () => MONTHS[parts.m - 1].slice(0, 3), A: () => DAYS[date.getDay()],
				a: () => DAYS[date.getDay()].slice(0, 3), u: () => date.getDay() || 7, w: () => date.getDay(),
				j: () => pad(Math.round((date - new Date(parts.Y, 0, 1)) / 86400000) + 1, 3),
				F: () => parts.Y + '-' + pad(parts.m) + '-' + pad(parts.d), D: () => pad(parts.m) + '/' + pad(parts.d) + '/' + pad(parts.Y % 100),
				T: () => pad(parts.H) + ':' + pad(parts.M) + ':' + pad(parts.S), R: () => pad(parts.H) + ':' + pad(parts.M),
				s: () => Math.floor(date.getTime() / 1000), '%': () => '%', n: () => '\n', t: () => '\t',
			};

			return String(format).replace(/%([a-zA-Z%])/g, (all, code) => (codes[code] ? String(codes[code]()) : all));
		},

		/**
		 * The browser's input value for now, in the input's type: in the time zone the field shows (data-utc-offset,
		 * minutes; the user's or the site's), else the browser's
		 *
		 * @param  {string}   type
		 * @param  {boolean}  seconds
		 * @param  {string}   offset  Minutes east of UTC, or null
		 *
		 * @return  {string}
		 */
		now(type, seconds, offset) {
			const zoned = offset !== null && offset !== undefined && offset !== '' && !isNaN(offset);
			const d = zoned ? new Date(Date.now() + Number(offset) * 60000) : new Date();
			const get = (part) => d[(zoned ? 'getUTC' : 'get') + part]();
			const date = get('FullYear') + '-' + pad(get('Month') + 1) + '-' + pad(get('Date'));
			const time = pad(get('Hours')) + ':' + pad(get('Minutes')) + (seconds ? ':' + pad(get('Seconds')) : '');

			return { date, month: date.slice(0, 7), time, 'datetime-local': date + 'T' + time }[type] || date;
		},

		/**
		 * Writes the input's date into the field's posted value, in the field's format
		 *
		 * @param  {HTMLElement}  wrapper  .cms-date
		 */
		update(wrapper) {
			const input = wrapper.querySelector('.cms-date-input');
			const value = wrapper.querySelector('.cms-date-value');

			if (input && value) {
				const parts = CMS.dateField.parse(input.value);

				value.value = parts ? CMS.dateField.format(parts, value.getAttribute('data-format') || '%Y-%m-%d') : '';
			}
		},

		/**
		 * Turns a text field into a date field (the old calendar's Calendar.setup(), extensions' own fields)
		 *
		 * @param  {HTMLInputElement}  field
		 * @param  {string}            format  strftime format of the field's value
		 */
		attach(field, format) {
			if (!field || field.closest('.cms-date') || !field.parentNode) {
				return;
			}

			const wrapper = document.createElement('span');
			const input = document.createElement('input');
			const type = CMS.dateField.typeOf(format);
			const parts = CMS.dateField.parseFormatted(field.value, format);

			wrapper.className = 'cms-date field-calendar';
			input.type = type;
			input.className = 'cms-date-input';

			if (/%[ST]/.test(format)) {
				input.step = 1;
			}

			input.readOnly = field.readOnly;
			input.disabled = field.disabled;
			field.parentNode.insertBefore(wrapper, field);
			wrapper.appendChild(input);

			if (parts) {
				input.value = CMS.dateField.nativeValue(parts, input);
			}

			if (field.id) {
				input.id = field.id;
				field.id += '_value';
			}

			field.type = 'hidden';
			field.classList.add('cms-date-value');
			field.setAttribute('data-format', format);
			wrapper.appendChild(field);
		},

		/**
		 * Reads a value written in a strftime format back (numbers only: what the old calendar's fields held)
		 *
		 * @param  {string}  value
		 * @param  {string}  format
		 *
		 * @return  {Object|null}
		 */
		parseFormatted(value, format) {
			const order = [];
			const pattern = String(format).replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/%([a-zA-Z%])/g, (all, code) => {
				const map = { Y: 'Y', y: 'y', m: 'm', d: 'd', e: 'd', H: 'H', k: 'H', I: 'I', l: 'I', M: 'M', S: 'S', p: 'p', P: 'p' };

				if (code === 'F' || code === 'T' || code === 'R') {
					order.push.apply(order, { F: ['Y', 'm', 'd'], T: ['H', 'M', 'S'], R: ['H', 'M'] }[code]);

					return { F: '(\\d{4})-(\\d{1,2})-(\\d{1,2})', T: '(\\d{1,2}):(\\d{1,2}):(\\d{1,2})', R: '(\\d{1,2}):(\\d{1,2})' }[code];
				}

				if (!map[code]) {
					return code === '%' ? '%' : '.*?';
				}

				order.push(map[code]);

				return map[code] === 'p' ? '([AaPp][Mm])' : '\\s*(\\d{1,4})';
			});
			const match = String(value || '').trim().match(new RegExp('^' + pattern + '$'));

			if (!value || !match) {
				return null;
			}

			const parts = { Y: new Date().getFullYear(), m: 1, d: 1, H: 0, M: 0, S: 0 };
			let pm = null;

			order.forEach((key, i) => {
				const part = match[i + 1];

				if (key === 'p') {
					pm = /^p/i.test(part);
				} else if (key === 'y') {
					parts.Y = 2000 + Number(part);
				} else if (key === 'I') {
					parts.H = Number(part) % 12;
				} else {
					parts[key] = Number(part);
				}
			});

			if (pm) {
				parts.H += 12;
			}

			return parts;
		},
	};

	// One set of listeners for every date field, also those subforms add later; capturing, so the posted value is
	// written before the field's own change handlers run (search filters submit their form on change)
	if (!CMS.dateField.listening) {
		CMS.dateField.listening = true;

		const sync = (event) => {
			const target = event.target;
			const wrapper = target.closest && target.classList ? target.closest('.cms-date') : null;

			if (!wrapper || !event.isTrusted && event.cmsDate) {
				return;
			}

			if (target.classList.contains('cms-date-input')) {
				CMS.dateField.update(wrapper);

				// The picker of a field shown as text: the text and its own change handlers follow
				const text = wrapper.querySelector('.cms-date-text');

				if (text && event.type === 'change') {
					text.removeAttribute('aria-invalid');
					text.dispatchEvent(Object.assign(new Event('input', { bubbles: true }), { cmsDate: true }));
					text.dispatchEvent(Object.assign(new Event('change', { bubbles: true }), { cmsDate: true }));
				}
			} else if (target.classList.contains('cms-date-jalali')) {
				CMS.dateField.updateJalali(wrapper);
			} else if (target.classList.contains('cms-date-text')) {
				// Typed in the language's format: the picker opens on that date
				const input = wrapper.querySelector('.cms-date-input');
				const parts = CMS.dateField.parseFormatted(target.value, target.getAttribute('data-format') || '%Y-%m-%d');

				if (input) {
					input.value = parts ? CMS.dateField.nativeValue(parts, input) : '';
				}
			}
		};

		document.addEventListener('input', sync, true);
		document.addEventListener('change', sync, true);

		document.addEventListener('click', (event) => {
			const button = event.target.closest ? event.target.closest('.cms-date-now, .cms-date-clear, .cms-date-pick') : null;
			const wrapper = button ? button.closest('.cms-date') : null;
			const input = wrapper ? wrapper.querySelector('.cms-date-input') : null;
			const text = wrapper ? wrapper.querySelector('.cms-date-text') : null;
			const jalali = wrapper ? wrapper.querySelector('.cms-date-jalali') : null;

			// Jalali fields: today's Jalali date (and the time, in the time zone the field shows)
			if (jalali) {
				let value = '';

				if (button.classList.contains('cms-date-now')) {
					const now = CMS.dateField.parse(CMS.dateField.now('datetime-local', true, jalali.getAttribute('data-utc-offset')));
					const j = CMS.dateField.toJalali(now.Y, now.m, now.d);
					const time = jalali.hasAttribute('data-jdp-only-date') ? '' : ' ' + pad(now.H) + ':' + pad(now.M) + (jalali.getAttribute('data-jdp-has-second') === 'true' ? ':' + pad(now.S) : '');

					value = j[0] + '/' + pad(j[1]) + '/' + pad(j[2]) + time;
				}

				jalali.value = value;
				CMS.dateField.updateJalali(wrapper);
				jalali.dispatchEvent(Object.assign(new Event('input', { bubbles: true }), { cmsDate: true }));
				jalali.dispatchEvent(Object.assign(new Event('change', { bubbles: true }), { cmsDate: true }));
				jalali.focus();

				return;
			}

			if (!input) {
				return;
			}

			// The browser's picker for a field shown as text (the input under the text field, so it opens there)
			if (button.classList.contains('cms-date-pick')) {
				try {
					input.showPicker();
				} catch (error) {
					input.focus();
				}

				return;
			}

			input.value = button.classList.contains('cms-date-now') ? CMS.dateField.now(input.type, input.step === '1', input.getAttribute('data-utc-offset')) : '';
			CMS.dateField.update(wrapper);

			const field = text || input;

			field.removeAttribute('aria-invalid');
			field.dispatchEvent(Object.assign(new Event('input', { bubbles: true }), { cmsDate: true }));
			field.dispatchEvent(Object.assign(new Event('change', { bubbles: true }), { cmsDate: true }));
			field.focus();
		});
	}

	// JalaliDatePicker (loaded by the layout for languages using the Jalali calendar): fields set time, seconds and limits
	// through their data-jdp-* attributes
	CMS.ready(() => {
		if (window.jalaliDatepicker && document.querySelector('.cms-date-jalali[data-jdp]')) {
			window.jalaliDatepicker.startWatch({
				selector: '.cms-date-jalali[data-jdp]', time: true, hasSecond: 'attr', minDate: 'attr', maxDate: 'attr', zIndex: 2000,
				showTodayBtn: true, showEmptyBtn: true, autoReadOnlyInput: false,
			});
		}
	});

	// The old calendar's script object: extensions call it after adding fields; the browser's input needs no start
	if (!window.JoomlaCalendar) {
		window.JoomlaCalendar = {
			init() {},
			getCalObject() {
				return null;
			},
			onSubmit() {},
		};
	}

	/* --- Show-on: form fields shown only when other fields have given values (data-showon; what cms.js was) --- */

	CMS.showon = {
		/**
		 * The fields a condition reads (a field and its [] form)
		 *
		 * @param  {string}  name
		 *
		 * @return  {Element[]}
		 */
		fields(name) {
			return Array.prototype.slice.call(document.querySelectorAll('[name="' + CSS.escape(name) + '"], [name="' + CSS.escape(name + '[]') + '"]'));
		},

		/**
		 * Shows or hides an element by its conditions: [{field, values, sign (= or !=), op ('', AND, OR)}]
		 *
		 * @param  {HTMLElement}  target
		 */
		update(target) {
			let conditions;

			try {
				conditions = JSON.parse(target.getAttribute('data-showon') || '[]') || [];
			} catch (error) {
				return;
			}

			let show = true;

			conditions.forEach((condition, j) => {
				condition.valid = 0;

				CMS.showon.fields(condition.field).forEach((field) => {
					let values;

					// Checkboxes and radio buttons count when checked; a multiple list without a choice is no value
					if (field.type === 'checkbox' || field.type === 'radio') {
						if (!field.checked) {
							return;
						}

						values = [field.value];
					} else if (field.tagName === 'SELECT' && field.multiple) {
						values = Array.prototype.filter.call(field.options, (option) => option.selected).map((option) => option.value);
					} else {
						values = [field.value];
					}

					values.forEach((value) => {
						const listed = (condition.values || []).indexOf(String(value)) !== -1;

						if ((condition.sign === '=' && listed) || (condition.sign === '!=' && !listed)) {
							condition.valid = 1;
						}
					});
				});

				// The first condition must be met; AND needs it and the one before, OR either of them
				if (!condition.op) {
					if (!condition.valid) {
						show = false;
					}
				} else if (condition.op === 'AND' && condition.valid + conditions[j - 1].valid < 2) {
					show = false;
					condition.valid = 0;
				} else if (condition.op === 'OR' && condition.valid + conditions[j - 1].valid > 0) {
					show = true;
					condition.valid = 1;
				}
			});

			target.style.display = show ? '' : 'none';

			// Options of a list are disabled too, and Chosen redraws the list
			if (target.tagName === 'OPTION') {
				target.disabled = !show;

				const list = target.parentNode;

				if (list && list.id && document.getElementById(list.id + '_chzn') && window.jQuery) {
					window.jQuery(list).trigger('liszt:updated').trigger('chosen:updated');
				}
			}
		},

		/**
		 * Starts the elements with conditions in a container (once each)
		 *
		 * @param  {Element|Document}  container
		 */
		initialise(container) {
			Array.prototype.forEach.call((container || document).querySelectorAll('[data-showon]'), (target) => {
				if (!target.hasAttribute('data-showon-initialised')) {
					target.setAttribute('data-showon-initialised', '');
					CMS.showon.update(target);
				}
			});
		},

		/**
		 * A field changed: the elements whose conditions read it follow
		 *
		 * @param  {Element}  field
		 */
		changed(field) {
			const name = field && field.name ? field.name.replace(/\[\]$/, '') : '';

			if (!name) {
				return;
			}

			Array.prototype.forEach.call(document.querySelectorAll('[data-showon-initialised]'), (target) => {
				if (target.getAttribute('data-showon').indexOf(JSON.stringify(name).slice(1, -1)) !== -1) {
					CMS.showon.update(target);
				}
			});
		},
	};

	// Changes are heard through jQuery where it is loaded: Chosen and subforms send theirs only with jQuery's trigger(),
	// which never reaches native listeners
	CMS.ready(() => {
		if (CMS.showon.started) {
			return;
		}

		CMS.showon.started = true;
		CMS.showon.initialise(document);

		// A subform row added (jQuery's subform-row-add) or content replaced (joomla:updated): its conditions name the
		// row's own fields
		const added = (target) => {
			if (target && target.classList && target.classList.contains('subform-repeatable-group')) {
				const baseName = target.getAttribute('data-base-name');
				const group = target.getAttribute('data-group');
				const search = new RegExp('\\[' + baseName + '\\]\\[' + baseName + 'X\\]', 'g');

				Array.prototype.forEach.call(target.querySelectorAll('[data-showon]'), (element) => {
					element.setAttribute('data-showon', element.getAttribute('data-showon').replace(search, '[' + baseName + '][' + group + ']'));
				});
			}

			CMS.showon.initialise(target && target.querySelectorAll ? target : document);
		};

		if (window.jQuery) {
			window.jQuery(document).on('change keyup', (event) => CMS.showon.changed(event.target));
			window.jQuery(document).on('subform-row-add joomla:updated', (event, row) => added(event.type === 'joomla:updated' ? event.target : (row && row.jquery ? row[0] : row)));
		} else {
			document.addEventListener('change', (event) => CMS.showon.changed(event.target));
			document.addEventListener('keyup', (event) => CMS.showon.changed(event.target));
			document.addEventListener('joomla:updated', (event) => added(event.target));
		}
	});

	// The old names (cms.js)
	if (!Joomla.Showon) {
		Joomla.Showon = { initialise: (container) => CMS.showon.initialise(container && container.jquery ? container[0] : container) };
	}

	if (!Joomla.setcollapse) {
		Joomla.setcollapse = (url, name, height) => {
			if (!document.getElementById('collapse-' + name)) {
				const frame = document.createElement('iframe');
				const wrapper = document.createElement('div');

				wrapper.className = 'collapse fade';
				wrapper.id = 'collapse-' + name;
				frame.className = 'iframe';
				frame.src = url;
				frame.height = height;
				frame.width = '100%';
				wrapper.appendChild(frame);
				document.getElementById('container-collapse').replaceChildren(wrapper);
			}
		};
	}

	/* --- Tooltips and popovers (JHtml bootstrap.tooltip, bootstrap.popover: Bootstrap's markup, our placement) --- */

	/*
	 * Elements matching a registered selector show a tooltip (title, data-original-title) or a popover (title and
	 * data-content) on hover and keyboard focus (or click, with trigger: 'click'), written as Bootstrap 2 wrote them, so
	 * the templates' styles apply. Placement (data-placement or the option: top, bottom, left, right) flips to the other
	 * side when the element is near an edge of the window, then the bubble slides to stay inside it, its arrow still
	 * pointing at the element.
	 */
	const TIP_MARGIN = 6;

	CMS.tip = {
		selectors: { tooltip: {}, popover: {} },
		shown: new Map(),
		counter: 0,

		/**
		 * Elements of a selector get tooltips or popovers
		 *
		 * @param  {string}  selector
		 * @param  {Object}  options  placement, html, title, content (popovers), trigger, delay
		 * @param  {string}  type     tooltip or popover
		 */
		register(selector, options, type) {
			CMS.tip.selectors[type === 'popover' ? 'popover' : 'tooltip'][selector] = options || {};
		},

		/**
		 * The registered selector an element matches, and its type and options
		 *
		 * @param  {Element}  target
		 *
		 * @return  {Object|null}  element, type, options
		 */
		find(target) {
			if (!target || !target.closest) {
				return null;
			}

			for (const type of ['popover', 'tooltip']) {
				for (const selector of Object.keys(CMS.tip.selectors[type])) {
					let element = null;

					try {
						element = target.closest(selector);
					} catch (error) {
						element = null;
					}

					// Bootstrap's own plugin already drives it (an extension started it): left alone
					if (element && !(window.jQuery && window.jQuery(element).data(type))) {
						return { element, type, options: CMS.tip.selectors[type][selector] };
					}
				}
			}

			return null;
		},

		/**
		 * The title, moved to data-original-title as Bootstrap did, so the browser's own tooltip doesn't show as well
		 *
		 * @param  {Element}  element
		 * @param  {Object}   options
		 *
		 * @return  {string}
		 */
		title(element, options) {
			if (element.hasAttribute('title')) {
				if (element.getAttribute('title') || !element.hasAttribute('data-original-title')) {
					element.setAttribute('data-original-title', element.getAttribute('title'));
				}

				element.removeAttribute('title');
			}

			return element.getAttribute('data-original-title') || options.title || '';
		},

		show(found) {
			const { element, type, options } = found;

			if (CMS.tip.shown.has(element)) {
				return;
			}

			const title = CMS.tip.title(element, options);
			const content = type === 'popover' ? element.getAttribute('data-content') || options.content || '' : '';

			if (!title && !content) {
				return;
			}

			const tip = document.createElement('div');
			const html = options.html !== false;
			const fill = (node, text) => {
				if (html) {
					node.innerHTML = text;
				} else {
					node.textContent = text;
				}
			};

			tip.id = 'cms-' + type + '-' + (++CMS.tip.counter);
			tip.setAttribute('role', 'tooltip');

			if (type === 'popover') {
				tip.className = 'popover fade';
				tip.innerHTML = '<div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div>';
				fill(tip.querySelector('.popover-title'), title);
				fill(tip.querySelector('.popover-content'), content);
			} else {
				tip.className = 'tooltip fade';
				tip.innerHTML = '<div class="tooltip-arrow"></div><div class="tooltip-inner"></div>';
				fill(tip.querySelector('.tooltip-inner'), title);
			}

			// Above the modal its element is in (Bootstrap's 1050, the CMS's own): tooltips' layer is below modals'
			const modal = element.closest('.modal, #sbox-window');

			if (modal) {
				tip.style.zIndex = (parseInt(window.getComputedStyle(modal).zIndex, 10) || 1050) + 10;
			}

			tip.style.display = 'block';
			tip.style.visibility = 'hidden';
			document.body.appendChild(tip);
			element.setAttribute('aria-describedby', tip.id);
			CMS.tip.shown.set(element, { tip, type, options });
			CMS.tip.place(element);
			tip.style.visibility = '';
			window.requestAnimationFrame(() => tip.classList.add('in'));
		},

		hide(element) {
			const entry = CMS.tip.shown.get(element);

			if (!entry) {
				return;
			}

			CMS.tip.shown.delete(element);
			element.removeAttribute('aria-describedby');
			entry.tip.classList.remove('in');
			window.setTimeout(() => entry.tip.remove(), 160);
		},

		hideAll() {
			Array.from(CMS.tip.shown.keys()).forEach(CMS.tip.hide);
		},

		/**
		 * Places a shown bubble: the asked side if it fits, else the opposite, else the side with the most room; then
		 * kept inside the window along the other axis, the arrow moved to keep pointing at the element
		 *
		 * @param  {Element}  element
		 */
		place(element) {
			const entry = CMS.tip.shown.get(element);

			if (!entry) {
				return;
			}

			const { tip, type, options } = entry;
			const arrow = tip.querySelector(type === 'popover' ? '.arrow' : '.tooltip-arrow');
			const box = element.getBoundingClientRect();
			const view = { width: document.documentElement.clientWidth, height: document.documentElement.clientHeight };
			const opposite = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' };
			let asked = (element.getAttribute('data-placement') || options.placement || (type === 'popover' ? 'right' : 'top')).replace(/^auto\s*/, '') || 'top';

			if (!opposite[asked]) {
				asked = 'top';
			}

			// Where the bubble would sit on a side (its margins, from the templates' CSS, included)
			const measure = (side) => {
				tip.classList.remove('top', 'bottom', 'left', 'right');
				tip.classList.add(side);
				arrow.style.left = '';
				arrow.style.top = '';

				const style = window.getComputedStyle(tip);
				const width = tip.offsetWidth;
				const height = tip.offsetHeight;
				const shiftX = parseFloat(style.marginLeft) || 0;
				const shiftY = parseFloat(style.marginTop) || 0;
				let left;
				let top;

				if (side === 'top' || side === 'bottom') {
					left = box.left + box.width / 2 - width / 2;
					top = side === 'top' ? box.top - height : box.bottom;
				} else {
					top = box.top + box.height / 2 - height / 2;
					left = side === 'left' ? box.left - width : box.right;
				}

				const room = { top: box.top, bottom: view.height - box.bottom, left: box.left, right: view.width - box.right }[side];
				const fits = side === 'top' || side === 'bottom' ? room >= height + Math.abs(shiftY) + TIP_MARGIN : room >= width + Math.abs(shiftX) + TIP_MARGIN;

				return { side, left, top, width, height, room, fits };
			};

			const tries = [asked, opposite[asked]].concat(['top', 'bottom', 'right', 'left'].filter((side) => side !== asked && side !== opposite[asked]));
			const measured = tries.map(measure);
			const chosen = measured.find((option) => option.fits) || measured.reduce((best, option) => (option.room > best.room ? option : best));
			const final = measure(chosen.side);
			let { left, top } = final;

			// Inside the window along the other axis, the arrow following the element
			if (final.side === 'top' || final.side === 'bottom') {
				const clamped = Math.max(TIP_MARGIN, Math.min(left, view.width - final.width - TIP_MARGIN));

				if (clamped !== left) {
					arrow.style.left = Math.max(12, Math.min(final.width - 12, box.left + box.width / 2 - clamped)) + 'px';
				}

				left = clamped;
			} else {
				const clamped = Math.max(TIP_MARGIN, Math.min(top, view.height - final.height - TIP_MARGIN));

				if (clamped !== top) {
					arrow.style.top = Math.max(12, Math.min(final.height - 12, box.top + box.height / 2 - clamped)) + 'px';
				}

				top = clamped;
			}

			tip.style.left = (left + window.pageXOffset) + 'px';
			tip.style.top = (top + window.pageYOffset) + 'px';
		},

		/**
		 * Starts the selectors pages registered (script options system.tooltip, system.popover: selector => options)
		 */
		start() {
			['tooltip', 'popover'].forEach((type) => {
				const registered = CMS.options.get('system.' + type) || {};

				Object.keys(registered).forEach((selector) => CMS.tip.register(selector, registered[selector], type));
			});
		},
	};

	if (!CMS.tip.listening) {
		CMS.tip.listening = true;

		const triggers = (found) => String(found.options.trigger || 'hover focus');
		const delayed = new WeakMap();
		const later = (found, show) => {
			const delay = found.options.delay;
			const wait = typeof delay === 'object' && delay ? delay[show ? 'show' : 'hide'] : delay;

			window.clearTimeout(delayed.get(found.element));

			if (wait > 0) {
				delayed.set(found.element, window.setTimeout(() => (show ? CMS.tip.show(found) : CMS.tip.hide(found.element)), wait));
			} else if (show) {
				CMS.tip.show(found);
			} else {
				CMS.tip.hide(found.element);
			}
		};

		document.addEventListener('mouseover', (event) => {
			const found = CMS.tip.find(event.target);

			if (found && triggers(found).indexOf('hover') !== -1 && !found.element.contains(event.relatedTarget)) {
				later(found, true);
			}
		});

		document.addEventListener('mouseout', (event) => {
			const found = CMS.tip.find(event.target);

			if (found && triggers(found).indexOf('hover') !== -1 && !found.element.contains(event.relatedTarget)) {
				later(found, false);
			}
		});

		document.addEventListener('focusin', (event) => {
			const found = CMS.tip.find(event.target);

			if (found && triggers(found).indexOf('focus') !== -1) {
				later(found, true);
			}
		});

		document.addEventListener('focusout', (event) => {
			const found = CMS.tip.find(event.target);

			if (found && triggers(found).indexOf('focus') !== -1) {
				later(found, false);
			}
		});

		document.addEventListener('click', (event) => {
			const found = CMS.tip.find(event.target);

			if (found && triggers(found).indexOf('click') !== -1) {
				if (CMS.tip.shown.has(found.element)) {
					CMS.tip.hide(found.element);
				} else {
					CMS.tip.show(found);
				}

				return;
			}

			// A click elsewhere closes the bubbles opened by clicks
			CMS.tip.shown.forEach((entry, element) => {
				if (String(entry.options.trigger || '').indexOf('click') !== -1 && !entry.tip.contains(event.target)) {
					CMS.tip.hide(element);
				}
			});
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				CMS.tip.hideAll();
			}
		});

		// Kept in place while the page scrolls or the window changes size; gone with their element
		const replace = () => CMS.tip.shown.forEach((entry, element) => (element.isConnected ? CMS.tip.place(element) : CMS.tip.hide(element)));

		window.addEventListener('scroll', replace, true);
		window.addEventListener('resize', replace);

		CMS.ready(CMS.tip.start);
	}

	/* --- Form validation (behavior.formvalidator: document.formvalidator, what validate.js was) --- */

	/*
	 * Forms with class form-validate: fields are checked when they lose focus and all of them when the form is sent
	 * (isValid(), which the forms' submitbutton functions call). Required fields (required, class required) need a
	 * value; class validate-<name> runs a handler (username, password, numeric, email, or one added with setHandler());
	 * a pattern attribute is checked first. Invalid fields and their labels get class invalid and aria-invalid.
	 */
	const createValidator = () => {
		const handlers = {};
		const custom = {};
		const labels = new WeakMap();
		const attached = new WeakSet();

		// Handlers got the field as a jQuery object: they still do where jQuery is loaded
		const wrap = (element) => (window.jQuery ? window.jQuery(element) : element);

		const setHandler = (name, fn, enabled) => {
			handlers[name] = { enabled: enabled === '' ? true : enabled, exec: fn };
		};

		const findLabel = (id, form) => {
			if (!id || !form) {
				return false;
			}

			const own = form.querySelector('#' + CSS.escape(id + '-lbl'));

			if (own) {
				return [own];
			}

			const found = Array.prototype.slice.call(form.querySelectorAll('label[for="' + CSS.escape(id) + '"]'));

			return found.length ? found : false;
		};

		const handleResponse = (state, element) => {
			if (!labels.has(element)) {
				labels.set(element, findLabel(element.id, element.form));
			}

			const label = labels.get(element);

			element.classList.toggle('invalid', state === false);
			element.setAttribute('aria-invalid', state === false ? 'true' : 'false');

			if (label) {
				label.forEach((item) => item.classList.toggle('invalid', state === false));
			}
		};

		// What jQuery's val() gave: the value without carriage returns, a multiple select's chosen values (null for none)
		const valueOf = (element) => {
			if (element.tagName === 'SELECT' && element.multiple) {
				const chosen = Array.prototype.filter.call(element.options, (option) => option.selected).map((option) => option.value);

				return chosen.length ? chosen : null;
			}

			return typeof element.value === 'string' ? element.value.replace(/\r/g, '') : '';
		};

		const validate = (element) => {
			if (typeof element === 'string') {
				element = document.getElementById(element.replace(/^#/, ''));
			}

			if (!element) {
				return true;
			}

			// Disabled fields aren't sent
			if (element.disabled || element.hasAttribute('disabled')) {
				handleResponse(true, element);

				return true;
			}

			const required = element.hasAttribute('required') || element.classList.contains('required');
			const value = valueOf(element);

			if (required) {
				if (element.tagName === 'FIELDSET' && (element.classList.contains('radio') || element.classList.contains('checkboxes'))) {
					if (!element.querySelector('input:checked')) {
						handleResponse(false, element);

						return false;
					}
				} else if (!value || element.classList.contains('placeholder') || (element.type === 'checkbox' && !element.checked)) {
					// Class placeholder: the field shows a placeholder, it's empty
					handleResponse(false, element);

					return false;
				}
			}

			const match = (element.getAttribute('class') || '').match(/validate-([a-zA-Z0-9_-]+)/);
			const handler = match ? match[1] : '';
			const pattern = element.getAttribute('pattern');

			// A pattern attribute first, then the handlers
			if (pattern) {
				const text = Array.isArray(value) ? value.join(',') : String(value || '');

				if (text.length) {
					const valid = new RegExp('^' + pattern + '$').test(text);

					handleResponse(valid, element);

					return valid;
				}

				handleResponse(!required, element);

				return !required;
			}

			if (handler && handler !== 'none' && handlers[handler] && value && handlers[handler].exec(value, wrap(element)) !== true) {
				handleResponse(false, element);

				return false;
			}

			handleResponse(true, element);

			return true;
		};

		const isValid = (form) => {
			const invalid = [];
			let valid = true;

			Array.prototype.forEach.call(form.querySelectorAll('input, textarea, select, fieldset'), (field) => {
				// Fields with class novalidate (the Permissions field, filters...) are left out
				if (!field.classList.contains('novalidate') && validate(field) === false) {
					valid = false;
					invalid.push(field);
				}
			});

			Object.keys(custom).forEach((key) => {
				if (custom[key].exec() !== true) {
					valid = false;
				}
			});

			if (!valid && invalid.length) {
				const message = Joomla.JText._('JLIB_FORM_FIELD_INVALID');
				const errors = [];

				for (let i = invalid.length - 1; i >= 0; i--) {
					const label = labels.get(invalid[i]);

					if (label) {
						errors.push(message + label.map((item) => item.textContent).join('').replace('*', ''));
					}
				}

				Joomla.renderMessages({ error: errors });
			}

			return valid;
		};

		const attachToForm = (form) => {
			if (!form || attached.has(form)) {
				return;
			}

			attached.add(form);

			const fields = [];

			Array.prototype.forEach.call(form.querySelectorAll('input, textarea, select, fieldset, button'), (element) => {
				const tag = element.tagName.toLowerCase();
				const type = (element.getAttribute('type') || '').toLowerCase();

				if ((tag === 'input' || tag === 'button') && (type === 'submit' || type === 'image')) {
					// Submit buttons with class validate check the form first
					if (element.classList.contains('validate')) {
						element.addEventListener('click', (event) => {
							if (!isValid(form)) {
								event.preventDefault();
								event.stopPropagation();
							}
						});
					}
				} else if (tag !== 'button' && !(tag === 'input' && type === 'button')) {
					if (element.classList.contains('required')) {
						element.setAttribute('aria-required', 'true');
						element.setAttribute('required', 'required');
					}

					if (tag !== 'fieldset') {
						element.addEventListener('blur', () => validate(element));

						if (element.classList.contains('validate-email')) {
							element.setAttribute('type', 'email');
						}
					}

					fields.push(element);
				}
			});

			if (window.jQuery) {
				window.jQuery(form).data('inputfields', fields.map(wrap));
			}
		};

		setHandler('username', (value) => !/[<|>|"|'|%|;|(|)|&]/i.test(value));
		setHandler('password', (value) => /^\S[\S ]{2,98}\S$/.test(value));
		setHandler('numeric', (value) => /^(\d|-)?(\d|,)*\.?\d*$/.test(value));
		setHandler('email', (value) => {
			// An internationalised domain in ASCII (xn--), by the browser's own IDNA (punycode.js did it before 3.18); only
			// for domains of letters, digits, dots, hyphens and non-ASCII characters, as a URL would drop a port or path
			const at = value.lastIndexOf('@');
			const domain = value.slice(at + 1);
			let ascii = value;

			if (at > -1 && /[^\x00-\x7F]/.test(domain) && !/[^A-Za-z0-9.\-\u0080-\uFFFF]/.test(domain)) {
				try {
					ascii = value.slice(0, at + 1) + new URL('http://' + domain).hostname;
				} catch (error) {
					ascii = value;
				}
			}

			return /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(ascii);
		});

		const attachAll = () => Array.prototype.forEach.call(document.querySelectorAll('form.form-validate'), attachToForm);

		return { isValid, validate, setHandler, attachToForm, custom, attachAll };
	};

	// From the start, so scripts can add handlers before the page is ready; attached to the forms once it is, on pages
	// which asked for it (behavior.formvalidator). frontend.js may have made it already: the same one is kept
	CMS.formValidator = CMS.formValidator || createValidator();
	document.formvalidator = CMS.formValidator;

	CMS.ready(() => {
		if (CMS.options.get('system.formvalidator')) {
			CMS.formValidator.attachAll();
		}
	});

	// The old constructor: a validator of its own, attached to the forms at once
	window.JFormValidator = function JFormValidator() {
		const validator = createValidator();

		validator.attachAll();

		return validator;
	};

	/* --- Keeping the session alive (behavior.keepalive) --- */

	CMS.keepalive = {
		timer: null,

		/**
		 * Asks the site for a page now and then, so a long edit doesn't outlive the session
		 *
		 * @param  {Object}  options  interval (milliseconds, 45 seconds by default), uri (com_ajax by default)
		 */
		start(options) {
			options = options || {};

			if (CMS.keepalive.timer) {
				window.clearInterval(CMS.keepalive.timer);
			}

			const paths = CMS.options.get('system.paths');
			const uri = options.uri ? options.uri.replace(/&amp;/g, '&')
				: (paths ? paths.root + '/index.php' : window.location.pathname) + '?option=com_ajax&format=json';

			CMS.keepalive.timer = window.setInterval(() => CMS.request({ url: uri, onSuccess() {}, onError() {} }), options.interval || 45000);

			return CMS.keepalive;
		},

		stop() {
			window.clearInterval(CMS.keepalive.timer);
			CMS.keepalive.timer = null;

			return CMS.keepalive;
		},
	};

	// Pages which asked for it carry its options (JHtml behavior.keepalive)
	CMS.ready(() => {
		const options = CMS.options.get('system.keepalive');

		if (options) {
			CMS.keepalive.start(options);
		}
	});

	/* --- Behaviours started from the page's script options --- */

	CMS.ready(() => {
		Object.keys(CMS.options.get('system.highlighter') || {}).forEach((sig) => {
			const options = CMS.options.get('system.highlighter')[sig];
			const start = document.getElementById(options.start);
			const end = document.getElementById(options.end);

			if (start && end) {
				CMS.highlighter.create({ startElement: start, endElement: end, className: options.className, onlyWords: false, tag: options.tag })
					.highlight(options.terms);
				start.remove();
				end.remove();
			}
		});
	});

	// Captions need the images' sizes
	window.addEventListener('load', () => {
		Object.keys(CMS.options.get('system.caption') || {}).forEach((selector) => CMS.caption.attach(selector));
	});

	/* --- The old names (the full list is in global.js) --- */

	const LEGACY = {
		'Joomla.submitform': CMS.form.submit,
		'Joomla.submitbutton': CMS.form.button,
		'Joomla.replaceTokens': CMS.form.replaceTokens,
		'Joomla.getOptions': CMS.options.get,
		'Joomla.loadOptions': CMS.options.load,
		'Joomla.checkAll': CMS.list.checkAll,
		'Joomla.isChecked': CMS.list.isChecked,
		'Joomla.listItemTask': CMS.list.itemTask,
		'Joomla.tableOrdering': CMS.list.order,
		'Joomla.renderMessages': CMS.messages.render,
		'Joomla.removeMessages': CMS.messages.remove,
		'Joomla.ajaxErrorsMessages': CMS.messages.fromAjaxError,
		'Joomla.popupWindow': CMS.window.open,
		'Joomla.loadingLayer': CMS.loading.layer,
		'Joomla.extend': CMS.extend,
		'Joomla.request': CMS.request,
	};

	Object.keys(LEGACY).forEach((name) => {
		Joomla[name.split('.')[1]] = LEGACY[name];
	});

	Joomla.Text = Joomla.JText = CMS.text;
	Object.defineProperty(Joomla, 'optionsStorage', {
		get: () => CMS.options.storage,
		set: (value) => {
			CMS.options.storage = value;
		},
		configurable: true,
	});

	if (!Joomla.Highlighter) {
		Joomla.Highlighter = (options) => CMS.highlighter.create(options);
	}

	if (!window.JCaption) {
		window.JCaption = function JCaption(selector) {
			CMS.caption.attach(selector);
		};
	}

	window.listItemTask = (id, task) => {
		window.console.warn('window.listItemTask() is deprecated use Joomla.listItemTask() instead');

		return Joomla.listItemTask(id, task);
	};

	window.submitbutton = (task) => {
		window.console.warn('window.submitbutton() is deprecated use Joomla.submitbutton() instead');
		Joomla.submitbutton(task);
	};

	window.submitform = (task) => {
		window.console.warn('window.submitform() is deprecated use Joomla.submitform() instead');
		Joomla.submitform(task);
	};
})(window, document);
