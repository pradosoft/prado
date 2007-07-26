<?php
/**
 * TOracleTableInfo class file.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.Common.Oracle
 */

/**
 * Loads the base TDbTableInfo class and TOracleTableColumn class.
 */
Prado::using('System.Data.Common.TDbTableInfo');
Prado::using('System.Data.Common.Oracle.TOracleTableColumn');

/**
 * TOracleTableInfo class provides additional table information for ORACLE database.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.Common.Oracle
 * @since 3.1.1
 */
class TOracleTableInfo extends TDbTableInfo
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
			return $schema.'.'.$this->getTableName();
		else
			$this->getTableName();
	}

	/**
	 * @param TDbConnection database connection.
	 * @return TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		Prado::using('System.Data.Common.Oracle.TOracleCommandBuilder');
		return new TOracleCommandBuilder($connection,$this);
	}
}

?>