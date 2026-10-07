<?php
// Writes plugins/sampledata/blog/data/<set>.json (a set of the Sample Data plugin) from the set's build site (the same content as
// sample_<set>.sql), with categories, tags, articles and menu items referred to by alias: php export.php <site> <repo> <set>
define('_JEXEC', 1); define('JPATH_BASE', $argv[1]);
require JPATH_BASE . '/includes/defines.php'; require JPATH_BASE . '/libraries/import.legacy.php'; require JPATH_BASE . '/libraries/cms.php';
require_once JPATH_BASE . '/configuration.php'; JFactory::$config = new Joomla\Registry\Registry(new JConfig);
$db     = JFactory::getDbo();
$set    = $argv[3];
$config = json_decode(file_get_contents(__DIR__ . '/../' . $set . '/set.json'), true);

$cats    = $db->setQuery("SELECT id, title, alias, description, params, metadata FROM #__categories WHERE extension = 'com_content' AND level = 1 AND alias != 'uncategorised' ORDER BY lft")->loadObjectList('id');
$catById = array_map(function ($c) { return $c->alias; }, $cats);
$tags    = $db->setQuery("SELECT id, title, alias FROM #__tags WHERE id > 1 ORDER BY lft")->loadObjectList('id');
$newest  = strtotime($db->setQuery("SELECT MAX(publish_up) FROM #__content")->loadResult() . ' UTC');

$data = array('categories' => array(), 'tags' => array(), 'articles' => array(), 'menus' => array(), 'modules' => array());
foreach ($cats as $c)
{
	$data['categories'][] = array('title' => $c->title, 'alias' => $c->alias, 'description' => $c->description, 'params' => $c->params, 'metadata' => $c->metadata);
}
foreach ($tags as $t)
{
	$data['tags'][] = array('title' => $t->title, 'alias' => $t->alias);
}

$map = array();
foreach ($db->setQuery("SELECT tag_id, content_item_id FROM #__contentitem_tag_map WHERE type_alias = 'com_content.article'")->loadObjectList() as $m)
{
	$map[$m->content_item_id][] = $tags[$m->tag_id]->alias;
}
$artById = array();
foreach ($db->setQuery("SELECT * FROM #__content ORDER BY id")->loadObjectList() as $a)
{
	$artById[$a->id] = $a->alias;
	$data['articles'][] = array(
		'title' => $a->title, 'alias' => $a->alias, 'category' => $catById[$a->catid], 'introtext' => $a->introtext, 'fulltext' => $a->fulltext,
		'state' => (int) $a->state, 'featured' => (int) $a->featured, 'age' => $newest - strtotime($a->publish_up . ' UTC'),
		'created_by_alias' => $a->created_by_alias, 'images' => $a->images, 'urls' => $a->urls, 'attribs' => $a->attribs,
		'metadesc' => $a->metadesc, 'metakey' => $a->metakey, 'metadata' => $a->metadata, 'hits' => (int) $a->hits,
		'tags' => isset($map[$a->id]) ? $map[$a->id] : array(),
	);
}

// Menus: the set's menus under menu types of their own (set.json), with links by alias; the home item as an alias of the site's own
$types = array();
foreach ($config['menus'] as $menutype => $menu)
{
	$types[$menutype]              = $menu['type'];
	$data['menus'][$menu['type']] = array('title' => $menu['title'], 'description' => $menu['description'], 'items' => array());
}
$quoted = implode(', ', array_map(array($db, 'quote'), array_keys($types)));
// A menu item alias names its target by alias ({menuitem:work}): the plugin adds the menus in set.json's order, so targets come first
$menuItems = $db->setQuery("SELECT * FROM #__menu WHERE client_id = 0 AND menutype IN ($quoted) ORDER BY lft")->loadObjectList('id');
foreach ($menuItems as $i)
{
	// The home item stays the site's own: in the set's menu, an alias of it ({menuitem:@home}), with the home item's options
	if ($i->home)
	{
		$home    = json_decode($i->params, true);
		$options = array('aliasoptions' => '{menuitem:@home}', 'alias_redirect' => 0, 'menu-anchor_title' => '',
			'menu-anchor_css' => isset($home['menu-anchor_css']) ? $home['menu-anchor_css'] : '', 'menu_image' => '', 'menu_image_css' => '', 'menu_text' => 1, 'menu_show' => 1);
		$data['menus'][$types[$i->menutype]]['items'][] = array('title' => $i->title, 'alias' => 'home-' . $set, 'type' => 'alias', 'link' => 'index.php?Itemid=', 'params' => json_encode($options));
		continue;
	}

	$link = preg_replace_callback('/view=(category|article)(&layout=[A-Za-z0-9_.:-]+)?&id=(\d+)/', function ($m) use ($catById, $artById) {
		return 'view=' . $m[1] . $m[2] . '&id={' . $m[1] . ':' . ($m[1] === 'category' ? $catById[$m[3]] : $artById[$m[3]]) . '}';
	}, $i->link);
	$params = $i->params;
	if ($i->type === 'alias')
	{
		$options                 = json_decode($params, true);
		$options['aliasoptions'] = '{menuitem:' . $menuItems[(int) $options['aliasoptions']]->alias . '}';
		$params                  = json_encode($options);
	}
	$data['menus'][$types[$i->menutype]]['items'][] = array('title' => $i->title, 'alias' => $i->alias, 'type' => $i->type, 'link' => $link, 'params' => $params);
}

// Modules: menu types and categories by name
foreach ($db->setQuery("SELECT * FROM #__modules WHERE client_id = 0 ORDER BY position, ordering")->loadObjectList() as $m)
{
	$params = json_decode($m->params, true);
	if (isset($params['menutype'], $types[$params['menutype']]))
	{
		$params['menutype'] = $types[$params['menutype']];
	}
	if (!empty($params['catid']))
	{
		$params['catid'] = array_map(function ($id) use ($catById) { return '{category:' . $catById[$id] . '}'; }, (array) $params['catid']);
	}
	$data['modules'][] = array('title' => $m->title, 'content' => $m->content, 'position' => $m->position, 'module' => $m->module,
		'showtitle' => (int) $m->showtitle, 'ordering' => (int) $m->ordering, 'params' => $params);
}

$style = json_decode($db->setQuery("SELECT params FROM #__template_styles WHERE template = " . $db->quote($config['template']) . " AND client_id = 0")->loadResult(), true);
if (isset($style['pagesMenu'], $types[$style['pagesMenu']]))
{
	$style['pagesMenu'] = $types[$style['pagesMenu']];
}
$data['template'] = $style;

$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
file_put_contents($argv[2] . '/plugins/sampledata/blog/data/' . $set . '.json', $json);
printf("%d categories, %d tags, %d articles, %d menu items, %d modules, %d bytes\n", count($data['categories']), count($data['tags']), count($data['articles']),
	array_sum(array_map(function ($m) { return count($m['items']); }, $data['menus'])), count($data['modules']), strlen($json));
