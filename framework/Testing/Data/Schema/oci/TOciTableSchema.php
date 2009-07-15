<?php
/**
 * TOciTableSchema class file.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2009 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

prado::using('System.Testing.Data.Schema.TDbTableSchame');

/**
 * TOciTableSchema represents the metadata for a Ora table.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @version $Id: TOciTableSchema.php 2679 2009-06-15 07:49:42Z Christophe.Boulain $
 * @package System.Testing.Data.Schema.oci
 * @since 1.0.5
 */
class TOciTableSchema extends TDbTableSchema
{
	/**
	 * @var string name of the schema (database) that this table belongs to.
	 * Defaults to null, meaning no schema (or the current database).
	 */
	public $schemaName;
}
