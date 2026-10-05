<?php
define('_JEXEC',1); define('JPATH_BASE', $argv[1]); require JPATH_BASE.'/includes/defines.php'; require JPATH_BASE.'/libraries/import.legacy.php'; require JPATH_BASE.'/libraries/cms.php';
require_once JPATH_BASE . "/configuration.php"; $c = new JConfig; JFactory::$config = new Joomla\Registry\Registry($c);
$db = JFactory::getDbo();
foreach (array_slice($argv, 2) as $sql)
{
	if (preg_match('/^\s*(SELECT|SHOW|PRAGMA)/i', $sql))
	{
		foreach ((array) $db->setQuery($sql)->loadAssocList() as $r) echo implode(' | ', $r), "\n";
		echo "--\n";
	}
	else
	{
		$db->setQuery($sql)->execute();
		echo "ok " . $db->getAffectedRows() . "\n";
	}
}
