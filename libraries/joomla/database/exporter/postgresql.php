<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Database
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * PostgreSQL export driver.
 *
 * @since       3.0.0
 * @deprecated  4.0  Use PDO PostgreSQL instead
 *
 * @property-read  JDatabaseDriverPostgresql  $db  The database connector to use for exporting structure and/or data.
 */
class JDatabaseExporterPostgresql extends JDatabaseExporter
{
	/**
	 * Builds the XML data for the tables to export.
	 *
	 * @return  string  An XML string
	 *
	 * @since   3.0.0
	 * @throws  Exception if an error occurs.
	 */
	protected function buildXml()
	{
		$buffer = array();

		$buffer[] = '<?xml version="1.0"?>';
		$buffer[] = '<postgresqldump xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
		$buffer[] = ' <database name="">';

		$buffer = array_merge($buffer, $this->buildXmlStructure());

		$buffer[] = ' </database>';
		$buffer[] = '</postgresqldump>';

		return implode("\n", $buffer);
	}

	/**
	 * Builds the XML structure to export.
	 *
	 * @return  array  An array of XML lines (strings).
	 *
	 * @since   3.0.0
	 * @throws  Exception if an error occurs.
	 */
	protected function buildXmlStructure()
	{
		$buffer = array();

		foreach ($this->from as $table)
		{
			// The driver's lookups need the real table name; the file uses the generic one, as for MySQL
			$realTable = $this->db->replacePrefix((string) $table);
			$table     = $this->getGenericTableName($realTable);

			// Get the details columns information.
			$fields    = $this->db->getTableColumns($realTable, false);
			$keys      = $this->db->getTableKeys($realTable) ?: array();
			$sequences = $this->db->getTableSequences($realTable) ?: array();

			// getTableColumns() leaves out the defaults of text columns, so read them all from the catalog
			$defaults = $this->db->setQuery(
				'SELECT a.attname, pg_catalog.pg_get_expr(d.adbin, d.adrelid, true) AS expr FROM pg_catalog.pg_attrdef d'
				. ' JOIN pg_catalog.pg_attribute a ON a.attrelid = d.adrelid AND a.attnum = d.adnum'
				. ' WHERE d.adrelid = ' . $this->db->quote($this->db->quoteName($realTable)) . '::regclass'
			)->loadAssocList('attname', 'expr');

			$buffer[] = '  <table_structure name="' . $this->escapeXml($table) . '">';

			foreach ($sequences as $sequence)
			{
				if (version_compare($this->db->getVersion(), '9.1.0') < 0)
				{
					$sequence->start_value = null;
				}

				$buffer[] = '   <sequence Name="' . $this->escapeXml($this->getGenericTableName($sequence->sequence)) . '"'
					. ' Schema="' . $this->escapeXml($sequence->schema) . '"'
					. ' Table="' . $this->escapeXml($this->getGenericTableName($sequence->table)) . '"'
					. ' Column="' . $this->escapeXml($sequence->column) . '"' . ' Type="' . $this->escapeXml($sequence->data_type) . '"'
					. ' Start_Value="' . $this->escapeXml($sequence->start_value) . '"' . ' Min_Value="' . $this->escapeXml($sequence->minimum_value) . '"'
					. ' Max_Value="' . $this->escapeXml($sequence->maximum_value) . '"' . ' Increment="' . $this->escapeXml($sequence->increment) . '"'
					. ' Cycle_option="' . $this->escapeXml($sequence->cycle_option) . '"'
					. ' />';
			}

			foreach ($fields as $field)
			{
				$default = isset($defaults[$field->column_name]) ? (string) $defaults[$field->column_name] : null;

				$buffer[] = '   <field Field="' . $this->escapeXml($field->column_name) . '"' . ' Type="' . $this->escapeXml($field->type) . '"'
					. ' Null="' . $this->escapeXml($field->null) . '"'
					. ($default !== null ? ' Default="' . $this->escapeXml($default) . '"' : '')
					. ' Comments="' . $this->escapeXml($field->comments) . '"'
					. ' />';
			}

			foreach ($keys as $key)
			{
				$buffer[] = '   <key Index="' . $this->escapeXml($this->getGenericTableName($key->idxName)) . '"'
					. ' is_primary="' . $this->escapeXml($key->isPrimary) . '"' . ' is_unique="' . $this->escapeXml($key->isUnique) . '"'
					. ' Query="' . $this->escapeXml(str_replace($this->db->getPrefix(), '#__', $key->Query)) . '" />';
			}

			$buffer[] = '  </table_structure>';
		}

		return $buffer;
	}

	/**
	 * Checks if all data and options are in order prior to exporting.
	 *
	 * @return  JDatabaseExporterPostgresql  Method supports chaining.
	 *
	 * @since   3.0.0
	 * @throws  Exception if an error is encountered.
	 */
	public function check()
	{
		// Check if the db connector has been set.
		if (!($this->db instanceof JDatabaseDriverPostgresql))
		{
			throw new Exception('JPLATFORM_ERROR_DATABASE_CONNECTOR_WRONG_TYPE');
		}

		// Check if the tables have been specified.
		if (empty($this->from))
		{
			throw new Exception('JPLATFORM_ERROR_NO_TABLES_SPECIFIED');
		}

		return $this;
	}
}
