/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

((window, document, Joomla) => {
	'use strict';

	if (!Joomla || !window.tinymce) {
		return;
	}

	const tinymce = window.tinymce;
	const text = (key, fallback) => (Joomla.JText && Joomla.JText._(key, fallback)) || fallback;

	// Older extensions insert through this
	window.jInsertEditorText = (content, editor) => {
		if (Joomla.editors.instances[editor]) {
			Joomla.editors.instances[editor].replaceSelection(content);
		}
	};

	/**
	 * Insert template: a dialog with the HTML templates of the templates folder and a preview
	 */
	const addTemplates = (editor, templates) => {
		const cache = {};
		const load = (url) => {
			if (!cache[url]) {
				cache[url] = fetch(url, { credentials: 'same-origin' }).then((response) => (response.ok ? response.text() : ''));
			}

			return cache[url];
		};

		const open = () => {
			if (!templates.length) {
				editor.windowManager.alert(text('PLG_TINYMCE_LATEST_TEMPLATE_NONE', 'There are no templates.'));

				return;
			}

			const items = templates.map((template, index) => ({ text: template.title, value: String(index) }));
			let html = '';

			const preview = (api, index) => {
				const template = templates[Number(index)];

				load(template.url).then((content) => {
					const stylesheets = [].concat(editor.options.get('content_css') || [])
						.filter((url) => /[/.]/.test(url))
						.map((url) => `<link rel="stylesheet" href="${editor.documentBaseURI.toAbsolute(url)}">`)
						.join('');

					html = content;
					api.setData({
						template: String(index),
						preview: `<!DOCTYPE html><html><head><base href="${editor.documentBaseURI.getURI()}">${stylesheets}</head>`
							+ `<body style="margin: 1rem">${content}</body></html>`,
					});
				});
			};

			const dialog = editor.windowManager.open({
				title: text('PLG_TINYMCE_LATEST_TEMPLATE_DIALOG_TITLE', 'Insert template'),
				size: 'large',
				body: {
					type: 'panel',
					items: [
						{ type: 'listbox', name: 'template', label: text('PLG_TINYMCE_LATEST_TEMPLATE_LABEL', 'Template'), items },
						{ type: 'iframe', name: 'preview', label: text('PLG_TINYMCE_LATEST_TEMPLATE_PREVIEW', 'Preview'), sandboxed: true },
					],
				},
				initialData: { template: '0', preview: '' },
				buttons: [
					{ type: 'cancel', text: 'Cancel' },
					{ type: 'submit', text: 'Insert', primary: true },
				],
				onChange: (api, details) => {
					if (details.name === 'template') {
						preview(api, api.getData().template);
					}
				},
				onSubmit: (api) => {
					editor.insertContent(html);
					api.close();
				},
			});

			preview(dialog, '0');
		};

		editor.ui.registry.addButton('jtemplate', {
			icon: 'template',
			tooltip: text('PLG_TINYMCE_LATEST_TEMPLATE_DIALOG_TITLE', 'Insert template'),
			onAction: open,
		});
		editor.ui.registry.addMenuItem('jtemplate', {
			icon: 'template',
			text: text('PLG_TINYMCE_LATEST_TEMPLATE_DIALOG_TITLE', 'Insert template'),
			onAction: open,
		});
	};

	/**
	 * Images dropped, pasted or chosen in the image dialog go to the images folder through the Media Manager
	 */
	const uploadHandler = (upload, sources, uploaded) => (blobInfo, progress) => new Promise((resolve, reject) => {
		const data = new FormData();
		const xhr = new XMLHttpRequest();

		// Pasted images are named blobid0.png, blobid1.png, ... on every page, which would clash with earlier ones
		let filename = blobInfo.filename();

		if (/^blobid\d+\./.test(filename)) {
			filename = `image-${new Date().toISOString().replace(/\D/g, '').slice(0, 14)}-${Math.random().toString(36).slice(2, 7)}${filename.slice(filename.lastIndexOf('.'))}`;
		}

		data.append('Filedata', blobInfo.blob(), filename);
		data.append('folder', upload.folder);
		data.append(upload.token, '1');

		xhr.open('POST', upload.url);
		xhr.upload.onprogress = (event) => progress(event.loaded / event.total * 100);
		xhr.onerror = () => reject({ message: text('PLG_TINYMCE_LATEST_UPLOAD_FAILED', 'The image couldn\'t be uploaded.'), remove: true });
		xhr.onload = () => {
			let response = null;

			try {
				response = JSON.parse(xhr.responseText);
			} catch (error) {
				response = null;
			}

			if (xhr.status === 200 && response && String(response.status) === '1' && response.location) {
				if (Array.isArray(response.srcset) && response.srcset.length > 1) {
					sources.set(upload.root + response.location, response.srcset.map((source) => ({ url: upload.root + source.location, width: parseInt(source.width, 10) })));
				}

				resolve(upload.root + response.location);
				uploaded(upload.root + response.location, response);
			} else {
				reject({
					message: (response && (response.message || response.error)) || text('PLG_TINYMCE_LATEST_UPLOAD_FAILED', 'The image couldn\'t be uploaded.'),
					remove: true,
				});
			}
		};

		xhr.send(data);
	});

	/**
	 * Images uploaded with a copy for small screens (the Media Manager's Small Screen Copies) get a srcset, and sizes up to the
	 * image's own width, so phones load the copy whatever the template. Such a srcset is dropped when the image's address
	 * changes (another image chosen in the dialog), or it would keep showing the old image.
	 */
	const pathOf = (editor, url) => {
		try {
			return new URL(url, editor.getDoc().baseURI).pathname;
		} catch (error) {
			return '';
		}
	};

	/**
	 * TinyMCE gives a dropped or pasted image the size of the file it read; the Media Manager may have stored it narrower
	 */
	const fixSize = (editor, url, response) => {
		const width = parseInt(response.width, 10);
		const height = parseInt(response.height, 10);

		if (!width || !height) {
			return;
		}

		editor.dom.select('img[width]').forEach((image) => {
			if (pathOf(editor, image.getAttribute('src') || '') === pathOf(editor, url) && parseInt(image.getAttribute('width'), 10) > width) {
				editor.dom.setAttribs(image, { width, height });
			}
		});
	};

	const applySources = (editor, sources) => {
		const uploaded = new Map();

		sources.forEach((list, url) => uploaded.set(pathOf(editor, url), list));

		editor.dom.select('img').forEach((image) => {
			const src = pathOf(editor, image.getAttribute('data-mce-src') || image.getAttribute('src') || '');
			const srcset = image.getAttribute('srcset');

			if (srcset) {
				const candidates = srcset.split(',').map((candidate) => pathOf(editor, candidate.trim().split(/\s+/)[0]));

				if (candidates.length === 2 && candidates[0].replace(/-\d+(\.[^./]+)$/, '$1') === candidates[1] && candidates[1] !== src) {
					editor.dom.setAttribs(image, { srcset: null, sizes: null });
				}

				return;
			}

			const list = uploaded.get(src);

			if (list) {
				const width = list[list.length - 1].width;

				editor.dom.setAttribs(image, {
					srcset: list.map((source) => `${editor.convertURL(source.url, 'src', image)} ${source.width}w`).join(', '),
					sizes: `(max-width: ${width}px) 100vw, ${width}px`,
				});
			}
		});
	};

	/*
	 * When TinyMCE's iframe is moved in the page (e.g. by sorting subform rows) the browser reloads it empty: start the editor
	 * again. The iframe's first load can come before or after TinyMCE's "init", so the listener goes on after both.
	 */
	/*
	 * PDF files ({pdf images/x.pdf}, shown by "Content - PDF"): a block with the file's first page in the editing area, the
	 * shortcode in the content. The page is a background, not an <img>: TinyMCE uploads data: images in the content.
	 */
	const PDF_PARAGRAPH = /<p>\s*(?:&nbsp;\s*)*\{pdf\s+([^{}<>"]+?)\s*\}\s*(?:&nbsp;\s*)*<\/p>/gi;
	const PDF_ALONE = /^\s*\{pdf\s+([^{}<>"]+?)\s*\}\s*$/i;
	const PDF_BLOCK = /<div\b[^>]*\bcms-pdf-editor\b[^>]*>[\s\S]*?<\/div>/gi;
	const PDF_CSS = '.cms-pdf-editor {position:relative;display:block;box-sizing:border-box;max-width:320px;min-height:140px;margin:1em 0;border:1px solid rgba(128, 128, 128, 0.5);'
		+ 'border-radius:4px;background:#fff center top / 100% auto no-repeat;cursor:default;}'
		+ '.cms-pdf-editor[data-mce-selected] {outline:2px solid #3b82f6;}'
		+ '.cms-pdf-editor-name {position:absolute;right:0;bottom:0;left:0;padding:4px 8px;background:rgba(0, 0, 0, 0.65);color:#fff;font:12px/1.4 system-ui, sans-serif;}';

	const escapeAttribute = (value) => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

	const pdfBlock = (path) => '<div class="cms-pdf-editor mceNonEditable" contenteditable="false" data-pdf="' + escapeAttribute(path) + '">'
		+ '<span class="cms-pdf-editor-name">' + escapeAttribute(path.split('/').pop()) + '</span></div>';

	const toPdfBlocks = (html) => {
		const alone = PDF_ALONE.exec(html);

		return alone ? pdfBlock(alone[1]) : html.replace(PDF_PARAGRAPH, (all, path) => pdfBlock(path));
	};

	const toPdfShortcodes = (html) => (html.indexOf('cms-pdf-editor') === -1 ? html : html.replace(PDF_BLOCK, (block) => {
		const path = /\bdata-pdf="([^"]*)"/.exec(block);
		const value = path ? path[1].replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&') : '';

		return value === '' ? '' : '<p>{pdf ' + escapeAttribute(value).replace(/&quot;/g, '') + '}</p>';
	}));

	const drawPdfBlocks = (editor) => {
		if (!window.CMS || !window.CMS.pdfViewer || !editor.getBody()) {
			return;
		}

		editor.getBody().querySelectorAll('div.cms-pdf-editor[data-pdf]:not([data-pdf-drawn])').forEach((block) => {
			block.setAttribute('data-pdf-drawn', '1');

			window.CMS.pdfViewer.thumbnail(block.getAttribute('data-pdf'), 320).then((result) => {
				block.style.backgroundImage = 'url(' + result.canvas.toDataURL('image/png') + ')';
				block.style.aspectRatio = result.canvas.width + ' / ' + result.canvas.height;
			}).catch(() => {});
		});
	};

	const watchIframe = (editor, element) => {
		let pending = 2;
		let timer = null;

		const restart = () => {
			clearTimeout(timer);
			timer = setTimeout(() => {
				const content = editor.getContent();

				editor.remove();
				element.value = content;
				Joomla.JoomlaTinyMCELatest.setupEditor(element);
			}, 300);
		};

		const ready = () => {
			pending -= 1;

			const iframe = !pending && editor.getContentAreaContainer() ? editor.getContentAreaContainer().querySelector('iframe') : null;

			if (!iframe) {
				return;
			}

			if (iframe.contentDocument && iframe.contentDocument.readyState !== 'complete') {
				iframe.addEventListener('load', () => iframe.addEventListener('load', restart), { once: true });
			} else {
				iframe.addEventListener('load', restart);
			}
		};

		if (editor.inline) {
			return;
		}

		editor.on('load', ready);
		editor.on('init', ready);
	};

	Joomla.JoomlaTinyMCELatest = {
		/**
		 * Start the editors in a part of the page
		 */
		setupEditors(target) {
			(target || document).querySelectorAll('.js-editor-tinymce-latest textarea').forEach((element) => this.setupEditor(element));
		},

		/**
		 * Start one editor, with the common options merged with its own
		 */
		setupEditor(element) {
			if (!element || !element.id) {
				return;
			}

			const pluginOptions = Joomla.getOptions('plg_editor_tinymce_latest', {}).tinyMCE || {};
			const name = (element.getAttribute('name') || '').replace(/\[\]|\]/g, '').split('[').pop();
			const defaults = pluginOptions.default || {};
			const own = pluginOptions[name] || {};
			const options = JSON.parse(JSON.stringify(Object.assign({}, defaults, own.joomlaMergeDefaults ? own : {})));
			const joomla = Object.assign({}, defaults.joomla || {}, own.joomla || {});

			delete options.joomla;
			delete options.joomlaMergeDefaults;

			const existing = tinymce.get(element.id);

			if (existing) {
				existing.remove();
			}

			options.target = element;
			options.base_url = joomla.baseURL;
			options.suffix = '.min';

			const sources = new Map();

			if (joomla.upload) {
				// TinyMCE puts the uploaded address in once the promise has settled
				options.images_upload_handler = uploadHandler(joomla.upload, sources, (url, response) => window.setTimeout(() => {
					const editor = tinymce.get(element.id);

					if (editor) {
						fixSize(editor, url, response);
						applySources(editor, sources);
					}
				}, 0));
			}

			options.setup = (editor) => {
				if (joomla.templates) {
					addTemplates(editor, joomla.templates);
				}

				editor.on('init', () => {
					if (joomla.readonly) {
						editor.mode.set('readonly');
					}

					editor.dom.addStyle(PDF_CSS);
					drawPdfBlocks(editor);
				});

				editor.on('BeforeSetContent', (event) => {
					if (typeof event.content === 'string' && event.content.indexOf('{pdf') !== -1) {
						event.content = toPdfBlocks(event.content);
					}
				});
				editor.on('SetContent', () => drawPdfBlocks(editor));
				editor.on('PostProcess', (event) => {
					if (typeof event.content === 'string') {
						event.content = toPdfShortcodes(event.content);
					}
				});

				// A hidden editor holds the content in its textarea: show it again so the form gets it
				if (element.form) {
					element.form.addEventListener('submit', () => {
						if (editor.isHidden()) {
							editor.show();
						}
					}, true);
				}

				editor.on('BeforeGetContent', () => applySources(editor, sources));

				watchIframe(editor, element);
			};

			// A page shown dark (data-scheme on <html>, as the Prothero administrator template writes it: dark, or auto and a device set
			// to dark) gets the dark twin of a light skin; the content keeps its own stylesheet (the site template's editor.css)
			const darkSkins = { oxide: 'oxide-dark', 'tinymce-5': 'tinymce-5-dark' };
			const scheme = document.documentElement.getAttribute('data-scheme');
			const dark = scheme === 'dark' || (scheme === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);

			if (dark && darkSkins[options.skin || 'oxide']) {
				options.skin = darkSkins[options.skin || 'oxide'];
			}

			// Its sticky toolbar stops under what stays at the top of the administrator (Isis' and Prothero's top bar, and their
			// pinned toolbar when that's on), instead of sliding under it
			if (options.toolbar_sticky && options.toolbar_sticky_offset === undefined) {
				const fixedHeight = (el) => (el && ['fixed', 'sticky'].indexOf(getComputedStyle(el).position) !== -1 ? el.offsetHeight : 0);
				const pinned = document.getElementById('isisJsData');
				let offset = fixedHeight(document.querySelector('nav.navbar-fixed-top'));

				if (pinned && pinned.getAttribute('data-tmpl-sticky') === 'true' && window.innerWidth > 767) {
					const subhead = document.querySelector('.subhead');

					offset += subhead ? subhead.offsetHeight : 0;
				}

				options.toolbar_sticky_offset = offset;
			}

			tinymce.init(options);

			// Until TinyMCE has started, the textarea holds the content
			Joomla.editors.instances[element.id] = {
				id: element.id,
				get instance() {
					return tinymce.get(element.id);
				},
				getValue() {
					return this.instance ? this.instance.getContent() : element.value;
				},
				setValue(value) {
					if (this.instance) {
						this.instance.setContent(value);
					} else {
						element.value = value;
					}
				},
				getSelection() {
					return this.instance ? this.instance.selection.getContent({ format: 'text' }) : '';
				},
				replaceSelection(value) {
					if (this.instance) {
						this.instance.execCommand('mceInsertContent', false, value);
					} else {
						element.value += value;
					}
				},
				onSave() {
					if (this.instance && this.instance.isHidden()) {
						this.instance.show();
					}

					return '';
				},
			};
		},
	};

	document.addEventListener('DOMContentLoaded', () => {
		Joomla.JoomlaTinyMCELatest.setupEditors(document);

		// Toggle editor
		document.addEventListener('click', (event) => {
			const button = event.target instanceof Element ? event.target.closest('.js-tinymce-latest-toggle') : null;
			const editor = button ? tinymce.get(button.getAttribute('data-editor')) : null;

			if (!editor) {
				return;
			}

			event.preventDefault();

			if (editor.isHidden()) {
				editor.show();
			} else {
				editor.hide();
			}

			const icon = button.querySelector('[class^="icon-"]');

			if (icon) {
				icon.className = editor.isHidden() ? 'icon-eye-close' : 'icon-eye';
			}
		});

		// Subform rows are added with jQuery's events, which only jQuery can listen to
		if (window.jQuery) {
			window.jQuery(document).on('subform-row-add', (event, row) => Joomla.JoomlaTinyMCELatest.setupEditors(row));
		}
	});
})(window, document, window.Joomla);
