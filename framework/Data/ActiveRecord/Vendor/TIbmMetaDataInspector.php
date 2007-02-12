<?php
/**
 * TIbmMetaDataInspector class file.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */
Prado::using('System.Data.ActiveRecord.Vendor.TDbMetaDataInspector');
Prado::using('System.Data.ActiveRecord.Vendor.TIbmColumnMetaData');
Prado::using('System.Data.ActiveRecord.Vendor.TIbmMetaData');

/**
 * TIbmMetaDataInspector class.
 *
 * Column details for IBM DB2 database. Using php_pdo_ibm.dll extension.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TIbmMetaDataInspector extends TDbMetaDataInspector
{
	private $_schema;

	/**
	 * @param string default schema.
	 */
	public function setSchema($schema)
	{
		$this->_schema=$schema;
	}

	/**
	 * @return string default schema.
	 */
	public function getSchema()
	{
		return $this->_schema;
	}
	/**
	 * Get the column definitions for given table.
	 * @param string table name.
	 * @return array column name value pairs of column meta data.
	 */
	protected function getColumnDefinitions($table)
	{
		if(count($parts= explode('.', $table)) > 1)
		{
			$tablename = $parts[1];
			$schema = $parts[0];
		}
		else
		{
			$tablename = $parts[0];
			$schema = $this->getSchema();
		}
		$sql="SELECT * FROM SYSCAT.COLUMNS WHERE TABNAME='".strtoupper($tablename)."'";
		if ($schema)
			$sql=$sql." AND TABSCHEMA='".strtoupper($schema)."'";

		$conn = $this->getDbConnection();
		$conn->setActive(true);
		$command = $conn->createCommand($sql);
		$command->prepare();
		$result=$command->query($sql);
		foreach ($result as $col)
    		$cols[strtolower($col['COLNAME'])] = $this->getColumnMetaData($col);
		return $cols;
	}

	protected function getColumnMetaData($col)
	{
		$name = strtolower($col['COLNAME']);
		$type = $col['TYPENAME'];
		$length = $col['LENGTH'];
		$notNull = $col['NULLS']==='N';
		$autoIncrement=$col['IDENTITY']==='N';
		$default = $col['DEFAULT'];
		$primaryKey = $col['KEYSEQ']?1:0;
		return new TIbmColumnMetaData($name,$type,$length,$notNull,$autoIncrement,$default,$primaryKey);
	}

	/**
	 * Not implemented, IBM does not always have foreign key constraints.
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
		return new TIbmMetaData($table,$columns,$pks);
	}
}
?>