<?php
// split.php <joomla root> <sql file>: the statements of an SQL file as JSON, split as Joomla's installer splits them
define('_JEXEC', 1); define('JPATH_PLATFORM', $argv[1] . '/libraries');
require $argv[1] . '/libraries/loader.php'; JLoader::setup();
JLoader::register('JDatabaseDriver', $argv[1] . '/libraries/joomla/database/driver.php');
echo json_encode(JDatabaseDriver::splitSql(file_get_contents($argv[2])));
