<?php
/**
 * TMysqlTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.Common.Mysql
 */

/**
 * Load common TDbTableCommon class.
 */
Prado::using('System.Data.Common.TDbTableColumn');

/**
 * Describes the column metadata of the schema for a Mysql database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.Common.Mysql
 * @since 3.1
 */
class TMysqlTableColumn extends TDbTableColumn
{
	/**
	 * @return boolean true if column will auto-increment when the column value is inserted as null.
	 */
	public function getAutoIncrement()
	{
		return $this->getInfo('AutoIncrement', false);
	}

	/**
	 * @return boolean true if auto increment is true.
	 */
	public function getHasSequence()
	{
		return $this->getAutoIncrement();
	}

	public function getDbTypeValues()
	{
		return $this->getInfo('DbTypeValues');
	}

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 * @return boolean derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		switch(strtolower($this->getDbType()))
		{
			case 'bit': case 'bit varying': case 'real': case 'serial': case 'int': case 'integer':
				return 'integer';
			case 'boolean':
				return 'boolean';
			case 'bigint': case 'bigserial': case 'double precision': case 'money': case 'numeric':
				return 'float';
			default:
				return 'string';
		}
	}
}

?>