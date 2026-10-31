/**
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @copyright  Portions: SqueezeBox 1.3 (C) Harald Kirschner, Rouven Weßling; MooTree (C) Rasmus Schultz; Form.PasswordStrength (C) Al Kent; Chosen 1.6.2 (C) Harvest; MIT License
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

((window, document) => {
	'use strict';

	if (window.CMS && window.CMS.global) {
		return;
	}

	// Scripts may have started the old namespace before this file: their editors and options stay
	const Joomla = window.Joomla = window.Joomla || {};

	const CMS = window.CMS = window.CMS || {};

	// frontend.js ran first (written into the page by hand): it already started captions, highlighting and keepalive
	const started = !!CMS.frontend;

	CMS.global = true;

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

		// Joomla.submitbutton(), which pages may replace with their own. The page's form (name adminForm) with
		// data-submit-validate is validated first, except for tasks with a "cancel" part (article.cancel,
		// config.cancel.application); data-submit-disable is a selector of fields left out of the post
		button(task) {
			const form = document.getElementById('adminForm') || document.querySelector('form[name="adminForm"]');

			if (!form) {
				Joomla.submitform(task);

				return;
			}

			const cancel = String(task || '').split('.').indexOf('cancel') !== -1;

			if (!cancel && form.hasAttribute('data-submit-validate') && document.formvalidator && !document.formvalidator.isValid(form)) {
				return;
			}

			const disable = form.getAttribute('data-submit-disable');

			if (disable && !cancel) {
				for (const field of document.querySelectorAll(disable)) {
					field.disabled = true;
				}
			}

			Joomla.submitform(task, form);
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

	/*
	 * Sends a request with fetch(). The callbacks get what they read of an XMLHttpRequest (status, statusText,
	 * responseText, getResponseHeader()), as Joomla.request() gave them one: onBefore(request) may still call
	 * setRequestHeader(), and with perform: false the caller sends it (request.send()).
	 */
	CMS.request = (options) => {
		options = Object.assign({ url: '', method: 'GET', data: null, perform: true }, options);
		options.method = options.data ? 'POST' : options.method.toUpperCase();

		const headers = { 'X-Requested-With': 'XMLHttpRequest', 'X-Ajax-Engine': 'Joomla!' };
		const controller = typeof AbortController === 'function' ? new AbortController() : null;
		let received = null;

		if (options.method === 'POST') {
			const token = CMS.options.get('csrf.token', '');

			if (token) {
				headers['X-CSRF-Token'] = token;
			}

			if (typeof options.data === 'string' && (!options.headers || !options.headers['Content-Type'])) {
				headers['Content-Type'] = 'application/x-www-form-urlencoded';
			}
		}

		Object.assign(headers, options.headers || {});

		const request = {
			readyState: 1,
			status: 0,
			statusText: '',
			responseText: '',
			response: '',
			responseURL: '',
			setRequestHeader(name, value) {
				headers[name] = value;
			},
			getResponseHeader: (name) => (received ? received.get(name) : null),
			getAllResponseHeaders() {
				let all = '';

				if (received) {
					received.forEach((value, name) => {
						all += name + ': ' + value + '\r\n';
					});
				}

				return all;
			},
			abort() {
				if (controller) {
					controller.abort();
				}
			},
			send(data) {
				const body = data === undefined ? options.data : data;
				const finish = (status, statusText, text) => {
					Object.assign(request, { readyState: 4, status, statusText, responseText: text, response: text });

					if (status === 200) {
						if (options.onSuccess) {
							options.onSuccess.call(window, text, request);
						}
					} else if (options.onError) {
						options.onError.call(window, request);
					}
				};

				fetch(options.url, {
					method: options.method,
					headers,
					body: options.method === 'GET' || options.method === 'HEAD' ? undefined : body,
					credentials: 'same-origin',
					signal: controller ? controller.signal : undefined,
				})
					.then((response) => response.text().then((text) => ({ response, text })))
					// A network failure or abort(): status 0, as an XMLHttpRequest reported them
					.then((result) => {
						received = result.response.headers;
						request.responseURL = result.response.url;
						finish(result.response.status, result.response.statusText, result.text);
					}, () => finish(0, '', ''));
			},
		};

		if (options.perform) {
			if (options.onBefore && options.onBefore.call(window, request) === false) {
				return request;
			}

			request.send(options.data);
		}

		return request;
	};

	/* --- Modal (what SqueezeBox was) --- */

	/*
	 * Opens links in a box over the page: an iframe, an image, part of the page (#id) or a page loaded with AJAX. The same
	 * markup and options as SqueezeBox: links with rel="{handler: 'iframe', size: {x: 800, y: 600}}" (evaluated when
	 * clicked, so sizes may be expressions), the box #sbox-window over #sbox-overlay (media/system/css/modal.css), the
	 * options and events (onOpen, onClose...). Below 980px wide it fills the screen.
	 */
	const modal = {
		presets: {
			onOpen() {},
			onClose() {},
			onUpdate() {},
			onResize() {},
			onMove() {},
			onShow() {},
			onHide() {},
			size: { x: 600, y: 450 },
			sizeLoading: { x: 200, y: 150 },
			marginInner: { x: 20, y: 20 },
			marginImage: { x: 50, y: 75 },
			handler: false,
			target: null,
			closable: true,
			closeBtn: true,
			zIndex: 65555,
			overlayOpacity: 0.7,
			classWindow: '',
			classOverlay: '',
			parse: false,
			parseSecure: false,
			overlay: true,
			iframeOptions: null,
			iframePreload: false,
			ajaxOptions: {},
			title: '',
			buttons: null,
		},
		initialized: false,
		isOpen: false,
		isLoading: false,
		options: {},
		element: null,
		url: '',
		adopted: null,
		opener: null,
		listeners: {},
		selectors: [],
		assigned: new WeakMap(),
	};

	const emit = (type, args) => {
		const name = 'on' + type.charAt(0).toUpperCase() + type.slice(1);

		if (typeof modal.options[name] === 'function') {
			modal.options[name].apply(CMS.modal, args);
		}

		(modal.listeners[type] || []).slice().forEach((listener) => listener.apply(CMS.modal, args));
		document.dispatchEvent(new CustomEvent('cms:modal-' + type, { detail: { modal: CMS.modal, args } }));

		// Scripts which listen with jQuery on SqueezeBox (Smart Search's indexer)
		if (window.jQuery && window.SqueezeBox === SqueezeBoxShim) {
			window.jQuery(SqueezeBoxShim).triggerHandler(type);
		}
	};

	const build = () => {
		if (modal.win) {
			return;
		}

		modal.overlay = document.createElement('div');
		modal.overlay.id = 'sbox-overlay';
		modal.overlay.setAttribute('aria-hidden', 'true');
		modal.overlay.tabIndex = -1;

		modal.win = document.createElement('div');
		modal.win.id = 'sbox-window';
		modal.win.className = 'shadow';
		modal.win.setAttribute('role', 'dialog');
		modal.win.setAttribute('aria-modal', 'true');
		modal.win.setAttribute('aria-hidden', 'true');

		modal.content = document.createElement('div');
		modal.content.id = 'sbox-content';
		modal.win.appendChild(modal.content);

		modal.closeBtn = document.createElement('a');
		modal.closeBtn.id = 'sbox-btn-close';
		modal.closeBtn.href = '#';
		modal.closeBtn.setAttribute('role', 'button');
		modal.closeBtn.setAttribute('aria-controls', 'sbox-window');
		modal.closeBtn.setAttribute('aria-label', CMS.text.get('JLIB_HTML_BEHAVIOR_CLOSE', 'Close'));
		modal.win.appendChild(modal.closeBtn);

		modal.overlay.addEventListener('click', (event) => CMS.modal.close(event));
		modal.closeBtn.addEventListener('click', (event) => CMS.modal.close(event));

		document.body.appendChild(modal.overlay);
		document.body.appendChild(modal.win);
	};

	const viewport = () => ({ x: document.documentElement.clientWidth, y: document.documentElement.clientHeight });

	/*
	 * A title above the content (options.title) and a row of buttons below it (options.buttons: {text, className,
	 * onClick(event, button)}), inside the box's size: the content takes what they leave
	 */
	const decorate = () => {
		[modal.header, modal.footer].forEach((part) => part && part.remove());
		modal.header = null;
		modal.footer = null;
		modal.win.removeAttribute('aria-labelledby');

		if (modal.options.title) {
			modal.header = document.createElement('h3');
			modal.header.id = 'sbox-title';
			modal.header.textContent = modal.options.title;
			modal.win.insertBefore(modal.header, modal.content);
			modal.win.setAttribute('aria-labelledby', 'sbox-title');
		}

		if (Array.isArray(modal.options.buttons) && modal.options.buttons.length) {
			modal.footer = document.createElement('div');
			modal.footer.id = 'sbox-buttons';

			modal.options.buttons.forEach((item) => {
				const button = document.createElement('button');

				button.type = 'button';
				button.className = item.className || 'btn';
				button.textContent = item.text || '';

				if (typeof item.onClick === 'function') {
					button.addEventListener('click', (event) => item.onClick.call(CMS.modal, event, button));
				}

				modal.footer.appendChild(button);
			});

			modal.win.insertBefore(modal.footer, modal.content.nextSibling);
		}

		modal.win.classList.toggle('sbox-framed', Boolean(modal.header || modal.footer));
	};

	// The box's place and size: the size asked for, centred, or below 980px the whole screen less a margin
	const layout = (size) => {
		const box = viewport();

		modal.size = Object.assign({}, modal.isLoading ? modal.options.sizeLoading : modal.options.size, size || {});

		if (modal.size.x === window.innerWidth) {
			modal.size.y -= 50;
			modal.size.x -= 20;
		}

		let to;

		if (box.x > 979) {
			to = {
				width: modal.size.x,
				height: modal.size.y,
				left: Math.round((box.x - modal.size.x - modal.options.marginInner.x) / 2),
				top: Math.max(10, Math.round((box.y - modal.size.y - modal.options.marginInner.y) / 2)),
			};
		} else {
			to = { width: box.x - 40, height: box.y - 40 - modal.options.marginInner.y, left: 10, top: 20 };
		}

		Object.assign(modal.win.style, {
			position: 'fixed',
			zIndex: modal.options.zIndex + 2,
			width: to.width + 'px',
			height: to.height + 'px',
			left: to.left + 'px',
			top: to.top + 'px',
		});

		emit('resize', [modal.win]);
		emit('move', [modal.overlay, modal.win]);
	};

	const onResize = () => {
		if (modal.isOpen) {
			layout(modal.lastSize);

			const frame = modal.content.querySelector('iframe');

			if (frame && modal.handler === 'iframe') {
				frame.width = modal.win.style.width.replace('px', '');
				frame.height = modal.win.style.height.replace('px', '');
			}
		}
	};

	const onKey = (event) => {
		if (event.key === 'Escape' || event.key === 'Esc') {
			CMS.modal.close(event);
		}
	};

	const parsers = {
		image: (preset) => (preset || /\.(?:jpe?g|png|gif|webp)$/i.test(modal.url) ? modal.url : false),
		clone(preset) {
			const target = byId(modal.options.target);

			if (target) {
				return target;
			}

			if (modal.element && !modal.element.parentNode) {
				return modal.element;
			}

			const bits = modal.url.match(/#([\w-]+)$/);

			return bits ? document.getElementById(bits[1]) : (preset ? modal.element : false);
		},
		ajax: (preset) => (preset || (modal.url && !/^(?:javascript|#)/i.test(modal.url)) ? modal.url : false),
		iframe: (preset) => (preset || modal.url ? modal.url : false),
		string: () => true,
	};

	parsers.url = parsers.ajax;
	parsers.adopt = parsers.clone;

	const handlers = {
		image(url) {
			const image = new Image();

			image.onload = () => {
				const box = viewport();
				const size = { x: image.width, y: image.height };

				box.x -= modal.options.marginImage.x;
				box.y -= modal.options.marginImage.y;

				for (let i = 2; i > 0; i -= 1) {
					if (size.x > box.x) {
						size.y *= box.x / size.x;
						size.x = box.x;
					} else if (size.y > box.y) {
						size.x *= box.y / size.y;
						size.y = box.y;
					}
				}

				size.x = Math.round(size.x);
				size.y = Math.round(size.y);
				image.width = size.x;
				image.height = size.y;
				image.id = 'sbox-image';
				applyContent(image, size);
			};
			image.onerror = () => onError();
			image.src = url;

			return null;
		},

		clone: (element) => (element ? element.cloneNode(true) : onError()),

		// The element itself, put back where it was when the box closes
		adopt(element) {
			if (!element) {
				return onError();
			}

			if (element.parentNode) {
				modal.adopted = { element, parent: element.parentNode, next: element.nextSibling };
			}

			return element;
		},

		// The page's HTML, its scripts run once it's in, as SqueezeBox did
		ajax(url) {
			const options = Object.assign({ method: 'get' }, modal.options.ajaxOptions || {});

			fetch(url, { method: options.method.toUpperCase(), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then((response) => (response.ok ? response.text() : Promise.reject(response)))
				.then((html) => {
					if (!modal.isOpen && !modal.pending) {
						return;
					}

					applyContent(html);

					if (options.evalScripts !== false) {
						for (const old of modal.content.querySelectorAll('script')) {
							const script = document.createElement('script');

							script.text = old.text;
							old.parentNode.replaceChild(script, old);
						}
					}

					emit('ajax', [html]);
				})
				.catch(() => onError());

			return null;
		},

		iframe(url) {
			const box = viewport();
			const frame = document.createElement('iframe');
			const options = modal.options.iframeOptions || {};

			frame.src = url;
			frame.frameBorder = 0;
			frame.width = box.x > 979 ? modal.options.size.x : box.x - 40;
			frame.height = box.x > 979 ? modal.options.size.y : box.y - 40 - modal.options.marginInner.y;

			Object.keys(options).forEach((name) => frame.setAttribute(name, options[name]));

			if (modal.options.iframePreload) {
				frame.style.display = 'none';
				frame.addEventListener('load', () => {
					frame.style.display = '';
					applyContent(frame);
				});
				modal.content.appendChild(frame);

				return false;
			}

			return frame;
		},

		string: (html) => html,
	};

	handlers.url = handlers.ajax;

	const toggleLoading = (state) => {
		modal.isLoading = state;
		modal.win.classList.toggle('sbox-loading', state);

		if (state) {
			modal.win.setAttribute('aria-busy', 'true');
		} else {
			modal.win.removeAttribute('aria-busy');
		}
	};

	const putBack = () => {
		if (modal.adopted) {
			const { element, parent, next } = modal.adopted;

			parent.insertBefore(element, next && next.parentNode === parent ? next : null);
			modal.adopted = null;
		}
	};

	const trash = () => {
		putBack();

		while (modal.content.firstChild) {
			modal.content.removeChild(modal.content.firstChild);
		}

		modal.element = null;
	};

	const onError = () => {
		setContent('string', modal.options.errorMsg || 'An error occurred');

		return null;
	};

	function applyContent(content, size) {
		if (!modal.isOpen && !modal.pending) {
			return;
		}

		modal.pending = false;
		modal.lastSize = size;

		if (!content) {
			toggleLoading(true);
		} else {
			if (modal.isLoading) {
				toggleLoading(false);
			}

			while (modal.content.firstChild && modal.content.firstChild !== content) {
				modal.content.removeChild(modal.content.firstChild);
			}

			if (typeof content === 'string') {
				modal.content.innerHTML = content;
			} else if (Array.isArray(content)) {
				content.forEach((part) => modal.content.appendChild(part));
			} else if (content.nodeType && content.parentNode !== modal.content) {
				modal.content.appendChild(content);
			}

			emit('update', [modal.content]);
		}

		layout(size);

		if (!modal.isOpen) {
			modal.isOpen = true;
			modal.win.setAttribute('aria-hidden', 'false');
			window.addEventListener('resize', onResize);
			document.addEventListener('keydown', onKey);
			emit('open', [modal.content]);
		}

		emit('show', [modal.win]);
	}

	function setContent(handler, content) {
		if (!handlers[handler]) {
			return false;
		}

		build();
		modal.handler = handler;
		modal.content.className = 'sbox-content-' + handler;
		modal.closeBtn.style.display = modal.options.closeBtn ? '' : 'none';

		if (!modal.overlayShown) {
			modal.overlayShown = true;
			modal.overlay.style.zIndex = modal.options.zIndex;

			if (modal.options.overlay) {
				Object.assign(modal.overlay.style, { width: '100%', height: '100%', opacity: modal.options.overlayOpacity });
				modal.overlay.setAttribute('aria-hidden', 'false');
				document.body.classList.add('body-overlayed');
			}
		}

		modal.pending = true;
		applyContent(handlers[handler](content));

		return CMS.modal;
	}

	// A link's own options: its rel (or another attribute), an object literal evaluated as SqueezeBox did
	const parseOptions = (element, attribute, secure) => {
		const value = element && attribute ? element.getAttribute(attribute) : '';

		if (!value || !/^\s*\{/.test(value)) {
			return null;
		}

		try {
			return secure ? JSON.parse(value) : (new Function('return (' + value + ');'))(); // eslint-disable-line no-new-func
		} catch (error) {
			return null;
		}
	};

	CMS.modal = {
		/**
		 * @param  {string|Element}  subject  A link (its href and rel), or an address
		 * @param  {Object}          options  handler (iframe, image, ajax, adopt, clone, string), size {x, y}, onClose...
		 */
		open(subject, options) {
			if (modal.isOpen) {
				trash();
			}

			modal.element = typeof subject === 'string' ? null : subject || null;
			modal.opener = document.activeElement;
			modal.options = merge(merge({}, modal.presets), options || {});

			const own = modal.element && modal.options.parse ? parseOptions(modal.element, modal.options.parse, modal.options.parseSecure) : null;

			if (own) {
				merge(modal.options, own);
			}

			modal.url = (modal.element ? modal.element.getAttribute('href') : subject) || modal.options.url || '';

			build();
			modal.overlay.className = modal.options.classOverlay || '';
			modal.win.className = ('shadow ' + (modal.options.classWindow || '')).trim();
			decorate();

			const handler = modal.options.handler;

			if (handler && parsers[handler]) {
				return setContent(handler, parsers[handler](true));
			}

			for (const name of ['image', 'clone', 'ajax', 'iframe', 'string']) {
				const content = parsers[name]();

				if (content) {
					return setContent(name, content);
				}
			}

			return false;
		},

		// The open box (SqueezeBox's, if an extension opened that one), and TinyMCE 4's dialog for editor buttons
		close(event) {
			const cancelable = event && typeof event.preventDefault === 'function';

			if (cancelable) {
				event.preventDefault();
			}

			if (modal.isOpen) {
				const closable = modal.options.closable;

				if (cancelable && (typeof closable === 'function' ? !closable.call(CMS.modal, event) : !closable)) {
					return CMS.modal;
				}

				modal.isOpen = false;
				modal.overlayShown = false;
				modal.win.setAttribute('aria-hidden', 'true');
				modal.overlay.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('body-overlayed');
				window.removeEventListener('resize', onResize);
				document.removeEventListener('keydown', onKey);
				emit('close', [modal.content]);
				emit('hide', [modal.win]);
				trash();
				toggleLoading(false);
				modal.options = merge({}, modal.presets);
				decorate();

				if (modal.opener && typeof modal.opener.focus === 'function') {
					modal.opener.focus();
				}

				return CMS.modal;
			}

			if (window.SqueezeBox && window.SqueezeBox !== SqueezeBoxShim && window.SqueezeBox.isOpen) {
				window.SqueezeBox.close();
			} else if (window.tinyMCE && window.tinyMCE.majorVersion < 5 && window.tinyMCE.activeEditor) {
				window.tinyMCE.activeEditor.windowManager.close();
			}

			return CMS.modal;
		},

		/**
		 * Links that open in the box when clicked: a selector (also links added later, such as subform rows), elements
		 *
		 * @param  {string|Element|NodeList|Array}  to       What opens it
		 * @param  {Object}                         options  Their options; parse: 'rel' reads each link's own
		 */
		assign(to, options) {
			options = Object.assign({ parse: 'rel' }, options || {});

			if (typeof to === 'string') {
				modal.selectors.push({ selector: to, options });
			} else {
				list(to).forEach((element) => modal.assigned.set(element, options));
			}

			return CMS.modal;
		},

		// Defaults for every box (SqueezeBox.initialize())
		setDefaults(options) {
			merge(modal.presets, options || {});

			return CMS.modal;
		},

		setContent: (handler, content) => setContent(handler, content),

		resize(size) {
			if (modal.isOpen) {
				layout(size);
			}

			return CMS.modal;
		},

		on(type, listener) {
			type = type.replace(/^on([A-Z])/, (match, first) => first.toLowerCase());
			(modal.listeners[type] = modal.listeners[type] || []).push(listener);

			return CMS.modal;
		},

		off(type, listener) {
			type = type.replace(/^on([A-Z])/, (match, first) => first.toLowerCase());
			modal.listeners[type] = (modal.listeners[type] || []).filter((item) => listener && item !== listener);

			return CMS.modal;
		},

		get isOpen() {
			return modal.isOpen;
		},

		get content() {
			return modal.content;
		},

		get window() {
			return modal.win;
		},
	};

	document.addEventListener('click', (event) => {
		if (event.defaultPrevented || event.button > 0 || !event.target.closest) {
			return;
		}

		let link = null;
		let options = null;

		for (let element = event.target; element && element !== document; element = element.parentNode) {
			if (modal.assigned.has(element)) {
				link = element;
				options = modal.assigned.get(element);
				break;
			}
		}

		if (!link) {
			for (const item of modal.selectors) {
				const found = event.target.closest(item.selector);

				if (found) {
					link = found;
					options = item.options;
					break;
				}
			}
		}

		if (link) {
			event.preventDefault();
			CMS.modal.open(link, options);
		}
	});

	// SqueezeBox's names (MooTools' SqueezeBox, if loaded by an extension, replaces this)
	const SqueezeBoxShim = {
		get presets() {
			return modal.presets;
		},
		get isOpen() {
			return modal.isOpen;
		},
		get options() {
			return modal.options;
		},
		get content() {
			return modal.content;
		},
		get win() {
			return modal.win;
		},
		get url() {
			return modal.url;
		},
		initialize(presets) {
			if (!modal.initialized) {
				modal.initialized = true;
				CMS.modal.setDefaults(presets);
			}

			return SqueezeBoxShim;
		},
		assign(to, options) {
			CMS.modal.assign(to, Object.assign({ parse: false }, options || {}));

			return SqueezeBoxShim;
		},
		open(subject, options) {
			CMS.modal.open(subject, options);

			return SqueezeBoxShim;
		},
		fromElement(element, options) {
			return CMS.modal.open(element, options);
		},
		close(event) {
			CMS.modal.close(event);

			return SqueezeBoxShim;
		},
		setContent(handler, content) {
			setContent(handler, content);

			return SqueezeBoxShim;
		},
		applyContent(content, size) {
			applyContent(content, size);

			return SqueezeBoxShim;
		},
		resize(size) {
			CMS.modal.resize(size);

			return SqueezeBoxShim;
		},
		addEvent(type, listener) {
			CMS.modal.on(type, listener);

			return SqueezeBoxShim;
		},
		removeEvent(type, listener) {
			CMS.modal.off(type, listener);

			return SqueezeBoxShim;
		},
		fireEvent(type, args) {
			emit(type.replace(/^on([A-Z])/, (match, first) => first.toLowerCase()), [].concat(args || []));

			return SqueezeBoxShim;
		},
		extend(properties) {
			return Object.assign(SqueezeBoxShim, properties);
		},
		handlers,
		parsers,
	};

	/* --- Tooltips (MooTools' Tips: title="Title::Text") --- */

	const tip = {
		selectors: [],
		active: null,
		timer: null,
	};

	const tipElement = (options) => {
		const wrap = document.createElement('div');

		wrap.className = ('tip-wrap ' + (options.className || '')).trim();
		wrap.setAttribute('role', 'tooltip');
		wrap.id = 'cms-tooltip';
		wrap.innerHTML = '<div class="tip-top"></div><div class="tip"><div class="tip-title"></div><div class="tip-text"></div></div><div class="tip-bottom"></div>';
		Object.assign(wrap.style, { position: 'absolute', top: '0', left: '0', zIndex: '65600', display: 'none' });
		document.body.appendChild(wrap);

		return wrap;
	};

	const tipPosition = (event) => {
		if (!tip.active) {
			return;
		}

		const { wrap, element, options } = tip.active;
		const offset = Object.assign({ x: 16, y: 16 }, options.offset || {});
		const rect = element.getBoundingClientRect();
		const x = options.fixed || !event || event.pageX === undefined ? rect.left + window.pageXOffset : event.pageX;
		const y = options.fixed || !event || event.pageY === undefined ? rect.bottom + window.pageYOffset : event.pageY;
		const room = document.documentElement.clientWidth + window.pageXOffset;
		let left = x + offset.x;

		if (left + wrap.offsetWidth > room) {
			left = Math.max(window.pageXOffset, x - offset.x - wrap.offsetWidth);
		}

		wrap.style.left = left + 'px';
		wrap.style.top = (y + offset.y) + 'px';
	};

	const tipHide = () => {
		window.clearTimeout(tip.timer);

		if (!tip.active) {
			return;
		}

		const { wrap, element, options } = tip.active;

		tip.timer = window.setTimeout(() => {
			if (typeof options.onHide === 'function') {
				options.onHide.call(element, wrap, element);
			}

			wrap.style.display = 'none';
			element.removeAttribute('aria-describedby');
		}, options.hideDelay === undefined ? 100 : options.hideDelay);
		tip.active = null;
	};

	const tipShow = (element, options, event) => {
		if (tip.active && tip.active.element === element) {
			return;
		}

		tipHide();

		if (!element.hasAttribute('data-tip-title') && element.hasAttribute('title')) {
			const parts = element.getAttribute('title').split('::');

			element.setAttribute('data-tip-title', parts.length > 1 ? parts[0] : '');
			element.setAttribute('data-tip-text', parts.length > 1 ? parts.slice(1).join('::') : parts[0]);
			element.removeAttribute('title');
		}

		const title = element.getAttribute('data-tip-title') || '';
		const text = element.getAttribute('data-tip-text') || '';

		if (!title && !text) {
			return;
		}

		const wrap = document.getElementById('cms-tooltip') || tipElement(options);
		const max = options.maxTitleChars || 50;

		wrap.className = ('tip-wrap ' + (options.className || '')).trim();
		wrap.querySelector('.tip-title').innerHTML = title.length > max ? title.substr(0, max - 1) + '…' : title;
		wrap.querySelector('.tip-text').innerHTML = text;
		window.clearTimeout(tip.timer);
		tip.active = { wrap, element, options };
		element.setAttribute('aria-describedby', 'cms-tooltip');

		tip.timer = window.setTimeout(() => {
			wrap.style.display = 'block';
			tipPosition(event);

			if (typeof options.onShow === 'function') {
				options.onShow.call(element, wrap, element);
			}
		}, options.showDelay === undefined ? 100 : options.showDelay);
	};

	const tipFind = (target) => {
		if (!target || !target.closest) {
			return null;
		}

		for (const item of tip.selectors) {
			const element = target.closest(item.selector);

			if (element) {
				return { element, options: item.options };
			}
		}

		return null;
	};

	CMS.tooltip = {
		/**
		 * Tooltips for elements with title="Title::Text" (or plain text): shown on hover and focus
		 *
		 * @param  {string}  selector  Which elements (also ones added later)
		 * @param  {Object}  options   maxTitleChars, offset {x, y}, showDelay, hideDelay, className, fixed, onShow(tip, element), onHide
		 */
		attach(selector, options) {
			tip.selectors.push({ selector, options: options || {} });

			return CMS.tooltip;
		},

		hide: tipHide,
	};

	document.addEventListener('mouseover', (event) => {
		const found = tipFind(event.target);

		if (found) {
			tipShow(found.element, found.options, event);
		} else if (tip.active && !tip.active.element.contains(event.target)) {
			tipHide();
		}
	});
	document.addEventListener('mousemove', (event) => {
		if (tip.active && !tip.active.options.fixed) {
			tipPosition(event);
		}
	});
	document.addEventListener('focusin', (event) => {
		const found = tipFind(event.target);

		if (found) {
			tipShow(found.element, Object.assign({}, found.options, { fixed: true }));
		}
	});
	document.addEventListener('focusout', tipHide);
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && tip.active) {
			tipHide();
		}
	});

	/* --- Tabs (JHtml tabs.start, what JTabs was) --- */

	const storage = {
		get(name) {
			try {
				return window.localStorage.getItem(name);
			} catch (error) {
				return null;
			}
		},
		set(name, value) {
			try {
				window.localStorage.setItem(name, value);
			} catch (error) {
				// Private windows: the tab isn't remembered
			}
		},
	};

	CMS.tabs = {
		/**
		 * @param  {Element|string}  dlist    The <dl class="tabs">: dt.tabs titles, dd.tabs panes
		 * @param  {Object}          options  display (index), useStorage, titleSelector, descriptionSelector, onActive(title, pane), onBackground
		 */
		create(dlist, options) {
			dlist = byId(dlist);
			options = Object.assign({
				display: null,
				useStorage: true,
				titleSelector: 'dt',
				descriptionSelector: 'dd',
				onActive(title, description) {
					description.style.display = 'block';
					title.classList.add('open');
					title.classList.remove('closed');
				},
				onBackground(title, description) {
					description.style.display = 'none';
					title.classList.add('closed');
					title.classList.remove('open');
				},
			}, options || {});

			const children = (selector) => Array.prototype.filter.call(dlist.children, (child) => child.matches(selector));
			const titles = children(options.titleSelector);
			const descriptions = children(options.descriptionSelector);
			const content = document.createElement('div');
			const storageName = 'jpanetabs_' + dlist.id;
			let display = options.display;

			content.className = 'current';
			dlist.parentNode.insertBefore(content, dlist.nextSibling);

			if (options.useStorage && (display === null || display === undefined)) {
				display = storage.get(storageName);
			}

			display = Math.min(Math.max(parseInt(display, 10) || 0, 0), titles.length - 1);

			const tabs = {
				titles,
				descriptions,
				content,
				display(index) {
					titles.forEach((title, i) => {
						if (i !== index) {
							options.onBackground.call(tabs, title, descriptions[i]);
						}
					});
					options.onActive.call(tabs, titles[index], descriptions[index]);

					if (options.useStorage) {
						storage.set(storageName, index);
					}
				},
			};

			titles.forEach((title, i) => {
				title.style.cursor = 'pointer';
				title.addEventListener('click', () => tabs.display(i));

				if (descriptions[i]) {
					content.appendChild(descriptions[i]);
				}
			});

			if (titles.length) {
				tabs.display(display);
			}

			if (typeof options.initialize === 'function') {
				options.initialize.call(tabs);
			}

			return tabs;
		},
	};

	/* --- Sliders (JHtml sliders.start, what Fx.Accordion was there) --- */

	const animateHeight = (element, open, duration) => {
		const from = element.offsetHeight;

		element.style.overflow = 'hidden';
		element.style.transition = 'none';
		element.style.height = from + 'px';
		element.style.display = '';

		const to = open ? element.scrollHeight : 0;

		if (!duration) {
			element.style.height = open ? '' : '0px';
			element.style.overflow = open ? '' : 'hidden';

			return;
		}

		element.offsetHeight; // eslint-disable-line no-unused-expressions
		element.style.transition = 'height ' + duration + 'ms ease';
		element.style.height = to + 'px';

		const done = () => {
			element.removeEventListener('transitionend', done);

			if (open) {
				element.style.height = '';
				element.style.overflow = '';
			}

			element.style.transition = '';
		};

		element.addEventListener('transitionend', done);
	};

	const cookie = (name, value) => {
		document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; SameSite=Lax';
	};

	CMS.sliders = {
		/**
		 * @param  {string|Array}  togglers  The panes' headings (h3.pane-toggler)
		 * @param  {string|Array}  panes     The panes (div.pane-slider)
		 * @param  {Object}        options   display / show (index open at start, -1 none), alwaysHide, duration, opacity, cookie (name)
		 */
		create(togglers, panes, options) {
			togglers = list(togglers);
			panes = list(panes);
			options = Object.assign({ display: 0, show: null, alwaysHide: true, duration: 300, opacity: false, cookie: '' }, options || {});

			let current = -1;

			const setState = (index, open, instantly) => {
				const toggler = togglers[index];
				const pane = panes[index];

				if (!toggler || !pane) {
					return;
				}

				toggler.classList.toggle('pane-toggler-down', open);
				toggler.classList.toggle('pane-toggler', !open);
				pane.classList.toggle('pane-down', open);
				pane.classList.toggle('pane-hide', !open);
				toggler.setAttribute('aria-expanded', open ? 'true' : 'false');

				if (options.opacity) {
					pane.style.opacity = open ? '1' : '0';
				}

				animateHeight(pane, open, instantly ? 0 : options.duration);
			};

			const sliders = {
				togglers,
				panes,
				display(index, instantly) {
					if (index === current) {
						if (!options.alwaysHide) {
							return;
						}

						index = -1;
					}

					togglers.forEach((toggler, i) => {
						if (i !== index) {
							setState(i, false, instantly);
						}
					});

					if (index >= 0) {
						setState(index, true, instantly);
					}

					current = index;

					if (options.cookie) {
						cookie(options.cookie, current);
					}
				},
			};

			togglers.forEach((toggler, i) => {
				toggler.setAttribute('role', 'button');
				toggler.addEventListener('click', (event) => {
					event.preventDefault();
					sliders.display(i);
				});
			});

			const start = options.show !== null && options.show !== undefined ? parseInt(options.show, 10) : parseInt(options.display, 10);

			togglers.forEach((toggler, i) => setState(i, false, true));

			if (start >= 0) {
				setState(start, true, true);
				current = start;
			}

			return sliders;
		},
	};

	/* --- Password strength (what Form.PasswordStrength was) --- */

	CMS.password = {
		// A score from the length and the kinds of characters used
		strength(value) {
			let pool = 0;

			if (/\d/.test(value)) {
				pool += 10;
			}

			if (/[a-z]/.test(value)) {
				pool += 26;
			}

			if (/[A-Z]/.test(value)) {
				pool += 26;
			}

			if (/[^\da-zA-Z]/.test(value)) {
				pool += 33;
			}

			return pool ? Math.round(value.length * Math.log(pool) / Math.log(2)) : 0;
		},

		/**
		 * A bar under the field, green once the password reaches the threshold; the field gets data-passwordstrength
		 *
		 * @param  {Element|string}  input    The password field
		 * @param  {Object}          options  threshold (66), primer (a password to take the threshold from), height, opacity, bgcolor, onUpdate(input, strength, threshold)
		 */
		meter(input, options) {
			input = byId(input);
			options = Object.assign({ threshold: 66, primer: '', height: 5, opacity: 1, bgcolor: 'transparent' }, options || {});

			if (!input) {
				return null;
			}

			if (options.primer) {
				options.threshold = CMS.password.strength(options.primer);
			}

			const bar = document.createElement('div');
			const meter = document.createElement('div');
			let previous = null;

			Object.assign(bar.style, {
				width: input.offsetWidth + 'px',
				maxWidth: '100%',
				height: options.height + 'px',
				opacity: options.opacity,
				backgroundColor: options.bgcolor,
			});
			bar.className = 'password-strength';
			Object.assign(meter.style, { width: '0', height: '100%', transition: 'width 0.25s, background-color 0.25s' });
			bar.appendChild(meter);
			input.parentNode.insertBefore(bar, input.nextSibling);

			const update = () => {
				const value = input.value;

				if (value === previous) {
					return;
				}

				previous = value;

				const strength = CMS.password.strength(value);
				const ratio = Math.min(Math.max(Math.round(strength / options.threshold * 100) / 100, 0), 1);
				const color = ratio < 0.5 ? 'rgb(255, ' + Math.round(255 * ratio * 2) + ', 0)' : 'rgb(' + Math.round(255 * (1 - ratio) * 2) + ', 255, 0)';

				meter.style.width = (100 * ratio) + '%';
				meter.style.backgroundColor = color;
				input.setAttribute('data-passwordstrength', strength);

				if (typeof options.onUpdate === 'function') {
					options.onUpdate.call(input, input, strength, options.threshold);
				}
			};

			input.addEventListener('keyup', update);
			input.addEventListener('change', update);

			if (input.value) {
				update();
			}

			return { input, bar, update };
		},
	};

	// The list's sidebar (layout joomla.sidebars.submenu): set up by the template's toggleSidebar() (Isis, Prothero); without
	// one its toggle is hidden
	CMS.ready(() => {
		if (!document.getElementById('j-toggle-sidebar-wrapper')) {
			return;
		}

		if (typeof window.toggleSidebar === 'function') {
			window.toggleSidebar(true);
		} else {
			['j-toggle-sidebar-header', 'j-toggle-button-wrapper'].forEach((id) => {
				const element = byId(id);

				if (element) {
					element.style.display = 'none';
				}
			});
		}
	});

	// The password field's layout: a meter (data-password-meter, its threshold) and a lock (a button with
	// data-password-lock, the field's id: Modify enables the field, Cancel empties and locks it again)
	CMS.ready(() => {
		document.querySelectorAll('input[data-password-meter]').forEach((input) => {
			CMS.password.meter(input, { threshold: Number(input.getAttribute('data-password-meter')) || 66 });
		});
	});

	document.addEventListener('click', (event) => {
		const button = event.target.closest ? event.target.closest('[data-password-lock]') : null;
		const input = button ? byId(button.getAttribute('data-password-lock')) : null;

		if (!input) {
			return;
		}

		const unlock = input.disabled;

		button.textContent = button.getAttribute(unlock ? 'data-text-cancel' : 'data-text-modify');
		button.classList.toggle('active', unlock);
		button.setAttribute('aria-pressed', unlock ? 'true' : 'false');
		input.disabled = !unlock;

		if (!unlock) {
			input.value = '';
		}
	});

	/* --- Tree (behavior.tree, what MooTree was) --- */

	// The icons: one file each in a theme that's a folder (media/system/images/tree/<name>.svg), else their order in the theme's
	// sprite (MooTree's), 18px each
	const TREE_ICONS = ['I', 'L', 'Lminus', 'Lplus', 'Rminus', 'Rplus', 'T', 'Tminus', 'Tplus', '_closed', '_doc', '_open', 'minus', 'plus'];
	const TREE_THEME = document.currentScript && /\/js\/global(\.min)?\.js/.test(document.currentScript.src)
		? document.currentScript.src.replace(/\/js\/global(\.min)?\.js.*$/, '/images/tree/') : '/media/system/images/tree/';
	const noop = () => {};

	class TreeNode {
		/**
		 * @param  {Object}  options  text, id, color, open, icon ('_open', '_closed', '_doc' or an 18px image, 'strip.gif#4'),
		 *                            openicon, data, onExpand(state), onSelect(state), onClick(); control and div are set by the tree
		 */
		constructor(options) {
			this.text = options.text;
			this.id = options.id || null;
			this.nodes = [];
			this.parent = null;
			this.last = true;
			this.control = options.control;
			this.selected = false;
			this.color = options.color || null;
			this.data = options.data || {};
			this.onExpand = options.onExpand || noop;
			this.onSelect = options.onSelect || noop;
			this.onClick = options.onClick || noop;
			this.open = !!options.open && options.open !== 'false';
			this.icon = options.icon;
			this.openicon = options.openicon || this.icon;

			if (this.id) {
				this.control.index[this.id] = this;
			}

			const div = (className) => {
				const element = document.createElement('div');

				element.className = className || '';

				return element;
			};

			this.div = { main: div('mooTree_node'), indent: div(), gadget: div(), icon: div(), text: div('mooTree_text'), sub: div() };
			this.div.main.appendChild(this.div.indent);
			this.div.main.appendChild(this.div.gadget);
			this.div.main.appendChild(this.div.icon);
			this.div.main.appendChild(this.div.text);
			this.div.text.tabIndex = 0;
			byId(options.div).appendChild(this.div.main);
			byId(options.div).appendChild(this.div.sub);

			this.div.gadget.addEventListener('click', () => this.toggle());
			[this.div.icon, this.div.text].forEach((element) => element.addEventListener('click', () => this.control.select(this)));
			this.div.text.addEventListener('keydown', (event) => {
				if (event.key === 'Enter') {
					this.control.select(this);
				} else if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
					this.toggle(false, event.key === 'ArrowRight');
				}
			});
		}

		insert(options) {
			options.div = this.div.sub;
			options.control = this.control;

			const node = new TreeNode(options);
			const nodes = this.nodes;

			node.parent = this;

			if (nodes.length) {
				nodes[nodes.length - 1].last = false;
			}

			nodes.push(node);
			node.update();

			if (nodes.length === 1) {
				this.update();
			} else {
				nodes[nodes.length - 2].update(true);
			}

			return node;
		}

		remove() {
			const parent = this.parent;

			this.removeNode();
			parent.update(true);
		}

		removeNode() {
			while (this.nodes.length) {
				this.nodes[this.nodes.length - 1].removeNode();
			}

			delete this.control.index[this.id];
			this.div.main.remove();
			this.div.sub.remove();

			if (this.parent) {
				const siblings = this.parent.nodes;

				siblings.splice(siblings.indexOf(this), 1);

				if (siblings.length) {
					siblings[siblings.length - 1].last = true;
				}
			}
		}

		clear() {
			this.control.disable();

			while (this.nodes.length) {
				this.nodes[this.nodes.length - 1].remove();
			}

			this.control.enable();
		}

		update(recursive, invalidated) {
			let draw = true;

			if (!this.control.enabled) {
				this.invalidated = true;
				draw = false;
			}

			if (invalidated) {
				if (!this.invalidated) {
					draw = false;
				} else {
					this.invalidated = false;
				}
			}

			if (draw) {
				const control = this.control;
				let indent = '';

				this.div.main.className = 'mooTree_node' + (this.selected ? ' mooTree_selected' : '');

				for (let node = this; node.parent; node = node.parent) {
					indent = this.getImg(node.parent.last || !control.grid ? '' : 'I') + indent;
				}

				this.div.indent.innerHTML = indent;
				this.div.text.textContent = this.text;

				if (this.color) {
					this.div.text.style.color = this.color;
				}

				this.div.icon.innerHTML = this.getImg(this.nodes.length
					? (this.open ? (this.openicon || this.icon || '_open') : (this.icon || '_closed'))
					: (this.icon || (control.mode === 'folders' ? '_closed' : '_doc')));
				this.div.gadget.innerHTML = this.getImg((control.grid ? (control.root === this ? (this.nodes.length ? 'R' : '') : (this.last ? 'L' : 'T')) : '')
					+ (this.nodes.length ? (this.open ? 'minus' : 'plus') : ''));
				this.div.sub.style.display = this.open ? 'block' : 'none';

				if (this.nodes.length) {
					this.div.text.setAttribute('aria-expanded', this.open ? 'true' : 'false');
				}
			}

			if (recursive) {
				this.nodes.forEach((node) => node.update(true, invalidated));
			}
		}

		getImg(name) {
			let style = '';

			if (name !== '') {
				let image = this.control.theme;
				let index = TREE_ICONS.indexOf(name);

				if (index === -1) {
					const parts = name.split('#');

					image = parts[0];
					index = parts.length === 2 ? parseInt(parts[1], 10) - 1 : 0;
				}

				style = image === this.control.theme && image.slice(-1) === '/'
					? ' style="background:url(' + encodeURI(image + name + '.svg') + ') no-repeat center"'
					: ' style="background-image:url(' + encodeURI(image) + ');background-position:-' + (index * 18) + 'px 0"';
			}

			return '<div class="mooTree_img"' + style + '></div>';
		}

		toggle(recursive, state) {
			this.open = state === undefined ? !this.open : state;
			this.update();
			this.onExpand(this.open);
			this.control.onExpand(this, this.open);

			if (recursive) {
				this.nodes.forEach((node) => node.toggle(true, this.open));
			}
		}

		select(state) {
			this.selected = state;
			this.update();
			this.onSelect(state);
		}

		// Nodes from an XML document (<node text="..." id="..." load="url">)
		load(url, vars) {
			if (this.loading) {
				return;
			}

			this.loading = true;
			this.toggle(false, true);
			this.clear();
			this.insert(Object.assign({}, this.control.loader));

			fetch(url + (vars ? (url.indexOf('?') === -1 ? '?' : '&') + vars : ''), { credentials: 'same-origin' })
				.then((response) => (response.ok ? response.text() : Promise.reject(response)))
				.then((text) => {
					const xml = new DOMParser().parseFromString(text, 'text/xml');

					this.control.disable();
					this.clear();
					this.importXml(xml.documentElement);
					this.control.enable();
					this.loading = false;
				})
				.catch(() => {
					this.loading = false;
					window.alert('Error loading: ' + this.text);
				});
		}

		importXml(element) {
			for (const child of element.childNodes) {
				if (child.tagName !== 'node') {
					continue;
				}

				const options = { data: {} };

				for (const attribute of child.attributes) {
					if (['text', 'id', 'icon', 'openicon', 'color', 'open'].indexOf(attribute.name) !== -1) {
						options[attribute.name] = attribute.value;
					} else {
						options.data[attribute.name] = attribute.value;
					}
				}

				const node = this.insert(options);

				if (node.data.load) {
					node.open = false;
					node.insert(Object.assign({}, this.control.loader));
					node.onExpand = function onExpand() {
						this.load(this.data.load);
						this.onExpand = noop;
					};
				}

				if (child.childNodes.length) {
					node.importXml(child);
				}
			}
		}
	}

	class Tree {
		/**
		 * @param  {Object}  config   div (where it goes), mode ('folders' or 'files'), grid, theme (a folder of the icons, ending
		 *                            in "/", or a sprite), loader,
		 *                            onExpand(node, state), onSelect(node, state), onClick(node)
		 * @param  {Object}  options  The root node (text, open, icon, data...)
		 */
		constructor(config, options) {
			options.control = this;
			options.div = config.div;
			this.index = {};
			this.enabled = true;
			this.theme = config.theme || TREE_THEME;
			this.loader = config.loader || { icon: 'mootree_loader.gif', text: 'Loading...', color: '#a0a0a0' };
			this.selected = null;
			this.mode = config.mode;
			this.grid = config.grid;
			this.onExpand = config.onExpand || noop;
			this.onSelect = config.onSelect || noop;
			this.onClick = config.onClick || noop;
			this.root = new TreeNode(options);
			this.root.update(true);
		}

		insert(options) {
			options.control = this;

			return this.root.insert(options);
		}

		select(node) {
			this.onClick(node);
			node.onClick();

			if (this.selected === node) {
				return;
			}

			if (this.selected) {
				this.selected.select(false);
				this.onSelect(this.selected, false);
			}

			this.selected = node;
			node.select(true);
			this.onSelect(node, true);
		}

		expand() {
			this.root.toggle(true, true);
		}

		collapse() {
			this.root.toggle(true, false);
		}

		get(id) {
			return this.index[id] || null;
		}

		/**
		 * Makes nodes of a nested ul/li/a list, then removes the list. Each link's href, target, title and name become the
		 * node's data (href also as url); a comment in a link sets node options: <!-- icon:_doc; color:#ff0000 -->
		 */
		adopt(id, parentNode) {
			parentNode = parentNode || this.root;
			this.disable();
			this.adoptList(byId(id), parentNode);
			parentNode.update(true);
			byId(id).remove();
			this.enable();
		}

		adoptList(element, parentNode) {
			for (const item of element.children) {
				if (item.nodeName !== 'LI') {
					continue;
				}

				const options = { text: '', data: {} };
				let comment = '';
				let sub = null;

				for (const child of item.children) {
					if (child.nodeName === 'A') {
						for (const part of child.childNodes) {
							if (part.nodeType === 3) {
								options.text += part.nodeValue;
							} else if (part.nodeType === 8) {
								comment += part.nodeValue;
							}
						}

						['href', 'target', 'title', 'name'].forEach((name) => {
							options.data[name] = child.getAttribute(name);
						});
					} else if (child.nodeName === 'UL') {
						sub = child;
					}
				}

				options.data.url = options.data.href;

				comment.split(';').forEach((bit) => {
					const pieces = bit.trim().split(':');

					if (pieces.length === 2) {
						options[pieces[0].trim()] = pieces[1].trim();
					}
				});

				if (item.id) {
					options.id = 'node_' + item.id;
				}

				const node = parentNode.insert(options);

				if (sub) {
					this.adoptList(sub, node);
				}
			}
		}

		disable() {
			this.enabled = false;
		}

		enable() {
			this.enabled = true;
			this.root.update(true, true);
		}
	}

	CMS.tree = {
		Tree,
		Node: TreeNode,
		icons: TREE_ICONS,
		create: (config, root) => new Tree(config, root),
	};

	/* --- Shift-click ranges of list checkboxes (behavior.multiselect) --- */

	const multiselect = { last: new WeakMap() };

	CMS.multiselect = {
		/**
		 * In the element (a manager's form or table), a shift-click checks or unchecks every checkbox between it and the last
		 * one clicked
		 *
		 * @param  {string|Element}  container  Its id
		 */
		attach(container) {
			container = byId(container);

			if (!container || multiselect.last.has(container)) {
				return;
			}

			multiselect.last.set(container, null);

			container.addEventListener('click', (event) => {
				const current = event.target;

				if (!current.matches || !current.matches('input[type=checkbox]')) {
					return;
				}

				const last = multiselect.last.get(container);
				const boxes = Array.prototype.slice.call(container.querySelectorAll('input[type=checkbox]'));

				if (event.shiftKey && last && boxes.indexOf(last) !== -1) {
					const from = Math.min(boxes.indexOf(last), boxes.indexOf(current));
					const to = Math.max(boxes.indexOf(last), boxes.indexOf(current));

					boxes.slice(from, to + 1).forEach((box) => {
						box.checked = current.checked;
					});

					// The toolbar counts the checked rows
					const form = current.form;

					if (form && form.boxchecked) {
						form.boxchecked.value = boxes.filter((box) => box.checked && /^cb\d+$/.test(box.id)).length;
					}
				}

				multiselect.last.set(container, current);
			});
		},
	};

	/* --- Email addresses cloaked from spambots (JHtml email.cloak) --- */

	/*
	 * The address comes in parts in data attributes (data-cloak-user, data-cloak-domain: a JSON list of the domain's parts;
	 * their vowels as character references, decoded only when the link is written), with the link's text (data-cloak-text,
	 * or data-cloak-text-user and -domain for an address) and data-cloak-link for a link. What the inline script of each
	 * address wrote before 3.18, from the same parts.
	 */
	CMS.cloak = {
		start(container) {
			(container || document).querySelectorAll('[data-cloak]').forEach((span) => {
				const parts = (prefix) => {
					let domain = [];

					try {
						domain = JSON.parse(span.getAttribute('data-cloak-' + prefix + 'domain') || '[]');
					} catch (error) {
						domain = [];
					}

					return span.getAttribute('data-cloak-' + prefix + 'user') + '&#64;' + domain.join('&#46;');
				};
				const address = parts('');
				let text = address;

				if (span.hasAttribute('data-cloak-text-user')) {
					text = parts('text-');
				} else if (span.hasAttribute('data-cloak-text')) {
					text = span.getAttribute('data-cloak-text');
				}

				span.removeAttribute('data-cloak');
				span.innerHTML = span.hasAttribute('data-cloak-link')
					? '<a ' + 'hr' + 'ef=\'' + '&#109;a' + 'i&#108;' + '&#116;o' + ':' + address + '\'>' + text + '</a>'
					: address;
			});
		},
	};

	CMS.ready(() => CMS.cloak.start());

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

	/* --- The help site field's refresh button (index.php?option=com_users&task=profile.gethelpsites) --- */

	document.addEventListener('click', (event) => {
		const button = event.target.closest ? event.target.closest('#helpsite-refresh') : null;

		if (!button) {
			return;
		}

		const select = document.getElementById(button.getAttribute('rel'));
		const showDefault = button.getAttribute('showDefault') === 'true';

		fetch('index.php?option=com_users&task=profile.gethelpsites&format=json', { credentials: 'same-origin' })
			.then((response) => response.json())
			.then((sites) => {
				while (select.firstChild) {
					select.removeChild(select.firstChild);
				}

				sites.forEach((site) => {
					if (site.value !== '' || showDefault) {
						select.appendChild(new Option(site.text, site.value));
					}
				});

				// Chosen redraws the list when told (a jQuery event)
				if (window.jQuery) {
					window.jQuery(select).trigger('liszt:updated').trigger('chosen:updated');
				}
			})
			// No list (the core has no gethelpsites task since Joomla 3): the field keeps its options
			.catch(() => {});
	});

	/* --- The media field (the image picker; layout joomla.form.field.media) --- */

	CMS.mediaField = {
		// Called by the Media Manager's picker in the modal
		insert(value, id) {
			const input = document.getElementById(id);

			if (!input || input.value === value) {
				return;
			}

			input.value = value;
			input.dispatchEvent(new Event('change', { bubbles: true }));

			if (window.jQuery) {
				window.jQuery(input).trigger('change');
			}

			CMS.mediaField.refreshPreview(id);
		},

		refreshPreview(id) {
			const input = document.getElementById(id);
			const image = document.getElementById(id + '_preview');

			if (!input || !image) {
				return;
			}

			const empty = document.getElementById(id + '_preview_empty');
			const box = document.getElementById(id + '_preview_img');

			image.setAttribute('src', input.value ? (input.getAttribute('data-basepath') || '') + input.value : '');

			if (empty) {
				empty.style.display = input.value ? 'none' : '';
			}

			if (box) {
				box.style.display = input.value ? '' : 'none';
			}
		},

		// The preview tooltip (behavior.tooltip's onShow)
		previewTip(tip) {
			for (const image of tip.querySelectorAll('img.media-preview')) {
				const wrap = tip.querySelector('div.tip');

				if (wrap) {
					wrap.style.maxWidth = 'none';
				}

				CMS.mediaField.refreshPreview(image.id.slice(0, -'_preview'.length));
			}
		},

		// The tooltip with the whole image path (behavior.tooltip's onShow)
		imagePathTip(tip, input) {
			const path = document.getElementById('TipImgpath');

			tip.style.maxWidth = 'none';

			if (path) {
				path.textContent = input.value;
			}

			tip.style.display = input.value ? '' : 'none';
		},
	};

	/* --- The Associations tab of edit forms (layout joomla.edit.associations) --- */

	// A change Chosen makes reaches jQuery's listeners only
	const onChange = (element, listener) => {
		if (window.jQuery) {
			window.jQuery(element).on('change', listener);
		} else {
			element.addEventListener('change', listener);
		}
	};

	const associationRows = () => document.querySelectorAll('#associations .control-group');

	// The language a row is for, from its label's for="jform_associations_en_GB_id"
	const associationCode = (row) => {
		const label = row.querySelector('.control-label label');

		return label ? label.getAttribute('for').replace(/_id$/, '') : '';
	};

	CMS.associations = {
		// Hides the row of the item's own language
		hide(formControl, languageCode) {
			for (const row of associationRows()) {
				if (associationCode(row) === formControl + '_associations_' + String(languageCode).replace('-', '_')) {
					row.style.display = 'none';
				}
			}
		},

		// Items for all languages have no associations
		showMessage() {
			const tab = document.getElementById('associations');
			const notice = document.createElement('div');

			for (const row of associationRows()) {
				row.style.display = 'none';
			}

			notice.id = 'associations-notice';
			notice.className = 'alert alert-info';
			notice.textContent = CMS.text.get('JGLOBAL_ASSOC_NOT_POSSIBLE');
			tab.insertBefore(notice, tab.firstChild);
		},

		// The associated items' own associations, filled in through the pickers' callbacks (window[prefix + 'en_GB'])
		inject(result, callbackPrefix) {
			if (!result.success) {
				Joomla.renderMessages({ warning: [CMS.text.get('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED')] });

				return;
			}

			Object.keys(result.data || {}).forEach((language) => {
				const item = result.data[language];

				window[callbackPrefix + language.replace('-', '_')](item.id, item.title, item.catid, null, null, language);
			});

			if (result.message) {
				Joomla.renderMessages({ notice: [result.message] });
			}
		},

		// The "Propagate" button: asks the site for the associations of the item chosen for one language
		propagate(fieldPrefix, callbackPrefix) {
			const form = document.querySelector('form[name="adminForm"]');
			const language = document.getElementById('jform_language');
			const query = new URLSearchParams({
				task: 'ajax.fetchAssociations',
				format: 'json',
				assocId: document.getElementById(fieldPrefix + '_id').value,
				excludeLang: language ? language.value : '',
			});
			const url = form.getAttribute('action') + '&' + CMS.options.get('csrf.token', '') + '=1&' + query.toString();

			fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then((response) => response.json())
				.then((result) => CMS.associations.inject(result, callbackPrefix))
				.catch(() => Joomla.renderMessages({ warning: [CMS.text.get('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED')] }));

			return false;
		},

		init(options) {
			const formControl = options.formControl || 'jform';
			const language = document.getElementById(formControl + '_language');

			if (String(options.hidden) === '1') {
				CMS.associations.showMessage();
			} else if (language) {
				CMS.associations.hide(formControl, language.value);
			}

			if (!language) {
				return;
			}

			// A new language empties the associations (they were for the old one)
			onChange(language, () => {
				let existed = false;
				const notice = document.getElementById('associations-notice');

				Joomla.removeMessages();

				if (notice) {
					notice.remove();
				}

				for (const row of associationRows()) {
					const code = associationCode(row).replace(formControl + '_associations_', '');
					const field = document.getElementById(formControl + '_associations_' + code + '_id');
					const clear = document.getElementById(formControl + '_associations_' + code + '_clear');

					row.style.display = '';
					existed = existed || (field && field.value !== '');

					if (clear) {
						clear.click();
					}
				}

				if (existed) {
					Joomla.renderMessages({ warning: [CMS.text.get('JGLOBAL_ASSOCIATIONS_RESET_WARNING')] });
				}

				if (language.value === '*') {
					CMS.associations.showMessage();
				} else {
					CMS.associations.hide(formControl, language.value);
				}
			});
		},
	};

	/* --- The Permissions field (JFormFieldRules): each change is saved at once --- */

	CMS.permissions = {
		// A parameter of the page's address, false when absent
		urlParam(name) {
			const value = new URLSearchParams(window.location.search).get(name);

			return value === null ? false : value;
		},

		/**
		 * @param  {Element}  select  The permission's list (jform_rules_<action>_<group>)
		 */
		send(select) {
			const icon = document.getElementById('icon_' + select.id);
			const param = CMS.permissions.urlParam;
			const component = param('component');
			const extension = param('extension');
			const option = param('option');
			const view = param('view');
			const contextField = document.getElementById('jform_context');
			const context = contextField ? contextField.value.split('.')[0] : '';
			const titleField = document.getElementById('jform_title');
			let asset = 'not';
			let title = component;

			if (option === 'com_config' && component === false && extension === false) {
				asset = 'root.1';
			} else if (extension === false && view === 'component') {
				asset = component;
			} else if (context) {
				asset = context + (view === 'group' ? '.fieldgroup.' : '.field.') + param('id');
				title = titleField.value;
			} else if (extension !== false && view !== false) {
				asset = extension + '.' + view + '.' + param('id');
				title = titleField.value;
			} else if (extension === false && view !== false) {
				asset = option + '.' + view + '.' + param('id');
				title = titleField.value;
			}

			const id = select.id.replace('jform_rules_', '');
			const split = id.lastIndexOf('_');
			const data = new URLSearchParams({
				comp: asset,
				action: id.substring(0, split),
				rule: id.substring(split + 1),
				value: select.value,
				title,
			});
			const setIcon = (className) => {
				icon.removeAttribute('style');
				icon.setAttribute('class', className);
			};

			icon.removeAttribute('class');
			icon.setAttribute('style', 'background:url(../media/system/images/modal/spinner.svg);display:inline-block;width:16px;height:16px');
			Joomla.removeMessages();

			CMS.request({
				url: document.getElementById('permissions-sliders').getAttribute('data-ajaxuri'),
				method: 'POST',
				data: data.toString(),
				onSuccess(text) {
					let response = {};

					try {
						response = JSON.parse(text);
					} catch (error) {
						setIcon('icon-cancel');
						Joomla.renderMessages(Joomla.ajaxErrorsMessages({ responseText: text, status: 200 }, 'parsererror'));

						return;
					}

					const saved = response.data && response.data.result === true;

					setIcon(saved ? 'icon-save' : 'icon-cancel');

					// The calculated setting, in the next column
					const next = saved ? select.closest('td').nextElementSibling : null;

					if (next) {
						for (const span of next.querySelectorAll('span')) {
							span.className = response.data.class;
							span.innerHTML = response.data.text;
						}
					}

					if (response.messages && typeof response.messages === 'object') {
						Joomla.renderMessages(response.messages);
						window.scrollTo(0, 0);
					}
				},
				onError(xhr) {
					setIcon('icon-cancel');
					Joomla.renderMessages(Joomla.ajaxErrorsMessages(xhr, ''));
					window.scrollTo(0, 0);
				},
			});
		},
	};

	/* --- Front-end editing (modules with .jmoddiv: layout joomla.edit.frontediting_modules) --- */

	/*
	 * Hovering a module the user may edit shows an edit button at its corner (its address and tooltip in data-jmodediturl,
	 * data-target and data-jmodtip); hovering a menu item in a menu module with data-jmenuedittip shows a link to edit the item.
	 * Both stay a moment after the pointer leaves, so it can reach them.
	 */
	const PENCIL = '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.1 1.3a1.5 1.5 0 0 1 2.1 0l.5.5a1.5 1.5 0 0 1 0 2.1l-8.6 8.6-3.3.9.9-3.3 8.4-8.8zM2 14h12v1H2z"/></svg>';

	const editing = { button: null, module: null, popup: null, item: null, timers: {} };

	const later = (name, delay, callback) => {
		window.clearTimeout(editing.timers[name]);
		editing.timers[name] = delay === null ? null : window.setTimeout(callback, delay);
	};

	const removeEditButton = () => {
		if (editing.button) {
			editing.button.remove();
			editing.button = editing.module = null;
		}
	};

	const removeMenuEdit = () => {
		if (editing.popup) {
			editing.popup.remove();
			editing.popup = editing.item = null;
		}
	};

	CMS.frontEditing = {
		showModule(module) {
			later('module', null);

			if (editing.module === module) {
				return;
			}

			removeEditButton();

			const button = document.createElement('a');
			const rect = module.getBoundingClientRect();
			const rtl = document.documentElement.getAttribute('dir') === 'rtl';

			button.className = 'btn jmodedit cms-frontedit';
			button.href = module.getAttribute('data-jmodediturl');
			button.target = module.getAttribute('data-target') || '_self';
			button.innerHTML = PENCIL;
			button.setAttribute('data-tip-title', '');
			button.setAttribute('data-tip-text', module.getAttribute('data-jmodtip') || '');
			button.setAttribute('aria-label', (module.getAttribute('data-jmodtip') || '').replace(/<[^>]*>/g, ' ').trim());
			button.style.top = (rect.top + window.pageYOffset) + 'px';
			document.body.appendChild(button);
			button.style.left = (rtl ? rect.left : rect.right - button.offsetWidth) + window.pageXOffset + 'px';
			module.classList.add('jmodinside');
			editing.button = button;
			editing.module = module;
		},

		showMenuItem(item) {
			later('menu', null);

			if (editing.item === item) {
				return;
			}

			removeMenuEdit();

			const ids = /\bitem-(\d+)\b/.exec(item.className);
			const module = item.closest('.jmoddiv');

			if (!ids || !module) {
				return;
			}

			const url = (module.getAttribute('data-jmodediturl') || '').replace(/\/index.php\?option=com_config&controller=config.display.modules([^\d]+).+$/,
				'/administrator/index.php?option=com_menus&view=item&layout=edit$1' + ids[1]);
			const popup = document.createElement('div');
			const link = document.createElement('a');
			const rect = item.getBoundingClientRect();

			popup.className = 'cms-frontedit-popup';
			link.className = 'btn jfedit-menu cms-frontedit';
			link.href = url;
			link.target = '_blank';
			link.innerHTML = PENCIL;
			link.setAttribute('data-tip-title', '');
			link.setAttribute('data-tip-text', (module.getAttribute('data-jmenuedittip') || '').replace('%s', ids[1]));
			link.setAttribute('aria-label', link.getAttribute('data-tip-text').replace(/<[^>]*>/g, ' ').trim());
			popup.appendChild(link);
			popup.style.top = (rect.bottom + window.pageYOffset) + 'px';
			popup.style.left = (rect.left + window.pageXOffset) + 'px';
			document.body.appendChild(popup);
			editing.popup = popup;
			editing.item = item;
		},
	};

	const MENU_ITEMS = '.jmoddiv[data-jmenuedittip] .nav li, .jmoddiv[data-jmenuedittip].nav li';

	document.addEventListener('mouseover', (event) => {
		const target = event.target;

		if (!target.closest) {
			return;
		}

		if (target.closest('.jmodedit')) {
			later('module', null);
		} else {
			const module = target.closest('.jmoddiv[data-jmodediturl]');

			if (module) {
				CMS.frontEditing.showModule(module);
			} else if (editing.button) {
				later('module', 500, removeEditButton);
			}
		}

		if (target.closest('.cms-frontedit-popup')) {
			later('menu', null);
		} else {
			const item = target.closest(MENU_ITEMS);

			if (item) {
				CMS.frontEditing.showMenuItem(item);
			} else if (editing.popup) {
				later('menu', 1500, removeMenuEdit);
			}
		}
	});

	// The buttons' tooltips: the module, its position, the menu item
	CMS.tooltip.attach('.cms-frontedit', { fixed: true, className: 'cms-frontedit-tip' });

	/* --- Site or Administrator tabs of lists (layout joomla.searchtools.default.client) --- */

	// A tab sets the list's hidden client_id field and fires its change: the field's own onchange clears the other
	// filters where they differ per client, and submits
	document.addEventListener('click', (event) => {
		const tab = event.target.closest ? event.target.closest('.js-stools-client-tab') : null;

		if (!tab) {
			return;
		}

		event.preventDefault();

		const field = document.getElementById(tab.getAttribute('data-target'));

		if (!field || tab.hasAttribute('aria-current')) {
			return;
		}

		field.value = tab.getAttribute('data-value');
		field.dispatchEvent(new Event('change', { bubbles: true }));

		// Fields without an onchange of their own
		if (!field.getAttribute('onchange') && field.form) {
			field.form.submit();
		}
	});

	/* --- The user field (layout joomla.form.field.user: the list of users in the CMS's modal; what fielduser.js was) --- */

	CMS.userField = {
		/**
		 * Opens the list of users for a field (its wrapper's data-url, completed with the field's id)
		 *
		 * @param  {HTMLElement}  wrapper  .field-user-wrapper
		 */
		open(wrapper) {
			const name = wrapper.querySelector('.field-user-input-name');
			const url = (wrapper.getAttribute('data-url') || '').replace('{field-user-id}', encodeURIComponent(name ? name.id : ''));

			if (url) {
				CMS.modal.open(url, {
					handler: 'iframe',
					size: { x: Math.min(1400, window.innerWidth - 80), y: Math.min(900, window.innerHeight - 140) },
				});
			}
		},

		/**
		 * Sets a field from a link of the list (data-user-value, data-user-name, data-user-field: the field's id)
		 *
		 * @param  {HTMLElement}  link
		 */
		select(link) {
			const fieldId = link.getAttribute('data-user-field');
			const value = document.getElementById(fieldId + '_id');
			const name = document.getElementById(fieldId);

			if (!value) {
				return;
			}

			value.value = link.getAttribute('data-user-value');
			value.dispatchEvent(new Event('change', { bubbles: true }));

			if (name) {
				name.value = link.getAttribute('data-user-name') || value.value;
				name.dispatchEvent(new Event('change', { bubbles: true }));
			}

			// The field's own onchange (data-onchange: inputs that are hidden have no change events of their own)
			const onchange = value.getAttribute('data-onchange');

			if (onchange) {
				new Function(onchange).call(value);
			}

			CMS.modal.close();
		},
	};

	// The fields' buttons (also of fields written for fielduser.js, whose Bootstrap modal is no longer used)
	document.addEventListener('click', (event) => {
		const button = event.target.closest ? event.target.closest('.field-user-wrapper .button-select') : null;

		if (button) {
			event.preventDefault();
			CMS.userField.open(button.closest('.field-user-wrapper'));
		}
	});

	// The list's links call the page that opened it
	if (!window.jSelectUser) {
		window.jSelectUser = (link) => CMS.userField.select(link);
	}

	/*
	 * --- The item pickers (layout joomla.form.field.modal: the fields Modal_Article, Modal_Category, Modal_Contact,
	 * Modal_Menu and Modal_Newsfeed; what modal-fields.js was) ---
	 */

	CMS.modalField = {
		/**
		 * Sets a field's item (without an id: clears it) and shows the buttons which fit
		 *
		 * @param  {string}  fieldId  The field's id (its parts are <id>_id, <id>_name, <id>_select...)
		 * @param  {string}  id
		 * @param  {string}  title
		 */
		set(fieldId, id, title) {
			const value = byId(fieldId + '_id');
			const name = byId(fieldId + '_name');
			const shown = { select: !id, new: !id, edit: Boolean(id), clear: Boolean(id), propagate: Boolean(id) };

			if (!value) {
				return false;
			}

			value.value = id || '';

			if (name) {
				name.value = id ? title || '' : value.getAttribute('data-text') || '';
			}

			Object.keys(shown).forEach((action) => {
				const button = byId(fieldId + '_' + action);

				if (button) {
					button.classList.toggle('hidden', !shown[action]);
				}
			});

			value.dispatchEvent(new Event('change', { bubbles: true }));

			if (value.getAttribute('data-required') === '1' && document.formvalidator) {
				document.formvalidator.validate(value);

				if (name) {
					document.formvalidator.validate(name);
				}
			}

			return false;
		},

		// The list's choice: sets the field and closes the list
		select(fieldId, id, title) {
			CMS.modalField.set(fieldId, id, title);

			if (CMS.modal.isOpen) {
				CMS.modal.close();
			}

			return false;
		},

		/**
		 * The function the field's list calls (data-function, e.g. jSelectArticle_<id>), which Propagate also calls for
		 * each language's field (its data-propagate prefix + en_GB)
		 *
		 * @param  {HTMLElement}  wrapper  The field's [data-modal-field]
		 */
		register(wrapper) {
			const fieldId = wrapper.getAttribute('data-modal-field');
			const name = wrapper.getAttribute('data-function') || '';

			if (/^[\w$]+$/.test(name) && typeof window[name] !== 'function') {
				window[name] = (id, title) => CMS.modalField.select(fieldId, id, title);
			}
		},

		/**
		 * Opens the list (select) or the item's form (new, edit: with Close, Save and Apply below it) at the pickers' size
		 *
		 * @param  {HTMLElement}  wrapper  The field's [data-modal-field]
		 * @param  {string}       action   select, new or edit
		 */
		open(wrapper, action) {
			const value = byId(wrapper.getAttribute('data-modal-field') + '_id');
			const url = (wrapper.getAttribute('data-url-' + action) || '').replace('{id}', encodeURIComponent(value ? value.value : ''));
			const options = {
				handler: 'iframe',
				size: { x: Math.min(1400, window.innerWidth - 80), y: Math.min(900, window.innerHeight - 140) },
				title: wrapper.getAttribute('data-title-' + action) || '',
			};

			if (!url) {
				return;
			}

			CMS.modalField.register(wrapper);

			// The form closes only through its buttons, so the item is checked in again
			if (action !== 'select') {
				const run = (task) => (event, button) => CMS.modalField.edit(wrapper, task, button);

				Object.assign(options, {
					closable: false,
					closeBtn: false,
					buttons: [
						{ text: wrapper.getAttribute('data-close'), className: 'btn', onClick: run('cancel') },
						{ text: wrapper.getAttribute('data-save'), className: 'btn btn-primary', onClick: run('save') },
						{ text: wrapper.getAttribute('data-apply'), className: 'btn btn-success', onClick: run('apply') },
					],
				});
			}

			CMS.modal.open(url, options);
		},

		// A button below the item's form
		edit(wrapper, task, button) {
			const frame = CMS.modal.content ? CMS.modal.content.querySelector('iframe') : null;

			return CMS.modalField.submit(frame, {
				fieldId: wrapper.getAttribute('data-modal-field'),
				task,
				prefix: wrapper.getAttribute('data-task'),
				form: wrapper.getAttribute('data-form'),
				idField: wrapper.getAttribute('data-id-field') || 'jform_id',
				titleField: wrapper.getAttribute('data-title-field') || 'jform_title',
				buttons: button ? Array.prototype.slice.call(button.parentNode.querySelectorAll('button')) : [],
			}, () => CMS.modal.close());
		},

		/**
		 * Runs a task of the form in an item's iframe: cancel (checks the item in, then done()), apply (saves, and sets
		 * the field from the saved item's id and title), save (apply, then cancel)
		 *
		 * @param  {HTMLIFrameElement}  frame
		 * @param  {Object}             options  fieldId, task, prefix (article), form (its id), idField, titleField, buttons
		 * @param  {Function}           done     Closes the box
		 */
		submit(frame, options, done) {
			let page = null;

			try {
				page = frame ? frame.contentDocument : null;
			} catch (error) {
				page = null;
			}

			const win = page ? frame.contentWindow : null;
			const busy = (state) => (options.buttons || []).forEach((item) => {
				item.disabled = state;
			});

			// A page without the form (an error): nothing to save or check in
			if (!win || !win.Joomla || typeof win.Joomla.submitbutton !== 'function') {
				if (options.task === 'cancel') {
					done();
				}

				return false;
			}

			if (options.task === 'cancel') {
				busy(true);
				frame.addEventListener('load', () => {
					busy(false);
					done();
				}, { once: true });
				win.Joomla.submitbutton(options.prefix + '.cancel');

				return false;
			}

			const form = page.getElementById(options.form) || page.querySelector('form[name="adminForm"]');

			if (form && page.formvalidator && !page.formvalidator.isValid(form)) {
				return false;
			}

			busy(true);
			frame.addEventListener('load', () => {
				const saved = frame.contentDocument;
				const id = saved ? saved.getElementById(options.idField) : null;
				const title = saved ? saved.getElementById(options.titleField) : null;

				busy(false);

				// Not saved (the page shows why): the form stays open
				if (!id || id.value === '' || id.value === '0') {
					return;
				}

				CMS.modalField.set(options.fieldId, id.value, title ? title.value : '');

				if (options.task === 'save') {
					CMS.modalField.submit(frame, Object.assign({}, options, { task: 'cancel' }), done);
				}
			}, { once: true });

			// Save is apply first, as saving redirects to the list: the saved item's id is read from its form
			win.Joomla.submitbutton(options.prefix + '.apply');

			return false;
		},
	};

	document.addEventListener('click', (event) => {
		const button = event.target.closest ? event.target.closest('[data-modal-field] [data-modal-action]') : null;

		if (!button || button.disabled) {
			return;
		}

		const wrapper = button.closest('[data-modal-field]');
		const fieldId = wrapper.getAttribute('data-modal-field');
		const action = button.getAttribute('data-modal-action');

		event.preventDefault();

		if (action === 'clear') {
			CMS.modalField.set(fieldId);
		} else if (action === 'propagate') {
			document.querySelectorAll('[data-modal-field]').forEach(CMS.modalField.register);
			CMS.associations.propagate(fieldId, wrapper.getAttribute('data-propagate'));
		} else {
			CMS.modalField.open(wrapper, action);
		}
	});

	CMS.ready(() => document.querySelectorAll('[data-modal-field]').forEach(CMS.modalField.register));

	// modal-fields.js' functions, which item lists and extensions' fields call
	window.processModalParent = (fieldPrefix, id, title) => CMS.modalField.set(fieldPrefix, id, title);

	window.processModalSelect = (itemType, fieldPrefix, id, title) => {
		const box = document.getElementById('ModalSelect' + itemType + '_' + fieldPrefix);

		CMS.modalField.select(fieldPrefix, id, title);

		// A field of an extension which still writes its own Bootstrap modals
		if (box && window.jQuery && window.jQuery.fn.modal) {
			window.jQuery(box).modal('hide');
		}

		return false;
	};

	window.processModalEdit = (element, fieldPrefix, action, itemType, task, formId, idFieldId, titleFieldId) => {
		const box = element.closest('.modal');
		const frame = box ? box.querySelector('iframe') : (CMS.modal.content && CMS.modal.content.querySelector('iframe'));

		return CMS.modalField.submit(frame, {
			fieldId: fieldPrefix,
			task,
			prefix: itemType.toLowerCase(),
			form: formId || itemType.toLowerCase() + '-form',
			idField: idFieldId || 'jform_id',
			titleField: titleFieldId || 'jform_title',
			buttons: [],
		}, () => {
			if (box && window.jQuery && window.jQuery.fn.modal) {
				window.jQuery(box).modal('hide');
			} else {
				CMS.modal.close();
			}
		});
	};

	/* --- The module ordering field (layout joomla.form.field.moduleorder: the places in the chosen position; what moduleorder.js was) --- */

	CMS.moduleOrder = {
		// The field naming the position (data-linked-field, else data-position-element, else jform_position)
		position(field) {
			return byId(field.getAttribute('data-linked-field') || '')
				|| byId(field.getAttribute('data-position-element') || '')
				|| byId('jform_position');
		},

		/**
		 * Asks for the modules of the position chosen and writes the list of places in it
		 *
		 * @param  {HTMLElement}  field  .module-ajax-ordering
		 */
		load(field) {
			const position = CMS.moduleOrder.position(field);
			const query = new URLSearchParams({
				client_id: field.getAttribute('data-client-id') || '',
				module_id: field.getAttribute('data-module-id') || '',
				position: position ? position.value : '',
			});
			const failed = (messages) => {
				CMS.messages.render(messages);
				window.scrollTo(0, 0);
			};

			fetch(field.getAttribute('data-url') + '&' + query.toString(), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then((response) => response.text().then((text) => ({ response, text })))
				.then(({ response, text }) => {
					let result;

					try {
						result = JSON.parse(text);
					} catch (error) {
						failed(CMS.messages.fromAjaxError({ status: response.status, statusText: response.statusText, responseText: text }, response.ok ? 'parsererror' : ''));

						return;
					}

					if (Array.isArray(result.data) && result.data.length) {
						CMS.moduleOrder.write(field, result.data);
					}

					// Messages come only with errors
					if (result.messages && typeof result.messages === 'object') {
						failed(result.messages);
					}
				}, () => failed(CMS.messages.fromAjaxError({ status: 0, statusText: '' }, '')));
		},

		/**
		 * Writes the list: lines "position,place,title" (the title escaped for HTML, and may hold commas)
		 *
		 * @param  {HTMLElement}  field
		 * @param  {string[]}     lines
		 */
		write(field, lines) {
			const id = field.id + '_1';
			const ordering = String(field.getAttribute('data-ordering'));
			const previous = document.getElementById(id);
			const select = document.createElement('select');
			const text = (html) => new DOMParser().parseFromString(html, 'text/html').documentElement.textContent;

			if (previous) {
				const chosen = CMS.chosen ? CMS.chosen.get(previous) : null;

				if (chosen) {
					chosen.destroy();
				}

				previous.remove();
			}

			select.id = id;
			select.name = field.getAttribute('data-name') || '';

			lines.forEach((line) => {
				const parts = String(line).split(',');
				const option = document.createElement('option');

				option.value = parts[1];
				option.textContent = text(parts.slice(2).join(','));
				option.selected = parts[1] === ordering;
				select.appendChild(option);
			});

			field.appendChild(select);

			// Searchable as the page's other lists (the administrator's every list: script options system.chosen)
			if (CMS.chosen) {
				CMS.chosen.attach(select, (CMS.options.get('system.chosen') || {}).select || {});
			}
		},
	};

	CMS.ready(() => {
		document.querySelectorAll('.module-ajax-ordering').forEach((field) => {
			const position = CMS.moduleOrder.position(field);

			CMS.moduleOrder.load(field);

			// The position list is Chosen's (with custom positions), whose changes reach jQuery's listeners only
			if (position) {
				onChange(position, () => CMS.moduleOrder.load(field));
			}
		});
	});

	/*
	 * --- Sorting items by dragging them (CMS.sortable; subforms and the Repeatable field use it through sortRows(), and
	 * jquery.compat.js' jQuery.fn.sortable() for extensions; what jQuery UI's sortable did) ---
	 *
	 * Pointer events, so mouse, pen and touch: a drag starts after the pointer moved 3 pixels (a click on a link or a button
	 * in an item still works), a placeholder shows where the item will go and the item moves once, when released (moving it
	 * while dragging reloaded the iframes in it, an editor's, every time it passed one). Items in a column go above or below
	 * an item's middle; items side by side (a grid, floats, inline blocks, axis "x") before or after its centre. A
	 * placeholder of the item's size (side by side, a placeholder class, forcePlaceholderSize) takes the item's place while
	 * the item follows the pointer, as jQuery UI's did; otherwise the item stays and a line shows the place. A focused
	 * handle moves its item with the arrow keys.
	 */
	const sortItems = (container, settings) => {
		const s = settings;
		let placeholder = null;

		const matches = (element) => (typeof s.items === 'function' ? s.items(element) : (s.items ? element.matches(s.items) : element.parentNode === container));
		const isItem = (element) => element && element.nodeType === 1 && element !== placeholder && container.contains(element) && element !== container && matches(element);
		const itemsOf = (parent) => Array.prototype.filter.call(parent.children, isItem);
		const closestItem = (node) => {
			while (node && node !== container) {
				if (isItem(node)) {
					return node;
				}

				node = node.parentNode;
			}

			return null;
		};
		const ownItem = (target) => {
			const handle = s.handle ? (target.closest ? target.closest(s.handle) : null) : null;
			const item = closestItem(s.handle ? handle : target);

			if (!item || (s.handle && !item.contains(handle)) || (s.cancel && target.closest && target.closest(s.cancel) && item.contains(target.closest(s.cancel)))) {
				return null;
			}

			return { handle: handle || item, item };
		};
		const isRtl = (element) => window.getComputedStyle(element).direction === 'rtl';
		const sideBySide = (items) => s.axis === 'x' || (s.axis !== 'y' && items.some((item, index) => {
			const next = items[index + 1];

			if (!next) {
				return false;
			}

			const a = item.getBoundingClientRect();
			const b = next.getBoundingClientRect();

			return Math.abs(a.top - b.top) < Math.min(a.height, b.height) / 2 && a.left !== b.left;
		}));
		const makePlaceholder = (item, inline) => {
			const tag = item.tagName === 'TR' ? 'tr' : (item.tagName === 'LI' ? 'li' : 'div');
			const element = document.createElement(tag);
			const style = window.getComputedStyle(item);

			if (tag === 'tr') {
				const cell = document.createElement('td');

				cell.colSpan = item.cells.length;
				element.appendChild(cell);
			}

			element.className = s.placeholder ? 'ui-sortable-placeholder ' + s.placeholder : 'cms-sort-placeholder' + (inline ? ' is-inline' : '');

			// Side by side, or when asked for (forcePlaceholderSize), it has the item's size; a placeholder class sizes its own
			if ((inline && !s.placeholder) || s.forcePlaceholderSize) {
				element.style.width = item.offsetWidth + 'px';
				element.style.height = item.offsetHeight + 'px';
				element.style.margin = style.margin;

				if (inline) {
					element.style.display = style.display === 'inline' ? 'inline-block' : style.display;
					element.style.float = style.float;
				}
			}

			return element;
		};
		const call = (name, item, changed) => {
			if (s[name]) {
				s[name](item, placeholder, changed);
			}
		};

		const onPointerDown = (event) => {
			const found = event.button > 0 || s.disabled ? null : ownItem(event.target);

			if (!found) {
				return;
			}

			const { handle, item } = found;
			const startX = event.clientX;
			const startY = event.clientY;
			let started = false;
			let inline = false;
			let rtl = false;
			let lifted = null;

			event.preventDefault();

			// The item out of the flow, following the pointer (fixed, at the size it had); a row's cells keep their widths
			const lift = (x, y) => {
				const box = item.getBoundingClientRect();
				const cells = item.tagName === 'TR' ? Array.prototype.slice.call(item.cells) : [];

				lifted = { x: x - box.left, y: y - box.top, style: item.getAttribute('style'), cells: cells.map((cell) => [cell, cell.getAttribute('style'), cell.getBoundingClientRect().width]) };
				lifted.cells.forEach(([cell, , width]) => {
					cell.style.width = width + 'px';
					cell.style.boxSizing = 'border-box';
				});
				Object.assign(item.style, {
					position: 'fixed', left: box.left + 'px', top: box.top + 'px', width: box.width + 'px', height: box.height + 'px', margin: '0',
					boxSizing: 'border-box', zIndex: '10000', pointerEvents: 'none',
				});
			};
			const restore = (element, style) => {
				if (style === null) {
					element.removeAttribute('style');
				} else {
					element.setAttribute('style', style);
				}
			};

			const move = (moveEvent) => {
				const x = moveEvent.clientX;
				const y = moveEvent.clientY;
				let before = null;

				if (!started) {
					if (Math.abs(x - startX) < 3 && Math.abs(y - startY) < 3) {
						return;
					}

					started = true;
					inline = sideBySide(itemsOf(item.parentNode));
					rtl = isRtl(item.parentNode);
					placeholder = makePlaceholder(item, inline);
					item.classList.add('cms-sort-dragging');

					if (inline || s.placeholder || s.forcePlaceholderSize) {
						item.parentNode.insertBefore(placeholder, item);
						lift(startX, startY);
					} else {
						try {
							handle.setPointerCapture(moveEvent.pointerId);
						} catch (error) {
							// The pointer is gone already
						}
					}

					call('onStart', item);
				}

				if (lifted) {
					item.style.left = (x - lifted.x) + 'px';
					item.style.top = (y - lifted.y) + 'px';
				}

				const others = itemsOf(item.parentNode).filter((other) => other !== item);

				for (const other of others) {
					const box = other.getBoundingClientRect();
					const beforeCentre = rtl ? x > box.left + box.width / 2 : x < box.left + box.width / 2;

					if (inline ? (y < box.top || (y <= box.bottom && beforeCentre)) : y < box.top + box.height / 2) {
						before = other;
						break;
					}
				}

				// After the last item: right after it (a subform's <template> may follow the rows)
				if (!before) {
					const last = others.length ? others[others.length - 1] : item;

					before = last === item ? item.nextSibling : last.nextSibling;
				}

				if (before !== placeholder && (placeholder.nextSibling !== before || placeholder.parentNode !== item.parentNode)) {
					item.parentNode.insertBefore(placeholder, before);
					call('onChange', item);
				}

				// Near the window's edges the page scrolls
				if (y < 40) {
					window.scrollBy(0, -20);
				} else if (y > window.innerHeight - 40) {
					window.scrollBy(0, 20);
				}
			};

			const stop = () => {
				document.removeEventListener('pointermove', move);
				document.removeEventListener('pointerup', stop);
				document.removeEventListener('pointercancel', stop);

				if (!started) {
					return;
				}

				let changed = false;

				item.classList.remove('cms-sort-dragging');

				if (lifted) {
					restore(item, lifted.style);
					lifted.cells.forEach(([cell, style]) => restore(cell, style));
					lifted = null;
				}

				if (placeholder.parentNode) {
					changed = placeholder.nextElementSibling !== item && placeholder.previousElementSibling !== item;

					if (changed) {
						placeholder.parentNode.insertBefore(item, placeholder);
					}

					placeholder.remove();
				}

				// The click that ends a drag opens nothing
				const swallow = (clickEvent) => {
					clickEvent.preventDefault();
					clickEvent.stopPropagation();
				};

				window.addEventListener('click', swallow, true);
				setTimeout(() => window.removeEventListener('click', swallow, true), 0);

				if (changed) {
					call('onMove', item);
				}

				call('onStop', item, changed);
				placeholder = null;
			};

			document.addEventListener('pointermove', move);
			document.addEventListener('pointerup', stop);
			document.addEventListener('pointercancel', stop);
		};

		// A touch on a handle drags, never pans the page (touch-action alone fails on inline handles); items without a
		// handle of their own leave touches to the page, or a list could never be scrolled past
		const onTouchStart = (event) => {
			if (s.handle && !s.disabled && ownItem(event.target)) {
				event.preventDefault();
			}
		};

		const onKeyDown = (event) => {
			const keys = { ArrowUp: -1, ArrowDown: 1 };

			if (s.horizontalKeys) {
				keys.ArrowLeft = -1;
				keys.ArrowRight = 1;
			}

			const found = keys[event.key] && !s.disabled && s.handle ? ownItem(event.target) : null;

			if (!found) {
				return;
			}

			const { handle, item } = found;
			const all = itemsOf(item.parentNode);
			const index = all.indexOf(item);
			const step = (event.key === 'ArrowLeft' || event.key === 'ArrowRight') && isRtl(item.parentNode) ? -keys[event.key] : keys[event.key];

			event.preventDefault();

			if (step < 0 && index > 0) {
				item.parentNode.insertBefore(item, all[index - 1]);
			} else if (step > 0 && index < all.length - 1) {
				item.parentNode.insertBefore(item, all[index + 1].nextSibling);
			} else {
				return;
			}

			handle.focus();
			call('onMove', item);
			call('onStop', item, true);
		};

		container.addEventListener('pointerdown', onPointerDown);
		container.addEventListener('touchstart', onTouchStart, { passive: false });
		container.addEventListener('keydown', onKeyDown);

		return {
			options: s,
			items: () => Array.prototype.filter.call(container.querySelectorAll('*'), isItem),
			destroy() {
				container.removeEventListener('pointerdown', onPointerDown);
				container.removeEventListener('touchstart', onTouchStart, { passive: false });
				container.removeEventListener('keydown', onKeyDown);
			},
		};
	};

	CMS.sortable = {
		/**
		 * @param  {HTMLElement|string}  container  The element whose items are sorted (or a selector)
		 * @param  {Object}              options    items (a selector or a function(element); the container's children
		 *                                          by default), handle (a selector; the item itself by default), cancel (a
		 *                                          selector where no drag starts; form controls by default), axis (x, y),
		 *                                          placeholder (a class name instead of the CMS's own placeholder),
		 *                                          forcePlaceholderSize, disabled (read on every drag, so it can change),
		 *                                          onStart(item, placeholder), onChange(item, placeholder),
		 *                                          onMove(item) when its place changed, onStop(item, changed)
		 *
		 * @return  {Object|null}  options (live), items(), destroy()
		 */
		attach(container, options) {
			container = typeof container === 'string' ? document.querySelector(container) : (container && container.jquery ? container[0] : container);

			if (!container) {
				return null;
			}

			return sortItems(container, Object.assign({ cancel: 'input, textarea, button, select, option', horizontalKeys: true }, options || {}));
		},
	};

	// Rows of subforms and the Repeatable field: jQuery UI's "sortstop" goes out through jQuery when it's there (TinyMCE 4
	// restarts the editors of a moved row on it)
	const sortRows = (container, rowSelector, handleSelector, onMove) => sortItems(container, {
		items: rowSelector,
		handle: handleSelector,
		onMove(row) {
			if (window.jQuery) {
				window.jQuery(row).trigger('sortstop', [{ item: window.jQuery(row) }]);
			}

			if (onMove) {
				onMove(row);
			}
		},
	});

	// Handles written as links without an address can't be focused: they get a tab stop
	const focusableHandles = (root, handleSelector) => {
		root.querySelectorAll(handleSelector).forEach((handle) => {
			if (!handle.hasAttribute('href') && !handle.hasAttribute('tabindex')) {
				handle.tabIndex = 0;
				handle.setAttribute('role', 'button');
			}
		});
	};

	/*
	 * Events of the subform and the Repeatable field: through jQuery when it's there, with the row as its argument
	 * (Chosen, tooltips, the editors and extensions listen so), natively otherwise (detail.row); and always natively as
	 * cms:<type>, for listeners which don't want to depend on jQuery.
	 */
	const emitRowEvent = (target, type, row) => {
		if (window.jQuery) {
			window.jQuery(target).trigger(type, row ? [row] : []);
		} else {
			target.dispatchEvent(new CustomEvent(type, { bubbles: true, detail: { row } }));
		}

		target.dispatchEvent(new CustomEvent('cms:' + type, { bubbles: true, detail: { row } }));
	};

	// A selector's value inside quotes
	const quoted = (value) => String(value).replace(/["\\]/g, '\\$&');

	// The old media field (a link calling jInsertFieldValue()) names its input: a copied row's must name the new one
	const fixOldMediaFields = (row) => {
		row.querySelectorAll('a[onclick*="jInsertFieldValue"]').forEach((clear) => {
			const input = Array.prototype.find.call(clear.parentNode.children, (item) => item.matches('input[type="text"]'));
			const select = clear.previousElementSibling;

			if (!input) {
				return;
			}

			clear.setAttribute('onclick', "jInsertFieldValue('', '" + input.id + "');return false;");

			if (select && select.getAttribute('href')) {
				select.setAttribute('href', select.getAttribute('href').replace(/&fieldid=(.+)&/, '&fieldid=' + input.id + '&'));
			}
		});
	};

	/*
	 * --- Lists reordered by dragging (JHtml::_('sortablelist.sortable'), layout joomla.html.sortablelist; what
	 * sortablelist.js and jQuery UI's sortable were) ---
	 *
	 * A row's handle (.sortable-handler) drags it among the rows of its group (sortable-group-id: an article's category, a
	 * category's parent); in nested lists (Categories, Menu Items) its sub-rows (tr[parents~="<item-id>"]) move with it.
	 * Mouse, pen and touch, a line where it will go, the arrow keys on a focused handle. After a move the group's order[]
	 * values are given out again by position (ascending or descending, as the list is sorted) and posted with the rows'
	 * cid[] to the list's saveOrderAjax address, as before.
	 */
	const sortableLists = new WeakSet();

	CMS.sortableList = {
		/**
		 * @param  {HTMLElement|string}  tbody    The list's tbody (or a selector)
		 * @param  {Object}              options  formId, sortDir (asc/desc), url (saveOrderAjax), nested, saveOrderButton
		 */
		attach(tbody, options) {
			tbody = typeof tbody === 'string' ? document.querySelector(tbody) : (tbody && tbody.jquery ? tbody[0] : tbody);
			options = Object.assign({ formId: 'adminForm', sortDir: 'asc', url: '', nested: false, saveOrderButton: false }, options || {});

			if (!tbody || sortableLists.has(tbody)) {
				return;
			}

			sortableLists.add(tbody);

			// ui-sortable: templates style a list being sorted by it (jQuery UI's class)
			tbody.classList.add('ui-sortable');

			const form = document.getElementById(options.formId);
			const handleSelector = tbody.querySelector('.sortable-handler') ? '.sortable-handler' : 'tr';
			const rows = () => Array.prototype.filter.call(tbody.rows, (row) => !row.classList.contains('cms-sort-placeholder'));
			const groupOf = (row) => row.getAttribute('sortable-group-id') || '';
			const sameGroup = (row) => rows().filter((item) => groupOf(item) === groupOf(row));
			const blockOf = (row) => {
				const id = row.getAttribute('item-id');

				return [row].concat(options.nested && id ? rows().filter((item) => (' ' + (item.getAttribute('parents') || '') + ' ').indexOf(' ' + id + ' ') > -1) : []);
			};
			const lastOf = (row) => {
				const block = blockOf(row);

				return block[block.length - 1];
			};
			const ownRow = (target) => {
				const handle = target.closest ? target.closest(handleSelector) : null;
				const row = handle ? handle.closest('tr') : null;

				return row && row.parentNode === tbody && !(handle.classList && handle.classList.contains('inactive')) ? { handle, row } : null;
			};

			if (form) {
				form.setAttribute('autocomplete', 'off');
			}

			focusableHandles(tbody, '.sortable-handler');

			let saving = Promise.resolve();

			// The group's order[] values, given out again in the rows' new order, then posted with their cid[]
			const save = (row) => {
				const group = sameGroup(row).filter((item) => item.querySelector('[name="order[]"]'));
				const desc = options.sortDir === 'desc';
				const values = group.map((item) => parseInt(item.querySelector('[name="order[]"]').value, 10) || 0)
					.sort((a, b) => (desc ? b - a : a - b));

				// Values must differ: items sharing one (new modules all had 0) were posted unchanged, so nothing was saved
				for (let i = 1; i < values.length; i += 1) {
					values[i] = desc ? Math.min(values[i], values[i - 1] - 1) : Math.max(values[i], values[i - 1] + 1);
				}

				group.forEach((item, index) => {
					const input = item.querySelector('[name="order[]"]');

					input.value = values[index];
					input.setAttribute('value', values[index]);
				});

				if (!options.url || !form) {
					return;
				}

				const data = new FormData(form);

				['task', 'cid[]', 'order[]'].forEach((name) => data.delete(name));

				group.forEach((item) => {
					const box = item.querySelector('input[type="checkbox"][name="cid[]"]') || item.querySelector('input[type="checkbox"]');

					if (box) {
						data.append('cid[]', box.value);
						data.append('order[]', item.querySelector('[name="order[]"]').value);
					}
				});

				// One save after another: a later drop's save must not be overtaken by an earlier one
				const body = new URLSearchParams(data);

				saving = saving.then(() => fetch(options.url, {
					method: 'POST',
					body,
					credentials: 'same-origin',
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
				}))
					.then((response) => (response.ok ? response.text() : Promise.reject(new Error(response.status + ' ' + response.statusText))))
					.then((text) => {
						// saveOrderAjax answers 1 when it saved; a refusal (an expired form token) is a page with its message
						if (text.trim() !== '1') {
							const page = new DOMParser().parseFromString(text, 'text/html');
							const notice = page.querySelector('#system-message-container .alert-message') || page.querySelector('#system-message-container .alert');

							throw new Error((notice ? notice.textContent : page.body.textContent).replace(/\s+/g, ' ').trim().slice(0, 300));
						}
					})
					.catch((error) => {
						Joomla.renderMessages({ error: [CMS.text.get('JLIB_APPLICATION_ERROR_REORDER_FAILED', 'Reorder failed. Error: %s').replace('%s', error.message)] });
					});
			};

			// Moves a row (and its sub-rows) before an element, or to the end
			const place = (row, before) => blockOf(row).forEach((item) => tbody.insertBefore(item, before));

			tbody.addEventListener('pointerdown', (event) => {
				const found = event.button > 0 ? null : ownRow(event.target);

				if (!found) {
					return;
				}

				const { handle, row } = found;
				const own = blockOf(row);
				const others = sameGroup(row).filter((item) => own.indexOf(item) < 0);
				const placeholder = document.createElement('tr');
				const cell = document.createElement('td');
				let started = false;

				cell.colSpan = row.cells.length;
				placeholder.className = 'cms-sort-placeholder';
				placeholder.appendChild(cell);
				event.preventDefault();
				handle.setPointerCapture(event.pointerId);

				const move = (moveEvent) => {
					const y = moveEvent.clientY;
					let before = null;

					if (!started) {
						started = true;
						own.forEach((item) => item.classList.add('cms-sort-dragging'));

						// Rows of other groups can't take it
						rows().forEach((item) => {
							if (groupOf(item) !== groupOf(row)) {
								item.classList.add('dndlist-group-disabled');
							}
						});
					}

					for (const item of others) {
						const box = item.getBoundingClientRect();

						if (y < box.top + box.height / 2) {
							before = item;
							break;
						}
					}

					if (!before) {
						before = others.length ? lastOf(others[others.length - 1]).nextSibling : own[own.length - 1].nextSibling;
					}

					tbody.insertBefore(placeholder, before);

					if (y < 40) {
						window.scrollBy(0, -20);
					} else if (y > window.innerHeight - 40) {
						window.scrollBy(0, 20);
					}
				};

				const stop = () => {
					handle.removeEventListener('pointermove', move);
					handle.removeEventListener('pointerup', stop);
					handle.removeEventListener('pointercancel', stop);
					own.forEach((item) => item.classList.remove('cms-sort-dragging'));
					rows().forEach((item) => item.classList.remove('dndlist-group-disabled'));

					if (!placeholder.parentNode) {
						return;
					}

					const changed = placeholder.nextSibling !== row && placeholder.previousSibling !== own[own.length - 1];

					if (changed) {
						place(row, placeholder);
					}

					placeholder.remove();

					if (changed) {
						save(row);

						if (window.jQuery) {
							window.jQuery(row).trigger('sortstop', [{ item: window.jQuery(row) }]);
						}
					}
				};

				handle.addEventListener('pointermove', move);
				handle.addEventListener('pointerup', stop);
				handle.addEventListener('pointercancel', stop);
			});

			// A touch on a handle drags, never pans the page (touch-action alone fails on inline handles)
			tbody.addEventListener('touchstart', (event) => {
				if (ownRow(event.target)) {
					event.preventDefault();
				}
			}, { passive: false });

			tbody.addEventListener('keydown', (event) => {
				const found = event.key === 'ArrowUp' || event.key === 'ArrowDown' ? ownRow(event.target) : null;

				if (!found) {
					return;
				}

				const { handle, row } = found;
				const group = sameGroup(row);
				const index = group.indexOf(row);

				event.preventDefault();

				if (event.key === 'ArrowUp' && index > 0) {
					place(row, group[index - 1]);
				} else if (event.key === 'ArrowDown' && index < group.length - 1) {
					place(row, lastOf(group[index + 1]).nextSibling);
				} else {
					return;
				}

				handle.focus();
				save(row);
			});

			if (options.saveOrderButton) {
				CMS.sortableList.saveOrderButton();
			}
		},

		// Lists with order fields (.text-area-order): Save Order (.saveorder) only once one changed
		saveOrderButton() {
			if (CMS.sortableList.buttonStarted) {
				return;
			}

			CMS.sortableList.buttonStarted = true;

			const buttons = document.querySelectorAll('.saveorder');
			const enable = (on) => buttons.forEach((button) => {
				button.style.opacity = on ? '' : '0.2';
				button.style.cursor = on ? '' : 'default';

				if (on) {
					button.removeAttribute('onclick');
				} else {
					button.setAttribute('onclick', 'return false;');
				}
			});

			enable(false);
			document.querySelectorAll('.text-area-order').forEach((input) => {
				const original = input.value;

				input.addEventListener('input', () => {
					if (input.value !== original) {
						enable(true);
					}
				});
			});
		},
	};

	CMS.ready(() => {
		[].concat(CMS.options.get('system.sortablelist') || []).forEach((list) => {
			if (list.tableId) {
				CMS.sortableList.attach('#' + list.tableId + ' tbody', list);
			} else if (list.saveOrderButton) {
				CMS.sortableList.saveOrderButton();
			}
		});

		// sortablelist.js' name, for extensions which start their lists themselves
		if (window.jQuery && !window.jQuery.JSortableList) {
			window.jQuery.JSortableList = function JSortableList(tbody, formId, sortDir, url, options, nested) {
				CMS.sortableList.attach(tbody, { formId, sortDir, url, nested: Boolean(nested) });
			};
		}
	});

	/* --- Subforms (layouts joomla.form.field.subform.repeatable and repeatable-table; what subform-repeatable.js was) --- */

	const subforms = new WeakMap();

	class Subform {
		/**
		 * @param  {HTMLElement}  container  div.subform-repeatable
		 * @param  {Object}       options    btAdd, btRemove, btMove, minimum, maximum, repeatableElement,
		 *                                   rowTemplateSelector, rowsContainer; the element's data-* attributes win
		 */
		constructor(container, options) {
			const data = container.dataset;

			this.container = container;
			this.options = Object.assign({}, Subform.defaults, options || {});

			['btAdd', 'btRemove', 'btMove', 'repeatableElement', 'rowTemplateSelector', 'rowsContainer'].forEach((name) => {
				if (data[name] !== undefined) {
					this.options[name] = data[name];
				}
			});

			['minimum', 'maximum'].forEach((name) => {
				if (data[name] !== undefined && data[name] !== '') {
					this.options[name] = Number(data[name]);
				}
			});

			subforms.set(container, this);

			if (window.jQuery) {
				window.jQuery(container).data('subformRepeatable', this);
			}

			this.prepareTemplate();
			this.rows = this.options.rowsContainer ? container.querySelector(this.options.rowsContainer) || container : container;
			this.lastRowNum = this.rows.querySelectorAll(this.options.repeatableElement).length;

			container.addEventListener('click', (event) => {
				const add = event.target.closest ? event.target.closest(this.options.btAdd) : null;
				const remove = !add && event.target.closest ? event.target.closest(this.options.btRemove) : null;

				if (add && container.contains(add)) {
					event.preventDefault();
					this.addRow(add.closest(this.options.repeatableElement));
				} else if (remove && container.contains(remove)) {
					event.preventDefault();
					this.removeRow(remove.closest(this.options.repeatableElement));
				}
			});

			if (this.options.btMove) {
				focusableHandles(container, this.options.btMove);
				sortRows(this.rows, this.options.repeatableElement, this.options.btMove, (row) => emitRowEvent(container, 'subform-row-move', row));
			}

			emitRowEvent(container, 'subform-ready');
		}

		// The row's HTML: the container's own <template> (its last; earlier ones belong to nested subforms), else a copy of the first row
		prepareTemplate() {
			const templates = this.options.rowTemplateSelector ? this.container.querySelectorAll(this.options.rowTemplateSelector) : [];

			if (templates.length) {
				const template = templates[templates.length - 1];

				// Layouts write < and > as SUBFORMLT and SUBFORMGT (for browsers without <template>)
				template.style.display = 'none';
				this.template = template.innerHTML.trim().replace(/SUBFORMLT|SUBFORMGT/g, (match) => (match === 'SUBFORMLT' ? '<' : '>'));
			} else {
				const first = this.container.querySelector(this.options.repeatableElement);
				const row = first ? first.cloneNode(true) : null;

				if (row) {
					this.clearScripts(row);
				}

				this.template = row ? row.outerHTML : '';
			}
		}

		/**
		 * Adds a row after another (or at the end)
		 *
		 * @param   {HTMLElement|null}  after
		 *
		 * @return  {HTMLElement|null}  The row, null at the maximum
		 */
		addRow(after) {
			const count = this.rows.querySelectorAll(this.options.repeatableElement).length;
			const holder = document.createElement('template');

			after = after && after.jquery ? after[0] : after;

			if (count >= this.options.maximum) {
				return null;
			}

			holder.innerHTML = this.template;

			// As jQuery.parseHTML() did: scripts of the template don't come along
			holder.content.querySelectorAll('script').forEach((script) => script.remove());

			const row = holder.content.firstElementChild;

			if (!row) {
				return null;
			}

			if (after && after.parentNode) {
				after.parentNode.insertBefore(row, after.nextSibling);
			} else {
				this.rows.appendChild(row);
			}

			row.setAttribute('data-new', 'true');
			this.fixUniqueAttributes(row, count);
			this.fixScripts(row);

			if (this.options.btMove) {
				focusableHandles(row, this.options.btMove);
			}

			// Subforms inside the new row
			row.querySelectorAll('div.subform-repeatable').forEach((nested) => CMS.subform.attach(nested));

			emitRowEvent(this.container, 'subform-row-add', row);

			return row;
		}

		removeRow(row) {
			row = row && row.jquery ? row[0] : row;

			if (!row || this.rows.querySelectorAll(this.options.repeatableElement).length <= this.options.minimum) {
				return;
			}

			emitRowEvent(this.container, 'subform-row-remove', row);
			row.remove();
		}

		/**
		 * Gives a new row's fields names and ids of their own: [base][baseX][field] becomes [base][base4][field]
		 *
		 * @param  {HTMLElement|DocumentFragment}  row
		 * @param  {number}   count     Rows there were
		 * @param  {string}   group     The row's group, e.g. optionsX (nested templates: the outer row's)
		 * @param  {string}   basename  Without the count, e.g. options
		 * @param  {boolean}  nested    A nested subform's template: the row counter stays
		 */
		fixUniqueAttributes(row, count, group, basename, nested) {
			group = group === undefined ? row.getAttribute('data-group') : group;
			basename = basename === undefined ? row.getAttribute('data-base-name') : basename;
			count = count || 0;

			const countNew = Math.max(this.lastRowNum, count);
			const groupNew = basename + countNew;
			const ids = {};
			const relabel = (from, to) => row.querySelectorAll('label[for="' + quoted(from) + '"]').forEach((label) => {
				label.setAttribute('for', to);
				label.id = to + '-lbl';
			});

			if (row.setAttribute) {
				row.setAttribute('data-group', groupNew);
			}

			row.querySelectorAll('[name]').forEach((element) => {
				const name = element.getAttribute('name');
				const id = name.replace(/(\[\]$)/g, '').replace(/(\]\[)/g, '__').replace(/\[/g, '_').replace(/\]/g, '');
				const nameNew = name.replace('[' + group + '][', '[' + groupNew + '][');
				let idNew = id.replace(group, groupNew).replace(/\W/g, '_');
				let forOld = id;
				const multiple = (element.type === 'checkbox' && /\[\]$/.test(name)) || element.type === 'radio';

				// Checkboxes of a list (name[]) and radios: one id each, the group's fieldset and label take the base id
				if (multiple) {
					const index = ids[id] || 0;

					if (!index) {
						const fieldset = element.closest(element.type === 'radio' ? 'fieldset.radio' : 'fieldset.checkboxes');

						if (fieldset) {
							fieldset.id = idNew;
						}

						relabel(id, idNew);
					}

					forOld += index;
					idNew += index;
				}

				ids[id] = (ids[id] || 0) + 1;
				element.setAttribute('name', nameNew);
				element.id = idNew;
				relabel(forOld, idNew);
			});

			// Other elements whose ids carry the group (the media field's modal: imageModal_jform_..._optionsX_image)
			row.querySelectorAll('[id*="_' + quoted(group) + '_"]').forEach((element) => {
				element.id = element.id.replace('_' + group + '_', '_' + groupNew + '_');
			});

			// Nested subforms' templates hold the old group too
			if (this.options.rowTemplateSelector) {
				row.querySelectorAll(this.options.rowTemplateSelector).forEach((template) => {
					this.fixUniqueAttributes(template.content, count, group, basename, true);
				});
			}

			if (!nested) {
				this.lastRowNum = countNew + 1;
			}
		}

		// A copy of a row whose lists Chosen had drawn: the plain lists, which fixScripts() lets Chosen draw again
		clearScripts(row) {
			row.querySelectorAll('select.chzn-done').forEach((select) => {
				const chosen = select.nextElementSibling;

				if (chosen && chosen.classList.contains('chzn-container')) {
					chosen.remove();
				}

				select.style.display = '';
				select.classList.add('fix-chosen');
			});
		}

		// Scripts of fields which don't start themselves in new rows (most listen to subform-row-add)
		fixScripts(row) {
			fixOldMediaFields(row);
		}
	}

	Subform.defaults = {
		// Selectors of the buttons, unique per subform (nested ones have their own)
		btAdd: '.group-add',
		btRemove: '.group-remove',
		btMove: '.group-move',
		minimum: 0,
		maximum: 10,
		// The rows, unique per subform
		repeatableElement: '.subform-repeatable-group',
		// The row's template, the same class in nested subforms
		rowTemplateSelector: 'template.subform-repeatable-template-section',
		// The rows' parent, the container by default
		rowsContainer: null,
	};

	CMS.subform = {
		Subform,
		defaults: Subform.defaults,

		/**
		 * Starts a subform (once)
		 *
		 * @param   {HTMLElement}  container  div.subform-repeatable
		 * @param   {Object}       options
		 *
		 * @return  {Subform}
		 */
		attach(container, options) {
			container = container && container.jquery ? container[0] : container;

			return subforms.get(container) || new Subform(container, options);
		},

		get: (container) => subforms.get(container && container.jquery ? container[0] : container) || null,
	};

	CMS.ready(() => {
		document.querySelectorAll('div.subform-repeatable').forEach((container) => CMS.subform.attach(container));

		// subform-repeatable.js' jQuery names, for extensions which start subforms they add themselves
		if (window.jQuery && !window.jQuery.fn.subformRepeatable) {
			window.jQuery.subformRepeatable = function subformRepeatable(container, options) {
				return CMS.subform.attach(container, options);
			};
			window.jQuery.subformRepeatable.defaults = Subform.defaults;
			window.jQuery.fn.subformRepeatable = function subformRepeatable(options) {
				return this.each((index, container) => {
					CMS.subform.attach(container, options);
				});
			};
		}
	});

	/*
	 * --- The Repeatable field (JFormFieldRepeatable, deprecated since Joomla 3.6 for the subform; no core form uses it;
	 * what repeatable.js was) ---
	 *
	 * A table of rows in the CMS's modal, kept as JSON ({"field": ["row 1", "row 2"]}) in a hidden input: Apply writes it,
	 * Cancel goes back to what it holds. The table lives outside the form (its fields aren't posted).
	 */

	const repeatables = new WeakMap();

	class Repeatable {
		/**
		 * @param  {HTMLInputElement}  input    input.form-field-repeatable
		 * @param  {Object}            options  container, modalElement, btModalOpen, btModalClose, btModalSaveData, btAdd,
		 *                                      btRemove, maximum, repeatableElement; the input's data-* attributes win
		 */
		constructor(input, options) {
			const data = input.dataset;

			this.input = input;
			this.options = Object.assign({}, Repeatable.defaults, options || {});

			['container', 'modalElement', 'btModalOpen', 'btModalClose', 'btModalSaveData', 'btAdd', 'btRemove', 'repeatableElement', 'textApply', 'textCancel'].forEach((name) => {
				if (data[name] !== undefined) {
					this.options[name] = data[name];
				}
			});

			if (data.maximum) {
				this.options.maximum = Number(data.maximum);
			}

			repeatables.set(input, this);

			if (window.jQuery) {
				window.jQuery(input).data('JRepeatable', this);
			}

			this.container = document.querySelector(this.options.container);
			this.modal = document.querySelector(this.options.modalElement);

			if (!this.container || !this.modal) {
				return;
			}

			document.body.appendChild(this.container);
			this.container.hidden = true;
			this.rows = this.container.querySelector(this.options.repeatableElement).parentNode;
			this.inputs = [];
			this.values = {};
			this.prepareModal();
			this.prepareTemplate();
			this.readValue();
			this.buildRows();

			document.addEventListener('click', (event) => {
				if (event.target.closest && event.target.closest(this.options.btModalOpen)) {
					event.preventDefault();
					this.open();
				}
			});

			// The table's own element: it's in the CMS's modal while open
			this.modal.addEventListener('click', (event) => {
				const target = event.target.closest ? event.target : null;
				const add = target ? target.closest(this.options.btAdd) : null;
				const remove = !add && target ? target.closest(this.options.btRemove) : null;

				if (add) {
					event.preventDefault();
					this.addRow(add.closest(this.options.repeatableElement));
				} else if (remove) {
					event.preventDefault();
					this.removeRow(remove.closest(this.options.repeatableElement));
				} else if (target && target.closest(this.options.btModalSaveData)) {
					event.preventDefault();
					this.apply();
				} else if (target && target.closest(this.options.btModalClose)) {
					event.preventDefault();
					this.cancel();
				}
			});

			focusableHandles(this.rows, '.sortable-handler');
			sortRows(this.rows, this.options.repeatableElement, '.sortable-handler');
			this.emit('weready');
		}

		emit(type, detail) {
			if (window.jQuery) {
				window.jQuery(this.input).trigger(type, detail === undefined ? [] : [detail]);
			}

			this.input.dispatchEvent(new CustomEvent('cms:repeatable-' + type, { bubbles: true, detail }));
		}

		// The Bootstrap modal's markup of extensions' copies of the field: its footer's buttons are the CMS modal's
		prepareModal() {
			const footer = this.modal.querySelector('.modal-footer');
			const text = (selector, fallback) => {
				const button = footer ? footer.querySelector(selector) : null;

				return button ? button.textContent.trim() : fallback;
			};

			this.texts = {
				apply: this.options.textApply || text(this.options.btModalSaveData, 'Apply'),
				cancel: this.options.textCancel || text(this.options.btModalClose, 'Cancel'),
			};

			if (footer) {
				footer.hidden = true;
			}

			this.modal.classList.remove('modal', 'hide', 'fade');
			this.emit('prepare-modal', this.modal);
		}

		prepareTemplate() {
			const rows = this.container.querySelectorAll(this.options.repeatableElement);
			const first = rows[0];

			this.clearScripts(first);

			first.querySelectorAll('[name]').forEach((element) => {
				const name = element.getAttribute('name');

				// Radios: one name for several
				if (this.values[name]) {
					return;
				}

				this.inputs.push({ name, type: element.getAttribute('type') || element.tagName.toLowerCase() });
				this.values[name] = [];
			});

			this.template = first.outerHTML;
			rows.forEach((row) => row.remove());
			this.emit('prepare-template', this.template);
		}

		// The stored JSON (older values may have single quotes)
		readValue() {
			const value = this.input.value;

			if (!value) {
				return;
			}

			try {
				this.values = JSON.parse(value);
			} catch (error) {
				try {
					this.values = JSON.parse(value.replace(/'/g, '"').replace(/\\"/g, "'"));
				} catch (error2) {
					window.console.log(error2);
				}
			}
		}

		open() {
			this.modal.hidden = false;

			CMS.modal.open(this.modal, {
				handler: 'adopt',
				size: { x: Math.min(1400, window.innerWidth - 80), y: Math.min(900, window.innerHeight - 140) },
				title: (document.querySelector(this.options.btModalOpen) || {}).textContent || '',
				closable: false,
				closeBtn: false,
				buttons: [
					{ text: this.texts.cancel, className: 'btn', onClick: () => this.cancel() },
					{ text: this.texts.apply, className: 'btn btn-primary', onClick: () => this.apply() },
				],
			});
		}

		// Keeps the changes
		apply() {
			this.refreshValue();
			CMS.modal.close();
		}

		// Back to what the input holds
		cancel() {
			CMS.modal.close();
			this.buildRows();
		}

		buildRows() {
			Array.prototype.slice.call(this.rows.children).forEach((row) => this.removeRow(row));

			const first = this.values[Object.keys(this.values)[0]];
			const count = (first && first.length) || 1;
			let row = null;

			for (let i = 0; i < count; i++) {
				row = this.addRow(row, i);
			}
		}

		addRow(after, valueKey) {
			// The table's element (in the CMS's modal while open, outside the container)
			const count = this.modal.querySelectorAll(this.options.repeatableElement).length;
			const holder = document.createElement('template');

			after = after && after.jquery ? after[0] : after;

			if (count >= this.options.maximum) {
				return null;
			}

			holder.innerHTML = this.template;
			holder.content.querySelectorAll('script').forEach((script) => script.remove());

			const row = holder.content.firstElementChild;

			if (after && after.parentNode) {
				after.parentNode.insertBefore(row, after.nextSibling);
			} else {
				this.rows.appendChild(row);
			}

			this.fixUniqueAttributes(row, count + 1);

			if (valueKey !== null && valueKey !== undefined) {
				this.inputs.forEach(({ name, type }) => {
					const value = this.values[name] ? this.values[name][valueKey] : null;
					const matching = (wanted) => row.querySelectorAll('[name*="' + quoted(name) + '"]' + (wanted === undefined ? '' : '[value="' + quoted(wanted) + '"]'));

					if (value === null || value === undefined) {
						return;
					}

					if (type === 'radio') {
						matching(value).forEach((element) => element.setAttribute('checked', 'checked'));
					} else if (type === 'checkbox') {
						(Array.isArray(value) ? value : [value]).forEach((item) => matching(item).forEach((element) => element.setAttribute('checked', 'checked')));
					} else {
						matching().forEach((element) => {
							if (element.multiple && Array.isArray(value)) {
								Array.prototype.forEach.call(element.options, (option) => {
									option.selected = value.indexOf(option.value) > -1;
								});
							} else {
								element.value = value;
							}
						});
					}
				});
			}

			this.fixScripts(row);
			focusableHandles(row, '.sortable-handler');
			this.emit('row-add', row);

			return row;
		}

		removeRow(row) {
			row = row && row.jquery ? row[0] : row;
			this.emit('row-remove', row);
			row.remove();
		}

		// count-name for every id, for and name in the row
		fixUniqueAttributes(row, count) {
			[['[id]', 'id'], ['label[for]', 'for'], ['[name]', 'name']].forEach(([selector, attribute]) => {
				row.querySelectorAll(selector).forEach((element) => element.setAttribute(attribute, count + '-' + element.getAttribute(attribute)));
			});
		}

		// The values of every row, as JSON in the input
		refreshValue() {
			const rows = this.modal.querySelectorAll(this.options.repeatableElement);

			this.values = {};

			this.inputs.forEach(({ name, type }) => {
				this.values[name] = Array.prototype.map.call(rows, (row) => {
					const all = row.querySelectorAll('[name*="' + quoted(name) + '"]');
					const checked = Array.prototype.filter.call(all, (element) => element.checked);
					let value;

					if (type === 'radio') {
						value = checked.length ? checked[0].value : undefined;
					} else if (type === 'checkbox') {
						value = checked.length > 1 ? checked.map((element) => element.value) : (checked.length ? checked[0].value : undefined);
					} else if (all.length && all[0].multiple) {
						value = Array.prototype.filter.call(all[0].options, (option) => option.selected).map((option) => option.value);
					} else {
						value = all.length ? all[0].value : undefined;
					}

					return value === null || value === undefined ? '' : value;
				});
			});

			this.input.value = JSON.stringify(this.values);
			this.emit('value-update', this.values);
		}

		// The template's lists without Chosen (drawn again in each row)
		clearScripts(row) {
			if (!window.jQuery || !window.jQuery.fn.chosen) {
				return;
			}

			row.querySelectorAll('select').forEach((select) => {
				if (window.jQuery(select).data('chosen')) {
					window.jQuery(select).chosen('destroy');
					select.classList.add('here-was-chosen');
				}
			});
		}

		fixScripts(row) {
			if (window.jQuery && window.jQuery.fn.chosen) {
				window.jQuery(row.querySelectorAll('select.here-was-chosen')).removeClass('here-was-chosen').chosen();
			}

			fixOldMediaFields(row);
			row.querySelectorAll('a[onclick*="jInsertFieldValue"]').forEach((clear) => {
				const input = Array.prototype.find.call(clear.parentNode.children, (item) => item.matches('input[type="text"]'));

				if (input && window.jMediaRefreshPreview) {
					window.jMediaRefreshPreview(input.id);
				}
			});

			// The administrator templates' media field
			if (window.jQuery && window.jQuery.fn.fieldMedia) {
				window.jQuery(row.querySelectorAll('.field-media-wrapper')).fieldMedia();
			}

			// Links opening in the modal
			CMS.modal.assign(row.querySelectorAll('a.modal'), { parse: 'rel' });
		}
	}

	Repeatable.defaults = {
		modalElement: '#modal-container',
		btModalOpen: '#open-modal',
		btModalClose: '.close-modal',
		btModalSaveData: '.save-modal-data',
		btAdd: 'a.add',
		btRemove: 'a.remove',
		maximum: 10,
		repeatableElement: 'table tbody tr',
	};

	CMS.repeatable = {
		Repeatable,
		defaults: Repeatable.defaults,

		attach(input, options) {
			input = input && input.jquery ? input[0] : input;

			return repeatables.get(input) || new Repeatable(input, options);
		},
	};

	// After the page's other scripts, as repeatable.js did (window load), so the fields' own scripts have run
	const startRepeatables = () => {
		document.querySelectorAll('input.form-field-repeatable').forEach((input) => CMS.repeatable.attach(input));

		if (window.jQuery && !window.jQuery.fn.JRepeatable) {
			window.jQuery.JRepeatable = function JRepeatable(input, options) {
				return CMS.repeatable.attach(input, options);
			};
			window.jQuery.JRepeatable.defaults = Repeatable.defaults;
			window.jQuery.fn.JRepeatable = function JRepeatableEach(options) {
				return this.each((index, input) => {
					CMS.repeatable.attach(input, options);
				});
			};
		}
	};

	if (document.readyState === 'complete') {
		CMS.ready(startRepeatables);
	} else {
		window.addEventListener('load', startRepeatables);
	}

	/*
	 * --- Tree of checkboxes (ul.treeselect: a module's Menu Assignment, a Menu Item Container's items; what
	 * treeselectmenu.jquery.js was) ---
	 *
	 * Each node gets a toggle (+/-) and, where #treeselectmenu is on the page, a copy of its menu (select, deselect, expand,
	 * collapse the node's items). The links #treeCheckAll, #treeUncheckAll, #treeExpandAll and #treeCollapseAll, the filter
	 * #treeselectfilter and the notice #noresultsfound are the page's. ul.treeselect[data-treeselect-propagate] (a Menu
	 * Item Container): a checked item checks its sub-items and disables them, and "All" (value 1) checks everything.
	 */
	const treeSubs = (node) => node.querySelectorAll('ul.treeselect-sub');

	CMS.treeSelect = {
		start(tree) {
			const menu = document.getElementById('treeselectmenu');

			tree.querySelectorAll('li').forEach((node) => {
				const item = node.querySelector('div.treeselect-item');
				const toggle = document.createElement('span');
				const clearfix = document.createElement('div');

				toggle.className = 'pull-left icon-';
				node.insertBefore(toggle, node.firstChild);
				clearfix.className = 'clearfix';

				if (item) {
					item.parentNode.insertBefore(clearfix, item.nextSibling);
				}

				if (!node.querySelector('ul.treeselect-sub')) {
					return;
				}

				toggle.classList.add('treeselect-toggle', 'icon-minus');
				toggle.tabIndex = 0;
				toggle.setAttribute('role', 'button');
				toggle.setAttribute('aria-expanded', 'true');

				const label = item ? item.querySelector('label') : null;

				if (menu && label) {
					label.insertAdjacentHTML('afterend', menu.innerHTML);

					// Expand and collapse only where the node's items have items of their own
					if (!node.querySelector('ul.treeselect-sub ul.treeselect-sub')) {
						item.querySelectorAll('div.treeselect-menu-expand').forEach((part) => part.remove());
					}
				}
			});

			if (tree.hasAttribute('data-treeselect-propagate')) {
				tree.querySelectorAll('input[type="checkbox"]:checked').forEach((box) => CMS.treeSelect.propagate(box));
			}
		},

		// Opens or closes a node and everything in it
		toggle(node, open) {
			node.querySelectorAll('span.treeselect-toggle').forEach((toggle) => {
				toggle.classList.toggle('icon-minus', open);
				toggle.classList.toggle('icon-plus', !open);
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			});

			treeSubs(node).forEach((sub) => {
				sub.style.display = open ? '' : 'none';
			});
		},

		check(root, checked) {
			const tree = root.closest ? root.closest('ul.treeselect') || root : root;

			root.querySelectorAll('input[type="checkbox"]').forEach((box) => {
				box.checked = checked;
			});

			if (tree.hasAttribute && tree.hasAttribute('data-treeselect-propagate')) {
				tree.querySelectorAll('input[type="checkbox"]').forEach((box) => {
					box.disabled = false;
				});
				tree.querySelectorAll('input[type="checkbox"]:checked').forEach((box) => CMS.treeSelect.propagate(box));
			}
		},

		// A Menu Item Container's checkbox: its sub-items follow it (and are disabled while it's checked); "All" sets them all
		propagate(box) {
			const node = box.closest('li');
			const subs = node ? node.querySelectorAll('.treeselect-sub input[type="checkbox"]') : [];

			subs.forEach((sub) => {
				sub.checked = box.checked;

				if (box.value === '1') {
					CMS.treeSelect.propagate(sub);
				} else {
					sub.disabled = box.checked;
				}
			});
		},

		filter(text) {
			const nodes = document.querySelectorAll('.treeselect li');
			const notice = document.getElementById('noresultsfound');
			let hidden = 0;

			text = text.toLowerCase();

			nodes.forEach((node) => {
				const shown = node.textContent.toLowerCase().indexOf(text) > -1;

				node.style.display = shown ? '' : 'none';
				hidden += shown ? 0 : 1;
			});

			if (notice) {
				notice.style.display = nodes.length && hidden === nodes.length ? '' : 'none';
			}
		},
	};

	CMS.ready(() => {
		const trees = document.querySelectorAll('ul.treeselect');
		const assignment = document.getElementById('jform_assignment');
		const selection = document.getElementById('menuselect-group');

		trees.forEach((tree) => CMS.treeSelect.start(tree));

		// A module's Menu Assignment: the menu items only when it's not "On all pages" (0) or "No pages" (-)
		if (assignment && selection) {
			const update = () => {
				selection.style.display = assignment.value === '0' || assignment.value === '-' ? 'none' : '';
			};

			update();
			onChange(assignment, update);
		}

		if (!trees.length) {
			return;
		}

		const filter = document.getElementById('treeselectfilter');

		if (filter) {
			filter.addEventListener('input', () => CMS.treeSelect.filter(filter.value));
		}

		document.addEventListener('click', (event) => {
			const target = event.target.closest ? event.target.closest('a, span.treeselect-toggle, input[type="checkbox"]') : null;
			const node = target && target.closest('.treeselect-item') ? target.closest('.treeselect-item').parentNode : null;

			if (!target) {
				return;
			}

			if (target.matches('input[type="checkbox"]')) {
				const tree = target.closest('ul.treeselect');

				if (tree && tree.hasAttribute('data-treeselect-propagate')) {
					CMS.treeSelect.propagate(target);
				}

				return;
			}

			const actions = {
				treeCheckAll: () => trees.forEach((tree) => CMS.treeSelect.check(tree, true)),
				treeUncheckAll: () => trees.forEach((tree) => CMS.treeSelect.check(tree, false)),
				treeExpandAll: () => trees.forEach((tree) => CMS.treeSelect.toggle(tree, true)),
				treeCollapseAll: () => trees.forEach((tree) => CMS.treeSelect.toggle(tree, false)),
			};

			if (actions[target.id]) {
				event.preventDefault();
				actions[target.id]();
			} else if (target.matches('span.treeselect-toggle') && target.closest('ul.treeselect')) {
				CMS.treeSelect.toggle(target.parentNode, target.classList.contains('icon-plus'));
			} else if (node && target.matches('a.checkall, a.uncheckall')) {
				event.preventDefault();
				node.querySelectorAll('ul.treeselect-sub').forEach((sub) => CMS.treeSelect.check(sub, target.matches('a.checkall')));
			} else if (node && target.matches('a.expandall, a.collapseall')) {
				event.preventDefault();
				CMS.treeSelect.toggle(node, target.matches('a.expandall'));
			}
		});

		document.addEventListener('keydown', (event) => {
			const toggle = event.target.matches && event.target.matches('ul.treeselect span.treeselect-toggle') ? event.target : null;

			if (toggle && (event.key === 'Enter' || event.key === ' ')) {
				event.preventDefault();
				toggle.click();
			}
		});
	});

	/*
	 * --- Searchable select lists (JHtml formbehavior.chosen and formbehavior.ajaxchosen; what Chosen 1.6.2, in Joomla's
	 * chzn version, and Ajax Chosen were) ---
	 *
	 * The same markup as Joomla's Chosen (div.chzn-container#<id>_chzn, a.chzn-single, ul.chzn-choices, div.chzn-drop,
	 * ul.chzn-results with li.active-result, .highlighted, .result-selected, .group-result...), so templates' styles and
	 * scripts reading it keep working, the same options (placeholder_text_*, no_results_text, disable_search_threshold,
	 * search_contains, allow_single_deselect, max_selected_options, width...) and Joomla's custom values. Events go out as
	 * Chosen's did (liszt:ready, liszt:showing_dropdown, liszt:hiding_dropdown, liszt:no_results, liszt:maxselected,
	 * change with {selected} or {deselected}): through jQuery when it's there, natively otherwise. liszt:updated and
	 * chosen:updated redraw a list after a script changed it. The native select stays the field the form posts. On phones
	 * the browser's own list is kept, as Chosen did. Added: listbox and option roles, aria-expanded and
	 * aria-activedescendant on the search field, which is labelled as the select is.
	 */
	const chosens = new WeakMap();
	const chosenEscape = (text) => String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
	let chosenCount = 0;

	// Through jQuery when it's there (handlers get the parameters as their second argument), natively otherwise
	const chosenEmit = (select, type, params) => {
		if (window.jQuery) {
			window.jQuery(select).trigger(type, params === undefined ? [] : [params]);
		} else if (type === 'change') {
			select.dispatchEvent(new Event('change', { bubbles: true }));
		} else {
			select.dispatchEvent(new CustomEvent(type, { bubbles: true, detail: params }));
		}
	};

	class Chosen {
		constructor(select, options) {
			const o = options || {};
			const label = select.id ? document.querySelector('label[for="' + quoted(select.id) + '"]') : null;

			this.form_field = select;
			this.options = o;
			this.is_multiple = select.multiple;
			this.id = 'cms-chzn-' + (chosenCount += 1);
			this.allow_single_deselect = o.allow_single_deselect != null && select.options[0] != null && select.options[0].text === '' ? o.allow_single_deselect : false;
			this.disable_search_threshold = o.disable_search_threshold || 0;
			this.disable_search = o.disable_search || false;
			this.enable_split_word_search = o.enable_split_word_search != null ? o.enable_split_word_search : true;
			this.group_search = o.group_search != null ? o.group_search : true;
			this.search_contains = o.search_contains || false;
			this.single_backstroke_delete = o.single_backstroke_delete != null ? o.single_backstroke_delete : true;
			this.max_selected_options = o.max_selected_options || Infinity;
			this.inherit_select_classes = o.inherit_select_classes || false;
			this.display_selected_options = o.display_selected_options != null ? o.display_selected_options : true;
			this.display_disabled_options = o.display_disabled_options != null ? o.display_disabled_options : true;
			this.include_group_label_in_selected = o.include_group_label_in_selected || false;
			this.max_shown_results = o.max_shown_results || Infinity;
			this.case_sensitive_search = o.case_sensitive_search || false;
			this.allow_custom_value = select.classList.contains('chzn-custom-value') || o.allow_custom_value || false;
			this.custom_value_prefix = select.getAttribute('data-custom_value_prefix') || '';
			this.is_rtl = select.classList.contains('chzn-rtl');
			this.active_field = false;
			this.mouse_on_container = false;
			this.results_showing = false;
			this.result_highlight = null;
			this.remote = o.remote || null;
			this.custom_tags = o.custom_tags || null;
			this.set_default_text();

			// The markup
			const container = document.createElement('div');

			container.className = ['chzn-container', 'chzn-container-' + (this.is_multiple ? 'multi' : 'single')]
				.concat(this.inherit_select_classes && select.className ? [select.className] : [], this.is_rtl ? ['chzn-rtl'] : []).join(' ');
			container.style.width = this.container_width();

			if (select.title) {
				container.title = select.title;
			}

			if (select.id) {
				container.id = select.id.replace(/[^\w]/g, '_') + '_chzn';
			}

			if (this.is_multiple) {
				container.innerHTML = '<ul class="chzn-choices"><li class="search-field"><input type="text" class="default" autocomplete="off" style="width:25px;" /></li></ul><div class="chzn-drop"><ul class="chzn-results"></ul></div>';
			} else {
				container.innerHTML = '<a class="chzn-single chzn-default"><span></span><div><b></b></div></a><div class="chzn-drop"><div class="chzn-search"><input type="text" autocomplete="off" /></div><ul class="chzn-results"></ul></div>';
			}

			select.style.display = 'none';
			select.classList.add('chzn-done');
			select.parentNode.insertBefore(container, select.nextSibling);

			this.container_el = container;
			this.dropdown = container.querySelector('div.chzn-drop');
			this.search_field_el = container.querySelector('input');
			this.search_results_el = container.querySelector('ul.chzn-results');

			if (this.is_multiple) {
				this.search_choices = container.querySelector('ul.chzn-choices');
				this.search_container = container.querySelector('li.search-field');
			} else {
				this.search_container = container.querySelector('div.chzn-search');
				this.selected_item = container.querySelector('.chzn-single');
			}

			// Accessibility: the search field is the combobox, the results its listbox, labelled as the select is
			this.search_results_el.id = this.id + '-results';
			this.search_results_el.setAttribute('role', 'listbox');

			if (this.is_multiple) {
				this.search_results_el.setAttribute('aria-multiselectable', 'true');
			}

			this.search_field_el.setAttribute('role', 'combobox');
			this.search_field_el.setAttribute('aria-autocomplete', 'list');
			this.search_field_el.setAttribute('aria-expanded', 'false');
			this.search_field_el.setAttribute('aria-controls', this.search_results_el.id);

			if (label || select.getAttribute('aria-label')) {
				this.search_field_el.setAttribute('aria-label', select.getAttribute('aria-label') || label.textContent.trim());
			}

			// Chosen's API used jQuery objects
			if (window.jQuery) {
				this.form_field_jq = window.jQuery(select);
				this.container = window.jQuery(container);
				this.search_field = window.jQuery(this.search_field_el);
				this.search_results = window.jQuery(this.search_results_el);
			} else {
				this.container = container;
				this.search_field = this.search_field_el;
				this.search_results = this.search_results_el;
			}

			this.current_selectedIndex = select.selectedIndex;
			this.results_build();

			if (select.tabIndex && select.getAttribute('tabindex') !== null) {
				this.search_field_el.tabIndex = select.tabIndex;
				select.tabIndex = -1;
			}

			this.register_observers(label);
			chosenEmit(select, 'liszt:ready', { chosen: this });
		}

		set_default_text() {
			const select = this.form_field;
			const o = this.options;

			if (select.getAttribute('data-placeholder')) {
				this.default_text = select.getAttribute('data-placeholder');
			} else if (this.is_multiple) {
				this.default_text = o.placeholder_text_multiple || o.placeholder_text || 'Select Some Options';
			} else {
				this.default_text = o.placeholder_text_single || o.placeholder_text || 'Select an Option';
			}

			this.custom_group_text = select.getAttribute('data-custom_group_text') || o.custom_group_text || 'Custom Value';
			this.results_none_found = select.getAttribute('data-no_results_text') || o.no_results_text || 'No results match';
		}

		container_width() {
			if (this.options.width != null) {
				return this.options.width;
			}

			const width = window.getComputedStyle(this.form_field).width;

			return width && width !== '0px' ? width : this.form_field.offsetWidth + 'px';
		}

		register_observers(label) {
			const container = this.container_el;
			const field = this.search_field_el;
			const select = this.form_field;
			const results = this.search_results_el;
			const update = () => this.results_update_field();

			this.click_test_action = (event) => this.test_active_click(event);

			container.addEventListener('mousedown', (event) => this.container_mousedown(event));
			container.addEventListener('mouseup', (event) => this.container_mouseup(event));
			container.addEventListener('mouseenter', () => {
				this.mouse_on_container = true;
			});
			container.addEventListener('mouseleave', () => {
				this.mouse_on_container = false;
			});
			// A touch opens or closes once (the mouse events browsers emulate after it are cancelled), the results still scroll
			container.addEventListener('touchstart', (event) => {
				this.mouse_on_container = true;
				this.touch_started = true;

				if (!event.target.closest('.chzn-results')) {
					this.container_mousedown(event);
					event.preventDefault();
				}
			}, { passive: false });
			container.addEventListener('touchend', (event) => {
				if (!event.target.closest('.chzn-results')) {
					this.container_mouseup(event);
				}
			});

			if (!this.is_multiple) {
				container.addEventListener('click', (event) => event.preventDefault());
			} else {
				this.search_choices.addEventListener('click', (event) => this.choices_click(event));
			}

			results.addEventListener('mouseup', (event) => this.search_results_mouseup(event));
			results.addEventListener('mouseover', (event) => this.search_results_mouseover(event));
			results.addEventListener('mouseout', (event) => this.search_results_mouseout(event));
			results.addEventListener('touchmove', () => {
				this.touch_started = false;
			}, { passive: true });
			results.addEventListener('touchend', (event) => {
				if (this.touch_started) {
					this.search_results_mouseover(event);
					this.search_results_mouseup(event);
					event.preventDefault();
				}
			});

			field.addEventListener('blur', (event) => this.input_blur(event));
			field.addEventListener('keyup', (event) => this.keyup_checker(event));
			field.addEventListener('keydown', (event) => this.keydown_checker(event));
			field.addEventListener('focus', (event) => this.input_focus(event));
			field.addEventListener('cut', () => setTimeout(() => this.results_search(), 50));
			field.addEventListener('paste', () => setTimeout(() => this.results_search(), 50));

			// Scripts changing the list: through jQuery's trigger (most), natively (CustomEvent)
			const incoming = {
				'liszt:updated': update,
				'chosen:updated': update,
				'liszt:activate': () => this.activate_field(),
				'chosen:activate': () => this.activate_field(),
				'liszt:open': (event) => this.container_mousedown(event),
				'chosen:open': (event) => this.container_mousedown(event),
				'liszt:close': (event) => this.input_blur(event),
				'chosen:close': (event) => this.input_blur(event),
			};

			Object.keys(incoming).forEach((type) => select.addEventListener(type, incoming[type]));

			if (window.jQuery) {
				this.jquery_handlers = incoming;
				Object.keys(incoming).forEach((type) => window.jQuery(select).on(type, incoming[type]));
			}

			if (label) {
				this.form_field_label = label;
				this.label_action = (event) => (this.is_multiple ? this.container_mousedown(event) : this.activate_field());
				label.addEventListener('click', this.label_action);
			}

			if (this.remote) {
				this.remote_observer();
			}
		}

		destroy() {
			document.removeEventListener('click', this.click_test_action);

			if (this.search_field_el.tabIndex > 0) {
				this.form_field.tabIndex = this.search_field_el.tabIndex;
			}

			if (this.form_field_label) {
				this.form_field_label.removeEventListener('click', this.label_action);
			}

			if (this.jquery_handlers && window.jQuery) {
				Object.keys(this.jquery_handlers).forEach((type) => window.jQuery(this.form_field).off(type, this.jquery_handlers[type]));
			}

			this.container_el.remove();
			this.form_field.style.display = '';
			this.form_field.classList.remove('chzn-done');
			chosens.delete(this.form_field);

			if (window.jQuery) {
				window.jQuery(this.form_field).removeData('chosen');
			}
		}

		search_field_disabled() {
			this.is_disabled = this.form_field.disabled;
			this.container_el.classList.toggle('chzn-disabled', this.is_disabled);
			this.search_field_el.disabled = this.is_disabled;

			if (this.is_disabled) {
				this.close_field();
			}
		}

		input_focus() {
			if (this.is_multiple) {
				if (!this.active_field) {
					setTimeout(() => this.container_mousedown(), 50);
				}
			} else if (!this.active_field) {
				this.activate_field();
			}
		}

		input_blur() {
			if (!this.mouse_on_container) {
				this.active_field = false;
				setTimeout(() => this.blur_test(), 100);
			}
		}

		container_mousedown(event) {
			if (this.is_disabled) {
				return;
			}

			if (event && event.type === 'mousedown' && !this.results_showing) {
				event.preventDefault();
			}

			if (event && event.target && event.target.classList && event.target.classList.contains('search-choice-close')) {
				return;
			}

			if (!this.active_field) {
				if (this.is_multiple) {
					this.search_field_el.value = '';
				}

				document.addEventListener('click', this.click_test_action);
				this.results_show();
			} else if (!this.is_multiple && event && event.target && event.target.closest && event.target.closest('a.chzn-single')) {
				event.preventDefault();
				this.results_toggle();
			}

			this.activate_field();
		}

		container_mouseup(event) {
			if (event.target.nodeName === 'ABBR' && !this.is_disabled) {
				this.results_reset();
			}
		}

		blur_test() {
			if (!this.active_field && this.container_el.classList.contains('chzn-container-active')) {
				this.close_field();
			}
		}

		close_field() {
			document.removeEventListener('click', this.click_test_action);
			this.active_field = false;
			this.results_hide();
			this.container_el.classList.remove('chzn-container-active');
			this.clear_backstroke();
			this.show_search_field_default();
			this.search_field_scale();
		}

		activate_field() {
			this.container_el.classList.add('chzn-container-active');
			this.active_field = true;
			this.search_field_el.focus();
		}

		test_active_click(event) {
			const active = event.target.closest ? event.target.closest('.chzn-container') : null;

			if (active === this.container_el) {
				this.active_field = true;
			} else {
				this.close_field();
			}
		}

		// The options as Chosen read them: groups, then their options (empty options are kept, unshown, for the indexes)
		parse() {
			const parsed = [];
			let optionsIndex = 0;
			const addOption = (option, group, groupDisabled) => {
				if (option.nodeName !== 'OPTION') {
					return;
				}

				if (option.text !== '') {
					if (group != null) {
						parsed[group].children += 1;
					}

					parsed.push({
						array_index: parsed.length,
						options_index: optionsIndex,
						value: option.value,
						text: option.text,
						title: option.title || undefined,
						selected: option.selected,
						disabled: groupDisabled === true ? true : option.disabled,
						group_array_index: group,
						group_label: group != null ? parsed[group].label : null,
						classes: option.className,
						style: option.style.cssText,
					});
				} else {
					parsed.push({ array_index: parsed.length, options_index: optionsIndex, empty: true });
				}

				optionsIndex += 1;
			};

			Array.prototype.forEach.call(this.form_field.childNodes, (node) => {
				if (node.nodeName === 'OPTGROUP') {
					const position = parsed.length;

					parsed.push({
						array_index: position,
						group: true,
						label: node.label,
						title: node.title || undefined,
						children: 0,
						disabled: node.disabled,
						classes: node.className,
					});
					Array.prototype.forEach.call(node.childNodes, (child) => addOption(child, position, node.disabled));
				} else {
					addOption(node);
				}
			});

			return parsed;
		}

		results_build() {
			this.selected_option_count = null;
			this.results_data = this.parse();

			if (this.is_multiple) {
				this.search_choices.querySelectorAll('li.search-choice').forEach((choice) => choice.remove());
			} else {
				this.single_set_selected_text();

				const noSearch = this.disable_search || this.form_field.options.length <= this.disable_search_threshold;

				this.search_field_el.readOnly = noSearch;
				this.container_el.classList.toggle('chzn-container-single-nosearch', noSearch);
			}

			this.results_data.forEach((item) => {
				if (item.selected && !item.group && !item.empty) {
					if (this.is_multiple) {
						this.choice_build(item);
					} else {
						this.single_set_selected_text(this.choice_label(item));
					}
				}
			});
			this.update_results_content(this.results_option_build());
			this.search_field_disabled();
			this.show_search_field_default();
			this.search_field_scale();
		}

		results_update_field() {
			this.set_default_text();

			if (!this.is_multiple) {
				this.results_reset_cleanup();
			}

			this.result_clear_highlight();
			this.results_build();

			if (this.results_showing) {
				this.winnow_results();
			}
		}

		include_option_in_results(option) {
			if (this.is_multiple && !this.display_selected_options && option.selected) {
				return false;
			}

			if (!this.display_disabled_options && option.disabled) {
				return false;
			}

			return !option.empty;
		}

		choice_label(item) {
			return (this.include_group_label_in_selected && item.group_label != null ? '<b class="group-name">' + chosenEscape(item.group_label) + '</b>' : '') + chosenEscape(item.text);
		}

		results_option_build() {
			let content = '';
			let shown = 0;

			for (const data of this.results_data) {
				const html = data.group ? this.result_add_group(data) : this.result_add_option(data);

				if (html !== '') {
					shown += 1;
					content += html;
				}

				if (shown >= this.max_shown_results) {
					break;
				}
			}

			return content;
		}

		result_add_option(option) {
			if (!option.search_match || !this.include_option_in_results(option)) {
				return '';
			}

			const classes = [];

			if (!option.disabled && !(option.selected && this.is_multiple)) {
				classes.push('active-result');
			}

			if (option.disabled && !(option.selected && this.is_multiple)) {
				classes.push('disabled-result');
			}

			if (option.selected) {
				classes.push('result-selected');
			}

			if (option.group_array_index != null) {
				classes.push('group-option');
			}

			if (option.classes) {
				classes.push(option.classes);
			}

			return '<li id="' + this.id + '-o' + option.array_index + '" role="option" aria-selected="' + (option.selected ? 'true' : 'false') + '"'
				+ (option.disabled ? ' aria-disabled="true"' : '') + ' class="' + chosenEscape(classes.join(' ')) + '"'
				+ (option.style ? ' style="' + chosenEscape(option.style) + '"' : '') + ' data-option-array-index="' + option.array_index + '"'
				+ (option.title ? ' title="' + chosenEscape(option.title) + '"' : '') + '>' + option.search_text + '</li>';
		}

		result_add_group(group) {
			if (!(group.search_match || group.group_match) || !(group.active_options > 0)) {
				return '';
			}

			return '<li role="presentation" class="' + chosenEscape(['group-result'].concat(group.classes ? [group.classes] : []).join(' ')) + '"'
				+ (group.title ? ' title="' + chosenEscape(group.title) + '"' : '') + '>' + group.search_text + '</li>';
		}

		update_results_content(html) {
			this.search_results_el.innerHTML = html;
		}

		get_search_text() {
			return this.search_field_el.value.trim();
		}

		search_string_match(text, regex) {
			if (regex.test(text)) {
				return true;
			}

			if (this.enable_split_word_search && (text.indexOf(' ') >= 0 || text.indexOf('[') === 0)) {
				return text.replace(/\[|\]/g, '').split(' ').some((part) => regex.test(part));
			}

			return false;
		}

		// The options matching the search text, the match emphasised
		winnow_results() {
			const searchText = this.get_search_text();
			const escaped = searchText.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
			const flags = this.case_sensitive_search ? '' : 'i';
			const regex = new RegExp((this.search_contains ? '' : '^') + escaped, flags);
			const firstMatch = new RegExp(escaped, flags);
			let results = 0;

			this.no_results_clear();

			for (const option of this.results_data) {
				let group = null;

				option.search_match = false;

				if (!this.include_option_in_results(option)) {
					continue;
				}

				if (option.group) {
					option.group_match = false;
					option.active_options = 0;
				}

				if (option.group_array_index != null && this.results_data[option.group_array_index]) {
					group = this.results_data[option.group_array_index];

					if (group.active_options === 0 && group.search_match) {
						results += 1;
					}

					group.active_options += 1;
				}

				const text = option.group ? option.label : option.text;

				option.search_text = chosenEscape(text);

				if (option.group && !this.group_search) {
					continue;
				}

				option.search_match = this.search_string_match(text, regex);

				if (option.search_match && !option.group) {
					results += 1;
				}

				if (option.search_match) {
					if (searchText.length) {
						const start = text.search(firstMatch);

						if (start >= 0) {
							option.search_text = chosenEscape(text.substr(0, start)) + '<em>' + chosenEscape(text.substr(start, searchText.length)) + '</em>'
								+ chosenEscape(text.substr(start + searchText.length));
						}
					}

					if (group) {
						group.group_match = true;
					}
				} else if (option.group_array_index != null && this.results_data[option.group_array_index].search_match) {
					option.search_match = true;
				}
			}

			this.result_clear_highlight();

			if (results < 1 && searchText.length) {
				this.update_results_content('');
				this.no_results(searchText);
			} else {
				this.update_results_content(this.results_option_build());
				this.winnow_results_set_highlight();
			}
		}

		winnow_results_set_highlight() {
			const selected = !this.is_multiple ? this.search_results_el.querySelector('.result-selected.active-result') : null;
			const target = selected || this.search_results_el.querySelector('.active-result');

			if (target) {
				this.result_do_highlight(target);
			}
		}

		result_do_highlight(item) {
			if (!item) {
				return;
			}

			this.result_clear_highlight();
			this.result_highlight = item;
			item.classList.add('highlighted');
			this.search_field_el.setAttribute('aria-activedescendant', item.id);

			const list = this.search_results_el;
			const maxHeight = parseInt(window.getComputedStyle(list).maxHeight, 10) || list.clientHeight;
			const top = item.offsetTop - list.offsetTop;
			const bottom = top + item.offsetHeight;

			if (bottom >= list.scrollTop + maxHeight) {
				list.scrollTop = Math.max(bottom - maxHeight, 0);
			} else if (top < list.scrollTop) {
				list.scrollTop = top;
			}
		}

		result_clear_highlight() {
			if (this.result_highlight) {
				this.result_highlight.classList.remove('highlighted');
			}

			this.result_highlight = null;
			this.search_field_el.removeAttribute('aria-activedescendant');
		}

		results_toggle() {
			if (this.results_showing) {
				this.results_hide();
			} else {
				this.results_show();
			}
		}

		results_search() {
			if (this.results_showing) {
				this.winnow_results();
			} else {
				this.results_show();
			}
		}

		results_show() {
			if (this.is_multiple && this.max_selected_options <= this.choices_count()) {
				chosenEmit(this.form_field, 'liszt:maxselected', { chosen: this });

				return false;
			}

			this.container_el.classList.add('chzn-with-drop');
			this.results_showing = true;
			this.search_field_el.setAttribute('aria-expanded', 'true');
			this.search_field_el.focus();
			this.winnow_results();
			chosenEmit(this.form_field, 'liszt:showing_dropdown', { chosen: this });

			return true;
		}

		results_hide() {
			if (this.results_showing) {
				this.result_clear_highlight();
				this.container_el.classList.remove('chzn-with-drop');
				this.search_field_el.setAttribute('aria-expanded', 'false');
				chosenEmit(this.form_field, 'liszt:hiding_dropdown', { chosen: this });
			}

			this.results_showing = false;
		}

		show_search_field_default() {
			if (this.is_multiple && this.choices_count() < 1 && !this.active_field) {
				this.search_field_el.value = '';
				this.search_field_el.placeholder = this.default_text;
				this.search_field_el.classList.add('default');
			} else {
				this.search_field_el.value = '';
				this.search_field_el.placeholder = '';
				this.search_field_el.classList.remove('default');
			}
		}

		choices_count() {
			if (this.selected_option_count == null) {
				this.selected_option_count = Array.prototype.filter.call(this.form_field.options, (option) => option.selected).length;
			}

			return this.selected_option_count;
		}

		choices_click(event) {
			event.preventDefault();

			if (!this.results_showing && !this.is_disabled) {
				this.results_show();
			}
		}

		search_results_mouseup(event) {
			const target = event.target.closest ? event.target.closest('.active-result') : null;

			if (target) {
				this.result_highlight = target;
				this.result_select(event);
				this.search_field_el.focus();
			}
		}

		search_results_mouseover(event) {
			const target = event.target.closest ? event.target.closest('.active-result') : null;

			if (target) {
				this.result_do_highlight(target);
			}
		}

		search_results_mouseout(event) {
			if (event.target.classList && event.target.classList.contains('active-result')) {
				this.result_clear_highlight();
			}
		}

		choice_build(item) {
			const choice = document.createElement('li');

			choice.className = 'search-choice';
			choice.innerHTML = '<span>' + this.choice_label(item) + '</span>';

			if (item.disabled) {
				choice.classList.add('search-choice-disabled');
			} else {
				const close = document.createElement('a');

				close.className = 'search-choice-close';
				close.setAttribute('data-option-array-index', item.array_index);
				close.setAttribute('role', 'button');
				close.setAttribute('aria-label', (CMS.text.get('JLIB_HTML_BEHAVIOR_CLOSE', '') || '×') + ': ' + item.text);
				close.addEventListener('click', (event) => {
					event.preventDefault();
					event.stopPropagation();

					if (!this.is_disabled) {
						this.choice_destroy(close);
					}
				});
				choice.appendChild(close);
			}

			this.search_container.parentNode.insertBefore(choice, this.search_container);
		}

		choice_destroy(link) {
			if (this.result_deselect(link.getAttribute('data-option-array-index'))) {
				this.show_search_field_default();

				if (this.is_multiple && this.choices_count() > 0 && this.search_field_el.value.length < 1) {
					this.results_hide();
				}

				link.closest('li').remove();
				this.search_field_scale();
			}
		}

		results_reset() {
			this.results_data.forEach((item) => {
				item.selected = false;
			});
			this.form_field.options[0].selected = true;
			this.single_set_selected_text();
			this.show_search_field_default();
			this.results_reset_cleanup();
			chosenEmit(this.form_field, 'change');

			if (this.active_field) {
				this.results_hide();
			}
		}

		results_reset_cleanup() {
			this.current_selectedIndex = this.form_field.selectedIndex;

			const abbr = this.selected_item ? this.selected_item.querySelector('abbr') : null;

			if (abbr) {
				abbr.remove();
			}
		}

		result_select(event) {
			if (this.result_highlight) {
				const high = this.result_highlight;

				this.result_clear_highlight();

				if (this.is_multiple && this.max_selected_options <= this.choices_count()) {
					chosenEmit(this.form_field, 'liszt:maxselected', { chosen: this });

					return false;
				}

				if (this.is_multiple) {
					high.classList.remove('active-result');
				} else {
					this.results_data.forEach((item) => {
						item.selected = false;
					});
				}

				high.classList.add('result-selected');

				const item = this.results_data[high.getAttribute('data-option-array-index')];

				item.selected = true;
				this.form_field.options[item.options_index].selected = true;
				this.selected_option_count = null;

				if (this.is_multiple) {
					this.choice_build(item);
				} else {
					this.single_set_selected_text(this.choice_label(item));
				}

				if (!((event.metaKey || event.ctrlKey) && this.is_multiple)) {
					this.results_hide();
				}

				this.show_search_field_default();

				if (this.is_multiple || this.form_field.selectedIndex !== this.current_selectedIndex) {
					chosenEmit(this.form_field, 'change', { selected: this.form_field.options[item.options_index].value });
				}

				this.current_selectedIndex = this.form_field.selectedIndex;
				event.preventDefault();
				this.search_field_scale();

				return true;
			}

			// Joomla: a value typed into a single list allowing custom values (a module's position) is added to it
			if (!this.is_multiple && this.allow_custom_value && this.get_search_text() !== '') {
				const value = this.get_search_text();
				let group = Array.prototype.find.call(this.form_field.querySelectorAll('optgroup'), (item) => item.getAttribute('label') === this.custom_group_text);
				const option = document.createElement('option');

				if (!group) {
					group = document.createElement('optgroup');
					group.setAttribute('label', this.custom_group_text);
					this.form_field.appendChild(group);
				}

				option.value = this.custom_value_prefix + value;
				option.textContent = value;
				group.appendChild(option);
				option.selected = true;

				if (!event.metaKey) {
					this.results_hide();
				}

				this.results_build();
				chosenEmit(this.form_field, 'change', { selected: option.value });
			}

			return false;
		}

		// A tag typed into a multiple list allowing them (custom_tags: {prefix, minLength}): an existing option of that text, else a new one
		add_custom_tag() {
			const text = this.get_search_text();
			const tags = this.custom_tags;

			if (text.length < (tags.minLength || 1)) {
				return false;
			}

			let option = Array.prototype.find.call(this.form_field.options, (item) => item.text === text);

			if (!option) {
				option = document.createElement('option');
				option.value = (tags.prefix || '') + text;
				option.textContent = text;
				this.form_field.appendChild(option);
			}

			option.selected = true;
			this.search_field_el.value = '';
			this.results_update_field();
			chosenEmit(this.form_field, 'change', { selected: option.value });

			return true;
		}

		single_set_selected_text(text) {
			const span = this.selected_item.querySelector('span');

			if (text == null || text === chosenEscape(this.default_text)) {
				this.selected_item.classList.add('chzn-default');
				span.textContent = this.default_text;
			} else {
				this.single_deselect_control_build();
				this.selected_item.classList.remove('chzn-default');
				span.innerHTML = text;
			}
		}

		result_deselect(position) {
			const data = this.results_data[position];

			if (this.form_field.options[data.options_index].disabled) {
				return false;
			}

			data.selected = false;
			this.form_field.options[data.options_index].selected = false;
			this.selected_option_count = null;
			this.result_clear_highlight();

			if (this.results_showing) {
				this.winnow_results();
			}

			chosenEmit(this.form_field, 'change', { deselected: this.form_field.options[data.options_index].value });
			this.search_field_scale();

			return true;
		}

		single_deselect_control_build() {
			if (!this.allow_single_deselect) {
				return;
			}

			if (!this.selected_item.querySelector('abbr')) {
				const abbr = document.createElement('abbr');

				abbr.className = 'search-choice-close';
				this.selected_item.querySelector('span').after(abbr);
			}

			this.selected_item.classList.add('chzn-single-with-deselect');
		}

		no_results(terms) {
			const item = document.createElement('li');
			const span = document.createElement('span');

			item.className = 'no-results';
			item.setAttribute('role', 'presentation');
			item.appendChild(document.createTextNode(this.results_none_found + ' "'));
			span.textContent = terms;
			item.appendChild(span);
			item.appendChild(document.createTextNode('"'));
			this.search_results_el.appendChild(item);
			chosenEmit(this.form_field, 'liszt:no_results', { chosen: this });
		}

		no_results_clear() {
			this.search_results_el.querySelectorAll('.no-results').forEach((item) => item.remove());
		}

		keydown_arrow() {
			if (this.results_showing && this.result_highlight) {
				let next = this.result_highlight.nextElementSibling;

				while (next && !next.classList.contains('active-result')) {
					next = next.nextElementSibling;
				}

				if (next) {
					this.result_do_highlight(next);
				}
			} else {
				this.results_show();
			}
		}

		keyup_arrow() {
			if (!this.results_showing && !this.is_multiple) {
				this.results_show();
			} else if (this.result_highlight) {
				let previous = this.result_highlight.previousElementSibling;

				while (previous && !previous.classList.contains('active-result')) {
					previous = previous.previousElementSibling;
				}

				if (previous) {
					this.result_do_highlight(previous);
				} else {
					if (this.choices_count() > 0) {
						this.results_hide();
					}

					this.result_clear_highlight();
				}
			}
		}

		keydown_backstroke() {
			if (this.pending_backstroke) {
				this.choice_destroy(this.pending_backstroke.querySelector('a'));
				this.clear_backstroke();
			} else {
				const choices = this.search_choices.querySelectorAll('li.search-choice');
				const last = choices[choices.length - 1];

				if (last && !last.classList.contains('search-choice-disabled')) {
					this.pending_backstroke = last;

					if (this.single_backstroke_delete) {
						this.keydown_backstroke();
					} else {
						last.classList.add('search-choice-focus');
					}
				}
			}
		}

		clear_backstroke() {
			if (this.pending_backstroke) {
				this.pending_backstroke.classList.remove('search-choice-focus');
			}

			this.pending_backstroke = null;
		}

		keydown_checker(event) {
			const key = event.keyCode;

			this.search_field_scale();

			if (key !== 8 && this.pending_backstroke) {
				this.clear_backstroke();
			}

			// Tags: a comma ends one
			if (this.custom_tags && event.key === ',') {
				event.preventDefault();
				this.add_custom_tag();

				return;
			}

			switch (key) {
				case 8:
					this.backstroke_length = this.search_field_el.value.length;
					break;
				case 9:
					if (this.results_showing && !this.is_multiple) {
						this.result_select(event);
					}

					this.mouse_on_container = false;
					break;
				case 13:
					if (this.results_showing || this.custom_tags) {
						event.preventDefault();
					}

					break;
				case 32:
					if (this.disable_search) {
						event.preventDefault();
					}

					break;
				case 38:
					event.preventDefault();
					this.keyup_arrow();
					break;
				case 40:
					event.preventDefault();
					this.keydown_arrow();
					break;
				default:
					break;
			}
		}

		keyup_checker(event) {
			const key = event.keyCode;

			this.search_field_scale();

			switch (key) {
				case 8:
					if (this.is_multiple && this.backstroke_length < 1 && this.choices_count() > 0) {
						this.keydown_backstroke();
					} else if (!this.pending_backstroke) {
						this.result_clear_highlight();
						this.results_search();
					}

					break;
				case 13:
					event.preventDefault();

					if (this.custom_tags && !this.result_highlight) {
						this.add_custom_tag();
					} else if (this.results_showing) {
						this.result_select(event);
					}

					break;
				case 27:
					if (this.results_showing) {
						this.results_hide();
					}

					break;
				case 9:
				case 16:
				case 17:
				case 18:
				case 38:
				case 40:
				case 91:
					break;
				default:
					this.results_search();
			}
		}

		// A multiple list's search field as wide as its text
		search_field_scale() {
			if (!this.is_multiple) {
				return;
			}

			const field = this.search_field_el;
			const style = window.getComputedStyle(field);
			const measure = document.createElement('span');

			measure.style.cssText = 'position:absolute;left:-1000px;top:-1000px;white-space:pre;visibility:hidden;';
			['fontSize', 'fontStyle', 'fontWeight', 'fontFamily', 'lineHeight', 'textTransform', 'letterSpacing'].forEach((name) => {
				measure.style[name] = style[name];
			});
			measure.textContent = field.value || field.placeholder;
			document.body.appendChild(measure);

			let width = measure.offsetWidth + 25;
			const max = this.container_el.offsetWidth - 10;

			measure.remove();

			if (max > 0 && width > max) {
				width = max;
			}

			field.style.width = width + 'px';
		}

		// Options asked of the server as the search text changes (what Ajax Chosen did): the selected ones are kept
		remote_observer() {
			const remote = this.remote;
			const field = this.search_field_el;
			const keepTyping = remote.keepTypingMsg || CMS.text.get('JGLOBAL_KEEP_TYPING', '');
			const lookingFor = remote.lookingForMsg || CMS.text.get('JGLOBAL_LOOKING_FOR', '');
			const minimum = parseInt(remote.minTermLength, 10) || 3;
			let previous = '';
			let timer = null;
			let controller = null;

			field.addEventListener('keyup', () => {
				const typed = field.value;
				const value = typed.trim();
				const notice = this.search_results_el.querySelector('.no-results');

				if (notice) {
					notice.textContent = value.length < minimum ? keepTyping : lookingFor + " '" + value + "'";
				}

				if (value === previous) {
					return;
				}

				previous = value;
				clearTimeout(timer);

				if (value.length < minimum) {
					return;
				}

				timer = setTimeout(() => {
					const params = new URLSearchParams(Object.assign({}, remote.data || {}, { [remote.jsonTermKey || 'term']: value }));
					const post = String(remote.type || 'GET').toUpperCase() === 'POST';
					const url = remote.url + (post ? '' : (remote.url.indexOf('?') > -1 ? '&' : '?') + params.toString());

					if (controller) {
						controller.abort();
					}

					controller = typeof AbortController === 'function' ? new AbortController() : null;

					fetch(url, {
						method: post ? 'POST' : 'GET',
						body: post ? params : undefined,
						credentials: 'same-origin',
						headers: { 'X-Requested-With': 'XMLHttpRequest' },
						signal: controller ? controller.signal : undefined,
					})
						.then((response) => response.json())
						.then((data) => {
							const items = remote.map ? remote.map(data) : data;
							const keep = Array.prototype.filter.call(this.form_field.options, (option) => option.selected).map((option) => option.value + '-' + option.text);

							Array.prototype.slice.call(this.form_field.options).forEach((option) => {
								if (!option.selected) {
									option.remove();
								}
							});
							this.form_field.querySelectorAll('optgroup').forEach((group) => {
								if (!group.children.length) {
									group.remove();
								}
							});

							const add = (parent, value, text) => {
								if (keep.indexOf(value + '-' + text) < 0) {
									const option = document.createElement('option');

									option.value = value;
									option.textContent = text;
									parent.appendChild(option);
								}
							};

							(items || []).forEach((item, index) => {
								if (item && item.group) {
									let group = Array.prototype.find.call(this.form_field.querySelectorAll('optgroup'), (g) => g.label === item.text);

									if (!group) {
										group = document.createElement('optgroup');
										group.label = item.text;
										this.form_field.appendChild(group);
									}

									Object.keys(item.items || {}).forEach((key) => {
										const child = item.items[key];

										add(group, typeof child === 'string' ? key : child.value, typeof child === 'string' ? child : child.text);
									});
								} else if (typeof item === 'string') {
									add(this.form_field, index, item);
								} else if (item) {
									add(this.form_field, item.value, item.text);
								}
							});

							if (items && items.length) {
								this.results_update_field();
								this.results_show();
							} else {
								this.no_results_clear();
								this.no_results(field.value);
							}

							field.value = typed;
							this.search_field_scale();
						})
						.catch(() => {});
				}, parseInt(remote.afterTypeDelay, 10) || 500);
			});
		}
	}

	// On phones the browser's own list, as Chosen did
	const chosenSupported = () => !/iP(od|hone)|IEMobile|Windows Phone|BlackBerry|BB10|Android.*Mobile/i.test(window.navigator.userAgent);

	CMS.chosen = {
		Chosen,

		/**
		 * Searchable lists for selects (a selector, an element or a list), once each; data-searchable="false" or
		 * data-chosen (true, as Joomla's Chosen had it) on a select leaves it alone
		 *
		 * @param   {string|Element|NodeList|Array}  selects
		 * @param   {Object}                         options  Chosen's options; remote {url, type, jsonTermKey,
		 *                                                    minTermLength, afterTypeDelay, map(data)}; custom_tags
		 *                                                    {prefix, minLength}
		 * @param   {Element}                        root     Where a selector is looked for (subform rows)
		 *
		 * @return  {Chosen[]}
		 */
		attach(selects, options, root) {
			const elements = typeof selects === 'string' ? list((root || document).querySelectorAll(selects)) : list(selects && selects.jquery ? selects.get() : selects);

			if (!chosenSupported()) {
				return [];
			}

			return elements.filter((select) => select.nodeName === 'SELECT').map((select) => {
				const flag = select.getAttribute('data-chosen');

				if (chosens.has(select) || (flag && flag !== 'false' && flag !== '0') || select.getAttribute('data-searchable') === 'false') {
					return chosens.get(select);
				}

				const chosen = new Chosen(select, options);

				chosens.set(select, chosen);

				if (window.jQuery) {
					window.jQuery(select).data('chosen', chosen);
				}

				return chosen;
			}).filter(Boolean);
		},

		get: (select) => chosens.get(select && select.jquery ? select[0] : select) || null,

		// Redraws a list after a script changed its options or value (liszt:updated)
		update(select) {
			const chosen = CMS.chosen.get(select);

			if (chosen) {
				chosen.results_update_field();
			}
		},

		// The lists the page asks for (script options system.chosen: {selector: options}; system.ajaxchosen and
		// system.chosentags by selector add the server lookup and typed tags), in a container (a new subform row)
		start(root) {
			const lists = CMS.options.get('system.chosen') || {};
			const remote = CMS.options.get('system.ajaxchosen') || {};
			const tags = CMS.options.get('system.chosentags') || {};
			const special = Object.keys(remote).concat(Object.keys(tags));

			// Lists with a server lookup or tags first, and every list ('select', the administrator's default) last: a general
			// selector would take the others without their options
			const general = Object.keys(lists).filter((selector) => special.indexOf(selector) < 0 && selector.trim() === 'select');
			const others = Object.keys(lists).filter((selector) => special.indexOf(selector) < 0 && general.indexOf(selector) < 0);

			special.concat(others, general).forEach((selector) => {
				const options = Object.assign({}, lists[selector] || {});

				if (remote[selector]) {
					options.remote = remote[selector];
				}

				if (tags[selector]) {
					options.custom_tags = tags[selector];
				}

				CMS.chosen.attach(selector, options, root);
			});
		},
	};

	// Chosen's and Ajax Chosen's jQuery names, as soon as jQuery is there (before any ready handler runs)
	const chosenJquery = () => {
		const jq = window.jQuery;

		if (!jq || jq.fn.chosen) {
			return Boolean(jq);
		}

		jq.fn.chosen = function chosen(options) {
			if (options === 'destroy') {
				this.each((index, select) => {
					const instance = chosens.get(select);

					if (instance) {
						instance.destroy();
					}
				});
			} else {
				CMS.chosen.attach(this.get(), options);
			}

			return this;
		};

		jq.fn.ajaxChosen = function ajaxChosen(settings, callback, chosenOptions) {
			const remote = Object.assign({}, settings || {});

			if (typeof callback === 'function') {
				remote.map = callback;
			}

			CMS.chosen.attach(this.get(), Object.assign({}, typeof chosenOptions === 'object' ? chosenOptions : {}, { remote }));

			return this;
		};

		return true;
	};

	if (!chosenJquery()) {
		document.addEventListener('readystatechange', chosenJquery);
	}

	// Started in jQuery's ready queue when it's there, as Chosen was (templates bind liszt:ready listeners at ready)
	CMS.ready(() => {
		chosenJquery();

		if (window.jQuery) {
			window.jQuery(() => CMS.chosen.start(document));
			window.jQuery(document).on('subform-row-add', (event, row) => CMS.chosen.start(row && row.jquery ? row[0] : row || document));
		} else {
			CMS.chosen.start(document);
			document.addEventListener('subform-row-add', (event) => CMS.chosen.start((event.detail && event.detail.row) || document));
		}
	});

	/*
	 * --- Search tools (the filter bar of lists, layout joomla.searchtools.default; what jquery.searchtools.js was) ---
	 *
	 * The Filter Options and list options buttons show and hide their containers, filters with a value are marked active
	 * (the select and its Chosen container), Clear empties them and sends the form, the columns' sort links set the
	 * ordering field. Lists drawn by Chosen are redrawn through liszt:updated (jQuery's trigger when it's there).
	 */
	const searchtoolsRedraw = (element) => {
		if (window.jQuery) {
			window.jQuery(element).trigger('liszt:updated');
		} else {
			element.dispatchEvent(new CustomEvent('liszt:updated'));
		}
	};

	// As jQuery's val(''): the empty option, or nothing chosen
	const searchtoolsEmpty = (element) => {
		if (element.tagName === 'SELECT') {
			let found = false;

			Array.prototype.forEach.call(element.options, (option) => {
				option.selected = !found && option.value === '';
				found = found || option.selected;
			});

			if (!found) {
				element.selectedIndex = -1;
			}
		} else if (element.type !== 'checkbox' && element.type !== 'radio' && element.type !== 'hidden') {
			element.value = '';
		}
	};

	// As jQuery's submit(): the submit event (listeners and onsubmit), then the form unless one cancelled it
	const searchtoolsSubmit = (form) => {
		if (form && form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))) {
			form.submit();
		}
	};

	const searchtoolsForms = new WeakMap();

	class Searchtools {
		constructor(form, options) {
			const o = Object.assign({}, Searchtools.defaults, options || {});
			const inForm = (selector) => list(form.querySelectorAll(selector));

			this.form = form;
			this.options = o;
			this.filterButton = inForm(o.filterBtnSelector);
			this.filterContainer = inForm(o.filterContainerSelector);
			this.listButton = inForm(o.listBtnSelector);
			this.listContainer = inForm(o.listContainerSelector);
			this.searchField = form.querySelector(o.searchFieldSelector) || document.querySelector(o.searchFieldSelector);
			this.clearButton = list(document.querySelectorAll(o.clearBtnSelector));
			this.orderField = form.querySelector(o.orderFieldSelector) || document.querySelector(o.orderFieldSelector);
			this.activeColumn = null;
			this.activeDirection = o.activeDirection;
			this.activeOrder = o.activeOrder;

			searchtoolsForms.set(form, this);

			if (o.filtersHidden) {
				this.hideFilters();
			} else {
				this.showFilters();
			}

			if (o.listHidden) {
				this.hideList();
			} else {
				this.showList();
			}

			this.filterButton.forEach((button) => button.addEventListener('click', (event) => {
				event.preventDefault();
				event.stopPropagation();
				this.toggleFilters();
			}));
			this.listButton.forEach((button) => button.addEventListener('click', (event) => {
				event.preventDefault();
				event.stopPropagation();
				this.toggleList();
			}));
			this.clearButton.forEach((button) => button.addEventListener('click', () => this.clear()));

			// Filters with a value are marked (Chosen's changes reach jQuery's listeners only)
			this.getFilterFields().forEach((element) => {
				this.checkFilter(element);

				if (window.jQuery) {
					window.jQuery(element).on('change', () => this.checkFilter(element));
				} else {
					element.addEventListener('change', () => this.checkFilter(element));
				}
			});

			this.createOrderField();

			form.addEventListener('click', (event) => {
				const column = event.target.closest ? event.target.closest(o.orderColumnSelector) : null;
				const order = column ? column.getAttribute('data-order') || '' : '';

				if (!column || !form.contains(column) || !order.length) {
					return;
				}

				const direction = column.getAttribute('data-direction');
				const ordering = order + ' ' + direction;

				event.preventDefault();
				this.activeColumn = order;

				if (ordering !== this.activeOrder) {
					this.activeDirection = direction;
					this.activeOrder = ordering;
					this.updateFieldValue(this.orderField, ordering);
				} else {
					this.toggleDirection();
				}

				searchtoolsSubmit(form);
			});
		}

		getFilterFields() {
			return this.filterContainer.reduce((all, container) => all.concat(list(container.querySelectorAll('select, input'))), []);
		}

		getListFields() {
			return this.listContainer.reduce((all, container) => all.concat(list(container.querySelectorAll('select'))), []);
		}

		checkFilter(element) {
			// A list by its chosen option, a text input by its value (jquery.searchtools.js marked text inputs always)
			const active = element.tagName === 'SELECT' ? element.selectedIndex > -1 && element.options[element.selectedIndex].value !== '' : element.value !== '';
			const chosen = element.id ? document.getElementById(element.id.replace(/[^\w]/g, '_') + '_chzn') : null;

			element.classList.toggle('active', active);

			if (chosen) {
				chosen.classList.toggle('active', active);
			}
		}

		clear() {
			this.getFilterFields().forEach((element) => {
				searchtoolsEmpty(element);
				this.checkFilter(element);

				if (this.options.chosenSupport) {
					searchtoolsRedraw(element);
				}
			});

			if (this.options.clearListOptions) {
				this.getListFields().forEach((element) => {
					searchtoolsEmpty(element);
					this.checkFilter(element);

					if (this.options.chosenSupport) {
						searchtoolsRedraw(element);
					}
				});

				const limit = document.getElementById('list_limit');

				if (limit) {
					limit.value = this.options.defaultLimit;

					if (this.options.chosenSupport) {
						searchtoolsRedraw(limit);
					}
				}
			}

			if (this.searchField) {
				this.searchField.value = '';
			}

			searchtoolsSubmit(this.form);
		}

		showContainers(containers, shown) {
			containers.forEach((container) => {
				container.style.display = shown ? 'block' : 'none';
				container.classList.toggle('shown', shown);
			});
		}

		hideList() {
			this.showContainers(this.listContainer, false);
			this.listButton.forEach((button) => button.classList.remove('btn-primary'));
		}

		showList() {
			this.showContainers(this.listContainer, true);
			this.listButton.forEach((button) => button.classList.add('btn-primary'));
		}

		toggleList() {
			const shown = !(this.listContainer[0] && this.listContainer[0].classList.contains('shown'));

			this.showContainers(this.listContainer, shown);
			this.listButton.forEach((button) => button.classList.toggle('btn-primary', shown));
		}

		hideFilters() {
			this.showContainers(this.filterContainer, false);
			this.filterButton.forEach((button) => button.classList.remove('btn-primary'));
		}

		showFilters() {
			this.showContainers(this.filterContainer, true);
			this.filterButton.forEach((button) => button.classList.add('btn-primary'));
		}

		toggleFilters() {
			const shown = !(this.filterContainer[0] && this.filterContainer[0].classList.contains('shown'));

			this.showContainers(this.filterContainer, shown);
			this.filterButton.forEach((button) => button.classList.toggle('btn-primary', shown));
		}

		toggleDirection() {
			this.activeDirection = String(this.activeDirection).toUpperCase() === 'ASC' ? 'DESC' : 'ASC';
			this.activeOrder = this.activeColumn + ' ' + this.activeDirection;
			this.updateFieldValue(this.orderField, this.activeOrder);
		}

		createOrderField() {
			const o = this.options;

			if (!this.orderField) {
				this.orderField = document.createElement('input');
				this.orderField.type = 'hidden';
				this.orderField.id = 'js-stools-field-order';
				this.orderField.className = 'js-stools-field-order';
				this.orderField.name = o.orderFieldName;
				this.orderField.value = this.activeOrder + ' ' + this.activeDirection;
				this.form.appendChild(this.orderField);
			}

			// The sort links' orderings the list lacks
			if (this.orderField.tagName === 'SELECT') {
				list(this.form.querySelectorAll(o.orderColumnSelector)).forEach((column) => {
					const order = column.getAttribute('data-order') || '';

					if (!order.length) {
						return;
					}

					const value = order + ' ' + column.getAttribute('data-direction');

					if (!Array.prototype.some.call(this.orderField.options, (option) => option.value === value)) {
						const option = document.createElement('option');

						option.textContent = column.getAttribute('data-name');
						option.value = value;
						option.selected = column.classList.contains('active');
						this.orderField.appendChild(option);
					}
				});

				searchtoolsRedraw(this.orderField);
			}

			this.activeOrder = this.orderField.value;
		}

		updateFieldValue(field, value) {
			const type = field.getAttribute('type');

			if (type === 'hidden' || type === 'text') {
				field.setAttribute('value', value);
				field.value = value;
			} else if (field.tagName === 'SELECT') {
				let option = Array.prototype.find.call(field.options, (item) => item.value === value);

				if (!option) {
					option = document.createElement('option');
					option.textContent = value;
					option.value = value;
					field.appendChild(option);
				}

				option.selected = true;

				if (this.options.chosenSupport) {
					searchtoolsRedraw(field);
				}
			}
		}
	}

	Searchtools.defaults = {
		formSelector: '.js-stools-form',
		searchFieldSelector: '.js-stools-field-search',
		clearBtnSelector: '.js-stools-btn-clear',
		mainContainerSelector: '.js-stools',
		searchBtnSelector: '.js-stools-btn-search',
		filterBtnSelector: '.js-stools-btn-filter',
		filterContainerSelector: '.js-stools-container-filters',
		filtersHidden: true,
		listBtnSelector: '.js-stools-btn-list',
		listContainerSelector: '.js-stools-container-list',
		listHidden: false,
		orderColumnSelector: '.js-stools-column-order',
		orderBtnSelector: '.js-stools-btn-order',
		orderFieldSelector: '.js-stools-field-order',
		orderFieldName: 'list[fullordering]',
		limitFieldSelector: '.js-stools-field-limit',
		defaultLimit: 20,
		activeOrder: null,
		activeDirection: 'ASC',
		chosenSupport: true,
		clearListOptions: false,
	};

	CMS.searchtools = {
		Searchtools,
		defaults: Searchtools.defaults,

		// Once per form
		attach(form, options) {
			form = typeof form === 'string' ? document.querySelector(form) : (form && form.jquery ? form[0] : form);

			if (!form) {
				return null;
			}

			if (!searchtoolsForms.get(form)) {
				const tools = new Searchtools(form, options);

				// Where jQuery.fn.searchtools() kept its instance
				if (window.jQuery) {
					window.jQuery.data(form, 'plugin_searchtools', tools);
				}
			}

			return searchtoolsForms.get(form);
		},

		get: (form) => searchtoolsForms.get(form && form.jquery ? form[0] : form) || null,
	};

	// The forms the page asks for (script options system.searchtools: {formSelector: options}), after the lists are drawn
	CMS.ready(() => {
		const start = () => {
			const forms = CMS.options.get('system.searchtools') || {};

			Object.keys(forms).forEach((selector) => CMS.searchtools.attach(selector, Object.assign({ formSelector: selector }, forms[selector])));
		};

		if (window.jQuery) {
			window.jQuery(start);

			if (!window.jQuery.fn.searchtools) {
				window.jQuery.fn.searchtools = function searchtools(options) {
					return this.each((index, form) => {
						CMS.searchtools.attach(form, options);
					});
				};
			}
		} else {
			start();
		}
	});

	/*
	 * --- Search suggestions (Smart Search's module and search page, and jquery.compat.js' jQuery.fn.autocomplete() for
	 * extensions; what jquery.autocomplete.js was) ---
	 *
	 * As the visitor types, suggestions from the server (url?q=..., an answer {suggestions: [...]}, each a text or {value,
	 * data}) or from a list of the page's own (lookup) in a list under the field: requests wait for a pause (delay),
	 * answers are kept per text, and a text whose start had no suggestions isn't asked for; matches in <strong>. The list
	 * keeps jQuery Autocomplete's class names (.autocomplete-suggestions, .autocomplete-suggestion, .autocomplete-selected),
	 * which Smart Search's and templates' styles use. Arrows, Enter, Escape and the mouse; the field is a combobox of the
	 * listbox for screen readers.
	 */
	if (!CMS.suggest) {
		let suggestCount = 0;

		const suggestDefaults = {
			paramName: 'q', minChars: 1, delay: 500, maxHeight: 400, params: {}, preventBadQueries: true, zIndex: 9999,
			containerClass: 'autocomplete-suggestions', lookupLimit: 0,
		};

		CMS.suggest = {
			/**
			 * @param  {HTMLInputElement|string}  input
			 * @param  {Object}                   options  url (or a function(query)), paramName (q), params (more query values), minChars (1),
			 *                                             delay (500), maxHeight (400), width, lookup (suggestions, or a
			 *                                             function(query, done)), lookupFilter(suggestion, query,
			 *                                             lowerCaseQuery), lookupLimit, transformResult(response, query),
			 *                                             formatResult(suggestion, query) (HTML), onSelect(suggestion),
			 *                                             onSearchStart(params) (false stops it), onSearchComplete(query,
			 *                                             suggestions), onSearchError(query, error), autoSelectFirst,
			 *                                             noCache, preventBadQueries, zIndex, containerClass
			 *
			 * @return  {Object|null}  setOptions(), disable(), enable(), clear(), hide(), dispose(), and options
			 */
			attach(input, options) {
				input = typeof input === 'string' ? document.querySelector(input) : (input && input.jquery ? input[0] : input);

				if (!input) {
					return null;
				}

				if (input.cmsSuggest) {
					return input.cmsSuggest;
				}

				const o = Object.assign({}, suggestDefaults, options || {});
				const box = document.createElement('div');
				let cache = {};
				let empty = [];
				let items = [];
				let active = -1;
				let timer = null;
				let current = '';
				let disabled = false;

				suggestCount += 1;
				input.setAttribute('autocomplete', 'off');
				input.setAttribute('role', 'combobox');
				input.setAttribute('aria-autocomplete', 'list');
				input.setAttribute('aria-expanded', 'false');
				box.className = o.containerClass;
				box.id = 'cms-suggest-' + suggestCount;
				box.setAttribute('role', 'listbox');
				box.style.cssText = 'position:absolute;display:none;z-index:' + parseInt(o.zIndex, 10) + ';max-height:' + parseInt(o.maxHeight, 10) + 'px;';
				input.setAttribute('aria-controls', box.id);
				document.body.appendChild(box);

				const escape = (text) => String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
				const format = (suggestion, query) => escape(suggestion.value)
					.replace(new RegExp(escape(query).replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&'), 'gi'), (m) => '<strong>' + m + '</strong>');
				const place = () => {
					const rect = input.getBoundingClientRect();
					const room = document.documentElement.clientWidth;
					const width = Math.min(parseInt(o.width, 10) > 0 ? parseInt(o.width, 10) : rect.width, room);

					// Within the window: a list wider than a field near its edge moves left
					box.style.left = (Math.max(0, Math.min(rect.left, room - width)) + window.pageXOffset) + 'px';
					box.style.top = (rect.bottom + window.pageYOffset) + 'px';
					box.style.width = width + 'px';
				};
				const hide = () => {
					box.style.display = 'none';
					input.setAttribute('aria-expanded', 'false');
					input.removeAttribute('aria-activedescendant');
					active = -1;
				};
				const highlight = (index) => {
					const rows = box.children;

					if (rows[active]) {
						rows[active].classList.remove('autocomplete-selected');
						rows[active].setAttribute('aria-selected', 'false');
					}

					active = index;

					if (rows[active]) {
						rows[active].classList.add('autocomplete-selected');
						rows[active].setAttribute('aria-selected', 'true');
						input.setAttribute('aria-activedescendant', rows[active].id);

						if (rows[active].offsetTop < box.scrollTop) {
							box.scrollTop = rows[active].offsetTop;
						} else if (rows[active].offsetTop + rows[active].offsetHeight > box.scrollTop + box.clientHeight) {
							box.scrollTop = rows[active].offsetTop + rows[active].offsetHeight - box.clientHeight;
						}
					} else {
						input.removeAttribute('aria-activedescendant');
					}
				};
				const choose = (index) => {
					const suggestion = items[index];

					if (suggestion !== undefined) {
						input.value = suggestion.value;
						hide();

						if (o.onSelect) {
							o.onSelect.call(input, suggestion);
						}
					}
				};
				const show = (query, list) => {
					items = list.map((item) => (typeof item === 'string' ? { value: item, data: null } : item)).filter((item) => item && item.value !== undefined);

					if (!items.length || input.value.trim() !== query || document.activeElement !== input) {
						hide();

						return;
					}

					box.innerHTML = items.map((item, index) => '<div class="autocomplete-suggestion" role="option" aria-selected="false" id="' + box.id + '-' + index
						+ '" data-index="' + index + '">' + (o.formatResult ? o.formatResult(item, query) : format(item, query)) + '</div>').join('');
					active = -1;
					place();
					box.style.display = 'block';
					input.setAttribute('aria-expanded', 'true');

					if (o.autoSelectFirst) {
						highlight(0);
					}
				};
				const answer = (query, list) => {
					if (!o.noCache) {
						cache[query] = list;
					}

					if (!list.length && o.preventBadQueries) {
						empty.push(query);
					}

					if (o.onSearchComplete) {
						o.onSearchComplete.call(input, query, list);
					}

					if (query === current) {
						show(query, list);
					}
				};
				const ask = (query) => {
					if (cache[query]) {
						show(query, cache[query]);

						return;
					}

					// A text whose start had no suggestions has none either
					if (empty.some((start) => query.indexOf(start) === 0)) {
						hide();

						return;
					}

					current = query;

					// The page's own suggestions
					if (o.lookup) {
						if (typeof o.lookup === 'function') {
							o.lookup(query, (data) => answer(query, (data && data.suggestions) || []));
						} else {
							const lower = query.toLowerCase();
							const filter = o.lookupFilter || ((suggestion) => suggestion.value.toLowerCase().indexOf(lower) > -1);
							const list = o.lookup.map((item) => (typeof item === 'string' ? { value: item, data: null } : item)).filter((item) => filter(item, query, lower));

							answer(query, o.lookupLimit > 0 ? list.slice(0, o.lookupLimit) : list);
						}

						return;
					}

					const params = Object.assign({}, o.params);

					params[o.paramName] = query;

					if (o.onSearchStart && o.onSearchStart.call(input, params) === false) {
						return;
					}

					const post = String(o.type || 'GET').toUpperCase() === 'POST';
					const body = new URLSearchParams(params).toString();
					const url = typeof o.url === 'function' ? o.url.call(input, query) : o.url;

					fetch(post ? url : url + (url.indexOf('?') > -1 ? '&' : '?') + body, {
						method: post ? 'POST' : 'GET',
						body: post ? body : undefined,
						credentials: 'same-origin',
						headers: post ? { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' } : { 'X-Requested-With': 'XMLHttpRequest' },
					})
						.then((response) => response.text())
						.then((text) => {
							const data = o.transformResult ? o.transformResult(text, query) : JSON.parse(text);

							answer(query, data && Array.isArray(data.suggestions) ? data.suggestions : []);
						})
						.catch((error) => {
							hide();

							if (o.onSearchError) {
								o.onSearchError.call(input, query, error);
							}
						});
				};

				const onInput = () => {
					const query = input.value.trim();

					clearTimeout(timer);

					if (disabled || query.length < o.minChars) {
						hide();

						return;
					}

					timer = setTimeout(() => ask(query), cache[query] ? 0 : parseInt(o.delay, 10) || 0);
				};
				const onKeyDown = (event) => {
					const open = box.style.display === 'block';

					if (event.key === 'ArrowDown' && open) {
						event.preventDefault();
						highlight(Math.min(active + 1, items.length - 1));
					} else if (event.key === 'ArrowUp' && open) {
						event.preventDefault();
						highlight(Math.max(active - 1, 0));
					} else if (event.key === 'Enter' && open && active > -1) {
						// The suggestion goes into the field; the next Enter sends the form
						event.preventDefault();
						choose(active);
					} else if (event.key === 'Escape' && open) {
						hide();
					} else if (event.key === 'Tab') {
						hide();
					}
				};
				const onBlur = () => setTimeout(hide, 150);
				const onResize = () => {
					if (box.style.display === 'block') {
						place();
					}
				};

				input.addEventListener('input', onInput);
				input.addEventListener('keydown', onKeyDown);
				input.addEventListener('blur', onBlur);
				box.addEventListener('mousedown', (event) => {
					const row = event.target.closest ? event.target.closest('.autocomplete-suggestion') : null;

					// The field keeps its focus
					event.preventDefault();

					if (row) {
						choose(parseInt(row.getAttribute('data-index'), 10));
					}
				});
				box.addEventListener('mouseover', (event) => {
					const row = event.target.closest ? event.target.closest('.autocomplete-suggestion') : null;

					if (row) {
						highlight(parseInt(row.getAttribute('data-index'), 10));
					}
				});
				window.addEventListener('resize', onResize);

				input.cmsSuggest = {
					options: o,
					element: input,
					container: box,
					setOptions(more) {
						Object.assign(o, more || {});
						box.className = o.containerClass;
						box.style.zIndex = parseInt(o.zIndex, 10);
						box.style.maxHeight = parseInt(o.maxHeight, 10) + 'px';
					},
					disable() {
						disabled = true;
						clearTimeout(timer);
						hide();
					},
					enable() {
						disabled = false;
					},
					clear() {
						cache = {};
						empty = [];
						items = [];
						current = '';
					},
					hide,
					dispose() {
						clearTimeout(timer);
						input.removeEventListener('input', onInput);
						input.removeEventListener('keydown', onKeyDown);
						input.removeEventListener('blur', onBlur);
						window.removeEventListener('resize', onResize);
						input.removeAttribute('role');
						input.removeAttribute('aria-autocomplete');
						input.removeAttribute('aria-expanded');
						input.removeAttribute('aria-controls');
						input.removeAttribute('aria-activedescendant');
						box.remove();
						delete input.cmsSuggest;
					},
				};

				return input.cmsSuggest;
			},
		};

		// The fields the page asks for (script options system.suggest: {selector: options})
		CMS.ready(() => {
			const fields = CMS.options.get('system.suggest') || {};

			Object.keys(fields).forEach((selector) => CMS.suggest.attach(selector, fields[selector]));
		});
	}

	/* --- Cropping: selecting an area of an image (CMS.cropper; the template editor's image tool, what Jcrop was) --- */

	CMS.cropper = {
		/**
		 * Lets an area of an image be selected: drawn by dragging, moved, resized from its corners and edges, nudged
		 * with the arrow keys (Shift: 10 pixels), cleared with Escape or a click outside it. Coordinates are in the
		 * image's own pixels (options.trueSize, as the image may be shown smaller), as Jcrop gave them. With an
		 * aspectRatio (width / height) the area keeps that shape: a corner resizes from the opposite one, an edge from
		 * the opposite edge around the area's middle, always inside the image.
		 *
		 * @param  {HTMLImageElement}  image
		 * @param  {Object}            options  trueSize [width, height]; aspectRatio (0: free); onChange(c),
		 *                                      onSelect(c), onRelease(); c is {x, y, x2, y2, w, h}
		 *
		 * @return  {Object}  release(), setSelect([x, y, x2, y2]), maximise(), setOptions(options), getSelection(),
		 *                    destroy()
		 */
		attach(image, options) {
			options = options || {};

			const wrapper = document.createElement('div');
			const box = document.createElement('div');
			const directions = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
			let area = null;
			let drag = null;

			wrapper.className = 'cms-crop';
			wrapper.tabIndex = 0;
			box.className = 'cms-crop-selection';
			box.hidden = true;
			box.innerHTML = directions.map((dir) => '<span class="cms-crop-handle" data-dir="' + dir + '"></span>').join('');
			image.parentNode.insertBefore(wrapper, image);
			wrapper.appendChild(image);
			wrapper.appendChild(box);
			image.draggable = false;

			// The image's own size, and the scale of what's shown
			const size = () => ({
				width: (options.trueSize && options.trueSize[0]) || image.naturalWidth || image.clientWidth,
				height: (options.trueSize && options.trueSize[1]) || image.naturalHeight || image.clientHeight,
			});
			const scale = () => size().width / (image.clientWidth || 1);
			const ratio = () => (options.aspectRatio > 0 ? options.aspectRatio : 0);

			// An area of the ratio from a corner (ax, ay) towards (sx, sy), as wide as the pointer asks, inside the image
			const fromCorner = (ax, ay, sx, sy, w, h) => {
				const r = ratio();

				w = Math.min(Math.max(w, h * r), sx > 0 ? image.clientWidth - ax : ax, (sy > 0 ? image.clientHeight - ay : ay) * r);
				h = w / r;

				return { x: sx > 0 ? ax : ax - w, y: sy > 0 ? ay : ay - h, w, h };
			};

			// The same from an edge: the opposite edge stays, the area grows around its middle
			const fromEdge = (dir, p, from) => {
				const r = ratio();
				const width = image.clientWidth;
				const height = image.clientHeight;

				if (dir === 'e' || dir === 'w') {
					const middle = from.y + from.h / 2;
					const fixed = dir === 'e' ? from.x : from.x + from.w;
					const w = Math.max(0, Math.min(dir === 'e' ? p.x - fixed : fixed - p.x, dir === 'e' ? width - fixed : fixed, 2 * Math.min(middle, height - middle) * r));

					return { x: dir === 'e' ? fixed : fixed - w, y: middle - w / r / 2, w, h: w / r };
				}

				const middle = from.x + from.w / 2;
				const fixed = dir === 's' ? from.y : from.y + from.h;
				const h = Math.max(0, Math.min(dir === 's' ? p.y - fixed : fixed - p.y, dir === 's' ? height - fixed : fixed, 2 * Math.min(middle, width - middle) / r));

				return { x: middle - h * r / 2, y: dir === 's' ? fixed : fixed - h, w: h * r, h };
			};

			const coords = () => {
				const s = scale();
				const c = {
					x: Math.round(area.x * s), y: Math.round(area.y * s), x2: Math.round((area.x + area.w) * s), y2: Math.round((area.y + area.h) * s),
				};

				c.w = c.x2 - c.x;
				c.h = c.y2 - c.y;

				return c;
			};

			const draw = () => {
				box.hidden = !area;

				if (area) {
					Object.assign(box.style, { left: area.x + 'px', top: area.y + 'px', width: area.w + 'px', height: area.h + 'px' });
				}

				wrapper.classList.toggle('has-selection', !!area);
			};

			const release = () => {
				area = null;
				draw();

				if (options.onRelease) {
					options.onRelease.call(api);
				}
			};

			const changed = (selected) => {
				draw();

				const callback = selected ? options.onSelect : options.onChange;

				if (callback) {
					callback.call(api, coords());
				}
			};

			// A point of the pointer in the shown image, inside it
			const point = (event) => {
				const rect = image.getBoundingClientRect();

				return {
					x: Math.max(0, Math.min(rect.width, event.clientX - rect.left)),
					y: Math.max(0, Math.min(rect.height, event.clientY - rect.top)),
				};
			};

			wrapper.addEventListener('pointerdown', (event) => {
				if (event.button !== 0) {
					return;
				}

				const p = point(event);
				const handle = event.target.closest('.cms-crop-handle');

				event.preventDefault();
				// Focused for the keys (Escape, arrows), without the ring keyboard focus shows
				wrapper.focus({ preventScroll: true, focusVisible: false });
				wrapper.setPointerCapture(event.pointerId);

				if (handle && area) {
					drag = { mode: 'resize', dir: handle.getAttribute('data-dir'), start: p, from: Object.assign({}, area) };
				} else if (area && event.target.closest('.cms-crop-selection')) {
					drag = { mode: 'move', start: p, from: Object.assign({}, area) };
				} else {
					drag = { mode: 'draw', start: p, from: null };
				}
			});

			wrapper.addEventListener('pointermove', (event) => {
				if (!drag) {
					return;
				}

				const p = point(event);
				const width = image.clientWidth;
				const height = image.clientHeight;

				if (drag.mode === 'draw' && ratio()) {
					area = fromCorner(drag.start.x, drag.start.y, p.x < drag.start.x ? -1 : 1, p.y < drag.start.y ? -1 : 1, Math.abs(p.x - drag.start.x), Math.abs(p.y - drag.start.y));
				} else if (drag.mode === 'draw') {
					area = { x: Math.min(drag.start.x, p.x), y: Math.min(drag.start.y, p.y), w: Math.abs(p.x - drag.start.x), h: Math.abs(p.y - drag.start.y) };
				} else if (drag.mode === 'move') {
					area.x = Math.max(0, Math.min(width - drag.from.w, drag.from.x + p.x - drag.start.x));
					area.y = Math.max(0, Math.min(height - drag.from.h, drag.from.y + p.y - drag.start.y));
				} else if (ratio() && drag.dir.length === 2) {
					const from = drag.from;
					const sx = drag.dir.indexOf('e') !== -1 ? 1 : -1;
					const sy = drag.dir.indexOf('s') !== -1 ? 1 : -1;
					const ax = sx > 0 ? from.x : from.x + from.w;
					const ay = sy > 0 ? from.y : from.y + from.h;

					area = fromCorner(ax, ay, sx, sy, Math.max(0, (p.x - ax) * sx), Math.max(0, (p.y - ay) * sy));
				} else if (ratio()) {
					area = fromEdge(drag.dir, p, drag.from);
				} else {
					// Resizing: the edges the handle carries follow the pointer, the others stay
					let left = drag.from.x;
					let top = drag.from.y;
					let right = drag.from.x + drag.from.w;
					let bottom = drag.from.y + drag.from.h;

					if (drag.dir.indexOf('w') !== -1) {
						left = p.x;
					}

					if (drag.dir.indexOf('e') !== -1) {
						right = p.x;
					}

					if (drag.dir.indexOf('n') !== -1) {
						top = p.y;
					}

					if (drag.dir.indexOf('s') !== -1) {
						bottom = p.y;
					}

					area = { x: Math.min(left, right), y: Math.min(top, bottom), w: Math.abs(right - left), h: Math.abs(bottom - top) };
				}

				changed(false);
			});

			const end = () => {
				if (!drag) {
					return;
				}

				const mode = drag.mode;

				drag = null;

				// A click without dragging clears the selection
				if (!area || area.w < 3 || area.h < 3) {
					if (mode !== 'move') {
						release();
					}

					return;
				}

				changed(true);
			};

			wrapper.addEventListener('pointerup', end);
			wrapper.addEventListener('pointercancel', end);

			wrapper.addEventListener('keydown', (event) => {
				// Only the selection goes (a window around the image stays open)
				if (event.key === 'Escape' && area) {
					event.preventDefault();
					event.stopPropagation();
					release();

					return;
				}

				const moves = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };

				if (!area || !moves[event.key]) {
					return;
				}

				const step = event.shiftKey ? 10 : 1;

				event.preventDefault();
				area.x = Math.max(0, Math.min(image.clientWidth - area.w, area.x + moves[event.key][0] * step));
				area.y = Math.max(0, Math.min(image.clientHeight - area.h, area.y + moves[event.key][1] * step));
				changed(true);
			});

			const api = {
				release,

				// Selects an area given in the image's own pixels
				setSelect(c) {
					const s = scale();

					area = { x: c[0] / s, y: c[1] / s, w: (c[2] - c[0]) / s, h: (c[3] - c[1]) / s };
					changed(true);
				},

				// The largest area the ratio allows (the whole image when free), in the middle
				maximise() {
					const { width, height } = size();
					const r = ratio() || width / height;
					const w = Math.min(width, height * r);
					const h = w / r;

					api.setSelect([(width - w) / 2, (height - h) / 2, (width + w) / 2, (height + h) / 2]);
				},

				setOptions(more) {
					Object.assign(options, more || {});

					return api;
				},

				getSelection: () => (area ? coords() : null),

				destroy() {
					wrapper.parentNode.insertBefore(image, wrapper);
					wrapper.remove();
				},
			};

			return api;
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

	// The old calendar of behavior.calendar() (removed in 3.18): Calendar.setup() turns its field into a date field and
	// hides the button that opened it
	if (!window.Calendar) {
		window.Calendar = {
			setup(params) {
				params = params || {};

				CMS.ready(() => {
					const field = typeof params.inputField === 'string' ? document.getElementById(params.inputField) : params.inputField;
					const button = typeof params.button === 'string' ? document.getElementById(params.button) : params.button;

					if (button && button !== field) {
						button.hidden = true;
					}

					CMS.dateField.attach(field, params.ifFormat || (params.showsTime ? '%Y-%m-%d %H:%M' : '%Y-%m-%d'));
				});
			},
		};
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
		// Elements with the class hasTooltip or hasPopover, or a data-tooltip or data-popover attribute, have them on every
		// page (the options bootstrap.tooltip and bootstrap.popover give by default); pages register other selectors, or
		// other options for these (a selector keeps its place, so the first matching one still wins)
		selectors: {
			tooltip: { '.hasTooltip': { html: true }, '[data-tooltip]': { html: true } },
			popover: { '.hasPopover': { html: true, trigger: 'hover focus' }, '[data-popover]': { html: true, trigger: 'hover focus' } },
		},
		shown: new Map(),
		counter: 0,

		// Elements given their own options (attach(): jQuery's .tooltip() and .popover() where Bootstrap's script isn't loaded)
		elements: new WeakMap(),

		/**
		 * An element's own tooltip or popover, as Bootstrap's jQuery plugins made one: options as Bootstrap's (its defaults,
		 * then the element's data-placement, data-trigger, data-html, data-delay, then the given ones; title and content may be
		 * functions, content an element), or a method: show, hide, toggle, destroy. With a selector option, the elements it
		 * matches get them.
		 *
		 * @param  {Element}        element
		 * @param  {string}         type     tooltip or popover
		 * @param  {Object|string}  option
		 */
		attach(element, type, option) {
			if (typeof option === 'string') {
				const options = CMS.tip.elements.get(element);

				if (option === 'destroy') {
					CMS.tip.hide(element);
					CMS.tip.elements.delete(element);
					element.removeAttribute('data-cms-tip');
				} else if (option === 'hide' || (option === 'toggle' && CMS.tip.shown.has(element))) {
					CMS.tip.hide(element);
				} else if (options && (option === 'show' || option === 'toggle')) {
					CMS.tip.show({ element, type, options });
				}

				return;
			}

			const data = element.dataset;
			const own = {};

			if (data.placement) {
				own.placement = data.placement;
			}

			if (data.trigger) {
				own.trigger = data.trigger;
			}

			if (data.html) {
				own.html = data.html !== 'false';
			}

			if (data.delay) {
				own.delay = parseInt(data.delay, 10) || 0;
			}

			const options = Object.assign({
				html: false,
				placement: type === 'popover' ? 'right' : 'top',
				trigger: type === 'popover' ? 'click' : 'hover focus',
			}, own, option || {});

			if (options.selector) {
				CMS.tip.register(options.selector, options, type);

				return;
			}

			element.setAttribute('data-cms-tip', type);
			CMS.tip.elements.set(element, options);
		},

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

			const own = target.closest('[data-cms-tip]');

			if (own && CMS.tip.elements.has(own)) {
				return { element: own, type: own.getAttribute('data-cms-tip') === 'popover' ? 'popover' : 'tooltip', options: CMS.tip.elements.get(own) };
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

			return element.getAttribute('data-original-title') || (typeof options.title === 'function' ? options.title.call(element) : options.title) || '';
		},

		show(found) {
			const { element, type, options } = found;

			if (CMS.tip.shown.has(element)) {
				return;
			}

			const title = CMS.tip.title(element, options);
			const content = type === 'popover'
				// The option first, then the attribute, as Bootstrap's popover
				? (typeof options.content === 'function' ? options.content.call(element) : options.content) || element.getAttribute('data-content') || ''
				: '';

			if (!title && !content) {
				return;
			}

			const tip = document.createElement('div');
			const html = options.html !== false;
			const fill = (node, text) => {
				if (text && text.nodeType) {
					node.textContent = '';
					node.appendChild(text);
				} else if (html) {
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

		const attachAll = () => Array.prototype.forEach.call(document.querySelectorAll('form.form-validate, form[data-validate]'), attachToForm);

		return { isValid, validate, setHandler, attachToForm, custom, attachAll };
	};

	// From the start, so scripts can add handlers before the page is ready; attached to every form.form-validate and
	// form[data-validate] once it is (the page needn't ask: behavior.formvalidator only adds its message). frontend.js may
	// have made it already: the same one is kept
	CMS.formValidator = CMS.formValidator || createValidator();
	document.formvalidator = CMS.formValidator;

	CMS.ready(() => CMS.formValidator.attachAll());

	// The old constructor: a validator of its own, attached to the forms at once
	window.JFormValidator = function JFormValidator() {
		const validator = createValidator();

		validator.attachAll();

		return validator;
	};

	/* --- The switcher (behavior.switcher: togglers in #submenu, pages in #config-document; what JSwitcher was) --- */

	CMS.switcher = {
		/**
		 * Shows one page at a time: the one of the clicked toggler (a page's id is the prefix plus its toggler's id), or the
		 * address's #hash, else the first. The open one is written into the address's hash.
		 *
		 * @param  {string|Element}  toggler  The togglers' container
		 * @param  {string|Element}  element  The pages' container
		 * @param  {Object}          options  onShow(page), onHide(page), togglerSelector ('a'), elementSelector ('div.tab'),
		 *                                    elementPrefix ('page-')
		 *
		 * @return  {Object}  display(id), hide(page), hideAll(), show(page)
		 */
		create(toggler, element, options) {
			options = Object.assign({
				onShow() {},
				onHide() {},
				togglerSelector: 'a',
				elementSelector: 'div.tab',
				elementPrefix: 'page-',
			}, options);

			toggler = byId(toggler);
			element = byId(element);

			const togglers = toggler ? Array.prototype.slice.call(toggler.querySelectorAll(options.togglerSelector)) : [];
			const pages = element ? Array.prototype.slice.call(element.querySelectorAll(options.elementSelector)) : [];
			let current = null;

			const hide = (page) => {
				options.onHide(page);
				page.style.display = 'none';
			};

			const show = (page) => {
				options.onShow(page);
				page.style.display = '';

				if (getComputedStyle(page).display === 'none') {
					page.style.display = 'block';
				}
			};

			const hideAll = () => {
				pages.forEach((page) => {
					page.style.display = 'none';
				});
				togglers.forEach((item) => item.classList.remove('active'));
			};

			const display = (id) => {
				const item = id ? document.getElementById(id) : null;
				const page = id ? document.getElementById(options.elementPrefix + id) : null;

				if (!item || !page || id === current) {
					return;
				}

				if (current) {
					const open = document.getElementById(options.elementPrefix + current);

					if (open) {
						hide(open);
					}

					document.getElementById(current).classList.remove('active');
				}

				show(page);
				item.classList.add('active');
				current = id;
				location.hash = current;
				window.scrollTo(window.scrollX, 0);
			};

			if (togglers.length && togglers.length === pages.length) {
				hideAll();
				togglers.forEach((item) => item.addEventListener('click', () => display(item.id)));
				display(location.hash.substring(1) || togglers[0].id);
			}

			return { display, hide, hideAll, show };
		},
	};

	/* --- Combo boxes (behavior.combobox; layout joomla.form.field.combo: a text field with a list of suggestions) --- */

	const combos = new WeakSet();

	// What jQuery's width()/height() gave: the content box, measured shown when the element itself is hidden
	const contentSize = (element, side) => {
		const measure = () => {
			const style = getComputedStyle(element);

			return side === 'width'
				? element.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight)
				: element.clientHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom);
		};

		if (getComputedStyle(element).display !== 'none') {
			return measure();
		}

		const saved = element.getAttribute('style');

		element.style.position = 'absolute';
		element.style.visibility = 'hidden';
		element.style.display = 'block';

		const size = measure();

		if (saved === null) {
			element.removeAttribute('style');
		} else {
			element.setAttribute('style', saved);
		}

		return size;
	};

	CMS.combobox = {
		/**
		 * Turns a div.combobox (a text field, a button and a ul.dropdown-menu of suggestions) into a combo box: the list
		 * opens with the field, typing filters it (updateList), arrows and Enter choose
		 *
		 * @param  {Element}  container  The div.combobox
		 * @param  {Object}   options    updateList (true)
		 */
		attach(container, options) {
			if (!container || combos.has(container)) {
				return;
			}

			options = Object.assign({ updateList: true }, options);

			const input = container.querySelector('input[type="text"]');
			const group = container.querySelector('div.btn-group');
			const button = group ? group.querySelector('[type="button"]') : null;
			const menu = container.querySelector('ul.dropdown-menu');

			if (!input || !group || !menu) {
				return;
			}

			combos.add(container);

			const links = Array.prototype.slice.call(menu.querySelectorAll('li a'));
			const visible = () => links.filter((link) => link.offsetWidth > 0 || link.offsetHeight > 0);
			const state = { empty: false, clicked: false, hovered: null, pickOnBlur: true, blocking: false };

			const inViewport = (element) => {
				const rect = element.getBoundingClientRect();

				return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight)
					&& rect.right <= (window.innerWidth || document.documentElement.clientWidth);
			};

			const preventSubmit = (event) => {
				if (event.keyCode === 13) {
					event.preventDefault();
				}
			};

			const blockSubmit = (on) => {
				if (on !== state.blocking) {
					['keypress', 'keydown', 'keyup'].forEach((type) => input[on ? 'addEventListener' : 'removeEventListener'](type, preventSubmit));
					state.blocking = on;
				}
			};

			const highlight = (where) => {
				if (where === 'clear') {
					if (state.hovered) {
						state.hovered.classList.remove('hover');
					}

					state.hovered = null;

					return;
				}

				const shown = visible();
				const now = shown.filter((link) => link.classList.contains('hover'))[0];
				let index = shown.indexOf(now);

				if (where === 'prev') {
					index = index === -1 ? shown.length - 1 : index - 1;
				} else {
					index = index === shown.length - 1 ? 0 : index + 1;
				}

				if (now) {
					now.classList.remove('hover');
				}

				state.hovered = shown[index] || null;

				if (state.hovered) {
					state.hovered.classList.add('hover');

					const box = menu.getBoundingClientRect();
					const rect = state.hovered.getBoundingClientRect();

					if (!(rect.top >= box.top && rect.left >= box.left && rect.top + rect.height <= box.top + box.height)) {
						menu.scrollTop = rect.top - box.top + menu.scrollTop;
					}
				}
			};

			const drop = () => {
				if (state.empty) {
					return;
				}

				const top = -(contentSize(input, 'height') + contentSize(menu, 'height'));

				// Opened in view: above the field when there's no room below
				menu.style.top = '100%';
				container.classList.add('nav-hover');
				group.classList.add('open');

				if (!inViewport(menu)) {
					menu.style.top = top + 'px';
				}

				blockSubmit(true);
			};

			const pick = () => {
				container.classList.remove('nav-hover');
				group.classList.remove('open');

				if (state.clicked) {
					state.clicked = false;
					state.empty = true;
				}

				highlight('clear');
				blockSubmit(false);
			};

			const updateList = (event) => {
				let key = event.keyCode || event.which;

				key = event.ctrlKey || event.altKey ? -1 : key;

				if ((key > 47 && key < 59) || (key > 62 && key < 127) || key === 32 || key === 8) {
					const text = input.value.toLowerCase();
					let hidden = 0;
					let moveHighlight = false;

					links.forEach((link) => {
						if (link.innerHTML.toLowerCase().indexOf(text) === 0) {
							link.style.display = '';
						} else {
							link.style.display = 'none';
							moveHighlight = moveHighlight || link.classList.contains('hover');
							hidden++;
						}
					});

					if (hidden === links.length) {
						state.empty = true;
						pick();
					} else {
						state.empty = false;

						if (moveHighlight) {
							highlight('clear');
						}

						drop();
					}
				} else if (!state.empty) {
					if (key === 38) {
						highlight('prev');
					} else if (key === 40) {
						highlight('next');
					} else if (key === 13 && state.hovered) {
						input.value = state.hovered.textContent;
						pick();
					}
				}
			};

			// The list as wide as the field and aligned with it, scrolling past 150px
			const width = contentSize(container, 'width');
			const total = width - 3;

			if (contentSize(menu, 'width') < total) {
				menu.style.width = total + 'px';
			}

			menu.style.left = (-width + contentSize(group, 'width')) + 'px';
			menu.style.maxHeight = '150px';
			menu.style.overflowY = 'scroll';

			input.addEventListener('focus', drop);
			input.addEventListener('blur', () => {
				if (state.pickOnBlur) {
					pick();
				}
			});

			if (options.updateList) {
				input.addEventListener('keyup', updateList);
			}

			menu.addEventListener('mouseenter', () => {
				highlight('clear');
				state.pickOnBlur = false;
			});
			menu.addEventListener('mouseleave', () => {
				state.pickOnBlur = true;
			});

			if (button) {
				button.addEventListener('click', () => {
					links.forEach((link) => {
						link.style.display = '';
					});
					state.clicked = state.empty;
					state.empty = false;
					input.focus();
				});
			}

			Array.prototype.forEach.call(menu.querySelectorAll('li'), (item) => item.addEventListener('click', (event) => {
				event.preventDefault();
				event.stopPropagation();
				input.value = event.target.textContent;
				pick();
			}));

			links.forEach((link) => {
				link.addEventListener('mouseenter', () => {
					link.classList.add('hover');
					state.hovered = link;
				});
				link.addEventListener('mouseleave', () => link.classList.remove('hover'));
			});
		},
	};

	/* --- Bootstrap 2's plugins: dropdowns, collapse, tabs, modals and alerts (CMS.bootstrap; what bootstrap.js did) --- */

	/*
	 * Bootstrap 2.3's markup and data attributes (data-toggle="dropdown|collapse|tab|pill|modal", data-dismiss="modal|alert",
	 * data-target, data-parent, data-backdrop, data-keyboard) and classes (.open, .in, .collapsed, .active), so templates,
	 * layouts and extensions' markup work unchanged. Events go out through jQuery when it's on the page, with the names
	 * Bootstrap fired there (show, shown, hide, hidden; "hideme" for collapse, as Joomla's copy had it; close and closed for
	 * alerts: handlers bound to "show.bs.modal" get them too), and natively as cms:<plugin>-<name> (detail.relatedTarget);
	 * a cancelled show, hide or close stops it. When Bootstrap's own script is on the page (an extension asked for it), it
	 * does all of this and these stand aside: its plugins have a Constructor. jQuery.fn.dropdown, collapse, tab, modal, alert,
	 * tooltip and popover (on CMS.tip) are stand-ins, defined only where Bootstrap's aren't.
	 */
	const bootstrapLoaded = (name) => {
		const jq = window.jQuery;

		return Boolean(jq && jq.fn && jq.fn[name] && jq.fn[name].Constructor);
	};

	const bsEmit = (element, plugin, name, related, cancelable) => {
		const jq = window.jQuery;
		let prevented = false;

		if (jq && jq.Event) {
			const event = jq.Event(name, related ? { relatedTarget: related } : {});

			jq(element).trigger(event);
			prevented = event.isDefaultPrevented();
		}

		const native = new CustomEvent('cms:' + plugin + '-' + (name === 'hideme' ? 'hide' : name), {
			bubbles: true,
			cancelable: Boolean(cancelable),
			detail: { relatedTarget: related || null },
		});

		return !element.dispatchEvent(native) || prevented ? false : true;
	};

	// The callback once the element's CSS transition has run (at once without one)
	const afterTransition = (element, callback) => {
		const style = window.getComputedStyle(element);
		const seconds = (list) => Math.max(0, ...String(list).split(',').map((value) => parseFloat(value) || 0));
		const duration = (seconds(style.transitionDuration) + seconds(style.transitionDelay)) * 1000;
		let done = false;

		const finish = () => {
			if (!done) {
				done = true;
				element.removeEventListener('transitionend', onEnd); // eslint-disable-line no-use-before-define
				callback();
			}
		};
		const onEnd = (event) => {
			if (event.target === element) {
				finish();
			}
		};

		if (!duration) {
			finish();

			return;
		}

		element.addEventListener('transitionend', onEnd);
		window.setTimeout(finish, duration + 50);
	};

	// What a link or button acts on: its data-target, else the #id its href ends with
	const bsTarget = (link) => {
		let selector = link.getAttribute('data-target');

		if (!selector) {
			const href = link.getAttribute('href');

			selector = href && /#[^\s]+$/.test(href) ? href.replace(/.*(?=#[^\s]+$)/, '') : '';
		}

		try {
			return selector && selector !== '#' ? document.querySelector(selector) : null;
		} catch (error) {
			return null;
		}
	};

	const bsDisabled = (element) => element.classList.contains('disabled') || element.disabled === true;

	const bsVisible = (element) => Boolean(element.offsetWidth || element.offsetHeight || element.getClientRects().length);

	CMS.bootstrap = {
		/* Dropdowns: .open on the toggle's parent (or its data-target); a click elsewhere closes them, arrow keys move */
		dropdown: {
			toggles: '[data-toggle="dropdown"]',

			parent(toggle) {
				return bsTarget(toggle) || toggle.parentNode;
			},

			clear() {
				Array.prototype.forEach.call(document.querySelectorAll('.dropdown-backdrop'), (backdrop) => backdrop.remove());
				Array.prototype.forEach.call(document.querySelectorAll(CMS.bootstrap.dropdown.toggles), (toggle) => {
					const parent = CMS.bootstrap.dropdown.parent(toggle);

					if (parent && parent.classList) {
						parent.classList.remove('open');
					}
				});
			},

			toggle(toggle) {
				if (bsDisabled(toggle)) {
					return;
				}

				const parent = CMS.bootstrap.dropdown.parent(toggle);
				const active = parent.classList.contains('open');

				CMS.bootstrap.dropdown.clear();

				if (!active) {
					// On touch screens a layer under the menu takes the tap that closes it (as Bootstrap 2.3)
					if ('ontouchstart' in document.documentElement) {
						const backdrop = document.createElement('div');

						backdrop.className = 'dropdown-backdrop';
						backdrop.addEventListener('click', () => CMS.bootstrap.dropdown.clear());
						toggle.parentNode.insertBefore(backdrop, toggle);
					}

					parent.classList.add('open');
				}

				toggle.focus();
			},

			keydown(event, element) {
				if ([27, 38, 40].indexOf(event.keyCode) < 0) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				if (bsDisabled(element)) {
					return;
				}

				const parent = CMS.bootstrap.dropdown.parent(element);
				const active = parent.classList.contains('open');

				if (!active || event.keyCode === 27) {
					const toggle = element.matches(CMS.bootstrap.dropdown.toggles) ? element : parent.querySelector(CMS.bootstrap.dropdown.toggles);

					if (event.keyCode === 27 && toggle) {
						toggle.focus();
					}

					if (toggle && (!active || event.keyCode === 27)) {
						CMS.bootstrap.dropdown.toggle(toggle);
					}

					return;
				}

				const items = Array.prototype.filter.call(parent.querySelectorAll('[role="menu"] li:not(.divider) a'), bsVisible);

				if (!items.length) {
					return;
				}

				let index = items.indexOf(document.activeElement);

				if (event.keyCode === 38 && index > 0) {
					index -= 1;
				}

				if (event.keyCode === 40 && index < items.length - 1) {
					index += 1;
				}

				items[Math.max(0, index)].focus();
			},
		},

		/* Collapse: .in shown, its height animated (.collapse's transition); data-parent keeps one open in an accordion */
		collapse: {
			states: new WeakMap(),

			state(element, options) {
				if (!CMS.bootstrap.collapse.states.has(element)) {
					CMS.bootstrap.collapse.states.set(element, {
						parent: element.getAttribute('data-parent') || null,
						transitioning: false,
					});
				}

				const state = CMS.bootstrap.collapse.states.get(element);

				if (options && typeof options === 'object' && options.parent) {
					state.parent = options.parent;
				}

				return state;
			},

			dimension: (element) => (element.classList.contains('width') ? 'width' : 'height'),

			// The toggles of an element (a[href="#id"], [data-target="#id"]): .collapsed while it's hidden
			mark(element, collapsed) {
				if (!element.id) {
					return;
				}

				const id = CSS.escape(element.id);

				Array.prototype.forEach.call(
					document.querySelectorAll('[data-toggle="collapse"][href$="#' + id + '"], [data-toggle="collapse"][data-target="#' + id + '"]'),
					(toggle) => {
						toggle.classList.toggle('collapsed', collapsed);
						toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

						// Expand buttons with labels for both states (the categories' subcategories): the label and a plus or minus icon
						if (toggle.hasAttribute('data-expand-label')) {
							const icon = toggle.querySelector('.icon-plus, .icon-minus');

							toggle.setAttribute('aria-label', toggle.getAttribute(collapsed ? 'data-expand-label' : 'data-collapse-label') || '');

							if (icon) {
								icon.classList.toggle('icon-plus', collapsed);
								icon.classList.toggle('icon-minus', !collapsed);
							}
						}
					}
				);
			},

			show(element) {
				const state = CMS.bootstrap.collapse.state(element);

				if (state.transitioning || element.classList.contains('in')) {
					return;
				}

				const parent = state.parent ? document.querySelector(state.parent) : null;

				if (parent) {
					const actives = parent.querySelectorAll(':scope > .accordion-group > .in');

					for (const active of actives) {
						if (CMS.bootstrap.collapse.state(active).transitioning) {
							return;
						}
					}

					Array.prototype.forEach.call(actives, (active) => CMS.bootstrap.collapse.hide(active));
				}

				const dimension = CMS.bootstrap.collapse.dimension(element);

				element.style[dimension] = '0px';

				if (!bsEmit(element, 'collapse', 'show', null, true)) {
					return;
				}

				state.transitioning = true;
				element.classList.add('in');
				CMS.bootstrap.collapse.mark(element, false);
				element.style[dimension] = element[dimension === 'width' ? 'scrollWidth' : 'scrollHeight'] + 'px';

				afterTransition(element, () => {
					element.style[dimension] = '';
					state.transitioning = false;
					bsEmit(element, 'collapse', 'shown');
				});
			},

			hide(element) {
				const state = CMS.bootstrap.collapse.state(element);

				if (state.transitioning || !element.classList.contains('in')) {
					return;
				}

				const dimension = CMS.bootstrap.collapse.dimension(element);

				// From its own size, not "auto" (which doesn't animate)
				element.classList.remove('collapse');
				element.style[dimension] = element.getBoundingClientRect()[dimension] + 'px';
				element.offsetWidth; // eslint-disable-line no-unused-expressions
				element.classList.add('collapse');

				if (!bsEmit(element, 'collapse', 'hideme', null, true)) {
					return;
				}

				state.transitioning = true;
				element.classList.remove('in');
				CMS.bootstrap.collapse.mark(element, true);
				element.style[dimension] = '0px';

				afterTransition(element, () => {
					state.transitioning = false;
					bsEmit(element, 'collapse', 'hidden');
				});
			},

			toggle(element) {
				CMS.bootstrap.collapse[element.classList.contains('in') ? 'hide' : 'show'](element);
			},
		},

		/* Tabs: a[data-toggle="tab|pill"] makes its li and the pane its href or data-target names .active */
		tab: {
			toggles: '[data-toggle="tab"], [data-toggle="pill"]',

			show(link) {
				const list = link.closest('ul:not(.dropdown-menu)');
				const item = link.closest('li');

				if (!item || item.classList.contains('active')) {
					return;
				}

				const actives = list ? list.querySelectorAll('.active') : [];
				const previous = actives.length ? actives[actives.length - 1].querySelector('a') : null;

				if (!bsEmit(link, 'tab', 'show', previous, true)) {
					return;
				}

				const pane = bsTarget(link);

				CMS.bootstrap.tab.activate(item, list);

				if (pane) {
					CMS.bootstrap.tab.activate(pane, pane.parentNode, () => bsEmit(link, 'tab', 'shown', previous));
				} else {
					bsEmit(link, 'tab', 'shown', previous);
				}
			},

			activate(element, container, callback) {
				const active = container ? container.querySelector(':scope > .active') : null;
				const transition = Boolean(callback && active && active.classList.contains('fade'));

				const next = () => {
					if (active) {
						active.classList.remove('active');
						Array.prototype.forEach.call(active.querySelectorAll(':scope > .dropdown-menu > .active'), (item) => item.classList.remove('active'));
					}

					element.classList.add('active');

					if (transition) {
						element.offsetWidth; // eslint-disable-line no-unused-expressions
						element.classList.add('in');
					} else {
						element.classList.remove('fade');
					}

					if (element.parentNode && element.parentNode.classList && element.parentNode.classList.contains('dropdown-menu')) {
						const dropdown = element.closest('li.dropdown');

						if (dropdown) {
							dropdown.classList.add('active');
						}
					}

					if (callback) {
						callback();
					}
				};

				if (transition) {
					afterTransition(active, next);
					active.classList.remove('in');
				} else {
					next();
				}
			},
		},

		/* Modals: div.modal shown over a .modal-backdrop; Escape and the backdrop close it (data-keyboard, data-backdrop) */
		modal: {
			states: new WeakMap(),

			state(element, options) {
				if (!CMS.bootstrap.modal.states.has(element)) {
					const backdrop = element.getAttribute('data-backdrop');

					CMS.bootstrap.modal.states.set(element, {
						shown: false,
						backdrop: backdrop === 'static' ? 'static' : backdrop !== 'false',
						keyboard: element.getAttribute('data-keyboard') !== 'false',
						element: null,
					});
				}

				const state = CMS.bootstrap.modal.states.get(element);

				if (options && typeof options === 'object') {
					['backdrop', 'keyboard'].forEach((name) => {
						if (typeof options[name] !== 'undefined') {
							state[name] = options[name];
						}
					});

					if (options.remote && !state.remote) {
						state.remote = options.remote;
						CMS.bootstrap.modal.load(element, options.remote);
					}
				}

				return state;
			},

			// A remote page into .modal-body (data-toggle="modal" on a link to a page, as Bootstrap 2 loaded it)
			load(element, url) {
				const body = element.querySelector('.modal-body');

				if (body) {
					fetch(url, { credentials: 'same-origin' }).then((response) => response.text()).then((html) => {
						body.innerHTML = html;
					}).catch(() => {});
				}
			},

			toggle(element, options) {
				CMS.bootstrap.modal[CMS.bootstrap.modal.state(element, options).shown ? 'hide' : 'show'](element, options);
			},

			show(element, options) {
				const state = CMS.bootstrap.modal.state(element, options);

				if (state.shown || !bsEmit(element, 'modal', 'show', null, true)) {
					return;
				}

				state.shown = true;
				document.body.classList.add('modal-open');
				CMS.bootstrap.modal.prepare(element);

				state.onKey = (event) => {
					if (event.keyCode === 27 && state.keyboard) {
						CMS.bootstrap.modal.hide(element);
					}
				};
				state.onFocus = (event) => {
					if (event.target !== element && !element.contains(event.target)) {
						element.focus();
					}
				};
				element.addEventListener('keyup', state.onKey);

				CMS.bootstrap.modal.backdrop(element, state, () => {
					const transition = element.classList.contains('fade');

					if (!element.parentNode) {
						document.body.appendChild(element);
					}

					element.style.display = 'block';

					if (transition) {
						element.offsetWidth; // eslint-disable-line no-unused-expressions
					}

					element.classList.add('in');
					element.setAttribute('aria-hidden', 'false');
					document.addEventListener('focusin', state.onFocus);

					const done = () => {
						element.focus();
						CMS.bootstrap.modal.fit(element);
						bsEmit(element, 'modal', 'shown');
					};

					if (transition) {
						afterTransition(element, done);
					} else {
						done();
					}
				});
			},

			hide(element) {
				const state = CMS.bootstrap.modal.state(element);

				if (!state.shown || !bsEmit(element, 'modal', 'hide', null, true)) {
					return;
				}

				state.shown = false;
				element.removeEventListener('keyup', state.onKey);
				document.removeEventListener('focusin', state.onFocus);
				element.classList.remove('in');
				element.setAttribute('aria-hidden', 'true');

				if (CMS.tip && CMS.tip.hideAll) {
					CMS.tip.hideAll();
				}

				const done = () => {
					element.style.display = 'none';
					CMS.bootstrap.modal.backdrop(element, state, () => {
						if (!document.querySelector('.modal.in')) {
							document.body.classList.remove('modal-open');
						}

						Array.prototype.forEach.call(element.querySelectorAll('.modal-body'), (body) => {
							body.style.maxHeight = '';
							body.style.overflowY = '';
						});
						bsEmit(element, 'modal', 'hidden');
					});
				};

				if (element.classList.contains('fade')) {
					afterTransition(element, done);
				} else {
					done();
				}
			},

			// The backdrop shown with the modal (a click on it closes the modal, unless data-backdrop="static"), and removed
			backdrop(element, state, callback) {
				const animate = element.classList.contains('fade');

				if (state.shown && state.backdrop) {
					const backdrop = document.createElement('div');

					backdrop.className = 'modal-backdrop' + (animate ? ' fade' : '');
					backdrop.addEventListener('click', () => {
						if (state.backdrop === 'static') {
							element.focus();
						} else {
							CMS.bootstrap.modal.hide(element);
						}
					});
					document.body.appendChild(backdrop);
					state.element = backdrop;
					backdrop.offsetWidth; // eslint-disable-line no-unused-expressions
					backdrop.classList.add('in');

					if (animate) {
						afterTransition(backdrop, callback);
					} else {
						callback();
					}
				} else if (!state.shown && state.element) {
					const backdrop = state.element;

					state.element = null;
					backdrop.classList.remove('in');

					const remove = () => {
						backdrop.remove();
						callback();
					};

					if (animate) {
						afterTransition(backdrop, remove);
					} else {
						remove();
					}
				} else {
					callback();
				}
			},

			// Joomla's modals (layout joomla.modal.main): the page of their url in a new iframe each time they open
			prepare(element) {
				const template = element.querySelector(':scope > .modal-body > template.modal-iframe');

				if (template) {
					const body = template.parentNode;

					Array.prototype.forEach.call(body.querySelectorAll(':scope > iframe'), (frame) => frame.remove());
					body.insertBefore(template.content.cloneNode(true), body.firstChild);
				}
			},

			// Joomla's modals: a body which would leave the window scrolls instead
			fit(element) {
				if (!element.hasAttribute('data-cms-modal')) {
					return;
				}

				const body = element.querySelector(':scope > .modal-body');

				if (!body) {
					return;
				}

				const header = element.querySelector(':scope > .modal-header');
				const footer = element.querySelector(':scope > .modal-footer');
				const outer = (part) => (part ? part.getBoundingClientRect().height : 0);
				const bodyStyle = window.getComputedStyle(body);
				const padding = (parseFloat(bodyStyle.paddingTop) || 0) + (parseFloat(bodyStyle.paddingBottom) || 0);
				const top = element.offsetTop;
				const maxBody = window.innerHeight - top * 2 - outer(header) - outer(footer) - padding;
				const frame = body.querySelector('.iframe');

				if (frame ? frame.getBoundingClientRect().height > maxBody : element.getBoundingClientRect().height > window.innerHeight - top * 2) {
					body.style.maxHeight = maxBody + 'px';
					body.style.overflowY = 'auto';

					if (frame) {
						frame.style.maxHeight = (maxBody - padding) + 'px';
					}
				}
			},
		},

		/* Alerts: [data-dismiss="alert"] removes its .alert (faded out with .fade) */
		alert: {
			close(element) {
				const box = element.classList.contains('alert') ? element : bsTarget(element) || element.closest('.alert');

				if (!box || !bsEmit(box, 'alert', 'close', null, true)) {
					return;
				}

				box.classList.remove('in');

				const remove = () => {
					bsEmit(box, 'alert', 'closed');
					box.remove();
				};

				if (box.classList.contains('fade')) {
					afterTransition(box, remove);
				} else {
					remove();
				}
			},
		},
	};

	// The data attributes, delegated, unless Bootstrap's own script is on the page
	document.addEventListener('click', (event) => {
		const target = event.target;

		if (!target || !target.closest) {
			return;
		}

		// A button of a modal clicking one in the modal's frame (data-frame-click="#saveBtn"), before it closes the modal
		const frameButton = target.closest('[data-frame-click]');

		if (frameButton) {
			const holder = frameButton.closest('.modal');
			const frame = holder && holder.querySelector('iframe');
			let inner = null;

			try {
				inner = frame && frame.contentDocument ? frame.contentDocument.querySelector(frameButton.getAttribute('data-frame-click')) : null;
			} catch (error) {
				inner = null;
			}

			if (inner) {
				inner.click();
			}
		}

		if (!bootstrapLoaded('dropdown')) {
			const toggle = target.closest(CMS.bootstrap.dropdown.toggles);

			if (toggle) {
				event.preventDefault();
				CMS.bootstrap.dropdown.toggle(toggle);
			} else if (!target.closest('.dropdown form')) {
				CMS.bootstrap.dropdown.clear();
			}
		}

		const collapseToggle = !bootstrapLoaded('collapse') && target.closest('[data-toggle="collapse"]');

		if (collapseToggle) {
			const element = bsTarget(collapseToggle);

			event.preventDefault();

			if (element) {
				const parent = collapseToggle.getAttribute('data-parent');

				CMS.bootstrap.collapse.state(element, { parent });

				// The other toggles of an accordion are marked closed, as Joomla's accordion script did
				if (parent && !element.classList.contains('in')) {
					Array.prototype.forEach.call(
						document.querySelectorAll('[data-toggle="collapse"][data-parent="' + parent + '"]'),
						(other) => other !== collapseToggle && other.classList.add('collapsed')
					);
				}

				CMS.bootstrap.collapse.toggle(element);
			}
		}

		const tabLink = !bootstrapLoaded('tab') && target.closest(CMS.bootstrap.tab.toggles);

		if (tabLink) {
			event.preventDefault();
			CMS.bootstrap.tab.show(tabLink);
		}

		if (!bootstrapLoaded('modal')) {
			const opener = target.closest('[data-toggle="modal"]');

			if (opener) {
				const element = bsTarget(opener);
				const href = opener.getAttribute('href');

				event.preventDefault();

				if (element) {
					const restore = () => {
						element.removeEventListener('cms:modal-hidden', restore);
						opener.focus();
					};

					element.addEventListener('cms:modal-hidden', restore);
					CMS.bootstrap.modal.toggle(element, {
						remote: href && !/^#/.test(href) && !/#[^\s]+$/.test(href) ? href : false,
						backdrop: opener.hasAttribute('data-backdrop') ? (opener.getAttribute('data-backdrop') === 'static' ? 'static' : opener.getAttribute('data-backdrop') !== 'false') : undefined,
						keyboard: opener.hasAttribute('data-keyboard') ? opener.getAttribute('data-keyboard') !== 'false' : undefined,
					});
				}
			} else {
				const dismiss = target.closest('[data-dismiss="modal"]');
				const element = dismiss && dismiss.closest('.modal');

				if (element) {
					event.preventDefault();
					CMS.bootstrap.modal.hide(element);
				}
			}
		}

		if (!bootstrapLoaded('alert')) {
			const dismiss = target.closest('[data-dismiss="alert"]');

			if (dismiss) {
				event.preventDefault();
				CMS.bootstrap.alert.close(dismiss);
			}
		}
	});

	document.addEventListener('keydown', (event) => {
		const element = !bootstrapLoaded('dropdown') && event.target && event.target.closest
			&& event.target.closest(CMS.bootstrap.dropdown.toggles + ', [role="menu"]');

		if (element) {
			CMS.bootstrap.dropdown.keydown(event, element);
		}
	});

	// What jQuery's plugin names do with an element and an option (Bootstrap 2's calls: .modal('show'), .collapse({...}), ...)
	const bootstrapRunners = {
		dropdown(element, option) {
			if (option === 'toggle') {
				CMS.bootstrap.dropdown.toggle(element);
			}
		},

		collapse(element, option) {
			const known = CMS.bootstrap.collapse.states.has(element);
			const options = option && typeof option === 'object' ? option : {};

			CMS.bootstrap.collapse.state(element, options);

			if (typeof option === 'string') {
				if (['show', 'hide', 'toggle'].indexOf(option) >= 0) {
					CMS.bootstrap.collapse[option](element);
				}
			} else if (!known && options.toggle !== false) {
				CMS.bootstrap.collapse.toggle(element);
			}
		},

		tab(element, option) {
			if (option === 'show') {
				CMS.bootstrap.tab.show(element);
			}
		},

		modal(element, option) {
			if (typeof option === 'string') {
				if (['show', 'hide', 'toggle'].indexOf(option) >= 0) {
					CMS.bootstrap.modal[option](element);
				}
			} else if (!option || option.show !== false) {
				CMS.bootstrap.modal.show(element, option);
			} else {
				CMS.bootstrap.modal.state(element, option);
			}
		},

		alert(element, option) {
			if (option === 'close') {
				CMS.bootstrap.alert.close(element);
			}
		},

		tooltip(element, option) {
			CMS.tip.attach(element, 'tooltip', option);
		},

		popover(element, option) {
			CMS.tip.attach(element, 'popover', option);
		},
	};

	/**
	 * A plugin's call on elements (a selector, an element or a list), by Bootstrap's script when it's on the page,
	 * otherwise by these: what JHtml's Bootstrap helpers start pages with
	 *
	 * @param  {string}                 name      dropdown, collapse, tab, modal, alert, tooltip or popover
	 * @param  {string|Element|Array}   elements
	 * @param  {string|Object}          option    A method's name, or options
	 */
	CMS.bootstrap.call = (name, elements, option) => {
		const list = typeof elements === 'string' ? document.querySelectorAll(elements) : (elements && elements.length !== undefined ? elements : [elements]);

		if (bootstrapLoaded(name)) {
			window.jQuery(list)[name](option);
		} else if (bootstrapRunners[name]) {
			Array.prototype.forEach.call(list, (element) => element && bootstrapRunners[name](element, option));
		}
	};

	/*
	 * Tab sets of JHtml bootstrap.startTabSet: their list (ul[data-tabs-for]) filled from the panes' titles (data-tab-title),
	 * as the tab helpers' inline scripts did, before other scripts look for the tabs (in jQuery's ready queue when it's on
	 * the page)
	 */
	CMS.bootstrap.buildTabs = (root) => {
		Array.prototype.forEach.call((root || document).querySelectorAll('ul.nav-tabs[data-tabs-for]'), (list) => {
			const content = document.getElementById(list.getAttribute('data-tabs-for'));

			if (!content || list.children.length) {
				return;
			}

			Array.prototype.forEach.call(content.children, (pane) => {
				if (!pane.classList.contains('tab-pane') || !pane.hasAttribute('data-tab-title')) {
					return;
				}

				const item = document.createElement('li');
				const link = document.createElement('a');

				item.className = pane.classList.contains('active') ? 'active' : '';
				link.href = '#' + pane.id;
				link.setAttribute('data-toggle', 'tab');
				link.innerHTML = pane.getAttribute('data-tab-title');
				item.appendChild(link);
				list.appendChild(item);
			});
		});
	};

	if (window.jQuery) {
		window.jQuery(() => CMS.bootstrap.buildTabs());
	} else {
		CMS.ready(() => CMS.bootstrap.buildTabs());
	}

	// Joomla's modals opened by Bootstrap's own script (an extension loaded it): their iframe, body.modal-open and fitting
	CMS.ready(() => {
		const jq = window.jQuery;

		if (!jq) {
			return;
		}

		const own = (event) => bootstrapLoaded('modal') && event.target.hasAttribute && event.target.hasAttribute('data-cms-modal');

		// Collapses opened and closed by Bootstrap's own script: their toggles' state, as ours mark it
		jq(document).on('show hideme', '.collapse', (event) => {
			if (bootstrapLoaded('collapse') && event.target.classList && event.target.classList.contains('collapse')) {
				CMS.bootstrap.collapse.mark(event.target, event.type === 'hideme');
			}
		});

		jq(document).on('show', '[data-cms-modal]', (event) => {
			if (own(event)) {
				document.body.classList.add('modal-open');
				CMS.bootstrap.modal.prepare(event.target);
			}
		}).on('shown', '[data-cms-modal]', (event) => {
			if (own(event)) {
				CMS.bootstrap.modal.fit(event.target);
			}
		}).on('hide', '[data-cms-modal]', (event) => {
			if (own(event)) {
				document.body.classList.remove('modal-open');
				Array.prototype.forEach.call(event.target.querySelectorAll('.modal-body'), (body) => {
					body.style.maxHeight = '';
					body.style.overflowY = '';
				});

				if (CMS.tip && CMS.tip.hideAll) {
					CMS.tip.hideAll();
				}
			}
		});
	});

	// jQuery's names for them, for extensions' scripts, where Bootstrap's script isn't on the page
	const bootstrapJquery = () => {
		const jq = window.jQuery;

		if (!jq || !jq.fn) {
			return false;
		}

		Object.keys(bootstrapRunners).forEach((name) => {
			if (!jq.fn[name]) {
				jq.fn[name] = function plugin(option) {
					return this.each((index, element) => bootstrapRunners[name](element, option));
				};
				jq.fn[name].cms = true;
			}
		});

		return true;
	};

	if (!bootstrapJquery()) {
		document.addEventListener('readystatechange', bootstrapJquery);
	}

	/* --- The open tab of Bootstrap tab sets, kept while browsing (behavior.tabstate) --- */

	/*
	 * Bootstrap's tabs (a[data-toggle="tab"]): each set's open tab is kept in sessionStorage for the page's address, as
	 * tabs-state.js did, and opened again when the page comes back (after saving, or from a list). A #hash naming a tab
	 * opens that tab instead (and the tab of the pane holding it). Tabs are opened by clicking them: the click reaches
	 * Bootstrap's own handler, so this needs no jQuery itself.
	 */
	const tabState = {
		started: false,

		// The address without its return parameter and hash
		key: () => (location.pathname + location.search).replace(/&return=[a-zA-Z0-9%]+/, ''),

		read(key) {
			try {
				return JSON.parse(sessionStorage.getItem(key)) || null;
			} catch (error) {
				return null;
			}
		},

		write(key, value) {
			try {
				if (value === null) {
					sessionStorage.removeItem(key);
				} else {
					sessionStorage.setItem(key, JSON.stringify(value));
				}
			} catch (error) {
				// Private windows may refuse storage: tabs just aren't remembered
			}
		},

		// Where a tab set is, the way tabs-state.js wrote it (stored values stay readable)
		path(element) {
			if (!element || element.nodeType !== 1) {
				return '';
			}

			if (element.id) {
				return "//*[@id='" + element.id + "']";
			}

			const same = Array.prototype.filter.call(element.parentNode.children, (child) => child.tagName === element.tagName);

			return tabState.path(element.parentNode) + '/' + element.tagName.toLowerCase()
				+ (same.length > 1 ? '[' + (same.indexOf(element) + 1) + ']' : '');
		},

		find(path) {
			try {
				return document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
			} catch (error) {
				return null;
			}
		},

		links: (container) => Array.prototype.slice.call((container || document).querySelectorAll('a[data-toggle="tab"]')),

		save(link) {
			const set = tabState.path(link.closest('.nav-tabs'));
			const href = link.getAttribute('href');

			if (!set || !href) {
				return;
			}

			const key = tabState.key();
			const saved = (tabState.read(key) || []).filter((entry) => entry.split('|')[0] !== set);

			saved.push(set + '|' + href);
			tabState.write(key, saved);
		},

		open(link) {
			if (link) {
				link.click();
			}
		},

		restore() {
			const links = tabState.links();
			const sets = links.map((link) => link.closest('ul')).filter((set, index, all) => set && all.indexOf(set) === index);

			links.forEach((link) => link.addEventListener('click', () => tabState.save(link)));

			links.forEach((link) => {
				if (link.parentNode.classList.contains('active')) {
					link.parentNode.classList.remove('active');
				}
			});

			// A new item's page was saved under the address without its id: the saved item's page takes it over
			let saved = tabState.read(tabState.key());

			if (!saved) {
				const unsaved = tabState.key().replace(/&id=[0-9]*|[a-z]&_id=[0-9]*/, '');

				saved = tabState.read(unsaved);
				tabState.write(unsaved, null);
			}

			if (location.hash) {
				const hash = location.hash;

				links.filter((link) => link.getAttribute('href') === hash).forEach((link) => {
					const pane = link.closest('.tab-pane');

					// A tab inside another tab's pane: that tab first
					if (pane && pane.id) {
						tabState.open(links.find((outer) => outer.getAttribute('href') === '#' + pane.id));
					}

					tabState.open(link);
				});

				history.pushState('', document.title, location.pathname + location.search);
			} else if (saved) {
				saved.forEach((entry) => {
					const parts = String(entry).split('|');
					const set = tabState.find(parts[0]);

					if (set) {
						tabState.open(tabState.links(set).find((link) => link.getAttribute('href') === parts[1]));
					}
				});
			}

			// Every set ends with an open tab: its first when nothing was kept for it
			sets.forEach((set) => {
				const own = tabState.links(set).filter((link) => link.closest('ul') === set);

				if (own.length && !own.some((link) => link.parentNode.classList.contains('active'))) {
					tabState.open(own[0]);
				}
			});
		},
	};

	CMS.tabState = {
		/**
		 * Remembers and restores the open tab of every Bootstrap tab set on the page (once per page)
		 */
		init() {
			if (tabState.started) {
				return;
			}

			tabState.started = true;

			// The tab helpers' lists first: tabs are opened by clicking them
			CMS.bootstrap.buildTabs();

			// What tabs-state.js added to jQuery
			const $ = window.jQuery;

			if ($) {
				$.urlParam = $.urlParam || ((name) => {
					const match = new RegExp('[\\?&](' + (/=$/.test(name) ? name : name + '=') + ')([^&#]*)').exec(location.href);

					return match ? match[1] : null;
				});
				$.getXpath = $.getXpath || ((element) => (typeof element === 'string' ? document.evaluate(element, document, null, 0, null) : tabState.path(element)));
				$.findXpath = $.findXpath || ((path, context) => {
					const found = [];
					const result = document.evaluate(path, context || document, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
					let node;

					while ((node = result.iterateNext())) {
						found.push(node);
					}

					return $(found);
				});
			}

			// After the page's own scripts have set their tabs up, as tabs-state.js waited
			setTimeout(tabState.restore, 100);
		},
	};

	/* --- Behaviours started from the page's script options --- */

	CMS.ready(() => {
		Object.keys(CMS.options.get('system.multiselect') || {}).forEach((id) => CMS.multiselect.attach(id));

		if (CMS.options.get('system.tabstate')) {
			CMS.tabState.init();
		}

		if (CMS.options.get('system.switcher')) {
			const pages = document.getElementById('config-document');

			if (pages) {
				document.switcher = CMS.switcher.create(document.getElementById('submenu'), pages);
			}
		}

		if (CMS.options.get('system.combobox')) {
			Array.prototype.forEach.call(document.querySelectorAll('div.combobox'), (box) => CMS.combobox.attach(box, { updateList: true }));
		}

		// What combobox.js added to jQuery
		if (window.jQuery && !window.jQuery.fn.ComboTransform) {
			window.jQuery.fn.ComboTransform = function ComboTransform(options) {
				return this.each(function attach() {
					CMS.combobox.attach(this, Object.assign({}, window.jQuery.fn.ComboTransform.options, options));
				});
			};
			window.jQuery.fn.ComboTransform.options = { updateList: true };
		}

		// What MiniColors and Simple Colors added to jQuery (extensions' own colour fields): the browser's picker instead
		if (window.jQuery && !window.jQuery.fn.minicolors) {
			window.jQuery.fn.minicolors = function minicolors(method, value) {
				if (method === 'value') {
					if (value === undefined) {
						return this.length ? this[0].value : undefined;
					}

					return this.each(function set() {
						this.value = value && value.color !== undefined ? value.color : value;
						CMS.colorField.update(this.closest('.cms-color') || document.createElement('span'));
					});
				}

				return typeof method === 'string' ? this : this.each(function attach() {
					CMS.colorField.attach(this);
				});
			};
		}

		// What fielduser.js added to jQuery: the fields need no start any more
		if (window.jQuery && !window.jQuery.fn.fieldUser) {
			window.jQuery.fn.fieldUser = function fieldUser() {
				return this;
			};
		}

		if (window.jQuery && !window.jQuery.fn.simplecolors) {
			window.jQuery.fn.simplecolors = function simplecolors() {
				return this;
			};
		}

		if (CMS.options.get('system.colorpicker')) {
			document.querySelectorAll(CMS.options.get('system.colorpicker')).forEach(CMS.colorField.attach);
		}

		if (CMS.options.get('system.associations.edit')) {
			CMS.associations.init(CMS.options.get('system.associations.edit'));
		}

		Object.keys(started ? {} : CMS.options.get('system.highlighter') || {}).forEach((sig) => {
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
		Object.keys(started ? {} : CMS.options.get('system.caption') || {}).forEach((selector) => CMS.caption.attach(selector));
	});

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
		const options = started ? null : CMS.options.get('system.keepalive');

		if (options) {
			CMS.keepalive.start(options);
		}
	});

	/* --- The editor switcher (layout joomla.editors.switcher, administrator) --- */

	/*
	 * The chosen editor is posted as editor_switch and kept for the session. With the form's reload task
	 * (data-editor-reload="item.reload" on the form; core components' edit forms find theirs from their Save button), the form
	 * is posted to it, so what wasn't saved comes back in the other editor. Otherwise, once confirmed, the switch is posted on
	 * its own and the page loads again.
	 */
	const SAVE_TASK = /submitbutton\(\s*['"]([A-Za-z0-9_]+)\.(?:apply|save)['"]/;

	const hiddenInput = (form, name, value) => {
		let input = form.querySelector('input[type="hidden"][name="' + name + '"]');

		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			form.appendChild(input);
		}

		input.value = value;
	};

	CMS.editorSwitch = {
		// The form's reload task, '' when it has none
		reloadTask(switcher, form) {
			if (!form || !form.elements.task) {
				return '';
			}

			if (form.hasAttribute('data-editor-reload')) {
				return form.getAttribute('data-editor-reload');
			}

			if (switcher.getAttribute('data-core') !== '1') {
				return '';
			}

			for (const element of document.querySelectorAll('[onclick*="submitbutton"]')) {
				const match = SAVE_TASK.exec(element.getAttribute('onclick'));

				if (match) {
					return match[1] + '.reload';
				}
			}

			return '';
		},

		// False when the user cancelled
		to(switcher, editor) {
			const form = switcher.closest('form');
			const task = CMS.editorSwitch.reloadTask(switcher, form);

			if (task) {
				hiddenInput(form, 'editor_switch', editor);
				Joomla.submitform(task, form);

				return true;
			}

			if (!window.confirm(switcher.getAttribute('data-confirm'))) {
				return false;
			}

			const post = document.createElement('form');

			post.method = 'post';
			post.action = window.location.href;
			post.hidden = true;
			hiddenInput(post, 'editor_switch', editor);
			hiddenInput(post, 'editor_switch_return', '1');
			hiddenInput(post, switcher.getAttribute('data-token'), '1');
			document.body.appendChild(post);
			post.submit();

			return true;
		},
	};

	const closeSwitchers = (except) => {
		for (const details of document.querySelectorAll('.editor-switch details[open]')) {
			if (details !== except) {
				details.open = false;
			}
		}
	};

	document.addEventListener('click', (event) => {
		if (!event.target.closest) {
			return;
		}

		const button = event.target.closest('[data-editor-switch-to]');

		closeSwitchers(event.target.closest('.editor-switch details'));

		if (!button || button.disabled) {
			return;
		}

		const switcher = button.closest('[data-editor-switch]');

		if (switcher.classList.contains('is-switching')) {
			return;
		}

		button.closest('details').open = false;
		switcher.classList.add('is-switching');

		// Still on the page (cancelled, or the form refused to submit): the switcher may be used again
		if (!CMS.editorSwitch.to(switcher, button.getAttribute('data-editor-switch-to'))) {
			switcher.classList.remove('is-switching');
		} else {
			window.setTimeout(() => switcher.classList.remove('is-switching'), 3000);
		}
	});

	document.addEventListener('keydown', (event) => {
		const details = event.key === 'Escape' && event.target.closest ? event.target.closest('.editor-switch details[open]') : null;

		if (details) {
			details.open = false;
			details.querySelector('summary').focus();
		}
	});

	/* --- The old names --- */

	/*
	 * LEGACY: every name core.js and the MooTools behaviours offered, mapped to the new code. New code should call the
	 * CMS names; these stay for extensions. The Joomla.* functions are properties pages may replace (Joomla.submitbutton
	 * is meant to be), so the new code calls them through Joomla where core.js did.
	 */
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

	// The same objects: strings and options loaded either way are there for both
	Joomla.Text = Joomla.JText = CMS.text;
	Object.defineProperty(Joomla, 'optionsStorage', {
		get: () => CMS.options.storage,
		set: (value) => {
			CMS.options.storage = value;
		},
		configurable: true,
	});

	window.jModalClose = () => CMS.modal.close();

	// TinyMCE 4's tiny-close.js would wrap these again: CMS.modal.close() closes its dialogs itself
	window.jModalClose_no_tinyMCE = window.SqueezeBoxClose_no_tinyMCE = null;

	if (!window.SqueezeBox) {
		window.SqueezeBox = SqueezeBoxShim;
	}

	if (!window.JTabs) {
		window.JTabs = function JTabs(dlist, options) {
			return CMS.tabs.create(dlist, options);
		};
	}

	if (!window.MooTreeControl) {
		window.MooTreeControl = Tree;
		window.MooTreeNode = TreeNode;
		window.MooTreeIcon = TREE_ICONS;
	}

	if (!window.JSwitcher) {
		window.JSwitcher = function JSwitcher(toggler, element, options) {
			return CMS.switcher.create(toggler, element, options);
		};
	}

	if (!Joomla.JMultiSelect) {
		Joomla.JMultiSelect = (container) => CMS.multiselect.attach(container);
	}

	Joomla.injectAssociations = CMS.associations.inject;
	Joomla.propagateAssociation = CMS.associations.propagate;
	window.hideAssociation = CMS.associations.hide;
	window.showAssociationMessage = CMS.associations.showMessage;

	// The Permissions field's lists call it: onchange="sendPermissions.call(this, event)"
	window.sendPermissions = function sendPermissions() {
		CMS.permissions.send(this);
	};
	window.getUrlParam = CMS.permissions.urlParam;

	if (!Joomla.Highlighter) {
		Joomla.Highlighter = (options) => CMS.highlighter.create(options);
	}

	if (!window.JCaption) {
		window.JCaption = function JCaption(selector) {
			CMS.caption.attach(selector);
		};
	}

	// The media field's names (the administrator templates' mediafield.js declares its own after this file)
	window.jInsertFieldValue = window.jInsertFieldValue || CMS.mediaField.insert;
	window.jMediaRefreshPreview = window.jMediaRefreshPreview || CMS.mediaField.refreshPreview;
	window.jMediaRefreshPreviewTip = window.jMediaRefreshPreviewTip || CMS.mediaField.previewTip;
	window.jMediaRefreshImgpathTip = window.jMediaRefreshImgpathTip || CMS.mediaField.imagePathTip;

	window.Form = window.Form || {};

	if (!window.Form.PasswordStrength) {
		window.Form.PasswordStrength = function PasswordStrength(input, options) {
			return CMS.password.meter(input, options);
		};
	}

	// Deprecated in Joomla 3 without a replacement: kept as they were
	const deprecated = (name, callback) => {
		window[name] = function legacy() {
			window.console.warn('window.' + name + '() is deprecated without a replacement!');

			return callback.apply(this, arguments);
		};
	};

	Joomla.isEmail = (text) => {
		window.console.warn('Joomla.isEmail() is deprecated, use the formvalidator instead');

		return /^[\w.!#$%&’*+/=?^`{|}~-]+@[a-z0-9-]+(?:\.[a-z0-9-]{2,})+$/i.test(text);
	};

	deprecated('writeDynaList', (selectParams, source, key, origKey, origVal, element) => {
		const select = document.createElement('select');

		selectParams.split(' ').forEach((param) => {
			const parts = param.split('=');
			const name = parts[0].trim().toLowerCase();

			// No attribute that could run script
			if (parts.length > 1 && name.substr(0, 2) !== 'on' && name !== 'href') {
				select.setAttribute(parts[0], parts[1].replace(/"/g, ''));
			}
		});

		const hasSelection = key == origKey; // eslint-disable-line eqeqeq

		source.forEach((item, i) => {
			if (item[0] != key) { // eslint-disable-line eqeqeq
				return;
			}

			const option = document.createElement('option');

			option.setAttribute('value', item[1]);
			option.innerText = item[2];

			if (hasSelection ? origVal == item[1] : i === 0) { // eslint-disable-line eqeqeq
				option.setAttribute('selected', 'selected');
			}

			select.appendChild(option);
		});

		(element || document.body).appendChild(select);
	});

	deprecated('changeDynaList', (listname, source, key, origKey, origVal) => {
		const select = document.adminForm[listname];
		const hasSelection = key == origKey; // eslint-disable-line eqeqeq
		let i = 0;

		while (select.firstChild) {
			select.removeChild(select.firstChild);
		}

		Object.keys(source).forEach((index) => {
			const item = source[index];

			if (item[0] != key) { // eslint-disable-line eqeqeq
				return;
			}

			const option = new Option();

			option.value = item[1];
			option.text = item[2];

			if ((hasSelection && origVal == option.value) || (!hasSelection && i === 0)) { // eslint-disable-line eqeqeq
				option.selected = true;
			}

			select.options[i] = option;
			i += 1;
		});

		select.length = i;
	});

	deprecated('radioGetCheckedValue', (radios) => {
		if (!radios) {
			return '';
		}

		if (radios.length === undefined) {
			return radios.checked ? radios.value : '';
		}

		for (const radio of radios) {
			if (radio.checked) {
				return radio.value;
			}
		}

		return '';
	});

	deprecated('getSelectedValue', (formName, listName) => {
		const select = document[formName][listName];

		return select.selectedIndex > -1 ? select.options[select.selectedIndex].value : null;
	});

	deprecated('checkAll_button', (n, task) => {
		for (let j = 0; j <= n; j += 1) {
			const box = document.adminForm['cb' + j];

			if (!box) {
				window.alert('You cannot change the order of items, as an item in the list is `Checked Out`');

				return;
			}

			box.checked = true;
		}

		Joomla.submitform(task || 'saveorder');
	});

	deprecated('saveorder', (n, task) => window.checkAll_button(n, task));

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

	// Old name => new name, for code moving to the new names (also in API-GUIDE.md)
	CMS.legacy = {
		'Joomla.submitform()': 'CMS.form.submit()',
		'Joomla.submitbutton()': 'CMS.form.button()',
		'Joomla.replaceTokens()': 'CMS.form.replaceTokens()',
		'Joomla.getOptions()': 'CMS.options.get()',
		'Joomla.loadOptions()': 'CMS.options.load()',
		'Joomla.optionsStorage': 'CMS.options.storage',
		'Joomla.Text._(), Joomla.JText._()': 'CMS.text.get()',
		'Joomla.Text.load()': 'CMS.text.load()',
		'Joomla.editors.instances': 'CMS.editors.instances',
		'Joomla.checkAll()': 'CMS.list.checkAll()',
		'Joomla.isChecked()': 'CMS.list.isChecked()',
		'Joomla.listItemTask()': 'CMS.list.itemTask()',
		'Joomla.tableOrdering()': 'CMS.list.order()',
		'Joomla.renderMessages()': 'CMS.messages.render()',
		'Joomla.removeMessages()': 'CMS.messages.remove()',
		'Joomla.ajaxErrorsMessages()': 'CMS.messages.fromAjaxError()',
		'Joomla.popupWindow()': 'CMS.window.open()',
		'Joomla.loadingLayer()': 'CMS.loading.layer(), CMS.loading.show(), CMS.loading.hide()',
		'Joomla.extend()': 'CMS.extend()',
		'Joomla.request()': 'CMS.request()',
		'jModalClose(), SqueezeBox.close()': 'CMS.modal.close()',
		'SqueezeBox.open(), SqueezeBox.fromElement()': 'CMS.modal.open()',
		'SqueezeBox.assign()': 'CMS.modal.assign()',
		'SqueezeBox.initialize()': 'CMS.modal.setDefaults()',
		'SqueezeBox.addEvent(), removeEvent()': 'CMS.modal.on(), CMS.modal.off()',
		'new Tips() (behavior.tooltip)': 'CMS.tooltip.attach()',
		'new JTabs() (tabs.start)': 'CMS.tabs.create()',
		'new Fx.Accordion() (sliders.start)': 'CMS.sliders.create()',
		'new Form.PasswordStrength()': 'CMS.password.meter()',
		'new MooTreeControl() (behavior.tree)': 'CMS.tree.create(), new CMS.tree.Tree()',
		'Joomla.JMultiSelect()': 'CMS.multiselect.attach()',
		'validate.js (behavior.formvalidator), document.formvalidator, JFormValidator': 'CMS.formValidator',
		'JSwitcher (behavior.switcher)': 'CMS.switcher.create()',
		'jQuery.fn.ComboTransform() (behavior.combobox)': 'CMS.combobox.attach()',
		'jQuery.fn.minicolors() (behavior.colorpicker, MiniColors)': 'CMS.colorField.attach(), the layout joomla.form.field.color',
		'jQuery.fn.simplecolors() (behavior.simplecolorpicker)': 'the layout joomla.form.field.color.simple (swatches)',
		'jSelectUser(), jQuery.fn.fieldUser() (fielduser.js)': 'CMS.userField.select(), CMS.userField.open()',
		'moduleorder.js': 'CMS.moduleOrder.load(), CMS.moduleOrder.write()',
		'jQuery.fn.autocomplete(), jQuery.fn.devbridgeAutocomplete() (jquery.autocomplete.js; stand-in in jquery.compat.js)': 'CMS.suggest.attach()',
		'jQuery.fn.sortable() (jQuery UI\'s sortable; stand-in in jquery.compat.js)': 'CMS.sortable.attach()',
		'jQuery.fn.searchtools() (jquery.searchtools.js, JHtml searchtools.form)': 'CMS.searchtools.attach()',
		'jQuery.fn.chosen(), jQuery.fn.ajaxChosen() (chosen.jquery.js, ajax-chosen.js; formbehavior.chosen, formbehavior.ajaxchosen)': 'CMS.chosen.attach(), CMS.chosen.update(), CMS.chosen.get()',
		'jQuery.JSortableList() (sortablelist.js, JHtml sortablelist.sortable)': 'CMS.sortableList.attach()',
		'treeselectmenu.jquery.js': 'CMS.treeSelect.start(), toggle(), check(), filter()',
		'jQuery.fn.subformRepeatable() (subform-repeatable.js)': 'CMS.subform.attach(), CMS.subform.get()',
		'jQuery.fn.JRepeatable() (repeatable.js)': 'CMS.repeatable.attach()',
		'processModalParent(), processModalSelect(), processModalEdit() (modal-fields.js)': 'CMS.modalField.set(), CMS.modalField.select(), CMS.modalField.submit()',
		'tabs-state.js (behavior.tabstate), jQuery.urlParam(), jQuery.getXpath(), jQuery.findXpath()': 'CMS.tabState.init()',
		'new JCaption()': 'CMS.caption.attach()',
		'Joomla.Highlighter()': 'CMS.highlighter.create()',
		'jInsertFieldValue(), jMediaRefreshPreview()': 'CMS.mediaField.insert(), CMS.mediaField.refreshPreview()',
		'jMediaRefreshPreviewTip(), jMediaRefreshImgpathTip()': 'CMS.mediaField.previewTip(), CMS.mediaField.imagePathTip()',
		'keepalive.js': 'CMS.keepalive.start(), CMS.keepalive.stop()',
		'Joomla.propagateAssociation(), Joomla.injectAssociations()': 'CMS.associations.propagate(), CMS.associations.inject()',
		'hideAssociation(), showAssociationMessage()': 'CMS.associations.hide(), CMS.associations.showMessage()',
		'sendPermissions(), getUrlParam()': 'CMS.permissions.send(), CMS.permissions.urlParam()',
		'frontediting.js': 'CMS.frontEditing.showModule(), CMS.frontEditing.showMenuItem()',
	};
})(window, document);
