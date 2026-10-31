/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */
((window, document) => {
	'use strict';

	if (window.CMS && window.CMS.pdfViewer) {
		return;
	}

	const CMS = window.CMS = window.CMS || {};
	const ZOOM_STEP = 1.25;
	const ZOOM_MIN = 0.25;
	const ZOOM_MAX = 5;

	let library = null;

	const options = () => (window.Joomla && window.Joomla.getOptions ? window.Joomla.getOptions('system.pdfviewer') : null) || {};

	const text = (key, fallback) => {
		const value = window.Joomla && window.Joomla.Text ? window.Joomla.Text._('JLIB_HTML_BEHAVIOR_PDF_' + key, fallback) : fallback;

		return value && value !== 'JLIB_HTML_BEHAVIOR_PDF_' + key ? value : fallback;
	};

	const icon = (name) => (options().icons || {})[name] || '';

	// A path of the site (images/x.pdf) or an address
	const resolve = (url) => (/^(https?:)?\/\//i.test(url) || url.charAt(0) === '/' ? url : (options().root || '/') + url);

	const load = () => {
		if (!library) {
			const settings = options();

			library = import(settings.lib).then((lib) => {
				lib.GlobalWorkerOptions.workerSrc = settings.worker;

				return lib;
			});

			library.catch(() => {
				library = null;
			});
		}

		return library;
	};

	// A document is closed through the task which loaded it
	const tasks = new WeakMap();

	const close = (pdf) => {
		if (pdf && tasks.has(pdf)) {
			tasks.get(pdf).destroy();
			tasks.delete(pdf);
		}
	};

	const open = (url) => load().then((lib) => {
		const settings = options();
		const task = lib.getDocument({
			url: resolve(url),
			cMapUrl: settings.cmaps,
			cMapPacked: true,
			standardFontDataUrl: settings.fonts,
			wasmUrl: settings.wasm,
			iccUrl: settings.iccs,
			enableXfa: false,
		});

		return task.promise.then((pdf) => {
			tasks.set(pdf, task);

			return pdf;
		});
	});

	const render = (page, scale, canvas) => {
		const ratio = window.devicePixelRatio || 1;
		const viewport = page.getViewport({ scale: scale * ratio });

		canvas.width = Math.floor(viewport.width);
		canvas.height = Math.floor(viewport.height);
		canvas.style.width = Math.floor(viewport.width / ratio) + 'px';
		canvas.style.height = Math.floor(viewport.height / ratio) + 'px';

		return page.render({ canvasContext: canvas.getContext('2d'), viewport });
	};

	/*
	 * First pages: a few at a time, as a folder may hold many PDFs
	 */
	const queue = [];
	let running = 0;

	const next = () => {
		while (running < 2 && queue.length) {
			const job = queue.shift();

			running += 1;
			job().then(() => {
				running -= 1;
				next();
			});
		}
	};

	const thumbnail = (url, width) => new Promise((resolveThumbnail, reject) => {
		queue.push(() => open(url).then((pdf) => pdf.getPage(1).then((page) => {
			const canvas = document.createElement('canvas');
			const scale = width / page.getViewport({ scale: 1 }).width;

			return render(page, scale, canvas).promise.then(() => {
				const pages = pdf.numPages;

				close(pdf);
				resolveThumbnail({ canvas, pages });
			});
		})).catch(reject));
		next();
	});

	/*
	 * Elements with data-pdf-thumb (the file): its first page drawn into them when they come into view
	 */
	const thumbs = new WeakSet();
	let thumbObserver = null;

	const drawThumbnail = (element) => {
		const width = parseInt(element.getAttribute('data-pdf-width'), 10) || element.clientWidth || 120;

		thumbnail(element.getAttribute('data-pdf-thumb'), width).then((result) => {
			result.canvas.className = 'cms-pdf-thumb';

			// Sized by its container's styles
			result.canvas.style.width = '';
			result.canvas.style.height = '';
			element.textContent = '';
			element.appendChild(result.canvas);
			element.classList.add('has-pdf-thumb');
			element.dispatchEvent(new CustomEvent('cms:pdf-thumbnail', { bubbles: true, detail: { pages: result.pages } }));
		}).catch(() => {});
	};

	const thumbnails = (root) => {
		(root || document).querySelectorAll('[data-pdf-thumb]').forEach((element) => {
			if (thumbs.has(element)) {
				return;
			}

			thumbs.add(element);

			if (!('IntersectionObserver' in window)) {
				drawThumbnail(element);

				return;
			}

			thumbObserver = thumbObserver || new IntersectionObserver((entries) => entries.forEach((entry) => {
				if (entry.isIntersecting) {
					thumbObserver.unobserve(entry.target);
					drawThumbnail(entry.target);
				}
			}), { rootMargin: '200px' });

			thumbObserver.observe(element);
		});
	};

	/*
	 * The viewer: a toolbar and the pages, drawn as they come into view, fitting the width until zoomed
	 */
	const viewers = new WeakMap();

	const button = (name, label) => {
		const element = document.createElement('button');

		element.type = 'button';
		element.className = 'cms-pdf-button cms-pdf-' + name;
		element.title = label;
		element.setAttribute('aria-label', label);
		element.innerHTML = icon(name);

		return element;
	};

	const attach = (element) => {
		if (!element || viewers.has(element) || !element.getAttribute('data-pdf')) {
			return null;
		}

		const url = resolve(element.getAttribute('data-pdf'));
		const name = element.getAttribute('data-name') || decodeURIComponent(url.split('/').pop().split('?')[0]);
		const download = element.getAttribute('data-download') !== '0';
		const state = { pdf: null, pages: [], scale: 1, fit: true, size: null, tasks: new Map(), current: 1 };

		const bar = document.createElement('div');
		const pagesBox = document.createElement('div');
		const status = document.createElement('p');
		const previous = button('previous', text('PREVIOUS', 'Previous page'));
		const following = button('next', text('NEXT', 'Next page'));
		const number = document.createElement('input');
		const count = document.createElement('span');
		const zoomOut = button('zoom-out', text('ZOOM_OUT', 'Zoom out'));
		const zoomIn = button('zoom-in', text('ZOOM_IN', 'Zoom in'));
		const fit = button('fit', text('FIT', 'Fit to width'));
		const full = button('fullscreen', text('FULLSCREEN', 'Full screen'));
		const zoom = document.createElement('span');

		bar.className = 'cms-pdf-bar';
		bar.setAttribute('role', 'toolbar');
		bar.setAttribute('aria-label', name);
		number.type = 'number';
		number.min = '1';
		number.value = '1';
		number.className = 'cms-pdf-number';
		number.setAttribute('aria-label', text('PAGE', 'Page'));
		count.className = 'cms-pdf-count';
		zoom.className = 'cms-pdf-zoom';
		zoom.setAttribute('aria-live', 'polite');

		const group = (...children) => {
			const span = document.createElement('span');

			span.className = 'cms-pdf-group';
			children.forEach((child) => span.appendChild(child));

			return span;
		};

		bar.appendChild(group(previous, number, count, following));
		bar.appendChild(group(zoomOut, zoom, zoomIn, fit));

		const end = group(full);

		if (download) {
			const link = document.createElement('a');

			link.className = 'cms-pdf-button cms-pdf-download';
			link.href = url;
			link.setAttribute('download', name);
			link.title = text('DOWNLOAD', 'Download');
			link.setAttribute('aria-label', text('DOWNLOAD', 'Download'));
			link.innerHTML = icon('download');
			end.appendChild(link);
		}

		bar.appendChild(end);

		pagesBox.className = 'cms-pdf-pages';
		pagesBox.tabIndex = 0;
		pagesBox.setAttribute('role', 'region');
		pagesBox.setAttribute('aria-label', name);
		status.className = 'cms-pdf-status';
		status.setAttribute('role', 'status');
		status.textContent = text('LOADING', 'Loading the PDF…');

		element.classList.add('cms-pdf-viewer');
		element.prepend(bar, pagesBox, status);

		const pageWidth = () => Math.max(100, pagesBox.clientWidth - 32);

		const setZoom = () => {
			zoom.textContent = Math.round(state.scale * 100) + '%';
			zoomOut.disabled = state.scale <= ZOOM_MIN;
			zoomIn.disabled = state.scale >= ZOOM_MAX;
		};

		// Pages not drawn yet take the first page's size
		const sizePage = (entry) => {
			const size = entry.size || state.size;

			entry.box.style.width = Math.floor(size.width * state.scale) + 'px';
			entry.box.style.height = Math.floor(size.height * state.scale) + 'px';
		};

		const draw = (entry) => {
			if (state.tasks.has(entry.number)) {
				state.tasks.get(entry.number).cancel();
			}

			state.pdf.getPage(entry.number).then((page) => {
				const viewport = page.getViewport({ scale: 1 });

				entry.size = { width: viewport.width, height: viewport.height };
				sizePage(entry);

				const canvas = entry.canvas || document.createElement('canvas');
				const task = render(page, state.scale, canvas);

				entry.canvas = canvas;
				entry.drawn = state.scale;
				state.tasks.set(entry.number, task);

				task.promise.then(() => {
					state.tasks.delete(entry.number);

					if (!canvas.parentNode) {
						entry.box.appendChild(canvas);
					}
				}).catch(() => {
					state.tasks.delete(entry.number);
				});
			});
		};

		const observer = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => entries.forEach((item) => {
			const entry = state.pages[parseInt(item.target.getAttribute('data-page'), 10) - 1];

			entry.visible = item.isIntersecting;

			if (item.isIntersecting && entry.drawn !== state.scale) {
				draw(entry);
			}
		}), { root: pagesBox, rootMargin: '300px 0px' }) : null;

		const redraw = () => {
			setZoom();
			state.pages.forEach((entry) => {
				sizePage(entry);

				if (entry.visible || !observer) {
					draw(entry);
				}
			});
		};

		const scaleTo = (scale, fitting) => {
			const page = state.current;

			state.fit = !!fitting;
			state.scale = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, scale));
			redraw();
			goTo(page, true);
		};

		const fitWidth = () => {
			if (state.size) {
				scaleTo(pageWidth() / state.size.width, true);
			}
		};

		// A page's top in the scrolling box
		const offset = (entry) => entry.box.getBoundingClientRect().top - pagesBox.getBoundingClientRect().top + pagesBox.scrollTop;

		const goTo = (number, instant) => {
			const entry = state.pages[Math.min(state.pages.length, Math.max(1, number)) - 1];

			if (entry) {
				pagesBox.scrollTo({ top: offset(entry) - 12, behavior: instant ? 'instant' : 'smooth' });
			}
		};

		const current = () => {
			const top = pagesBox.scrollTop + pagesBox.clientHeight / 3;
			let found = 1;

			state.pages.forEach((entry) => {
				if (offset(entry) <= top) {
					found = entry.number;
				}
			});

			if (found !== state.current) {
				state.current = found;
				number.value = String(found);
			}

			previous.disabled = found <= 1;
			following.disabled = found >= state.pages.length;
		};

		previous.addEventListener('click', () => goTo(state.current - 1));
		following.addEventListener('click', () => goTo(state.current + 1));
		number.addEventListener('change', () => goTo(parseInt(number.value, 10) || 1));
		zoomIn.addEventListener('click', () => scaleTo(state.scale * ZOOM_STEP));
		zoomOut.addEventListener('click', () => scaleTo(state.scale / ZOOM_STEP));
		fit.addEventListener('click', fitWidth);
		pagesBox.addEventListener('scroll', current, { passive: true });

		full.addEventListener('click', () => {
			if (document.fullscreenElement === element) {
				document.exitFullscreen();
			} else if (element.requestFullscreen) {
				element.requestFullscreen().catch(() => {});
			}
		});

		const onFullscreen = () => {
			const on = document.fullscreenElement === element;

			full.innerHTML = icon(on ? 'fullscreen-exit' : 'fullscreen');
			full.title = on ? text('EXIT_FULLSCREEN', 'Exit full screen') : text('FULLSCREEN', 'Full screen');
			full.setAttribute('aria-label', full.title);
		};

		document.addEventListener('fullscreenchange', onFullscreen);

		if (!document.fullscreenEnabled) {
			full.hidden = true;
		}

		let resizing = 0;
		const resize = 'ResizeObserver' in window ? new ResizeObserver(() => {
			clearTimeout(resizing);
			resizing = setTimeout(() => {
				if (state.fit && state.pdf) {
					fitWidth();
				}
			}, 150);
		}) : null;

		open(url).then((pdf) => {
			state.pdf = pdf;

			return pdf.getPage(1).then((page) => {
				const viewport = page.getViewport({ scale: 1 });

				state.size = { width: viewport.width, height: viewport.height };
				count.textContent = '/ ' + pdf.numPages;
				number.max = String(pdf.numPages);

				for (let i = 1; i <= pdf.numPages; i += 1) {
					const box = document.createElement('div');

					box.className = 'cms-pdf-page';
					box.setAttribute('data-page', String(i));
					pagesBox.appendChild(box);
					state.pages.push({ number: i, box, canvas: null, drawn: 0, size: null, visible: false });

					if (observer) {
						observer.observe(box);
					}
				}

				status.textContent = '';
				status.hidden = true;
				element.classList.add('is-loaded');

				// Measured once the pages' box is shown
				state.scale = pageWidth() / state.size.width;
				redraw();
				current();

				if (resize) {
					resize.observe(pagesBox);
				}
			});
		}).catch(() => {
			status.textContent = text('ERROR', 'This PDF can\'t be shown here.');
			element.classList.add('is-failed');
		});

		const viewer = {
			element,
			destroy() {
				document.removeEventListener('fullscreenchange', onFullscreen);

				if (observer) {
					observer.disconnect();
				}

				if (resize) {
					resize.disconnect();
				}

				state.tasks.forEach((task) => task.cancel());

				close(state.pdf);

				[bar, pagesBox, status].forEach((node) => node.remove());
				element.classList.remove('cms-pdf-viewer', 'is-loaded', 'is-failed');
				viewers.delete(element);
			},
		};

		viewers.set(element, viewer);

		return viewer;
	};

	const detach = (element) => {
		const viewer = viewers.get(element);

		if (viewer) {
			viewer.destroy();
		}
	};

	CMS.pdfViewer = {
		load,
		open,
		close,
		thumbnail,
		thumbnails,
		attach,
		detach,
		start(root) {
			(root || document).querySelectorAll('.cms-pdf[data-pdf]').forEach(attach);
			thumbnails(root);
		},
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => CMS.pdfViewer.start());
	} else {
		CMS.pdfViewer.start();
	}
})(window, document);
