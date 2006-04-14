<?php

Prado::using('System.DataAccess.SQLMap.DataMapper.*');
Prado::using('System.DataAccess.SQLMap.Configuration.*');
Prado::using('System.DataAccess.SQLMap.Statements.*');
Prado::using('System.Collections.*');
Prado::using('System.DataAccess.SQLMap.DataMapper.TTypeHandlerFactory');
Prado::using('System.DataAccess.SQLMap.DataMapper.TSqlMapCache');
Prado::using('System.DataAccess.SQLMap.DataMapper.TDataMapperException');
Prado::using('System.DataAccess.TAdodbProvider');

/**
 * DataMapper client, a facade to provide access the rest of the DataMapper
 * framework. It provides three core functions:
 * 
 *  # execute an update query (including insert and delete).
 *  # execute a select query for a single object
 *  # execute a select query for a list of objects
 *
 * Do not create this class explicitly, use TDomSqlMapBuilder to obtain
 * an instance by parsing through the xml configurations. Example:
 * <code>
 * $builder = new TDomSqlMapBuilder(); 
 * $mapper = $builder->configure($configFile);
 * </code>
 *
 * Otherwise use convient classes TMapper or TSqlMap to obtain singleton
 * instances.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess.SQLMap
 * @since 3.0
 */
class TSqlMapper extends TComponent
{
	private $_connection;
	private $_mappedStatements;
	private $_provider;
	private $_resultMaps;
	private $_parameterMaps;
	private $_typeHandlerFactory;
	private $_cacheModelsEnabled = true;
	private $_cacheMaps;

	/**
	 * Create a new SqlMap.
	 * @param TTypeHandlerFactory
	 */
	public function __construct($typeHandlerFactory=null)
	{
		$this->_mappedStatements = new TMap;
		$this->_resultMaps = new TMap;
		$this->_parameterMaps = new TMap;
		$this->_typeHandlerFactory = $typeHandlerFactory;
		$this->_cacheMaps = new TMap;
	}

	/**
	 * Cleanup work before serializing.
	 * This is a PHP defined magic method.
	 * @return array the names of instance-variables to serialize.
	 */
	public function __sleep()
	{
		if(!is_null($this->_connection) && !$this->_connection->getIsClosed())
			$this->closeConnection();
		$this->_connection = null;
		return array_keys(get_object_vars($this));
	}
	
	/**
	 * This method will be automatically called when unserialization happens.
	 * This is a PHP defined magic method.
	 */
	public function __wake()
	{

	}
	
	/**
	 * Set the falg to tell us if cache models were enabled or not.
	 * This should only be called during configuration parsing.
	 * It does not disable the cache after the configuration phase.
	 * @param boolean enables cache.
	 */
	public function setCacheModelsEnabled($value)
	{
		$this->_cacheModelsEnabled = $value;
	}

	/**
	 * @return boolean true if cache models were enabled  when this SqlMap was
	 * built.
	 */
	public function getIsCacheModelsEnabled()
	{
		return $this->_cacheModelsEnabled;
	}

	/**
	 * @return TTypeHandlerFactory The TypeHandlerFactory
	 */
	public function getTypeHandlerFactory()
	{
		return $this->_typeHandlerFactory;
	}
	
	/**
	 * @return TMap mapped statements collection.
	 */
	public function getStatements()
	{
		return $this->_mappedStatements;
	}

	/**
	 * @return TMap result maps collection.
	 */
	public function getResultMaps()
	{
		return $this->_resultMaps;
	}

	/**
	 * Adds a named cache.
	 * @param TSqlMapCacheModel the cache to add.
	 * @throws TSqlMapConfigurationException
	 */
	public function addCache(TSqlMapCacheModel $cacheModel)
	{
		if($this->_cacheMaps->contains($cacheModel->getID()))
			throw new TSqlMapConfigurationException(
				'sqlmap_cache_model_already_exists', $cacheModel->getID());
		else
			$this->_cacheMaps->add($cacheModel->getID(), $cacheModel);
	}

	/**
	 * Gets a cache by name
	 * @param string the name of the cache to get.
	 * @return TSqlMapCacheModel the cache object.
	 * @throws TSqlMapConfigurationException
	 */
	public function getCache($name)
	{
		if(!$this->_cacheMaps->contains($name))
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_cache_model', $name);
		return $this->_cacheMaps[$name];
	}

