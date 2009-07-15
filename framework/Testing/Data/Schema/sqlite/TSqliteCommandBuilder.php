<?php
/**
 * TSqliteCommandBuilder class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2009 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

prado::using('System.Testing.Data.Schema.TDbCommandBuilder');

/**
 * TSqliteCommandBuilder provides basic methods to create query commands for SQLite tables.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TSqliteCommandBuilder.php 2679 2009-06-15 07:49:42Z Christophe.Boulain $
 * @package System.Testing.Data.Schema.sqlite
 * @since 1.0
 */
class TSqliteCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Generates the expression for selecting rows with specified composite key values.
	 * This method is overridden because SQLite does not support the default
	 * IN expression with composite columns.
	 * @param TDbTableSchema the table schema
	 * @param array list of primary key values to be selected within
	 * @param string column prefix (ended with dot)
	 * @return string the expression for selection
	 * @since 1.0.4
	 */
	protected function createCompositeInCondition($table,$values,$prefix)
	{
		$keyNames=array();
		foreach(array_keys($values[0]) as $name)
			$keyNames[]=$prefix.$table->columns[$name]->rawName;
		$vs=array();
		foreach($values as $value)
			$vs[]=implode("||','||",$value);
		return implode("||','||",$keyNames).' IN ('.implode(', ',$vs).')';
	}
}
