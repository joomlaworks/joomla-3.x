/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * jQuery plugins core no longer ships, as stand-ins on the CMS's own code (global.js), for extensions which still call
 * them; core never loads this file. It's loaded by JHtml::_('jquery.ui', array('sortable')) and by requests for
 * jquery.ui.sortable.js or jquery.autocomplete.js (JHtml::_('behavior.jquerycompat')).
 *
 * - jQuery UI's sortable: $(list).sortable(options) on CMS.sortable. Options items, handle, cancel, axis, placeholder,
 *   forcePlaceholderSize, disabled; events start, change, beforeStop, update, stop (callbacks and jQuery events
 *   sortstart...); methods option, disable, enable, destroy, toArray, serialize, refresh, instance, widget. Not done:
 *   dragging between lists (connectWith), helpers, containment, revert.
 * - Ajax Autocomplete for jQuery (devbridge): $(field).autocomplete(options) on CMS.suggest. Options serviceUrl, params,
 *   paramName, type, dataType, minChars, deferRequestBy, width, maxHeight, zIndex, lookup, lookupFilter, lookupLimit,
 *   transformResult, formatResult, onSelect, onSearchStart, onSearchComplete, onSearchError, autoSelectFirst, noCache,
 *   preventBadQueries, containerClass; methods setOptions, disable, enable, clear, clearCache, hide, dispose.
 *
 * Options it doesn't know are ignored, so an extension's script keeps running.
 */
