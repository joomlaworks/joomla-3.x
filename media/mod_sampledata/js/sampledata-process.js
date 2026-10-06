/**
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/* Sample Data module: installs a set step by step, each step a request to the set's plugin */
((document, window) => {
	'use strict';

	let inProgress = false;

	const message = (list, text, type) => {
		const item = document.createElement('li');

		item.className = `alert alert-${type}`;
		item.innerHTML = text;
		list.appendChild(item);

		return item;
	};

	const runStep = (set, button, type, steps, step) => {
		const progress = set.querySelector('.sampledata-progress');
		const list = set.querySelector('.sampledata-messages');

		if (step > steps) {
			const container = set.closest('.sampledata-container');

			// Installing another set now replaces this one
			if (container) {
				container.dataset.installed = button.dataset.title || '';
			}

			inProgress = false;
			button.disabled = true;
			button.classList.add('btn-success');
			button.innerHTML = `<span class="icon-publish" aria-hidden="true"></span> ${Joomla.JText._('MOD_SAMPLEDATA_INSTALLED')}`;

			return;
		}

		const loader = document.createElement('li');

		loader.innerHTML = `<img src="${window.modSampledataIconProgress}" width="30" height="30" alt="">`;
		list.appendChild(loader);

		const body = new FormData();

		body.append('type', type);
		body.append('plugin', `SampledataApplyStep${step}`);
		body.append('step', step);
		body.append(window.modSampledataToken, '1');

		fetch(window.modSampledataUrl, { method: 'POST', body, credentials: 'same-origin' })
			.then((response) => response.json())
			.then((response) => {
				loader.remove();

				// Every sample data plugin answers; only the set's own plugin gives a result
				const results = (response && response.success && Array.isArray(response.data) ? response.data : []).filter((result) => result && typeof result === 'object');

				if (!results.length) {
					message(list, Joomla.JText._('MOD_SAMPLEDATA_INVALID_RESPONSE'), 'error');
					inProgress = false;

					return;
				}

				let success = true;

				results.forEach((result) => {
					success = success && !!result.success;
					message(list, result.message, result.success ? 'success' : 'error');
				});

				progress.value = step / steps;

				if (success) {
					runStep(set, button, type, steps, step + 1);
				} else {
					inProgress = false;
				}
			})
			.catch(() => {
				loader.remove();
				message(list, Joomla.JText._('MOD_SAMPLEDATA_REQUEST_FAILED'), 'error');
				inProgress = false;
			});
	};

	const apply = (button) => {
		const set = button.closest('.sampledata-set');

		if (inProgress || !set) {
			return;
		}

		if (button.dataset.processed) {
			window.alert(Joomla.JText._('MOD_SAMPLEDATA_ITEM_ALREADY_PROCESSED'));

			return;
		}

		const container = set.closest('.sampledata-container');
		const installed = container ? container.dataset.installed : '';
		const question = installed ? Joomla.JText._('MOD_SAMPLEDATA_CONFIRM_REPLACE').replace('%s', installed) : Joomla.JText._('MOD_SAMPLEDATA_CONFIRM_START');

		if (!window.confirm(question)) {
			return;
		}

		button.dataset.processed = '1';
		set.querySelector('.sampledata-progress').hidden = false;
		set.querySelector('.sampledata-messages').hidden = false;
		inProgress = true;
		runStep(set, button, button.dataset.type, parseInt(button.dataset.steps, 10), 1);
	};

	document.addEventListener('click', (event) => {
		const button = event.target instanceof Element ? event.target.closest('.sampledata-apply') : null;

		if (button) {
			event.preventDefault();
			apply(button);
		}
	});

	// The old name, for overrides of the module's layout
	window.sampledataApply = (element) => {
		apply(element);

		return false;
	};
})(document, window);
