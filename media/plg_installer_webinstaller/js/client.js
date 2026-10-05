/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The "Install from Web" tab: loads the Joomla! Extensions Directory browser from the remote server and fills the install form.
 * The markup the server sends calls Joomla.loadweb(), Joomla.installfromweb(), Joomla.apps.initiateSearch() and the global
 * apps_base_url in inline handlers, so those names are kept.
 */
((window, document, Joomla) => {
	'use strict';

	if (!Joomla) {
		throw new Error('Joomla API is not properly initialised');
	}

	const byId = (id) => document.getElementById(id);
	const options = () => Joomla.apps.options;

	Joomla.apps = {
		view: 'dashboard',
		id: 0,
		ordering: '',
		version: 'current',
		list: 0,
		loaded: 0,
		options: {},
	};

	const isRemote = (url) => url.indexOf(options().base_url) === 0 || /^index\.php/.test(url);

	const setHidden = (element, hidden) => {
		if (element) {
			element.hidden = hidden;
		}
	};

	// Only over the directory: Joomla.loadingLayer() covers the whole window, which blocked the backend while the server was slow
	const setLoading = (loading) => {
		const directory = byId('webinstaller-directory');

		setHidden(byId('webinstaller-loading'), !loading);

		if (directory) {
			directory.setAttribute('aria-busy', loading ? 'true' : 'false');
		}
	};

	Joomla.apps.hideLoadingLayer = () => setLoading(false);

	const showError = () => {
		setHidden(byId('web-loader'), true);
		setHidden(byId('web-loader-error'), false);
		setLoading(false);
	};

	// Same limit as the jQuery version had; fetch() itself never gives up
	const timeout = 20000;

	const getJson = (url) => {
		const controller = window.AbortController ? new AbortController() : null;
		const timer = controller ? window.setTimeout(() => controller.abort(), timeout) : 0;

		return fetch(url, { credentials: 'omit', signal: controller ? controller.signal : undefined })
			.then((response) => {
				if (!response.ok) {
					throw new Error(response.status + ' ' + response.statusText);
				}

				return response.json();
			})
			.finally(() => window.clearTimeout(timer));
	};

	// A slow response mustn't replace the one to a later request (e.g. a search typed while a category was loading)
	let lastRequest = 0;

	// Grid or list of extensions; the server's markup has both, one of them with Bootstrap's "hidden" class
	const setListView = (list) => {
		Joomla.apps.list = list ? 1 : 0;

		document.querySelectorAll('.list-container').forEach((element) => element.classList.toggle('hidden', !list));
		document.querySelectorAll('.grid-container').forEach((element) => element.classList.toggle('hidden', list));

		const gridButton = byId('btn-grid-view');
		const listButton = byId('btn-list-view');

		if (gridButton) {
			gridButton.classList.toggle('active', !list);
		}

		if (listButton) {
			listButton.classList.toggle('active', list);
		}
	};

	// Links to other views of the directory: their href is relative to the remote server, so it's kept aside and handled on click
	const prepareLinks = () => {
		document.querySelectorAll('#jed-container a.transcode').forEach((link) => {
			if (!link.hasAttribute('data-webinstaller-href')) {
				link.setAttribute('data-webinstaller-href', link.getAttribute('href') || '');
				link.setAttribute('href', '#');
			}
		});
	};

	Joomla.loadweb = (url) => {
		if (!url) {
			return false;
		}

		if (!isRemote(url)) {
			window.open(url, '_blank');

			return false;
		}

		const searchOrdering = byId('com-apps-ordering');
		const searchVersion = byId('com-apps-filter-joomla-version');

		url += '&product=' + options().product + '&release=' + options().release + '&dev_level=' + options().dev_level
			+ '&list=' + (Joomla.apps.list ? 'list' : 'grid') + '&pv=' + options().pv;

		if (Joomla.apps.ordering !== '' && searchOrdering && searchOrdering.value) {
			url += '&ordering=' + searchOrdering.value;
		}

		if (Joomla.apps.version !== '' && searchVersion && searchVersion.value) {
			url += '&filter_version=' + searchVersion.value;
		}

		const request = ++lastRequest;

		window.scrollTo(0, 0);
		setHidden(byId('web-loader-error'), true);
		setLoading(true);

		getJson(url)
			.then((response) => {
				if (request !== lastRequest) {
					return;
				}

				const container = byId('jed-container');

				setHidden(byId('web-loader'), true);
				container.innerHTML = response.data.html;

				const installAt = byId('joomlaapsinstallatinput');

				if (installAt) {
					installAt.value = options().installat_url;
				}

				prepareLinks();

				if (Joomla.apps.list) {
					setListView(true);
				}

				setLoading(false);
			})
			.catch(() => {
				if (request === lastRequest) {
					showError();
				}
			});

		return true;
	};

	Joomla.webpaginate = (url, target) => {
		const loader = byId('web-paginate-loader');

		setHidden(loader, false);

		getJson(url)
			.then((response) => {
				setHidden(loader, true);
				byId(target).innerHTML = response.data.html;
				prepareLinks();
			})
			.catch(() => setHidden(loader, true));
	};

	// For extensions which are bought or registered on the developer's site, which then sends the site back here
	Joomla.installfromwebexternal = (redirectUrl) => {
		const message = Joomla.JText._('PLG_INSTALLER_WEBINSTALLER_REDIRECT_TO_EXTERNAL_SITE_TO_INSTALL').replace('[SITEURL]', redirectUrl);

		if (!window.confirm(message)) {
			return false;
		}

		const form = byId('adminForm');

		form.setAttribute('action', redirectUrl);
		['task', 'install_directory', 'install_url', 'installtype', 'filter_search'].forEach((name) => {
			form.querySelectorAll('input[name="' + name + '"]').forEach((input) => {
				input.disabled = true;
			});
		});

		return true;
	};

	Joomla.installfromweb = (installUrl, name) => {
		if (!installUrl) {
			window.alert(Joomla.JText._('PLG_INSTALLER_WEBINSTALLER_CANNOT_INSTALL_EXTENSION_IN_PLUGIN'));

			return false;
		}

		byId('install_url').value = installUrl;
		byId('uploadform-web-url').textContent = installUrl;
		byId('uploadform-web-name').textContent = name || '';
		setHidden(byId('uploadform-web-name-label'), !name);
		setHidden(byId('jed-container'), true);
		setHidden(byId('uploadform-web'), false);

		return true;
	};

	Joomla.installfromwebcancel = () => {
		setHidden(byId('uploadform-web'), true);
		setHidden(byId('jed-container'), false);

		if (Joomla.apps.list) {
			setListView(true);
		}
	};

	Joomla.installfromwebajaxsubmit = () => {
		let tail = '&view=' + Joomla.apps.view;
		const searchBox = byId('com-apps-searchbox');
		const searchOrdering = byId('com-apps-ordering');
		const searchVersion = byId('com-apps-filter-joomla-version');
		let ordering = Joomla.apps.ordering;
		let version = Joomla.apps.version;

		if (Joomla.apps.id) {
			tail += '&id=' + Joomla.apps.id;
		}

		if (searchBox && searchBox.value) {
			tail += '&filter_search=' + encodeURI(searchBox.value.toLowerCase().replace(/ +/g, '_').replace(/[^a-z0-9-_]/g, '').trim());
		}

		if (ordering !== '' && searchOrdering && searchOrdering.value) {
			ordering = searchOrdering.value;
		}

		if (ordering) {
			tail += '&ordering=' + ordering;
		}

		if (version !== '' && searchVersion && searchVersion.value) {
			version = searchVersion.value;
		}

		if (version) {
			tail += '&filter_version=' + version;
		}

		Joomla.loadweb(options().base_url + 'index.php?format=json&option=com_apps' + tail);
	};

	Joomla.apps.initiateSearch = () => {
		Joomla.apps.view = 'dashboard';
		Joomla.installfromwebajaxsubmit();
	};

	Joomla.apps.initialize = () => {
		if (Joomla.apps.loaded) {
			return;
		}

		Joomla.apps.loaded = 1;
		Joomla.loadweb(options().base_url + 'index.php?format=json&option=com_apps&view=dashboard');

		if (options().installfrom_url) {
			Joomla.installfromweb(options().installfrom_url);
		}
	};

	Joomla.submitbutton5 = () => {
		const form = byId('adminForm');
		const url = form.install_url.value;

		if (url !== '' && url !== 'http://') {
			Joomla.submitbutton4();
		} else if (url === '') {
			window.alert(Joomla.JText._('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'));
		} else {
			setHidden(byId('appsloading'), false);
			form.installtype.value = 'web';
			form.submit();
		}
	};

	// The server's markup is replaced on every load, so its controls are handled from the document
	const onClick = (event) => {
		const target = event.target instanceof Element ? event.target : null;

		if (!target) {
			return;
		}

		const action = target.closest('[data-webinstaller-action]');

		if (action) {
			switch (action.getAttribute('data-webinstaller-action')) {
				case 'install':
					if (options().installfromon) {
						Joomla.submitbutton4();
					} else {
						Joomla.submitbutton5();
					}
					break;
				case 'cancel':
					Joomla.installfromwebcancel();
					break;
				case 'close-error':
					setHidden(byId('web-loader-error'), true);
					break;
			}

			return;
		}

		const link = target.closest('#jed-container a.transcode[data-webinstaller-href]');

		if (link) {
			const url = link.getAttribute('data-webinstaller-href');

			event.preventDefault();

			if (!isRemote(url)) {
				Joomla.loadweb(url);

				return;
			}

			Joomla.apps.view = url.replace(/^.+[&?]view=(\w+).*$/, '$1');

			if (Joomla.apps.view === 'dashboard') {
				Joomla.apps.id = 0;
			} else if (Joomla.apps.view === 'category') {
				Joomla.apps.id = url.replace(/^.+[&?]id=(\d+).*$/, '$1');
			}

			Joomla.loadweb(url.indexOf(options().base_url) === 0 ? url : options().base_url + url);

			return;
		}

		if (target.closest('#search-reset')) {
			byId('com-apps-searchbox').value = '';
			Joomla.apps.initiateSearch();
		} else if (target.closest('.grid-view')) {
			setListView(false);
		} else if (target.closest('.list-view')) {
			setListView(true);
		}
	};

	const onChange = (event) => {
		if (event.target.id === 'com-apps-ordering') {
			Joomla.apps.ordering = event.target.selectedIndex;
			Joomla.installfromwebajaxsubmit();
		} else if (event.target.id === 'com-apps-filter-joomla-version') {
			Joomla.apps.version = event.target.selectedIndex;
			Joomla.installfromwebajaxsubmit();
		}
	};

	const onKeyDown = (event) => {
		if (event.key === 'Enter' && event.target.id === 'com-apps-searchbox') {
			event.preventDefault();
			Joomla.apps.initiateSearch();
		}
	};

	document.addEventListener('DOMContentLoaded', () => {
		Joomla.apps.options = Joomla.getOptions('plg_installer_webinstaller', {});

		// Inline handlers in the server's markup use this global
		window.apps_base_url = options().base_url;

		const pane = byId('web');
		const tabLink = document.querySelector('#myTabTabs a[href="#web"]');

		if (!pane) {
			return;
		}

		document.addEventListener('click', onClick);
		document.addEventListener('change', onChange);
		document.addEventListener('keydown', onKeyDown);

		/*
		 * Load the directory once the tab is shown, however it's shown: by a click, or by the script which reopens the last tab
		 * (which uses jQuery's click(), which doesn't run native listeners on links)
		 */
		const loadWhenActive = () => {
			if (pane.classList.contains('active')) {
				Joomla.apps.initialize();
			}
		};

		new MutationObserver(loadWhenActive).observe(pane, { attributes: true, attributeFilter: ['class'] });
		loadWhenActive();

		// An extension to install was passed in the URL (e.g. by the developer's site): show it in this tab
		if (options().installfrom_url && tabLink) {
			tabLink.click();
		}
	});
})(window, document, window.Joomla);
