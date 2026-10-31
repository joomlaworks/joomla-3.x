/**
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/**
 * The language override form's search: finds known language strings (refreshing their cache first when it expired) and
 * copies the chosen one into the form. Results are shown and copied as text, exactly as the language files have them.
 * Requests carry the form token (X-CSRF-Token, the page's csrf.token option).
 */
(function (window, document, Joomla) {
	'use strict';

	const byId = (id) => document.getElementById(id);
	const show = (element, shown) => {
		if (element) {
			element.style.display = shown ? 'block' : 'none';
		}
	};

	const post = (task, data) => fetch('index.php?option=com_languages&task=strings.' + task + '&format=json', {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'X-CSRF-Token': Joomla.getOptions('csrf.token', ''),
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: new URLSearchParams(data || {}).toString(),
	}).then((response) => (response.ok ? response.json() : Promise.reject(response)));

	// The answer's own messages and error
	const report = (answer) => {
		if (answer.error && answer.message) {
			window.alert(answer.message);
		}

		if (answer.messages) {
			Joomla.renderMessages(answer.messages);
		}
	};

	const failed = () => window.alert(Joomla.JText._('COM_LANGUAGES_VIEW_OVERRIDE_REQUEST_ERROR'));

	Joomla.overrider = {
		states: {
			refreshing: false,
			refreshed: false,
			counter: 0,
			searchstring: '',
			searchtype: 'value',
		},

		// The results shown, by id (counter + index)
		results: {},

		// Refreshes the database cache of known language strings
		refreshCache() {
			const status = byId('refresh-status');

			this.states.refreshing = true;
			show(status, true);

			return post('refresh')
				.then(report, failed)
				.then(() => {
					show(status, false);
					this.states.refreshing = false;
				});
		},

		/**
		 * Searches the known language strings
		 *
		 * @param  {number}  more  The limit start of further results (none: a new search)
		 */
		searchStrings(more) {
			const field = byId('jform_searchstring');
			const type = byId('jform_searchtype');
			const container = byId('results-container');
			const moreLink = byId('more-results');

			if (this.states.refreshing) {
				return;
			}

			// A new search takes the form's values; "more" goes on with the last search
			if (!more) {
				this.states.searchstring = field ? field.value : '';
				this.states.searchtype = type ? type.value : 'value';
			}

			if (!this.states.searchstring) {
				if (field) {
					field.classList.add('invalid');
				}

				return;
			}

			if (more) {
				moreLink.classList.add('overrider-spinner');
			} else {
				show(moreLink, false);
				container.querySelectorAll('div.language-results').forEach((results) => results.remove());
				container.classList.add('overrider-spinner');
				show(container, true);
			}

			post('search', { searchstring: this.states.searchstring, searchtype: this.states.searchtype, more: more || '' })
				.then((answer) => {
					report(answer);

					if (answer.data) {
						if (answer.data.results) {
							this.insertResults(answer.data.results);
						}

						this.states.more = answer.data.more;
						show(moreLink, Boolean(answer.data.more));
					}
				}, failed)
				.then(() => {
					container.classList.remove('overrider-spinner');
					moreLink.classList.remove('overrider-spinner');
				});
		},

		/**
		 * Shows results before the "more" link
		 *
		 * @param  {Array}  results  {constant, string, file}
		 */
		insertResults(results) {
			const list = document.createElement('div');

			this.states.counter += 1;
			list.id = 'language-results' + this.states.counter;
			list.className = 'language-results';

			results.forEach((item, index) => {
				const id = String(this.states.counter) + index;
				const row = document.createElement('div');
				const key = document.createElement('div');
				const string = document.createElement('div');

				this.results[id] = item;
				row.className = 'result row' + (index % 2);
				row.setAttribute('role', 'button');
				row.tabIndex = 0;
				row.setAttribute('data-result', id);
				key.id = 'override_key' + id;
				key.className = 'result-key';
				key.textContent = item.constant;
				key.title = item.file || '';
				string.id = 'override_string' + id;
				string.className = 'result-string';
				string.textContent = item.string;
				row.appendChild(key);
				row.appendChild(string);
				list.appendChild(row);
			});

			if (!results.length) {
				const none = document.createElement('div');

				none.textContent = Joomla.JText._('COM_LANGUAGES_VIEW_OVERRIDE_NO_RESULTS');
				list.appendChild(none);
			}

			byId('more-results').before(list);
		},

		// Copies a result into the form
		selectString(id) {
			const item = this.results[id];

			if (item) {
				byId('jform_key').value = item.constant;
				byId('jform_override').value = item.string;
				window.scrollTo(0, 0);
			}
		},
	};

	document.addEventListener('DOMContentLoaded', () => {
		const field = byId('jform_searchstring');
		const expired = (Joomla.getOptions('com_languages.override') || {}).expired;
		const choose = (event) => {
			const row = event.target.closest ? event.target.closest('[data-result]') : null;

			if (row && (event.type === 'click' || event.key === 'Enter' || event.key === ' ')) {
				event.preventDefault();
				Joomla.overrider.selectString(row.getAttribute('data-result'));
			}
		};

		// The cache of known strings is refreshed once, when the search field is first used, if it expired
		if (field) {
			field.addEventListener('focus', () => {
				if (!Joomla.overrider.states.refreshed && expired) {
					Joomla.overrider.states.refreshed = true;
					Joomla.overrider.refreshCache();
				}

				field.classList.remove('invalid');
			});
		}

		document.addEventListener('click', choose);
		document.addEventListener('keydown', choose);
	});
})(window, document, window.Joomla);

/*
 * The content language form: the flag beside the image list follows the chosen image (a language pack may bring its own
 * flag as a GIF, also in the flags' old folder). Chosen's changes reach jQuery's handlers only: heard through jQuery when
 * the page has it.
 */
(function (window, document, Joomla) {
	'use strict';

	const start = () => {
		const list = document.getElementById('jform_image');
		const holder = document.getElementById('flag');

		if (!list || !holder) {
			return;
		}

		const root = ((Joomla && Joomla.getOptions && Joomla.getOptions('system.paths')) || {}).root || '';
		let tries = [];

		const update = () => {
			const flag = list.value.toLowerCase();
			let image = holder.querySelector('img');

			if (!flag) {
				if (image) {
					image.removeAttribute('src');
					image.removeAttribute('alt');
				}

				return;
			}

			if (!image) {
				image = holder.appendChild(document.createElement('img'));
			}

			tries = [root + '/media/system/images/flags/' + flag + '.gif', root + '/media/mod_languages/images/' + flag + '.gif'];
			image.onerror = () => {
				if (tries.length) {
					image.src = tries.shift();
				}
			};
			image.alt = flag;
			image.src = root + '/media/system/images/flags/' + flag + '.webp';
		};

		if (window.jQuery) {
			window.jQuery(list).on('change', update);
		} else {
			list.addEventListener('change', update);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})(window, document, window.Joomla);
