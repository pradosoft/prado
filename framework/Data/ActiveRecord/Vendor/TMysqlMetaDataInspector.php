<?php
/**
 * TMysqlMetaDataInspector class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

Prado::using('System.Data.ActiveRecord.Vendor.TDbMetaDataInspector');
Prado::using('System.Data.ActiveRecord.Vendor.TMysqlColumnMetaData');
Prado::using('System.Data.ActiveRecord.Vendor.TMysqlMetaData');

/**
 * TMysqlMetaDataInspector class.
 *
 * Gathers table column properties for Mysql database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TMysqlMetaDataInspector extends TDbMetaDataInspector
{
	/**
	 * Get the column definitions for given table.
	 * @param string table name.
	 * @return array column name value pairs of column meta data.
	 */
	protected function getColumnDefinitions($table)
	{
		$sql="SHOW FULL FIELDS FROM `{$table}`";
		$conn = $this->getDbConnection();
		$conn->setActive(true);
		$command = $conn->createCommand($sql);
		$command->prepare();
		foreach($command->query() as $col)
			$cols[$col['Field']] = $this->getColumnMetaData($col);
		return $cols;
	}

	protected function getColumnMetaData($col)
	{
		$name = '`'.$col['Field'].'`'; //quote the column names!
		$type = $col['Type'];
		$notNull = $col['Null']==='NO';
		$autoIncrement=is_int(strpos(strtolower($col['Extra']), 'auto_increment'));
		$default = $col['Default'];
		$primaryKey = $col['Key']==='PRI';
		return new TMysqlColumnMetaData($name,$type,$notNull,$autoIncrement,$default,$primaryKey);
	}

	/**
	 * Not implemented, Mysql does not always have foreign key constraints.
	 */
	protected function getConstraintKeys($table)
	{
		return array('primary'=>array(), 'foreign'=>array());
	}

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
		return new TMysqlMetaData($table,$columns,$pks);
	}
}

?>