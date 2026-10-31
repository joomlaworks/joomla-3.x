/**
 * @copyright  (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

((window, document) => {
	'use strict';

	const cm = window.CodeMirror;
	const Joomla = window.Joomla = window.Joomla || {};

	if (!cm) {
		return;
	}

	Joomla.editors = Joomla.editors || {};
	Joomla.editors.instances = Joomla.editors.instances || {};

	// Editor buttons written for Joomla 3.6 and before insert through this
	window.jInsertEditorText = (text, editor) => Joomla.editors.instances[editor].replaceSelection(text);

	cm.commands.toggleFullScreen = (editor) => editor.setOption('fullScreen', !editor.getOption('fullScreen'));
	cm.commands.closeFullScreen = (editor) => {
		if (editor.getOption('fullScreen')) {
			editor.setOption('fullScreen', false);
		}
	};

	// Read when the editors start: global.js (Joomla.getOptions) may come after this file
	let configured = false;
	const configure = () => {
		const settings = (Joomla.getOptions && Joomla.getOptions('plg_editors_codemirror')) || {};

		configured = true;
		cm.keyMap.default['Ctrl-Q'] = 'toggleFullScreen';
		cm.keyMap.default[settings.fullScreen || 'F10'] = 'toggleFullScreen';
		cm.keyMap.default.Esc = 'closeFullScreen';

		if (settings.modeURL) {
			cm.modeURL = settings.modeURL;
		}
	};

	const makeMarker = () => {
		const marker = document.createElement('div');

		marker.className = 'CodeMirror-markergutter-mark';

		return marker;
	};

	cm.defineInitHook((editor) => {
		const name = editor.options.mode || '';
		const mode = cm.findModeByMIME(name) || cm.findModeByName(name) || cm.findModeByExtension(name);

		cm.autoLoadMode(editor, mode ? mode.mode : editor.options.mode);

		if (mode && mode.mime) {
			editor.setOption('mode', mode.mime);
		}

		editor.on('gutterClick', (ed, line, gutter) => {
			if (gutter !== 'CodeMirror-markergutter') {
				return;
			}

			const info = ed.lineInfo(line);
			const hasMarker = !!info.gutterMarkers && !!info.gutterMarkers['CodeMirror-markergutter'];

			ed.setGutterMarker(line, 'CodeMirror-markergutter', hasMarker ? null : makeMarker());
		});

		const wrapper = editor.getWrapperElement();

		// A fieldset around an editor would grow with its longest line
		if (wrapper.parentNode && wrapper.parentNode.tagName === 'FIELDSET') {
			wrapper.parentNode.style.minWidth = '0';
		}

		// Created in a hidden element (a tab, a slider, a modal): drawn again once it has a size
		if (window.ResizeObserver) {
			let hidden = wrapper.offsetWidth === 0;

			new ResizeObserver(() => {
				if (hidden && wrapper.offsetWidth) {
					editor.refresh();
				}

				hidden = wrapper.offsetWidth === 0;
			}).observe(wrapper);
		}
	});

	const start = (container) => {
		if (!configured) {
			configure();
		}

		(container || document).querySelectorAll('textarea.codemirror-source').forEach((textarea) => {
			let options = {};

			textarea.classList.remove('codemirror-source');

			try {
				options = JSON.parse(textarea.getAttribute('data-options') || '{}');
			} catch (error) {
				options = {};
			}

			const editor = cm.fromTextArea(textarea, options);

			Joomla.editors.instances[textarea.id] = editor;

			// The Height option, over the template's (Isis and Prothero size it to the window, 400 to 800 pixels) and CodeMirror's
			if (textarea.getAttribute('data-height')) {
				const wrapper = editor.getWrapperElement();

				wrapper.style.minHeight = '0';
				wrapper.style.maxHeight = 'none';
				editor.setSize(null, textarea.getAttribute('data-height'));
			}
		});
	};

	// New subform rows (the subform always sends cms:subform-row-add natively)
	document.addEventListener('cms:subform-row-add', (event) => start(event.detail && event.detail.row));

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => start());
	} else {
		start();
	}
})(window, document);