(() => {
	'use strict';

	const $ = window.jQuery;
	const CMS = window.CMS;

	if (!$ || !CMS) {
		return;
	}

	// $(element).name(options) creates or updates an instance; $(element).name('method', ...) calls a method, whose value
	// (a getter's) is that of the first element, as jQuery UI's widgets do
	const bridge = (name, dataKey, Plugin) => {
		$.fn[name] = function (options, ...args) {
			if (typeof options === 'string') {
				let value;

				this.each(function () {
					const instance = $.data(this, dataKey);

					if (value === undefined && instance && options.charAt(0) !== '_' && typeof instance[options] === 'function') {
						const result = instance[options](...args);

						if (result !== instance && result !== undefined) {
							value = result;
						}
					}
				});

				return value !== undefined ? value : this;
			}

			return this.each(function () {
				const instance = $.data(this, dataKey);

				if (instance) {
					instance.option(options || {});
				} else {
					new Plugin(this, options || {});
				}
			});
		};
	};

	/* --- jQuery UI's sortable --- */

	class Sortable {
		constructor(element, options) {
			this.element = $(element);
			this.widgetName = 'sortable';
			this.widgetFullName = 'ui-sortable';
			this.options = $.extend({
				items: '> *', handle: false, cancel: 'input, textarea, button, select, option', axis: false, placeholder: false,
				forcePlaceholderSize: false, disabled: false,
			}, options);

			this.cms = CMS.sortable.attach(element, {
				items: (item) => this.isItem(item),
				onStart: (item, placeholder) => this.trigger('start', item, placeholder),
				onChange: (item, placeholder) => this.trigger('change', item, placeholder),
				onStop: (item, changed) => {
					this.trigger('beforeStop', item);

					if (changed) {
						this.trigger('update', item);
					}

					this.trigger('stop', item);
				},
			});

			this.sync();
			this.element.addClass('ui-sortable');
			$.data(element, 'ui-sortable', this);
			$.data(element, 'sortable', this);
		}

		isItem(element) {
			const items = this.options.items || '> *';
			const container = this.element[0];

			// Selectors from the list (> li): a child of it
			if (/^\s*>[^,>]*$/.test(items)) {
				return element.parentNode === container && $(element).is(items.replace(/^\s*>\s*/, '') || '*');
			}

			return this.element.find(items).index(element) > -1;
		}

		items() {
			return this.element.find(this.options.items || '> *').filter((index, element) => !$(element).hasClass('ui-sortable-placeholder') && !$(element).hasClass('cms-sort-placeholder'));
		}

		// The options CMS.sortable reads on every drag
		sync() {
			const o = this.options;

			$.extend(this.cms.options, {
				handle: o.handle || null,
				cancel: o.cancel || null,
				axis: o.axis === 'x' || o.axis === 'y' ? o.axis : null,
				placeholder: o.placeholder || null,
				forcePlaceholderSize: !!o.forcePlaceholderSize,
				disabled: !!o.disabled,
			});
			this.element.toggleClass('ui-sortable-disabled', !!o.disabled);
		}

		ui(item, placeholder) {
			const $item = $(item);

			return {
				item: $item, helper: $item, placeholder: $(placeholder || []), sender: null, offset: $item.offset(), position: $item.position(),
				originalPosition: $item.position(),
			};
		}

		trigger(type, item, placeholder) {
			const event = $.Event(('sort' + type).toLowerCase());
			const ui = this.ui(item, placeholder);
			const callback = this.options[type];

			this.element.trigger(event, ui);

			if (typeof callback === 'function') {
				callback.call(this.element[0], event, ui);
			}
		}

		option(name, value) {
			if (name === undefined) {
				return $.extend({}, this.options);
			}

			if (typeof name === 'string' && value === undefined) {
				return this.options[name];
			}

			$.extend(this.options, typeof name === 'string' ? { [name]: value } : name);
			this.sync();

			return this;
		}

		disable() {
			return this.option('disabled', true);
		}

		enable() {
			return this.option('disabled', false);
		}

		destroy() {
			this.cms.destroy();
			this.element.removeClass('ui-sortable ui-sortable-disabled');
			$.removeData(this.element[0], 'ui-sortable');
			$.removeData(this.element[0], 'sortable');
		}

		toArray(options) {
			const o = options || {};

			return this.items().map((index, element) => $(o.item || element).attr(o.attribute || 'id') || '').get();
		}

		serialize(options) {
			const o = options || {};
			const list = [];

			this.items().each((index, element) => {
				const match = ($(o.item || element).attr(o.attribute || 'id') || '').match(o.expression || /(.+)[\-=_](.+)/);

				if (match) {
					list.push((o.key || match[1] + '[]') + '=' + (o.key && o.expression ? match[1] : match[2]));
				}
			});

			if (!list.length && o.key) {
				list.push(o.key + '=');
			}

			return list.join('&');
		}

		// Positions are measured on every move: nothing to refresh
		refresh() {}

		refreshPositions() {}

		cancel() {}

		instance() {
			return this;
		}

		widget() {
			return this.element;
		}
	}

	if (!$.fn.sortable) {
		bridge('sortable', 'ui-sortable', Sortable);

		if ($.ui && !$.ui.sortable) {
			$.ui.sortable = Sortable;
		}
	}

	/* --- Ajax Autocomplete for jQuery --- */

	class Autocomplete {
		constructor(element, options) {
			this.element = element;
			this.options = {};
			this.suggest = CMS.suggest.attach(element, {});
			this.option(options);
			$.data(element, 'autocomplete', this);
		}

		// The plugin's option names, as CMS.suggest's
		option(options) {
			const o = $.extend(this.options, options);
			const transform = o.transformResult;

			this.suggest.setOptions({
				url: o.serviceUrl || '',
				params: o.params || {},
				paramName: o.paramName || 'query',
				type: o.type || 'GET',
				minChars: o.minChars !== undefined ? o.minChars : 1,
				delay: o.deferRequestBy || 0,
				width: o.width,
				maxHeight: o.maxHeight || 300,
				zIndex: o.zIndex || 9999,
				containerClass: o.containerClass || 'autocomplete-suggestions',
				lookup: o.lookup || null,
				lookupFilter: o.lookupFilter || null,
				lookupLimit: o.lookupLimit || 0,
				transformResult: (text, query) => {
					const response = o.dataType === 'json' || !transform ? JSON.parse(text) : text;

					return transform ? transform(response, query) : response;
				},
				formatResult: o.formatResult || null,
				onSelect: o.onSelect || null,
				onSearchStart: o.onSearchStart || null,
				onSearchComplete: o.onSearchComplete || null,
				onSearchError: o.onSearchError || null,
				autoSelectFirst: !!o.autoSelectFirst,
				noCache: !!o.noCache,
				preventBadQueries: o.preventBadQueries !== false,
			});

			return this;
		}

		setOptions(options) {
			return this.option(options);
		}

		disable() {
			this.suggest.disable();
		}

		enable() {
			this.suggest.enable();
		}

		clear() {
			this.suggest.clear();
		}

		clearCache() {
			this.suggest.clear();
		}

		hide() {
			this.suggest.hide();
		}

		dispose() {
			this.suggest.dispose();
			$.removeData(this.element, 'autocomplete');
		}
	}

	if (!$.fn.devbridgeAutocomplete) {
		bridge('devbridgeAutocomplete', 'autocomplete', Autocomplete);

		if (!$.fn.autocomplete) {
			$.fn.autocomplete = $.fn.devbridgeAutocomplete;
		}
	}
})();
