<?php
// Writes plugins/sampledata/blog/data/news.json (the Sample Data plugin's News set) from the News build site (the same content as sample_news.sql), with
// categories, tags, articles and menu items referred to by alias: php export_plugin_data.php <site> <repo>
define('_JEXEC', 1); define('JPATH_BASE', $argv[1]);
require JPATH_BASE . '/includes/defines.php'; require JPATH_BASE . '/libraries/import.legacy.php'; require JPATH_BASE . '/libraries/cms.php';
require_once JPATH_BASE . '/configuration.php'; JFactory::$config = new Joomla\Registry\Registry(new JConfig);
$db = JFactory::getDbo();

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

// Menus: the main menu (without its home item: the site keeps its own) and the company menu, with links by alias
$types = array('mainmenu' => 'newsmenu', 'companymenu' => 'newscompany');
foreach ($db->setQuery("SELECT menutype, title, description FROM #__menu_types WHERE menutype IN ('mainmenu', 'companymenu')")->loadObjectList() as $t)
{
	$data['menus'][$types[$t->menutype]] = array('title' => $t->menutype === 'mainmenu' ? 'News Menu' : 'News Company Menu', 'description' => $t->menutype === 'mainmenu' ? 'The sections of the news site' : $t->description, 'items' => array());
}
foreach ($db->setQuery("SELECT * FROM #__menu WHERE client_id = 0 AND menutype IN ('mainmenu', 'companymenu') AND home = 0 ORDER BY lft")->loadObjectList() as $i)
{
	$link = preg_replace_callback('/view=(category|article)(&layout=blog)?&id=(\d+)/', function ($m) use ($catById, $artById) {
		return 'view=' . $m[1] . $m[2] . '&id={' . $m[1] . ':' . ($m[1] === 'category' ? $catById[$m[3]] : $artById[$m[3]]) . '}';
	}, $i->link);
	$data['menus'][$types[$i->menutype]]['items'][] = array('title' => $i->title, 'alias' => $i->alias, 'link' => $link, 'params' => $i->params);
}

// Modules: menu types and categories by name
foreach ($db->setQuery("SELECT * FROM #__modules WHERE client_id = 0 ORDER BY position, ordering")->loadObjectList() as $m)
{
	$params = json_decode($m->params, true);
	if (isset($params['menutype']))
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

$style = json_decode($db->setQuery("SELECT params FROM #__template_styles WHERE template = 'hammond' AND client_id = 0")->loadResult(), true);
$style['pagesMenu'] = $types[$style['pagesMenu']];
$data['template'] = $style;

$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
file_put_contents($argv[2] . '/plugins/sampledata/blog/data/news.json', $json);
printf("%d categories, %d tags, %d articles, %d menu items, %d modules, %d bytes\n", count($data['categories']), count($data['tags']), count($data['articles']),
	array_sum(array_map(function ($m) { return count($m['items']); }, $data['menus'])), count($data['modules']), strlen($json));
