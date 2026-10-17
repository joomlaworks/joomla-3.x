/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * HTML ↔ Markdown for the EasyMDE editor. Articles stay HTML in the database (the site, modules, feeds and search read
 * them), so the editor turns the HTML into Markdown when it opens and back when the form is saved. Whatever Markdown can't
 * express (an element it has no syntax for, an attribute it can't write, a comment) stays HTML inside the Markdown, as it
 * was, which Markdown allows: nothing is lost on the way.
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

	const attributesFit = (node, allowed) => Array.prototype.every.call(node.attributes, (attribute) => allowed.indexOf(attribute.name) !== -1);

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

	const keepAsHtml = (node) => {
		const name = node.nodeName.toLowerCase();

		if (name === COMMENT) {
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

		return !(name in EXPRESSIBLE) || !attributesFit(node, EXPRESSIBLE[name]);
	};

	const turndown = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', hr: '---', bulletListMarker: '-', emDelimiter: '_' });

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
				return `\n\n${node.outerHTML}\n\n`;
			}

			return standsAlone(node) ? `\n\n${asHtmlBlock(node.outerHTML)}\n\n` : node.outerHTML;
		},
	});

	turndown.addRule('joomlaComment', {
		filter: (node) => node.nodeName.toLowerCase() === COMMENT,
		replacement: (content, node) => `<!--${node.getAttribute('data-text')}-->`,
	});

	// Text which looks like markup or an entity stays text: < before a letter, / ! or ?, & before an entity-like name
	const escape = turndown.escape.bind(turndown);

	turndown.escape = (text) => escape(text).replace(/&(?=#?[A-Za-z0-9]+;)/g, '&amp;').replace(/<(?=[A-Za-z\/!?])/g, '&lt;');

	const toMarkdown = (html) => {
		const doc = new window.DOMParser().parseFromString(`<body>${html || ''}</body>`, 'text/html');
		const walker = doc.createTreeWalker(doc.body, window.NodeFilter.SHOW_COMMENT);
		const comments = [];

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

		return turndown.turndown(doc.body);
	};

	const toHtml = (markdown) => {
		const html = marked.parse(markdown || '', { gfm: true, breaks: false, async: false });

		// An empty paragraph marked leaves next to an HTML block
		return html.replace(/<p>\s*<\/p>\n?/g, '').trim();
	};

	window.JoomlaEasyMDE = window.JoomlaEasyMDE || {};
	window.JoomlaEasyMDE.convert = { toMarkdown, toHtml };
})(window);
