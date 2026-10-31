/**
 * @copyright	(C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license		GNU General Public License version 2 or later; see LICENSE.md
 */

// and, for an update, in a message with a button to Joomla Update (options: plg_quickicon_joomlaupdate)
document.addEventListener('DOMContentLoaded', () => {
	const options = Joomla.getOptions('plg_quickicon_joomlaupdate') || {};
	const text = options.text || {};
	const link = document.querySelector('#plg_quickicon_joomlaupdate span.j-links-link');

	if (!link || !options.ajaxUrl) {
		return;
	}

	const say = (html) => {
		link.innerHTML = html;
	};

	window.setTimeout(() => {
		fetch(options.ajaxUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then((response) => (response.ok ? response.json() : Promise.reject(response)))
			.then((updates) => {
				if (!Array.isArray(updates)) {
					say(text.ERROR);
				} else if (!updates.length || updates[0].version === options.version) {
					say(text.UPTODATE);
				} else {
					// ‎ keeps the version left to right in right to left languages
					const version = '‎' + updates[0].version;
					const container = document.getElementById('system-message-container');

					say(text.UPDATEFOUND.replace('%s', version));

					if (container) {
						container.insertAdjacentHTML('afterbegin', '<div class="alert alert-error alert-joomlaupdate">'
							+ text.UPDATEFOUND_MESSAGE.replace('%s', version)
							+ ' <a class="btn btn-primary" href="' + options.url + '">' + text.UPDATEFOUND_BUTTON + '</a></div>');
					}
				}
			})
			.catch(() => say(text.ERROR));
	}, 2000);
});
