/**
 * @copyright  (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/* --- Drop upload --- */
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

/* --- Media field --- */
/*
 * The image field (the templates' layout joomla.form.field.media: .field-media-wrapper, its options in data attributes):
 * Select opens the image picker in the field's modal (a new frame each time), whose Insert sets the value; Clear empties
 * it; the preview (a popover on the eye, or an image under the field) and the path's tooltip follow the value. Fields of
 * subform rows added later start too. jQuery.fn.fieldMedia and jQuery.fieldMedia stay for extensions (where jQuery is).
 */
(() => {
	'use strict';

	const defaults = {
		basepath: '', // base path to file
		buttonClear: '.button-clear', // selector for button to clear the value
		buttonSelect: '.button-select', // selector for button to change the value
		buttonSaveSelected: '.button-save-selected', // selector for button to save the selected value
		input: '.field-media-input', // selector for the input
		preview: true, // whether use the preview
		previewAsTooltip: true, // whether use the preview
		previewContainer: '.field-media-preview', // selector for the preview container
		previewWidth: 200, // preview width
		previewHeight: 200, // preview height
		url: 'index.php?option=com_media&tmpl=component', // url for load the iframe
		modal: '.modal', // modal selector
		modalWidth: '100%', // modal width
		modalHeight: '300px', // modal height
	};

	const instances = new WeakMap();

	// The data attributes as jQuery's data() read them: data-preview-width is previewWidth, "true" true, "200" 200
	const dataOptions = (element) => {
		const options = {};

		Object.keys(element.dataset).forEach((name) => {
			const value = element.dataset[name];

			options[name] = value === 'true' ? true : value === 'false' ? false : /^-?\d+(\.\d+)?$/.test(value) ? Number(value) : value;
		});

		return options;
	};

	// A plugin call (modal, tooltip, popover): Bootstrap's when the page has it, else the CMS's (global.js)
	const call = (name, element, option) => {
		if (element && window.CMS && window.CMS.bootstrap) {
			window.CMS.bootstrap.call(name, element, option);
		}
	};

	class FieldMedia {
		constructor(container, options) {
			this.options = Object.assign({}, defaults, options);
			this.container = container;
			this.modal = container.querySelector(this.options.modal);
			this.modalBody = this.modal ? this.modal.querySelector('.modal-body') : null;
			this.input = container.querySelector(this.options.input);
			this.preview = container.querySelector(this.options.previewContainer);

			const select = container.querySelector(this.options.buttonSelect);
			const clear = container.querySelector(this.options.buttonClear);

			if (select) {
				select.addEventListener('click', () => this.modalOpen());
			}

			if (clear) {
				clear.addEventListener('click', () => this.clearValue());
			}

			// When the modal closes, its frame goes (through jQuery when the page has it, where both modals send "hide")
			if (this.modal && window.jQuery) {
				window.jQuery(this.modal).on('hide', () => this.removeIframe());
			} else if (this.modal) {
				this.modal.addEventListener('cms:modal-hide', () => this.removeIframe());
			}

			this.updatePreview();
		}

		// The image picker in the modal
		modalOpen() {
			if (!this.modalBody || !this.input) {
				return;
			}

			const frame = document.createElement('iframe');

			frame.name = 'field-media-modal';
			frame.src = this.options.url.replace('{field-media-id}', this.input.id);
			frame.style.width = this.options.modalWidth;
			frame.style.height = this.options.modalHeight;
			this.modalBody.appendChild(frame);
			call('modal', this.modal, 'show');
			document.body.classList.add('modal-open');

			// Each page the frame loads (folders are pages): its Insert sets the value, its Cancel lets the page scroll again
			frame.addEventListener('load', () => {
				let doc = null;

				try {
					doc = frame.contentDocument;
				} catch (error) {
					doc = null;
				}

				if (!doc) {
					return;
				}

				doc.addEventListener('click', (event) => {
					const target = event.target && event.target.closest ? event.target : null;

					if (target && target.closest(this.options.buttonSaveSelected)) {
						const url = doc.getElementById('f_url');

						if (url && url.value) {
							this.setValue(url.value);
						}

						this.modalClose();
					} else if (target && target.closest('.button-cancel')) {
						document.body.classList.remove('modal-open');
					}
				});
			});
		}

		modalClose() {
			call('modal', this.modal, 'hide');
			document.body.classList.remove('modal-open');
			this.removeIframe();
		}

		removeIframe() {
			if (this.modalBody) {
				this.modalBody.textContent = '';
			}

			document.body.classList.remove('modal-open');
		}

		setValue(value) {
			this.input.value = value;
			this.input.dispatchEvent(new Event('change', { bubbles: true }));
			this.updatePreview();
		}

		clearValue() {
			this.setValue('');
		}

		updatePreview() {
			if (!this.options.preview || !this.preview || !this.input) {
				return;
			}

			const value = this.input.value;

			if (!this.options.previewAsTooltip) {
				if (value) {
					const image = new Image();

					image.src = this.options.basepath + value;

					// As before: measured before it loads (0 by 0), so the height decides
					if (image.width > image.height) {
						image.style.width = this.options.previewWidth + 'px';
					} else {
						image.style.height = this.options.previewHeight + 'px';
					}

					this.preview.textContent = '';
					this.preview.appendChild(image);
				}

				return;
			}

			call('popover', this.preview, 'destroy');
			call('tooltip', this.input, 'destroy');

			if (!value) {
				call('popover', this.preview);

				return;
			}

			const image = new Image(this.options.previewWidth, this.options.previewHeight);

			image.src = this.options.basepath + value;
			call('popover', this.preview, { content: image });
			call('tooltip', this.input, { placement: 'top', title: value, html: false });
		}
	}

	const start = (element, options) => {
		if (!instances.has(element)) {
			instances.set(element, new FieldMedia(element, Object.assign({}, options || {}, dataOptions(element))));
		}

		return instances.get(element);
	};

	const startAll = (root) => {
		Array.prototype.forEach.call((root || document).querySelectorAll('.field-media-wrapper'), (element) => start(element));
	};

	// jQuery's names for it, for extensions
	const jq = window.jQuery;

	if (jq && jq.fn && !jq.fn.fieldMedia) {
		jq.fieldMedia = function fieldMedia(container, options) {
			return start(container && container.jquery ? container[0] : container, options);
		};
		jq.fieldMedia.defaults = defaults;
		jq.fn.fieldMedia = function fieldMedia(options) {
			return this.each((index, element) => {
				start(element, options);
			});
		};
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => startAll());
	} else {
		startAll();
	}

	document.addEventListener('cms:subform-row-add', (event) => startAll((event.detail && event.detail.row) || document));
})();

