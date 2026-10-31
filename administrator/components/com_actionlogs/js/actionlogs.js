/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * User Actions Log: the export buttons send the separate export form (#exportForm), "Export Selected" with the chosen
 * items' IDs.
 */
(() => {
	'use strict';

	const Joomla = window.Joomla;
	const exportForm = document.getElementById('exportForm');

	if (!Joomla || !exportForm) {
		return;
	}

	const submit = Joomla.submitbutton;

	Joomla.submitbutton = (task) => {
		if (task === 'actionlogs.exportSelectedLogs') {
			exportForm.cids.value = Array.prototype.map.call(document.querySelectorAll('input[name="cid[]"]:checked'), (box) => box.value).join(',');
		}

		if (task === 'actionlogs.exportLogs' || task === 'actionlogs.exportSelectedLogs') {
			Joomla.submitform(task, exportForm);

			return;
		}

		submit(task);
	};
})();