	/**
	 * Flushes all cached objects that belong to this SqlMap
	 */
	public function flushCaches()
	{
		foreach($this->_cacheMaps as $cache)
			$cache->flush();
	}

	/**
	 * @return TMap parameter maps collection.
	 */
	public function getParameterMaps()
	{
		return $this->_parameterMaps;
	}

	/**
	 * Gets a MappedStatement by name.
	 * @param string The name of the statement.
	 * @return IMappedStatement The MappedStatement
	 * @throws TSqlMapUndefinedException
	 */
	public function getMappedStatement($name)
	{
		if($this->_mappedStatements->contains($name) == false)
			throw new TSqlMapUndefinedException(
						'sqlmap_contains_no_statement', $name);
		return $this->_mappedStatements[$name];
	}	

	/**
	 * Adds a (named) MappedStatement.
	 * @param string The key name
	 * @param IMappedStatement The statement to add
	 * @throws TSqlMapDuplicateException
	 */
	public function addMappedStatement(IMappedStatement $statement)
	{
		$key = $statement->getID();
		if($this->_mappedStatements->contains($key) == true)
			throw new TSqlMapDuplicateException(
					'sqlmap_already_contains_statement', $key);
		$this->_mappedStatements->add($key, $statement);
	}
	
	/**
	 * Gets a named result map 
	 * @param string result name.
	 * @return TResultMap the result map.
	 * @throws TSqlMapUndefinedException
	 */
	public function getResultMap($name)
	{
		if($this->_resultMaps->contains($name) == false)
			throw new TSqlMapUndefinedException(
					'sqlmap_contains_no_result_map', $name);
		return $this->_resultMaps[$name];
	}

	/**
	 * @param TResultMap add a new result map to this SQLMap
	 * @throws TSqlMapDuplicateException
	 */
	public function addResultMap(TResultMap $result)
	{
		$key = $result->getID();
		if($this->_resultMaps->contains($key) == true)
			throw new TSqlMapDuplicateException(
					'sqlmap_already_contains_result_map', $key);
		$this->_resultMaps->add($key, $result);
	}

	/**
	 * @param string parameter map ID name.
	 * @return TParameterMap the parameter with given ID.
	 * @throws TSqlMapUndefinedException
	 */
	public function getParameterMap($name)
	{
		if($this->_parameterMaps->contains($name) == false)
			throw new TSqlMapUndefinedException(
					'sqlmap_contains_no_parameter_map', $name);
		return $this->_parameterMaps[$name];
	}
	
	/**
	 * @param TParameterMap add a new parameter map to this SQLMap.
	 * @throws TSqlMapDuplicateException
	 */
	public function addParameterMap(TParameterMap $parameter)
	{
		$key = $parameter->getID();
		if($this->_parameterMaps->contains($key) == true)
			throw new TSqlMapDuplicateException(
					'sqlmap_already_contains_parameter_map', $key);
		$this->_parameterMaps->add($key, $parameter);
	}

	/**
	 * @param TDatabaseProvider changes the database provider.
	 */
	public function setDataProvider($provider)
	{
		$this->_provider = $provider;
	}

	/**
	 * @return TDatabaseProvider database provider.
	 */
	public function getDataProvider()
	{
		return $this->_provider;
	}

	/**
	 * Get the current connection, opens the connection if necessary.
	 * @return TDbConnection database connection.
	 */
	protected function getConnection()
	{
		if(is_null($this->_connection))
			$this->_connection = $this->getDataProvider()->getConnection();
		$this->_connection->open();
		return $this->_connection;
	}

	/**
	 * Open a connection, on the specified connection string if provided.
	 * @param string The connection DSN string
	 * @return TDbConnection database connection.
	 */
	public function openConnection($connectionString=null)
	{
		if(!is_null($connectionString))
		{
			if(!is_null($this->_connection))
				throw new TSqlMapConnectionException(
					'sqlmap_connection_already_exists');
			$this->getDataProvider()->setConnectionString($connectionString);
		}
		return $this->getConnection();
	}

	/**
	 * Close the current database connection.
	 */
	public function closeConnection()
	{
		if(is_null($this->_connection))
			throw new TSqlMapConnectionException(
				'sqlmap_unable_to_close_null_connection');
		$this->_connection->close();
	}

