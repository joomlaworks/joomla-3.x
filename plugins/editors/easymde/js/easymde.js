/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

((window, document, Joomla) => {
	'use strict';

	if (!Joomla || !window.EasyMDE || !window.JoomlaEasyMDE || !window.JoomlaEasyMDE.convert) {
		return;
	}

	const { EasyMDE } = window;
	const { toMarkdown, toHtml } = window.JoomlaEasyMDE.convert;
	const text = (key, fallback) => {
		const value = Joomla.JText ? Joomla.JText._(`PLG_EDITORS_EASYMDE_${key}`, fallback) : fallback;

		return value === `PLG_EDITORS_EASYMDE_${key}` ? fallback : value;
	};

	// Older extensions insert through this
	window.jInsertEditorText = (content, editor) => {
		if (Joomla.editors.instances[editor]) {
			Joomla.editors.instances[editor].replaceSelection(content);
		}
	};

	const BLOCK = /^(address|article|aside|audio|blockquote|div|dl|figure|footer|form|h[1-6]|header|hr|iframe|ol|p|pre|section|table|ul|video)$/i;

	// HTML from the editor buttons (read more, image, article link...) goes in as Markdown; blocks on lines of their own
	const insert = (cm, html, sources) => {
		const doc = new window.DOMParser().parseFromString(`<body>${html}</body>`, 'text/html');
		const markdown = toMarkdown(html, sources);
		const block = Array.prototype.some.call(doc.body.children, (node) => BLOCK.test(node.nodeName));

		// Inline, into the text: an element which stays HTML needs no line of its own (see convert.js' standsAlone)
		if (!block) {
			cm.replaceSelection(markdown.trim().replace(/^(<[^>]+>)\n/, '$1'));

			return;
		}

		const cursor = cm.getCursor('from');
		const before = cm.getLine(cursor.line).slice(0, cursor.ch).trim() !== '' ? '\n\n' : '';

		cm.replaceSelection(`${before}${markdown}\n\n`);
	};

	// Relative addresses (images/uploads/x.png) are the site's: in the administrator, the preview needs them from the root
	const previewHtml = (markdown, root, sources) => {
		const doc = new window.DOMParser().parseFromString(`<body>${toHtml(markdown, sources)}</body>`, 'text/html');
		const relative = (value) => value && !/^([a-z][a-z0-9+.-]*:|\/|#|\?)/i.test(value);

		doc.body.querySelectorAll('[src], [href], [poster]').forEach((node) => {
			['src', 'href', 'poster'].forEach((name) => {
				const value = node.getAttribute(name);

				if (relative(value)) {
					node.setAttribute(name, `${root}/${value}`);
				}
			});
		});

		doc.body.querySelectorAll('img[srcset]').forEach((node) => {
			node.setAttribute('srcset', node.getAttribute('srcset').split(',').map((candidate) => {
				const parts = candidate.trim().split(/\s+/);

				return (relative(parts[0]) ? `${root}/${parts[0]}` : parts[0]) + (parts[1] ? ` ${parts[1]}` : '');
			}).join(', '));
		});

		return doc.body.innerHTML;
	};

	// The Media Manager refuses names with spaces or unsafe characters; pasted images are all called image.png
	const safeName = (file) => {
		const name = file.name || 'file';
		const dot = name.lastIndexOf('.');
		const extension = dot > 0 ? name.slice(dot + 1).toLowerCase().replace(/[^a-z0-9]/g, '') : '';
		let base = (dot > 0 ? name.slice(0, dot) : name).normalize('NFKD').replace(/[̀-ͯ]/g, '')
			.replace(/[^A-Za-z0-9_-]+/g, '-').replace(/-+/g, '-').replace(/^[-_]+|[-_]+$/g, '');

		if (base === '' || /^image$/i.test(base)) {
			base = `${base || 'file'}-${new Date().toISOString().replace(/\D/g, '').slice(0, 14)}`;
		}

		return extension ? `${base}.${extension}` : base;
	};

	const markup = (file, path) => {
		const label = file.name.replace(/\.[^.]+$/, '').replace(/[[\]]/g, '');

		if (/^image\//.test(file.type)) {
			return `![${label}](${path})`;
		}

		if (/^video\//.test(file.type)) {
			return `<video src="${path}" controls></video>`;
		}

		if (/^audio\//.test(file.type)) {
			return `<audio src="${path}" controls></audio>`;
		}

		return `[${file.name.replace(/[[\]]/g, '')}](${path})`;
	};

	const send = (upload, file, name) => {
		const data = new FormData();

		data.append('Filedata', file, name);
		data.append('folder', upload.folder);
		data.append(upload.token, '1');

		return fetch(upload.url, { method: 'POST', body: data, credentials: 'same-origin' })
			.then((response) => response.text().then((body) => {
				try {
					return { http: response.status, response: JSON.parse(body) };
				} catch (error) {
					return { http: response.status, response: null };
				}
			}))
			.catch(() => null);
	};

	// One file: a placeholder at the cursor ("![Uploading photo.jpg…]()"), then its link, image or player
	const uploadFile = (cm, upload, file, after, sources) => {
		const name = safeName(file);
		const placeholder = `![${text('UPLOADING', 'Uploading')} ${name}…]()`;

		cm.replaceSelection(placeholder + (after || ''));

		const replace = (value) => {
			const index = cm.getValue().indexOf(placeholder);

			if (index !== -1) {
				cm.replaceRange(value, cm.posFromIndex(index), cm.posFromIndex(index + placeholder.length));
			}
		};
		const fail = (message) => {
			replace('');
			Joomla.renderMessages({ error: [`${name}: ${message || text('UPLOAD_FAILED', 'The file couldn\'t be uploaded.')}`] });
		};

		send(upload, file, name).then((result) => {
			const response = result && result.response;

			// A file of the same name is kept: the Media Manager adds the time to this one's name
			if (result && result.http === 200 && response && String(response.status) === '1' && response.location) {
				const path = String(response.location).replace(/^\/+/, '');

				// With a copy for small screens (the Media Manager's Small Screen Copies), the image gets a srcset in the HTML
				if (/^image\//.test(file.type) && Array.isArray(response.srcset) && response.srcset.length > 1) {
					const width = parseInt(response.srcset[response.srcset.length - 1].width, 10);

					sources.set(path, {
						srcset: response.srcset.map((source) => `${String(source.location).replace(/^\/+/, '')} ${parseInt(source.width, 10)}w`).join(', '),
						sizes: `(max-width: ${width}px) 100vw, ${width}px`,
					});
				}

				replace(markup(file, path));
			} else {
				fail(response && (response.message || response.error));
			}
		});
	};

	const uploadFiles = (cm, upload, files, after, sources) => {
		Array.prototype.forEach.call(files, (file) => uploadFile(cm, upload, file, after, sources));
	};

	const icon = (name) => `easymde-icon easymde-icon-${name}`;

	const toolbar = (options, sources) => {
		const items = [
			{ name: 'bold', action: EasyMDE.toggleBold, className: icon('bold'), title: text('BOLD', 'Bold') },
			{ name: 'italic', action: EasyMDE.toggleItalic, className: icon('italic'), title: text('ITALIC', 'Italic') },
			{ name: 'strikethrough', action: EasyMDE.toggleStrikethrough, className: icon('strikethrough'), title: text('STRIKETHROUGH', 'Strikethrough') },
			{ name: 'heading', action: EasyMDE.toggleHeadingSmaller, className: icon('heading'), title: text('HEADING', 'Heading') },
			'|',
			{ name: 'quote', action: EasyMDE.toggleBlockquote, className: icon('quote'), title: text('QUOTE', 'Quote') },
			{ name: 'unordered-list', action: EasyMDE.toggleUnorderedList, className: icon('unordered-list'), title: text('UNORDERED_LIST', 'Bulleted list') },
			{ name: 'ordered-list', action: EasyMDE.toggleOrderedList, className: icon('ordered-list'), title: text('ORDERED_LIST', 'Numbered list') },
			{ name: 'code', action: EasyMDE.toggleCodeBlock, className: icon('code'), title: text('CODE', 'Code') },
			'|',
			{ name: 'link', action: EasyMDE.drawLink, className: icon('link'), title: text('LINK', 'Link') },
			{ name: 'image', action: EasyMDE.drawImage, className: icon('image'), title: text('IMAGE', 'Image') },
		];

		if (options.upload) {
			items.push({
				name: 'upload-file',
				action: (editor) => {
					const input = document.createElement('input');

					input.type = 'file';
					input.multiple = true;
					input.addEventListener('change', () => uploadFiles(editor.codemirror, options.upload, input.files, '', sources));
					input.click();
				},
				className: icon('upload'),
				title: text('UPLOAD', 'Upload files'),
			});
		}

		return items.concat([
			{ name: 'table', action: EasyMDE.drawTable, className: icon('table'), title: text('TABLE', 'Table') },
			{ name: 'horizontal-rule', action: EasyMDE.drawHorizontalRule, className: icon('horizontal-rule'), title: text('HORIZONTAL_RULE', 'Horizontal line') },
			'|',
			{ name: 'preview', action: EasyMDE.togglePreview, className: `${icon('preview')} no-disable`, title: text('PREVIEW', 'Preview'), noDisable: true },
			{
				name: 'side-by-side', action: EasyMDE.toggleSideBySide, className: `${icon('side-by-side')} no-disable no-mobile`, title: text('SIDE_BY_SIDE', 'Side by side'), noDisable: true, noMobile: true,
				icon: `<span class="easymde-label">${text('SIDE_BY_SIDE_LABEL', 'Side-by-side')}</span>`,
			},
			{ name: 'fullscreen', action: EasyMDE.toggleFullScreen, className: `${icon('fullscreen')} no-disable no-mobile`, title: text('FULLSCREEN', 'Full screen'), noDisable: true, noMobile: true },
			'|',
			{ name: 'undo', action: EasyMDE.undo, className: `${icon('undo')} no-disable`, title: text('UNDO', 'Undo'), noDisable: true },
			{ name: 'redo', action: EasyMDE.redo, className: `${icon('redo')} no-disable`, title: text('REDO', 'Redo'), noDisable: true },
			'|',
			{ name: 'guide', action: options.guide, className: `${icon('guide')} no-disable`, title: text('GUIDE', 'Markdown guide'), noDisable: true },
		]);
	};

	const setup = (element) => {
		if (element.getAttribute('data-easymde-ready') || !element.id) {
			return;
		}

		let options = {};

		try {
			options = JSON.parse(element.getAttribute('data-easymde') || '{}');
		} catch (error) {
			options = {};
		}

		element.setAttribute('data-easymde-ready', '1');

		// The form's textarea keeps the HTML; the editor works on a copy holding the Markdown
		// srcset and sizes of the images, which Markdown can't hold (see convert.js)
		const sources = new Map();
		let html = element.value;
		let markdown = toMarkdown(html, sources);
		const area = document.createElement('textarea');

		area.className = 'joomla-editor-easymde-markdown';
		area.value = markdown;
		element.style.display = 'none';
		element.parentNode.insertBefore(area, element.nextSibling);

		const words = (value) => (value.match(/[\p{L}\p{N}][\p{L}\p{N}'’-]*/gu) || []).length;
		const statusbar = [
			{ className: 'easymde-words', defaultValue: (el) => { el.textContent = `${text('WORDS', 'Words')}: ${words(markdown)}`; }, onUpdate: (el) => { el.textContent = `${text('WORDS', 'Words')}: ${words(editor.value())}`; } },
			{ className: 'easymde-lines', defaultValue: (el) => { el.textContent = `${text('LINES', 'Lines')}: ${markdown.split('\n').length}`; }, onUpdate: (el) => { el.textContent = `${text('LINES', 'Lines')}: ${editor.codemirror.lineCount()}`; } },
			'cursor',
		];

		if (options.upload) {
			statusbar.unshift({ className: 'easymde-upload-hint', defaultValue: (el) => { el.textContent = text('UPLOAD_HINT', 'Drop or paste files to upload them'); } });
		}

		const editor = new EasyMDE({
			element: area,
			autoDownloadFontAwesome: false,
			spellChecker: false,
			nativeSpellcheck: !!options.spellcheck,
			inputStyle: options.spellcheck ? 'contenteditable' : 'textarea',
			minHeight: options.height || '500px',
			toolbar: toolbar(options, sources),
			status: statusbar,
			previewRender: (value) => previewHtml(value, options.upload ? options.upload.root : '', sources),
			sideBySideFullscreen: false,
			unorderedListStyle: '-',
			blockStyles: { bold: '**', italic: '_', code: '```' },
			insertTexts: {
				horizontalRule: ['', '\n\n---\n\n'],
				image: ['![](', ')'],
				link: ['[', '](https://)'],
				table: ['', '\n\n| Column | Column |\n| --- | --- |\n| Text | Text |\n\n'],
			},
			uploadImage: false,
			direction: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
			forceSync: false,
		});
		const cm = editor.codemirror;

		cm.getWrapperElement().parentNode.style.setProperty('--easymde-height', options.height || '500px');

		// Content nobody changed is posted as it was: no normalising of HTML on opening and saving
		const sync = () => {
			const value = editor.value();

			element.value = value === markdown ? html : toHtml(value, sources);
		};
		let timer = null;

		cm.on('change', () => {
			window.clearTimeout(timer);
			timer = window.setTimeout(sync, 150);
		});

		if (element.form) {
			element.form.addEventListener('submit', sync, true);
		}

		if (options.readonly) {
			cm.setOption('readOnly', 'nocursor');
		}

		// Side by side from the start, where there's room for two columns
		if (options.sideBySide && window.matchMedia('(min-width: 1024px)').matches) {
			EasyMDE.toggleSideBySide(editor);
		}

		if (options.upload) {
			cm.on('drop', (instance, event) => {
				const files = event.dataTransfer && event.dataTransfer.files;

				if (files && files.length && !instance.getOption('readOnly')) {
					// On a line of its own after the line under the pointer: a drop never splits text
					const line = instance.coordsChar({ left: event.clientX, top: event.clientY }).line;

					event.preventDefault();
					instance.setCursor({ line, ch: instance.getLine(line).length });

					if (instance.getLine(line).trim() !== '') {
						instance.replaceSelection('\n');
					}

					uploadFiles(instance, options.upload, files, '\n', sources);
				}
			});

			cm.on('paste', (instance, event) => {
				const files = event.clipboardData && event.clipboardData.files;

				if (files && files.length && !instance.getOption('readOnly')) {
					event.preventDefault();
					uploadFiles(instance, options.upload, files, '', sources);
				}
			});
		}

		Joomla.editors.instances[element.id] = {
			id: element.id,
			element,
			instance: editor,
			getValue() {
				sync();

				return element.value;
			},
			setValue(value) {
				html = value;
				markdown = toMarkdown(value, sources);
				editor.value(markdown);
				sync();
			},
			// HTML, as other editors give it: buttons wrap it in their own HTML (the menu item link) and insert that
			getSelection() {
				const selected = cm.getSelection();

				return selected ? toHtml(selected, sources).replace(/^<p>((?:(?!<\/?p[\s>])[\s\S])*)<\/p>$/, '$1') : '';
			},
			replaceSelection(value) {
				insert(cm, value, sources);
				sync();
			},
			disable(disabled) {
				cm.setOption('readOnly', disabled ? 'nocursor' : false);
			},
			onSave() {
				sync();

				return '';
			},
		};
	};

	Joomla.JoomlaEasyMDE = {
		setupEditors(container) {
			(container || document).querySelectorAll('textarea.joomla-editor-easymde').forEach(setup);
		},
	};

	document.addEventListener('DOMContentLoaded', () => {
		Joomla.JoomlaEasyMDE.setupEditors(document);

		// Subform rows added later (only sent through jQuery's trigger)
		if (window.jQuery) {
			window.jQuery(document).on('subform-row-add', (event, row) => Joomla.JoomlaEasyMDE.setupEditors(row));
		}
	});
})(window, document, window.Joomla);
