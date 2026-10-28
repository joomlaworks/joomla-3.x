/**
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/* The Article, Page Break and Read More buttons (editors-xtd): the articles modal, the page break form, Read More */

(function() {
	"use strict";
	/**
	 * Javascript to insert the link
	 * View element calls jSelectArticle when an article is clicked
	 * jSelectArticle creates the link tag, sends it to the editor,
	 * and closes the select frame.
	 **/
	window.jSelectArticle = function (id, title, catid, object, link, lang) {
		var hreflang = '', editor, tag;

		if (!Joomla.getOptions('xtd-articles')) {
			// Something went wrong!
			window.parent.jModalClose();
			return false;
		}

		editor = Joomla.getOptions('xtd-articles').editor;

		if (lang !== '')
		{
			hreflang = ' hreflang="' + lang + '"';
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

	document.addEventListener('DOMContentLoaded', function(){
		// Get the elements
		var elements = document.querySelectorAll('.select-link');

		for(var i = 0, l = elements.length; l>i; i++) {
			// Listen for click event
			elements[i].addEventListener('click', function (event) {
				event.preventDefault();
				var functionName = event.target.getAttribute('data-function');

				if (functionName === 'jSelectArticle') {
					// Used in xtd_contacts
					window[functionName](event.target.getAttribute('data-id'), event.target.getAttribute('data-title'), event.target.getAttribute('data-cat-id'), null, event.target.getAttribute('data-uri'), event.target.getAttribute('data-language'));
				} else {
					// Used in com_menus
					window.parent[functionName](event.target.getAttribute('data-id'), event.target.getAttribute('data-title'), event.target.getAttribute('data-cat-id'), null, event.target.getAttribute('data-uri'), event.target.getAttribute('data-language'));
				}
			})
		}
	});
})();

(function() {
	"use strict";

	window.insertPagebreak = function(editor) {
		/** Get the pagebreak title **/
		var alt, tag, title = document.getElementById('title').value;

		if (!window.parent.Joomla.getOptions('xtd-pagebreak')) {
			// Something went wrong!
			window.parent.jModalClose();
			return false;
		}

		/** Get the pagebreak toc alias -- not inserting for now **/
		/** don't know which attribute to use... **/
		alt = document.getElementById('alt').value;

		title  = (title != '') ? 'title="' + title + '"' : '';
		alt    = (alt != '') ? 'alt="' + alt + '"' : '';

		tag = '<hr class="system-pagebreak" ' + title + ' ' + alt + '/>';

		/** Use the API, if editor supports it **/
		if (window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances && window.parent.Joomla.editors.instances.hasOwnProperty(editor)) {
			window.parent.Joomla.editors.instances[editor].replaceSelection(tag)
		} else {
			window.parent.jInsertEditorText(tag, editor);
		}

		window.parent.jModalClose();
		return false;
	};
})();

window.insertReadmore = function(editor) {
	"use strict";
	if (!Joomla.getOptions('xtd-readmore')) {
		// Something went wrong!
		return false;
	}

	var content, options = window.Joomla.getOptions('xtd-readmore');

	if (window.Joomla && window.Joomla.editors && window.Joomla.editors.instances && window.Joomla.editors.instances.hasOwnProperty(editor)) {
		content = window.Joomla.editors.instances[editor].getValue();
	} else {
		content = (new Function('return ' + options.editor))();
	}

	if (content.match(/<hr\s+id=("|')system-readmore("|')\s*\/*>/i)) {
		alert(options.exists);
		return false;
	} else {
		/** Use the API, if editor supports it **/
		if (window.Joomla && window.Joomla.editors && window.Joomla.editors.instances && window.Joomla.editors.instances.hasOwnProperty(editor)) {
			window.Joomla.editors.instances[editor].replaceSelection('<hr id="system-readmore" />');
		} else {
			window.jInsertEditorText('<hr id="system-readmore" />', editor);
		}
	}
};
