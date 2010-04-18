<?php
/**
 * TDbSchema class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2009 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

prado::using('System.Testing.Data.Schema.TDbCommandBuilder');

/**
 * TDbSchema is the base class for retrieving metadata information.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TDbSchema.php 2679 2009-06-15 07:49:42Z Christophe.Boulain $
 * @package System.Testing.Data.Schema
 * @since 1.0
 */
abstract class TDbSchema extends TComponent
{
	private $_tableNames=array();
	private $_tables=array();
	private $_connection;
	private $_builder;
	private $_cacheExclude=array();

	/**
	 * Creates a table instance representing the metadata for the named table.
	 * @return TDbTableSchema driver dependent table metadata, null if the table does not exist.
	 */
	abstract protected function createTable($name);

	/**
	 * Constructor.
	 * @param TDbConnection database connection.
	 */
	public function __construct($conn)
	{
		parent::__construct();
		$conn->setActive(true);
		$this->_connection=$conn;
		foreach($conn->schemaCachingExclude as $name)
			$this->_cacheExclude[$name]=true;
	}

	/**
	 * @return TDbConnection database connection. The connection is active.
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * Obtains the metadata for the named table.
	 * @param string table name
	 * @return TDbTableSchema table metadata. Null if the named table does not exist.
	 */
	public function getTable($name)
	{
		if(isset($this->_tables[$name]))
			return $this->_tables[$name];
		else if(!isset($this->_cacheExclude[$name]) && ($duration=$this->_connection->schemaCachingDuration)>0 && ($cache=prado::getApplication()->getCache())!==null)
		{
			$key='prado:dbschema'.$this->_connection->connectionString.':'.$this->_connection->username.':'.$name;
			if(($table=$cache->get($key))===false)
			{
				$table=$this->createTable($name);
				$cache->set($key,$table,$duration);
			}
			return $this->_tables[$name]=$table;
		}
		else
			return $this->_tables[$name]=$this->createTable($name);
	}

	/**
	 * Returns the metadata for all tables in the database.
	 * @param string the schema of the tables. Defaults to empty string, meaning the current or default schema.
	 * @return array the metadata for all tables in the database.
	 * Each array element is an instance of {@link TDbTableSchema} (or its child class).
	 * The array keys are table names.
	 * @since 1.0.2
	 */
	public function getTables($schema='')
	{
		$tables=array();
		foreach($this->getTableNames($schema) as $name)
			$tables[$name]=$this->getTable($name);
		return $tables;
	}

	/**
	 * Returns all table names in the database.
	 * @param string the schema of the tables. Defaults to empty string, meaning the current or default schema.
	 * If not empty, the returned table names will be prefixed with the schema name.
	 * @return array all table names in the database.
	 * @since 1.0.2
	 */
	public function getTableNames($schema='')
	{
		if(!isset($this->_tableNames[$schema]))
			$this->_tableNames[$schema]=$this->findTableNames($schema);
		return $this->_tableNames[$schema];
	}

	/**
	 * @return TDbCommandBuilder the SQL command builder for this connection.
	 */
	public function getCommandBuilder()
	{
		if($this->_builder!==null)
			return $this->_builder;
		else
			return $this->_builder=$this->createCommandBuilder();
	}

	/**
	 * Refreshes the schema.
	 * This method resets the loaded table metadata and command builder
	 * so that they can be recreated to reflect the change of schema.
	 */
	public function refresh()
	{
		$this->_tables=array();
		$this->_builder=null;
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		return "'".$name."'";
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		return '"'.$name.'"';
	}

	/**
	 * Compares two table names.
	 * The table names can be either quoted or unquoted. This method
	 * will consider both cases.
	 * @param string table name 1
	 * @param string table name 2
	 * @return boolean whether the two table names refer to the same table.
	 */
	public function compareTableNames($name1,$name2)
	{
		$name1=str_replace(array('"','`',"'"),'',$name1);
		$name2=str_replace(array('"','`',"'"),'',$name2);
		if(($pos=strrpos($name1,'.'))!==false)
			$name1=substr($name1,$pos+1);
		if(($pos=strrpos($name2,'.'))!==false)
			$name2=substr($name2,$pos+1);
		return $name1===$name2;
	}

	/**
	 * Creates a command builder for the database.
	 * This method may be overridden by child classes to create a DBMS-specific command builder.
	 * @return TDbCommandBuilder command builder instance
	 */
	protected function createCommandBuilder()
	{
		return new TDbCommandBuilder($this);
	}

	/**
	 * Returns all table names in the database.
	 * This method should be overridden by child classes in order to support this feature
	 * because the default implemenation simply throws an exception.
	 * @param string the schema of the tables. Defaults to empty string, meaning the current or default schema.
	 * If not empty, the returned table names will be prefixed with the schema name.
	 * @return array all table names in the database.
	 * @since 1.0.2
	 */
	protected function findTableNames($schema='')
	{
		throw new TDbException('{0} does not support fetching all table names.',
			get_class($this));
	}
}
