/**
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */
window.addEventListener('DOMContentLoaded', function() {
	var versions = document.getElementById('versionsModal');

	// Pages which render the toolbar without showing it (edit forms in a modal) have no Versions modal
	if (versions) {
		document.body.appendChild(versions);
	}
});
