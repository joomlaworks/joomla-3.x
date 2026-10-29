/**
 * @copyright	(C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license		GNU General Public License version 2 or later; see LICENSE.md
 */

// The Privacy Requests quick icon: asks how many requests are urgent, then says it on the icon and, when there are some, in a
// message with a button to the requests (options: js-privacy-check)
document.addEventListener('DOMContentLoaded', () => {
	const options = Joomla.getOptions('js-privacy-check') || {};
	const text = options.plg_quickicon_privacycheck_text || {};
	const link = document.querySelector('#plg_quickicon_privacycheck span.j-links-link');

	if (!link || !options.plg_quickicon_privacycheck_ajax_url) {
		return;
	}

	const say = (html) => {
		link.innerHTML = html;
	};

	fetch(options.plg_quickicon_privacycheck_ajax_url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
		.then((response) => (response.ok ? response.json() : Promise.reject(response)))
		.then((result) => {
			const count = parseInt(result && result.data ? result.data.number_urgent_requests : NaN, 10);
			const badge = '<span class="label label-important">' + count + '</span>';
			const container = document.getElementById('system-message-container');

			if (isNaN(count)) {
				say(text.ERROR);
			} else if (!count) {
				say(text.NOREQUEST);
			} else {
				say(text.REQUESTFOUND + '&nbsp;' + badge);

				if (container) {
					container.insertAdjacentHTML('afterbegin', '<div class="alert alert-error alert-joomlaupdate">'
						+ badge + '&nbsp;' + text.REQUESTFOUND_MESSAGE
						+ ' <a class="btn btn-primary" href="' + options.plg_quickicon_privacycheck_url + '">' + text.REQUESTFOUND_BUTTON + '</a></div>');
				}
			}
		})
		.catch(() => say(text.ERROR));
});
