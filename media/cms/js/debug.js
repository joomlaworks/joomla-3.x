/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 *
 * The debug console (plugins/system/debug) as a panel: a button fixed at the bottom right with the page's time, memory, queries and
 * errors opens it, docked to the bottom of the window, its sections as tabs. Open or closed, the tab and the height are remembered.
 * Without this script the console stays at the end of the page.
 */
(function () {
	'use strict';

	var KEY = 'joomla-debug-console';

	var load = function () {
		try {
			return JSON.parse(localStorage.getItem(KEY)) || {};
		} catch (e) {
			return {};
		}
	};

	var save = function (state) {
		try {
			localStorage.setItem(KEY, JSON.stringify(state));
		} catch (e) {}
	};

	var make = function (tag, className, text) {
		var el = document.createElement(tag);

		if (className) {
			el.className = className;
		}

		if (text !== undefined) {
			el.textContent = text;
		}

		return el;
	};

	var bytes = function (value) {
		var mb = value / 1048576;

		return (mb >= 10 ? Math.round(mb) : mb.toFixed(1)) + ' MB';
	};

	function init() {
		var debug = document.getElementById('system-debug');

		if (!debug || debug.classList.contains('dbg-panel')) {
			return;
		}

		var state = load();
		var data = debug.dataset;
		var root = document.documentElement;

		// The sections: each top-level header and the container after it (extensions add theirs the same way)
		var sections = [];

		Array.prototype.forEach.call(debug.children, function (header) {
			var container = header.nextElementSibling;

			if (header.classList.contains('dbg-header') && container && container.classList.contains('dbg-container')) {
				sections.push({ header: header, container: container });
			}
		});

		if (!sections.length) {
			return;
		}

		// The panel's frame: a bar with the title, the tabs and the close button; the sections below it; a handle to resize it
		var handle = make('div', 'dbg-resize');
		var bar = make('div', 'dbg-bar-top');
		var title = make('strong', 'dbg-title', data.label || '');
		var tabs = make('div', 'dbg-tabs');
		var close = make('button', 'dbg-close');
		var body = make('div', 'dbg-body');

		tabs.setAttribute('role', 'tablist');
		close.type = 'button';
		close.setAttribute('aria-label', data.close || 'Close');
		close.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

		bar.appendChild(title);
		bar.appendChild(tabs);
		bar.appendChild(close);

		var buttons = sections.map(function (section, i) {
			var id = section.container.id;
			var tab = make('button', 'dbg-tab');
			var count = section.header.getAttribute('data-count');

			tab.type = 'button';
			tab.setAttribute('role', 'tab');
			tab.setAttribute('aria-controls', id);
			tab.appendChild(document.createTextNode(section.header.textContent.trim()));

			if (count !== null && count !== '') {
				tab.appendChild(make('span', 'dbg-count', count));
			}

			if (section.header.classList.contains('dbg-error')) {
				tab.classList.add('dbg-tab-error');
			}

			section.container.setAttribute('role', 'tabpanel');
			section.header.hidden = true;
			body.appendChild(section.container);

			tab.addEventListener('click', function () {
				show(i);
			});

			tabs.appendChild(tab);

			return tab;
		});

		// The title and the original headers are replaced by the bar
		Array.prototype.forEach.call(debug.querySelectorAll(':scope > h2'), function (h2) {
			h2.hidden = true;
		});

		debug.appendChild(handle);
		debug.appendChild(bar);
		debug.appendChild(body);

		var show = function (index) {
			sections.forEach(function (section, i) {
				var active = i === index;

				section.container.style.display = active ? 'block' : 'none';
				buttons[i].setAttribute('aria-selected', active ? 'true' : 'false');
				buttons[i].tabIndex = active ? 0 : -1;
			});

			if (debug.classList.contains('dbg-open')) {
				buttons[index].scrollIntoView({ block: 'nearest', inline: 'nearest' });
			}

			state.tab = sections[index].container.id;
			save(state);
		};

		// The button: what went wrong first, then time, memory and queries
		var launcher = make('button', 'dbg-launcher');
		var errors = parseInt(data.errors || '0', 10);

		launcher.type = 'button';
		launcher.setAttribute('aria-controls', 'system-debug');
		launcher.setAttribute('aria-expanded', 'false');
		launcher.title = data.label || '';
		launcher.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M8 2v4M16 2v4M5 10h14M7 6h10a2 2 0 0 1 2 2v9a5 5 0 0 1-5 5h-4a5 5 0 0 1-5-5V8a2 2 0 0 1 2-2zM2 14h3M19 14h3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		if (errors > 0) {
			launcher.appendChild(make('span', 'dbg-launcher-errors', String(errors)));
		}

		if (data.time && data.time !== '0') {
			launcher.appendChild(make('span', 'dbg-launcher-item', data.time + ' ms'));
		}

		if (data.memory) {
			launcher.appendChild(make('span', 'dbg-launcher-item', bytes(parseInt(data.memory, 10))));
		}

		if (data.queries && data.queries !== '0') {
			launcher.appendChild(make('span', 'dbg-launcher-item dbg-launcher-queries', data.queries + ' SQL'));
		}

		document.body.appendChild(launcher);

		// Out of the page's flow: a panel, hidden until opened
		debug.classList.add('dbg-panel');
		root.classList.add('dbg-enhanced');

		// Above a status bar fixed at the bottom (Prothero's, Isis')
		var place = function () {
			var status = document.getElementById('status');
			var offset = 0;

			if (status && getComputedStyle(status).position === 'fixed' && status.offsetHeight) {
				offset = status.offsetHeight;
			}

			root.style.setProperty('--dbg-bottom', offset + 'px');
		};

		var setHeight = function (px) {
			var max = window.innerHeight - 40;
			var height = Math.max(160, Math.min(px, max));

			debug.style.height = height + 'px';

			return height;
		};

		var open = function (yes, quiet) {
			debug.classList.toggle('dbg-open', yes);
			launcher.setAttribute('aria-expanded', yes ? 'true' : 'false');
			launcher.hidden = yes;
			state.open = yes;
			save(state);

			if (yes) {
				place();
				setHeight(state.height || Math.round(window.innerHeight * (window.innerWidth < 768 ? 0.8 : 0.6)));
				if (!quiet) {
					close.focus({ preventScroll: true });
				}
			} else if (!quiet) {
				launcher.focus({ preventScroll: true });
			}
		};

		launcher.addEventListener('click', function () {
			open(true);
		});

		close.addEventListener('click', function () {
			open(false);
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && debug.classList.contains('dbg-open')) {
				open(false);
			}
		});

		// Arrow keys move between the tabs
		tabs.addEventListener('keydown', function (event) {
			var current = buttons.indexOf(document.activeElement);

			if (current < 0 || (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft')) {
				return;
			}

			var rtl = getComputedStyle(tabs).direction === 'rtl';
			var step = (event.key === 'ArrowRight') !== rtl ? 1 : -1;
			var next = (current + step + buttons.length) % buttons.length;

			show(next);
			buttons[next].focus();
			event.preventDefault();
		});

		// Resizing: drag the top edge
		handle.addEventListener('pointerdown', function (event) {
			var startY = event.clientY;
			var startHeight = debug.offsetHeight;

			handle.setPointerCapture(event.pointerId);
			debug.classList.add('dbg-resizing');

			var move = function (e) {
				state.height = setHeight(startHeight + (startY - e.clientY));
			};

			var up = function () {
				handle.removeEventListener('pointermove', move);
				handle.removeEventListener('pointerup', up);
				debug.classList.remove('dbg-resizing');
				save(state);
			};

			handle.addEventListener('pointermove', move);
			handle.addEventListener('pointerup', up);
			event.preventDefault();
		});

		// Links to a part of another section (e.g. a profile bar to its query) open that section first
		debug.addEventListener('click', function (event) {
			var link = event.target.closest('a[href^="#"]');
			var target = link && link.getAttribute('href').length > 1 ? debug.querySelector('[id="' + link.getAttribute('href').slice(1) + '"], [name="' + link.getAttribute('href').slice(1) + '"]') : null;

			if (!target) {
				return;
			}

			sections.forEach(function (section, i) {
				if (section.container.contains(target) && section.container.style.display === 'none') {
					show(i);
				}
			});

			target.scrollIntoView({ block: 'start' });
			event.preventDefault();
		});

		window.addEventListener('resize', function () {
			place();

			if (debug.classList.contains('dbg-open')) {
				setHeight(debug.offsetHeight);
			}
		});

		var first = 0;

		sections.forEach(function (section, i) {
			if (section.container.id === state.tab) {
				first = i;
			}
		});

		place();
		show(first);

		if (state.open) {
			open(true, true);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