// The image field's old global functions (SqueezeBox's image picker and tooltips of the layout of stock Joomla)
function jInsertFieldValue(value, id) {
	const field = document.getElementById(id);

	if (field && field.value !== value) {
		field.value = value;
		field.dispatchEvent(new Event('change', { bubbles: true }));
		jMediaRefreshPreview(id);
	}
}

function jMediaRefreshPreview(id) {
	const field = document.getElementById(id);
	const image = document.getElementById(id + '_preview');
	const empty = document.getElementById(id + '_preview_empty');
	const shown = document.getElementById(id + '_preview_img');

	if (!field || !image) {
		return;
	}

	const value = field.value;

	image.setAttribute('src', value ? (field.getAttribute('data-basepath') || '') + value : '');

	if (empty) {
		empty.style.display = value ? 'none' : '';
	}

	if (shown) {
		shown.style.display = value ? '' : 'none';
	}
}

function jMediaRefreshPreviewTip(tip) {
	Array.prototype.forEach.call(tip.querySelectorAll('img.media-preview'), (image) => {
		const box = tip.querySelector('div.tip');

		if (box) {
			box.style.maxWidth = 'none';
		}

		jMediaRefreshPreview(image.id.substring(0, image.id.length - '_preview'.length));
		tip.style.display = 'block';
	});
}

// The tooltip of an image field's input showing its whole path
function jMediaRefreshImgpathTip(tip, element) {
	const field = element && element.jquery ? element[0] : element;
	const path = field ? field.value : '';
	const holder = document.getElementById('TipImgpath');

	tip.style.maxWidth = 'none';

	if (holder) {
		holder.textContent = path;
	}

	tip.style.display = path.length ? 'block' : 'none';
}

/* --- Media Manager --- */
/*
 * The Media Manager's page (MediaManager): the list in its frame (#folderframe), the views' buttons, the upload form's and
 * the folder field's folder following the list, the toolbar's Delete, the previews' players. The list's frame uses its
 * parent's.
 */
((scope) => {
	'use strict';

	// Defined by the Media Manager's page only (its list's iframe uses the parent's)
	if (scope.MediaManager) {
		return;
	}

	// The page's settings (com_media.manager): the files folder relative to the site, shown in the upload form, and the view
	const settings = (scope.Joomla && scope.Joomla.getOptions && scope.Joomla.getOptions('com_media.manager')) || {};

	if (scope.basepath === undefined) {
		scope.basepath = settings.basepath || '';
	}

	if (scope.viewstyle === undefined) {
		scope.viewstyle = settings.viewstyle || 'thumbs';
	}

	// The toolbar's Delete: only with something checked in the list
	scope.deleteMedia = () => {
		const frame = document.getElementById('folderframe');
		const list = frame && frame.contentDocument;

		if (!list || !list.querySelector('input[name="rm[]"]:checked')) {
			alert(scope.Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));

			return false;
		}

		MediaManager.submit('folder.delete'); // eslint-disable-line no-use-before-define

		return true;
	};

	// A modal's "hidden" (through jQuery when the page has it, where both modals send it)
	const onHidden = (id, callback) => {
		const element = document.getElementById(id);

		if (element && scope.jQuery) {
			scope.jQuery(element).on('hidden', callback);
		} else if (element) {
			element.addEventListener('cms:modal-hidden', callback);
		}
	};

	const MediaManager = scope.MediaManager = {
		/**
		 * Shows a video in the preview window's player (the browser's own); the lists call it from their iframe
		 *
		 * @param   {string}  src     The video's address
		 * @param   {string}  poster  An image of the same name, shown before it plays ('' for none)
		 */
		playVideo(src, poster) {
			const video = document.getElementById('videoPreviewPlayer');

			if (video) {
				if (poster) {
					video.setAttribute('poster', poster);
				} else {
					video.removeAttribute('poster');
				}

				video.src = src;
				video.load();
			}
		},

		// Pauses the preview's video (when its window closes)
		pauseVideo() {
			const video = document.getElementById('videoPreviewPlayer');

			if (video) {
				video.pause();
			}
		},

		/**
		 * Plays an audio file in the preview window's player (the browser's own); the lists call it from their iframe
		 *
		 * @param   {string}  src    The file's address
		 * @param   {string}  cover  An image of the same name ('' for none: the waveform)
		 */
		playAudio(src, cover) {
			const audio = document.getElementById('audioPreviewPlayer');
			const image = document.getElementById('audioPreviewCover');
			const wave = document.querySelector('#audioPlayer .media-audio-wave');

			if (!audio) {
				return;
			}

			// The file's cover (an image of the same name), else the waveform (an SVG: no hidden property, the attribute)
			if (image) {
				image.toggleAttribute('hidden', !cover);
				image.src = cover || '';
			}

			if (wave) {
				wave.toggleAttribute('hidden', !!cover);
			}

			audio.src = src;
			audio.load();
		},

		// Pauses the preview's audio (when its window closes)
		pauseAudio() {
			const audio = document.getElementById('audioPreviewPlayer');

			if (audio) {
				audio.pause();
			}
		},

		// Basic setup
		initialize() {
			const player = document.getElementById('audioPreviewPlayer');
			const holder = document.getElementById('audioPlayer');

			this.folderpath = document.getElementById('folderpath');
			this.updatepaths = document.querySelectorAll('input.update-folder');
			this.frame = window.frames.folderframe;
			this.frameElement = document.getElementById('folderframe');

			// The previews stop when their window closes
			onHidden('videoPreview', () => this.pauseVideo());
			onHidden('audioPreview', () => this.pauseAudio());

			// The waveform moves while the audio plays
			if (player && holder) {
				['play', 'playing'].forEach((type) => player.addEventListener(type, () => holder.classList.add('is-playing')));
				['pause', 'ended', 'emptied'].forEach((type) => player.addEventListener(type, () => holder.classList.remove('is-playing')));
			}

			// Every page the list loads (a folder): the upload form and the folder field follow it
			if (this.frameElement) {
				this.frameElement.addEventListener('load', () => this.onloadframe());
			}
		},

		/**
		 * Called from outside. Only ever called with task 'folder.delete'
		 *
		 * @param   {string}  task
		 */
		submit(task) {
			const form = this.frame.document.getElementById('mediamanager-form');
			const username = document.getElementById('username');
			const password = document.getElementById('password');

			form.task.value = task;

			if (username) {
				form.username.value = username.value;
				form.password.value = password ? password.value : '';
			}

			form.submit();
		},

		// The list's frame loaded a folder
		onloadframe() {
			this.frameurl = this.frame.location.href;

			const folder = this.getFolder() || '';
			const upload = document.getElementById('uploadForm');
			const active = document.getElementById(scope.viewstyle);

			Array.prototype.forEach.call(this.updatepaths, (field) => {
				field.value = folder;
			});

			if (this.folderpath) {
				this.folderpath.value = scope.basepath + (folder ? '/' + folder : '');
			}

			if (upload) {
				const action = new URL(upload.getAttribute('action'), window.location.href);

				action.searchParams.set('folder', folder);
				action.hash = '';
				upload.setAttribute('action', action.href);
			}

			if (active) {
				active.classList.add('active');
			}
		},

		/**
		 * Switch the view type
		 *
		 * @param  {string}  type  'thumbs' || 'details'
		 */
		setViewType(type) {
			const next = document.getElementById(type);
			const previous = document.getElementById(scope.viewstyle);

			if (next) {
				next.classList.add('active');
			}

			if (previous && previous !== next) {
				previous.classList.remove('active');
			}

			scope.viewstyle = type;

			this.setFrameUrl('index.php?option=com_media&view=mediaList&tmpl=component&folder=' + encodeURIComponent(this.getFolder())
				+ '&layout=' + encodeURIComponent(type));
		},

		refreshFrame() {
			this.setFrameUrl();
		},

		getFolder() {
			return new URLSearchParams(this.frame.location.search).get('folder') || '';
		},

		// Without an address (refreshFrame()) the list reloads
		setFrameUrl(url) {
			if (url) {
				this.frameurl = url;
			}

			this.frame.location.href = this.frameurl;
		},
	};

	const start = () => {
		if (!window.frames.folderframe) {
			return;
		}

		MediaManager.initialize();

		// The list may have loaded already (it's in the page)
		try {
			if (MediaManager.frame.document.readyState === 'complete' && MediaManager.frame.location.href !== 'about:blank') {
				MediaManager.onloadframe();
			}
		} catch (error) {
			// Not loaded yet: its load event follows
		}

		// What the old script offered for the next load (kept for extensions; every load updates the form now)
		document.updateUploader = () => {
			if (MediaManager.frameElement) {
				MediaManager.frameElement.addEventListener('load', () => MediaManager.onloadframe(), { once: true });
			}
		};
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})(window);

