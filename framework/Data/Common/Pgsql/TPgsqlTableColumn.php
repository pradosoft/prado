<?php
/**
 * TPgsqlTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.Common.Pgsql
 */

/**
 * Load common TDbTableCommon class.
 */
Prado::using('System.Data.Common.TDbTableColumn');

/**
 * Describes the column metadata of the schema for a PostgreSQL database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.Common.Pgsql
 * @since 3.1
 */
class TPgsqlTableColumn extends TDbTableColumn
{
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