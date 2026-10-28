/**
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The CMS's shared JavaScript, in one file: the namespace window.CMS (what core.js was, plus vanilla versions of what
 * needed MooTools). Older names stay as aliases of the new code (see LEGACY at the end): Joomla.submitform() is
 * CMS.form.submit(), SqueezeBox.open() is CMS.modal.open(), jModalClose() is CMS.modal.close(), and so on.
 * media/system/js/core.js and core-uncompressed.js are copies of this file's minified and full versions, for pages
 * which name core.js themselves; it runs once however often it's loaded.
 */
((window, document) => {
	'use strict';

	if (window.CMS && window.CMS.global) {
		return;
	}

	// Scripts may have started the old namespace before this file: their editors and options stay
	const Joomla = window.Joomla = window.Joomla || {};

	const CMS = window.CMS = window.CMS || {};

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
					+ 'display:none;background:#fff url("' + root + '/media/jui/images/ajax-loader.gif") center no-repeat fixed;';
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

				if (modal.opener && typeof modal.opener.focus === 'function') {
					modal.opener.focus();
				}

				modal.options = merge({}, modal.presets);

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
	};
})(window, document);
