/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The template's files (Templates: Customise):
 * - the trees of folders: closed but for the folders of the open file, a click on a folder opens or closes it; in the New
 *   File and Manage Folders windows a click on a folder chooses it instead (its address goes to the window's field);
 * - the open file and its folders are marked in the tree;
 * - an image's crop area (#image-crop, its own size in data-width and data-height) goes to the form's x, y, w and h.
 *
 * The preview of a template (its thumbnail's button, data-template-preview: the image's address) opens in CMS.modal, as
 * wide as the image or the window allows, scrolling when the image is taller than the window.
 */
(() => {
	'use strict';

	const FOLDERS = '.folder, .component-folder, .plugin-folder, .layout-folder';
	const FOLDER_LINKS = '.folder-url, .component-folder-url, .plugin-folder-url, .layout-folder-url';
	const FILES = '.file, .component-file-url, .plugin-file-url';

	const toggle = (element, shown) => {
		element.style.display = shown ? '' : 'none';
	};

	const preview = (button) => {
		const image = new Image();

		image.alt = button.getAttribute('data-template-name') || '';
		image.addEventListener('load', () => {
			const width = Math.min(image.naturalWidth, window.innerWidth - 80);
			const height = Math.min(Math.round((width * image.naturalHeight) / image.naturalWidth), window.innerHeight - 80);
			const root = document.createElement('div');

			image.style.cssText = 'display:block;width:100%;height:auto;';
			root.style.cssText = 'width:' + width + 'px;height:' + height + 'px;overflow:auto;overscroll-behavior:contain;';
			root.appendChild(image);

			window.CMS.modal.open(root, { handler: 'adopt', size: { x: width, y: height } });
		}, { once: true });
		image.src = button.getAttribute('data-template-preview');
	};

	document.addEventListener('click', (event) => {
		const button = event.target.closest && event.target.closest('[data-template-preview]');

		if (button) {
			event.preventDefault();
			preview(button);
		}
	});

	const start = () => {
		const holder = document.querySelector('.tree-holder');

		if (!holder && !document.getElementById('image-crop')) {
			return;
		}

		document.querySelectorAll('.folder ul, .component-folder ul, .plugin-folder ul, .layout-folder ul').forEach((list) => toggle(list, false));
		document.querySelectorAll('.directory-tree').forEach((list) => list.classList.remove('directory-tree'));
		document.querySelectorAll('.show > ul').forEach((list) => toggle(list, true));

		document.addEventListener('click', (event) => {
			if (!event.target.closest) {
				return;
			}

			const link = event.target.closest(FOLDER_LINKS);

			if (link) {
				event.preventDefault();

				const chooser = link.closest('#fileModal, #folderModal');

				if (chooser) {
					document.querySelectorAll('.folder-url.selected').forEach((selected) => selected.classList.remove('selected'));
					link.classList.add('selected');
					chooser.querySelector('input.address').value = link.getAttribute('data-id');

					return;
				}
			}

			// A file's link inside a folder doesn't close the folder
			if (event.target.closest(FILES)) {
				return;
			}

			const folder = event.target.closest(FOLDERS);
			const list = folder ? Array.prototype.find.call(folder.children, (child) => child.tagName === 'UL') : null;

			if (list) {
				toggle(list, list.style.display === 'none');
			}
		});

		// The open file: its folders, and the file in the last of them
		const path = document.querySelector('p.lead.hidden.path');
		const open = holder ? holder.querySelectorAll('.nav.nav-list .folder.show') : [];
		const name = path ? path.textContent.trim().split('/').pop() : '';

		if (path && path.textContent.trim().charAt(0) === '/') {
			open.forEach((folder) => {
				const link = folder.querySelector('a');

				if (link) {
					link.classList.add('active');
				}
			});

			const last = open.length ? open[open.length - 1].querySelector('ul') : null;

			if (last) {
				last.querySelectorAll('li > a').forEach((link) => {
					const label = link.querySelector('span');

					if (label && label.textContent.trim() === name) {
						link.classList.add('active');
					}
				});
			}
		}

		const image = document.getElementById('image-crop');

		if (image && window.CMS && window.CMS.cropper) {
			const fields = ['x', 'y', 'w', 'h'].map((id) => document.getElementById(id));
			const show = (coords) => fields.forEach((field) => {
				field.value = coords[field.id];
			});
			const clear = () => fields.forEach((field) => {
				field.value = '';
			});

			window.CMS.cropper.attach(image, {
				onChange: show,
				onSelect: show,
				onRelease: clear,
				trueSize: [parseInt(image.getAttribute('data-width'), 10), parseInt(image.getAttribute('data-height'), 10)],
			});
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
