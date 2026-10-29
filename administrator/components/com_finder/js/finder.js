/**
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/**
 * Smart Search's indexer (view indexer, in a modal of the Index page): starts the index, sends batches until every item is
 * done, optimises, and shows the progress; when it's complete it closes the modal, whose page then reloads.
 */
window.FinderIndexer = function FinderIndexer() {
	const options = Joomla.getOptions('com_finder.indexer') || {};
	const token = document.getElementById('finder-indexer-token');
	const path = 'index.php?option=com_finder&tmpl=component&format=json' + (token ? '&' + token.getAttribute('name') + '=1' : '');
	const header = document.getElementById('finder-progress-header');
	const message = document.getElementById('finder-progress-message');
	const bar = document.getElementById('progress-bar');
	let totalItems = null;
	let offset = 0;
	let optimized = false;

	const removeBar = () => {
		const progress = document.getElementById('progress');

		if (progress) {
			progress.remove();
		}
	};

	// The server's messages may hold markup (links to the options)
	const fail = (title, text) => {
		removeBar();
		header.textContent = title;
		header.classList.add('finder-error');
		message.innerHTML = text;
		message.classList.add('finder-error');
	};

	// The modal the indexer is in: a Bootstrap modal of the toolbar, or the CMS's
	const closeModal = () => {
		const parent = window.parent;

		if (parent === window) {
			return;
		}

		if (parent.jQuery && parent.jQuery.fn.modal && parent.document.getElementById('modal-archive')) {
			parent.jQuery('#modal-archive').modal('hide');
		} else if (parent.CMS && parent.CMS.modal) {
			parent.CMS.modal.close();
		}
	};

	const update = (title, text) => {
		const progress = (offset / totalItems) * 100;

		header.textContent = title;
		message.innerHTML = text;

		if (bar) {
			if (progress >= 100) {
				bar.classList.remove('bar-success');
				bar.classList.add('bar-warning');
				bar.setAttribute('aria-valuemin', 100);
				bar.setAttribute('aria-valuemax', 200);
			}

			bar.style.width = progress + '%';
			bar.setAttribute('aria-valuenow', progress);
		}

		if (text === options.complete) {
			removeBar();
			closeModal();
		}
	};

	const request = (task) => {
		fetch(path + '&task=' + task, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then((response) => response.text().then((text) => ({ response, text })))
			.then(({ response, text }) => {
				let json = null;

				try {
					json = JSON.parse(text);
				} catch (error) {
					json = null;
				}

				if (!response.ok || !json || json.error) {
					if (json && json.header) {
						fail(json.header, json.message);
					} else {
						fail(Joomla.JText._('COM_FINDER_AN_ERROR_HAS_OCCURRED'), Joomla.JText._('COM_FINDER_MESSAGE_RETURNED') + ' <br />'
							+ (text ? text.replace(/</g, '&lt;') : Joomla.JText._('COM_FINDER_NO_ERROR_RETURNED')));
					}

					return;
				}

				if (json.start) {
					totalItems = json.totalItems;
				}

				offset += json.batchOffset;
				update(json.header, json.message);

				if (offset < totalItems) {
					request('indexer.batch');
				} else if (!optimized) {
					optimized = true;
					request('indexer.optimize');
				}
			})
			.catch(() => fail(Joomla.JText._('COM_FINDER_AN_ERROR_HAS_OCCURRED'), Joomla.JText._('COM_FINDER_NO_ERROR_RETURNED')));
	};

	request('indexer.start');
};

document.addEventListener('DOMContentLoaded', () => {
	window.Indexer = new window.FinderIndexer();

	// In the CMS's modal (SqueezeBox's names), its page reloads when it closes
	if (window.parent !== window && window.parent.CMS && window.parent.CMS.modal && window.parent.CMS.modal.isOpen) {
		window.parent.CMS.modal.on('close', () => window.parent.location.reload());
	}
});
