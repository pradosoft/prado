<?php
/**
 * TMysqlTableInfo class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Data.Common.Mysql
 */

/**
 * Loads the base TDbTableInfo class and TMysqlTableColumn class.
 */
Prado::using('System.Data.Common.TDbTableInfo');
Prado::using('System.Data.Common.Mysql.TMysqlTableColumn');

/**
 * TMysqlTableInfo class provides additional table information for MySQL database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.Common.Mysql
 * @since 3.1
 */
class TMysqlTableInfo extends TDbTableInfo
{
	/**
	 * @return string name of the schema this column belongs to.
	 */
	public function getSchemaName()
	{
		return $this->getInfo('SchemaName');
	}

	/**
	 * @return string full name of the table, database dependent.
	 */
	public function getTableFullName()
	{
		if(($schema=$this->getSchemaName())!==null)
			return '`'.$schema.'`.`'.$this->getTableName().'`';
		else
			return '`'.$this->getTableName().'`';
	}

	/**
	 * @param TDbConnection database connection.
	 * @return TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		Prado::using('System.Data.Common.Mysql.TMysqlCommandBuilder');
		return new TMysqlCommandBuilder($connection,$this);
	}
}

