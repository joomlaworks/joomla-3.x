/**
 * @package     Joomla.Installation
 * @subpackage  JavaScript
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 *
 * The installer's script, without jQuery, Bootstrap or Joomla's core scripts. The views use data attributes:
 *   data-action="next"            submits the step's form (#adminForm)
 *   data-goto="view"              shows another step
 *   data-action="install-languages", "verify-ftp", "detect-ftp-root", "remove-folder"
 *   data-show-when="name=value"   shown only while the radio jform[name] has that value
 * Window.Install keeps the methods of the previous script, for markup which still calls them (e.g. onchange="Install.setlanguage()").
 */
(function () {
	'use strict';

	const optionsElement = document.getElementById('installation-options');
	const options = optionsElement ? JSON.parse(optionsElement.textContent) : {};
	const baseUrl = (options.url || 'index.php').replace(/&amp;/g, '&');
	const strings = options.strings || {};

	let busy = false;
	let view = options.view || '';
	let keepAliveTimer = null;

	const text = (key, fallback) => strings[key] || fallback || key;
	const container = () => document.getElementById('container-installation');
	const messageContainer = () => document.getElementById('system-message-container');

	/* Loading layer, messages and tokens */

	function loading(show) {
		const layer = document.getElementById('loading-layer');

		if (layer) {
			layer.hidden = !show;
		}
	}

	function removeMessages() {
		const box = messageContainer();

		if (box) {
			box.innerHTML = '';
		}
	}

	/**
	 * Render messages: an object of type => list of messages, as the installer's JSON responses send them.
	 */
	function renderMessages(messages) {
		const box = messageContainer();
		const classes = { message: 'success', notice: 'info', warning: 'warning', error: 'error' };

		if (!box || !messages) {
			return;
		}

		Object.keys(messages).forEach((type) => {
			const alert = document.createElement('div');

			alert.className = 'alert alert-' + (classes[type] || 'info');
			alert.setAttribute('role', 'alert');

			[].concat(messages[type]).forEach((message) => {
				const p = document.createElement('p');

				// The installer's own messages, which may hold markup (e.g. links)
				p.innerHTML = message;
				alert.appendChild(p);
			});

			box.appendChild(alert);
		});

		box.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function renderError(message) {
		renderMessages({ error: [message] });
	}

	/**
	 * Each response brings a new form token; replace the old one in every form.
	 */
	function replaceTokens(token) {
		if (!/^[0-9a-f]{32}$/.test(token || '')) {
			return;
		}

		document.querySelectorAll('input[type="hidden"][value="1"]').forEach((input) => {
			if (/^[0-9a-f]{32}$/.test(input.name)) {
				input.name = token;
			}
		});
	}

	/* Requests */

	function serialize(form) {
		// The installer reads JSON requests from this marker, as before
		return 'format: json&' + new URLSearchParams(new FormData(form)).toString();
	}

	function parse(textBody) {
		try {
			return JSON.parse(textBody);
		} catch (e) {
			return null;
		}
	}

	/**
	 * POST a form. Resolves with the parsed JSON response, rejects with a message.
	 */
	function post(url, form) {
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
			body: serialize(form),
		}).then((response) => response.text().then((body) => {
			const data = parse(body);

			if (data && data.token) {
				replaceTokens(data.token);
			}

			if (!response.ok || data === null) {
				const message = data && data.message ? data.message
					: (body.trim() === '' ? text('JLIB_JS_AJAX_ERROR_NO_CONTENT') : text('JLIB_JS_AJAX_ERROR_PARSE', 'Parse error'));

				return Promise.reject(new Error(response.status + ': ' + message));
			}

			return data;
		}), () => Promise.reject(new Error(text('JLIB_JS_AJAX_ERROR_CONNECTION_ABORT', 'Connection error'))));
	}

	/* Validation: the browser's own checks, on the fields which are shown */

	function validate(form) {
		form.classList.add('was-validated');

		const fields = Array.prototype.filter.call(form.elements, (element) => element.willValidate
			&& !element.closest('[hidden]') && element.getClientRects().length > 0);

		for (const field of fields) {
			if (!field.checkValidity()) {
				field.reportValidity();
				field.focus();

				return false;
			}
		}

		return true;
	}

	/* Navigation */

	function goToPage(page, fromSubmit) {
		if (!fromSubmit) {
			removeMessages();
			loading(true);
		}

		fetch(baseUrl + '?tmpl=body&view=' + encodeURIComponent(page), { credentials: 'same-origin' })
			.then((response) => response.text())
			.then((html) => {
				const box = container();

				box.innerHTML = html;
				box.classList.remove('is-entering');
				void box.offsetWidth;
				box.classList.add('is-entering');
				runScripts(box);
				view = page;
				initPage();
				window.scrollTo({ top: 0, behavior: 'smooth' });
			})
			.catch(() => renderError(text('JLIB_JS_AJAX_ERROR_CONNECTION_ABORT', 'Connection error')))
			.finally(() => {
				loading(false);
				busy = false;
			});

		return false;
	}

	/**
	 * Scripts in HTML set with innerHTML don't run; run those of a step (e.g. from a language pack's overrides).
	 */
	function runScripts(root) {
		root.querySelectorAll('script').forEach((old) => {
			const script = document.createElement('script');

			Array.prototype.forEach.call(old.attributes, (attribute) => script.setAttribute(attribute.name, attribute.value));
			script.textContent = old.textContent;
			old.replaceWith(script);
		});
	}

	function submitForm(form, url) {
		if (busy) {
			alert(text('INSTL_PROCESS_BUSY', 'Process is in progress. Please wait...'));

			return false;
		}

		if (!form) {
			return false;
		}

		if (form.id === 'adminForm' && !validate(form)) {
			return false;
		}

		busy = true;
		loading(true);
		removeMessages();

		post(url || baseUrl, form)
			.then((r) => {
				renderMessages(r.messages);

				const lang = document.documentElement.getAttribute('lang') || '';

				if (r.lang && lang.toLowerCase() === String(r.lang).toLowerCase()) {
					goToPage(r.data.view, true);
				} else {
					window.location = baseUrl + '?view=' + encodeURIComponent(r.data.view);
				}
			})
			.catch((error) => {
				loading(false);
				busy = false;
				renderError(error.message);
			});

		return false;
	}

	function submitform() {
		return submitForm(document.getElementById('adminForm'));
	}

	function setlanguage() {
		return submitForm(document.getElementById('languageForm'));
	}

	/* Installation */

	function install(tasks) {
		const form = document.getElementById('adminForm');
		const bar = document.querySelector('#install_progress .progress-bar');
		const total = tasks.length;
		let done = 0;

		const percent = document.getElementById('install_percent');

		const progress = (value) => {
			if (bar) {
				bar.style.width = value + '%';
				bar.parentNode.setAttribute('aria-valuenow', String(Math.round(value)));
			}

			if (percent) {
				percent.textContent = Math.round(value) + '%';
			}
		};

		const next = () => {
			if (!tasks.length) {
				progress(100);
				goToPage('complete');

				return;
			}

			const task = tasks.shift();
			const row = document.getElementById('install_' + task);

			if (row) {
				row.classList.add('active');
			}

			progress((done + 0.15) / total * 100);

			post(baseUrl + '?task=Install' + encodeURIComponent(task), form)
				.then((r) => {
					if (r.messages) {
						renderMessages(r.messages);
						goToPage(r.data.view, true);

						return;
					}

					done++;

					if (row) {
						row.classList.remove('active');
						row.classList.add('done');
					}

					progress(done / total * 100);
					next();
				})
				.catch((error) => {
					renderError(text('JLIB_DATABASE_ERROR_DATABASE_CONNECT', 'A database error occurred.') + ' ' + error.message);
					goToPage('summary');
				});
		};

		next();
	}

	/* FTP */

	function ftpRequest(button, task, onSuccess) {
		const form = button.closest('form');

		button.disabled = true;

		post(baseUrl + '?task=' + task, form)
			.then((r) => {
				if (r.error === false) {
					onSuccess(r);
				} else {
					renderError(r.message);
				}
			})
			.catch((error) => renderError(error.message))
			.finally(() => {
				button.disabled = false;
			});
	}

	function detectFtpRoot(button) {
		ftpRequest(button, 'detectftproot', (r) => {
			const root = document.getElementById('jform_ftp_root');

			if (root) {
				root.value = r.data.root;
			}
		});

		return false;
	}

	function verifyFtpSettings(button) {
		ftpRequest(button, 'verifyftpsettings', () => {
			removeMessages();
			renderMessages({ message: [text('INSTL_FTP_SETTINGS_CORRECT', 'Settings correct')] });
		});

		return false;
	}

	/* Removing the installation folder */

	function removeFolder(button) {
		const form = button.closest('form');
		const languages = document.getElementById('languages');
		const errorBox = document.getElementById('theDefaultError');
		const errorMessage = document.getElementById('theDefaultErrorMessage');

		const showError = (message) => {
			if (errorBox && errorMessage) {
				errorMessage.innerHTML = message;
				errorBox.hidden = false;
			} else {
				renderError(message);
			}
		};

		if (languages) {
			languages.hidden = true;
		}

		if (errorBox) {
			errorBox.hidden = true;
		}

		button.disabled = true;

		post(baseUrl + '?task=removefolder', form)
			.then((r) => {
				if (r.error === false) {
					button.textContent = r.data.text;
					button.classList.remove('btn-warning');
					button.classList.add('btn-success');
					stopKeepAlive();
				} else {
					showError(r.message);
					button.disabled = false;
				}
			})
			.catch((error) => {
				showError(error.message);
				button.disabled = false;
			});

		return false;
	}

	/* Show and hide parts of a form with a radio's value */

	function toggle(id, name, value) {
		const checked = document.querySelector('input[name="jform[' + name + ']"]:checked');
		const element = document.getElementById(id);

		if (element) {
			element.hidden = !checked || checked.value !== String(value);
		}
	}

	function updateConditional(root) {
		(root || document).querySelectorAll('[data-show-when]').forEach((element) => {
			const parts = element.getAttribute('data-show-when').split('=');
			const checked = document.querySelector('input[name="jform[' + parts[0] + ']"]:checked');

			element.hidden = !checked || checked.value !== parts[1];
		});
	}

	/* The database step: SQLite needs no server or user, and its "database name" is a file */

	function initDatabase(root) {
		const type = root.querySelector('#jform_db_type');
		const name = root.querySelector('#jform_db_name');

		if (!type || !name) {
			return;
		}

		const label = root.querySelector('#jform_db_name-lbl');
		const desc = root.querySelector('#db_name_desc');
		const groups = root.querySelectorAll('[data-sqlite="hide"]');
		const texts = {
			label: label ? label.innerHTML : '',
			desc: desc ? desc.innerHTML : '',
			sqliteLabel: name.form ? name.form.getAttribute('data-sqlite-label') || '' : '',
			sqliteDesc: name.form ? name.form.getAttribute('data-sqlite-desc') || '' : '',
		};
		let serverName = '';

		const randomName = () => {
			const bytes = new Uint8Array(8);

			window.crypto.getRandomValues(bytes);

			return 'database/joomla-' + Array.from(bytes, (b) => ('0' + b.toString(16)).slice(-2)).join('') + '.sqlite';
		};

		const update = () => {
			const sqlite = type.value === 'mysqlonsqlite';

			groups.forEach((group) => {
				group.hidden = sqlite;
			});

			if (label && texts.sqliteLabel) {
				label.innerHTML = sqlite ? texts.sqliteLabel : texts.label;
			}

			if (desc && texts.sqliteDesc) {
				desc.innerHTML = sqlite ? texts.sqliteDesc : texts.desc;
			}

			// Suggest a file with a name nobody can guess, keeping a database name typed for the other types
			if (sqlite && !/[\\/.]/.test(name.value)) {
				serverName = name.value;
				name.value = randomName();
			} else if (!sqlite && /\.sqlite$/.test(name.value)) {
				name.value = serverName;
			}
		};

		type.addEventListener('change', update);
		update();
	}

	/* Per step set-up, after each page load */

	function initPage() {
		const root = container();

		if (!root) {
			return;
		}

		initDatabase(root);
		updateConditional(root);

		const progress = root.querySelector('#install_progress[data-tasks]');

		if (progress) {
			install(JSON.parse(progress.getAttribute('data-tasks')));
		}

		const first = root.querySelector('#adminForm input:not([type="hidden"]):not([disabled]), #adminForm select, #adminForm textarea');

		if (first && root.querySelector('#adminForm .field') && !document.activeElement.closest('#container-installation')) {
			first.focus({ preventScroll: true });
		}
	}

	/* Events */

	document.addEventListener('click', (event) => {
		const target = event.target.closest('[data-action], [data-goto]');

		if (!target || !container() || !container().contains(target)) {
			return;
		}

		event.preventDefault();

		if (target.hasAttribute('data-goto')) {
			goToPage(target.getAttribute('data-goto'));

			return;
		}

		switch (target.getAttribute('data-action')) {
			case 'next':
				submitform();
				break;

			case 'install-languages':
				document.querySelectorAll('[data-languages-wait]').forEach((element) => {
					element.hidden = false;
				});
				document.querySelectorAll('[data-languages-desc]').forEach((element) => {
					element.hidden = true;
				});
				submitform();
				break;

			case 'verify-ftp':
				verifyFtpSettings(target);
				break;

			case 'detect-ftp-root':
				detectFtpRoot(target);
				break;

			case 'remove-folder':
				removeFolder(target);
				break;
		}
	});

	document.addEventListener('change', (event) => {
		if (event.target.matches('input[type="radio"]')) {
			updateConditional(container());
		}
	});

	// Enter in a field submits the step, as the "Next" button does
	document.addEventListener('submit', (event) => {
		const form = event.target;

		event.preventDefault();

		if (form.id === 'adminForm' && form.querySelector('[data-action="next"]')) {
			submitform();
		}
	});

	/* Keep the session alive while the installer is open */

	function startKeepAlive() {
		const interval = Math.max(60, parseInt(options.keepalive, 10) || 840) * 1000;

		keepAliveTimer = window.setInterval(() => {
			fetch(baseUrl + '?tmpl=body&view=' + encodeURIComponent(view || 'site'), { credentials: 'same-origin', cache: 'no-store' })
				.catch(() => {});
		}, interval);
	}

	function stopKeepAlive() {
		if (keepAliveTimer) {
			window.clearInterval(keepAliveTimer);
			keepAliveTimer = null;
		}
	}

	window.Install = {
		submitform: submitform,
		setlanguage: setlanguage,
		goToPage: goToPage,
		install: install,
		detectFtpRoot: detectFtpRoot,
		verifyFtpSettings: verifyFtpSettings,
		removeFolder: removeFolder,
		toggle: toggle,
	};

	initPage();
	startKeepAlive();
})();
