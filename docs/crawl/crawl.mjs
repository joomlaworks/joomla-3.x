/*
 * Crawls a site's administrator and front end in headless Chrome and reports pages with problems: an error status, PHP
 * messages in the page (Warning/Notice/Deprecated/Fatal error ... on line N), database errors (SQLSTATE), script errors,
 * and files the page asked for that failed (4xx/5xx).
 *
 * Usage: node docs/crawl/crawl.mjs --base=http://127.0.0.1:8080 [--user=admin] [--password=...] [options]
 *   --base=URL          the site's address (with its folder, without a trailing slash)
 *   --user, --password  an administrator's login (CRAWL_PASSWORD is read when --password is left out); without
 *                       a password only the front end is crawled
 *   --admin-max=N       administrator pages at most (default 300), --site-max=N front-end pages at most (default 150)
 *   --wait=MS           time given to each page after it starts loading (default 1800)
 *   --ignore=REGEX      script errors matching it aren't reported (default: Chrome's aborted page transitions)
 *   --chrome=PATH       the Chrome binary (default google-chrome)
 *
 * Administrator pages: the top menu, the list sidebars and the Control Panel's icons, then the sidebars of every page
 * visited; links with a task, a token, logout, tmpl= or format= are left out. Front-end pages: links on the home page,
 * then on every page visited, same site only, without query strings, files and feeds. Needs Node 22 or newer (its
 * WebSocket) and Chrome. Exits with 1 when a page has a problem. Run PHP with error reporting on (e.g. the built-in
 * server: php -d error_reporting=-1 -d display_errors=1 -S 127.0.0.1:8080), or PHP's messages can't show up.
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const args = Object.fromEntries(process.argv.slice(2).map((a) => {
	const [key, ...value] = a.replace(/^--/, '').split('=');

	return [key, value.length ? value.join('=') : true];
}));

if (!args.base) {
	console.error('Usage: node docs/crawl/crawl.mjs --base=URL [--user=NAME --password=SECRET] (see the file\'s header)');
	process.exit(2);
}

const base = String(args.base).replace(/\/+$/, '');
const user = args.user || 'admin';
const password = args.password || process.env.CRAWL_PASSWORD || '';
const adminMax = parseInt(args['admin-max'] || 300, 10);
const siteMax = parseInt(args['site-max'] || 150, 10);
const wait = parseInt(args.wait || 1800, 10);
const ignore = new RegExp(args.ignore || 'ViewTransition');
const PHP_MESSAGE = /(Fatal error|Warning|Notice|Deprecated|Parse error|Strict Standards|Recoverable fatal error)\s*:\s[^\n]{0,200}? on line \d+/;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const profile = mkdtempSync(join(tmpdir(), 'crawl-'));
const port = 9300 + Math.floor(Math.random() * 500);
const chrome = spawn(args.chrome || 'google-chrome', ['--headless=new', `--remote-debugging-port=${port}`, '--no-first-run',
	'--ignore-certificate-errors', `--user-data-dir=${profile}`, 'about:blank'], { stdio: 'ignore' });
let target;

for (let i = 0; i < 75 && !target; i++) {
	await sleep(200);

	try {
		target = (await (await fetch(`http://127.0.0.1:${port}/json`)).json()).find((t) => t.type === 'page');
	} catch (error) {
		// Chrome isn't listening yet
	}
}

if (!target) {
	console.error('Chrome did not start');
	chrome.kill();
	process.exit(2);
}

const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve) => ws.addEventListener('open', resolve));

let id = 0;
const pending = new Map();
const page = { status: 0, scripts: [], files: [] };

ws.addEventListener('message', (message) => {
	const data = JSON.parse(message.data);

	if (data.id && pending.has(data.id)) {
		pending.get(data.id)(data);
		pending.delete(data.id);
	}

	if (data.method === 'Runtime.exceptionThrown') {
		const details = data.params.exceptionDetails;
		const text = (details.exception && details.exception.description || details.text || '').split('\n')[0];

		if (!ignore.test(text)) {
			page.scripts.push(text);
		}
	}

	if (data.method === 'Network.responseReceived') {
		const response = data.params.response;

		if (data.params.type === 'Document') {
			page.status = response.status;
		} else if (response.status >= 400 && response.url.startsWith(base)) {
			page.files.push(response.status + ' ' + response.url.replace(base, ''));
		}
	}
});

const send = (method, params = {}) => new Promise((resolve) => {
	const i = ++id;

	pending.set(i, resolve);
	ws.send(JSON.stringify({ id: i, method, params }));
});
const evaluate = async (expression) => {
	const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });

	return result.result && result.result.result ? result.result.result.value : undefined;
};
const visit = async (url) => {
	page.status = 0;
	page.scripts = [];
	page.files = [];
	await send('Page.navigate', { url });
	await sleep(wait);
};

await send('Page.enable');
await send('Runtime.enable');
await send('Network.enable');

let problems = 0;
const check = async (url) => {
	const text = await evaluate(`document.body ? document.body.innerText : ''`) || '';
	const php = (text.match(PHP_MESSAGE) || [''])[0];
	const database = (text.match(/SQLSTATE\[[^\n]{0,160}/) || [''])[0];
	const found = [];

	if (page.status >= 400) {
		found.push('status ' + page.status);
	}

	if (php) {
		found.push('PHP: ' + php);
	}

	if (database) {
		found.push('database: ' + database);
	}

	if (page.scripts.length) {
		found.push('script: ' + page.scripts.join(' ; '));
	}

	if (page.files.length) {
		found.push('files: ' + page.files.join(', '));
	}

	if (found.length) {
		problems++;
		console.log('!! ' + url.replace(base, '') + '\n   ' + found.join('\n   '));
	}
};
const crawl = async (start, collect, max) => {
	const queue = [...start];
	const seen = new Set(queue);
	let count = 0;

	while (queue.length && count < max) {
		const url = queue.shift();

		count++;
		await visit(url);
		await check(url);

		for (const link of await evaluate(collect) || []) {
			if (!seen.has(link)) {
				seen.add(link);
				queue.push(link);
			}
		}
	}

	return count;
};

let adminCount = 0;

if (password) {
	await visit(base + '/administrator/');
	await evaluate(`(() => {
		const form = document.getElementById('form-login');
		document.getElementById('mod-login-username').value = ${JSON.stringify(user)};
		document.getElementById('mod-login-password').value = ${JSON.stringify(password)};
		form.submit();
	})()`);
	await sleep(3000);

	if (await evaluate(`!!document.getElementById('form-login')`)) {
		console.error('The administrator login failed');
	} else {
		const adminLinks = `[...new Set([...document.querySelectorAll('#menu a[href*="option="], #submenu a[href*="option="], .j-sidebar-container a[href*="option="], .quick-icons a[href*="option="]')]
			.map((a) => a.href).filter((h) => h.startsWith(${JSON.stringify(base + '/administrator/')}) && !/task=|logout|tmpl=|format=|[0-9a-f]{32}=1/.test(h)))]`;
		const start = await evaluate(adminLinks) || [];

		adminCount = await crawl(start, adminLinks, adminMax);
	}
}

await visit(base + '/');
await check(base + '/');

const siteLinks = `[...new Set([...document.querySelectorAll('a[href]')].map((a) => a.href.split('#')[0])
	.filter((h) => h.startsWith(${JSON.stringify(base + '/')}) && !/[?]|\\.(jpe?g|png|gif|webp|svg|pdf|zip|xml)$|\\/administrator|feed|logout|component\\/mailto/i.test(h)))]`;
const siteCount = 1 + await crawl((await evaluate(siteLinks) || []).filter((h) => h !== base + '/'), siteLinks, siteMax - 1);

console.log(`${adminCount + siteCount} pages (${adminCount} administrator, ${siteCount} site); ${problems} with problems`);
ws.close();
chrome.kill();
await sleep(300);
rmSync(profile, { recursive: true, force: true });
process.exit(problems ? 1 : 0);
