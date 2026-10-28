/**
 * @copyright  (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The Media Manager's scripts, each starting only where it belongs: the drop upload (the lists, with its script options),
 * the media field (its fields), the Media Manager (its page) and the image picker (its page). The last three need jQuery.
 */

/* --- Drop upload --- */
(() => {
	'use strict';

	const options = window.Joomla && Joomla.getOptions ? Joomla.getOptions('com_media.dropUpload') : null;

	if (!options) {
		return;
	}

	const SELECT_KEY = 'com_media.dropUpload.select';
	const PARALLEL   = 2;

	const text = (key, ...values) => values.reduce((string, value) => string.replace('%s', value), options.text[key] || key);

	// MooTools replaces Array.from on some pages, so lists are copied the old way
	const toArray = (list) => Array.prototype.slice.call(list || []);

	const hasFiles = (event) => event.dataTransfer && toArray(event.dataTransfer.types).indexOf('Files') !== -1;

	const element = (tag, className, content) => {
		const node = document.createElement(tag);

		node.className = className;

		if (content !== undefined) {
			node.textContent = content;
		}

		return node;
	};

	// In the image picker: the image dropped before the list reloaded
	const selectDropped = () => {
		let path = null;

		try {
			path = window.sessionStorage.getItem(SELECT_KEY);
			window.sessionStorage.removeItem(SELECT_KEY);
		} catch (e) {
			return;
		}

		if (!path || !window.parent || !window.parent.ImageManager) {
			return;
		}

		window.parent.ImageManager.populateFields(path);

		const link = document.querySelector('a.img-preview.selected');

		if (link) {
			link.scrollIntoView({ block: 'nearest' });
		}
	};

	let overlay = null;
	let panel   = null;
	let depth   = 0;
	let busy    = false;

	const build = () => {
		overlay = element('div', 'media-drop-overlay');
		overlay.hidden = true;
		overlay.appendChild(element('div', 'media-drop-overlay-text', text('hint', options.shown)));
		document.body.appendChild(overlay);
	};

	const showOverlay = (show) => {
		if (overlay) {
			overlay.hidden = !show;
		}
	};

	const upload = (file, row) => new Promise((resolve) => {
		const progress = row.querySelector('progress');
		const status   = row.querySelector('.media-drop-status');
		const finish   = (ok, message, path) => {
			row.classList.add(ok ? 'is-done' : 'is-failed');
			progress.hidden    = true;
			status.textContent = message;
			resolve({ ok, path, image: ok && /\.(avif|bmp|gif|jpe?g|png|webp)$/i.test(path || '') });
		};

		if (options.maxBytes > 0 && file.size > options.maxBytes) {
			finish(false, text('too_large', Math.round(options.maxBytes / 1048576)));

			return;
		}

		const data = new FormData();
		const xhr  = new XMLHttpRequest();

		data.append('Filedata', file, file.name);
		data.append('folder', options.folder);
		data.append(options.token, '1');

		xhr.open('POST', options.url);

		xhr.upload.addEventListener('progress', (event) => {
			if (event.lengthComputable) {
				progress.value = event.loaded / event.total;
			}
		});

		xhr.addEventListener('load', () => {
			let response = null;

			try {
				response = JSON.parse(xhr.responseText);
			} catch (e) {
				response = null;
			}

			if (xhr.status === 200 && response && String(response.status) === '1') {
				finish(true, text('done'), response.path || '');
			} else {
				finish(false, response && response.message ? response.message : text('failed'));
			}
		});

		xhr.addEventListener('error', () => finish(false, text('failed')));
		xhr.send(data);
	});

	const uploadAll = (files) => {
		if (!files.length || busy) {
			return;
		}

		busy = true;

		if (panel) {
			panel.remove();
		}

		panel = element('div', 'media-drop-panel');
		panel.setAttribute('role', 'status');

		const summary = element('div', 'media-drop-summary', text('uploading'));
		const list    = element('ul', 'media-drop-list');

		panel.appendChild(summary);
		panel.appendChild(list);
		document.body.appendChild(panel);

		const rows = files.map((file) => {
			const row = element('li', 'media-drop-item');

			row.appendChild(element('span', 'media-drop-name', file.name));
			row.appendChild(element('progress', 'media-drop-progress'));
			row.appendChild(element('span', 'media-drop-status'));
			row.querySelector('progress').max = 1;
			row.querySelector('progress').value = 0;
			list.appendChild(row);

			return row;
		});

		const results = new Array(files.length);
		let next = 0;

		const worker = () => {
			if (next >= files.length) {
				return Promise.resolve();
			}

			const index = next++;

			return upload(files[index], rows[index]).then((result) => {
				results[index] = result;

				return worker();
			});
		};

		const workers = [];

		for (let i = 0; i < Math.min(PARALLEL, files.length); i++) {
			workers.push(worker());
		}

		Promise.all(workers).then(() => {
			const done   = results.filter((result) => result.ok);
			const failed = results.length - done.length;

			busy = false;
			summary.textContent = text('summary', done.length, results.length);

			// A single image dropped on the image picker is selected once the list shows it
			if (options.picker && done.length === 1 && done[0].image) {
				try {
					window.sessionStorage.setItem(SELECT_KEY, done[0].path);
				} catch (e) {
					// The list still reloads, without the selection
				}
			}

			// All in: the list reloads with them. Some failed: their messages stay until closed
			if (!failed) {
				window.setTimeout(() => window.location.reload(), 600);

				return;
			}

			const close = element('button', 'btn btn-small media-drop-close', text('close'));

			close.type = 'button';
			close.addEventListener('click', () => {
				if (done.length) {
					window.location.reload();
				} else {
					panel.remove();
				}
			});
			panel.appendChild(close);
		});
	};

	document.addEventListener('dragenter', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		event.preventDefault();
		depth++;
		showOverlay(true);
	});

	document.addEventListener('dragover', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		event.preventDefault();
		event.dataTransfer.dropEffect = busy ? 'none' : 'copy';
	});

	document.addEventListener('dragleave', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		depth = Math.max(0, depth - 1);

		if (!depth) {
			showOverlay(false);
		}
	});

	document.addEventListener('drop', (event) => {
		if (!hasFiles(event)) {
			return;
		}

		event.preventDefault();
		depth = 0;
		showOverlay(false);
		uploadAll(toArray(event.dataTransfer.files));
	});

	const start = () => {
		build();

		if (options.picker) {
			selectDropped();
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* --- Media field --- */
/**
 * Field media
 */
;(function($){
	'use strict';

	if (!$) {
		return;
	}

	$.fieldMedia = function(container, options){
		// Merge options with defaults
		this.options = $.extend({}, $.fieldMedia.defaults, options);

		// Set up elements
		this.$container = $(container);
		this.$modal = this.$container.find(this.options.modal);
		this.$modalBody = this.$modal.children('.modal-body');
		this.$input = this.$container.find(this.options.input);
		this.$containerPreview = this.$container.find(this.options.previewContainer);
		this.$buttonSelect = this.$container.find(this.options.buttonSelect);
		this.$buttonClear  = this.$container.find(this.options.buttonClear);

		// Bind events
		this.$buttonSelect.on('click', this.modalOpen.bind(this));
		this.$buttonClear.on('click', this.clearValue.bind(this));
		this.$modal.on('hide', this.removeIframe.bind(this));

		// Update preview for existing value
		this.updatePreview();
	};

	// display modal for select the file
	$.fieldMedia.prototype.modalOpen = function() {
		var $iframe = $('<iframe>', {
			name: 'field-media-modal',
			src: this.options.url.replace('{field-media-id}', this.$input.attr('id')),
			width: this.options.modalWidth,
			height: this.options.modalHeight
		});
		this.$modalBody.append($iframe);
		this.$modal.modal('show');
		$('body').addClass('modal-open');

		var self = this; // save context
		$iframe.load(function(){
			var content = $(this).contents();

			// bind insert
			content.on('click', self.options.buttonSaveSelected, function(){
				var value = content.find('#f_url').val();
				if (value) {
					self.setValue(value);
				}
				self.modalClose.call(self);
			});

			// bind cancel
			content.on('click', '.button-cancel', function(){
				$('body').removeClass('modal-open');
				self.modalClose.bind(self);
			});
		});
	};

	// close modal
	$.fieldMedia.prototype.modalClose = function() {
		this.$modal.modal('hide');
		$('body').removeClass('modal-open');
		this.$modalBody.empty();
	};

	// Clear the iframe
	$.fieldMedia.prototype.removeIframe = function() {
		this.$modalBody.empty();
		$('body').removeClass('modal-open');
	};

	// set the value
	$.fieldMedia.prototype.setValue = function(value) {
		this.$input.val(value).trigger('change');
		this.updatePreview();
	};

	// clear the value
	$.fieldMedia.prototype.clearValue = function() {
		this.setValue('');
	};

	// update preview
	$.fieldMedia.prototype.updatePreview = function() {
		if (!this.options.preview) {
			return;
		}

		if (this.options.preview && !this.options.previewAsTooltip) {
			var value = this.$input.val();

			if (!value) {
				this.$containerPreview.append('');
			} else {
				var imgPreview = new Image();
				imgPreview.src = this.options.basepath + value;
				if (imgPreview.width > imgPreview.height) {
					this.$containerPreview.html($('<img>',{src: imgPreview.src, style: 'width: ' + this.options.previewWidth + 'px'}));
				} else {
					this.$containerPreview.html($('<img>',{src: imgPreview.src, style: 'height: ' + this.options.previewHeight + 'px'}));
				}
			}
		} else {
			// Reset tooltip and preview
			try {
				this.$containerPreview.popover('destroy');
				this.$input.tooltip('destroy');
			} catch(err) {
				// Doing this so that jQueryUI tooltips and popover can exist alongside Bootstrap.
			}

			var value = this.$input.val();

			if (!value) {
				this.$containerPreview.popover();
			} else {
				var imgPreview = new Image(this.options.previewWidth, this.options.previewHeight);
				imgPreview.src = this.options.basepath + value;

				this.$containerPreview.popover({content: imgPreview});
				this.$input.tooltip({placement: 'top', title: value, html: false});
			}
		}
	};

	// default options
	$.fieldMedia.defaults = {
		basepath: '', // base path to file
		buttonClear: '.button-clear', // selector for button to clear the value
		buttonSelect: '.button-select', // selector for button to change the value
		buttonSaveSelected: '.button-save-selected', // selector for button to save the selected value
		input: '.field-media-input', // selector for the input
		preview: true, // whether use the preview
		previewAsTooltip: true, // whether use the preview
		previewContainer: '.field-media-preview', // selector for the preview container
		previewWidth: 200, // preview width
		previewHeight: 200, // preview height
		url: 'index.php?option=com_media&tmpl=component', // url for load the iframe
		modal: '.modal', // modal selector
		modalWidth: '100%', // modal width
		modalHeight: '300px', // modal height
	};

	$.fn.fieldMedia = function(options){
		return this.each(function(){
			var $el = $(this), instance = $el.data('fieldMedia');
			if(!instance){
				var options = options || {},
					data = $el.data();

				// Check options in the element
				for (var p in data) {
					if (data.hasOwnProperty(p)) {
						options[p] = data[p];
					}
				}

				instance = new $.fieldMedia(this, options);
				$el.data('fieldMedia', instance);
			}
		});
	};

	// Initialise all defaults on load and again when subform rows are added
	$(function($) {
		initMediaField();
		$(document).on('subform-row-add', initMediaField);

		function initMediaField (event, container)
		{
			$(container || document).find('.field-media-wrapper').fieldMedia();
		}
	});

})(window.jQuery);

// Compatibility with mootools modal layout
function jInsertFieldValue(value, id) {
	var $ = jQuery.noConflict();
	var old_value = $("#" + id).val();
	if (old_value != value) {
		var $elem = $("#" + id);
		$elem.val(value);
		$elem.trigger("change");
		if (typeof($elem.get(0).onchange) === "function") {
			$elem.get(0).onchange();
		}
		jMediaRefreshPreview(id);
	}
}

function jMediaRefreshPreview(id) {
	var $ = jQuery.noConflict();
	var value = $("#" + id).val();
	var $img = $("#" + id + "_preview");
	var basepath = $("#" + id).data("basepath");

	if ($img.length) {
		if (value) {
			$img.attr("src", basepath + value);
			$("#" + id + "_preview_empty").hide();
			$("#" + id + "_preview_img").show()
		} else {
			$img.attr("src", "");
			$("#" + id + "_preview_empty").show();
			$("#" + id + "_preview_img").hide();
		}
	}
}

function jMediaRefreshPreviewTip(tip)
{
	var $ = jQuery.noConflict();
	var $tip = $(tip);
	var $img = $tip.find("img.media-preview");

	$img.each(function(index, value) {
		$tip.find("div.tip").css("max-width", "none");
		var id = $(this).attr("id");
		id = id.substring(0, id.length - "_preview".length);
		jMediaRefreshPreview(id);
		$tip.show(this);
	});
}

// JQuery for tooltip for INPUT showing whole image path
function jMediaRefreshImgpathTip(tip, els)
{
	var $ = jQuery.noConflict();
	var $tip = $(tip);
	$tip.css("max-width", "none");
	var $imgpath = $(els).val();
	$("#TipImgpath").html($imgpath);

	if ($imgpath.length) {
		$tip.show();
	} else {
		$tip.hide();
	}
}

/* --- Media Manager --- */
/**
 * JMediaManager behavior for media component
 *
 * @package		Joomla.Extensions
 * @subpackage  Media
 * @since		1.5
 */
;(function( $, scope ) {
	"use strict";

	// Defined by the Media Manager's page only (its list's iframe uses the parent's)
	if (!$ || scope.MediaManager) {
		return;
	}

	var MediaManager = scope.MediaManager = {

		/**
		 * Shows a video in the preview window's player (the browser's own); the lists call it from their iframe
		 *
		 * @param   string  src  The video's address
		 *
		 * @return  void
		 */
		playVideo: function(src) {
			var video = document.getElementById('videoPreviewPlayer');

			if (video) {
				video.src = src;
				video.load();
			}
		},

		/**
		 * Pauses the preview's video (when its window closes)
		 *
		 * @return  void
		 */
		pauseVideo: function() {
			var video = document.getElementById('videoPreviewPlayer');

			if (video) {
				video.pause();
			}
		},

		/**
		 * Basic setup
		 *
		 * @return  void
		 */
		initialize: function() {
			this.folderpath = $( '#folderpath' );

			this.updatepaths = $( 'input.update-folder' );

			this.frame = window.frames.folderframe;

			var self = this;

			$(this.frame).one('load', function (e) {
				self.frameurl = self.frame.location.href;
			});
		},

		/**
		 * Called from outside. Only ever called with task 'folder.delete'
		 *
		 * @param   string  task  [description]
		 *
		 * @return  void
		 */
		submit: function( task ) {
			var form = this.frame.document.getElementById( 'mediamanager-form' );
			form.task.value = task;

			if ( $( '#username' ).length ) {
				form.username.value = $( '#username' ).val();
				form.password.value = $( '#password' ).val();
			}

			form.submit();
		},

		/**
		 * [onloadframe description]
		 *
		 * @return  {[type]}
		 */
		onloadframe: function() {
			// Update the frame url
			this.frameurl = this.frame.location.href;

			var folder = this.getFolder() || '',
				query = [],
				a = getUriObject( $( '#uploadForm' ).prop( 'action' ) ),
				q = getQueryObject( a.query ),
				k, v;

			this.updatepaths.each( function( path, el ) {
				el.value = folder;
			} );

			// A jQuery object: .value set nothing, so the field showed only the folder (empty at the top)
			this.folderpath.val( scope.basepath + ( folder ? '/' + folder : '' ) );

			q.folder = folder;

			for ( k in q ) {
				if (!q.hasOwnProperty( k )) { continue; }

				v = q[ k ];
				query.push(encodeURIComponent(k) + (v === null ? '' : '=' + encodeURIComponent(v)));
			}

			a.query = query.join( '&' );
			a.fragment = null;

			$( '#uploadForm' ).prop( 'action', buildUri(a) );
			$( '#' + scope.viewstyle ).addClass( 'active' );
		},

		/**
		 * Switch the view type
		 *
		 * @param  string  type  'thumbs' || 'details'
		 */
		setViewType: function( type ) {
			$( '#' + type ).addClass( 'active' );
			$( '#' + scope.viewstyle ).removeClass( 'active' );
			scope.viewstyle = type;
			var folder = this.getFolder();

			folder = encodeURIComponent(folder);
			type = encodeURIComponent(type);

			this.setFrameUrl( 'index.php?option=com_media&view=mediaList&tmpl=component&folder=' + folder + '&layout=' + type );
		},

		refreshFrame: function() {
			this.setFrameUrl();
		},

		getFolder: function() {
			var args = getQueryObject( this.frame.location.search.substring( 1 ) );

			args.folder = args.folder === undefined ? '' : args.folder;

			return args.folder;
		},

		setFrameUrl: function( url ) {
			if ( url !== null ) {
				this.frameurl = url;
			}

			this.frame.location.href = this.frameurl;
		},
	};

	/**
	 * Convert a query string to an object
	 *
	 * @param   string  q  A query string (no leading ?)
	 *
	 * @return  object
	 */
	function getQueryObject( q ) {
		var rs = {};

		q = q || '';

		$.each( q.split( /[&;]/ ),
			function( key, val ) {
				var keys = val.split( '=' );

				rs[ decodeURIComponent(keys[ 0 ]) ] = keys.length == 2 ? decodeURIComponent(keys[ 1 ]) : null;
			});

		return rs;
	}

	/**
	 * Break a url into its component parts
	 *
	 * @param   string  u  URL
	 *
	 * @return  object
	 */
	function getUriObject( u ) {
		var bitsAssociate = {},
			bits = u.match( /^(?:([^:\/?#.]+):)?(?:\/\/)?(([^:\/?#]*)(?::(\d*))?)((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[\?#]|$)))*\/?)?([^?#\/]*))?(?:\?([^#]*))?(?:#(.*))?/ );

		$.each([ 'uri', 'scheme', 'authority', 'domain', 'port', 'path', 'directory', 'file', 'query', 'fragment' ],
			function( key, index ) {
				bitsAssociate[ index ] = ( !!bits && !!bits[ key ] ) ? bits[ key ] : '';
			});

		return bitsAssociate;
	}

	/**
	 * Build a url from component parts
	 *
	 * @param   object  o  Such as the return value of `getUriObject()`
	 *
	 * @return  string
	 */
	function buildUri ( o ) {
		return o.scheme + '://' + o.domain +
			(o.port ? ':' + o.port : '') +
			(o.path ? o.path : '/') +
			(o.query ? '?' + o.query : '') +
			(o.fragment ? '#' + o.fragment : '');
	}

	$(function() {
		if (!window.frames.folderframe) {
			return;
		}

		// Added to populate data on iframe load
		MediaManager.initialize();

		document.updateUploader = function() {
			$(MediaManager.frame).one('load', function() {
				MediaManager.onloadframe();
			});
		};

		document.updateUploader();
	});

}( window.jQuery, window ));

/* --- Image picker --- */
/**
 * JImageManager behavior for media component
 *
 * @package		Joomla.Extensions
 * @subpackage	Media
 * @since		1.5
 */

(function ($, doc)
{
	'use strict';

	// Defined by the image picker only (its list's iframe uses the parent's, set before this deferred file runs)
	if (!$ || window.ImageManager) {
		return;
	}

	window.ImageManager = {

		/**
		 * Initialization
		 *
		 * @return  void
		 */
		initialize: function ()
		{
			var o = this.getUriObject(window.self.location.href),
				q = this.getQueryObject(o.query);

			var options = Joomla.getOptions('mediamanager');

			this.author = options.author;
			this.base   = options.base;
			this.asset  = options.asset;
			this.editor = q.e_name;

			// Setup image manager fields object
			this.fields = {
				'url': doc.getElementById("f_url"),
				'alt': doc.getElementById("f_alt"),
				'align': doc.getElementById("f_align"),
				'title': doc.getElementById("f_title"),
				'caption': doc.getElementById("f_caption"),
				'c_class': doc.getElementById("f_caption_class")
			};

			// Setup image listing objects
			this.folderlist = doc.getElementById('folderlist');
			this.frame = window.frames.imageframe;
			this.frameurl = this.frame.location.href;

			// Setup image listing frame
			$('#imageframe').on('load', function ()
			{
				ImageManager.onloadimageview();
			});

			// Setup folder up button
			$('#upbutton').off('click').on('click', function ()
			{
				ImageManager.upFolder();
			});
		},

		/**
		 * Called when the iframe is reloaded.
		 * Updates the form action with the correct folder.
		 * This should really be a hidden input rather than part of the action, no?
		 *
		 * @return  void
		 */
		onloadimageview: function ()
		{
			var folder = this.getImageFolder(),
				$form = $('#uploadForm'),
				portString = '', a, q;

			// Update the frame url
			this.frameurl = this.frame.location.href;
			this.setFolder(folder);

			a = this.getUriObject($form.prop('action'));
			q = this.getQueryObject(a.query);
			q.folder = folder;
			a.query = $.param(q);

			if (typeof (a.port) !== 'undefined' && a.port != 80)
			{
				portString = ':' + a.port;
			}

			$form.prop('action', a.scheme + '://' + a.domain + portString + a.path + '?' + a.query);
		},

		/**
		 * Get the current directory based on the query string of the iframe
		 *
		 * @return  string
		 */
		getImageFolder: function ()
		{
			return this.getQueryObject(this.frame.location.search.substring(1)).folder;
		},

		/**
		 * Called from outside when the 'OK' button (maybe 'insert' or 'submit', whatever) is clicked.
		 *
		 * @return  boolean  Always true
		 */
		onok: function ()
		{
			var tag = '',
				attr = [],
				figclass = '',
				captionclass = '',
			// Get the image tag field information
				url = this.fields.url.value,
				alt = this.fields.alt.value,
				align = this.fields.align.value,
				title = this.fields.title.value,
				caption = this.fields.caption.value,
				c_class = this.fields.c_class.value;

			if (url)
			{
				// Set alt attribute
				attr.push('alt="' + alt + '"');

				// Set align attribute
				if (align && !caption)
				{
					attr.push('class="pull-' + align + '"');
				}

				// Set title attribute
				if (title)
				{
					attr.push('title="' + title + '"');
				}

				tag = '<img src="' + url + '" ' + attr.join(' ') + '/>';

				// Process caption
				if (caption)
				{
					if (align)
					{
						figclass = ' class="pull-' + align + '"';
					}

					if (c_class)
					{
						captionclass = ' class="' + c_class + '"';
					}

					tag = '<figure' + figclass + '>' + tag + '<figcaption' + captionclass + '>' + caption + '</figcaption></figure>';
				}
			}

			/** Use the API, if editor supports it **/
			if (window.Joomla && Joomla.editors.instances.hasOwnProperty(this.editor)) {
				Joomla.editors.instances[editor].replaceSelection(tag)
			} else {
				window.parent.jInsertEditorText(tag, this.editor);
			}

			return true;
		},

		/**
		 * Called from outside when the directory selector is used.
		 *
		 * @param   string  folder  The folder to switch to
		 * @param   mixed   asset   Probably an integer or undefined, optional
		 * @param   mixed   author  Probably an integer or undefined, optional
		 *
		 * @return  void
		 */
		setFolder: function (folder, asset, author)
		{
			for (var i = 0, l = this.folderlist.length; i < l; i++)
			{
				if (folder == this.folderlist.options[i].value)
				{
					this.folderlist.selectedIndex = i;
					$(this.folderlist)
						.trigger('liszt:updated') // Mootools
						.trigger('chosen:updated'); // jQuery

					break;
				}
			}

			if (!!asset || !!author)
			{
				this.setFrameUrl(folder, asset, author);
			}
		},

		/**
		 * Move up one directory
		 *
		 * @return  void
		 */
		upFolder: function ()
		{
			var path = this.folderlist.value.split('/'),
				search;

			path.pop();
			search = path.join('/');

			this.setFolder(search);
			this.setFrameUrl(search, this.asset, this.author);
		},

		/**
		 * Called from outside when a file is selected
		 *
		 * @param   string  file  Relative path to the file.
		 *
		 * @return  void
		 */
		populateFields: function (file)
		{
		    $.each($('a.img-preview', $('#imageframe').contents()), function(i, v) {
			if (v.href == "javascript:ImageManager.populateFields('" + file + "')") {
			    $(v, $('#imageframe').contents()).addClass('selected');
			} else {
			    $(v, $('#imageframe').contents()).removeClass('selected');
			}
		    });

		    $("#f_url").val(this.base + file);
		},

		/**
		 * Not used.
		 * Should display messages. There are none.
		 *
		 * @param   string  text  The message text
		 *
		 * @return  void
		 */
		showMessage: function (text)
		{
			var $message = $('#message');

			$message.find('>:first-child').remove();
			$message.append(text);
			$('#messages').css('display', 'block');
		},

		/**
		 * Not used.
		 * Refreshes the iframe
		 *
		 * @return  void
		 */
		refreshFrame: function ()
		{
			this.frame.location.href = this.frameurl;
		},

		/**
		 * Sets the iframe url, loading a new page. Usually for changing directory.
		 *
		 * @param  string  folder  Relative path to directory
		 * @param  mixed   asset   Probably an integer or undefined, optional
		 * @param  mixed   author  Probably an integer or undefined, optional
		 */
		setFrameUrl: function (folder, asset, author)
		{
			var qs = {
				option: 'com_media',
				view: 'imagesList',
				tmpl: 'component',
				asset: asset,
				author: author,
				folder: folder
			};

			// Don't run folder through params because / will end up double encoded.
			this.frameurl = 'index.php?' + $.param(qs);
			this.frame.location.href = this.frameurl;
		},

		/**
		 * Convert a query string to an object
		 *
		 * @param   string  q  A query string (no leading ?)
		 *
		 * @return  object
		 */
		getQueryObject: function (q)
		{
			var rs = {};

			$.each((q || '').split(/[&;]/), function (key, val)
			{
				var keys = val.split('=');

				rs[ decodeURIComponent(keys[0]) ] = keys.length == 2 ? decodeURIComponent(keys[1]) : null;
			});

			return rs;
		},

		/**
		 * Break a url into its component parts
		 *
		 * @param   string  u  URL
		 *
		 * @return  object
		 */
		getUriObject: function (u)
		{
			var bitsAssociate = {},
				bits = u.match(/^(?:([^:\/?#.]+):)?(?:\/\/)?(([^:\/?#]*)(?::(\d*))?)((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[\?#]|$)))*\/?)?([^?#\/]*))?(?:\?([^#]*))?(?:#(.*))?/);

			$.each(['uri', 'scheme', 'authority', 'domain', 'port', 'path', 'directory', 'file', 'query', 'fragment'], function (key, index)
			{
				bitsAssociate[index] = (!!bits && !!bits[key]) ? bits[key] : '';
			});

			return bitsAssociate;
		}
	};

	$(function ()
	{
		if (!doc.getElementById('imageframe'))
		{
			return;
		}

		window.ImageManager.initialize();
	});

}(window.jQuery, document));
