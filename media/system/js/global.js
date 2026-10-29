/**
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The CMS's shared JavaScript, in one file: the namespace window.CMS (what core.js was, plus vanilla versions of what
 * needed MooTools). Older names stay as aliases of the new code (see LEGACY at the end): Joomla.submitform() is
 * CMS.form.submit(), SqueezeBox.open() is CMS.modal.open(), jModalClose() is CMS.modal.close(), and so on.
 * It runs once however often it's loaded. Its styles are in media/system/css/global.css.
 *
 * Parts are ports of MIT-licensed MooTools code: the modal of SqueezeBox 1.3 (Harald Kirschner, Rouven Weßling), the tree of
 * MooTree (Rasmus Schultz, mindplay.dk), the password meter of Form.PasswordStrength (Al Kent).
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

	/* --- Tree (behavior.tree, what MooTree was) --- */

	// The icons' order in the theme's sprite (media/system/images/mootree.gif), 18px each
	const TREE_ICONS = ['I', 'L', 'Lminus', 'Lplus', 'Rminus', 'Rplus', 'T', 'Tminus', 'Tplus', '_closed', '_doc', '_open', 'minus', 'plus'];
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

				style = ' style="background-image:url(' + encodeURI(image) + ');background-position:-' + (index * 18) + 'px 0"';
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
		 * @param  {Object}  config   div (where it goes), mode ('folders' or 'files'), grid, theme (the sprite), loader,
		 *                            onExpand(node, state), onSelect(node, state), onClick(node)
		 * @param  {Object}  options  The root node (text, open, icon, data...)
		 */
		constructor(config, options) {
			options.control = this;
			options.div = config.div;
			this.index = {};
			this.enabled = true;
			this.theme = config.theme || 'mootree.gif';
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
				if (window.jQuery && window.jQuery(previous).data('chosen')) {
					window.jQuery(previous).chosen('destroy');
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

			if (window.jQuery && window.jQuery.fn.chosen) {
				window.jQuery(select).chosen();
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
	 * --- Sorting rows by dragging a handle (subforms and the Repeatable field; what jQuery UI's sortable did for them) ---
	 *
	 * Pointer events, so mouse, pen and touch; a line shows where the row will go and the row moves once, when released
	 * (moving it while dragging reloaded the iframes in it, an editor's, every time it passed a row). A focused handle moves
	 * its row with the arrow keys. jQuery UI's "sortstop" goes out through jQuery when it's there (TinyMCE 4 restarts the
	 * editors of a moved row on it).
	 */
	const sortRows = (container, rowSelector, handleSelector, onMove) => {
		const isRow = (element) => element.nodeType === 1 && element.matches(rowSelector) && !element.classList.contains('cms-sort-placeholder');
		const siblings = (row) => Array.prototype.filter.call(row.parentNode.children, (item) => item !== row && isRow(item));
		const moved = (row) => {
			if (window.jQuery) {
				window.jQuery(row).trigger('sortstop', [{ item: window.jQuery(row) }]);
			}

			if (onMove) {
				onMove(row);
			}
		};
		const ownRow = (target) => {
			const handle = target.closest ? target.closest(handleSelector) : null;
			const row = handle ? handle.closest(rowSelector) : null;

			return row && container.contains(row) ? { handle, row } : null;
		};

		container.addEventListener('pointerdown', (event) => {
			const found = event.button > 0 ? null : ownRow(event.target);

			if (!found) {
				return;
			}

			const { handle, row } = found;
			const placeholder = document.createElement(row.tagName === 'TR' ? 'tr' : 'div');
			let started = false;

			if (row.tagName === 'TR') {
				const cell = document.createElement('td');

				cell.colSpan = row.cells.length;
				placeholder.appendChild(cell);
			}

			placeholder.className = 'cms-sort-placeholder';
			event.preventDefault();
			handle.setPointerCapture(event.pointerId);

			const move = (moveEvent) => {
				const y = moveEvent.clientY;
				let before = null;

				if (!started) {
					started = true;
					row.classList.add('cms-sort-dragging');
				}

				const others = siblings(row);

				for (const item of others) {
					const box = item.getBoundingClientRect();

					if (y < box.top + box.height / 2) {
						before = item;
						break;
					}
				}

				// Below the last row: right after it (a subform's <template> may follow the rows)
				if (!before) {
					const last = others.length ? others[others.length - 1] : row;

					before = last === row ? row.nextSibling : last.nextSibling;
				}

				row.parentNode.insertBefore(placeholder, before);

				// Near the window's edges the page scrolls
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
				row.classList.remove('cms-sort-dragging');

				if (placeholder.parentNode) {
					const changed = placeholder.nextSibling !== row && placeholder.previousSibling !== row;

					if (changed) {
						placeholder.parentNode.insertBefore(row, placeholder);
					}

					placeholder.remove();

					if (changed) {
						moved(row);
					}
				}
			};

			handle.addEventListener('pointermove', move);
			handle.addEventListener('pointerup', stop);
			handle.addEventListener('pointercancel', stop);
		});

		// A touch on a handle drags, never pans the page (touch-action alone fails on inline handles)
		container.addEventListener('touchstart', (event) => {
			if (ownRow(event.target)) {
				event.preventDefault();
			}
		}, { passive: false });

		container.addEventListener('keydown', (event) => {
			const found = event.key === 'ArrowUp' || event.key === 'ArrowDown' ? ownRow(event.target) : null;

			if (!found) {
				return;
			}

			const { handle, row } = found;
			const others = siblings(row);
			const all = Array.prototype.filter.call(row.parentNode.children, isRow);
			const index = all.indexOf(row);

			event.preventDefault();

			if (event.key === 'ArrowUp' && index > 0) {
				row.parentNode.insertBefore(row, all[index - 1]);
			} else if (event.key === 'ArrowDown' && index < others.length) {
				row.parentNode.insertBefore(row, all[index + 1].nextSibling);
			} else {
				return;
			}

			handle.focus();
			moved(row);
		});
	};

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

			// The group's order[] values, given out again in the rows' new order, then posted with their cid[]
			const save = (row) => {
				const group = sameGroup(row).filter((item) => item.querySelector('[name="order[]"]'));
				const values = group.map((item) => parseInt(item.querySelector('[name="order[]"]').value, 10) || 0)
					.sort((a, b) => (options.sortDir === 'desc' ? b - a : a - b));

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

				fetch(options.url, {
					method: 'POST',
					body: new URLSearchParams(data),
					credentials: 'same-origin',
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
				}).catch((error) => window.console.warn(error));
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

	/* --- Cropping: selecting an area of an image (CMS.cropper; the template editor's image tool, what Jcrop was) --- */

	CMS.cropper = {
		/**
		 * Lets an area of an image be selected: drawn by dragging, moved, resized from its corners and edges, nudged
		 * with the arrow keys (Shift: 10 pixels), cleared with Escape or a click outside it. Coordinates are in the
		 * image's own pixels (options.trueSize, as the image may be shown smaller), as Jcrop gave them.
		 *
		 * @param  {HTMLImageElement}  image
		 * @param  {Object}            options  trueSize [width, height]; onChange(c), onSelect(c), onRelease(); c is
		 *                                      {x, y, x2, y2, w, h}
		 *
		 * @return  {Object}  release(), setSelect([x, y, x2, y2]), getSelection(), destroy()
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

				if (drag.mode === 'draw') {
					area = { x: Math.min(drag.start.x, p.x), y: Math.min(drag.start.y, p.y), w: Math.abs(p.x - drag.start.x), h: Math.abs(p.y - drag.start.y) };
				} else if (drag.mode === 'move') {
					area.x = Math.max(0, Math.min(width - drag.from.w, drag.from.x + p.x - drag.start.x));
					area.y = Math.max(0, Math.min(height - drag.from.h, drag.from.y + p.y - drag.start.y));
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
				if (event.key === 'Escape' && area) {
					event.preventDefault();
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
