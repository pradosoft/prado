<?php
/**
 * TSqliteMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Sqlite
 */

namespace Prado\Data\Common\Sqlite;

/**
 * Load the base TDbMetaData class.
 */
use Prado\Data\Common\TDbMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TSqliteMetaData loads SQLite database table and column information.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Sqlite
 * @since 3.1
 */
class TSqliteMetaData extends TDbMetaData
{
	/**
	 * @return string TDbTableInfo class name.
	 */
	protected function getTableInfoClass()
	{
		return '\Prado\Data\Common\Sqlite\TSqliteTableInfo';
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string $name $name table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		return parent::quoteTableName($name, "'", "'");
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string $name $name column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		return parent::quoteColumnName($name, '"', '"');
	}

	/**
	 * Quotes a column alias for use in a query.
	 * @param string $name $name column alias
	 * @return string the properly quoted column alias
	 */
	public function quoteColumnAlias($name)
	{
		return parent::quoteColumnAlias($name, '"', '"');
	}

	/**
	 * Get the column definitions for given table.
	 * @param string $tableName table name.
	 * @return TPgsqlTableInfo table information.
	 */
	protected function createTableInfo($tableName)
	{
		$tableName = str_replace("'", '', $tableName);
		$this->getDbConnection()->setActive(true);
		$table = $this->getDbConnection()->quoteString($tableName);
		$sql = "PRAGMA table_info({$table})";
		$command = $this->getDbConnection()->createCommand($sql);
		$foreign = $this->getForeignKeys($table);
		$index = 0;
		$columns = [];
		$primary = [];
		foreach ($command->query() as $col) {
			$col['index'] = $index++;
			$column = $this->processColumn($col, $foreign);
			$columns[$col['name']] = $column;
			if ($column->getIsPrimaryKey()) {
				$primary[] = $col['name'];
			}
		}
		$info['TableName'] = $tableName;
		if ($this->getIsView($tableName)) {
			$info['IsView'] = true;
		}
		if (count($columns) === 0) {
			throw new TDbException('dbmetadata_invalid_table_view', $tableName);
		}
		$class = $this->getTableInfoClass();
		$tableInfo = new $class($info, $primary, $foreign);
		$tableInfo->getColumns()->copyFrom($columns);
		return $tableInfo;
	}

	/**
	 * @param string $tableName table name.
	 * @return bool true if the table is a view.
	 */
	protected function getIsView($tableName)
	{
		$sql = 'SELECT count(*) FROM sqlite_master WHERE type="view" AND name= :table';
		$this->getDbConnection()->setActive(true);
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $tableName);
		return (int) ($command->queryScalar()) === 1;
	}

	/**
	 * @param array $col column information.
	 * @param array $foreign foreign key details.
	 * @return TSqliteTableColumn column details.
	 */
	protected function processColumn($col, $foreign)
	{
		$columnId = $col['name']; //use column name as column Id

		$info['ColumnName'] = '"' . $columnId . '"'; //quote the column names!
		$info['ColumnId'] = $columnId;
		$info['ColumnIndex'] = $col['index'];

		if ($col['notnull'] !== '99') {
			$info['AllowNull'] = true;
		}

		if ($col['pk'] === '1') {
			$info['IsPrimaryKey'] = true;
		}
		if ($this->isForeignKeyColumn($columnId, $foreign)) {
			$info['IsForeignKey'] = true;
		}

		if ($col['dflt_value'] !== null) {
			$info['DefaultValue'] = $col['dflt_value'];
		}

		$type = strtolower($col['type']);
		$info['AutoIncrement'] = $type === 'integer' && $col['pk'] === '1';

		$info['DbType'] = $type;
		$match = [];
		if (is_int($pos = strpos($type, '(')) && preg_match('/\((.*)\)/', $type, $match)) {
			$ps = explode(',', $match[1]);
			if (count($ps) === 2) {
				$info['NumericPrecision'] = (int) ($ps[0]);
				$info['NumericScale'] = (int) ($ps[1]);
			} else {
				$info['ColumnSize'] = (int) ($match[1]);
			}
			$info['DbType'] = substr($type, 0, $pos);
		}

		return new TSqliteTableColumn($info);
	}

	/**
	 *
	 *
	 * @param string $table quoted table name.
	 * @return array foreign key details.
	 */
	protected function getForeignKeys($table)
	{
		$sql = "PRAGMA foreign_key_list({$table})";
		$command = $this->getDbConnection()->createCommand($sql);
		$fkeys = [];
		foreach ($command->query() as $col) {
			$fkeys[$col['table']]['keys'][$col['from']] = $col['to'];
			$fkeys[$col['table']]['table'] = $col['table'];
		}
		return count($fkeys) > 0 ? array_values($fkeys) : $fkeys;
	}

	/**
	 * @param string $columnId column name.
	 * @param array $foreign foreign key column names.
	 * @return bool true if column is a foreign key.
	 */
	protected function isForeignKeyColumn($columnId, $foreign)
	{
		foreach ($foreign as $fk) {
			if (in_array($columnId, array_keys($fk['keys']))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns all table names in the database.
	 * @param string $schema the schema of the tables. This is not used for sqlite database.
	 * @return array all table names in the database.
	 */
	public function findTableNames($schema = '')
	{
		$sql = "SELECT DISTINCT tbl_name FROM sqlite_master WHERE tbl_name<>'sqlite_sequence'";
		return $this->getDbConnection()->createCommand($sql)->queryColumn();
	}
}
