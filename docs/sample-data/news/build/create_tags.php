<?php
// Creates tags (title list in argv) with Joomla's tag table, so the nested set is right
define('_JEXEC', 1); define('JPATH_BASE', $argv[1]);
require JPATH_BASE . '/includes/defines.php'; require JPATH_BASE . '/libraries/import.legacy.php'; require JPATH_BASE . '/libraries/cms.php';
require_once JPATH_BASE . '/configuration.php'; JFactory::$config = new Joomla\Registry\Registry(new JConfig);
new \Joomla\CMS\Application\ConsoleApplication;
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
foreach (array_slice($argv, 2) as $title)
{
	$table = JTable::getInstance('Tag', 'TagsTable');
	if ($table->load(array('alias' => JApplicationHelper::stringURLSafe($title)))) { echo "exists $title\n"; continue; }
	$table->reset(); $table->id = 0;
	$table->setLocation(1, 'last-child');
	$table->bind(array('title' => $title, 'alias' => JApplicationHelper::stringURLSafe($title), 'published' => 1, 'access' => 1, 'language' => '*',
		'params' => '{}', 'metadata' => '{}', 'urls' => '{}', 'images' => '{}', 'description' => '', 'created_user_id' => 0, 'created_time' => JFactory::getDate()->toSql()));
	if (!$table->check() || !$table->store()) { echo "FAIL $title: " . $table->getError() . "\n"; continue; }
	$table->rebuildPath($table->id);
	echo "created $title ({$table->id})\n";
}
