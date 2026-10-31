/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

(() => {
	'use strict';

	const Joomla = window.Joomla;

	if (!Joomla || !document.querySelector('[data-usercount]')) {
		return;
	}

	const submit = Joomla.submitbutton;

	Joomla.submitbutton = (task) => {
		if (task === 'groups.delete') {
			const withUsers = Array.prototype.some.call(document.querySelectorAll('input[name="cid[]"]:checked'), (box) => {
				const cell = box.closest('[data-usercount]');

				return cell && cell.getAttribute('data-usercount') !== '0';
			});

			if (withUsers && !window.confirm(Joomla.JText._('COM_USERS_GROUPS_CONFIRM_DELETE'))) {
				return;
			}
		}

		submit(task);
	};
})();
