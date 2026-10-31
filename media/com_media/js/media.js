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

	// The page's settings (com_media.manager): the files folder relative to the site, shown in the upload form, and the view
	var settings = (scope.Joomla && scope.Joomla.getOptions && scope.Joomla.getOptions('com_media.manager')) || {};

	if (scope.basepath === undefined) {
		scope.basepath = settings.basepath || '';
	}

	if (scope.viewstyle === undefined) {
		scope.viewstyle = settings.viewstyle || 'thumbs';
	}

	// The toolbar's Delete: only with something checked in the list
	scope.deleteMedia = function() {
		var frame = document.getElementById('folderframe');
		var list = frame && frame.contentDocument;

		if (!list || !list.querySelector('input[name="rm[]"]:checked')) {
			alert(scope.Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));

			return false;
		}

		MediaManager.submit('folder.delete');

		return true;
	};

	var MediaManager = scope.MediaManager = {

		/**
		 * Shows a video in the preview window's player (the browser's own); the lists call it from their iframe
		 *
		 * @param   string  src     The video's address
		 * @param   string  poster  An image of the same name, shown before it plays ('' for none)
		 *
		 * @return  void
		 */
		playVideo: function(src, poster) {
			var video = document.getElementById('videoPreviewPlayer');

			if (video) {
				if (poster) {
					video.setAttribute('poster', poster);
				} else {
					video.removeAttribute('poster');
				}

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
		 * Plays an audio file in the preview window's player (the browser's own); the lists call it from their iframe
		 *
		 * @param   string  src  The file's address
		 *
		 * @return  void
		 */
		playAudio: function(src, cover) {
			var audio = document.getElementById('audioPreviewPlayer');
			var image = document.getElementById('audioPreviewCover');
			var wave = document.querySelector('#audioPlayer .media-audio-wave');

			if (!audio) {
				return;
			}

			// The file's cover (an image of the same name), else the waveform (an SVG: no hidden property, the attribute)
			if (image) {
				image.toggleAttribute('hidden', !cover);
				image.src = cover || '';
			}

			if (wave) {
				wave.toggleAttribute('hidden', !!cover);
			}

			audio.src = src;
			audio.load();
		},

		/**
		 * Pauses the preview's audio (when its window closes)
		 *
		 * @return  void
		 */
		pauseAudio: function() {
			var audio = document.getElementById('audioPreviewPlayer');

			if (audio) {
				audio.pause();
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

			// The previews stop when their window closes (Bootstrap's modal event)
			$('#videoPreview').on('hidden', function () {
				self.pauseVideo();
			});
			$('#audioPreview').on('hidden', function () {
				self.pauseAudio();
			});

			// The waveform moves while the audio plays
			$('#audioPreviewPlayer').on('play playing', function () {
				$('#audioPlayer').addClass('is-playing');
			}).on('pause ended emptied', function () {
				$('#audioPlayer').removeClass('is-playing');
			});

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

		// Without an address (refreshFrame()) the list reloads (it went to "undefined")
		setFrameUrl: function( url ) {
			if ( url ) {
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

/* --- Cropping (the Media Manager's Crop button) --- */
/*
 * The image checked in the list opens in the CMS's modal: a bar of shapes above it (Free, or a ratio, which selects the
 * largest area of that shape), the selection's size below it, and Cancel, Save & Replace and Save As New under that. The
 * area is posted in the image's own pixels to com_media's crop task; the list reloads with the result.
 */
(() => {
	'use strict';

	const RATIOS = ['21:9', '16:9', '14:9', '4:3', '3:2', '1:1'];
	const TYPES = /\.(?:jpe?g|png|webp|gif)$/i;

	const start = () => {
		const CMS = window.CMS;
		const options = CMS && CMS.options.get('com_media.crop');
		const button = document.getElementById('media-crop');
		const frame = document.getElementById('folderframe');

		if (!options || !button || !frame || !CMS.cropper || !CMS.modal) {
			return;
		}

		const text = (key) => CMS.text.get(key, key);
		const listDocument = () => {
			try {
				return frame.contentDocument;
			} catch (error) {
				return null;
			}
		};

		// One checked image of a type that can be cropped
		const chosen = () => {
			const doc = listDocument();
			const boxes = doc ? Array.prototype.filter.call(doc.querySelectorAll('input[name="rm[]"]'), (box) => box.checked) : [];

			return boxes.length === 1 && /^cb-image/.test(boxes[0].id) && TYPES.test(boxes[0].value) ? boxes[0].value : null;
		};
		const update = () => {
			button.disabled = !chosen();
		};
		const watch = () => {
			const doc = listDocument();

			update();

			if (doc) {
				doc.addEventListener('change', update);
				doc.addEventListener('click', () => setTimeout(update, 0));
			}
		};

		const folder = () => (window.MediaManager && window.MediaManager.frame ? window.MediaManager.getFolder() || '' : '');
		const address = (path) => options.base + '/' + path.split('/').map(encodeURIComponent).join('/');

		// After a replacement the browser still holds the old picture: the list's links to it ask for a fresh one
		const refresh = (path) => {
			const stamp = 't=' + Date.now();

			frame.addEventListener('load', () => {
				const doc = listDocument();

				if (!doc) {
					return;
				}

				doc.querySelectorAll('img[src], a[href]').forEach((element) => {
					const attribute = element.tagName === 'IMG' ? 'src' : 'href';
					let url;

					try {
						url = new URL(element.getAttribute(attribute), doc.baseURI);
					} catch (error) {
						return;
					}

					if (decodeURIComponent(url.pathname).endsWith('/' + path)) {
						url.search = stamp;
						element.setAttribute(attribute, url.href);
					}
				});
			}, { once: true });

			window.MediaManager.refreshFrame();
		};

		const open = (name) => {
			const path = (folder() ? folder() + '/' : '') + name;
			const root = document.createElement('div');
			const bar = document.createElement('div');
			const stage = document.createElement('div');
			const image = document.createElement('img');
			const size = document.createElement('p');
			let cropper = null;
			let busy = false;
			let buttons = [];

			root.className = 'media-crop';
			bar.className = 'media-crop-bar btn-group';
			bar.setAttribute('role', 'group');
			bar.setAttribute('aria-label', text('COM_MEDIA_CROP_RATIO'));
			stage.className = 'media-crop-stage';
			size.className = 'media-crop-size';
			size.setAttribute('aria-live', 'polite');
			image.alt = name;

			[['0', text('COM_MEDIA_CROP_FREE')]].concat(RATIOS.map((ratio) => [ratio, ratio])).forEach(([value, label]) => {
				const choice = document.createElement('button');

				choice.type = 'button';
				choice.className = 'btn btn-small' + (value === '0' ? ' active' : '');
				choice.setAttribute('aria-pressed', value === '0' ? 'true' : 'false');
				choice.setAttribute('data-ratio', value);
				choice.textContent = label;
				bar.appendChild(choice);
			});

			stage.appendChild(image);
			root.appendChild(bar);
			root.appendChild(stage);
			root.appendChild(size);

			const showSize = (c) => {
				size.classList.remove('text-error');
				size.textContent = c && c.w > 0 && c.h > 0 ? text('COM_MEDIA_CROP_SIZE').replace('%s', c.w).replace('%s', c.h) : text('COM_MEDIA_CROP_NO_AREA');
			};

			// The image as large as the space between the bar and the size allows
			const fit = () => {
				const selection = cropper ? cropper.getSelection() : null;

				image.style.maxHeight = Math.max(50, stage.clientHeight) + 'px';

				if (selection) {
					cropper.setSelect([selection.x, selection.y, selection.x2, selection.y2]);
				}
			};

			bar.addEventListener('click', (event) => {
				const choice = event.target.closest('[data-ratio]');

				if (!choice || !cropper) {
					return;
				}

				const [w, h] = choice.getAttribute('data-ratio').split(':').map(Number);

				bar.querySelectorAll('[data-ratio]').forEach((item) => {
					item.classList.toggle('active', item === choice);
					item.setAttribute('aria-pressed', item === choice ? 'true' : 'false');
				});

				cropper.setOptions({ aspectRatio: h ? w / h : 0 });

				// A shape selects its largest area; Free keeps the selection
				if (h) {
					cropper.maximise();
				}
			});

			const save = (mode) => {
				const c = cropper ? cropper.getSelection() : null;

				if (busy) {
					return;
				}

				if (!c || c.w < 1 || c.h < 1) {
					size.classList.add('text-error');
					size.textContent = text('COM_MEDIA_CROP_NO_AREA');

					return;
				}

				if (mode === 'replace' && !window.confirm(text('COM_MEDIA_CROP_CONFIRM_REPLACE').replace('%s', name))) {
					return;
				}

				const data = new FormData();

				data.append(CMS.options.get('csrf.token', ''), '1');
				data.append('folder', folder());
				data.append('file', name);
				data.append('mode', mode);
				[['x', c.x], ['y', c.y], ['w', c.w], ['h', c.h]].forEach(([key, value]) => data.append(key, value));

				busy = true;
				buttons.forEach((item) => {
					item.disabled = true;
				});

				fetch(options.url, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then((response) => response.json())
					.then((answer) => {
						if (!answer || answer.status !== '1') {
							throw new Error(answer && answer.message ? answer.message : text('COM_MEDIA_ERROR_UNABLE_TO_CROP'));
						}

						busy = false;
						CMS.modal.close();
						Joomla.renderMessages({ message: [answer.message] });
						refresh(mode === 'replace' ? path : answer.path);
					})
					.catch((error) => {
						busy = false;
						buttons.forEach((item) => {
							item.disabled = false;
						});
						size.classList.add('text-error');
						size.textContent = error && error.message && !(error instanceof SyntaxError) ? error.message : text('COM_MEDIA_ERROR_UNABLE_TO_CROP');
					});
			};

			CMS.modal.open(root, {
				handler: 'adopt',
				title: text('COM_MEDIA_CROP_TITLE').replace('%s', name),
				size: { x: Math.min(1400, window.innerWidth - 80), y: Math.min(900, window.innerHeight - 140) },
				closable: () => !busy,
				buttons: [
					{ text: text('JCANCEL'), className: 'btn', onClick: () => CMS.modal.close() },
					{ text: text('COM_MEDIA_CROP_SAVE_REPLACE'), className: 'btn btn-danger', onClick: () => save('replace') },
					{ text: text('COM_MEDIA_CROP_SAVE_NEW'), className: 'btn btn-primary', onClick: () => save('new') },
				],
				onClose() {
					window.removeEventListener('resize', fit);

					if (cropper) {
						cropper.destroy();
						cropper = null;
					}
				},
			});

			buttons = Array.prototype.slice.call(document.querySelectorAll('#sbox-buttons .btn'));
			buttons.slice(1).forEach((item) => {
				item.disabled = true;
			});

			image.addEventListener('load', () => {
				fit();
				cropper = CMS.cropper.attach(image, { onChange: showSize, onSelect: showSize, onRelease: () => showSize(null) });
				showSize(null);
				buttons.forEach((item) => {
					item.disabled = false;
				});
				window.addEventListener('resize', fit);
			}, { once: true });
			image.addEventListener('error', () => {
				size.classList.add('text-error');
				size.textContent = text('COM_MEDIA_ERROR_UNABLE_TO_CROP');
			}, { once: true });
			image.src = address(path) + '?t=' + Date.now();
		};

		frame.addEventListener('load', watch);
		watch();
		button.addEventListener('click', () => {
			const name = chosen();

			if (name) {
				open(name);
			}
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* --- Renaming (the pencil next to each name in the Media Manager's lists) --- */
/*
 * Runs in the list's frame. Every item with a checkbox (its value is the item's name) gets a pencil after its name; the
 * pencil turns the name into a field (a file's extension stays and isn't shown) with save and cancel buttons, Enter and
 * Escape. Saved through com_media's rename task, then the list reloads; a renamed folder is renamed in the folder tree too.
 */
(() => {
	'use strict';

	const start = () => {
		const CMS = window.CMS;
		const options = CMS && CMS.options.get('com_media.rename');

		if (!options) {
			return;
		}

		const text = options.text || {};
		const top = window.parent && window.parent !== window ? window.parent : window;
		const message = (type, value) => {
			const joomla = top.Joomla || window.Joomla;

			if (value && joomla && joomla.renderMessages) {
				joomla.renderMessages({ [type]: [value] });
			}
		};
		const button = (kind, label) => {
			const element = document.createElement('button');

			element.type = 'button';
			element.className = 'media-rename-' + kind;
			element.title = label;
			element.setAttribute('aria-label', label);
			element.innerHTML = options.icons[kind];

			return element;
		};

		// The folder tree of the page around the list: the renamed folder's name, and its and its subfolders' links
		const renameInTree = (oldPath, newPath, newName) => {
			let doc;

			try {
				doc = top.document;
			} catch (error) {
				return;
			}

			doc.querySelectorAll('a.folder-url').forEach((link) => {
				const url = new URL(link.getAttribute('href'), doc.baseURI);
				const path = url.searchParams.get('folder') || '';

				if (path !== oldPath && path.indexOf(oldPath + '/') !== 0) {
					return;
				}

				url.searchParams.set('folder', newPath + path.slice(oldPath.length));
				link.setAttribute('href', 'index.php' + url.search);

				if (path === oldPath) {
					const label = Array.prototype.filter.call(link.childNodes, (node) => node.nodeType === 3 && node.textContent.trim()).pop();

					if (label) {
						label.textContent = ' ' + newName + ' ';
					}
				}
			});
		};

		const edit = (box, label, pencil, place) => {
			const isFolder = /^cb-folder/.test(box.id);
			const name = box.value;
			const dot = isFolder ? -1 : name.lastIndexOf('.');
			// The extension stays as it is (the server keeps it), so the field holds the name before it
			const base = dot > 0 ? name.slice(0, dot) : name;
			const form = document.createElement('span');
			const input = document.createElement('input');
			const save = button('save', text.save);
			const cancel = button('cancel', text.cancel);
			let busy = false;

			form.className = 'media-rename-form';
			input.type = 'text';
			input.className = 'media-rename-input';
			input.value = base;
			input.setAttribute('aria-label', text.name + ': ' + name);
			input.spellcheck = false;
			form.appendChild(input);

			// In tiles they sit above the name, so the field has the tile's width
			const actions = document.createElement('span');

			actions.className = 'media-rename-actions';
			actions.appendChild(save);
			actions.appendChild(cancel);
			form.appendChild(actions);
			label.hidden = true;
			pencil.hidden = true;
			place.appendChild(form);
			input.focus();
			input.select();

			const close = () => {
				form.remove();
				label.hidden = false;
				pencil.hidden = false;
				pencil.focus();
			};

			const submit = () => {
				const wanted = input.value.trim();

				if (busy) {
					return;
				}

				if (!wanted || wanted === base) {
					close();

					return;
				}

				const data = new FormData();

				data.append(CMS.options.get('csrf.token', ''), '1');
				data.append('folder', options.folder || '');
				data.append('name', name);
				data.append('type', isFolder ? 'folder' : 'file');
				data.append('newname', wanted);

				busy = true;
				form.classList.add('is-busy');
				[input, save, cancel].forEach((element) => {
					element.disabled = true;
				});

				fetch(options.url, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then((response) => response.json())
					.then((answer) => {
						if (!answer || answer.status !== '1') {
							throw new Error(answer && answer.message ? answer.message : '');
						}

						if (!answer.message) {
							close();

							return;
						}

						if (isFolder) {
							const parent = options.folder ? options.folder + '/' : '';

							renameInTree(parent + name, parent + answer.name, answer.name);
						}

						message('message', answer.message);
						window.location.reload();
					})
					.catch((error) => {
						busy = false;
						form.classList.remove('is-busy');
						[input, save, cancel].forEach((element) => {
							element.disabled = false;
						});
						message('error', error && error.message && !(error instanceof SyntaxError) ? error.message : (text.failed || '').replace('%s', name));
						input.focus();
					});
			};

			input.addEventListener('keydown', (event) => {
				if (event.key === 'Enter') {
					event.preventDefault();
					submit();
				} else if (event.key === 'Escape') {
					event.preventDefault();
					close();
				}
			});
			save.addEventListener('click', submit);
			cancel.addEventListener('click', close);

			// Clicks in the field don't reach the tile (which opens the file) or select it
			form.addEventListener('click', (event) => event.stopPropagation());
		};

		document.querySelectorAll('input[name="rm[]"]').forEach((box) => {
			const item = box.closest('li.imgOutline, tr');
			const place = item ? item.querySelector('td.description') || item.querySelector('.imgPreview') || item.querySelector('.small') : null;

			if (!place || place.classList.contains('media-rename')) {
				return;
			}

			// The name as it was, kept whole (links, text), hidden while editing
			const label = document.createElement('span');
			const pencil = button('pencil', text.rename);

			label.className = 'media-rename-label';

			while (place.firstChild) {
				label.appendChild(place.firstChild);
			}

			place.classList.add('media-rename');
			place.appendChild(label);

			// Tiles: on the name bar's edge (CSS), where saving and cancelling then are; in the details view after the name
			place.appendChild(pencil);

			pencil.addEventListener('click', (event) => {
				event.preventDefault();
				event.stopPropagation();
				edit(box, label, pencil, place);
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* --- The bar above the list: thumbnail sizes, Check All Items (next to the views' buttons) --- */
/*
 * The size of the list's tiles, in steps (data-sizes; data-default the size without a choice), set as --media-tile on the
 * list's frame and kept for the session (the frame sets it from there before drawing, view medialist). Shown with the
 * thumbnails only.
 */
(() => {
	'use strict';

	const KEY = 'com_media.tileSize';

	const start = () => {
		const group = document.querySelector('.media-tile-size');
		const frame = document.getElementById('folderframe');

		if (!group || !frame) {
			return;
		}

		const sizes = group.getAttribute('data-sizes').split(',').map(Number);
		const standard = Number(group.getAttribute('data-default'));
		const read = () => {
			try {
				const stored = parseInt(sessionStorage.getItem(KEY), 10);

				return sizes.indexOf(stored) > -1 ? stored : standard;
			} catch (error) {
				return standard;
			}
		};
		let size = read();

		const apply = () => {
			try {
				frame.contentDocument.documentElement.style.setProperty('--media-tile', size + 'px');
			} catch (error) {
				// The list isn't there yet
			}

			group.querySelector('[data-tile-size="-1"]').disabled = size === sizes[0];
			group.querySelector('[data-tile-size="1"]').disabled = size === sizes[sizes.length - 1];
			group.querySelector('[data-tile-size="0"]').disabled = size === standard;
		};

		group.addEventListener('click', (event) => {
			const button = event.target.closest('[data-tile-size]');

			if (!button) {
				return;
			}

			const step = Number(button.getAttribute('data-tile-size'));
			const index = sizes.indexOf(size);

			size = step === 0 ? standard : sizes[Math.max(0, Math.min(sizes.length - 1, index + step))];

			try {
				if (size === standard) {
					sessionStorage.removeItem(KEY);
				} else {
					sessionStorage.setItem(KEY, String(size));
				}
			} catch (error) {
				// Kept for this page only
			}

			apply();
		});

		// Check All Items: the list's checkboxes from the page's one, which follows them (all, some, none); with the
		// thumbnails only (the details table has one in its head), and only when the list has checkboxes
		const all = document.getElementById('media-check-all');
		const allLabel = all ? all.closest('label') : null;
		let thumbs = !group.hidden;
		const boxes = () => {
			try {
				return Array.prototype.slice.call(frame.contentDocument.querySelectorAll('input[name="rm[]"]'));
			} catch (error) {
				return [];
			}
		};
		const follow = () => {
			const list = boxes();
			const checked = list.filter((box) => box.checked).length;

			if (all) {
				allLabel.hidden = !thumbs || !list.length;
				all.checked = list.length > 0 && checked === list.length;
				all.indeterminate = checked > 0 && checked < list.length;
			}
		};

		if (all) {
			all.addEventListener('change', () => {
				// Every box first, then their events (which make the page's box follow)
				const on = all.checked;
				const changed = boxes().filter((box) => box.checked !== on);
				const form = frame.contentDocument.getElementById('mediamanager-form');

				changed.forEach((box) => {
					box.checked = on;
				});

				if (form && form.boxchecked) {
					form.boxchecked.value = on ? boxes().length : 0;
				}

				changed.forEach((box) => box.dispatchEvent(new Event('change', { bubbles: true })));
			});
		}

		// The views' buttons (MediaManager.setViewType()): the sizes and Check All Items go with the thumbnails
		['thumbs', 'details'].forEach((id) => {
			const view = document.getElementById(id);

			if (view) {
				view.addEventListener('click', () => {
					thumbs = id === 'thumbs';
					group.hidden = !thumbs;
					follow();
				});
			}
		});

		// The list's frame as tall as the window allows below its top (it was 500 pixels), less a status bar fixed at the
		// window's foot (Prothero's)
		const fitFrame = () => {
			const status = document.getElementById('status');
			const fixed = status && window.getComputedStyle(status).position === 'fixed' ? status.offsetHeight : 0;
			const top = frame.getBoundingClientRect().top + window.pageYOffset;

			frame.style.height = Math.max(300, window.innerHeight - top - fixed - 16) + 'px';
		};

		window.addEventListener('resize', fitFrame);
		fitFrame();

		frame.addEventListener('load', () => {
			apply();
			follow();

			try {
				frame.contentDocument.addEventListener('change', follow);
			} catch (error) {
				// Another page in the frame
			}
		});
		apply();
		follow();

		try {
			frame.contentDocument.addEventListener('change', follow);
		} catch (error) {
			// Not loaded yet: the load listener adds it
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();

/* --- Image preview (the list's images in the CMS's modal) --- */
/*
 * MediaPreview.open(address, listDocument), called by the list's frame: the image as large as the window allows, with a
 * bar under it (its name; its pixel size and file size) and, when the folder has more images, buttons at its sides and
 * the arrow keys to go through them (in the list's order, round; neighbours loaded ahead).
 */
(() => {
	'use strict';

	if (window.MediaPreview) {
		return;
	}

	const text = (key, fallback) => (window.CMS ? window.CMS.text.get(key, fallback) : fallback);
	const bytes = (size) => {
		const units = ['B', 'KB', 'MB', 'GB'];
		let unit = 0;

		while (size >= 1024 && unit < units.length - 1) {
			size /= 1024;
			unit += 1;
		}

		return (unit ? size.toFixed(size < 10 ? 1 : 0) : size) + ' ' + units[unit];
	};
	const nameOf = (address) => {
		try {
			return decodeURIComponent(new URL(address, document.baseURI).pathname.split('/').pop());
		} catch (error) {
			return address;
		}
	};

	window.MediaPreview = {
		open(address, listDocument) {
			const CMS = window.CMS;
			const options = (CMS && CMS.options.get('com_media.preview')) || {};
			const icons = options.icons || {};
			const links = listDocument ? Array.prototype.map.call(listDocument.querySelectorAll('a.img-preview[href]'), (link) => link.href) : [];
			const images = links.filter((link, index) => links.indexOf(link) === index);
			const absolute = new URL(address, listDocument ? listDocument.baseURI : document.baseURI).href;
			let current = Math.max(0, images.indexOf(absolute));

			if (!images.length) {
				images.push(absolute);
			}

			const root = document.createElement('div');
			const stage = document.createElement('div');
			const image = document.createElement('img');
			const bar = document.createElement('div');
			const name = document.createElement('span');
			const position = document.createElement('span');
			const size = document.createElement('span');
			const rtl = window.getComputedStyle(document.documentElement).direction === 'rtl';
			const sizes = {};
			const arrow = (step, key, fallback, icon) => {
				const button = document.createElement('button');

				button.type = 'button';
				button.className = 'media-preview-arrow media-preview-' + (step < 0 ? 'previous' : 'next');
				button.title = text(key, fallback);
				button.setAttribute('aria-label', button.title);
				button.innerHTML = icon || (step < 0 ? '&lsaquo;' : '&rsaquo;');
				button.addEventListener('click', () => go(step));

				return button;
			};

			root.className = 'media-preview';
			stage.className = 'media-preview-stage';
			bar.className = 'media-preview-bar';
			name.className = 'media-preview-name';
			position.className = 'media-preview-position';
			size.className = 'media-preview-size';
			size.setAttribute('aria-live', 'polite');
			stage.appendChild(image);

			if (images.length > 1) {
				stage.appendChild(arrow(-1, 'COM_MEDIA_PREVIEW_PREVIOUS', 'Previous image', icons.previous));
				stage.appendChild(arrow(1, 'COM_MEDIA_PREVIEW_NEXT', 'Next image', icons.next));
			}

			bar.appendChild(name);
			bar.appendChild(position);
			bar.appendChild(size);
			root.appendChild(stage);
			root.appendChild(bar);

			// The file's size, asked of the server once per image
			const fileSize = (url) => {
				if (sizes[url] === undefined) {
					sizes[url] = fetch(url, { method: 'HEAD', credentials: 'same-origin' })
						.then((response) => parseInt(response.headers.get('Content-Length'), 10) || 0)
						.catch(() => 0);
				}

				return sizes[url];
			};

			const show = () => {
				const url = images[current];
				const shown = current;

				name.textContent = nameOf(url);
				name.title = name.textContent;
				position.textContent = images.length > 1 ? text('COM_MEDIA_PREVIEW_POSITION', '%1$s of %2$s').replace('%1$s', current + 1).replace('%2$s', images.length) : '';
				size.textContent = '';
				image.alt = name.textContent;
				image.src = url;

				image.decode().catch(() => null).then(() => fileSize(url)).then((length) => {
					if (shown === current) {
						size.textContent = image.naturalWidth + ' × ' + image.naturalHeight + (length ? ' · ' + bytes(length) : '');
					}
				});

				// The neighbours, ready for the arrows
				[-1, 1].forEach((step) => {
					if (images.length > 1) {
						new Image().src = images[(current + step + images.length) % images.length];
					}
				});
			};

			const go = (step) => {
				current = (current + step + images.length) % images.length;
				show();
			};

			const onKey = (event) => {
				if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
					return;
				}

				event.preventDefault();
				go((event.key === 'ArrowRight') !== rtl ? 1 : -1);
			};

			CMS.modal.open(root, {
				handler: 'adopt',
				size: { x: Math.min(1600, window.innerWidth - 80), y: Math.min(1000, window.innerHeight - 100) },
				classWindow: 'media-preview-window',
				onClose() {
					document.removeEventListener('keydown', onKey);
				},
			});

			if (images.length > 1) {
				document.addEventListener('keydown', onKey);
			}

			show();
		},
	};
})();

/* --- The list (the Media Manager's frame) --- */
/*
 * Its checkboxes belong to #mediamanager-form, not adminForm; its previews open in the parent's windows: images in the
 * CMS's modal (MediaPreview, with the folder's other images), audio and video in the Media Manager's players.
 */
(() => {
	'use strict';

	const start = () => {
		const form = document.getElementById('mediamanager-form');

		if (!form || window.parent === window || !window.Joomla) {
			return;
		}

		const parent = window.parent;

		window.Joomla.isChecked = (checked, list) => {
			const target = list || form;
			const all = target.elements['checkall-toggle'];

			target.boxchecked.value = Number(target.boxchecked.value) + (checked ? 1 : -1);

			if (all) {
				all.checked = !Array.prototype.some.call(target.elements, (element) => element.type === 'checkbox' && element !== all && !element.checked);
			}
		};

		if (parent.document.updateUploader) {
			parent.document.updateUploader();
		}

		document.addEventListener('click', (event) => {
			const link = event.target.closest ? event.target.closest('.audio-preview, .video-preview, .img-preview, .preview') : null;

			if (!link) {
				return;
			}

			event.preventDefault();

			const href = link.getAttribute('href');
			const cover = link.getAttribute('data-cover') || '';

			if (link.classList.contains('audio-preview') || link.classList.contains('video-preview')) {
				const audio = link.classList.contains('audio-preview');

				if (parent.jQuery) {
					parent.jQuery(audio ? '#audioPreview' : '#videoPreview').modal('show');
				}

				parent.MediaManager[audio ? 'playAudio' : 'playVideo'](href, cover);

				return;
			}

			if (parent.MediaPreview) {
				parent.MediaPreview.open(href, document);
			} else {
				parent.CMS.modal.open(href, { handler: 'image' });
			}

			// Focus leaves the list's frame for the modal, so Escape closes it
			const close = parent.document.getElementById('sbox-btn-close');

			if (close) {
				close.focus({ focusVisible: false });
			}
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
