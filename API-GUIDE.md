# Joomla 3.x UTD: developer API additions (for extension and template developers)

Joomla 3.x UTD keeps Joomla 3's API, so extensions made for Joomla 3 keep working. It also adds to that API and fixes parts of it that didn't work as documented. This guide covers those changes, starting with the document's `<head>`.

For commands of your own on the command line, see [CLI-GUIDE.md](CLI-GUIDE.md). Each change is also listed in [CHANGELOG.md](CHANGELOG.md), with the release that made it.

## CONTENTS

- [The document head](#the-document-head)
  - [Resource hints: preconnect, dns-prefetch, preload](#resource-hints-preconnect-dns-prefetch-preload)
  - [HTTP Link headers](#http-link-headers)
  - [Other links](#other-links)
  - [Scripts and stylesheets](#scripts-and-stylesheets)
  - [Order of output](#order-of-output)
  - [Duplicates](#duplicates)
  - [Reading the document's links](#reading-the-documents-links)
- [Blocks: layouts and the renderer](#blocks-layouts-and-the-renderer)
  - [Overriding a block](#overriding-a-block)
  - [A template's own blocks](#a-templates-own-blocks)
  - [The preview](#the-preview)
  - [Rendering blocks yourself](#rendering-blocks-yourself)
  - [Opening a module's text in an editor of your choice](#opening-a-modules-text-in-an-editor-of-your-choice)
- [Editors: choosing one and switching](#editors-choosing-one-and-switching)
  - [Naming the editor yourself](#naming-the-editor-yourself)
  - [Switching without losing unsaved changes](#switching-without-losing-unsaved-changes)
- [JavaScript: global.js and the CMS namespace](#javascript-globaljs-and-the-cms-namespace)
  - [The old names](#the-old-names)
  - [The modal](#the-modal)
  - [Tooltips, tabs, sliders, tree, password meter](#tooltips-tabs-sliders-tree-password-meter)
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

**In practice:** Hammond, Finch and Rookwood (`templates/<name>/helper.php`, `preload()`, `preloadImage()`, `embedHints()`) preload their stylesheets and script with the exact addresses they load, the article's main image in the article view (Rookwood's with the same `imagesrcset`/`imagesizes` as its `<img>`), and add a preconnect when an article has an X post (whose script the template loads at once), and a dns-prefetch for YouTube videos (which load lazily). They check `method_exists()` first, so the same templates work on Joomla versions without these methods.

The same links can still be added with `addHeadLink()` (e.g. `addHeadLink($origin, 'preconnect')`), and they're ordered and sent the same way.

### HTTP Link headers

Hint links (`preconnect`, `dns-prefetch`, `preload`, `modulepreload`, however they were added) are also sent as HTTP `Link:` headers. Headers reach the browser before the HTML, and some servers and CDNs turn them into `103 Early Hints`. For the example above:

```
Link: <https://fonts.gstatic.com>; rel=preconnect; crossorigin=anonymous, <https://fonts.gstatic.com>; rel=dns-prefetch, ...
```

- **Size limit:** at most 2,000 bytes of `Link:` headers per response. Hints beyond that only appear as `<link>` tags. Servers and proxies refuse responses with large headers (nginx answers 502 Bad Gateway), so preloading dozens of files would otherwise break the page.
- **Skipped:** addresses a header can't carry (non-ASCII characters, spaces, `<` or `>`), and hints the response already has.
- **Parameters:** `as`, `type`, `crossorigin`, `media`, `fetchpriority`, `imagesrcset` and `imagesizes` are carried over. Give an image's `srcset` and `sizes` as `imagesrcset` and `imagesizes`, so the browser preloads the size it will use.
- **Page cache:** the System - Page Cache plugin stores headers with the page, so cached pages send them too.
- **Turning them off:** `$doc->setLinkHeaders(false)`, e.g. from a system plugin, before the head is rendered. `getLinkHeaders()` tells whether they're on.

### Other links

`addHeadLink($href, $relation, $relType = 'rel', $attribs = array())` writes any `<link>`: canonical, alternate, icons, feeds and so on. Since 3.18:

- **One link per address *and* relation.** Before, links were stored by address only, so a second link to the same address replaced the first, whatever its `rel` (a dns-prefetch replaced the preconnect for the same origin, a canonical replaced a feed with the same address). Adding the same address with the same `rel` again still replaces it, now with the new attributes.
- **Escaped.** The address, the relation and every attribute value are escaped when written. Entities already in them are kept (no double encoding), so `&amp;` from `JRoute::_()` or your own `htmlspecialchars()` stays as it is. Before, the values were written exactly as given, and a value with a quote could add attributes of its own.
- **Attributes:** names that aren't valid attribute names are left out, and so are values that are `null`, `false` or not scalar. `true` writes an empty value (`crossorigin=""`).

### Scripts and stylesheets

`addScript($url, $options = array(), $attribs = array())` and `addStyleSheet($url, $options = array(), $attribs = array())` take any attribute in `$attribs`. `$options` is for Joomla itself (`version`; `conditional`, for old Internet Explorer comments).

```php
// A module script with an ID, and a fallback for browsers without modules
$doc->addScript(JUri::root(true) . '/media/com_example/js/app.js', array(), array('type' => 'module', 'id' => 'example-app'));
$doc->addScript(JUri::root(true) . '/media/com_example/js/app-legacy.js', array(), array('nomodule' => true, 'defer' => true));

// A script from a CDN with Subresource Integrity
$doc->addScript('https://cdn.example.com/lib.js', array(), array('async' => true, 'crossorigin' => 'anonymous',
	'integrity' => 'sha384-…', 'referrerpolicy' => 'no-referrer'));

// Data for the script: arrays are written as JSON
$doc->addScript(JUri::root(true) . '/media/com_example/js/map.js', array(), array('defer' => true, 'data-config' => array('zoom' => 12)));

// A print stylesheet, and an alternate one
$doc->addStyleSheet(JUri::root(true) . '/media/com_example/css/print.css', array(), array('media' => 'print'));
$doc->addStyleSheet(JUri::root(true) . '/media/com_example/css/contrast.css', array(), array('rel' => 'alternate stylesheet', 'title' => 'High contrast'));

// An inline module script
$doc->addScriptDeclaration('import { start } from "/media/com_example/js/app.js"; start();', 'module');
```

In an HTML5 document (`$doc->setHtml5(true)`, as Hammond, Finch, Rookwood, Isis and Prothero set), the first two come out as:

```html
<script src="/media/com_example/js/app.js" type="module" id="example-app"></script>
<script src="/media/com_example/js/app-legacy.js" nomodule defer></script>
```

- **Boolean attributes** (`async`, `defer`, `nomodule`, `disabled`, ...): `true` writes the attribute without a value (`name="name"` outside HTML5), and `false` or `null` leaves it out. Since 3.18 this works for every attribute. Stock Joomla 3 did it for `async` and `defer` only, so `'nomodule' => false` wrote `nomodule=""`, which turns `nomodule` *on*. An empty string still writes an empty value (`'crossorigin' => ''` gives `crossorigin=""`).
- **Escaped:** since 3.18 the URL is escaped too, as link addresses are. Before, a URL with a quote could add attributes of its own. Entities already in it are kept, and a raw `&` becomes `&amp;` (the same URL to the browser). Attribute values were escaped already. Names that aren't valid attribute names are left out (a name with a space could add attributes).
- **`type`:** a JavaScript or CSS type is left out in HTML5 documents (it's the default); any other, e.g. `module`, is written.
- **`rel` on a stylesheet** replaces `stylesheet` and is written once (stock Joomla 3 wrote it twice).
- **One tag per URL:** adding the same URL again merges the attributes into the existing tag, and the tag keeps its place.
- **Inline scripts and styles** take no attributes, and all declarations of one type share one `<script>` or `<style>` block (see [Duplicates](#duplicates)). Top-level `const`/`let` names in two declarations of the same type therefore collide; wrap yours in a block or a function.

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

## BLOCKS: LAYOUTS AND THE RENDERER

"Editor - Blocks (Editor.js)" writes content as blocks (Editor.js' JSON: a list of `{type, data}`), and the "Custom Blocks" module uses the same editor. The form still gets HTML: when the content is saved, the blocks are rendered on the server, one layout per block type, with the **site's default template**'s overrides. So templates decide what every block's HTML is.

The core layouts are in `layouts/joomla/blocks/`: `paragraph`, `header`, `list`, `image`, `embed`, `quote`, `table`, `code`, `raw`, `delimiter`, `warning` and `readmore`. A block type without a layout writes nothing.

### Overriding a block

Copy the layout into your template and change it:

```
templates/<your template>/html/layouts/joomla/blocks/quote.php
```

The layout gets `$displayData` with:

- `data`: the block's data, as Editor.js saves it (each core layout's comment lists its fields; e.g. a quote has `text`, `caption` and `alignment`),
- `block`: the whole block,
- `renderer`: the `Joomla\CMS\Blocks\BlockRenderer`, whose helpers every value must go through:
  - `inline($html)`: text which may hold inline formatting (bold, italic, underline, marker, inline code, links), with everything else removed and links only to http(s), mailto, tel, the site's own paths and anchors,
  - `url($url)`: an address, empty unless it's one of those,
  - `siteUrl($url)`: the same, written as editors store addresses of the site (`images/x.webp` rather than `/subfolder/images/x.webp`),
  - `e($text)`: plain text, escaped,
  - `plain($html)`: the plain text of formatted text (for `alt` and `title` attributes).

```php
<?php
defined('JPATH_BASE') or die;

$r    = $displayData['renderer'];
$data = $displayData['data'];
?>
<figure class="pull-quote">
	<blockquote><?php echo $r->inline($data['text']); ?></blockquote>
	<?php if (!empty($data['caption'])) : ?>
		<figcaption><?php echo $r->inline($data['caption']); ?></figcaption>
	<?php endif; ?>
</figure>
```

The HTML is made when the content is saved: after changing a block's layout, saving the content again rewrites it. The editor keeps the blocks with the HTML (base64 JSON in a hidden element, signed with the site's secret), so it opens them as blocks again. Raw HTML blocks go through the saving user's text filter (Global Configuration), as any HTML does.

### A template's own blocks

A template offers blocks of its own with two files each, in a folder named after it:

```
templates/<template>/html/layouts/joomla/blocks/<template>/<block>.xml   the fields
templates/<template>/html/layouts/joomla/blocks/<template>/<block>.php   the HTML
```

The editor finds them in every installed site template. The block menu offers the site's default template's blocks, each with its template's name as a note ("Testimonials · Rookwood"); other templates' blocks still open when the content has them. Blocks can be as generic (a grid of cells with an image and text each) or as specific (a pricing table) as you want them. The definition:

```xml
<?xml version="1.0" encoding="utf-8"?>
<block title="TPL_MYTEMPLATE_BLOCK_GRID" description="TPL_MYTEMPLATE_BLOCK_GRID_DESC" icon="grid">
    <field name="heading" type="text" label="TPL_MYTEMPLATE_BLOCK_HEADING" />
    <group name="cells" label="TPL_MYTEMPLATE_BLOCK_CELLS" item="TPL_MYTEMPLATE_BLOCK_CELL" min="3" max="3" columns="3">
        <field name="image" type="image" label="TPL_MYTEMPLATE_BLOCK_IMAGE" />
        <field name="title" type="text" label="TPL_MYTEMPLATE_BLOCK_TITLE" />
        <field name="text" type="richtext" label="TPL_MYTEMPLATE_BLOCK_TEXT" />
        <field name="link" type="url" label="TPL_MYTEMPLATE_BLOCK_LINK" />
    </group>
</block>
```

- **Field types:** `text`, `richtext` (inline formatting: bold, italic, links, marker...), `textarea` (plain, several lines), `image` (uploaded through the Media Manager, with alt text; the value is `url`, `alt`, `width`, `height` and `srcset`), `url`, `list` (with `<option value="...">LABEL</option>`), `checkbox`.
- **Groups** (`<group>`): entries of the same fields, from `min` to `max` (Add and Remove buttons), shown in `columns` columns in the editor. Groups hold fields, not other groups.
- **Icons:** `block`, `hero`, `quote`, `grid`, `columns`.
- **Labels** are language strings of the template's language file (`language/en-GB/en-GB.tpl_<template>.ini`, also the site-wide copy).

The layout gets `$displayData['data']` with the values by field name (a group's: a list of entries), plus `template`, `name` and `renderer`. Write every value through the renderer's helpers; `$r->image($value, $sizes, $attributes)` writes an image field as an `<img>`, with its srcset and size:

```php
<?php
defined('JPATH_BASE') or die;

$r    = $displayData['renderer'];
$data = $displayData['data'];
?>
<section class="grid-cells">
	<h2><?php echo $r->e($data['heading']); ?></h2>
	<?php foreach ($data['cells'] as $cell) : ?>
		<div class="cell">
			<?php echo $r->image($cell['image'], '(max-width: 767px) 100vw, 33vw'); ?>
			<h3><?php echo $r->e($cell['title']); ?></h3>
			<?php echo $r->inline($cell['text']); ?>
		</div>
	<?php endforeach; ?>
</section>
```

What's saved is the template's folder name and the block's name, never a path: `{"type": "template", "data": {"template": "rookwood", "block": "testimonials", "fields": {...}}}`. A template block is rendered with, in turn:

1. the site template's `html/layouts/joomla/blocks/<template>/<block>.php` (so any template may restyle another's blocks: `templates/hammond/html/layouts/joomla/blocks/rookwood/testimonials.php`),
2. the layout of the template that has the block, when it's installed, even if it isn't the default,
3. `layouts/joomla/blocks/template.php`, which writes the values plainly (texts, images, links), so content never comes out empty.

Values go through the saving user's text filter, by field type, like the rest of the content.

### The preview

The editor's Preview button shows the blocks as they are (saved or not) on a page made by the layout `joomla.blocks.preview`: by default, the site template's `css/template.css` and `css/custom.css` around the blocks. A template whose content needs more (its own stylesheets, a wrapper with its article classes, a theme) overrides it in `html/layouts/joomla/blocks/preview.php`; Hammond, Finch and Rookwood do. `$displayData`: `html`, `template`, `params` (the default style's options), `base` (the site's address, for `<base>`), `language`, `direction`, `renderer`. The page is shown in a sandboxed frame: embeds' scripts run, in an origin of their own.

### Rendering blocks yourself

```php
use Joomla\CMS\Blocks\BlockRenderer;

$renderer = new BlockRenderer;           // the site's default template, or new BlockRenderer('mytemplate')
echo $renderer->render($blocks);         // $blocks: arrays with "type" and "data"
```

### Opening a module's text in an editor of your choice

A module whose manifest has `<customContent />` is edited in the module form's content field (stored in `#__modules.content`). The manifest may name that field's editor and buttons:

```xml
<customContent editor="editorjs|none" hide="readmore,pagebreak" />
```

`editor`, `syntax`, `buttons`, `hide` and `height` are taken (`JModuleHelper::prepareContentField()`); the field's text filter always stays. "Custom Code" uses `editor="codemirror|none" syntax="html" buttons="false"`, "Custom Blocks" the line above, and "Custom HTML" `editor="jce|tinymce_latest|tinymce|none"`.

## EDITORS: CHOOSING ONE AND SWITCHING

In the administrator, an editor that follows the user's choice gets a menu above it, "Editor: TinyMCE", which switches between TinyMCE, TinyMCE (legacy), Markdown (EasyMDE) and Blocks (Editor.js). Only enabled editors the user's access levels allow are listed. The choice lasts until the session ends; the user's own Editor setting and Global Configuration stay as they were. The site shows no menu, and Global Configuration's "Default Editor (Site)" may set one editor for everyone there.

### Naming the editor yourself

An editor you name stays as it is, without the menu:

- **A form field:** `<field type="editor" editor="codemirror|none" ... />` (the first one enabled, else the next).
- **A module's text:** `<customContent editor="editorjs|none" />` in the manifest (see [Opening a module's text in an editor of your choice](#opening-a-modules-text-in-an-editor-of-your-choice)).
- **In code:** pass `'switcher' => false` in `display()`'s `$params`, e.g. `JEditor::getInstance('tinymce_latest')->display($name, $html, $width, $height, $col, $row, true, $id, null, null, array('switcher' => false))`. An instance of an editor other than the user's never gets the menu; one that happens to be the user's would.

An editor follows the user's choice when it's the one Joomla works out for the user: a field without `editor`, `JFactory::getEditor()`, or `JEditor::getInstance(JFactory::getConfig()->get('editor'))`. These get the menu.

All four editors store HTML, so the content needs no conversion of its own: each editor reads the HTML the form has. Blocks keeps its block data in a hidden, signed element at the start of the content. The other editors leave it there, so going back to Blocks restores the blocks (also template blocks, which can't be made from HTML). If the text was changed in another editor, Blocks makes the blocks again from the HTML and says so.

### Switching without losing unsaved changes

Switching posts the edit form to its `reload` task with the chosen editor (`editor_switch`). Joomla's `FormController::reload()` keeps the posted data in the session and opens the form again, now in the other editor, without saving anything. Core components' forms are found automatically, from the task of their Save button.

A component of your own gets this too once its form says which task to post:

```html
<form action="..." method="post" name="adminForm" id="adminForm" data-editor-reload="item.reload">
```

The task must keep the posted data and open the form again with it. A controller extending `JControllerForm` has `reload()` already (the edit view's model must read `$app->getUserState('com_x.edit.item.data')` in `loadFormData()`, as Joomla's models do). Without the attribute, or with `data-editor-reload=""`, switching asks the user to confirm ("changes you haven't saved will be lost") and loads the page again.

The menu is the layout `joomla.editors.switcher`, which templates can override. `Joomla\CMS\Editor\Editor::getSwitchableEditors()` lists the editors it offers and `getSessionEditor()` returns the one chosen in this session, if any.

## JAVASCRIPT: GLOBAL.JS AND THE CMS NAMESPACE

The CMS's shared JavaScript is one file, `media/system/js/global.js` (minified `global.min.js`), with one global object, `window.CMS`. It holds what `core.js` was and vanilla versions of everything that used to need MooTools: the modal (SqueezeBox), tooltips, tabs, sliders, the tree and the password strength meter. It needs no other library. Its styles are `media/system/css/global.css` (`global.min.css`), always loaded with it. `JHtml::_('behavior.cms')` loads both; so does every behaviour below that needs them.

On the site, visitors' pages get less: `JHtml::_('behavior.core')` loads `media/system/js/frontend.js`, the part of `global.js` that pages for visitors use (options, strings, forms, lists, messages, `CMS.request`, keepalive, captions, highlighting, and the old `Joomla.*` names for them), without the stylesheet. In the administrator, `behavior.core` loads `global.js`. When a page needs more (`behavior.cms`: modals, tooltips, tabs, sliders, trees, front-end editing, the media field...), `global.js` takes `frontend.js`' place in the page, so scripts between them still find the namespace. Extensions should ask for `behavior.core` when the `Joomla.*` basics are enough, and `behavior.cms` for the rest. `media/system/js/core.js` (with `core-uncompressed.js`) is a copy of `global.js` for pages that name the file themselves. It runs once, however often it's included.

Scripts and stylesheets merged into these files were removed: asking for one through `JHtml::_('script')` or `JHtml::_('stylesheet')` runs the behaviour that loads them instead (`HTMLHelper::GLOBAL_FILES`): `system/core.js`, `keepalive.js`, `caption.js`, `highlighter.js` (`behavior.core`), `associations-edit.js`, `frontediting.js`, `permissions.js`, `modal.js`, `tabs.js`, `mootree.js`, `passwordstrength.js`, `keepalive.js`, `editor-switch.js`, `multiselect.js`, `caption.js`, `highlighter.js`, `helpsite.js`, `media/mediafield-mootools.js`, and `system/modal.css`, `mootree.css`, `mootree_rtl.css`, `editor-switch.css`. Polyfills and old-browser fallbacks were removed without a replacement (`HTMLHelper::DROPPED_FILES`; asking for one loads nothing): `system/polyfill.classlist.js`, `polyfill.event.js`, `polyfill.filter.js`, `polyfill.map.js`, `polyfill.xpath.js`, `html5fallback.js`, `progressbar.js`. `JHtml::_('behavior.polyfill')` does nothing.

Behaviours are started from data the page already carries, not from more `<script>` tags: the PHP writes script options and `global.js` reads them when the page is ready. `behavior.keepalive` (`system.keepalive`: `CMS.keepalive`, with `start({interval, uri})` and `stop()` for scripts), `behavior.multiselect($id)` (`system.multiselect`: `CMS.multiselect.attach(id)`), `behavior.caption($selector)` (`system.caption`, once the images are loaded: `CMS.caption.attach(selector)`), `behavior.highlighter($terms)` (`system.highlighter`: `CMS.highlighter.create(options).highlight(words)`). The media field's functions are `CMS.mediaField.insert(value, id)`, `refreshPreview(id)`, `previewTip(tip)` and `imagePathTip(tip, input)`.

MooTools and MooTools More are still bundled, and `JHtml::_('behavior.framework')` (or a script loaded with `array('framework' => true)`) still loads them, for extensions which ask for them. Nothing in the core does.

### The old names

Every name `core.js` and the MooTools behaviours offered still works and calls the new code. New code should use the `CMS` names; the old ones stay for extensions. `CMS.legacy` holds this map too.

| Old | New |
|---|---|
| `Joomla.submitform()` | `CMS.form.submit()` |
| `Joomla.submitbutton()` (pages replace it) | `CMS.form.button()` |
| `Joomla.replaceTokens()` | `CMS.form.replaceTokens()` |
| `Joomla.getOptions()`, `Joomla.loadOptions()`, `Joomla.optionsStorage` | `CMS.options.get()`, `CMS.options.load()`, `CMS.options.storage` |
| `Joomla.Text._()`, `Joomla.JText._()`, `Joomla.Text.load()` | `CMS.text.get()`, `CMS.text.load()` |
| `Joomla.editors.instances` | `CMS.editors.instances` (the same object) |
| `Joomla.checkAll()`, `Joomla.isChecked()`, `Joomla.listItemTask()`, `Joomla.tableOrdering()` | `CMS.list.checkAll()`, `CMS.list.isChecked()`, `CMS.list.itemTask()`, `CMS.list.order()` |
| `Joomla.renderMessages()`, `Joomla.removeMessages()`, `Joomla.ajaxErrorsMessages()` | `CMS.messages.render()`, `CMS.messages.remove()`, `CMS.messages.fromAjaxError()` |
| `Joomla.popupWindow()` | `CMS.window.open()` |
| `Joomla.loadingLayer()` | `CMS.loading.layer()`, `CMS.loading.show()`, `CMS.loading.hide()` |
| `Joomla.extend()`, `Joomla.request()` | `CMS.extend()`, `CMS.request()` |
| `jModalClose()`, `SqueezeBox.close()` | `CMS.modal.close()` |
| `SqueezeBox.open()`, `SqueezeBox.fromElement()`, `SqueezeBox.assign()`, `SqueezeBox.initialize()` | `CMS.modal.open()`, `CMS.modal.assign()`, `CMS.modal.setDefaults()` |
| `SqueezeBox.addEvent()`, `SqueezeBox.removeEvent()` | `CMS.modal.on()`, `CMS.modal.off()` |
| `new Tips()` (`behavior.tooltip`) | `CMS.tooltip.attach()` |
| `new JTabs()` (`tabs.start`) | `CMS.tabs.create()` |
| `new Fx.Accordion()` (`sliders.start`) | `CMS.sliders.create()` |
| `new Form.PasswordStrength()` | `CMS.password.meter()`, `CMS.password.strength()` |
| `new MooTreeControl()` (`behavior.tree`) | `CMS.tree.create()`, `new CMS.tree.Tree()` |
| `Joomla.JMultiSelect()` (`behavior.multiselect`) | `CMS.multiselect.attach()` |
| `new JCaption()` (`behavior.caption`) | `CMS.caption.attach()` |
| `Joomla.Highlighter()` (`behavior.highlighter`) | `CMS.highlighter.create()` |
| `jInsertFieldValue()`, `jMediaRefreshPreview()`, `jMediaRefreshPreviewTip()`, `jMediaRefreshImgpathTip()` | `CMS.mediaField.insert()`, `refreshPreview()`, `previewTip()`, `imagePathTip()` |
| `Joomla.propagateAssociation()`, `Joomla.injectAssociations()`, `hideAssociation()`, `showAssociationMessage()` | `CMS.associations.propagate()`, `inject()`, `hide()`, `showMessage()` |
| `sendPermissions()`, `getUrlParam()` (the Permissions field) | `CMS.permissions.send()`, `CMS.permissions.urlParam()` |
| `frontediting.js` | `CMS.frontEditing.showModule()`, `CMS.frontEditing.showMenuItem()` |

`SqueezeBox`, `JTabs`, `Form.PasswordStrength` and `MooTreeControl` are defined only when MooTools' own versions aren't there: an extension that loads MooTools and `media/system/js/modal.js` itself gets the real SqueezeBox, and `CMS.modal.close()` (so `jModalClose()`) closes whichever box is open.

### The modal

`JHtml::_('behavior.modal', 'a.modal', $options)` makes matching links open in a box, also links added to the page later (subform rows). Links keep SqueezeBox's markup: the options in `rel`, evaluated when the link is clicked, so sizes may be expressions:

```html
<a class="modal" href="index.php?option=com_example&amp;view=pick&amp;tmpl=component"
	rel="{handler: 'iframe', size: {x: Math.min(1000, window.innerWidth - 80), y: 600}}">Pick</a>
```

From script:

```js
CMS.modal.open('index.php?option=com_example&view=pick&tmpl=component', {handler: 'iframe', size: {x: 800, y: 500}});
CMS.modal.on('close', () => { /* ... */ });
CMS.modal.close();          // also from the page inside: window.parent.CMS.modal.close()
```

Handlers are `iframe`, `image`, `ajax` (the default for addresses: the page's HTML, its scripts run), `clone` and `adopt` (an element of the page, `#id`; adopt puts it back when the box closes) and `string`. Options and events are SqueezeBox's: `size`, `closable`, `closeBtn`, `overlay`, `iframeOptions`, `iframePreload`, `classWindow`, `onOpen`, `onClose`, `onUpdate`, `onShow`, `onHide`, `onResize`, `onMove`. Below 980px wide the box fills the screen. The box is `#sbox-window` over `#sbox-overlay`, styled by the modal section of `media/system/css/global.css`, so templates' SqueezeBox styles still apply. Escape and a click on the overlay close it. Events also go out as `cms:modal-open`, `cms:modal-close` and so on, on `document`.

### Tooltips, tabs, sliders, tree, password meter

- **`JHtml::_('behavior.tooltip', '.hasTip', $options)`:** `title="Title::Text"` (or plain text) shown on hover and on focus, in MooTools' markup (`div.tip-wrap > div.tip > div.tip-title + div.tip-text`). The options are as before (`maxTitleChars`, `offset`, `showDelay`, `hideDelay`, `className`, `fixed`, `onShow(tip, element)`, `onHide`).
- **`JHtml::_('tabs.start')` / `JHtml::_('sliders.start')`:** the same markup and options. `onActive` and `onBackground` callbacks now get plain elements, not MooTools ones: use `element.style.display` and `element.classList`, not `setStyle()` or `addClass()`.
- **`JHtml::_('behavior.tree')`:** the same nodes, markup, icons and API (`insert()`, `select()`, `expand()`, `collapse()`, `get()`, `adopt()`, `load()`).
- **Password fields with `strengthmeter="true"`:** `CMS.password.meter(input, {threshold: 66})` draws the bar and sets `data-passwordstrength` on the field.

`behavior.modal` and `behavior.tooltip` no longer load jQuery or MooTools. Code which relied on them for that must ask for `jquery.framework` or `behavior.framework` itself.

## OTHER ADDITIONS

Short notes; the CHANGELOG has the details.

- **Removing menu items' module assignments:** `JModuleHelper::removeMenuAssignments(array $menuIds)` removes the module assignments of deleted menu items (both "on" and "except" rows). A module left without any assignment is set to all pages, as "on all pages except nothing" means all pages. Only positive IDs are taken, so the "all pages" rows (`menuid` 0) are never touched. Use it instead of your own `DELETE` on `#__modules_menu`.
- **Structured data:** "System - Structured Data & Social Tags" triggers `onStructuredDataBeforeRender($context, $data)` before writing its Open Graph, X and JSON-LD data. `$data->page`, `->opengraph`, `->x` and `->graph` can be changed or added to.
- **Tags may be uninstalled:** `TagsHelper::isAvailable()` (`Joomla\CMS\Helper\TagsHelper`) tells whether the Tags component is there. Code using the tags tables directly should check it first.
- **Comparing text as MySQL does:** `UserHelper::getSameTextCondition($db, $column, $value)` builds a SQL condition matching `$value` the way MySQL's default collation compares text (case-insensitively), also on PostgreSQL, SQL Server and SQLite. Use it for "is this name already taken" checks.
- **PostgreSQL and backticks:** both PostgreSQL drivers turn MySQL-style `` `name` `` quoting into `"name"` outside string literals (`JDatabaseDriverPostgresql::translateBackticks()`), so queries written for MySQL run there.
- **Uploads that never replace a file:** `(new JHelperMedia)->getUniqueFilePath($path)` returns `$path` when it's free, else the path with the time added to the name (`photo-20261018-143502.jpg`, then `-2`, `-3`...). The Media Manager's upload controllers use it.
- **Headers of cached views:** with view caching on (`JControllerLegacy::display($cachable = true)`), the response headers a view sets (`$app->setHeader()`, `JResponse::setHeader()`) and its `allowCache(true)` are kept with the cached view and set again on a cache hit, so a view's `Last-Modified`/`ETag` no longer need passing through the page (as K2 did with a `<script type="application/x-k2-headers">`).
- **Browser time of cachable responses:** `$app->getBrowserCacheTime()` returns how long browsers may keep a response marked cachable (`allowCache(true)`) without a `Cache-Control` of its own: Global Configuration's Cache Time in seconds (15 minutes when unset). `respond()` sends `Expires` that far ahead unless an `Expires` is set already (it's a single-value header: the first one set stays), which is how the view cache counts it from when a cached view was stored.
- **The order of system plugins:** the core system plugins run in this order: Little WAF (-100), Language Filter (-90), Remember Me (-80), Page Cache (-70), then the rest (1 to 14) and Debug (1000). New sites get it, and updates set it once, keeping core plugins a site admin moved. Page Cache serves a stored page in `onAfterInitialise` and ends the request there, so a system plugin installed with the default ordering 0 doesn't run on a cached page at all (its `onAfterInitialise` included). A plugin which must act before a cached page is served, e.g. to redirect or block a request, needs an ordering below -70. If it only needs to keep a page out of the cache, a `pagecache` plugin (`onPageCacheSetCaching`, `onPageCacheIsExcluded`) is the better way.
- **Core media files that moved:** administrator-only extensions keep their scripts and styles in their own folders (`administrator/components/com_x/js`, `css`; `HTMLHelper::MOVED_FILES`), and `media/` folders are named after their extension (`media/contacts` is `media/com_contact`, `media/media` is `media/com_media`, `media/mailto` is `media/com_mailto`; the debug console's `media/cms` files are in `media/system`; `HTMLHelper::MOVED_FOLDERS`). `JHtml::_('script')`, `JHtml::_('stylesheet')` and `JHtml::_('image')` still find a file by its old name, relative (`contacts/con_tel.png`, `media/mediafield.min.js`) or from the site's root (`media/media/js/mediafield.min.js`), trying the old place first, so a template's copy under the old name still wins. Addresses written out by hand (`<link href="/media/cms/css/debug.css">`, `url(/media/media/...)`) are not covered: use the new paths. Put your own extension's files in `media/<your extension>` only when the site loads them; files only the administrator uses belong in the extension's own folder, as a site may protect `/administrator` with a password.
- **Writing PHP configuration files:** the registry's PHP format takes a `sort` option, `$registry->toString('PHP', array('class' => 'JConfig', 'sort' => true))`, which writes the properties in alphabetical order. `configuration.php` is written this way.

## COMPATIBILITY WITH STOCK JOOMLA 3, JOOMLA 4 AND LATER

- The new methods don't exist in stock Joomla 3. An extension meant for both can check first, e.g. `method_exists($doc, 'addPreload')`, and fall back to `addHeadLink()`.
- Joomla 4 and later have different methods for the same things. In Joomla 4, `$doc->getPreloadManager()` sends preloads as HTTP headers only, and the Web Asset Manager handles stylesheets and scripts. Code meant for both should check the version or the methods it uses.
- `addHeadLink()`, `addCustomTag()` and the declarations take the same arguments as in stock Joomla 3. Only the output is better: no duplicates, escaped values, and links no longer replacing each other.
