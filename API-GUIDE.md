# Joomla 3.x UTD: developer API additions (for extension and template developers)

Joomla 3.x UTD keeps Joomla 3's API, so extensions made for Joomla 3 keep working. It also adds to that API and fixes parts of it that didn't work as documented. This guide covers those changes, starting with the document's `<head>`.

For commands of your own on the command line, see [CLI-GUIDE.md](CLI-GUIDE.md). Each change is also listed in [CHANGELOG.md](CHANGELOG.md), with the release that made it.

## CONTENTS

- [The document head](#the-document-head)
  - [Resource hints: preconnect, dns-prefetch, preload](#resource-hints-preconnect-dns-prefetch-preload)
  - [HTTP Link headers](#http-link-headers)
  - [Other links](#other-links)
  - [Order of output](#order-of-output)
  - [Duplicates](#duplicates)
  - [Reading the document's links](#reading-the-documents-links)
- [Other additions](#other-additions)
- [Compatibility with stock Joomla 3, Joomla 4 and later](#compatibility-with-stock-joomla-3-joomla-4-and-later)

## THE DOCUMENT HEAD

Everything here is a method of the HTML document (`Joomla\CMS\Document\HtmlDocument`, `JDocumentHtml`), which you get with `JFactory::getDocument()` or `$app->getDocument()`. Components, modules, plugins and templates can call these methods at any time before the head is rendered, which happens last, after the component and the modules. `onBeforeCompileHead` is the last event that can still change the head. The methods return the document, so calls can be chained.

### Resource hints: preconnect, dns-prefetch, preload

Since 3.18:

```php
$doc = JFactory::getDocument();

// Connect early to another origin. true = crossorigin="anonymous", needed when fonts or other CORS requests come from it.
// Adds a dns-prefetch for the same origin too, for browsers without preconnect (third argument false: preconnect only).
$doc->addPreconnect('https://fonts.gstatic.com', true);

// Only resolve the host name
$doc->addDnsPrefetch('https://stats.example.org');

// Fetch a resource early, as what the second argument names (style, script, font, image, fetch, ...)
$doc->addPreload(JUri::root(true) . '/media/com_example/fonts/inter.woff2', 'font', array('type' => 'font/woff2'));
$doc->addPreload(JUri::root(true) . '/images/uploads/hero.webp', 'image', array('fetchpriority' => 'high'));
```

The result, wherever in the request these calls were made:

```html
<link href="https://fonts.gstatic.com" rel="preconnect" crossorigin="anonymous" />
<link href="https://fonts.gstatic.com" rel="dns-prefetch" />
<link href="https://stats.example.org" rel="dns-prefetch" />
<link href="/media/com_example/fonts/inter.woff2" rel="preload" as="font" type="font/woff2" crossorigin="anonymous" />
<link href="/images/uploads/hero.webp" rel="preload" as="image" fetchpriority="high" />
```

- **`addPreconnect($href, $crossOrigin = false, $dnsPrefetch = true)`**: the hint is for the *origin* of `$href` (scheme, host and port, lowercased), so any address on it will do. `$crossOrigin` can be `true` or `'anonymous'`, `'use-credentials'`, or `false` for none. A browser opens separate connections for requests with and without CORS, so the hint must match how the resources are requested: fonts always need `true`.
- **`addDnsPrefetch($href)`**: also reduced to the origin.
- **`addPreload($href, $as, $attribs = array())`**: `$attribs` adds attributes, e.g. `type`, `media`, `fetchpriority`, `crossorigin`. Fonts get `crossorigin="anonymous"` unless you give `crossorigin` yourself, because browsers fetch fonts in CORS mode and would otherwise ignore the preload. A preload must match the real request (same address, same `crossorigin`), or the resource is fetched twice.

The same links can still be added with `addHeadLink()` (e.g. `addHeadLink($origin, 'preconnect')`), and they're ordered and sent the same way.

### HTTP Link headers

Hint links (`preconnect`, `dns-prefetch`, `preload`, `modulepreload`, however they were added) are also sent as HTTP `Link:` headers. Headers reach the browser before the HTML, and some servers and CDNs turn them into `103 Early Hints`. For the example above:

```
Link: <https://fonts.gstatic.com>; rel=preconnect; crossorigin=anonymous, <https://fonts.gstatic.com>; rel=dns-prefetch, ...
```

- **Size limit:** at most 2,000 bytes of `Link:` headers per response. Hints beyond that only appear as `<link>` tags. Servers and proxies refuse responses with large headers (nginx answers 502 Bad Gateway), so preloading dozens of files would otherwise break the page.
- **Skipped:** addresses a header can't carry (non-ASCII characters, spaces, `<` or `>`), and hints the response already has.
- **Parameters:** `as`, `type`, `crossorigin`, `media` and `fetchpriority` are carried over.
- **Page cache:** the System - Page Cache plugin stores headers with the page, so cached pages send them too.
- **Turning them off:** `$doc->setLinkHeaders(false)`, e.g. from a system plugin, before the head is rendered. `getLinkHeaders()` tells whether they're on.

### Other links

`addHeadLink($href, $relation, $relType = 'rel', $attribs = array())` writes any `<link>`: canonical, alternate, icons, feeds and so on. Since 3.18:

- **One link per address *and* relation.** Before, links were stored by address only, so a second link to the same address replaced the first, whatever its `rel` (a dns-prefetch replaced the preconnect for the same origin, a canonical replaced a feed with the same address). Adding the same address with the same `rel` again still replaces it, now with the new attributes.
- **Escaped.** The address, the relation and every attribute value are escaped when written. Entities already in them are kept (no double encoding), so `&amp;` from `JRoute::_()` or your own `htmlspecialchars()` stays as it is. Before, the values were written exactly as given, and a value with a quote could add attributes of its own.
- **Attributes:** names that aren't valid attribute names are left out, and so are values that are `null`, `false` or not scalar. `true` writes an empty value (`crossorigin=""`).

### Order of output

What `<jdoc:include type="head" />` writes, from top to bottom:

1. `<meta charset>`
2. `<base>`, when set
3. Meta tags (`setMetaData()`): robots, Open Graph, X, `http-equiv`, ...
4. `<meta name="description">`, `<meta name="generator">`
5. `<title>`
6. Links:
   1. every **preconnect**
   2. every **dns-prefetch**
   3. every **preload** and **modulepreload**
   4. every other link (icons, canonical, alternates, feeds), in the order they were added
7. Stylesheets (`addStyleSheet()`)
8. Inline styles (`addStyleDeclaration()`)
9. Script options (`addScriptOptions()`, the `joomla-script-options` JSON)
10. Scripts (`addScript()`)
11. Inline scripts (`addScriptDeclaration()`)
12. Custom tags (`addCustomTag()`)

Each group keeps the order its items were added in. So the hints come right after the title, before every stylesheet and script, whichever extension added them and when. Custom tags come last, after every script, so don't use `addCustomTag()` for resource hints: use the methods above. Anything the template writes itself, before or after the `<jdoc:include>` tag, stays where it is.

### Duplicates

Since 3.18, the same content added more than once is written once. Duplicates usually come from a loop or a module shown twice.

- **`addCustomTag($html)`**: the same tag (after trimming) is kept once.
- **`addScriptDeclaration($content)` and `addStyleDeclaration($content)`**: the same code again, for the same type, is kept once. Only a whole declaration counts: code which merely contains, or is part of, an earlier declaration is still added.
- **Links:** one per address and relation (above).
- **Stylesheets and scripts:** stored by address, as before, so each is written once.

### Reading the document's links

`$doc->_links` is an array of arrays with `relation`, `relType`, `attribs` and, since 3.18, `href`. A link's key is its address, except for further relations of the same address, whose key is the address followed by a NUL byte, `rel=` and the relation. When you look for links, use `href` when it's set and fall back to the key:

```php
// Find the canonical link and remove it (e.g. to add another)
$canonical = null;

foreach ($doc->_links as $key => $link)
{
	if ($link['relation'] === 'canonical')
	{
		$canonical = isset($link['href']) ? $link['href'] : $key;
		unset($doc->_links[$key]);
	}
}
```

## OTHER ADDITIONS

Short notes; the CHANGELOG has the details.

- **Removing menu items' module assignments:** `JModuleHelper::removeMenuAssignments(array $menuIds)` removes the module assignments of deleted menu items (both "on" and "except" rows). A module left without any assignment is set to all pages, as "on all pages except nothing" means all pages. Only positive IDs are taken, so the "all pages" rows (`menuid` 0) are never touched. Use it instead of your own `DELETE` on `#__modules_menu`.
- **Structured data:** "System - Structured Data & Social Tags" triggers `onStructuredDataBeforeRender($context, $data)` before writing its Open Graph, X and JSON-LD data. `$data->page`, `->opengraph`, `->x` and `->graph` can be changed or added to.
- **Tags may be uninstalled:** `TagsHelper::isAvailable()` (`Joomla\CMS\Helper\TagsHelper`) tells whether the Tags component is there. Code using the tags tables directly should check it first.
- **Comparing text as MySQL does:** `UserHelper::getSameTextCondition($db, $column, $value)` builds a SQL condition matching `$value` the way MySQL's default collation compares text (case-insensitively), also on PostgreSQL, SQL Server and SQLite. Use it for "is this name already taken" checks.
- **PostgreSQL and backticks:** both PostgreSQL drivers turn MySQL-style `` `name` `` quoting into `"name"` outside string literals (`JDatabaseDriverPostgresql::translateBackticks()`), so queries written for MySQL run there.
- **Writing PHP configuration files:** the registry's PHP format takes a `sort` option, `$registry->toString('PHP', array('class' => 'JConfig', 'sort' => true))`, which writes the properties in alphabetical order. `configuration.php` is written this way.

## COMPATIBILITY WITH STOCK JOOMLA 3, JOOMLA 4 AND LATER

- The new methods don't exist in stock Joomla 3. An extension meant for both can check first, e.g. `method_exists($doc, 'addPreload')`, and fall back to `addHeadLink()`.
- Joomla 4 and later have different methods for the same things. In Joomla 4, `$doc->getPreloadManager()` sends preloads as HTTP headers only, and the Web Asset Manager handles stylesheets and scripts. Code meant for both should check the version or the methods it uses.
- `addHeadLink()`, `addCustomTag()` and the declarations take the same arguments as in stock Joomla 3. Only the output is better: no duplicates, escaped values, and links no longer replacing each other.