	/**
	 * Executes a Sql SELECT statement that returns that returns data 
	 * to populate a single object instance.
	 *
	 * The parameter object is generally used to supply the input
	 * data for the WHERE clause parameter(s) of the SELECT statement.
	 * 
	 * @param string The name of the sql statement to execute.
	 * @param mixed The object used to set the parameters in the SQL.
	 * @param mixed An object of the type to be returned.
	 * @return object A single result object populated with the result set data.
	 */
	public function queryForObject($statementName, $parameter=null, $result=null)
	{
		$statement = $this->getMappedStatement($statementName);
		$connection = $this->getConnection();
		return $statement->executeQueryForObject($connection, 
									$parameter, $result);
	}

	/**
	 * Executes a Sql SELECT statement that returns data to populate a number 
	 * of result objects.
	 *
	 * The parameter object is generally used to supply the input
	 * data for the WHERE clause parameter(s) of the SELECT statement.
	 *
	 * @param string The name of the sql statement to execute.
	 * @param mixed The object used to set the parameters in the SQL.
	 * @param TList An Ilist object used to hold the objects, 
	 * pass in null if want to return a list instead.
	 * @param int The number of rows to skip over.
	 * @param int The maximum number of rows to return.
	 * @return TList A List of result objects.
	 */
	public function queryForList($statementName, $parameter=null, 
									$result=null, $skip=-1, $max=-1)
	{
		$statement = $this->getMappedStatement($statementName);
		$connection = $this->getConnection();
		return $statement->executeQueryForList($connection, 
								$parameter, $result, $skip, $max);
	}

	/**
	 * Runs a query for list with a custom object that gets a chance to deal 
	 * with each row as it is processed.
	 *
	 * Example: $sqlmap->queryWithRowDelegate('getAccounts', array($this, 'rowHandler'));
	 *
	 * @param string The name of the sql statement to execute.
	 * @param callback Row delegate handler, a valid callback required.
	 * @param mixed The object used to set the parameters in the SQL.
	 * @param TList An Ilist object used to hold the objects, 
	 * pass in null if want to return a list instead.
	 * @param int The number of rows to skip over.
	 * @param int The maximum number of rows to return.
	 * @return TList A List of result objects.
	 */
	public function queryWithRowDelegate($statementName, $delegate, $parameter=null, 
									$result=null, $skip=-1, $max=-1)
	{
		$statement = $this->getMappedStatement($statementName);
		$connection = $this->getConnection();
		return $statement->executeQueryForList($connection, 
								$parameter, $result, $skip, $max, $delegate);
	}

	/**
	 * Executes the SQL and retuns a subset of the results in a dynamic 
	 * TPagedList that can be used to automatically scroll through results 
	 * from a database table.
	 * @param string The name of the sql statement to execute.
	 * @param mixed The object used to set the parameters in the SQL.
	 * @param integer The maximum number of objects to store in each page.
	 * @return TPagedList A PaginatedList of beans containing the rows.
	 */
	public function queryForPagedList($statementName, $parameter=null, $pageSize=10)
	{
		$statement = $this->getMappedStatement($statementName);
		return new TSqlMapPagedList($statement, $parameter, $pageSize);
	}

	/**
	 * Executes the SQL and retuns a subset of the results in a dynamic 
	 * TPagedList that can be used to automatically scroll through results 
	 * from a database table. 
	 * 
	 * Runs paged list query with row delegate
	 * Example: $sqlmap->queryForPagedListWithRowDelegate('getAccounts', array($this, 'rowHandler'));
	 *
	 * @param string The name of the sql statement to execute.
	 * @param callback Row delegate handler, a valid callback required.
	 * @param mixed The object used to set the parameters in the SQL.
	 * @param integer The maximum number of objects to store in each page.
	 * @return TPagedList A PaginatedList of beans containing the rows.
	 */
	public function queryForPagedListWithRowDelegate($statementName, 
								$delegate, $parameter=null, $pageSize=10)
	{
		$statement = $this->getMappedStatement($statementName);
		return new TSqlMapPagedList($statement, $parameter, $pageSize, $delegate);
	}


