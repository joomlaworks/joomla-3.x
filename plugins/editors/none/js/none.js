/**
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

;(function(Joomla, window, document){

	function insertAtCursor(myField, myValue) {
		if (document.selection) {
			// IE support
			myField.focus();
			var sel = document.selection.createRange();
			sel.text = myValue;
		} else if (myField.selectionStart || myField.selectionStart === 0) {
			// MOZILLA/NETSCAPE support
			myField.value = myField.value.substring(0, myField.selectionStart)
				+ myValue
				+ myField.value.substring(myField.selectionEnd, myField.value.length);
		} else {
			myField.value += myValue;
		}
	}

	function getSelection(myField) {
		if (document.selection) {
			// IE support
			myField.focus();
			return document.selection.createRange();
		} else if (myField.selectionStart || myField.selectionStart === 0) {
			// MOZILLA/NETSCAPE support
			return myField.value.substring(myField.selectionStart, myField.selectionEnd);
		} else {
			return myField.value;
		}
	}

	// @deprecated 4.0 Use directly Joomla.editors.instances[editor].replaceSelection(text);
	window.jInsertEditorText = function(text, editor) {
		Joomla.editors.instances[editor].replaceSelection(text);
	};

	// Text boxes growing with their text where the browser can't by CSS (field-sizing: content)
	function grow(textarea) {
		var style = window.getComputedStyle(textarea);
		var borders = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);

		textarea.style.height = 'auto';
		textarea.style.height = (textarea.scrollHeight + (style.boxSizing === 'border-box' ? borders : 0)) + 'px';
	}

	if (!(window.CSS && CSS.supports && CSS.supports('field-sizing', 'content'))) {
		document.addEventListener('input', function(event) {
			if (event.target.matches && event.target.matches('.js-editor-none textarea[data-grow]')) {
				grow(event.target);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function() {
		var editors = document.querySelectorAll('.js-editor-none');

		for(var i = 0, l = editors.length; i < l; i++) {
			/** Register Editor */
			Joomla.editors.instances[editors[i].childNodes[0].id] = {
				'id': editors[i].childNodes[0].id,
				'element':  editors[i].childNodes[0],
				'getValue': function () { return this.element.value; },
				'setValue': function (text) { return this.element.value = text; },
				'getSelection': function () { return getSelection(this.element); },
				'replaceSelection': function (text) { var result = insertAtCursor(this.element, text); if (this.element.hasAttribute('data-grow') && !(window.CSS && CSS.supports && CSS.supports('field-sizing', 'content'))) { grow(this.element); } return result; },
				'onSave': function() { return ''; }
			};

			if (editors[i].childNodes[0].hasAttribute('data-grow') && !(window.CSS && CSS.supports && CSS.supports('field-sizing', 'content'))) {
				grow(editors[i].childNodes[0]);
			}
		}
	});
}(Joomla, window, document));
