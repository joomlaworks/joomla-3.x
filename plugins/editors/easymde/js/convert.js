/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

((window) => {
	'use strict';

	const { TurndownService, turndownPluginGfm, marked } = window;

	if (!TurndownService || !marked) {
		return;
	}

	// The elements Markdown has syntax for, with the attributes it can write
	const EXPRESSIBLE = {
		p: [], br: [], h1: [], h2: [], h3: [], h4: [], h5: [], h6: [], strong: [], b: [], em: [], i: [], del: [], s: [], strike: [],
		code: [], pre: [], blockquote: [], ul: [], ol: ['start'], li: [], a: ['href', 'title'], img: ['src', 'alt', 'title'], hr: [],
		table: [], thead: [], tbody: [], tr: [], th: [], td: [],
	};
	const BLOCK_IN_CELL = 'p, div, ul, ol, table, blockquote, pre, h1, h2, h3, h4, h5, h6, br, hr';
	const BLOCKS = ['ADDRESS', 'ARTICLE', 'ASIDE', 'AUDIO', 'BLOCKQUOTE', 'BODY', 'DD', 'DETAILS', 'DIV', 'DL', 'DT', 'FIELDSET', 'FIGCAPTION', 'FIGURE',
		'FOOTER', 'FORM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'HEADER', 'HR', 'IFRAME', 'LI', 'MAIN', 'NAV', 'OL', 'P', 'PRE', 'SCRIPT', 'SECTION',
		'STYLE', 'TABLE', 'UL', 'VIDEO'];
	const isBlockElement = (node) => node.nodeType === 1 && BLOCKS.indexOf(node.nodeName) !== -1;
	const COMMENT = 'joomla-easymde-comment';
	const RAW = 'joomla-easymde-raw';

	// Elements Turndown treats as blank when they have no content (the rest are void or "meaningful when blank")
	const BLANKABLE = (node) => !/^(AREA|BASE|BR|COL|COMMAND|EMBED|HR|IMG|INPUT|KEYGEN|LINK|META|PARAM|SOURCE|TRACK|WBR|A|TABLE|THEAD|TBODY|TFOOT|TH|TD|IFRAME|SCRIPT|AUDIO|VIDEO)$/.test(node.nodeName);

	// The HTML of an element which stays HTML, with the comments and empty elements its placeholders stand for put back
	const outer = (node) => {
		const copy = node.cloneNode(true);

		copy.querySelectorAll(`${COMMENT}, ${RAW}`).forEach((placeholder) => {
			if (placeholder.nodeName.toLowerCase() === COMMENT) {
				placeholder.replaceWith(placeholder.ownerDocument.createComment(placeholder.getAttribute('data-text')));
			} else {
				const template = placeholder.ownerDocument.createElement('template');

				template.innerHTML = placeholder.getAttribute('data-html');
				placeholder.replaceWith(template.content);
			}
		});

		return copy.outerHTML;
	};

	const attributesFit = (node, allowed) => Array.prototype.every.call(node.attributes, (attribute) => allowed.indexOf(attribute.name) !== -1);

	// An image's srcset of its copy for small screens and itself (photo-600.webp 600w, photo.webp 900w), as the editors' uploads
	// get: Markdown can't write it, but it follows from the image, so the image stays Markdown and toHtml() puts it back
	const hasOwnSources = (node) => {
		const candidates = (node.getAttribute('srcset') || '').split(',').map((candidate) => candidate.trim().split(/\s+/)[0]);

		return candidates.length === 2 && candidates[1] === node.getAttribute('src') && candidates[0].replace(/-\d+(\.[^./]+)$/, '$1') === candidates[1];
	};

	// A table becomes Markdown only when Markdown can say everything about it: a header row, plain cells, nothing else
	const tableFits = (table) => {
		const rows = Array.prototype.slice.call(table.rows);

		if (!rows.length || table.querySelector('caption, colgroup, col, tfoot, table') || table.querySelector(BLOCK_IN_CELL)) {
			return false;
		}

		if (Array.prototype.some.call(table.querySelectorAll('*'), (node) => !(node.nodeName.toLowerCase() in EXPRESSIBLE) || !attributesFit(node, EXPRESSIBLE[node.nodeName.toLowerCase()]))) {
			return false;
		}

		const head = rows[0];
		const width = head.cells.length;

		return Array.prototype.every.call(head.cells, (cell) => cell.nodeName === 'TH')
			&& rows.every((row, i) => row.cells.length === width && (i === 0 || Array.prototype.every.call(row.cells, (cell) => cell.nodeName === 'TD')));
	};

	const isEmptyParagraph = (node) => node.nodeName === 'P' && node.textContent.replace(/[\s\u00a0]/g, '') === ''
		&& Array.prototype.every.call(node.querySelectorAll('*'), (child) => child.nodeName === 'BR');

	const keepAsHtml = (node) => {
		const name = node.nodeName.toLowerCase();

		if (name === COMMENT || name === RAW) {
			return false;
		}

		// GitHub-style task list items: the gfm plugin writes them
		if (name === 'input' && node.type === 'checkbox' && node.parentNode && node.parentNode.nodeName === 'LI') {
			return false;
		}

		// A bare <span> says nothing: its content is enough
		if (name === 'span' && !node.attributes.length) {
			return false;
		}

		if (name === 'table') {
			return !tableFits(node);
		}

		// A code block's language (class="language-php") is written after its fence
		if (name === 'code' && node.parentNode && node.parentNode.nodeName === 'PRE') {
			return !attributesFit(node, ['class']) || (node.className !== '' && !/^language-\S+$/.test(node.className));
		}

		if (name === 'pre') {
			const code = node.firstChild;

			return !attributesFit(node, []) || node.childNodes.length !== 1 || !code || code.nodeName !== 'CODE';
		}

		// A paragraph holding nothing but an element which stays HTML and has no text after its tag (an image with a class or
		// a size, an iframe): that element's line would be an HTML block, not a paragraph, so the paragraph stays HTML too
		if (name === 'p' && !node.attributes.length) {
			// An empty paragraph (spacing: &nbsp; or a <br>), which Markdown has no way to write
			if (isEmptyParagraph(node)) {
				return true;
			}

			const children = Array.prototype.filter.call(node.childNodes, (child) => child.nodeType !== 3 || child.data.trim() !== '');
			const only = children.length === 1 && children[0].nodeType === 1 ? children[0] : null;

			return !!only && (!only.childNodes.length || isBlockElement(only)) && keepAsHtml(only);
		}

		if (name === 'img' && hasOwnSources(node)) {
			return !attributesFit(node, EXPRESSIBLE.img.concat(['srcset', 'sizes']));
		}

		return !(name in EXPRESSIBLE) || !attributesFit(node, EXPRESSIBLE[name]);
	};

	// Turndown drops blank elements before any rule sees them: empty paragraphs and empty elements with attributes (an anchor
	// <a id="top"></a>, <div class="clearfix"></div>) stay as they are
	const turndown = new TurndownService({
		headingStyle: 'atx',
		codeBlockStyle: 'fenced',
		hr: '---',
		bulletListMarker: '-',
		emDelimiter: '_',
		blankReplacement: (content, node) => {
			if (isEmptyParagraph(node) || node.attributes.length) {
				return node.isBlock ? `\n\n${outer(node)}\n\n` : outer(node);
			}

			return node.isBlock ? '\n\n' : '';
		},
	});

	if (turndownPluginGfm) {
		turndown.use(turndownPluginGfm.gfm);
	}

	// An inline element standing on its own in a block (no text beside it, e.g. a <cite> in a <blockquote>): Markdown would
	// wrap it in a paragraph, unless its opening tag is alone on its line, which makes the line raw HTML
	const standsAlone = (node) => {
		const parent = node.parentNode;
		const blank = (sibling) => !sibling || (sibling.nodeType === 3 && !sibling.data.trim()) || isBlockElement(sibling);
		let previous = node.previousSibling;
		let next = node.nextSibling;

		while (previous && previous.nodeType === 3 && !previous.data.trim()) {
			previous = previous.previousSibling;
		}

		while (next && next.nodeType === 3 && !next.data.trim()) {
			next = next.nextSibling;
		}

		return parent && ['BLOCKQUOTE', 'LI', 'DIV', 'BODY', 'TD', 'TH', 'FIGURE', 'SECTION', 'ARTICLE', 'ASIDE'].indexOf(parent.nodeName) !== -1
			&& blank(previous) && blank(next);
	};

	const asHtmlBlock = (html) => html.replace(/^(<[^>]+>)(?!\n)/, '$1\n');

	// Rules added later win, so these come after the plugin's
	turndown.addRule('joomlaKeepAsHtml', {
		filter: (node) => node.nodeType === 1 && keepAsHtml(node),
		replacement: (content, node) => {
			if (node.isBlock) {
				return `\n\n${outer(node)}\n\n`;
			}

			return standsAlone(node) ? `\n\n${asHtmlBlock(outer(node))}\n\n` : outer(node);
		},
	});

	turndown.addRule('joomlaComment', {
		filter: (node) => node.nodeName.toLowerCase() === COMMENT,
		replacement: (content, node) => `<!--${node.getAttribute('data-text')}-->`,
	});

	turndown.addRule('joomlaRaw', {
		filter: (node) => node.nodeName.toLowerCase() === RAW,
		replacement: (content, node) => (node.getAttribute('data-block') ? `\n\n${node.getAttribute('data-html')}\n\n` : node.getAttribute('data-html')),
	});

	// Text which looks like markup or an entity stays text: < before a letter, / ! or ?, & before an entity-like name
	const escape = turndown.escape.bind(turndown);

	turndown.escape = (text) => escape(text).replace(/&(?=#?[A-Za-z0-9]+;)/g, '&amp;').replace(/<(?=[A-Za-z\/!?])/g, '&lt;');

	/*
	 * sources (a Map, optional) collects the srcset and sizes of the images which stay Markdown, by address, for toHtml()
	 */
	const toMarkdown = (html, sources) => {
		const doc = new window.DOMParser().parseFromString(`<body>${html || ''}</body>`, 'text/html');
		const walker = doc.createTreeWalker(doc.body, window.NodeFilter.SHOW_COMMENT);
		const comments = [];

		if (sources) {
			doc.body.querySelectorAll('img[srcset]').forEach((image) => {
				if (hasOwnSources(image) && !keepAsHtml(image)) {
					sources.set(image.getAttribute('src'), { srcset: image.getAttribute('srcset'), sizes: image.getAttribute('sizes') || '' });
				}
			});
		}

		// Turndown drops comments: they go through as placeholder elements
		while (walker.nextNode()) {
			comments.push(walker.currentNode);
		}

		comments.forEach((comment) => {
			const placeholder = doc.createElement(COMMENT);

			// With text, as Turndown drops empty elements before any rule sees them
			placeholder.setAttribute('data-text', comment.data);
			placeholder.textContent = 'comment';
			comment.parentNode.replaceChild(placeholder, comment);
		});

		// Empty elements with attributes (<span class="icon-star"></span>, <div class="clearfix"></div>): Turndown would drop
		// them as blank and collapse the space after them, so they go through as placeholders with text too
		Array.prototype.filter.call(doc.body.querySelectorAll('*'), (node) => !node.childNodes.length && node.attributes.length && BLANKABLE(node)
			&& !node.closest('pre, code')).forEach((node) => {
			const placeholder = doc.createElement(RAW);

			placeholder.setAttribute('data-html', node.outerHTML);

			if (isBlockElement(node)) {
				placeholder.setAttribute('data-block', '1');
			}

			placeholder.textContent = 'element';
			node.parentNode.replaceChild(placeholder, node);
		});

		return turndown.turndown(doc.body);
	};

	const attribute = (value) => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	let currentSources = null;
	const parser = new marked.Marked({
		gfm: true,
		breaks: false,
		async: false,
		renderer: {
			image(token) {
				const html = marked.Renderer.prototype.image.call(this, token);
				const found = currentSources && currentSources.get(token.href);

				if (!found || !/^<img [^>]*>$/.test(html)) {
					return html;
				}

				return html.replace(/>$/, ` srcset="${attribute(found.srcset)}"${found.sizes ? ` sizes="${attribute(found.sizes)}"` : ''}>`);
			},
		},
	});

	/*
	 * sources (a Map, optional): srcset and sizes by image address, from toMarkdown() and the uploads
	 */
	const toHtml = (markdown, sources) => {
		currentSources = sources || null;

		const html = parser.parse(markdown || '');

		currentSources = null;

		// An empty paragraph marked leaves next to an HTML block
		return html.replace(/<p>\s*<\/p>\n?/g, '').trim();
	};

	window.JoomlaEasyMDE = window.JoomlaEasyMDE || {};
	window.JoomlaEasyMDE.convert = { toMarkdown, toHtml };
})(window);
