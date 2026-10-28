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
