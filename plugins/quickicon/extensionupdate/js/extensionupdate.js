/**
 * @copyright	(C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license		GNU General Public License version 2 or later; see LICENSE.md
 */

// The Extension Updates quick icon: asks how many extensions have updates (Joomla itself, 700, aside), then says it on the icon
// and, when there are some, in a message with a button to the Update page (options: plg_quickicon_extensionupdate)
document.addEventListener('DOMContentLoaded', () => {
	const options = Joomla.getOptions('plg_quickicon_extensionupdate') || {};
	const text = options.text || {};
	const link = document.querySelector('#plg_quickicon_extensionupdate span.j-links-link');

	if (!link || !options.ajaxUrl) {
		return;
	}

	const say = (html) => {
		link.innerHTML = html;
	};

	fetch(options.ajaxUrl + '&eid=0&skip=700', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
		.then((response) => (response.ok ? response.json() : Promise.reject(response)))
		.then((updates) => {
			const container = document.getElementById('system-message-container');

			if (!Array.isArray(updates)) {
				say(text.ERROR);
			} else if (!updates.length) {
				say(text.UPTODATE);
			} else {
				say(text.UPDATEFOUND.replace('%s', updates.length));

				if (container) {
					container.insertAdjacentHTML('afterbegin', '<div class="alert alert-error alert-joomlaupdate">'
						+ text.UPDATEFOUND_MESSAGE.replace('%s', updates.length)
						+ ' <a class="btn btn-primary" href="' + options.url + '">' + text.UPDATEFOUND_BUTTON + '</a></div>');
				}
			}
		})
		.catch(() => say(text.ERROR));
});
