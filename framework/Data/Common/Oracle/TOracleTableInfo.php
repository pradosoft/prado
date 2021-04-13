<?php

/**
 * TOracleTableInfo class file.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Oracle
 */

namespace Prado\Data\Common\Oracle;

use Prado\Collections\TMap;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TDbTableInfo class describes the meta data of a database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Oracle
 * @since 3.1
 */
class TOracleTableInfo extends \Prado\TComponent
{
	private $_info = [];

	private $_primaryKeys;
	private $_foreignKeys;

	private $_columns;

	private $_lowercase;

	/**
	 * Sets the database table meta data information.
	 * @param array $tableInfo table column information.
	 * @param array $primary
	 * @param array $foreign
	 */
	public function __construct($tableInfo = [], $primary = [], $foreign = [])
	{
		$this->_info = $tableInfo;
		$this->_primaryKeys = $primary;
		$this->_foreignKeys = $foreign;
		$this->_columns = new TMap;
		parent::__construct();
	}

	/**
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @return \Prado\Data\Common\TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TOracleCommandBuilder($connection, $this);
	}

	/**
	 * @param string $name information array key name
	 * @param mixed $default default value if information array value is null
	 * @return mixed information array value.
	 */
	public function getInfo($name, $default = null)
	{
		return $this->_info[$name] ?? $default;
	}

	/**
	 * @param string $name information array key name
	 * @param mixed $value new information array value.
	 */
	protected function setInfo($name, $value)
	{
		$this->_info[$name] = $value;
	}

	/**
	 * @return string name of the table this column belongs to.
	 */
	public function getTableName()
	{
		return $this->getInfo('TableName');
	}

	/**
	 * @return string full name of the table, database dependent.
	 */
	public function getTableFullName()
	{
		return $this->_info['SchemaName'] . '.' . $this->getTableName();
	}

	/**
	 * @return bool whether the table is a view, default is false.
	 */
	public function getIsView()
	{
		return $this->getInfo('IsView', false);
	}

	/**
	 * @return TMap TDbTableColumn column meta data.
	 */
	public function getColumns()
	{
		return $this->_columns;
	}

	/**
	 * @param string $name column id
	 * @return \Prado\Data\Common\TDbTableColumn column information.
	 */
	public function getColumn($name)
	{
		if (($column = $this->_columns->itemAt($name)) !== null) {
			return $column;
		}
		throw new TDbException('dbtableinfo_invalid_column_name', $name, $this->getTableFullName());
	}

	/**
	 * @return array table column names (identifier quoted)
	 */
	public function getColumnNames()
	{
		$names = [];
		foreach ($this->getColumns() as $column) {
			$names[] = $column->getColumnName();
		}
		return $names;
	}

	/**
	 * @return string[] names of primary key columns.
	 */
	public function getPrimaryKeys()
	{
		return $this->_primaryKeys;
	}

	/**
	 * @return array tuples of foreign table and column name.
	 */
	public function getForeignKeys()
	{
		return $this->_foreignKeys;
	}

	/**
	 * @return array lowercased column key names mapped to normal column ids.
	 */
	public function getLowerCaseColumnNames()
	{
		if ($this->_lowercase === null) {
			$this->_lowercase = [];
			foreach ($this->getColumns()->getKeys() as $key) {
				$this->_lowercase[strtolower($key)] = $key;
			}
		}
		return $this->_lowercase;
	}
}
