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

	window.jSelectContact = function(id, title, catid, object, link, lang)
	{
		var hreflang = '', tag, editor;

		if (!Joomla.getOptions('xtd-contacts')) {
			// Something went wrong!
			window.parent.jModalClose();
			return false;
		}

		editor = Joomla.getOptions('xtd-contacts').editor;

		if (lang !== '') {
			hreflang = ' hreflang = "' + lang + '"';
		}

		tag = '<a' + hreflang + ' href="' + link + '">' + title + '</a>';

		/** Use the API, if editor supports it **/
		if (window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances && window.parent.Joomla.editors.instances.hasOwnProperty(editor)) {
			window.parent.Joomla.editors.instances[editor].replaceSelection(tag)
		} else {
			window.parent.jInsertEditorText(tag, editor);
		}

		window.parent.jModalClose();
	};
})();
