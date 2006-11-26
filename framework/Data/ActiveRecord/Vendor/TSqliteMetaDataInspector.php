<?php
/**
 * TSqliteMetaDataInspector class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

Prado::using('System.Data.ActiveRecord.Vendor.TDbMetaDataInspector');
Prado::using('System.Data.ActiveRecord.Vendor.TSqliteColumnMetaData');
Prado::using('System.Data.ActiveRecord.Vendor.TSqliteMetaData');

/**
 * Table meta data inspector for Sqlite database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TSqliteMetaDataInspector extends TDbMetaDataInspector
{
	/**
	 * Create a new instance of meta data.
	 * @param string table name
	 * @param array column meta data
	 * @param array primary key meta data
	 * @param array foreign key meta data.
	 * @return TDbMetaData table meta data.
	 */
	protected function createMetaData($table, $columns, $primary, $foreign)
	{
		$pks = array();
		foreach($columns as $name=>$column)
			if($column->getIsPrimaryKey())
				$pks[] = $name;
		return new TSqliteMetaData($table,$columns,$pks);
	}

	/**
	 * Get the column definitions for given table.
	 * @param string table name.
	 * @return array column name value pairs of column meta data.
	 */
	protected function getColumnDefinitions($table)
	{
		$conn=$this->getDbConnection();
		$conn->setActive(true);
		$table = $conn->quoteString($table);
		$command = $conn->createCommand("PRAGMA table_info({$table})");
		$command->prepare();
		$cols = array();
		foreach($command->query() as $col)
			$cols[$col['name']] = $this->getColumnMetaData($col);
		return $cols;
	}

	/**
	 * Returns the column details.
	 * @param array column details.
	 * @return TPgsqlColumnMetaData column meta data.
	 */
	protected function getColumnMetaData($col)
	{
		$name = '"'.$col['name'].'"'; //quote the column names!
		$type = $col['type'];

		$notNull = $col['notnull']==='99';
		$primary = $col['pk']==='1';
		$autoIncrement = strtolower($type)==='integer' && $primary;
		$default = $col['dflt_value'];
		return new TSqliteColumnMetaData($name,$type,$notNull,$autoIncrement,$default,$primary);
	}

	/**
	 * Not implemented, sqlite does not have foreign key constraints.
	 */
	protected function getConstraintKeys($table)
	{
		return array('primary'=>array(), 'foreign'=>array());
	}
}

?>