/* --- Image picker --- */
/*
 * The image picker (view images: the editors' Image button, the image field's modal): its list in a frame (#imageframe),
 * the folder list and Up button, the upload form following the folder, the chosen image's fields, and Insert (onok()),
 * which writes the image into the editor. The list's frame uses its parent's ImageManager.
 */
((doc) => {
	'use strict';

	// Defined by the image picker only (its list's iframe uses the parent's, set before this deferred file runs)
	if (window.ImageManager) {
		return;
	}

	// A query string of these values (missing ones empty, as jQuery's $.param() wrote them)
	const query = (values) => Object.keys(values)
		.map((name) => encodeURIComponent(name) + '=' + encodeURIComponent(values[name] === undefined || values[name] === null ? '' : values[name]))
		.join('&');

	window.ImageManager = {
		// Initialization
		initialize() {
			const options = Joomla.getOptions('mediamanager') || {};
			const frame = doc.getElementById('imageframe');
			const up = doc.getElementById('upbutton');

			this.author = options.author;
			this.base = options.base;
			this.asset = options.asset;
			this.editor = new URLSearchParams(window.self.location.search).get('e_name');

			// Setup image manager fields object
			this.fields = {
				url: doc.getElementById('f_url'),
				alt: doc.getElementById('f_alt'),
				align: doc.getElementById('f_align'),
				title: doc.getElementById('f_title'),
				caption: doc.getElementById('f_caption'),
				c_class: doc.getElementById('f_caption_class'),
			};

			// Setup image listing objects
			this.folderlist = doc.getElementById('folderlist');
			this.frame = window.frames.imageframe;
			this.frameurl = this.frame.location.href;

			// Setup image listing frame
			frame.addEventListener('load', () => this.onloadimageview());

			// Setup folder up button
			if (up) {
				up.addEventListener('click', () => this.upFolder());
			}
		},

		// The list's frame loaded a folder: the folder list and the upload form follow it
		onloadimageview() {
			const folder = this.getImageFolder();
			const form = doc.getElementById('uploadForm');

			// Update the frame url
			this.frameurl = this.frame.location.href;
			this.setFolder(folder);

			if (form) {
				const action = new URL(form.getAttribute('action'), window.location.href);

				action.searchParams.set('folder', folder === null ? '' : folder);
				action.hash = '';
				form.setAttribute('action', action.href);
			}
		},

		// The folder shown in the list's frame
		getImageFolder() {
			return new URLSearchParams(this.frame.location.search).get('folder');
		},

		/**
		 * Insert: the chosen image (and its caption) written into the editor
		 *
		 * @return  {boolean}  Always true
		 */
		onok() {
			const url = this.fields.url.value;
			const alt = this.fields.alt.value;
			const align = this.fields.align.value;
			const title = this.fields.title.value;
			const caption = this.fields.caption.value;
			const captionClass = this.fields.c_class.value;
			const attr = [];
			let tag = '';

			if (url) {
				// Set alt attribute
				attr.push('alt="' + alt + '"');

				// Set align attribute
				if (align && !caption) {
					attr.push('class="pull-' + align + '"');
				}

				// Set title attribute
				if (title) {
					attr.push('title="' + title + '"');
				}

				tag = '<img src="' + url + '" ' + attr.join(' ') + '/>';

				// Process caption
				if (caption) {
					tag = '<figure' + (align ? ' class="pull-' + align + '"' : '') + '>' + tag
						+ '<figcaption' + (captionClass ? ' class="' + captionClass + '"' : '') + '>' + caption + '</figcaption></figure>';
				}
			}

			// Use the API, if editor supports it
			if (window.Joomla && Joomla.editors.instances.hasOwnProperty(this.editor)) {
				Joomla.editors.instances[this.editor].replaceSelection(tag);
			} else {
				window.parent.jInsertEditorText(tag, this.editor);
			}

			return true;
		},

		/**
		 * Called from outside when the directory selector is used.
		 *
		 * @param   {string}  folder  The folder to switch to
		 * @param   {mixed}   asset   Probably an integer or undefined, optional
		 * @param   {mixed}   author  Probably an integer or undefined, optional
		 */
		setFolder(folder, asset, author) {
			for (let i = 0; i < this.folderlist.length; i += 1) {
				if (folder == this.folderlist.options[i].value) { // eslint-disable-line eqeqeq
					this.folderlist.selectedIndex = i;

					// Its searchable list is drawn again (Chosen hears it natively and through jQuery)
					this.folderlist.dispatchEvent(new CustomEvent('liszt:updated'));

					break;
				}
			}

			if (!!asset || !!author) {
				this.setFrameUrl(folder, asset, author);
			}
		},

		// Move up one directory
		upFolder() {
			const path = this.folderlist.value.split('/');

			path.pop();

			const search = path.join('/');

			this.setFolder(search);
			this.setFrameUrl(search, this.asset, this.author);
		},

		/**
		 * Called from outside when a file is selected
		 *
		 * @param   {string}  file  Relative path to the file.
		 */
		populateFields(file) {
			const list = doc.getElementById('imageframe');
			const call = "javascript:ImageManager.populateFields('" + file + "')";

			if (list && list.contentDocument) {
				Array.prototype.forEach.call(list.contentDocument.querySelectorAll('a.img-preview'), (link) => {
					link.classList.toggle('selected', link.href === call || link.getAttribute('href') === call);
				});
			}

			this.fields.url.value = this.base + file;
		},

		/**
		 * Not used. Should display messages. There are none.
		 *
		 * @param   {string}  text  The message text
		 */
		showMessage(text) {
			const message = doc.getElementById('message');
			const messages = doc.getElementById('messages');

			if (message) {
				if (message.firstElementChild) {
					message.firstElementChild.remove();
				}

				message.insertAdjacentHTML('beforeend', text);
			}

			if (messages) {
				messages.style.display = 'block';
			}
		},

		// Not used. Refreshes the iframe
		refreshFrame() {
			this.frame.location.href = this.frameurl;
		},

		/**
		 * Sets the iframe url, loading a new page. Usually for changing directory.
		 *
		 * @param  {string}  folder  Relative path to directory
		 * @param  {mixed}   asset   Probably an integer or undefined, optional
		 * @param  {mixed}   author  Probably an integer or undefined, optional
		 */
		setFrameUrl(folder, asset, author) {
			this.frameurl = 'index.php?' + query({
				option: 'com_media',
				view: 'imagesList',
				tmpl: 'component',
				asset,
				author,
				folder,
			});
			this.frame.location.href = this.frameurl;
		},

		/**
		 * Convert a query string to an object (as before, for extensions' scripts)
		 *
		 * @param   {string}  q  A query string (no leading ?)
		 *
		 * @return  {Object}
		 */
		getQueryObject(q) {
			const result = {};

			(q || '').split(/[&;]/).forEach((part) => {
				const keys = part.split('=');

				result[decodeURIComponent(keys[0])] = keys.length === 2 ? decodeURIComponent(keys[1]) : null;
			});

			return result;
		},
	};

	const start = () => {
		if (doc.getElementById('imageframe')) {
			window.ImageManager.initialize();
		}
	};

	if (doc.readyState === 'loading') {
		doc.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})(document);

/* --- Cropping (the Media Manager's Crop button) --- */
/*
 * The image checked in the list opens in the CMS's modal: a bar of shapes above it (Free, or a ratio, which selects the
 * largest area of that shape), the selection's size below it, and Cancel, Save & Replace and Save As New under that. The
 * area is posted in the image's own pixels to com_media's crop task; the list reloads with the result.
 */
(() => {
	'use strict';

	const RATIOS = ['21:9', '16:9', '14:9', '4:3', '3:2', '1:1'];
	const TYPES = /\.(?:jpe?g|png|webp|gif)$/i;

	const start = () => {
		const CMS = window.CMS;
		const options = CMS && CMS.options.get('com_media.crop');
		const button = document.getElementById('media-crop');
		const frame = document.getElementById('folderframe');

		if (!options || !button || !frame || !CMS.cropper || !CMS.modal) {
			return;
		}

		const text = (key) => CMS.text.get(key, key);
		const listDocument = () => {
			try {
				return frame.contentDocument;
			} catch (error) {
				return null;
			}
		};

		// One checked image of a type that can be cropped
		const chosen = () => {
			const doc = listDocument();
			const boxes = doc ? Array.prototype.filter.call(doc.querySelectorAll('input[name="rm[]"]'), (box) => box.checked) : [];

			return boxes.length === 1 && /^cb-image/.test(boxes[0].id) && TYPES.test(boxes[0].value) ? boxes[0].value : null;
		};
		const update = () => {
			button.disabled = !chosen();
		};
		const watch = () => {
			const doc = listDocument();

			update();

			if (doc) {
				doc.addEventListener('change', update);
				doc.addEventListener('click', () => setTimeout(update, 0));
			}
		};

		const folder = () => (window.MediaManager && window.MediaManager.frame ? window.MediaManager.getFolder() || '' : '');
		const address = (path) => options.base + '/' + path.split('/').map(encodeURIComponent).join('/');

		// After a replacement the browser still holds the old picture: the list's links to it ask for a fresh one
		const refresh = (path) => {
			const stamp = 't=' + Date.now();

			frame.addEventListener('load', () => {
				const doc = listDocument();

				if (!doc) {
					return;
				}

				doc.querySelectorAll('img[src], a[href]').forEach((element) => {
					const attribute = element.tagName === 'IMG' ? 'src' : 'href';
					let url;

					try {
						url = new URL(element.getAttribute(attribute), doc.baseURI);
					} catch (error) {
						return;
					}

					if (decodeURIComponent(url.pathname).endsWith('/' + path)) {
						url.search = stamp;
						element.setAttribute(attribute, url.href);
					}
				});
			}, { once: true });

			window.MediaManager.refreshFrame();
		};

		const open = (name) => {
			const path = (folder() ? folder() + '/' : '') + name;
			const root = document.createElement('div');
			const bar = document.createElement('div');
			const stage = document.createElement('div');
			const image = document.createElement('img');
			const size = document.createElement('p');
			let cropper = null;
			let busy = false;
			let buttons = [];

			root.className = 'media-crop';
			bar.className = 'media-crop-bar btn-group';
			bar.setAttribute('role', 'group');
			bar.setAttribute('aria-label', text('COM_MEDIA_CROP_RATIO'));
			stage.className = 'media-crop-stage';
			size.className = 'media-crop-size';
			size.setAttribute('aria-live', 'polite');
			image.alt = name;

			[['0', text('COM_MEDIA_CROP_FREE')]].concat(RATIOS.map((ratio) => [ratio, ratio])).forEach(([value, label]) => {
				const choice = document.createElement('button');

				choice.type = 'button';
				choice.className = 'btn btn-small' + (value === '0' ? ' active' : '');
				choice.setAttribute('aria-pressed', value === '0' ? 'true' : 'false');
				choice.setAttribute('data-ratio', value);
				choice.textContent = label;
				bar.appendChild(choice);
			});

			stage.appendChild(image);
			root.appendChild(bar);
			root.appendChild(stage);
			root.appendChild(size);

			const showSize = (c) => {
				size.classList.remove('text-error');
				size.textContent = c && c.w > 0 && c.h > 0 ? text('COM_MEDIA_CROP_SIZE').replace('%s', c.w).replace('%s', c.h) : text('COM_MEDIA_CROP_NO_AREA');
			};

			// The image as large as the space between the bar and the size allows
			const fit = () => {
				const selection = cropper ? cropper.getSelection() : null;

				image.style.maxHeight = Math.max(50, stage.clientHeight) + 'px';

				if (selection) {
					cropper.setSelect([selection.x, selection.y, selection.x2, selection.y2]);
				}
			};

			bar.addEventListener('click', (event) => {
				const choice = event.target.closest('[data-ratio]');

				if (!choice || !cropper) {
					return;
				}

				const [w, h] = choice.getAttribute('data-ratio').split(':').map(Number);

				bar.querySelectorAll('[data-ratio]').forEach((item) => {
					item.classList.toggle('active', item === choice);
					item.setAttribute('aria-pressed', item === choice ? 'true' : 'false');
				});

				cropper.setOptions({ aspectRatio: h ? w / h : 0 });

				// A shape selects its largest area; Free keeps the selection
				if (h) {
					cropper.maximise();
				}
			});

			const save = (mode) => {
				const c = cropper ? cropper.getSelection() : null;

				if (busy) {
					return;
				}

				if (!c || c.w < 1 || c.h < 1) {
					size.classList.add('text-error');
					size.textContent = text('COM_MEDIA_CROP_NO_AREA');

					return;
				}

				if (mode === 'replace' && !window.confirm(text('COM_MEDIA_CROP_CONFIRM_REPLACE').replace('%s', name))) {
					return;
				}

				const data = new FormData();

				data.append(CMS.options.get('csrf.token', ''), '1');
				data.append('folder', folder());
				data.append('file', name);
				data.append('mode', mode);
				[['x', c.x], ['y', c.y], ['w', c.w], ['h', c.h]].forEach(([key, value]) => data.append(key, value));

				busy = true;
				buttons.forEach((item) => {
					item.disabled = true;
				});

				fetch(options.url, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then((response) => response.json())
					.then((answer) => {
						if (!answer || answer.status !== '1') {
							throw new Error(answer && answer.message ? answer.message : text('COM_MEDIA_ERROR_UNABLE_TO_CROP'));
						}

						busy = false;
						CMS.modal.close();
						Joomla.renderMessages({ message: [answer.message] });
						refresh(mode === 'replace' ? path : answer.path);
					})
					.catch((error) => {
						busy = false;
						buttons.forEach((item) => {
							item.disabled = false;
						});
						size.classList.add('text-error');
						size.textContent = error && error.message && !(error instanceof SyntaxError) ? error.message : text('COM_MEDIA_ERROR_UNABLE_TO_CROP');
					});
			};

			CMS.modal.open(root, {
				handler: 'adopt',
				title: text('COM_MEDIA_CROP_TITLE').replace('%s', name),
				size: { x: Math.min(1400, window.innerWidth - 80), y: Math.min(900, window.innerHeight - 140) },
				closable: () => !busy,
				buttons: [
					{ text: text('JCANCEL'), className: 'btn', onClick: () => CMS.modal.close() },
					{ text: text('COM_MEDIA_CROP_SAVE_REPLACE'), className: 'btn btn-danger', onClick: () => save('replace') },
					{ text: text('COM_MEDIA_CROP_SAVE_NEW'), className: 'btn btn-primary', onClick: () => save('new') },
				],
				onClose() {
					window.removeEventListener('resize', fit);

					if (cropper) {
						cropper.destroy();
						cropper = null;
					}
				},
			});

			buttons = Array.prototype.slice.call(document.querySelectorAll('#sbox-buttons .btn'));
			buttons.slice(1).forEach((item) => {
				item.disabled = true;
			});

			image.addEventListener('load', () => {
				fit();
				cropper = CMS.cropper.attach(image, { onChange: showSize, onSelect: showSize, onRelease: () => showSize(null) });
				showSize(null);
				buttons.forEach((item) => {
					item.disabled = false;
				});
				window.addEventListener('resize', fit);
			}, { once: true });
			image.addEventListener('error', () => {
				size.classList.add('text-error');
				size.textContent = text('COM_MEDIA_ERROR_UNABLE_TO_CROP');
			}, { once: true });
			image.src = address(path) + '?t=' + Date.now();
		};

		frame.addEventListener('load', watch);
		watch();
		button.addEventListener('click', () => {
			const name = chosen();

			if (name) {
				open(name);
			}
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* --- Renaming (the pencil next to each name in the Media Manager's lists) --- */
/*
 * Runs in the list's frame. Every item with a checkbox (its value is the item's name) gets a pencil after its name; the
 * pencil turns the name into a field (a file's extension stays and isn't shown) with save and cancel buttons, Enter and
 * Escape. Saved through com_media's rename task, then the list reloads; a renamed folder is renamed in the folder tree too.
 */
(() => {
	'use strict';

	const start = () => {
		const CMS = window.CMS;
		const options = CMS && CMS.options.get('com_media.rename');

		if (!options) {
			return;
		}

		const text = options.text || {};
		const top = window.parent && window.parent !== window ? window.parent : window;
		const message = (type, value) => {
			const joomla = top.Joomla || window.Joomla;

			if (value && joomla && joomla.renderMessages) {
				joomla.renderMessages({ [type]: [value] });
			}
		};
		const button = (kind, label) => {
			const element = document.createElement('button');

			element.type = 'button';
			element.className = 'media-rename-' + kind;
			element.title = label;
			element.setAttribute('aria-label', label);
			element.innerHTML = options.icons[kind];

			return element;
		};

		// The folder tree of the page around the list: the renamed folder's name, and its and its subfolders' links
		const renameInTree = (oldPath, newPath, newName) => {
			let doc;

			try {
				doc = top.document;
			} catch (error) {
				return;
			}

			doc.querySelectorAll('a.folder-url').forEach((link) => {
				const url = new URL(link.getAttribute('href'), doc.baseURI);
				const path = url.searchParams.get('folder') || '';

				if (path !== oldPath && path.indexOf(oldPath + '/') !== 0) {
					return;
				}

				url.searchParams.set('folder', newPath + path.slice(oldPath.length));
				link.setAttribute('href', 'index.php' + url.search);

				if (path === oldPath) {
					const label = Array.prototype.filter.call(link.childNodes, (node) => node.nodeType === 3 && node.textContent.trim()).pop();

					if (label) {
						label.textContent = ' ' + newName + ' ';
					}
				}
			});
		};

		const edit = (box, label, pencil, place) => {
			const isFolder = /^cb-folder/.test(box.id);
			const name = box.value;
			const dot = isFolder ? -1 : name.lastIndexOf('.');
			// The extension stays as it is (the server keeps it), so the field holds the name before it
			const base = dot > 0 ? name.slice(0, dot) : name;
			const form = document.createElement('span');
			const input = document.createElement('input');
			const save = button('save', text.save);
			const cancel = button('cancel', text.cancel);
			let busy = false;

			form.className = 'media-rename-form';
			input.type = 'text';
			input.className = 'media-rename-input';
			input.value = base;
			input.setAttribute('aria-label', text.name + ': ' + name);
			input.spellcheck = false;
			form.appendChild(input);

			// In tiles they sit above the name, so the field has the tile's width
			const actions = document.createElement('span');

			actions.className = 'media-rename-actions';
			actions.appendChild(save);
			actions.appendChild(cancel);
			form.appendChild(actions);
			label.hidden = true;
			pencil.hidden = true;
			place.appendChild(form);
			input.focus();
			input.select();

			const close = () => {
				form.remove();
				label.hidden = false;
				pencil.hidden = false;
				pencil.focus();
			};

			const submit = () => {
				const wanted = input.value.trim();

				if (busy) {
					return;
				}

				if (!wanted || wanted === base) {
					close();

					return;
				}

				const data = new FormData();

				data.append(CMS.options.get('csrf.token', ''), '1');
				data.append('folder', options.folder || '');
				data.append('name', name);
				data.append('type', isFolder ? 'folder' : 'file');
				data.append('newname', wanted);

				busy = true;
				form.classList.add('is-busy');
				[input, save, cancel].forEach((element) => {
					element.disabled = true;
				});

				fetch(options.url, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then((response) => response.json())
					.then((answer) => {
						if (!answer || answer.status !== '1') {
							throw new Error(answer && answer.message ? answer.message : '');
						}

						if (!answer.message) {
							close();

							return;
						}

						if (isFolder) {
							const parent = options.folder ? options.folder + '/' : '';

							renameInTree(parent + name, parent + answer.name, answer.name);
						}

						message('message', answer.message);
						window.location.reload();
					})
					.catch((error) => {
						busy = false;
						form.classList.remove('is-busy');
						[input, save, cancel].forEach((element) => {
							element.disabled = false;
						});
						message('error', error && error.message && !(error instanceof SyntaxError) ? error.message : (text.failed || '').replace('%s', name));
						input.focus();
					});
			};

			input.addEventListener('keydown', (event) => {
				if (event.key === 'Enter') {
					event.preventDefault();
					submit();
				} else if (event.key === 'Escape') {
					event.preventDefault();
					close();
				}
			});
			save.addEventListener('click', submit);
			cancel.addEventListener('click', close);

			// Clicks in the field don't reach the tile (which opens the file) or select it
			form.addEventListener('click', (event) => event.stopPropagation());
		};

		document.querySelectorAll('input[name="rm[]"]').forEach((box) => {
			const item = box.closest('li.imgOutline, tr');
			const place = item ? item.querySelector('td.description') || item.querySelector('.imgPreview') || item.querySelector('.small') : null;

			if (!place || place.classList.contains('media-rename')) {
				return;
			}

			// The name as it was, kept whole (links, text), hidden while editing
			const label = document.createElement('span');
			const pencil = button('pencil', text.rename);

			label.className = 'media-rename-label';

			while (place.firstChild) {
				label.appendChild(place.firstChild);
			}

			place.classList.add('media-rename');
			place.appendChild(label);

			// Tiles: on the name bar's edge (CSS), where saving and cancelling then are; in the details view after the name
			place.appendChild(pencil);

			pencil.addEventListener('click', (event) => {
				event.preventDefault();
				event.stopPropagation();
				edit(box, label, pencil, place);
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* --- The bar above the list: thumbnail sizes, Check All Items (next to the views' buttons) --- */
/*
 * The size of the list's tiles, in steps (data-sizes; data-default the size without a choice), set as --media-tile on the
 * list's frame and kept for the session (the frame sets it from there before drawing, view medialist). Shown with the
 * thumbnails only.
 */
(() => {
	'use strict';

	const KEY = 'com_media.tileSize';

	const start = () => {
		const group = document.querySelector('.media-tile-size');
		const frame = document.getElementById('folderframe');

		if (!group || !frame) {
			return;
		}

		const sizes = group.getAttribute('data-sizes').split(',').map(Number);
		const standard = Number(group.getAttribute('data-default'));
		const read = () => {
			try {
				const stored = parseInt(sessionStorage.getItem(KEY), 10);

				return sizes.indexOf(stored) > -1 ? stored : standard;
			} catch (error) {
				return standard;
			}
		};
		let size = read();

		const apply = () => {
			try {
				frame.contentDocument.documentElement.style.setProperty('--media-tile', size + 'px');
			} catch (error) {
				// The list isn't there yet
			}

			group.querySelector('[data-tile-size="-1"]').disabled = size === sizes[0];
			group.querySelector('[data-tile-size="1"]').disabled = size === sizes[sizes.length - 1];
			group.querySelector('[data-tile-size="0"]').disabled = size === standard;
		};

		group.addEventListener('click', (event) => {
			const button = event.target.closest('[data-tile-size]');

			if (!button) {
				return;
			}

			const step = Number(button.getAttribute('data-tile-size'));
			const index = sizes.indexOf(size);

			size = step === 0 ? standard : sizes[Math.max(0, Math.min(sizes.length - 1, index + step))];

			try {
				if (size === standard) {
					sessionStorage.removeItem(KEY);
				} else {
					sessionStorage.setItem(KEY, String(size));
				}
			} catch (error) {
				// Kept for this page only
			}

			apply();
		});

		// Check All Items: the list's checkboxes from the page's one, which follows them (all, some, none); with the
		// thumbnails only (the details table has one in its head), and only when the list has checkboxes
		const all = document.getElementById('media-check-all');
		const allLabel = all ? all.closest('label') : null;
		let thumbs = !group.hidden;
		const boxes = () => {
			try {
				return Array.prototype.slice.call(frame.contentDocument.querySelectorAll('input[name="rm[]"]'));
			} catch (error) {
				return [];
			}
		};
		const follow = () => {
			const list = boxes();
			const checked = list.filter((box) => box.checked).length;

			if (all) {
				allLabel.hidden = !thumbs || !list.length;
				all.checked = list.length > 0 && checked === list.length;
				all.indeterminate = checked > 0 && checked < list.length;
			}
		};

		if (all) {
			all.addEventListener('change', () => {
				// Every box first, then their events (which make the page's box follow)
				const on = all.checked;
				const changed = boxes().filter((box) => box.checked !== on);
				const form = frame.contentDocument.getElementById('mediamanager-form');

				changed.forEach((box) => {
					box.checked = on;
				});

				if (form && form.boxchecked) {
					form.boxchecked.value = on ? boxes().length : 0;
				}

				changed.forEach((box) => box.dispatchEvent(new Event('change', { bubbles: true })));
			});
		}

		// The views' buttons (MediaManager.setViewType()): the sizes and Check All Items go with the thumbnails
		['thumbs', 'details'].forEach((id) => {
			const view = document.getElementById(id);

			if (view) {
				view.addEventListener('click', () => {
					thumbs = id === 'thumbs';
					group.hidden = !thumbs;
					follow();
				});
			}
		});

		// The list's frame as tall as the window allows below its top (it was 500 pixels), less a status bar fixed at the
		// window's foot (Prothero's)
		const fitFrame = () => {
			const status = document.getElementById('status');
			const fixed = status && window.getComputedStyle(status).position === 'fixed' ? status.offsetHeight : 0;
			const top = frame.getBoundingClientRect().top + window.pageYOffset;

			frame.style.height = Math.max(300, window.innerHeight - top - fixed - 16) + 'px';
		};

		window.addEventListener('resize', fitFrame);
		fitFrame();

		frame.addEventListener('load', () => {
			apply();
			follow();

			try {
				frame.contentDocument.addEventListener('change', follow);
			} catch (error) {
				// Another page in the frame
			}
		});
		apply();
		follow();

		try {
			frame.contentDocument.addEventListener('change', follow);
		} catch (error) {
			// Not loaded yet: the load listener adds it
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* --- Image preview (the list's images in the CMS's modal) --- */
/*
 * MediaPreview.open(address, listDocument), called by the list's frame: the image as large as the window allows, with a
 * bar under it (its name; its pixel size and file size) and, when the folder has more images, buttons at its sides and
 * the arrow keys to go through them (in the list's order, round; neighbours loaded ahead).
 */
(() => {
	'use strict';

	if (window.MediaPreview) {
		return;
	}

	const text = (key, fallback) => (window.CMS ? window.CMS.text.get(key, fallback) : fallback);
	const bytes = (size) => {
		const units = ['B', 'KB', 'MB', 'GB'];
		let unit = 0;

		while (size >= 1024 && unit < units.length - 1) {
			size /= 1024;
			unit += 1;
		}

		return (unit ? size.toFixed(size < 10 ? 1 : 0) : size) + ' ' + units[unit];
	};
	const nameOf = (address) => {
		try {
			return decodeURIComponent(new URL(address, document.baseURI).pathname.split('/').pop());
		} catch (error) {
			return address;
		}
	};

	window.MediaPreview = {
		open(address, listDocument) {
			const CMS = window.CMS;
			const options = (CMS && CMS.options.get('com_media.preview')) || {};
			const icons = options.icons || {};
			const links = listDocument ? Array.prototype.map.call(listDocument.querySelectorAll('a.img-preview[href]'), (link) => link.href) : [];
			const images = links.filter((link, index) => links.indexOf(link) === index);
			const absolute = new URL(address, listDocument ? listDocument.baseURI : document.baseURI).href;
			let current = Math.max(0, images.indexOf(absolute));

			if (!images.length) {
				images.push(absolute);
			}

			const root = document.createElement('div');
			const stage = document.createElement('div');
			const image = document.createElement('img');
			const bar = document.createElement('div');
			const name = document.createElement('span');
			const position = document.createElement('span');
			const size = document.createElement('span');
			const rtl = window.getComputedStyle(document.documentElement).direction === 'rtl';
			const sizes = {};
			const arrow = (step, key, fallback, icon) => {
				const button = document.createElement('button');

				button.type = 'button';
				button.className = 'media-preview-arrow media-preview-' + (step < 0 ? 'previous' : 'next');
				button.title = text(key, fallback);
				button.setAttribute('aria-label', button.title);
				button.innerHTML = icon || (step < 0 ? '&lsaquo;' : '&rsaquo;');
				button.addEventListener('click', () => go(step));

				return button;
			};

			root.className = 'media-preview';
			stage.className = 'media-preview-stage';
			bar.className = 'media-preview-bar';
			name.className = 'media-preview-name';
			position.className = 'media-preview-position';
			size.className = 'media-preview-size';
			size.setAttribute('aria-live', 'polite');
			stage.appendChild(image);

			if (images.length > 1) {
				stage.appendChild(arrow(-1, 'COM_MEDIA_PREVIEW_PREVIOUS', 'Previous image', icons.previous));
				stage.appendChild(arrow(1, 'COM_MEDIA_PREVIEW_NEXT', 'Next image', icons.next));
			}

			bar.appendChild(name);
			bar.appendChild(position);
			bar.appendChild(size);
			root.appendChild(stage);
			root.appendChild(bar);

			// The file's size, asked of the server once per image
			const fileSize = (url) => {
				if (sizes[url] === undefined) {
					sizes[url] = fetch(url, { method: 'HEAD', credentials: 'same-origin' })
						.then((response) => parseInt(response.headers.get('Content-Length'), 10) || 0)
						.catch(() => 0);
				}

				return sizes[url];
			};

			const show = () => {
				const url = images[current];
				const shown = current;

				name.textContent = nameOf(url);
				name.title = name.textContent;
				position.textContent = images.length > 1 ? text('COM_MEDIA_PREVIEW_POSITION', '%1$s of %2$s').replace('%1$s', current + 1).replace('%2$s', images.length) : '';
				size.textContent = '';
				image.alt = name.textContent;
				image.src = url;

				image.decode().catch(() => null).then(() => fileSize(url)).then((length) => {
					if (shown === current) {
						size.textContent = image.naturalWidth + ' × ' + image.naturalHeight + (length ? ' · ' + bytes(length) : '');
					}
				});

				// The neighbours, ready for the arrows
				[-1, 1].forEach((step) => {
					if (images.length > 1) {
						new Image().src = images[(current + step + images.length) % images.length];
					}
				});
			};

			const go = (step) => {
				current = (current + step + images.length) % images.length;
				show();
			};

			const onKey = (event) => {
				if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
					return;
				}

				event.preventDefault();
				go((event.key === 'ArrowRight') !== rtl ? 1 : -1);
			};

			CMS.modal.open(root, {
				handler: 'adopt',
				size: { x: Math.min(1600, window.innerWidth - 80), y: Math.min(1000, window.innerHeight - 100) },
				classWindow: 'media-preview-window',
				onClose() {
					document.removeEventListener('keydown', onKey);
				},
			});

			if (images.length > 1) {
				document.addEventListener('keydown', onKey);
			}

			show();
		},
	};
})();

/* --- The list (the Media Manager's frame) --- */
/*
 * Its checkboxes belong to #mediamanager-form, not adminForm; its previews open in the parent's windows: images in the
 * CMS's modal (MediaPreview, with the folder's other images), audio and video in the Media Manager's players.
 */
(() => {
	'use strict';

	const start = () => {
		const form = document.getElementById('mediamanager-form');

		if (!form || window.parent === window || !window.Joomla) {
			return;
		}

		const parent = window.parent;

		window.Joomla.isChecked = (checked, list) => {
			const target = list || form;
			const all = target.elements['checkall-toggle'];

			target.boxchecked.value = Number(target.boxchecked.value) + (checked ? 1 : -1);

			if (all) {
				all.checked = !Array.prototype.some.call(target.elements, (element) => element.type === 'checkbox' && element !== all && !element.checked);
			}
		};

		if (parent.document.updateUploader) {
			parent.document.updateUploader();
		}

		document.addEventListener('click', (event) => {
			const link = event.target.closest ? event.target.closest('.audio-preview, .video-preview, .img-preview, .preview') : null;

			if (!link) {
				return;
			}

			event.preventDefault();

			const href = link.getAttribute('href');
			const cover = link.getAttribute('data-cover') || '';

			if (link.classList.contains('audio-preview') || link.classList.contains('video-preview')) {
				const audio = link.classList.contains('audio-preview');

				if (parent.CMS && parent.CMS.bootstrap) {
					parent.CMS.bootstrap.call('modal', audio ? '#audioPreview' : '#videoPreview', 'show');
				}

				parent.MediaManager[audio ? 'playAudio' : 'playVideo'](href, cover);

				return;
			}

			if (parent.MediaPreview) {
				parent.MediaPreview.open(href, document);
			} else {
				parent.CMS.modal.open(href, { handler: 'image' });
			}

			// Focus leaves the list's frame for the modal, so Escape closes it
			const close = parent.document.getElementById('sbox-btn-close');

			if (close) {
				close.focus({ focusVisible: false });
			}
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
