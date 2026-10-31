/**
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

document.addEventListener('DOMContentLoaded', function () {
	if (typeof window.jModalClose_no_tinyMCE === 'undefined')
	{
		window.jModalClose_no_tinyMCE = typeof(jModalClose) == 'function'  ?  jModalClose  :  false;

		jModalClose = function () {
			if (window.jModalClose_no_tinyMCE) window.jModalClose_no_tinyMCE.apply(this, arguments);
			tinyMCE.activeEditor.windowManager.close();
		};
	}

	if (typeof window.SqueezeBoxClose_no_tinyMCE === 'undefined')
	{
		if (typeof(SqueezeBox) == 'undefined')  SqueezeBox = {};
		window.SqueezeBoxClose_no_tinyMCE = typeof(SqueezeBox.close) == 'function'  ?  SqueezeBox.close  :  false;

		SqueezeBox.close = function () {
			if (window.SqueezeBoxClose_no_tinyMCE)  window.SqueezeBoxClose_no_tinyMCE.apply(this, arguments);
			tinyMCE.activeEditor.windowManager.close();
		};
	}
});
