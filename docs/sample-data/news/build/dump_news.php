<?php
// Writes installation/sql/{mysql,postgresql,sqlazure}/sample_news.sql from the News build site: php dump_news.php <site> <repo>
define('_JEXEC', 1); define('JPATH_BASE', $argv[1]);
require JPATH_BASE . '/includes/defines.php'; require JPATH_BASE . '/libraries/import.legacy.php'; require JPATH_BASE . '/libraries/cms.php';
require_once JPATH_BASE . '/configuration.php'; JFactory::$config = new Joomla\Registry\Registry(new JConfig);
$db   = JFactory::getDbo();
$repo = $argv[2];

// Table => [primary key column for ordering, identity/serial column or null]
$tables = array(
	'#__assets'              => array('id', 'id'),
	'#__categories'          => array('id', 'id'),
	'#__content'             => array('id', 'id'),
	'#__content_frontpage'   => array('content_id', null),
	'#__contentitem_tag_map' => array('core_content_id', null),
	'#__menu'                => array('id', 'id'),
	'#__menu_types'          => array('id', 'id'),
	'#__modules'             => array('id', 'id'),
	'#__modules_menu'        => array('moduleid', null),
	'#__tags'                => array('id', 'id'),
	'#__ucm_base'            => array('ucm_id', 'ucm_id'),
	'#__ucm_content'         => array('core_content_id', 'core_content_id'),
);
$identity = array('mysql' => array(), 'postgresql' => array(), 'sqlazure' => array());
$nullDates = array('mysql' => '0000-00-00 00:00:00', 'postgresql' => '1970-01-01 00:00:00', 'sqlazure' => '1900-01-01 00:00:00');

// The columns of each table in each dialect's schema, to check the dump fits them
$schema = array();
foreach (array_keys($nullDates) as $dialect)
{
	$sql = file_get_contents($repo . '/installation/sql/' . $dialect . '/joomla.sql');
	foreach ($tables as $table => $info)
	{
		if (!preg_match('/CREATE TABLE (?:IF NOT EXISTS )?[`"]' . preg_quote($table, '/') . '[`"] ?\((.*?)\n\)/s', $sql, $m))
		{
			fwrite(STDERR, "No $table in $dialect\n"); exit(1);
		}
		preg_match_all('/^\s*[`"]([a-z_A-Z]+)[`"] /m', $m[1], $cols);
		$schema[$dialect][$table] = $cols[1];
		// SQL Server: no IDENTITY on ucm_base
		$identity[$dialect][$table] = $info[1] !== null && ($dialect !== 'sqlazure' || strpos($m[1], 'IDENTITY') !== false);
	}
}

$data = array();
foreach ($tables as $table => $info)
{
	$columns = $db->getTableColumns($table, false);
	$rows    = $db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName($table))->order($db->quoteName($info[0])))->loadAssocList();
	$data[$table] = array($columns, $rows);
	foreach (array_keys($nullDates) as $dialect)
	{
		$missing = array_diff(array_keys($columns), $schema[$dialect][$table]);
		if ($missing)
		{
			fwrite(STDERR, "$table: columns not in $dialect: " . implode(', ', $missing) . "\n"); exit(1);
		}
	}
}
$styleParams = $db->setQuery("SELECT params FROM #__template_styles WHERE template = 'hammond' AND client_id = 0")->loadResult();

function literal($value, $column, $dialect, $nullDates)
{
	if ($value === null)
	{
		return 'NULL';
	}
	$type = strtolower($column->Type);
	if (preg_match('/int/', $type) && preg_match('/^-?\d+$/', (string) $value))
	{
		return (string) (int) $value;
	}
	$value = (string) $value;
	// Also in text columns which are dates in the other schemas (#__ucm_content.core_checked_out_time)
	if ($value === '0000-00-00 00:00:00')
	{
		$value = $nullDates[$dialect];
	}
	// One line per row, as the other sample data files: HTML doesn't need the line breaks
	$value = preg_replace('/>\s*\n\s*</', '><', $value);
	$value = trim(str_replace(array("\r\n", "\n", "\r"), ' ', $value));
	if ($dialect === 'mysql')
	{
		$value = str_replace('\\', '\\\\', $value);
	}
	$quoted = "'" . str_replace("'", "''", $value) . "'";
	// SQL Server keeps characters outside the database's code page only in Unicode literals
	return $dialect === 'sqlazure' && preg_match('/[^\x00-\x7F]/', $value) ? 'N' . $quoted : $quoted;
}

foreach ($nullDates as $dialect => $nullDate)
{
	$q   = $dialect === 'mysql' ? '`' : '"';
	$out = "--\n-- IMPORTANT - THIS FILE MUST BE SAVED WITH UTF-8 ENCODING ONLY. BEWARE IF EDITING!\n--\n"
		. "-- The News sample data: the Hammond template's news site. Built from a fresh install with the command line (articles,\n"
		. "-- categories, tags, menus and modules), its images are hosted on the project's site. The installer moves its dates\n"
		. "-- forward, keeping the time between them.\n--\n\n";
	if ($dialect === 'mysql')
	{
		$out .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\nSET time_zone = \"+00:00\";\n\n";
	}
	foreach ($tables as $table => $info)
	{
		$out .= $dialect === 'mysql' ? "TRUNCATE `$table`;\n" : ($dialect === 'postgresql' ? "TRUNCATE \"$table\" RESTART IDENTITY;\n" : "TRUNCATE TABLE \"$table\";\n");
	}
	foreach ($data as $table => list($columns, $rows))
	{
		if (!$rows)
		{
			continue;
		}
		$id    = $tables[$table][1];
		$ident = $identity[$dialect][$table];
		$out  .= "\n--\n-- Dumping data for table `$table`\n--\n\n";
		if ($dialect === 'sqlazure' && $ident)
		{
			$out .= "SET IDENTITY_INSERT \"$table\" ON;\n\n";
		}
		// SQL Server takes at most 1000 rows per INSERT
		foreach (array_chunk($rows, $dialect === 'sqlazure' ? 900 : 100000) as $chunk)
		{
			$out .= "INSERT INTO $q$table$q (" . implode(', ', array_map(function ($c) use ($q) { return $q . $c . $q; }, array_keys($columns))) . ") VALUES\n";
			$lines = array();
			foreach ($chunk as $row)
			{
				$values = array();
				foreach ($columns as $name => $column)
				{
					$values[] = literal($row[$name], $column, $dialect, $nullDates);
				}
				$lines[] = '(' . implode(', ', $values) . ')';
			}
			$out .= implode(",\n", $lines) . ";\n";
		}
		if ($dialect === 'sqlazure' && $ident)
		{
			$out .= "\nSET IDENTITY_INSERT \"$table\" OFF;\n";
		}
		if ($dialect === 'postgresql' && $id !== null)
		{
			$out .= "\nSELECT setval('{$table}_{$id}_seq', max($id)) FROM \"$table\";\n";
		}
	}
	$out .= "\n--\n-- The Hammond template's settings for the News site\n--\n\n"
		. "UPDATE {$q}#__template_styles{$q} SET {$q}params{$q} = " . literal($styleParams, (object) array('Type' => 'text'), $dialect, $nullDates)
		. " WHERE {$q}template{$q} = 'hammond' AND {$q}client_id{$q} = 0;\n";
	file_put_contents($repo . '/installation/sql/' . $dialect . '/sample_news.sql', $out);
	echo $dialect, ': ', strlen($out), " bytes\n";
}
