/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * TinyMCE 8 for Joomla 3: starts the editors and connects them to Joomla's editor API (Joomla.editors.instances), the editor
 * buttons (the "CMS Content" menu), the HTML templates and image uploads through the Media Manager.
 */
((window, document, Joomla) => {
	'use strict';

	if (!Joomla || !window.tinymce) {
		return;
	}

	const tinymce = window.tinymce;
	const text = (key, fallback) => (Joomla.JText && Joomla.JText._(key, fallback)) || fallback;

	// The dialog an editor button opened, so its page can close it (jModalClose(), SqueezeBox.close())
	let openDialog = null;

	const closeDialog = () => {
		if (openDialog) {
			const dialog = openDialog;

			openDialog = null;
			dialog.close();
		}
	};

	// Older extensions insert through this
	window.jInsertEditorText = (content, editor) => {
		if (Joomla.editors.instances[editor]) {
			Joomla.editors.instances[editor].replaceSelection(content);
		}
	};

	const joomlaIcon = '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M8.3 8.6c1-1 2.7-1 3.7 0l.3.3 3.1-3.2-.2-.2a7 7 0 0 0-6.6-1.9 4.3 4.3 0 1 0-4.9 4.8 7.3 7.3 0 0 0 1.8 7l7.1 7.2 3.2-3.2-7.1-7.1a2.6 2.6 0 0 1 0-3.7Zm23.7-4.3a4.3 4.3 0 0 0-8.5-.6 7.2 7.2 0 0 0-7 1.8l-7.1 7.1 3.1 3.2 7.2-7.1a2.6 2.6 0 0 1 3.7 3.7l-.3.3 3.2 3.1.2-.2a7 7 0 0 0 1.8-7 4.3 4.3 0 0 0 3.7-4.3Zm-3.7 19.2a7.2 7.2 0 0 0-1.9-6.7l-7.1-7.1-3.2 3.1 7.1 7.2a2.6 2.6 0 0 1-3.7 3.7l-.2-.3-3.2 3.2.3.2a7.2 7.2 0 0 0 7.1 1.8 4.3 4.3 0 1 0 4.8-5.1Zm-9.2-7-7.1 7.2a2.6 2.6 0 0 1-3.7-3.7l.2-.3-3.1-3.1-.3.2a7.2 7.2 0 0 0-1.8 6.8 4.3 4.3 0 1 0 5.2 5 7.2 7.2 0 0 0 6.7-1.8l7.1-7.1-3.2-3.2Z"/></svg>';

	/**
	 * The editor buttons (editors-xtd plugins) as a menu: a button with a page opens it in a dialog, the others run their script
	 */
	const addEditorButtons = (editor, buttons) => {
		editor.ui.registry.addIcon('joomla', joomlaIcon);
		editor.ui.registry.addMenuButton('jxtdbuttons', {
			text: text('PLG_TINYMCE_LATEST_CMS_CONTENT', 'CMS Content'),
			icon: 'joomla',
			fetch: (callback) => {
				callback(buttons.map((button) => ({
					type: 'menuitem',
					text: button.text,
					onAction: () => {
						if (button.url) {
							openDialog = editor.windowManager.openUrl({
								title: button.text,
								url: button.url,
								width: button.width || 800,
								height: button.height || 500,
								buttons: [{ type: 'cancel', text: 'Close' }],
								onClose: () => {
									openDialog = null;
								},
							});
						}

						if (button.onclick) {
							// The editor buttons' own scripts, as plg_editors_tinymce runs them
							new Function(button.onclick).call(editor.getElement()); // eslint-disable-line no-new-func
						}
					},
				})));
			},
		});
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
	const uploadHandler = (upload) => (blobInfo, progress) => new Promise((resolve, reject) => {
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
				resolve(upload.root + response.location);
			} else {
				reject({
					message: (response && (response.message || response.error)) || text('PLG_TINYMCE_LATEST_UPLOAD_FAILED', 'The image couldn\'t be uploaded.'),
					remove: true,
				});
			}
		};

		xhr.send(data);
	});

	/*
	 * When TinyMCE's iframe is moved in the page (e.g. by sorting subform rows) the browser reloads it empty: start the editor
	 * again. The iframe's first load can come before or after TinyMCE's "init", so the listener goes on after both.
	 */
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

			if (joomla.upload) {
				options.images_upload_handler = uploadHandler(joomla.upload);
			}

			options.setup = (editor) => {
				addEditorButtons(editor, joomla.buttons || []);

				if (joomla.templates) {
					addTemplates(editor, joomla.templates);
				}

				editor.on('init', () => {
					if (joomla.readonly) {
						editor.mode.set('readonly');
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

				watchIframe(editor, element);
			};

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

		// Editor buttons' pages close their modal with these: close the dialog too
		const modalClose = typeof window.jModalClose === 'function' ? window.jModalClose : null;

		window.jModalClose = function jModalClose(...args) {
			if (modalClose) {
				modalClose.apply(this, args);
			}

			closeDialog();
		};

		window.SqueezeBox = window.SqueezeBox || {};
		const squeezeBoxClose = typeof window.SqueezeBox.close === 'function' ? window.SqueezeBox.close : null;

		window.SqueezeBox.close = function close(...args) {
			if (squeezeBoxClose) {
				squeezeBoxClose.apply(this, args);
			}

			closeDialog();
		};

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
