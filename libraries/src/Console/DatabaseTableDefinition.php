<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * A table's structure read from an exported XML file (MySQL or PostgreSQL format), which can be created again on MySQL/MariaDB
 * (and SQLite, which runs MySQL's SQL) or on PostgreSQL, and which converts the table's values between them.
 *
 * Joomla 3 marks "no date" differently on each: 0000-00-00 00:00:00 on MySQL, 1970-01-01 00:00:00 on PostgreSQL.
 *
 * @since  3.17.0
 */
class DatabaseTableDefinition
{
	/**
	 * The "no date" value of each kind of database
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	const NULL_DATES = array('mysql' => '0000-00-00 00:00:00', 'postgresql' => '1970-01-01 00:00:00');

	/**
	 * What a file's column types, defaults and index statements may be, as these are written into the SQL which creates the
	 * table: a file could otherwise put any SQL there (e.g. a generated column reading the server's files)
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const SAFE_SQL = array(
		// int, int(10) unsigned, varchar(255), decimal(10,2), enum('a','b'), double precision
		'mysqlType'   => "/^[a-z]+(?: [a-z]+)*(?:\\((?:\\d+(?:\\s*,\\s*\\d+)?|'(?:[^'\\\\]|''|\\\\.)*'(?:\\s*,\\s*'(?:[^'\\\\]|''|\\\\.)*')*)\\))?(?: (?:unsigned|zerofill|signed))*$/i",
		'mysqlExtra'  => '/^(?:\\s*(?:auto_increment|DEFAULT_GENERATED|on update (?:CURRENT_TIMESTAMP|now)(?:\\(\\d*\\))?))*\\s*$/i',
		// integer, character varying(255), timestamp(6) without time zone, numeric(10,2), text[]
		'pgType'      => '/^[a-z_][a-z0-9_]*(?: [a-z_][a-z0-9_]*)*(?:\\(\\d+(?:\\s*,\\s*\\d+)?\\))?(?: [a-z_][a-z0-9_]*)*(?:\\[\\])*$/i',
		// '...'::character varying, 0, (-1)::integer, NULL, true, now(), CURRENT_TIMESTAMP, nextval('#__x_id_seq'::regclass)
		'pgDefault'   => "/^(?:'(?:[^']|'')*'|\\(?-?\\d+(?:\\.\\d+)?\\)?|NULL|true|false|CURRENT_(?:TIMESTAMP|DATE|TIME)(?:\\(\\d*\\))?|LOCALTIMESTAMP|(?:now|nextval|gen_random_uuid|uuid_generate_v4)\\((?:'(?:[^']|'')*'(?:::[a-z_][a-z0-9_]*(?: [a-z_][a-z0-9_]*)*)?)?\\))(?:::[a-z_][a-z0-9_]*(?: [a-z_][a-z0-9_]*)*(?:\\(\\d+(?:,\\s*\\d+)?\\))?(?:\\[\\])*)*$/i",
	);

	/**
	 * The kind of database the file comes from: mysql or postgresql
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	public $dialect;

	/**
	 * The table name, with the #__ prefix
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	public $name;

	/**
	 * Column name => array(type, length, scale, unsigned, size, values, source, nullable, default, autoIncrement)
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	public $columns = array();

	/**
	 * Keys: array(name, primary, unique, fulltext, columns => array(array(column, prefix length or null)), query)
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	public $keys = array();

	/**
	 * Read a table_structure element.
	 *
	 * @param   \SimpleXMLElement  $structure  The element
	 * @param   string             $dialect    The kind of database of the file: mysql or postgresql
	 *
	 * @return  static
	 *
	 * @since   3.17.0
	 */
	public static function fromXml(\SimpleXMLElement $structure, $dialect)
	{
		$definition          = new static;
		$definition->dialect = $dialect;
		$definition->name    = (string) $structure['name'];

		if ($dialect === 'postgresql')
		{
			$definition->readPostgresql($structure);
		}
		else
		{
			$definition->readMysql($structure);
		}

		return $definition;
	}

	/**
	 * @param   \SimpleXMLElement  $structure  The table_structure element of a MySQL file
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function readMysql(\SimpleXMLElement $structure)
	{
		foreach ($structure->field as $field)
		{
			static::check('mysqlType', (string) $field['Type'], $this->name);
			static::check('mysqlExtra', (string) $field['Extra'], $this->name);

			$column = static::parseType((string) $field['Type']);

			$column['nullable']      = strtoupper((string) $field['Null']) === 'YES';
			$column['autoIncrement'] = stripos((string) $field['Extra'], 'auto_increment') !== false;
			$column['default']       = null;

			// Without a Default attribute the column has no default (or NULL)
			if (isset($field['Default']))
			{
				$default = (string) $field['Default'];

				$column['default'] = preg_match('/^current_timestamp(\(\d*\))?$/i', $default)
					? array('expression', 'CURRENT_TIMESTAMP')
					: array('literal', $default);
			}

			$this->columns[(string) $field['Field']] = $column;
		}

		$keys = array();

		foreach ($structure->key as $key)
		{
			$name = (string) $key['Key_name'];

			if (!isset($keys[$name]))
			{
				$keys[$name] = array(
					'name'     => $name,
					'primary'  => $name === 'PRIMARY',
					'unique'   => (string) $key['Non_unique'] === '0',
					'fulltext' => strtoupper((string) $key['Index_type']) === 'FULLTEXT',
					'columns'  => array(),
					'query'    => null,
				);
			}

			$length = (string) $key['Sub_part'];

			$keys[$name]['columns'][(int) $key['Seq_in_index']] = array((string) $key['Column_name'], $length !== '' ? (int) $length : null);
		}

		foreach ($keys as $key)
		{
			ksort($key['columns']);
			$key['columns'] = array_values($key['columns']);
			$this->keys[]   = $key;
		}
	}

	/**
	 * @param   \SimpleXMLElement  $structure  The table_structure element of a PostgreSQL file
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function readPostgresql(\SimpleXMLElement $structure)
	{
		$sequenced = array();

		foreach ($structure->sequence as $sequence)
		{
			$sequenced[] = (string) $sequence['Column'];
		}

		foreach ($structure->field as $field)
		{
			$name    = (string) $field['Field'];
			$column  = static::parseType((string) $field['Type']);
			$default = isset($field['Default']) ? trim((string) $field['Default']) : '';

			static::check('pgType', (string) $field['Type'], $this->name);

			if ($default !== '')
			{
				static::check('pgDefault', $default, $this->name);
			}

			$column['nullable']      = strtoupper((string) $field['Null']) === 'YES';
			$column['autoIncrement'] = in_array($name, $sequenced, true) || stripos($default, 'nextval(') === 0;
			$column['default']       = null;

			if ($default !== '' && !$column['autoIncrement'] && stripos($default, 'NULL') !== 0)
			{
				if (preg_match("/^'((?:[^']|'')*)'(::.*)?$/s", $default, $matches))
				{
					$column['default'] = array('literal', str_replace("''", "'", $matches[1]));
				}
				elseif (preg_match('/^\(?(-?[0-9.]+)\)?(::.*)?$/', $default, $matches))
				{
					$column['default'] = array('literal', $matches[1]);
				}
				elseif (preg_match('/^(true|false)$/i', $default))
				{
					$column['default'] = array('literal', strtolower($default) === 'true' ? '1' : '0');
				}
				elseif (preg_match('/^(now\(\)|current_timestamp|localtimestamp)/i', $default))
				{
					$column['default'] = array('expression', 'CURRENT_TIMESTAMP');
				}
				else
				{
					$column['default'] = array('raw', $default);
				}

				// Copied as it is between PostgreSQL databases
				$column['default'][2] = $default;
			}

			$this->columns[$name] = $column;
		}

		$tablePart = substr($this->name, 3);

		foreach ($structure->key as $key)
		{
			$query = (string) $key['Query'];

			// The primary key or an index of this table (an expression index is copied as it is between PostgreSQL databases)
			$table = '(?:ONLY )?(?:"?[a-z0-9_]+"?\\.)?"?' . preg_quote($this->name, '/') . '"?';

			if ($query !== '' && !preg_match('/^(?:ALTER TABLE ' . $table . ' ADD (?:CONSTRAINT "?[#a-z0-9_]+"? )?PRIMARY KEY'
				. '|CREATE (?:UNIQUE )?INDEX "?[#a-z0-9_]+"? ON ' . $table . ' USING [a-z]+) \\([^;]*\\)$/i', $query))
			{
				throw new \RuntimeException(sprintf('The structure of %s has an index statement which isn\'t an index of that table.', $this->name));
			}

			$primary = in_array(strtolower((string) $key['is_primary']), array('t', 'true', '1'), true);
			$name    = (string) $key['Index'];

			// The index name without the table name (#__menu_idx_alias => idx_alias), as MySQL names keys per table
			if (strpos($name, '#__' . $tablePart . '_') === 0)
			{
				$name = substr($name, strlen('#__' . $tablePart . '_'));
			}

			$columns = array();

			if (preg_match('/\((.*)\)\s*$/s', $query, $matches))
			{
				foreach (explode(',', $matches[1]) as $part)
				{
					$part = trim($part);

					// Only plain columns (an expression index is kept as its query)
					if (!preg_match('/^"?([A-Za-z_][A-Za-z0-9_$]*)"?$/', $part, $columnMatch))
					{
						$columns = array();

						break;
					}

					$columns[] = array($columnMatch[1], null);
				}
			}

			$this->keys[] = array(
				'name'     => $primary ? 'PRIMARY' : $name,
				'primary'  => $primary,
				'unique'   => in_array(strtolower((string) $key['is_unique']), array('t', 'true', '1'), true),
				'fulltext' => false,
				'columns'  => $columns,
				'query'    => $query,
			);
		}
	}

	/**
	 * Refuse a type, default or other piece of SQL from a file which doesn't have the expected form.
	 *
	 * @param   string  $kind   A key of SAFE_SQL
	 * @param   string  $value  The value
	 * @param   string  $table  The table, for the message
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 * @throws  \RuntimeException
	 */
	public static function check($kind, $value, $table)
	{
		if (!preg_match(static::SAFE_SQL[$kind], trim((string) $value)))
		{
			throw new \RuntimeException(sprintf('The structure of %s has a column type or default which can\'t be imported: %s', $table,
				substr((string) $value, 0, 80)));
		}
	}

	/**
	 * Read a column type of either kind of database.
	 *
	 * @param   string  $type  The type, e.g. "int unsigned", "varchar(255)", "character varying(255)", "timestamp without time zone"
	 *
	 * @return  array  type, length, scale, unsigned, size, values, source
	 *
	 * @since   3.17.0
	 */
	public static function parseType($type)
	{
		$source = trim($type);
		$lower  = strtolower($source);
		$column = array('type' => 'text', 'length' => null, 'scale' => null, 'unsigned' => false, 'size' => '', 'values' => null, 'source' => $source);

		$column['unsigned'] = (bool) preg_match('/\bunsigned\b/', $lower);

		if (preg_match('/\((\d+)(?:\s*,\s*(\d+))?\)/', $lower, $matches))
		{
			$column['length'] = (int) $matches[1];
			$column['scale']  = isset($matches[2]) ? (int) $matches[2] : null;
		}

		if (preg_match('/^(enum|set)\s*\((.*)\)/s', $source, $matches))
		{
			$column['type']   = strtolower($matches[1]);
			$column['values'] = $matches[2];
			$column['length'] = null;

			return $column;
		}

		$map = array(
			'/^tinyint\b/'                                       => 'tinyint',
			'/^(smallint|int2|smallserial)\b/'                   => 'smallint',
			'/^mediumint\b/'                                     => 'mediumint',
			'/^(bigint|int8|bigserial)\b/'                       => 'bigint',
			'/^(integer|int4|serial|int)\b/'                     => 'int',
			'/^(decimal|numeric|dec)\b/'                         => 'decimal',
			'/^(double|float8)\b/'                               => 'double',
			'/^(float|real|float4)\b/'                           => 'float',
			'/^(character varying|varchar)\b/'                   => 'varchar',
			'/^(character|char|bpchar)\b/'                       => 'char',
			'/^(tinytext|text|mediumtext|longtext|citext)\b/'    => 'text',
			'/^(tinyblob|blob|mediumblob|longblob|bytea|varbinary|binary)\b/' => 'blob',
			'/^(datetime|timestamp)\b/'                          => 'datetime',
			'/^date\b/'                                          => 'date',
			'/^time\b/'                                          => 'time',
			'/^year\b/'                                          => 'year',
			'/^(boolean|bool)\b/'                                => 'boolean',
			'/^(json|jsonb)\b/'                                  => 'json',
			'/^uuid\b/'                                          => 'uuid',
			'/^bit\b/'                                           => 'tinyint',
		);

		foreach ($map as $pattern => $name)
		{
			if (preg_match($pattern, $lower))
			{
				$column['type'] = $name;

				break;
			}
		}

		if (preg_match('/^(tiny|medium|long)(text|blob)/', $lower, $matches))
		{
			$column['size'] = $matches[1];
		}

		// PostgreSQL's bytea has no size limit
		if ($lower === 'bytea')
		{
			$column['size'] = 'long';
		}

		// Display widths such as int(11) aren't lengths
		if (!in_array($column['type'], array('varchar', 'char', 'decimal'), true))
		{
			$column['length'] = null;
		}

		if ($column['type'] === 'varchar' && $column['length'] === null)
		{
			$column['type'] = 'text';
		}

		return $column;
	}

	/**
	 * Get the statements which create the table.
	 *
	 * @param   string             $dialect  The kind of database: mysql or postgresql
	 * @param   \JDatabaseDriver   $db       The database driver, for quoting
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	public function getCreateStatements($dialect, $db)
	{
		return $dialect === 'postgresql' ? $this->getPostgresqlStatements($db) : $this->getMysqlStatements($db);
	}

	/**
	 * @param   \JDatabaseDriver  $db  The database driver
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	protected function getMysqlStatements($db)
	{
		$lines = array();

		foreach ($this->columns as $name => $column)
		{
			$type = $this->getMysqlType($column);
			$line = $db->quoteName($name) . ' ' . $type . ($column['nullable'] ? ' NULL' : ' NOT NULL');

			if ($column['autoIncrement'])
			{
				$line .= ' AUTO_INCREMENT';
			}
			elseif ($column['default'] !== null && $column['default'][0] !== 'raw' && !preg_match('/(text|blob|json)$/', $type))
			{
				$line .= ' DEFAULT ' . $this->renderDefault($column, 'mysql', $db);
			}

			$lines[] = $line;
		}

		foreach ($this->keys as $key)
		{
			$columns = array();

			if (!$key['columns'])
			{
				continue;
			}

			foreach ($key['columns'] as $part)
			{
				list($name, $length) = $part;
				$column              = isset($this->columns[$name]) ? $this->columns[$name] : null;

				// MySQL only indexes a prefix of long text, and utf8mb4 keys can be at most 767 bytes on older servers
				if (!$key['fulltext'] && $column && $length === null
					&& (in_array($column['type'], array('text', 'blob', 'json'), true) || $column['type'] === 'varchar' && $column['length'] > 191))
				{
					$length = 191;
				}

				$columns[] = $db->quoteName($name) . ($length ? '(' . (int) $length . ')' : '');
			}

			if ($key['primary'])
			{
				$lines[] = 'PRIMARY KEY (' . implode(', ', $columns) . ')';
			}
			else
			{
				$kind    = $key['fulltext'] ? 'FULLTEXT KEY' : ($key['unique'] ? 'UNIQUE KEY' : 'KEY');
				$lines[] = $kind . ' ' . $db->quoteName(substr($key['name'], 0, 64)) . ' (' . implode(', ', $columns) . ')';
			}
		}

		$charset = $db->hasUTF8mb4Support() ? 'utf8mb4' : 'utf8';

		return array(
			'CREATE TABLE ' . $db->quoteName($this->name) . " (\n  " . implode(",\n  ", $lines) . "\n)"
			. ' ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ' DEFAULT COLLATE=' . $charset . '_unicode_ci',
		);
	}

	/**
	 * @param   array  $column  The column
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getMysqlType(array $column)
	{
		if ($this->dialect === 'mysql')
		{
			return $column['source'];
		}

		switch ($column['type'])
		{
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint':
				return $column['type'] . ($column['unsigned'] ? ' unsigned' : '');

			case 'decimal':
				return 'decimal(' . ($column['length'] ?: 10) . ',' . (int) $column['scale'] . ')';

			case 'float':
			case 'double':
			case 'date':
			case 'datetime':
			case 'time':
			case 'year':
				return $column['type'];

			case 'char':
				return 'char(' . ($column['length'] ?: 1) . ')';

			case 'varchar':
				return $column['length'] > 16383 ? 'mediumtext' : 'varchar(' . $column['length'] . ')';

			case 'blob':
				return ($column['size'] ?: 'long') . 'blob';

			case 'boolean':
				return 'tinyint';

			case 'uuid':
				return 'char(36)';

			case 'enum':
			case 'set':
				return 'varchar(255)';

			default:
				// PostgreSQL's text holds up to 1GB, MySQL's text 64KB
				return $column['size'] ? $column['size'] . 'text' : 'mediumtext';
		}
	}

	/**
	 * @param   \JDatabaseDriver  $db  The database driver
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	protected function getPostgresqlStatements($db)
	{
		$lines   = array();
		$primary = array();

		foreach ($this->keys as $key)
		{
			if ($key['primary'])
			{
				$primary = array_column($key['columns'], 0);
			}
		}

		foreach ($this->columns as $name => $column)
		{
			$line = $db->quoteName($name) . ' ' . $this->getPostgresqlType($column) . ($column['nullable'] ? ' NULL' : ' NOT NULL');

			if (!$column['autoIncrement'] && $column['default'] !== null)
			{
				$line .= ' DEFAULT ' . $this->renderDefault($column, 'postgresql', $db);
			}
			elseif ($this->dialect === 'mysql' && !$column['autoIncrement'] && !$column['nullable'] && !in_array($name, $primary, true))
			{
				/*
				 * Code written for MySQL leaves out NOT NULL columns, which MySQL (without strict mode, as Joomla runs it) fills with
				 * an empty value; PostgreSQL refuses the row instead. Give such columns that value as their default.
				 */
				$line .= ' DEFAULT ' . $this->getImplicitDefault($column, $db);
			}

			$lines[] = $line;
		}

		$statements = array();

		foreach ($this->keys as $key)
		{
			if ($key['primary'] && $key['columns'])
			{
				$lines[] = 'PRIMARY KEY (' . implode(', ', $db->quoteName(array_column($key['columns'], 0))) . ')';

				continue;
			}

			// No fulltext indexes; PostgreSQL's text search works differently
			if ($key['fulltext'])
			{
				continue;
			}

			$index = $db->quoteName($this->name . '_' . $key['name']);

			if (!$key['columns'])
			{
				// An expression index can only be copied between PostgreSQL databases
				if ($this->dialect === 'postgresql' && $key['query'] !== null)
				{
					$statements[] = $key['query'];
				}

				continue;
			}

			$columns = array();

			foreach ($key['columns'] as $part)
			{
				list($name, $length) = $part;
				$column              = isset($this->columns[$name]) ? $this->columns[$name] : null;

				// Long text only indexes a prefix, as on MySQL: whole values could be too large for an index
				$columns[] = $length && $column && in_array($column['type'], array('text', 'blob', 'json'), true)
					? '(left(' . $db->quoteName($name) . ', ' . (int) $length . '))'
					: $db->quoteName($name);
			}

			$statements[] = 'CREATE ' . ($key['unique'] ? 'UNIQUE ' : '') . 'INDEX ' . $index . ' ON ' . $db->quoteName($this->name)
				. ' (' . implode(', ', $columns) . ')';
		}

		array_unshift($statements, 'CREATE TABLE ' . $db->quoteName($this->name) . " (\n  " . implode(",\n  ", $lines) . "\n)");

		return $statements;
	}

	/**
	 * @param   array  $column  The column
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getPostgresqlType(array $column)
	{
		if ($column['autoIncrement'])
		{
			return $column['type'] === 'bigint' || $column['type'] === 'int' && $column['unsigned'] ? 'bigserial' : 'serial';
		}

		if ($this->dialect === 'postgresql')
		{
			return $column['source'];
		}

		switch ($column['type'])
		{
			case 'tinyint':
			case 'year':
				return 'smallint';

			case 'smallint':
				return $column['unsigned'] ? 'integer' : 'smallint';

			case 'mediumint':
				return 'integer';

			case 'int':
				return $column['unsigned'] ? 'bigint' : 'integer';

			case 'bigint':
				return 'bigint';

			case 'decimal':
				return 'numeric(' . ($column['length'] ?: 10) . ',' . (int) $column['scale'] . ')';

			case 'float':
				return 'real';

			case 'double':
				return 'double precision';

			// PostgreSQL returns character(n) padded with spaces (MySQL removes them), so e.g. "*" in a language column wouldn't match
			case 'char':
			case 'varchar':
				return 'character varying(' . ($column['length'] ?: 1) . ')';

			case 'blob':
				return 'bytea';

			case 'date':
				return 'date';

			case 'datetime':
				return 'timestamp without time zone';

			case 'time':
				return 'time without time zone';

			case 'boolean':
				return 'boolean';

			case 'json':
				return 'json';

			case 'uuid':
				return 'uuid';

			case 'enum':
			case 'set':
				return 'character varying(255)';

			default:
				return 'text';
		}
	}

	/**
	 * @param   array             $column   The column
	 * @param   string            $dialect  The kind of database
	 * @param   \JDatabaseDriver  $db       The database driver
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function renderDefault(array $column, $dialect, $db)
	{
		list($kind, $value) = $column['default'];

		if ($dialect === 'postgresql' && $this->dialect === 'postgresql' && isset($column['default'][2]))
		{
			return $column['default'][2];
		}

		if ($kind === 'expression')
		{
			return $value;
		}

		// A PostgreSQL expression, only written for PostgreSQL
		if ($kind === 'raw')
		{
			return $value;
		}

		// PostgreSQL keeps a quoted number as e.g. '0'::smallint, which Joomla's database check doesn't recognise as 0
		if ($dialect === 'postgresql' && is_numeric($value)
			&& in_array($column['type'], array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double', 'year'), true))
		{
			return $value;
		}

		return $db->quote($this->convertDate($column, $value, $dialect));
	}

	/**
	 * The value MySQL uses for a NOT NULL column which an INSERT leaves out, written for PostgreSQL.
	 *
	 * @param   array             $column  The column
	 * @param   \JDatabaseDriver  $db      The database driver
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getImplicitDefault(array $column, $db)
	{
		switch ($column['type'])
		{
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint':
			case 'decimal':
			case 'float':
			case 'double':
			case 'year':
				return '0';

			case 'boolean':
				return 'false';

			case 'date':
				return $db->quote(substr(static::NULL_DATES['postgresql'], 0, 10));

			case 'datetime':
				return $db->quote(static::NULL_DATES['postgresql']);

			case 'time':
				return $db->quote('00:00:00');

			case 'blob':
				return "''::bytea";

			case 'json':
				return $db->quote('null');

			default:
				return $db->quote('');
		}
	}

	/**
	 * Convert a value for the target database. Returns the SQL for it.
	 *
	 * @param   string            $name     The column
	 * @param   string|null       $value    The value (null for NULL)
	 * @param   string            $dialect  The kind of database of the target
	 * @param   \JDatabaseDriver  $db       The database driver
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function convertValue($name, $value, $dialect, $db)
	{
		if ($value === null)
		{
			return 'NULL';
		}

		$column = isset($this->columns[$name]) ? $this->columns[$name] : null;

		if ($column === null)
		{
			return $db->quote($value);
		}

		if ($column['type'] === 'blob' && $dialect === 'postgresql')
		{
			return "decode('" . base64_encode($value) . "', 'base64')";
		}

		if ($column['type'] === 'boolean' && $dialect === 'mysql')
		{
			return in_array(strtolower($value), array('t', 'true', '1', 'y', 'yes'), true) ? '1' : '0';
		}

		$value = $this->convertDate($column, $value, $dialect);

		// PostgreSQL's text can't hold NUL characters
		if ($dialect === 'postgresql' && strpos($value, "\0") !== false)
		{
			$value = str_replace("\0", '', $value);
		}

		return $db->quote($value);
	}

	/**
	 * Convert the "no date" value of a date column between the kinds of databases.
	 *
	 * @param   array   $column   The column
	 * @param   string  $value    The value
	 * @param   string  $dialect  The kind of database of the target
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function convertDate(array $column, $value, $dialect)
	{
		if ($dialect === $this->dialect || !in_array($column['type'], array('date', 'datetime', 'time'), true))
		{
			return $value;
		}

		/*
		 * PostgreSQL adds the server's time zone offset to "with time zone" columns (2026-10-05 12:00:00+02). Joomla writes and
		 * reads these values as they are, so what it stored is the part before the offset; MySQL doesn't take an offset.
		 */
		if ($this->dialect === 'postgresql')
		{
			$value = preg_replace('/(\d{2}:\d{2}(:\d{2})?)(\.\d+)?([+-]\d{2}(:?\d{2}){0,2}|Z)$/', '$1', $value);
		}

		if ($column['type'] === 'time')
		{
			return $value;
		}

		$from = static::NULL_DATES[$this->dialect];
		$to   = static::NULL_DATES[$dialect];

		if ($value === $from || $value === substr($from, 0, 10))
		{
			return $column['type'] === 'date' ? substr($to, 0, 10) : $to;
		}

		// MySQL accepts dates such as 2026-00-00, which PostgreSQL rejects
		if ($dialect === 'postgresql' && preg_match('/^\d{4}-00|^\d{4}-\d\d-00/', $value))
		{
			return $column['type'] === 'date' ? substr($to, 0, 10) : $to;
		}

		return $value;
	}

	/**
	 * The columns whose values are numbered automatically.
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	public function getAutoIncrementColumns()
	{
		return array_keys(array_filter($this->columns, function ($column) {
			return $column['autoIncrement'];
		}));
	}
}
