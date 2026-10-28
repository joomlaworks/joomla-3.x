/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 *
 * Files dropped on a list of the Media Manager (or the image picker) are uploaded into the folder it shows, through the
 * JSON upload with the Media Manager's options, two at a time, each with its progress; the list reloads when they're in.
 * In the image picker, a single dropped image is selected after the reload.
 */
(() => {
	'use strict';

	const options = window.Joomla && Joomla.getOptions ? Joomla.getOptions('com_media.dropUpload') : null;

	if (!options) {
		return;
	}

	const SELECT_KEY = 'com_media.dropUpload.select';
	const PARALLEL   = 2;

	const text = (key, ...values) => values.reduce((string, value) => string.replace('%s', value), options.text[key] || key);

	// MooTools replaces Array.from on some pages, so lists are copied the old way
	const toArray = (list) => Array.prototype.slice.call(list || []);

	const hasFiles = (event) => event.dataTransfer && toArray(event.dataTransfer.types).indexOf('Files') !== -1;

	const element = (tag, className, content) => {
		const node = document.createElement(tag);

		node.className = className;

		if (content !== undefined) {
			node.textContent = content;
		}

		return node;
	};

	// In the image picker: the image dropped before the list reloaded
	const selectDropped = () => {
		let path = null;

		try {
			path = window.sessionStorage.getItem(SELECT_KEY);
			window.sessionStorage.removeItem(SELECT_KEY);
		} catch (e) {
			return;
		}

		if (!path || !window.parent || !window.parent.ImageManager) {
			return;
		}

		window.parent.ImageManager.populateFields(path);

		const link = document.querySelector('a.img-preview.selected');

		if (link) {
			link.scrollIntoView({ block: 'nearest' });
		}
	};

	let overlay = null;
	let panel   = null;
	let depth   = 0;
	let busy    = false;

	const build = () => {
		overlay = element('div', 'media-drop-overlay');
		overlay.hidden = true;
		overlay.appendChild(element('div', 'media-drop-overlay-text', text('hint', options.shown)));
		document.body.appendChild(overlay);
	};

	const showOverlay = (show) => {
		if (overlay) {
			overlay.hidden = !show;
		}
	};

	const upload = (file, row) => new Promise((resolve) => {
		const progress = row.querySelector('progress');
		const status   = row.querySelector('.media-drop-status');
		const finish   = (ok, message, path) => {
			row.classList.add(ok ? 'is-done' : 'is-failed');
			progress.hidden    = true;
			status.textContent = message;
			resolve({ ok, path, image: ok && /\.(avif|bmp|gif|jpe?g|png|webp)$/i.test(path || '') });
		};

		if (options.maxBytes > 0 && file.size > options.maxBytes) {
			finish(false, text('too_large', Math.round(options.maxBytes / 1048576)));

			return;
		}

		const data = new FormData();
		const xhr  = new XMLHttpRequest();

		data.append('Filedata', file, file.name);
		data.append('folder', options.folder);
		data.append(options.token, '1');

		xhr.open('POST', options.url);

		xhr.upload.addEventListener('progress', (event) => {
			if (event.lengthComputable) {
				progress.value = event.loaded / event.total;
			}
		});

		xhr.addEventListener('load', () => {
			let response = null;

			try {
				response = JSON.parse(xhr.responseText);
			} catch (e) {
				response = null;
			}

			if (xhr.status === 200 && response && String(response.status) === '1') {
				finish(true, text('done'), response.path || '');
			} else {
				finish(false, response && response.message ? response.message : text('failed'));
			}
		});

		xhr.addEventListener('error', () => finish(false, text('failed')));
		xhr.send(data);
	});

	const uploadAll = (files) => {
		if (!files.length || busy) {
			return;
		}

		busy = true;

		if (panel) {
			panel.remove();
		}

		panel = element('div', 'media-drop-panel');
		panel.setAttribute('role', 'status');

		const summary = element('div', 'media-drop-summary', text('uploading'));
		const list    = element('ul', 'media-drop-list');

		panel.appendChild(summary);
		panel.appendChild(list);
		document.body.appendChild(panel);

		const rows = files.map((file) => {
			const row = element('li', 'media-drop-item');

			row.appendChild(element('span', 'media-drop-name', file.name));
			row.appendChild(element('progress', 'media-drop-progress'));
			row.appendChild(element('span', 'media-drop-status'));
			row.querySelector('progress').max = 1;
			row.querySelector('progress').value = 0;
			list.appendChild(row);

			return row;
		});

		const results = new Array(files.length);
		let next = 0;

		const worker = () => {
			if (next >= files.length) {
				return Promise.resolve();
			}

			const index = next++;

			return upload(files[index], rows[index]).then((result) => {
				results[index] = result;

				return worker();
			});
		};

		const workers = [];

		for (let i = 0; i < Math.min(PARALLEL, files.length); i++) {
			workers.push(worker());
		}

		Promise.all(workers).then(() => {
			const done   = results.filter((result) => result.ok);
			const failed = results.length - done.length;

			busy = false;
			summary.textContent = text('summary', done.length, results.length);

			// A single image dropped on the image picker is selected once the list shows it
			if (options.picker && done.length === 1 && done[0].image) {
				try {
					window.sessionStorage.setItem(SELECT_KEY, done[0].path);
				} catch (e) {
					// The list still reloads, without the selection
				}
			}

			// All in: the list reloads with them. Some failed: their messages stay until closed
			if (!failed) {
				window.setTimeout(() => window.location.reload(), 600);

				return;
			}

			const close = element('button', 'btn btn-small media-drop-close', text('close'));

			close.type = 'button';
			close.addEventListener('click', () => {
				if (done.length) {
					window.location.reload();
				} else {
					panel.remove();
				}
			});
			panel.appendChild(close);
		});
	};

	document.addEventListener('dragenter', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		event.preventDefault();
		depth++;
		showOverlay(true);
	});

	document.addEventListener('dragover', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		event.preventDefault();
		event.dataTransfer.dropEffect = busy ? 'none' : 'copy';
	});

	document.addEventListener('dragleave', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		depth = Math.max(0, depth - 1);

		if (!depth) {
			showOverlay(false);
		}
	});

	document.addEventListener('drop', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		event.preventDefault();
		depth = 0;
		showOverlay(false);
		uploadAll(toArray(event.dataTransfer.files));
	});

	const start = () => {
		build();

		if (options.picker) {
			selectDropped();
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
