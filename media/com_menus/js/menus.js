/**
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */
(function() {
	"use strict";
	/**
	 * Javascript to insert the link
	 * View element calls jSelectContact when a contact is clicked
	 * jSelectContact creates the link tag, sends it to the editor,
	 * and closes the select frame.
	 */

	window.jSelectMenuItem = function(id, title, uri, object, link, lang)
	{
		var thislang = '', tag, editor;

		if (!Joomla.getOptions('xtd-menus')) {
			// Something went wrong!
			window.parent.jModalClose();
			return false;
		}

		editor = Joomla.getOptions('xtd-menus').editor;

		if (lang !== '')
		{
			thislang = '&lang=';
		}

		tag = '<a href=\"' + uri + thislang + lang + '">' + title + '</a>';

		/** Use the API, if editor supports it **/
		if (window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances && window.parent.Joomla.editors.instances.hasOwnProperty(editor)) {
			if (typeof window.parent.Joomla.editors.instances[editor]['getSelection'] !== 'undefined' && window.parent.Joomla.editors.instances[editor].getSelection()) {
				window.parent.Joomla.editors.instances[editor].replaceSelection('<a href=\"' + uri + thislang + lang + '">' + window.parent.Joomla.editors.instances[editor].getSelection() + '</a>');
			} else {
				window.parent.Joomla.editors.instances[editor].replaceSelection(tag)
			}
		} else {
			window.parent.jInsertEditorText(tag, editor);
		}

		window.parent.jModalClose();
	};
})();