	/**
	 * Executes the SQL and retuns all rows selected in a map that is keyed on
	 * the property named  in the keyProperty parameter.  The value at each key
	 * will be the value of the property specified in the valueProperty
	 * parameter.  If valueProperty is null, the entire result object will be
	 * entered.
	 * @param string The name of the sql statement to execute.
	 * @param mixed The object used to set the parameters in the SQL.
	 * @param string The property of the result object to be used as the key.
	 * @param string The property of the result object to be used as the value.
	 * @return TMap Array object containing the rows keyed by keyProperty.
	 */
	public function queryForMap($statementName, $parameter=null, 
								$keyProperty=null, $valueProperty=null) 
	{
		$statement = $this->getMappedStatement($statementName);
		$connection = $this->getConnection();
		return $statement->executeQueryForMap($connection, 
								$parameter, $keyProperty, $valueProperty);
	}

	/**
	 * Runs a query with a custom object that gets a chance to deal 
	 * with each row as it is processed.
	 *
	 * Example: $sqlmap->queryForMapWithRowDelegate('getAccounts', array($this, 'rowHandler'));
	 *
	 * @param string The name of the sql statement to execute.
	 * @param callback Row delegate handler, a valid callback required.
	 * @param mixed The object used to set the parameters in the SQL.
	 * @param string The property of the result object to be used as the key.
	 * @param string The property of the result object to be used as the value.
	 * @return TMap Array object containing the rows keyed by keyProperty.
	 */
	public function queryForMapWithRowDelegate($statementName, 
			$delegate, $parameter=null, $keyProperty=null, $valueProperty=null) 
	{
		$statement = $this->getMappedStatement($statementName);
		$connection = $this->getConnection();
		return $statement->executeQueryForMap($connection, 
								$parameter, $keyProperty, $valueProperty, $delegate);
	}

	/**
	 * Executes a Sql INSERT statement.
	 *
	 * Insert is a bit different from other update methods, as it provides
	 * facilities for returning the primary key of the newly inserted row
	 * (rather than the effected rows),
	 *
	 * The parameter object is generally used to supply the input data for the
	 * INSERT values.
	 *
	 * @param string The name of the statement to execute.
	 * @param string The parameter object.
	 * @return mixed The primary key of the newly inserted row.  
	 * This might be automatically generated by the RDBMS, 
	 * or selected from a sequence table or other source.
	 */
	public function insert($statementName, $parameter=null)
	{
		$statement = $this->getMappedStatement($statementName);
		$connection = $this->getConnection();
		$generatedKey = $statement->executeInsert($connection, $parameter);
		return $generatedKey;
	}

	/**
	 * Executes a Sql UPDATE statement.
	 *
	 * Update can also be used for any other update statement type, such as  
	 * inserts and deletes.  Update returns the number of rows effected. 
	 *
	 * The parameter object is generally used to supply the input data for the
	 * UPDATE values as well as the WHERE clause parameter(s).
	 *
	 * @param string The name of the statement to execute.
	 * @param mixed The parameter object.
	 * @return integer The number of rows effected.
	 */
	public function update($statementName, $parameter=null)
	{
		$statement = $this->getMappedStatement($statementName);
		$connection = $this->getConnection();
		return $statement->executeUpdate($connection, $parameter);
	}

	/**
	 * Executes a Sql DELETE statement.  Delete returns the number of rows effected. 
	 * @param string The name of the statement to execute. 
	 * @param mixed The parameter object.
	 * @return integer The number of rows effected.
	 */
	public function delete($statementName, $parameter=null)
	{
		return $this->update($statementName, $parameter);
	}


	/**
	 * Begins a database transaction on the currect session.
	 * Some databases will always return false if transaction support is not
	 * available
	 * @return boolean true if successful, false otherwise.
	 */
	public function beginTransaction()
	{
		return $this->getConnection()->beginTransaction();
	}

	/**
	 * End a transaction successfully. If the database does not support 
	 * transactions, will return true also as data is always committed.
	 * @return boolean true if successful, false otherwise.
	 */
	public function commitTransaction()
	{
		return $this->getConnection()->commit();
	}

	/**
	 * End a transaction, rollback all changes. If the database does not 
	 * support transactions, will return false as data is never rollbacked.
	 * @return boolean true if successful, false otherwise.
	 */
	public function rollbackTransaction()
	{
		return $this->getConnection()->rollback();
	}
}

?>