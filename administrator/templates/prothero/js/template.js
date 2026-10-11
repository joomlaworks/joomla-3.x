/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.prothero
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 *
 * Prothero's script, plain JavaScript, for every page: the administrator's (button groups, coloured status lists, the admin
 * menu's scrolling submenus, the pinned toolbar, the list sidebar, scrolling tab rows) and the login page's (show/hide the
 * password, close messages, mark the form busy). It does what Isis' jQuery script did, with the same markup.
 *
 * Some core scripts announce their events through jQuery only (Chosen's "liszt:ready" and its changes, subforms'
 * "subform-row-add"), which listeners added with addEventListener never receive: those are heard through jQuery when the page
 * has it (it always has where those scripts run). Bootstrap's tooltips are a jQuery plugin too and are set up the same way.
 */
(function () {
	'use strict';

	var $ = function (selector, context) { return (context || document).querySelector(selector); };
	var $$ = function (selector, context) { return Array.prototype.slice.call((context || document).querySelectorAll(selector)); };
	var jq = function () { return window.jQuery && window.jQuery.fn ? window.jQuery : null; };
	var height = function (el) { return el ? el.getBoundingClientRect().height : 0; };

	/* --- Button groups (Yes/No and other radio groups) --- */

	// The chosen option's label gets "active" and a colour class: danger for 0 (success with btn-group-reversed), else success
	function colourLabels(input) {
		var group = input.closest('.btn-group');
		var reversed = !!(group && group.classList.contains('btn-group-reversed'));
		var labels = [];

		if (input.closest('label')) {
			labels.push(input.closest('label'));
		}

		if (input.id) {
			labels = labels.concat($$('label[for="' + input.id + '"]'));
		}

		labels.forEach(function (label) {
			label.classList.remove('active', 'btn-success', 'btn-danger', 'btn-primary');

			if (input.checked) {
				var cls = 'primary';

				if (input.value !== '') {
					cls = (input.value == 0 ? !reversed : reversed) ? 'danger' : 'success';
				}

				label.classList.add('active', 'btn-' + cls);
			}
		});
	}

	document.addEventListener('change', function (event) {
		var input = event.target;

		if (input.type === 'radio' && input.closest('.btn-group')) {
			var group = input.closest('.btn-group');

			$$('input[type="radio"]', group).forEach(function (radio) {
				if (radio.name === input.name) {
					colourLabels(radio);
				}
			});
		}
	});

	/* --- Status lists (Chosen select boxes coloured by their value) --- */

	function colourChosen(select) {
		var single = select.nextElementSibling && select.nextElementSibling.classList.contains('chzn-container') ? $('.chzn-single', select.nextElementSibling) : null;
		var cls = (select.className.match(/(^|\s)(chzn-color[a-z0-9_-]*)/) || [])[2];

		if (single && cls) {
			single.classList.add(cls);
			single.setAttribute('rel', 'value_' + select.value);
		}
	}

	function colourAllChosen() {
		$$('select[class^="chzn-color"], select[class*=" chzn-color"]').forEach(colourChosen);
	}

	// A choice made in a list (click or keyboard) updates its colour once Chosen has set the select's value
	['click', 'keyup'].forEach(function (type) {
		document.addEventListener(type, function (event) {
			var container = event.target.closest && event.target.closest('.chzn-container');

			if (container && container.previousElementSibling && container.previousElementSibling.tagName === 'SELECT') {
				var select = container.previousElementSibling;

				setTimeout(function () { colourChosen(select); }, 0);
			}
		});
	});

	/* --- Set up a page, or a part of it a subform just added --- */

	function initTemplate(container) {
		container = container || document;

		// Tooltips (Bootstrap's plugin)
		if (jq() && jq().fn.tooltip) {
			jq()($$('[rel="tooltip"]', container)).tooltip();
		}

		// Radios into button groups
		$$('.radio.btn-group label', container).forEach(function (label) {
			label.classList.add('btn');
		});

		// Disabled groups: no clicks, buttons styled disabled
		$$('fieldset.btn-group', container).forEach(function (fieldset) {
			if (fieldset.disabled) {
				fieldset.style.pointerEvents = 'none';
				$$('.btn', fieldset).forEach(function (btn) { btn.classList.add('disabled'); });
			}
		});

		// The chosen options of button groups
		$$('.btn-group input:checked', container).forEach(colourLabels);
	}

	/* --- The admin menu: submenus too long for the screen scroll, their children shown beside them (#nav-empty) --- */

	function initMenu() {
		var menu = $('#menu');
		var emptyMenu = $('#nav-empty');
		var status = $('#status');

		if (!menu || !emptyMenu || window.innerWidth <= 767) {
			return;
		}

		var hideEmpty = function () {
			emptyMenu.innerHTML = '';
			emptyMenu.style.display = 'none';
		};

		// A dropdown no taller than the screen above the status bar, scrolling (and a little wider for the scrollbar) when it's longer
		var fit = function (item) {
			var dropdown = item.querySelector(':scope > ul');

			if (!dropdown || !dropdown.offsetWidth) {
				return;
			}

			dropdown.style.width = '';
			dropdown.style.maxHeight = '';

			var maxHeight = window.innerHeight - dropdown.getBoundingClientRect().top - height(status) - 20;

			if (dropdown.scrollHeight > maxHeight) {
				dropdown.style.width = (dropdown.offsetWidth + 15) + 'px';
			}

			dropdown.style.maxHeight = maxHeight + 'px';
		};

		$$('#menu > li').forEach(function (item) {
			var open = function () {
				hideEmpty();

				// Measured once Bootstrap has opened it (its click handler runs after this one)
				setTimeout(function () { fit(item); }, 0);
			};

			item.addEventListener('click', open);
			item.addEventListener('mouseenter', open);
		});

		$$('#menu > li > ul .dropdown-submenu > a').forEach(function (link) {
			link.addEventListener('mouseover', function () {
				var dropdown = link.nextElementSibling;
				var parent = link.closest('ul');

				if (!dropdown || dropdown.tagName !== 'UL' || !parent) {
					return;
				}

				// Beside the dropdown the pointer is in, measured now that it's open, its first entry level with the link. #nav-empty is
				// positioned in its offset parent (the bar's container), so screen coordinates are taken back to it
				var parentBox = parent.getBoundingClientRect();
				var rtl = document.documentElement.getAttribute('dir') === 'rtl';
				var gap = 4;

				dropdown.style.display = 'none';
				emptyMenu.innerHTML = dropdown.innerHTML;
				emptyMenu.style.display = 'block';

				var origin = (emptyMenu.offsetParent || document.body).getBoundingClientRect();
				var style = getComputedStyle(emptyMenu);
				var first = emptyMenu.querySelector('li > a');
				var inset = first ? first.getBoundingClientRect().top - emptyMenu.getBoundingClientRect().top : 0;
				var top = link.getBoundingClientRect().top - inset;

				// Keep it on the screen, above the status bar
				top = Math.max(0, Math.min(top, window.innerHeight - height(status) - emptyMenu.offsetHeight - 8));

				emptyMenu.style.top = (top - origin.top - parseFloat(style.marginTop)) + 'px';
				emptyMenu.style.left = ((rtl ? parentBox.left - gap - emptyMenu.offsetWidth : parentBox.right + gap) - origin.left - parseFloat(style.marginLeft)) + 'px';
			});
		});

		$$('#menu > li > ul a.no-dropdown').forEach(function (link) {
			link.addEventListener('mouseenter', hideEmpty);
		});

		// When the menus close (Bootstrap closes them on any click), the copied submenu goes too
		document.addEventListener('click', function () {
			setTimeout(function () {
				if (!$('#menu > li.open')) {
					hideEmpty();
				}
			}, 0);
		});
	}

	/* --- The phone menu: shown or hidden at once (Bootstrap's collapse animates its height, which looked choppy) --- */

	function initMenuButton() {
		var button = $('.navbar .btn-navbar');
		var menu = $('.navbar .nav-collapse');

		if (!button || !menu) {
			return;
		}

		button.setAttribute('aria-expanded', 'false');

		// Caught before Bootstrap's handler on the document, which then never sees the click
		button.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();

			var open = !menu.classList.contains('in');

			menu.classList.toggle('in', open);
			menu.style.height = '';
			button.classList.toggle('collapsed', !open);
			button.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	/* --- The pinned toolbar --- */

	function initStickyToolbar() {
		var data = $('#isisJsData');
		var subhead = $('.subhead');
		var navbar = $('nav.navbar');
		var main = $('.container-main');
		var navTop = 0;
		var fixed = false;

		if (!data || data.getAttribute('data-tmpl-sticky') !== 'true' || !subhead) {
			return;
		}

		var measure = function () {
			navTop = subhead.getBoundingClientRect().top + window.pageYOffset - parseInt(data.getAttribute('data-tmpl-offset'), 10);

			if (main) {
				main.style.top = (height(subhead) + height(navbar)) + 'px';
			}

			// Keep the toolbar's place while it's pinned, when it isn't collapsed
			if (document.body.clientWidth > 480) {
				var collapse = $('.subhead-collapse');

				if (collapse) {
					collapse.style.height = height(subhead) + 'px';
				}
			}
		};

		var update = function () {
			var scrollTop = window.pageYOffset;

			if (scrollTop >= navTop && !fixed) {
				fixed = true;
				subhead.classList.add('subhead-fixed');

				if (main) {
					main.style.top = (height(subhead) + height(navbar)) + 'px';
				}
			} else if (scrollTop <= navTop && fixed) {
				fixed = false;
				subhead.classList.remove('subhead-fixed');
			}
		};

		measure();
		update();
		window.addEventListener('resize', measure);
		window.addEventListener('scroll', update, { passive: true });
	}

	/* --- The list sidebar: hidden or shown, remembered (layouts/joomla/sidebars call it, and its toggle button) --- */

	window.toggleSidebar = function (force) {
		var sidebar = $('#j-sidebar-container');
		var main = $('#j-main-container');
		var message = $('#system-message-container');
		var debug = $('#system-debug');
		var icon = $('#j-toggle-sidebar-icon');
		var buttonWrapper = $('#j-toggle-button-wrapper');
		var button = $('#j-toggle-sidebar-button');
		var toggle = $('#j-toggle-sidebar');
		var rtl = document.documentElement.getAttribute('dir') === 'rtl';
		var openIcon = rtl ? 'icon-arrow-right-2' : 'icon-arrow-left-2';
		var closedIcon = rtl ? 'icon-arrow-left-2' : 'icon-arrow-right-2';
		var isComponent = document.body.classList.contains('component');
		var cls = function (el, add, remove) {
			if (el) {
				if (remove) { el.classList.remove.apply(el.classList, remove.split(' ')); }
				if (add) { el.classList.add.apply(el.classList, add.split(' ')); }
			}
		};
		var store = function (value) {
			try { localStorage.setItem('jsidebar', value); } catch (e) {}
		};

		if (!sidebar) {
			return;
		}

		// Visible now: the toggle area is shown
		var visible = !!(toggle && toggle.getClientRects().length);

		cls(sidebar, 'j-sidebar-container', 'span2');
		cls(message, 'j-toggle-main');
		cls(main, 'j-toggle-main');

		if (!isComponent) {
			cls(debug, 'j-toggle-main');
		}

		var bodyWidth = document.body.offsetWidth;
		var content = $('#content');
		var contentWidth = content ? content.offsetWidth : bodyWidth;
		var contentRelative = contentWidth / bodyWidth * 100;
		var mainRelative = (contentWidth - sidebar.offsetWidth) / bodyWidth * 100;
		var mainHeight = main ? main.offsetHeight + 30 : 0;

		if (force) {
			// The last choice
			try { visible = localStorage.getItem('jsidebar') === 'true'; } catch (e) { visible = false; }
		} else {
			[message, sidebar, buttonWrapper, main].concat(isComponent ? [] : [debug]).forEach(function (el) {
				cls(el, 'j-toggle-transition');
			});
		}

		var text = function (key) { return window.Joomla && Joomla.JText ? Joomla.JText._(key) : ''; };
		var tabindex = function (remove) {
			$$('a, input, select, textarea, button', sidebar).forEach(function (el) {
				if (remove) { el.removeAttribute('tabindex'); } else { el.setAttribute('tabindex', '-1'); }
			});
		};

		if (visible) {
			if (toggle) { toggle.style.display = 'none'; }
			cls(sidebar, 'j-sidebar-hidden', 'j-sidebar-visible');
			cls(buttonWrapper, 'j-toggle-hidden', 'j-toggle-visible');
			cls(icon, 'j-toggle-hidden ' + closedIcon, 'j-toggle-visible ' + openIcon);
			cls(message, 'span12', 'span10');
			cls(main, 'span12 expanded', 'span10');
			if (button) { button.setAttribute('data-original-title', text('JTOGGLE_SHOW_SIDEBAR')); }
			sidebar.setAttribute('aria-hidden', 'true');
			tabindex(false);

			if (!isComponent && debug) {
				debug.style.width = contentRelative + '%';
			}

			store('true');
		} else {
			if (toggle) { toggle.style.display = ''; }
			cls(sidebar, 'j-sidebar-visible', 'j-sidebar-hidden');
			cls(buttonWrapper, 'j-toggle-visible', 'j-toggle-hidden');
			cls(icon, 'j-toggle-visible ' + openIcon, 'j-toggle-hidden ' + closedIcon);
			cls(message, 'span10', 'span12');
			cls(main, 'span10', 'span12 expanded');
			if (button) { button.setAttribute('data-original-title', text('JTOGGLE_HIDE_SIDEBAR')); }
			sidebar.removeAttribute('aria-hidden');
			tabindex(true);

			if (!isComponent && debug) {
				debug.style.width = (bodyWidth > 768 && mainHeight < sidebar.offsetHeight ? mainRelative : contentRelative) + '%';
			}

			store('false');
		}
	};

	/* --- Tab rows (and the toolbar on phones) that don't fit scroll sideways (css/template.css): the side with more gets a fade --- */

	function initTabRows() {
		var rows = $$('.nav-tabs:not(.nav-stacked), #toolbar').filter(function (row) {
			return !row.closest('.tabs-left, .tabs-right');
		});

		var update = function (row) {
			var left = Math.abs(row.scrollLeft);

			row.classList.toggle('tabs-more-end', row.scrollWidth - row.clientWidth - left > 2);
			row.classList.toggle('tabs-more-start', left > 2);
			row.classList.toggle('tabs-rtl', getComputedStyle(row).direction === 'rtl');
		};

		rows.forEach(function (row) {
			var active = $('li.active', row);

			// The active tab in view
			if (active && row.scrollWidth > row.clientWidth) {
				row.scrollLeft = active.offsetLeft - row.offsetLeft - 16;
			}

			update(row);
			row.addEventListener('scroll', function () { update(row); }, { passive: true });
			row.addEventListener('click', function () { setTimeout(function () { update(row); }, 0); });
		});

		window.addEventListener('resize', function () { rows.forEach(update); });
	}

	/* --- The login page --- */

	function initLogin() {
		$$('.loginReveal').forEach(function (button) {
			var input = document.getElementById(button.getAttribute('aria-controls'));
			var label = $('.loginReveal-label', button);

			if (!input) {
				return;
			}

			button.hidden = false;

			button.addEventListener('click', function () {
				var show = input.type === 'password';

				input.type = show ? 'text' : 'password';
				button.setAttribute('aria-pressed', show ? 'true' : 'false');
				label.textContent = button.getAttribute(show ? 'data-hide' : 'data-show');
				input.focus();
			});
		});

		// The message layout's close buttons are meant for Bootstrap's script, which the login page doesn't load
		document.addEventListener('click', function (event) {
			var close = event.target.closest('#system-message-container .close');

			if (close && close.parentNode) {
				close.parentNode.remove();
			}
		});

		var form = $('#form-login');

		if (form) {
			form.addEventListener('submit', function () {
				var submit = $('.loginSubmit', form);

				// Never shown back after a successful login; a failed one reloads the page
				if (submit) {
					submit.setAttribute('aria-busy', 'true');
				}

				// The password stays hidden if the browser restores the page from its cache
				$$('.loginReveal[aria-pressed="true"]', form).forEach(function (button) {
					button.click();
				});
			});
		}
	}

	/* --- Start --- */

	function init() {
		if (document.body.classList.contains('loginPage')) {
			initLogin();

			return;
		}

		initTemplate(document);
		colourAllChosen();
		initMenu();
		initMenuButton();
		initStickyToolbar();
		initTabRows();

		// Events only jQuery hears: Chosen ready (and changed through its API), subform rows added
		if (jq()) {
			jq()(document.body)
				.on('liszt:ready chosen:ready change', 'select', function () { colourChosen(this); })
				.on('subform-row-add', function (event, row) { initTemplate(row && row.nodeType ? row : (row && row[0]) || document); });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
