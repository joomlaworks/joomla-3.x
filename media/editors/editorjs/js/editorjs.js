/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The Blocks editor (Editor.js) for Joomla's editor fields. The form's textarea keeps HTML, which the site, modules, feeds and
 * search read: when the blocks change, they're rendered on the server through layouts (layouts/joomla/blocks/<type>.php,
 * overridable by the site template) and written into the textarea, after a hidden element holding the blocks themselves
 * (base64 JSON), from which the editor opens them again. A fingerprint of the content's text and images tells whether it was
 * changed outside the editor since; then the blocks are made again from the HTML. Content nobody changed is posted as it was.
 */
((window, document, Joomla) => {
	'use strict';

	if (!window.EditorJS) {
		return;
	}

	/*
	 * MooTools 1.4 (which core pages may load, also after this script) replaces Array.from with one that knows no iterables: a
	 * Map's entries() becomes [iterator], and Editor.js fails ("isBlock is not a function"). Iterables and calls with a map
	 * function get the browser's behaviour back; everything else (arrays, strings, single values) stays as MooTools' own code
	 * expects. Done when the editors start, once every script has loaded.
	 */
	const restoreArrayFrom = () => {
		if (!window.MooTools || /\[native code\]/.test(Function.prototype.toString.call(Array.from)) || Array.from.joomlaEditorJS) {
			return;
		}

		const mooFrom = Array.from;

		Array.from = function from(item, mapFn, thisArg) {
			const iterable = item != null && typeof item !== 'string' && !Array.isArray(item) && typeof item[Symbol.iterator] === 'function';

			if (!iterable && typeof mapFn !== 'function') {
				return mooFrom.apply(this, arguments);
			}

			let list = [];

			if (iterable) {
				list = [...item];
			} else if (item != null && typeof item !== 'string' && typeof item.length === 'number') {
				for (let i = 0; i < item.length; i += 1) {
					list.push(item[i]);
				}
			} else {
				list = item == null ? [] : (typeof item === 'string' ? item.split('') : mooFrom.call(this, item));
			}

			return typeof mapFn === 'function' ? list.map((value, index) => mapFn.call(thisArg, value, index)) : list;
		};

		Array.from.joomlaEditorJS = true;
	};

	const text = (key, fallback) => {
		const value = Joomla.JText ? Joomla.JText._(`PLG_EDITORS_EDITORJS_${key}`, fallback) : fallback;

		return value === `PLG_EDITORS_EDITORJS_${key}` ? fallback : value;
	};

	const MARKER = 'joomla-blocks';

	/*
	 * Inline formatting text may keep, with the attributes it may keep: what Editor.js' inline tools write (it drops anything
	 * else when it saves), plus <strong> and <em>, which become <b> and <i>. Text with other formatting (<s>, <sub>, <span
	 * style>...) stays a Raw HTML block, so nothing is lost.
	 */
	const INLINE = {
		A: ['href'], B: [], STRONG: [], I: [], EM: [], U: ['class'], MARK: ['class'], CODE: ['class'], BR: [],
	};
	const RENAME = { STRONG: 'b', EM: 'i' };
	const BLOCK_TAGS = /^(ADDRESS|ARTICLE|ASIDE|BLOCKQUOTE|DETAILS|DIALOG|DD|DIV|DL|DT|FIELDSET|FIGCAPTION|FIGURE|FOOTER|FORM|H[1-6]|HEADER|HR|IFRAME|LI|MAIN|NAV|OL|P|PRE|SECTION|TABLE|UL|VIDEO|AUDIO|SCRIPT|STYLE)$/;

	// The players of the services the Embed tool may show (the server's embed layout allows the same)
	const PLAYERS = {
		youtube: /^https:\/\/www\.youtube\.com\/embed\/[A-Za-z0-9_-]+(\?[A-Za-z0-9_=&;%-]*)?$/,
		vimeo: /^https:\/\/player\.vimeo\.com\/video\/[0-9]+(\?[A-Za-z0-9_=&;%-]*)?$/,
		twitter: /^https:\/\/platform\.twitter\.com\/embed\/Tweet\.html\?id=[0-9]+$/,
		instagram: /^https:\/\/www\.instagram\.com\/p\/[A-Za-z0-9_-]+\/embed\/?$/,
		codepen: /^https:\/\/codepen\.io\/[A-Za-z0-9_-]+\/embed\/[A-Za-z0-9_-]+(\?[A-Za-z0-9_=&;%-]*)?$/,
	};

	const parse = (html) => new window.DOMParser().parseFromString(`<!doctype html><body>${html || ''}</body>`, 'text/html');

	const escapeHtml = (value) => String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

	// An address which may be used: http(s), mailto, tel, paths and anchors
	const safeUrl = (url) => {
		const value = String(url || '').trim();

		if (value === '' || /[\x00-\x1f\x7f]/.test(value)) {
			return '';
		}

		if (/^(https?:)?\/\//i.test(value) || /^(mailto|tel):/i.test(value)) {
			return value;
		}

		return /^[a-z][a-z0-9+.-]*:/i.test(value) ? '' : value;
	};

	/*
	 * Text with nothing but the inline formatting of INLINE: other elements are unwrapped (their text stays), other
	 * attributes and links elsewhere than safeUrl() allows are dropped
	 */
	const sanitizeInline = (html) => {
		const doc = parse(html);
		const clean = (node) => {
			Array.prototype.slice.call(node.childNodes).forEach((child) => {
				if (child.nodeType === 8) {
					child.remove();

					return;
				}

				if (child.nodeType !== 1) {
					return;
				}

				clean(child);

				const allowed = INLINE[child.nodeName];

				if (!allowed) {
					child.replaceWith(...(/^(SCRIPT|STYLE|TEMPLATE|IFRAME|OBJECT|EMBED)$/.test(child.nodeName) ? [] : Array.prototype.slice.call(child.childNodes)));

					return;
				}

				Array.prototype.slice.call(child.attributes).forEach((attribute) => {
					if (allowed.indexOf(attribute.name) === -1 || (attribute.name === 'href' && safeUrl(attribute.value) === '')) {
						child.removeAttribute(attribute.name);
					}
				});

				if (RENAME[child.nodeName]) {
					const renamed = doc.createElement(RENAME[child.nodeName]);

					renamed.append(...Array.prototype.slice.call(child.childNodes));
					child.replaceWith(renamed);
				}
			});
		};

		clean(doc.body);

		return doc.body.innerHTML;
	};

	// Whether an element's content is text and INLINE formatting only, with only the attributes INLINE allows
	const isInlineOnly = (node) => Array.prototype.every.call(node.querySelectorAll('*'), (child) => {
		const allowed = INLINE[child.nodeName];

		return allowed && Array.prototype.every.call(child.attributes, (attribute) => allowed.indexOf(attribute.name) !== -1);
	});

	const hasNoAttributes = (node) => !node.attributes.length;

	/* UTF-8 from base64 (the server encodes the blocks) */
	const fromBase64 = (value) => new window.TextDecoder().decode(Uint8Array.from(window.atob(value), (c) => c.charCodeAt(0)));

	/*
	 * A fingerprint of content's text and images (FNV-1a), which survives what text filters and other editors do to markup but
	 * not changes to what it says or shows
	 */
	const fingerprint = (body) => {
		const images = Array.prototype.map.call(body.querySelectorAll('img'), (image) => image.getAttribute('src') || '').join('|');
		const value = `${body.textContent.replace(/\s+/g, '')}#${images}`;
		let hash = 0x811c9dc5;

		for (let i = 0; i < value.length; i += 1) {
			hash ^= value.charCodeAt(i);
			hash = Math.imul(hash, 0x01000193) >>> 0;
		}

		return hash.toString(16);
	};

	/* Site addresses: shown from the site's root in the administrator, stored as editors store them (images/x.webp) */
	const forDisplay = (url, root) => (url && !/^([a-z][a-z0-9+.-]*:|\/|#)/i.test(url) ? `${root}/${url}` : url);
	const forStorage = (url, root) => (url && root && url.indexOf(`${root}/`) === 0 ? url.slice(root.length + 1) : (url && !root && /^\/[^/]/.test(url) ? url.slice(1) : url));

	// Images of template blocks: any object of their fields with an address
	const mapFieldImages = (value, map, depth) => {
		if (!value || typeof value !== 'object' || depth > 5) {
			return;
		}

		if (typeof value.url === 'string') {
			value.url = map(value.url);

			(Array.isArray(value.srcset) ? value.srcset : []).forEach((source) => {
				source.location = map(source.location);
			});

			return;
		}

		Object.keys(value).forEach((key) => mapFieldImages(value[key], map, depth + 1));
	};

	const mapImages = (blocks, map) => blocks.forEach((block) => {
		if (block.data && block.data.fields && (block.type === 'template' || block.type.indexOf('tpl__') === 0)) {
			mapFieldImages(block.data.fields, map, 0);
		}

		if (block.type === 'image' && block.data && block.data.file) {
			const file = block.data.file;

			file.url = map(file.url);

			if (Array.isArray(file.srcset)) {
				file.srcset.forEach((source) => {
					source.location = map(source.location);
				});
			}
		}
	});

	/*
	 * Templates' blocks (html/layouts/joomla/blocks/<template>/<block>.xml, read by the editor plugin), by "template/block".
	 * They're saved as {type: "template", data: {template, block, fields}}, and are Editor.js tools named tpl__<template>__<block>.
	 */
	const DEFINITIONS = {};
	const toolName = (template, block) => `tpl__${template}__${block}`;

	const cleanImage = (value) => {
		const image = value && typeof value === 'object' ? value : {};
		const url = safeUrl(image.url);

		return url === '' ? null : {
			url,
			alt: typeof image.alt === 'string' ? image.alt : '',
			width: parseInt(image.width, 10) || 0,
			height: parseInt(image.height, 10) || 0,
			srcset: (Array.isArray(image.srcset) ? image.srcset : []).filter((source) => source && safeUrl(source.location) !== '').map((source) => ({ location: safeUrl(source.location), width: parseInt(source.width, 10) || 0 })),
		};
	};

	// A template block's values, as its definition's fields say (anything else is left out)
	const cleanFields = (fields, value, depth) => (fields || []).reduce((result, field) => {
		const item = value && typeof value === 'object' ? value[field.name] : undefined;

		switch (field.type) {
			case 'richtext':
				result[field.name] = sanitizeInline(typeof item === 'string' ? item : '');
				break;
			case 'url':
				result[field.name] = safeUrl(item);
				break;
			case 'checkbox':
				result[field.name] = !!item;
				break;
			case 'image':
				result[field.name] = cleanImage(item);
				break;
			case 'list':
				result[field.name] = (field.options || []).some((option) => option.value === item) ? item : (field.default || '');
				break;
			case 'group':
				result[field.name] = depth > 0 ? [] : (Array.isArray(item) ? item : []).slice(0, field.max || 20).map((entry) => cleanFields(field.fields, entry, depth + 1));
				break;
			default:
				result[field.name] = typeof item === 'string' ? item : '';
		}

		return result;
	}, {});

	/*
	 * Blocks as they're opened: every text sanitised, addresses checked, unknown embeds dropped, so blocks someone wrote into the
	 * content by hand can't run anything in the editor
	 */
	const sanitizeBlocks = (blocks) => (Array.isArray(blocks) ? blocks : []).filter((block) => block && typeof block.type === 'string' && /^[a-z][a-z0-9_-]{0,49}$/.test(block.type)).map((block) => {
		const data = block.data && typeof block.data === 'object' ? block.data : {};
		const inline = (value) => sanitizeInline(typeof value === 'string' ? value : '');
		const items = (list) => (Array.isArray(list) ? list : []).map((item) => ({
			content: inline(item && typeof item === 'object' ? item.content : item),
			meta: item && item.meta && typeof item.meta === 'object' ? { checked: !!item.meta.checked } : {},
			items: items(item && item.items),
		}));

		switch (block.type) {
			case 'paragraph':
				return { type: 'paragraph', data: { text: inline(data.text) } };
			case 'header':
				return { type: 'header', data: { text: inline(data.text), level: Math.min(6, Math.max(1, parseInt(data.level, 10) || 2)) } };
			case 'list':
				return { type: 'list', data: { style: ['ordered', 'unordered', 'checklist'].indexOf(data.style) !== -1 ? data.style : 'unordered', meta: data.meta && typeof data.meta === 'object' ? { start: parseInt(data.meta.start, 10) || 1 } : {}, items: items(data.items) } };
			case 'quote':
				return { type: 'quote', data: { text: inline(data.text), caption: inline(data.caption), alignment: data.alignment === 'center' ? 'center' : 'left' } };
			case 'warning':
				return { type: 'warning', data: { title: inline(data.title), message: inline(data.message) } };
			case 'table':
				return { type: 'table', data: { withHeadings: !!data.withHeadings, content: (Array.isArray(data.content) ? data.content : []).map((row) => (Array.isArray(row) ? row : []).map(inline)) } };
			case 'image': {
				const file = data.file && typeof data.file === 'object' ? data.file : {};
				const url = safeUrl(file.url);

				return url === '' ? null : {
					type: 'image',
					data: {
						file: {
							url,
							alt: typeof file.alt === 'string' ? file.alt : '',
							width: parseInt(file.width, 10) || 0,
							height: parseInt(file.height, 10) || 0,
							srcset: (Array.isArray(file.srcset) ? file.srcset : []).filter((source) => source && safeUrl(source.location) !== '').map((source) => ({ location: safeUrl(source.location), width: parseInt(source.width, 10) || 0 })),
						},
						caption: inline(data.caption),
						withBorder: !!data.withBorder,
						withBackground: !!data.withBackground,
						stretched: !!data.stretched,
					},
				};
			}
			case 'embed':
				return PLAYERS[data.service] && PLAYERS[data.service].test(String(data.embed || '')) ? {
					type: 'embed',
					data: { service: data.service, source: safeUrl(data.source), embed: data.embed, width: parseInt(data.width, 10) || 580, height: parseInt(data.height, 10) || 320, caption: inline(data.caption) },
				} : null;
			case 'code':
				return { type: 'code', data: { code: typeof data.code === 'string' ? data.code : '' } };
			case 'raw':
				return { type: 'raw', data: { html: typeof data.html === 'string' ? data.html : '' } };
			case 'delimiter':
			case 'readmore':
				return { type: block.type, data: {} };
			case 'template': {
				const template = String(data.template || '');
				const name = String(data.block || '');

				if (!/^[a-z0-9_]{1,50}$/.test(template) || !/^[a-z0-9_-]{1,50}$/.test(name)) {
					return null;
				}

				const definition = DEFINITIONS[`${template}/${name}`];

				// Without its definition (its template was removed), kept as it was, every string sanitised
				return { type: 'template', data: { template, block: name, fields: definition ? cleanFields(definition.fields, data.fields, 0) : sanitizeBlocks([{ type: 'other', data: data.fields || {} }])[0].data } };
			}
			default: {
				// Editor.js' name of a template block
				if (block.type.indexOf('tpl__') === 0) {
					return sanitizeBlocks([{ type: 'template', data }])[0] || null;
				}

				// Blocks of other kinds (templates' own, later): every string sanitised as text
				const clean = (value) => {
					if (typeof value === 'string') {
						return inline(value);
					}

					if (Array.isArray(value)) {
						return value.map(clean);
					}

					if (value && typeof value === 'object') {
						return Object.keys(value).reduce((result, key) => Object.assign(result, { [key]: clean(value[key]) }), {});
					}

					return value;
				};

				return { type: block.type, data: clean(data) };
			}
		}
	}).filter(Boolean);

	/*
	 * HTML as blocks: what Editor.js' tools express becomes their blocks, anything else (attributes, layout markup, embeds of
	 * other services, comments) a Raw HTML block holding it as it was
	 */
	const embedFromIframe = (iframe) => {
		const src = iframe.getAttribute('src') || '';
		const service = Object.keys(PLAYERS).find((name) => PLAYERS[name].test(src));

		return service ? { type: 'embed', data: { service, source: src, embed: src, width: parseInt(iframe.getAttribute('width'), 10) || 580, height: parseInt(iframe.getAttribute('height'), 10) || 320, caption: '' } } : null;
	};

	// The image's alt text goes with its file (the Image tool keeps the file's data as it is), not into a visible caption
	const imageBlock = (image, caption) => ({
		type: 'image',
		data: { file: { url: image.getAttribute('src') || '', alt: image.getAttribute('alt') || '' }, caption: caption || '', withBorder: false, withBackground: false, stretched: false },
	});

	const listItems = (list) => {
		const items = [];

		Array.prototype.forEach.call(list.children, (li) => {
			if (li.nodeName !== 'LI') {
				return;
			}

			const copy = li.cloneNode(true);
			const nested = Array.prototype.filter.call(copy.children, (child) => child.nodeName === 'UL' || child.nodeName === 'OL');

			nested.forEach((child) => child.remove());
			items.push({ content: sanitizeInline(copy.innerHTML.trim()), meta: {}, items: nested.length ? listItems(nested[0]) : [] });
		});

		return items;
	};

	const listFits = (list) => Array.prototype.every.call(list.querySelectorAll('li'), (li) => hasNoAttributes(li) && Array.prototype.every.call(li.children, (child) => (child.nodeName === 'UL' || child.nodeName === 'OL' ? hasNoAttributes(child) : INLINE[child.nodeName] && isInlineOnly(child))));

	const tableFits = (table) => !table.querySelector('table, [colspan], [rowspan], caption, colgroup') && Array.prototype.every.call(table.querySelectorAll('td, th'), (cell) => isInlineOnly(cell));

	const htmlToBlocks = (html) => {
		const body = parse(html).body;
		const blocks = [];
		let inline = '';

		const marker = body.querySelector(`div.${MARKER}`);

		if (marker) {
			marker.remove();
		}

		const flush = () => {
			if (inline.trim() !== '') {
				blocks.push({ type: 'paragraph', data: { text: sanitizeInline(inline.trim()) } });
			}

			inline = '';
		};
		const raw = (node) => {
			flush();
			blocks.push({ type: 'raw', data: { html: node.nodeType === 8 ? `<!--${node.data}-->` : node.outerHTML } });
		};

		Array.prototype.forEach.call(body.childNodes, (node) => {
			if (node.nodeType === 3) {
				inline += escapeHtml(node.data);

				return;
			}

			if (node.nodeType === 8) {
				raw(node);

				return;
			}

			if (node.nodeType !== 1) {
				return;
			}

			const name = node.nodeName;

			if (INLINE[name] && isInlineOnly(node) && Array.prototype.every.call(node.attributes, (attribute) => INLINE[name].indexOf(attribute.name) !== -1)) {
				inline += node.outerHTML;

				return;
			}

			if (name === 'IMG') {
				flush();
				blocks.push(imageBlock(node));

				return;
			}

			if (!BLOCK_TAGS.test(name) && !INLINE[name]) {
				raw(node);

				return;
			}

			flush();

			const only = node.children.length === 1 && node.textContent.trim() === '' ? node.children[0] : null;

			if (name === 'P' && hasNoAttributes(node) && only && only.nodeName === 'IMG') {
				blocks.push(imageBlock(only));
			} else if (name === 'P' && hasNoAttributes(node) && isInlineOnly(node)) {
				if (node.innerHTML.trim() !== '' && node.innerHTML.trim() !== '&nbsp;') {
					blocks.push({ type: 'paragraph', data: { text: sanitizeInline(node.innerHTML.trim()) } });
				}
			} else if (/^H[1-6]$/.test(name) && hasNoAttributes(node) && isInlineOnly(node)) {
				blocks.push({ type: 'header', data: { text: sanitizeInline(node.innerHTML.trim()), level: parseInt(name.charAt(1), 10) } });
			} else if ((name === 'UL' || name === 'OL') && hasNoAttributes(node) && listFits(node)) {
				blocks.push({ type: 'list', data: { style: name === 'OL' ? 'ordered' : 'unordered', meta: {}, items: listItems(node) } });
			} else if (name === 'BLOCKQUOTE' && hasNoAttributes(node) && Array.prototype.every.call(node.children, (child) => (child.nodeName === 'P' || child.nodeName === 'CITE') && hasNoAttributes(child) && isInlineOnly(child))) {
				const cite = node.querySelector('cite');
				const paragraphs = Array.prototype.filter.call(node.children, (child) => child.nodeName === 'P').map((p) => p.innerHTML.trim());

				blocks.push({ type: 'quote', data: { text: sanitizeInline(paragraphs.length ? paragraphs.join('<br>') : node.innerHTML.trim()), caption: cite ? sanitizeInline(cite.innerHTML) : '', alignment: 'left' } });
			} else if (name === 'HR' && node.id === 'system-readmore') {
				blocks.push({ type: 'readmore', data: {} });
			} else if (name === 'HR' && hasNoAttributes(node)) {
				blocks.push({ type: 'delimiter', data: {} });
			} else if (name === 'FIGURE' && node.querySelector('img') && node.children.length <= 2 && (node.children.length === 1 || node.querySelector('figcaption'))) {
				const caption = node.querySelector('figcaption');

				blocks.push(imageBlock(node.querySelector('img'), caption ? sanitizeInline(caption.innerHTML) : ''));
			} else if (name === 'IFRAME' && embedFromIframe(node)) {
				blocks.push(embedFromIframe(node));
			} else if (name === 'PRE' && hasNoAttributes(node) && (node.children.length === 0 || (node.children.length === 1 && node.children[0].nodeName === 'CODE'))) {
				blocks.push({ type: 'code', data: { code: node.textContent } });
			} else if (name === 'TABLE' && hasNoAttributes(node) && tableFits(node)) {
				const rows = Array.prototype.slice.call(node.querySelectorAll('tr'));
				const withHeadings = rows.length > 0 && Array.prototype.every.call(rows[0].children, (cell) => cell.nodeName === 'TH');

				blocks.push({ type: 'table', data: { withHeadings, content: rows.map((row) => Array.prototype.map.call(row.children, (cell) => sanitizeInline(cell.innerHTML.trim()))) } });
			} else {
				raw(node);
			}
		});

		flush();

		return blocks;
	};

	/*
	 * The hidden element holding the blocks, written before the content's HTML: the blocks as the server filtered and encoded
	 * them, its signature (the editor plugin drops blocks whose signature doesn't match before the editor opens) and the
	 * fingerprint of the HTML they gave
	 */
	const markerHtml = (result) => `<div class="${MARKER}" hidden="hidden" data-version="1" data-hash="${fingerprint(parse(result.html).body)}" data-signature="${escapeHtml(result.signature)}" data-blocks="${escapeHtml(result.blocks)}"></div>`;

	/*
	 * The blocks of the textarea's content: from its hidden element when the content is as the editor left it, else made from
	 * its HTML (rebuilt tells which)
	 */
	const readContent = (html) => {
		const body = parse(html).body;
		const marker = body.querySelector(`div.${MARKER}[data-blocks]`);

		if (marker) {
			const hash = marker.getAttribute('data-hash');

			marker.remove();

			try {
				const data = JSON.parse(fromBase64(marker.getAttribute('data-blocks')));

				if (hash === fingerprint(body)) {
					return { blocks: sanitizeBlocks(data.blocks), rebuilt: false };
				}
			} catch (error) {
				// A damaged element: the HTML decides
			}

			return { blocks: sanitizeBlocks(htmlToBlocks(body.innerHTML)), rebuilt: true };
		}

		return { blocks: sanitizeBlocks(htmlToBlocks(html)), rebuilt: false };
	};

	/* Read more: where an article's introduction ends; one per content */
	class ReadMore {
		static get toolbox() {
			return {
				title: text('READMORE', 'Read more'),
				icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 12h3M10.5 12h3M17 12h3"/><path d="M8 6l4 3 4-3M8 18l4-3 4 3"/></svg>',
			};
		}

		static get isReadOnlySupported() {
			return true;
		}

		render() {
			const element = document.createElement('div');

			element.className = 'editorjs-readmore';
			element.setAttribute('contenteditable', 'false');
			element.innerHTML = `<span>${escapeHtml(text('READMORE', 'Read more'))}</span><small>${escapeHtml(text('READMORE_HINT', 'The introduction ends here'))}</small>`;

			return element;
		}

		save() {
			return {};
		}
	}

	/* Images go to the Media Manager (optimised as its options say); a srcset comes back when it saved a copy for small screens */
	const uploader = (options) => ({
		uploadByFile(file) {
			if (!options.upload) {
				return Promise.resolve({ success: 0 });
			}

			const data = new FormData();
			const dot = (file.name || '').lastIndexOf('.');
			const extension = dot > 0 ? file.name.slice(dot + 1).toLowerCase().replace(/[^a-z0-9]/g, '') : (file.type.split('/')[1] || 'png');
			let base = (dot > 0 ? file.name.slice(0, dot) : '').normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^A-Za-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '');

			if (base === '' || /^image$/i.test(base)) {
				base = `image-${new Date().toISOString().replace(/\D/g, '').slice(0, 14)}`;
			}

			data.append('Filedata', file, `${base}.${extension}`);
			data.append('folder', options.upload.folder);
			data.append(options.upload.token, '1');

			return window.fetch(options.upload.url, { method: 'POST', body: data, credentials: 'same-origin' })
				.then((response) => response.json())
				.then((response) => {
					if (!response || String(response.status) !== '1' || !response.location) {
						throw new Error((response && (response.message || response.error)) || '');
					}

					return {
						success: 1,
						file: {
							url: options.root + response.location,
							width: parseInt(response.width, 10) || 0,
							height: parseInt(response.height, 10) || 0,
							srcset: Array.isArray(response.srcset) ? response.srcset.map((source) => ({ location: options.root + source.location, width: parseInt(source.width, 10) || 0 })) : [],
						},
					};
				})
				.catch((error) => {
					Joomla.renderMessages({ error: [error.message || text('UPLOAD_FAILED', 'The image couldn\'t be uploaded.')] });

					return { success: 0 };
				});
		},
		uploadByUrl(url) {
			const address = safeUrl(url);

			return Promise.resolve(address ? { success: 1, file: { url: address } } : { success: 0 });
		},
	});

	const ICONS = {
		block: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/></svg>',
		hero: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 14h6M7 10h10"/></svg>',
		quote: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 11H4v-1a4 4 0 0 1 4-4M17 11h-3v-1a4 4 0 0 1 4-4M4 11v5h5v-5M14 11v5h5v-5"/></svg>',
		grid: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg>',
		columns: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="5" height="16" rx="1"/><rect x="10" y="4" width="5" height="16" rx="1"/><rect x="17" y="4" width="4" height="16" rx="1"/></svg>',
	};

	const make = (tag, className, attributes) => {
		const node = document.createElement(tag);

		if (className) {
			node.className = className;
		}

		Object.keys(attributes || {}).forEach((name) => node.setAttribute(name, attributes[name]));

		return node;
	};

	/*
	 * A template's block as an Editor.js tool: a form made from its definition (text, formatted text, multi-line text, an image,
	 * a link, a list, a Yes/No, and groups of entries with a smallest and largest count, shown in columns). The block menu offers
	 * the site template's blocks; other installed templates' only open blocks already in the content.
	 */
	const templateTool = (definition, upload) => class TemplateBlock {
		static get toolbox() {
			return definition.menu ? { title: `${definition.title} · ${definition.note}`, icon: ICONS[definition.icon] || ICONS.block } : undefined;
		}

		static get isReadOnlySupported() {
			return true;
		}

		constructor({ data, block, readOnly }) {
			this.fields = cleanFields(definition.fields, data && data.fields, 0);
			this.block = block;
			this.readOnly = readOnly;
			this.read = () => ({});
		}

		changed() {
			if (this.block && typeof this.block.dispatchChange === 'function') {
				this.block.dispatchChange();
			}
		}

		// One field: its element and how to read its value
		field(field, value, readers) {
			const wrapper = make('label', `editorjs-tpl-field is-${field.type}`);
			const label = make('span', 'editorjs-tpl-label');
			let input;

			label.textContent = field.label;
			wrapper.appendChild(label);

			switch (field.type) {
				case 'textarea':
					input = make('textarea', 'editorjs-tpl-input', { rows: '3' });
					input.value = value || '';
					readers[field.name] = () => input.value;
					break;
				case 'richtext':
					input = make('div', 'editorjs-tpl-input editorjs-tpl-rich', { contenteditable: this.readOnly ? 'false' : 'true' });
					input.innerHTML = sanitizeInline(value || '');
					readers[field.name] = () => sanitizeInline(input.innerHTML);
					// Plain text when pasting: Editor.js would make blocks of it
					input.addEventListener('paste', (event) => {
						event.preventDefault();
						event.stopPropagation();
						document.execCommand('insertText', false, (event.clipboardData || window.clipboardData).getData('text/plain'));
					});
					break;
				case 'checkbox':
					input = make('input', 'editorjs-tpl-check', { type: 'checkbox' });
					input.checked = !!value;
					readers[field.name] = () => input.checked;
					break;
				case 'list':
					input = make('select', 'editorjs-tpl-input');
					(field.options || []).forEach((option) => {
						const node = make('option', '', { value: option.value });

						node.textContent = option.text;
						input.appendChild(node);
					});
					input.value = value || field.default || '';
					readers[field.name] = () => input.value;
					break;
				case 'image':
					return this.imageField(field, value, readers, wrapper);
				default:
					input = make('input', 'editorjs-tpl-input', { type: field.type === 'url' ? 'url' : 'text' });
					input.value = value || '';
					readers[field.name] = () => (field.type === 'url' ? safeUrl(input.value) : input.value);
			}

			if (this.readOnly) {
				input.setAttribute('disabled', 'disabled');
			}

			wrapper.appendChild(input);

			return wrapper;
		}

		// An image: a preview, a button to upload one through the Media Manager, its alt text
		imageField(field, value, readers, wrapper) {
			let image = value && value.url ? value : null;
			const preview = make('div', 'editorjs-tpl-image');
			const alt = make('input', 'editorjs-tpl-input', { type: 'text', placeholder: text('IMAGE_ALT', 'Alternative text') });
			const choose = make('button', 'editorjs-tpl-button', { type: 'button' });
			const remove = make('button', 'editorjs-tpl-button', { type: 'button' });
			const file = make('input', '', { type: 'file', accept: 'image/*', hidden: 'hidden' });
			const show = () => {
				preview.innerHTML = image ? `<img src="${escapeHtml(image.url)}" alt="">` : '';
				remove.hidden = !image;
			};

			choose.textContent = text('IMAGE_CHOOSE', 'Upload an image');
			remove.textContent = text('IMAGE_REMOVE', 'Remove');
			alt.value = image ? image.alt || '' : '';
			choose.addEventListener('click', () => file.click());
			remove.addEventListener('click', () => {
				image = null;
				show();
				this.changed();
			});
			file.addEventListener('change', () => {
				if (!file.files.length) {
					return;
				}

				choose.disabled = true;
				upload.uploadByFile(file.files[0]).then((result) => {
					choose.disabled = false;

					if (result.success) {
						image = result.file;
						show();
						this.changed();
					}
				});
			});
			readers[field.name] = () => (image ? Object.assign({}, image, { alt: alt.value }) : null);

			show();

			if (this.readOnly || !upload) {
				choose.hidden = true;
				remove.hidden = true;
				alt.disabled = this.readOnly;
			}

			const actions = make('span', 'editorjs-tpl-actions');

			actions.append(choose, remove, file);
			wrapper.append(preview, alt, actions);

			return wrapper;
		}

		// A group: its entries side by side (definition.columns), with Add and Remove within its smallest and largest count
		group(field, values, readers) {
			const wrapper = make('div', 'editorjs-tpl-group');
			const title = make('div', 'editorjs-tpl-label');
			const entries = make('div', 'editorjs-tpl-entries');
			const add = make('button', 'editorjs-tpl-button', { type: 'button' });
			const items = [];
			const refresh = () => {
				items.forEach((item, index) => {
					item.title.textContent = `${field.item} ${index + 1}`;
					item.remove.hidden = this.readOnly || items.length <= field.min;
				});
				add.hidden = this.readOnly || items.length >= field.max;
			};
			const addEntry = (value) => {
				const entry = make('div', 'editorjs-tpl-entry');
				const head = make('div', 'editorjs-tpl-entry-head');
				const item = { readers: {}, title: make('span', ''), remove: make('button', 'editorjs-tpl-button', { type: 'button' }) };

				item.remove.textContent = text('REMOVE', 'Remove');
				item.remove.addEventListener('click', () => {
					items.splice(items.indexOf(item), 1);
					entry.remove();
					refresh();
					this.changed();
				});
				head.append(item.title, item.remove);
				entry.appendChild(head);
				(field.fields || []).forEach((child) => entry.appendChild(this.field(child, value ? value[child.name] : undefined, item.readers)));
				items.push(item);
				entries.appendChild(entry);
			};

			title.textContent = field.label;
			entries.style.setProperty('--columns', String(field.columns || 1));
			add.textContent = `+ ${field.item}`;
			add.addEventListener('click', () => {
				addEntry(null);
				refresh();
				this.changed();
			});

			(values || []).forEach(addEntry);

			while (items.length < field.min) {
				addEntry(null);
			}

			refresh();
			readers[field.name] = () => items.map((item) => Object.keys(item.readers).reduce((result, name) => Object.assign(result, { [name]: item.readers[name]() }), {}));
			wrapper.append(title, entries, add);

			return wrapper;
		}

		render() {
			const root = make('div', 'editorjs-tpl');
			const head = make('div', 'editorjs-tpl-head');
			const readers = {};

			head.innerHTML = `<span class="editorjs-tpl-icon">${ICONS[definition.icon] || ICONS.block}</span><strong>${escapeHtml(definition.title)}</strong><small>${escapeHtml(definition.note)}</small>`;
			root.appendChild(head);

			(definition.fields || []).forEach((field) => {
				root.appendChild(field.type === 'group' ? this.group(field, this.fields[field.name], readers) : this.field(field, this.fields[field.name], readers));
			});

			// Keys in the fields are the fields', not Editor.js' (Enter would split the block, Backspace merge it, Tab open the menu)
			root.addEventListener('keydown', (event) => {
				if (/^(Enter|Backspace|Delete|Tab|ArrowUp|ArrowDown|ArrowLeft|ArrowRight)$/.test(event.key) && event.target !== root) {
					event.stopPropagation();
				}
			});
			// Editor.js reads only typing (InputEvent) from a block's input events and fails on the others (checkboxes, file
			// inputs): those stop here, and the block reports its change itself
			root.addEventListener('input', (event) => {
				if (!(event instanceof window.InputEvent)) {
					event.stopPropagation();
				}

				this.changed();
			});
			root.addEventListener('change', () => this.changed());

			this.read = () => Object.keys(readers).reduce((result, name) => Object.assign(result, { [name]: readers[name]() }), {});

			return root;
		}

		save() {
			return { template: definition.template, block: definition.block, fields: cleanFields(definition.fields, this.read(), 0) };
		}
	};

	// Template blocks as Editor.js has them: tools named tpl__<template>__<block>
	const toEditor = (blocks) => blocks.map((block) => (block.type === 'template' ? { type: toolName(block.data.template, block.data.block), data: block.data } : block));

	/*
	 * The preview: the page the server made, in a dialog, in a sandboxed frame (scripts of embeds run, but in an origin of their
	 * own, never the administrator's), at a desktop, tablet or phone width
	 */
	const openPreview = (page) => {
		let dialog = document.querySelector('dialog.editorjs-dialog');

		if (!dialog) {
			dialog = make('dialog', 'editorjs-dialog');
			dialog.innerHTML = `<div class="editorjs-dialog-head"><strong>${escapeHtml(text('PREVIEW_TITLE', 'Preview with the site template (not saved)'))}</strong>`
				+ `<span class="editorjs-dialog-sizes">${[['100%', 'DESKTOP', 'Desktop'], ['768px', 'TABLET', 'Tablet'], ['390px', 'PHONE', 'Phone']].map((size) => `<button type="button" class="btn btn-small" data-width="${size[0]}">${escapeHtml(text(`PREVIEW_${size[1]}`, size[2]))}</button>`).join('')}</span>`
				+ `<button type="button" class="btn btn-small editorjs-dialog-close">${escapeHtml(text('CLOSE', 'Close'))}</button></div>`
				+ '<div class="editorjs-dialog-body"><iframe sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox" title="Preview"></iframe></div>';
			document.body.appendChild(dialog);
			dialog.querySelector('.editorjs-dialog-close').addEventListener('click', () => dialog.close());
			dialog.querySelector('.editorjs-dialog-sizes').addEventListener('click', (event) => {
				const button = event.target.closest('[data-width]');

				if (button) {
					dialog.querySelector('iframe').style.width = button.getAttribute('data-width');
					dialog.querySelectorAll('[data-width]').forEach((other) => other.classList.toggle('active', other === button));
				}
			});
		}

		dialog.querySelector('iframe').srcdoc = page;

		if (!dialog.open) {
			dialog.showModal();
		}
	};

	const tools = (options) => {
		const list = {
			header: { class: window.Header, inlineToolbar: true, config: { levels: [2, 3, 4, 5, 6], defaultLevel: 2 } },
			list: { class: window.EditorjsList, inlineToolbar: true, config: { defaultStyle: 'unordered' } },
			image: { class: window.ImageTool, config: { uploader: uploader(options), features: { border: true, background: true, caption: 'optional', stretch: true } } },
			quote: { class: window.Quote, inlineToolbar: true },
			table: { class: window.Table, inlineToolbar: true },
			embed: { class: window.Embed, inlineToolbar: true, config: { services: (options.embeds || []).reduce((services, name) => Object.assign(services, { [name]: true }), {}) } },
			code: { class: window.CodeTool },
			raw: { class: window.RawTool },
			delimiter: { class: window.Delimiter },
			warning: { class: window.Warning, inlineToolbar: true },
			marker: { class: window.Marker },
			inlineCode: { class: window.InlineCode },
			underline: { class: window.Underline },
		};

		if (options.readmore) {
			list.readmore = { class: ReadMore };
		}

		(options.blocks || []).forEach((definition) => {
			list[toolName(definition.template, definition.block)] = { class: templateTool(definition, options.upload ? uploader(options) : null), inlineToolbar: true };
		});

		// A tool whose script didn't load is left out rather than breaking the editor
		Object.keys(list).forEach((name) => {
			if (typeof list[name].class !== 'function') {
				delete list[name];
			}
		});

		return list;
	};

	const setupEditor = (element) => {
		if (element.getAttribute('data-editorjs-ready')) {
			return;
		}

		element.setAttribute('data-editorjs-ready', '1');

		let options = {};

		try {
			options = JSON.parse(element.getAttribute('data-editorjs')) || {};
		} catch (error) {
			options = {};
		}

		const root = options.root || '';
		const holder = document.getElementById(`${element.id}_editorjs`);

		(options.blocks || []).forEach((definition) => {
			DEFINITIONS[`${definition.template}/${definition.block}`] = definition;
		});

		const opened = readContent(element.value);

		mapImages(opened.blocks, (url) => forDisplay(url, root));

		let dirty = false;
		let pending = null;
		let timer = null;
		let lastRange = null;
		let baseline = null;
		const original = element.value;

		// The blocks as they're saved, in a form two saves can be compared in
		const stored = (output) => {
			const blocks = sanitizeBlocks(output.blocks.map((block) => ({ type: block.type, data: block.data })));

			mapImages(blocks, (url) => forStorage(url, root));

			return blocks;
		};

		const editor = new window.EditorJS({
			holder,
			tools: tools(options),
			data: { blocks: toEditor(opened.blocks) },
			readOnly: !!options.readonly,
			placeholder: text('PLACEHOLDER', 'Write, or press Tab for blocks'),
			minHeight: 80,
			onChange: (api, event) => {
				const events = Array.isArray(event) ? event : [event];

				// One Read more per content
				events.forEach((change) => {
					if (change && change.type === 'block-added' && change.detail && change.detail.target && change.detail.target.name === 'readmore') {
						let count = 0;

						for (let i = 0; i < api.blocks.getBlocksCount(); i += 1) {
							count += api.blocks.getBlockByIndex(i).name === 'readmore' ? 1 : 0;
						}

						if (count > 1) {
							api.blocks.delete(change.detail.index);
							Joomla.renderMessages({ warning: [text('READMORE_ONE', 'There is a Read more already: only one is used.')] });
						}
					}
				});

				dirty = true;
				window.clearTimeout(timer);
				timer = window.setTimeout(() => flush().catch(() => {}), 800);
			},
		});

		// What the editor saves before anyone changed anything (its tools report changes while they set up): a save which gives
		// the same keeps the content as it was, unrendered
		editor.isReady.then(() => editor.save()).then((output) => {
			baseline = JSON.stringify(stored(output));
		}).catch(() => {});

		if (opened.rebuilt) {
			Joomla.renderMessages({ notice: [text('REBUILT', 'This content was changed outside this editor since it was last saved with it, so its blocks were made again from the content.')] });
		}

		// Server rendering: the blocks' HTML through the site template's layouts
		const render = (blocks, preview) => {
			const data = new FormData();

			data.append('blocks', JSON.stringify({ blocks }));
			data.append(options.render.token, '1');

			if (preview) {
				data.append('preview', '1');
			}

			return window.fetch(options.render.url, { method: 'POST', body: data, credentials: 'same-origin' })
				.then((response) => response.json())
				.then((response) => {
					const result = response && response.success && Array.isArray(response.data) ? response.data.find((item) => item && typeof item.html === 'string' && typeof item.blocks === 'string') : null;

					if (!result) {
						throw new Error((response && response.message) || text('RENDER_FAILED', 'The blocks couldn\'t be turned into content to save.'));
					}

					return result;
				});
		};

		// The textarea brought up to date with the blocks (when they changed)
		const flush = () => {
			if (pending) {
				return pending.then(() => (dirty ? flush() : undefined));
			}

			if (!dirty) {
				return Promise.resolve();
			}

			dirty = false;
			pending = editor.save()
				.then((output) => {
					const blocks = stored(output);

					if (baseline !== null && JSON.stringify(blocks) === baseline && !opened.rebuilt) {
						return original;
					}

					return blocks.length ? render(blocks).then((result) => `${markerHtml(result)}\n${result.html}`) : '';
				})
				.then((value) => {
					element.value = value;
					pending = null;
				}, (error) => {
					dirty = true;
					pending = null;
					throw error;
				});

			return pending;
		};

		// The bar above the editor: Preview (the blocks as they are now, saved or not, rendered with the site template) and Full Screen
		const wrapper = element.closest('.js-editor-editorjs');
		const bar = wrapper ? wrapper.querySelector('.editorjs-bar') : null;
		const fullscreen = (on) => {
			const button = bar.querySelector('[data-editorjs-action="fullscreen"]');

			wrapper.classList.toggle('is-fullscreen', on);
			document.documentElement.classList.toggle('editorjs-fullscreen-open', on);
			button.setAttribute('aria-pressed', on ? 'true' : 'false');
			button.textContent = on ? text('FULLSCREEN_EXIT', 'Exit Full Screen') : text('FULLSCREEN', 'Full Screen');
		};

		if (bar) {
			bar.addEventListener('click', (event) => {
				const button = event.target.closest('[data-editorjs-action]');

				if (!button) {
					return;
				}

				if (button.getAttribute('data-editorjs-action') === 'fullscreen') {
					fullscreen(!wrapper.classList.contains('is-fullscreen'));

					return;
				}

				button.disabled = true;
				editor.save()
					.then((output) => render(stored(output), true))
					.then((result) => openPreview(result.document || ''))
					.catch((error) => Joomla.renderMessages({ error: [error && error.message ? error.message : text('RENDER_FAILED', 'The blocks couldn\'t be turned into content to save.')] }))
					.then(() => {
						button.disabled = false;
					});
			});

			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && wrapper.classList.contains('is-fullscreen') && !document.querySelector('dialog.editorjs-dialog[open]')) {
					fullscreen(false);
				}
			});
		}

		// The form waits for the blocks to be saved into the textarea
		if (element.form) {
			element.form.addEventListener('submit', (event) => {
				if (!dirty && !pending) {
					return;
				}

				event.preventDefault();
				event.stopImmediatePropagation();
				window.clearTimeout(timer);

				flush().then(() => {
					const button = document.createElement('input');

					button.type = 'submit';
					button.style.display = 'none';
					element.form.appendChild(button).click();
					element.form.removeChild(button);
				}, (error) => {
					Joomla.renderMessages({ error: [error && error.message ? error.message : text('RENDER_FAILED', 'The blocks couldn\'t be turned into content to save.')] });
				});
			}, true);
		}

		// Where the editor buttons insert: the last place the caret was in the editor
		document.addEventListener('selectionchange', () => {
			const selection = window.getSelection();

			if (selection.rangeCount && holder.contains(selection.anchorNode)) {
				lastRange = selection.getRangeAt(0).cloneRange();
			}
		});

		const insertBlocks = (blocks) => {
			let index = editor.blocks.getCurrentBlockIndex();

			index = index < 0 ? editor.blocks.getBlocksCount() : index + 1;

			mapImages(blocks, (url) => forDisplay(url, root));
			toEditor(blocks).forEach((block) => {
				editor.blocks.insert(block.type, block.data, undefined, index, false);
				index += 1;
			});
		};

		Joomla.editors.instances[element.id] = {
			id: element.id,
			element,
			instance: editor,
			// HTML, with a Read more when the blocks have one (the Read more button checks for it) even before the textarea caught up
			getValue() {
				let value = element.value;

				if (!/<hr\s+id=("|')system-readmore("|')/i.test(value) && editor.blocks) {
					for (let i = 0; i < editor.blocks.getBlocksCount(); i += 1) {
						if (editor.blocks.getBlockByIndex(i).name === 'readmore') {
							value += '<hr id="system-readmore">';
							break;
						}
					}
				}

				return value;
			},
			setValue(value) {
				const read = readContent(value);

				mapImages(read.blocks, (url) => forDisplay(url, root));
				editor.render({ blocks: toEditor(read.blocks) });
				dirty = true;
			},
			getSelection() {
				const selection = window.getSelection();

				if (!selection.rangeCount || !holder.contains(selection.anchorNode)) {
					return '';
				}

				const container = document.createElement('div');

				container.appendChild(selection.getRangeAt(0).cloneContents());

				return sanitizeInline(container.innerHTML);
			},
			// The editor buttons' HTML: inline content (a link, a {loadmodule}) at the caret, anything else as blocks after it
			replaceSelection(html) {
				const body = parse(html).body;
				const inline = Array.prototype.every.call(body.childNodes, (node) => node.nodeType === 3 || (node.nodeType === 1 && INLINE[node.nodeName] && node.nodeName !== 'BR'));
				const editable = lastRange && lastRange.startContainer && (lastRange.startContainer.nodeType === 1 ? lastRange.startContainer : lastRange.startContainer.parentNode).closest('[contenteditable="true"]');

				if (inline && editable && holder.contains(editable)) {
					const selection = window.getSelection();

					editable.focus();
					selection.removeAllRanges();
					selection.addRange(lastRange);
					document.execCommand('insertHTML', false, sanitizeInline(html));
				} else {
					insertBlocks(sanitizeBlocks(htmlToBlocks(html)));
				}

				dirty = true;
				window.clearTimeout(timer);
				timer = window.setTimeout(() => flush().catch(() => {}), 800);
			},
			disable(disabled) {
				editor.isReady.then(() => editor.readOnly.toggle(!!disabled));
			},
			onSave() {},
			flush,
		};
	};

	// Older extensions insert through this
	if (typeof window.jInsertEditorText !== 'function') {
		window.jInsertEditorText = (content, editor) => {
			if (Joomla.editors.instances[editor]) {
				Joomla.editors.instances[editor].replaceSelection(content);
			}
		};
	}

	Joomla.JoomlaEditorJS = {
		setupEditors(target) {
			restoreArrayFrom();
			(target || document).querySelectorAll('textarea.joomla-editor-editorjs').forEach(setupEditor);
		},
		htmlToBlocks,
		sanitizeBlocks,
		readContent,
	};

	document.addEventListener('DOMContentLoaded', () => Joomla.JoomlaEditorJS.setupEditors());

	// Subform rows added later
	if (window.jQuery) {
		window.jQuery(document).on('subform-row-add', (event, row) => Joomla.JoomlaEditorJS.setupEditors(row));
	}
})(window, document, window.Joomla || {});
