<?php
/**
 * TMappedStatement and related classes.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Statements
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Collections\TList;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\SqlMap\Configuration\TResultProperty;
use Prado\Data\SqlMap\Configuration\TSqlMapInsert;
use Prado\Data\SqlMap\Configuration\TSqlMapStatement;
use Prado\Data\SqlMap\DataMapper\TLazyLoadList;
use Prado\Data\SqlMap\DataMapper\TPropertyAccess;
use Prado\Data\SqlMap\DataMapper\TSqlMapExecutionException;
use Prado\Data\SqlMap\TSqlMapManager;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TMappedStatement class executes SQL mapped statements. Mapped Statements can
 * hold any SQL statement and use Parameter Maps and Result Maps for input and output.
 *
 * This class is usualy instantiated during SQLMap configuration by TSqlDomBuilder.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.0
 */
class TMappedStatement extends \Prado\TComponent implements IMappedStatement
{
	/**
	 * @var TSqlMapStatement current SQL statement.
	 */
	private $_statement;

	/**
	 * @var TPreparedCommand SQL command prepareer
	 */
	private $_command;

	/**
	 * @var TSqlMapper sqlmap used by this mapper.
	 */
	private $_manager;

	/**
	 * @var TPostSelectBinding[] post select statement queue.
	 */
	private $_selectQueue = [];

	/**
	 * @var bool true when data is mapped to a particular row.
	 */
	private $_IsRowDataFound = false;

	/**
	 * @var TSQLMapObjectCollectionTree group by object collection tree
	 */
	private $_groupBy;

	/**
	 * @var Post select is to query for list.
	 */
	const QUERY_FOR_LIST = 0;

	/**
	 * @var Post select is to query for list.
	 */
	const QUERY_FOR_ARRAY = 1;

	/**
	 * @var Post select is to query for object.
	 */
	const QUERY_FOR_OBJECT = 2;

	/**
	 * @return string Name used to identify the TMappedStatement amongst the others.
	 * This the name of the SQL statement by default.
	 */
	public function getID()
	{
		return $this->_statement->ID;
	}

	/**
	 * @return TSqlMapStatement The SQL statment used by this MappedStatement
	 */
	public function getStatement()
	{
		return $this->_statement;
	}

	/**
	 * @return TSqlMapManager The SqlMap used by this MappedStatement
	 */
	public function getManager()
	{
		return $this->_manager;
	}

	/**
	 * @return TPreparedCommand command to prepare SQL statements.
	 */
	public function getCommand()
	{
		return $this->_command;
	}

	/**
	 * Empty the group by results cache.
	 */
	protected function initialGroupByResults()
	{
		$this->_groupBy = new TSqlMapObjectCollectionTree();
	}

	/**
	 * Creates a new mapped statement.
	 * @param TSqlMapManager $sqlMap an sqlmap.
	 * @param TSqlMapStatement $statement An SQL statement.
	 */
	public function __construct(TSqlMapManager $sqlMap, TSqlMapStatement $statement)
	{
		$this->_manager = $sqlMap;
		$this->_statement = $statement;
		$this->_command = new TPreparedCommand();
		$this->initialGroupByResults();
	}

	public function getSqlString()
	{
		return $this->getStatement()->getSqlText()->getPreparedStatement()->getPreparedSql();
	}

	/**
	 * Execute SQL Query.
	 * @param IDbConnection $connection database connection
	 * @param array $sql SQL statement and parameters.
	 * @param mixed $command
	 * @param mixed $max
	 * @param mixed $skip
	 * @throws TSqlMapExecutionException if execution error or false record set.
	 * @throws TSqlMapQueryExecutionException if any execution error
	 * @return mixed record set if applicable.
	 */
	/*	protected function executeSQLQuery($connection, $sql)
		{
			try
			{
				if(!($recordSet = $connection->execute($sql['sql'],$sql['parameters'])))
				{
					throw new TSqlMapExecutionException(
						'sqlmap_execution_error_no_record', $this->getID(),
						$connection->ErrorMsg());
				}
				return $recordSet;
			}
			catch (Exception $e)
			{
				throw new TSqlMapQueryExecutionException($this->getStatement(), $e);
			}
		}*/

	/**
	 * Execute SQL Query with limits.
	 * @param IDbConnection $connection database connection
	 * @param $command
	 * @param int $max The maximum number of rows to return.
	 * @param int $skip The number of rows to skip over.
	 * @throws TSqlMapExecutionException if execution error or false record set.
	 * @throws TSqlMapQueryExecutionException if any execution error
	 * @return mixed record set if applicable.
	 */
	protected function executeSQLQueryLimit($connection, $command, $max, $skip)
	{
		if ($max > -1 || $skip > -1) {
			$maxStr = $max > 0 ? ' LIMIT ' . $max : '';
			$skipStr = $skip > 0 ? ' OFFSET ' . $skip : '';
			$command->setText($command->getText() . $maxStr . $skipStr);
		}
		$connection->setActive(true);
		return $command->query();

		/*//var_dump($command);
		try
		{
			$recordSet = $connection->selectLimit($sql['sql'],$max,$skip,$sql['parameters']);
			if(!$recordSet)
			{
				throw new TSqlMapExecutionException(
							'sqlmap_execution_error_query_for_list',
							$connection->ErrorMsg());
			}
			return $recordSet;
		}
		catch (Exception $e)
		{
			throw new TSqlMapQueryExecutionException($this->getStatement(), $e);
		}*/
	}

	/**
	 * Executes the SQL and retuns a List of result objects.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @param null|object $result result collection object.
	 * @param int $skip The number of rows to skip over.
	 * @param int $max The maximum number of rows to return.
	 * @param null|callable $delegate row delegate handler
	 * @return array a list of result objects
	 * @see executeQueryForList()
	 */
	public function executeQueryForList($connection, $parameter, $result = null, $skip = -1, $max = -1, $delegate = null)
	{
		$sql = $this->_command->create($this->_manager, $connection, $this->_statement, $parameter, $skip, $max);
		return $this->runQueryForList($connection, $parameter, $sql, $result, $delegate);
	}

	/**
	 * Executes the SQL and retuns a List of result objects.
	 *
	 * This method should only be called by internal developers, consider using
	 * <tt>executeQueryForList()</tt> first.
	 *
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @param array $sql SQL string and subsititution parameters.
	 * @param object $result result collection object.
	 * @param null|callable $delegate row delegate handler
	 * @return array a list of result objects
	 * @see executeQueryForList()
	 */
	public function runQueryForList($connection, $parameter, $sql, $result, $delegate = null)
	{
		$registry = $this->getManager()->getTypeHandlers();
		$list = $result instanceof \ArrayAccess ? $result :
							$this->_statement->createInstanceOfListClass($registry);
		$connection->setActive(true);
		$reader = $sql->query();
		//$reader = $this->executeSQLQueryLimit($connection, $sql, $max, $skip);
		if ($delegate !== null) {
			foreach ($reader as $row) {
				$obj = $this->applyResultMap($row);
				$param = new TResultSetListItemParameter($obj, $parameter, $list);
				$this->raiseRowDelegate($delegate, $param);
			}
		} else {
			//var_dump($sql,$parameter);
			foreach ($reader as $row) {
//				var_dump($row);
				$list[] = $this->applyResultMap($row);
			}
		}

		if (!$this->_groupBy->isEmpty()) {
			$list = $this->_groupBy->collect();
			$this->initialGroupByResults();
		}

		$this->executePostSelect($connection);
		$this->onExecuteQuery($sql);

		return $list;
	}

	/**
	 * Executes the SQL and retuns all rows selected in a map that is keyed on
	 * the property named in the keyProperty parameter.  The value at each key
	 * will be the value of the property specified in the valueProperty parameter.
	 * If valueProperty is null, the entire result object will be entered.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @param string $keyProperty The property of the result object to be used as the key.
	 * @param null|string $valueProperty The property of the result object to be used as the value (or null).
	 * @param int $skip The number of rows to skip over.
	 * @param int $max The maximum number of rows to return.
	 * @param null|callable $delegate row delegate handler
	 * @return array An array of object containing the rows keyed by keyProperty.
	 */
	public function executeQueryForMap($connection, $parameter, $keyProperty, $valueProperty = null, $skip = -1, $max = -1, $delegate = null)
	{
		$sql = $this->_command->create($this->_manager, $connection, $this->_statement, $parameter, $skip, $max);
		return $this->runQueryForMap($connection, $parameter, $sql, $keyProperty, $valueProperty, $delegate);
	}

	/**
	 * Executes the SQL and retuns all rows selected in a map that is keyed on
	 * the property named in the keyProperty parameter.  The value at each key
	 * will be the value of the property specified in the valueProperty parameter.
	 * If valueProperty is null, the entire result object will be entered.
	 *
	 * This method should only be called by internal developers, consider using
	 * <tt>executeQueryForMap()</tt> first.
	 *
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @param mixed $command
	 * @param string $keyProperty The property of the result object to be used as the key.
	 * @param null|string $valueProperty The property of the result object to be used as the value (or null).
	 * @param null|callable $delegate row delegate, a callback function
	 * @return array An array of object containing the rows keyed by keyProperty.
	 * @see executeQueryForMap()
	 */
	public function runQueryForMap($connection, $parameter, $command, $keyProperty, $valueProperty = null, $delegate = null)
	{
		$map = [];
		//$recordSet = $this->executeSQLQuery($connection, $sql);
		$connection->setActive(true);
		$reader = $command->query();
		if ($delegate !== null) {
			//while($row = $recordSet->fetchRow())
			foreach ($reader as $row) {
				$obj = $this->applyResultMap($row);
				$key = TPropertyAccess::get($obj, $keyProperty);
				$value = ($valueProperty === null) ? $obj :
							TPropertyAccess::get($obj, $valueProperty);
				$param = new TResultSetMapItemParameter($key, $value, $parameter, $map);
				$this->raiseRowDelegate($delegate, $param);
			}
		} else {
			//while($row = $recordSet->fetchRow())
			foreach ($reader as $row) {
				$obj = $this->applyResultMap($row);
				$key = TPropertyAccess::get($obj, $keyProperty);
				$map[$key] = ($valueProperty === null) ? $obj :
								TPropertyAccess::get($obj, $valueProperty);
			}
		}
		$this->onExecuteQuery($command);
		return $map;
	}

	/**
	 * Raises delegate handler.
	 * This method is invoked for each new list item. It is the responsibility
	 * of the handler to add the item to the list.
	 * @param callable $handler to be executed
	 * @param mixed $param event parameter
	 */
	protected function raiseRowDelegate($handler, $param)
	{
		if (is_string($handler)) {
			call_user_func($handler, $this, $param);
		} elseif (is_callable($handler, true)) {
			// an array: 0 - object, 1 - method name/path
			[$object, $method] = $handler;
			if (is_string($object)) {	// static method call
				call_user_func($handler, $this, $param);
			} else {
				if (($pos = strrpos($method, '.')) !== false) {
					$object = $this->getSubProperty(substr($method, 0, $pos));
					$method = substr($method, $pos + 1);
				}
				$object->$method($this, $param);
			}
		} else {
			throw new TInvalidDataValueException('sqlmap_invalid_delegate', $this->getID(), $handler);
		}
	}

	/**
	 * Executes an SQL statement that returns a single row as an object of the
	 * type of the <tt>$result</tt> passed in as a parameter.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter The parameter data (object, arrary, primitive) used to set the parameters in the SQL
	 * @param null|mixed $result The result object.
	 * @return object the object.
	 */
	public function executeQueryForObject($connection, $parameter, $result = null)
	{
		$sql = $this->_command->create($this->_manager, $connection, $this->_statement, $parameter);
		return $this->runQueryForObject($connection, $sql, $result);
	}

	/**
	 * Executes an SQL statement that returns a single row as an object of the
	 * type of the <tt>$result</tt> passed in as a parameter.
	 *
	 * This method should only be called by internal developers, consider using
	 * <tt>executeQueryForObject()</tt> first.
	 *
	 * @param IDbConnection $connection database connection
	 * @param $command
	 * @param object &$result The result object.
	 * @return object the object.
	 * @see executeQueryForObject()
	 */
	public function runQueryForObject($connection, $command, &$result)
	{
		$object = null;
		$connection->setActive(true);
		foreach ($command->query() as $row) {
			$object = $this->applyResultMap($row, $result);
		}

		if (!$this->_groupBy->isEmpty()) {
			$list = $this->_groupBy->collect();
			$this->initialGroupByResults();
			$object = $list[0];
		}

		$this->executePostSelect($connection);
		$this->onExecuteQuery($command);

		return $object;
	}

	/**
	 * Execute an insert statement. Fill the parameter object with the ouput
	 * parameters if any, also could return the insert generated key.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter The parameter object used to fill the statement.
	 * @return string the insert generated key.
	 */
	public function executeInsert($connection, $parameter)
	{
		$generatedKey = $this->getPreGeneratedSelectKey($connection, $parameter);

		$command = $this->_command->create($this->_manager, $connection, $this->_statement, $parameter);
//		var_dump($command,$parameter);
		$result = $command->execute();

		if ($generatedKey === null) {
			$generatedKey = $this->getPostGeneratedSelectKey($connection, $parameter);
		}

		$this->executePostSelect($connection);
		$this->onExecuteQuery($command);
		return $generatedKey;
	}

	/**
	 * Gets the insert generated ID before executing an insert statement.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter insert statement parameter.
	 * @return string new insert ID if pre-select key statement was executed, null otherwise.
	 */
	protected function getPreGeneratedSelectKey($connection, $parameter)
	{
		if ($this->_statement instanceof TSqlMapInsert) {
			$selectKey = $this->_statement->getSelectKey();
			if (($selectKey !== null) && !$selectKey->getIsAfter()) {
				return $this->executeSelectKey($connection, $parameter, $selectKey);
			}
		}
	}

	/**
	 * Gets the inserted row ID after executing an insert statement.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter insert statement parameter.
	 * @return string last insert ID, null otherwise.
	 */
	protected function getPostGeneratedSelectKey($connection, $parameter)
	{
		if ($this->_statement instanceof TSqlMapInsert) {
			$selectKey = $this->_statement->getSelectKey();
			if (($selectKey !== null) && $selectKey->getIsAfter()) {
				return $this->executeSelectKey($connection, $parameter, $selectKey);
			}
		}
	}

	/**
	 * Execute the select key statement, used to obtain last insert ID.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter insert statement parameter
	 * @param TSqlMapSelectKey $selectKey select key statement
	 * @return string last insert ID.
	 */
	protected function executeSelectKey($connection, $parameter, $selectKey)
	{
		$mappedStatement = $this->getManager()->getMappedStatement($selectKey->getID());
		$generatedKey = $mappedStatement->executeQueryForObject(
			$connection,
			$parameter,
			null
		);
		if (strlen($prop = $selectKey->getProperty()) > 0) {
			TPropertyAccess::set($parameter, $prop, $generatedKey);
		}
		return $generatedKey;
	}

	/**
	 * Execute an update statement. Also used for delete statement.
	 * Return the number of rows effected.
	 * @param IDbConnection $connection database connection
	 * @param mixed $parameter The object used to set the parameters in the SQL.
	 * @return int The number of rows effected.
	 */
	public function executeUpdate($connection, $parameter)
	{
		$sql = $this->_command->create($this->getManager(), $connection, $this->_statement, $parameter);
		$affectedRows = $sql->execute();
		//$this->executeSQLQuery($connection, $sql);
		$this->executePostSelect($connection);
		$this->onExecuteQuery($sql);
		return $affectedRows;
	}

	/**
	 * Process 'select' result properties
	 * @param IDbConnection $connection database connection
	 */
	protected function executePostSelect($connection)
	{
		while (count($this->_selectQueue)) {
			$postSelect = array_shift($this->_selectQueue);
			$method = $postSelect->getMethod();
			$statement = $postSelect->getStatement();
			$property = $postSelect->getResultProperty()->getProperty();
			$keys = $postSelect->getKeys();
			$resultObject = $postSelect->getResultObject();

			if ($method == self::QUERY_FOR_LIST || $method == self::QUERY_FOR_ARRAY) {
				$values = $statement->executeQueryForList($connection, $keys, null);

				if ($method == self::QUERY_FOR_ARRAY) {
					$values = $values->toArray();
				}
				TPropertyAccess::set($resultObject, $property, $values);
			} elseif ($method == self::QUERY_FOR_OBJECT) {
				$value = $statement->executeQueryForObject($connection, $keys, null);
				TPropertyAccess::set($resultObject, $property, $value);
			}
		}
	}

	/**
	 * Raise the execute query event.
	 * @param array $sql prepared SQL statement and subsititution parameters
	 */
	public function onExecuteQuery($sql)
	{
		$this->raiseEvent('OnExecuteQuery', $this, $sql);
	}

	/**
	 * Apply result mapping.
	 * @param array $row a result set row retrieved from the database
	 * @param null|&object $resultObject the result object, will create if necessary.
	 * @return object the result filled with data, null if not filled.
	 */
	protected function applyResultMap($row, &$resultObject = null)
	{
		if ($row === false) {
			return null;
		}

		$resultMapName = $this->_statement->getResultMap();
		$resultClass = $this->_statement->getResultClass();

		$obj = null;
		if ($this->getManager()->getResultMaps()->contains($resultMapName)) {
			$obj = $this->fillResultMap($resultMapName, $row, null, $resultObject);
		} elseif (strlen($resultClass) > 0) {
			$obj = $this->fillResultClass($resultClass, $row, $resultObject);
		} else {
			$obj = $this->fillDefaultResultMap(null, $row, $resultObject);
		}
		if (class_exists('TActiveRecord', false) && $obj instanceof TActiveRecord) {
			//Create a new clean active record.
			$obj = TActiveRecord::createRecord(get_class($obj), $obj);
		}
		return $obj;
	}

	/**
	 * Fill the result using ResultClass, will creates new result object if required.
	 * @param string $resultClass result object class name
	 * @param array $row a result set row retrieved from the database
	 * @param object $resultObject the result object, will create if necessary.
	 * @return object result object filled with data
	 */
	protected function fillResultClass($resultClass, $row, $resultObject)
	{
		if ($resultObject === null) {
			$registry = $this->getManager()->getTypeHandlers();
			$resultObject = $this->_statement->createInstanceOfResultClass($registry, $row);
		}

		if ($resultObject instanceof \ArrayAccess) {
			return $this->fillResultArrayList($row, $resultObject);
		} elseif (is_object($resultObject)) {
			return $this->fillResultObjectProperty($row, $resultObject);
		} else {
			return $this->fillDefaultResultMap(null, $row, $resultObject);
		}
	}

	/**
	 * Apply the result to a TList or an array.
	 * @param array $row a result set row retrieved from the database
	 * @param object $resultObject result object, array or list
	 * @return object result filled with data.
	 */
	protected function fillResultArrayList($row, $resultObject)
	{
		if ($resultObject instanceof TList) {
			foreach ($row as $v) {
				$resultObject[] = $v;
			}
		} else {
			foreach ($row as $k => $v) {
				$resultObject[$k] = $v;
			}
		}
		return $resultObject;
	}

	/**
	 * Apply the result to an object.
	 * @param array $row a result set row retrieved from the database
	 * @param object $resultObject result object, array or list
	 * @return object result filled with data.
	 */
	protected function fillResultObjectProperty($row, $resultObject)
	{
		$index = 0;
		$registry = $this->getManager()->getTypeHandlers();
		foreach ($row as $k => $v) {
			$property = new TResultProperty;
			if (is_string($k) && strlen($k) > 0) {
				$property->setColumn($k);
			}
			$property->setColumnIndex(++$index);
			$type = gettype(TPropertyAccess::get($resultObject, $k));
			$property->setType($type);
			$value = $property->getPropertyValue($registry, $row);
			TPropertyAccess::set($resultObject, $k, $value);
		}
		return $resultObject;
	}

	/**
	 * Fills the result object according to result mappings.
	 * @param string $resultMapName result map name.
	 * @param array $row a result set row retrieved from the database
	 * @param null|mixed $parentGroup
	 * @param null|&object $resultObject result object to fill, will create new instances if required.
	 * @return object result object filled with data.
	 */
	protected function fillResultMap($resultMapName, $row, $parentGroup = null, &$resultObject = null)
	{
		$resultMap = $this->getManager()->getResultMap($resultMapName);
		$registry = $this->getManager()->getTypeHandlers();
		$resultMap = $resultMap->resolveSubMap($registry, $row);

		if ($resultObject === null) {
			$resultObject = $resultMap->createInstanceOfResult($registry);
		}

		if (is_object($resultObject)) {
			if (strlen($resultMap->getGroupBy()) > 0) {
				return $this->addResultMapGroupBy($resultMap, $row, $parentGroup, $resultObject);
			} else {
				foreach ($resultMap->getColumns() as $property) {
					$this->setObjectProperty($resultMap, $property, $row, $resultObject);
				}
			}
		} else {
			$resultObject = $this->fillDefaultResultMap($resultMap, $row, $resultObject);
		}
		return $resultObject;
	}

	/**
	 * ResultMap with GroupBy property. Save object collection graph in a tree
	 * and collect the result later.
	 * @param TResultMap $resultMap result mapping details.
	 * @param array $row a result set row retrieved from the database
	 * @param mixed $parent
	 * @param object &$resultObject the result object
	 * @return object result object.
	 */
	protected function addResultMapGroupBy($resultMap, $row, $parent, &$resultObject)
	{
		$group = $this->getResultMapGroupKey($resultMap, $row);

		if (empty($parent)) {
			$rootObject = ['object' => $resultObject, 'property' => null];
			$this->_groupBy->add(null, $group, $rootObject);
		}

		foreach ($resultMap->getColumns() as $property) {
			//set properties.
			$this->setObjectProperty($resultMap, $property, $row, $resultObject);
			$nested = $property->getResultMapping();

			//nested property
			if ($this->getManager()->getResultMaps()->contains($nested)) {
				$nestedMap = $this->getManager()->getResultMap($nested);
				$groupKey = $this->getResultMapGroupKey($nestedMap, $row);

				//add the node reference first
				if (empty($parent)) {
					$this->_groupBy->add($group, $groupKey, '');
				}

				//get the nested result mapping value
				$value = $this->fillResultMap($nested, $row, $groupKey);

				//add it to the object tree graph
				$groupObject = ['object' => $value, 'property' => $property->getProperty()];
				if (empty($parent)) {
					$this->_groupBy->add($group, $groupKey, $groupObject);
				} else {
					$this->_groupBy->add($parent, $groupKey, $groupObject);
				}
			}
		}
		return $resultObject;
	}

	/**
	 * Gets the result 'group by' groupping key for each row.
	 * @param TResultMap $resultMap result mapping details.
	 * @param array $row a result set row retrieved from the database
	 * @return string groupping key.
	 */
	protected function getResultMapGroupKey($resultMap, $row)
	{
		$groupBy = $resultMap->getGroupBy();
		if (isset($row[$groupBy])) {
			return $resultMap->getID() . $row[$groupBy];
		} else {
			return $resultMap->getID() . crc32(serialize($row));
		}
	}

	/**
	 * Fill the result map using default settings. If <tt>$resultMap</tt> is null
	 * the result object returned will be guessed from <tt>$resultObject</tt>.
	 * @param TResultMap $resultMap result mapping details.
	 * @param array $row a result set row retrieved from the database
	 * @param object $resultObject the result object
	 * @return mixed the result object filled with data.
	 */
	protected function fillDefaultResultMap($resultMap, $row, $resultObject)
	{
		if ($resultObject === null) {
			$resultObject = '';
		}

		if ($resultMap !== null) {
			$result = $this->fillArrayResultMap($resultMap, $row, $resultObject);
		} else {
			$result = $row;
		}

		//if scalar result types
		if (count($result) == 1 && ($type = gettype($resultObject)) != 'array') {
			return $this->getScalarResult($result, $type);
		} else {
			return $result;
		}
	}

	/**
	 * Retrieve the result map as an array.
	 * @param TResultMap $resultMap result mapping details.
	 * @param array $row a result set row retrieved from the database
	 * @param object $resultObject the result object
	 * @return array array list of result objects.
	 */
	protected function fillArrayResultMap($resultMap, $row, $resultObject)
	{
		$result = [];
		$registry = $this->getManager()->getTypeHandlers();
		foreach ($resultMap->getColumns() as $column) {
			if (($column->getType() === null)
				&& ($resultObject !== null) && !is_object($resultObject)) {
				$column->setType(gettype($resultObject));
			}
			$result[$column->getProperty()] = $column->getPropertyValue($registry, $row);
		}
		return $result;
	}

	/**
	 * Converts the first array value to scalar value of given type.
	 * @param array $result list of results
	 * @param string $type scalar type.
	 * @return mixed scalar value.
	 */
	protected function getScalarResult($result, $type)
	{
		$scalar = array_shift($result);
		settype($scalar, $type);
		return $scalar;
	}

	/**
	 * Set a property of the result object with appropriate value.
	 * @param TResultMap $resultMap result mapping details.
	 * @param TResultProperty $property the result property to fill.
	 * @param array $row a result set row retrieved from the database
	 * @param object &$resultObject the result object
	 */
	protected function setObjectProperty($resultMap, $property, $row, &$resultObject)
	{
		$select = $property->getSelect();
		$key = $property->getProperty();
		$nested = $property->getNestedResultMap();
		$registry = $this->getManager()->getTypeHandlers();
		if ($key === '') {
			$resultObject = $property->getPropertyValue($registry, $row);
		} elseif (strlen($select) == 0 && ($nested === null)) {
			$value = $property->getPropertyValue($registry, $row);

			$this->_IsRowDataFound = $this->_IsRowDataFound || ($value != null);
			if (is_array($resultObject) || is_object($resultObject)) {
				TPropertyAccess::set($resultObject, $key, $value);
			} else {
				$resultObject = $value;
			}
		} elseif ($nested !== null) {
			if ($property->instanceOfListType($resultObject) || $property->instanceOfArrayType($resultObject)) {
				if (strlen($resultMap->getGroupBy()) <= 0) {
					throw new TSqlMapExecutionException(
						'sqlmap_non_groupby_array_list_type',
						$resultMap->getID(),
						get_class($resultObject),
						$key
					);
				}
			} else {
				$obj = $nested->createInstanceOfResult($this->getManager()->getTypeHandlers());
				if ($this->fillPropertyWithResultMap($nested, $row, $obj) == false) {
					$obj = null;
				}
				TPropertyAccess::set($resultObject, $key, $obj);
			}
		} else { //'select' ResultProperty
			$this->enquequePostSelect($select, $resultMap, $property, $row, $resultObject);
		}
	}

	/**
	 * Add nested result property to post select queue.
	 * @param string $select post select statement ID
	 * @param TResultMap $resultMap current result mapping details.
	 * @param TResultProperty $property current result property.
	 * @param array $row a result set row retrieved from the database
	 * @param object $resultObject the result object
	 */
	protected function enquequePostSelect($select, $resultMap, $property, $row, $resultObject)
	{
		$statement = $this->getManager()->getMappedStatement($select);
		$key = $this->getPostSelectKeys($resultMap, $property, $row);
		$postSelect = new TPostSelectBinding;
		$postSelect->setStatement($statement);
		$postSelect->setResultObject($resultObject);
		$postSelect->setResultProperty($property);
		$postSelect->setKeys($key);

		if ($property->instanceOfListType($resultObject)) {
			$values = null;
			if ($property->getLazyLoad()) {
				$values = TLazyLoadList::newInstance(
					$statement,
					$key,
					$resultObject,
					$property->getProperty()
				);
				TPropertyAccess::set($resultObject, $property->getProperty(), $values);
			} else {
				$postSelect->setMethod(self::QUERY_FOR_LIST);
			}
		} elseif ($property->instanceOfArrayType($resultObject)) {
			$postSelect->setMethod(self::QUERY_FOR_ARRAY);
		} else {
			$postSelect->setMethod(self::QUERY_FOR_OBJECT);
		}

		if (!$property->getLazyLoad()) {
			$this->_selectQueue[] = $postSelect;
		}
	}

	/**
	 * Finds in the post select property the SQL statement primary selection keys.
	 * @param TResultMap $resultMap result mapping details
	 * @param TResultProperty $property result property
	 * @param array $row current row data.
	 * @return array list of primary key values.
	 */
	protected function getPostSelectKeys($resultMap, $property, $row)
	{
		$value = $property->getColumn();
		if (is_int(strpos($value, ',', 0)) || is_int(strpos($value, '=', 0))) {
			$keys = [];
			foreach (explode(',', $value) as $entry) {
				$pair = explode('=', $entry);
				$keys[trim($pair[0])] = $row[trim($pair[1])];
			}
			return $keys;
		} else {
			$registry = $this->getManager()->getTypeHandlers();
			return $property->getPropertyValue($registry, $row);
		}
	}

	/**
	 * Fills the property with result mapping results.
	 * @param TResultMap $resultMap nested result mapping details.
	 * @param array $row a result set row retrieved from the database
	 * @param object &$resultObject the result object
	 * @return bool true if the data was found, false otherwise.
	 */
	protected function fillPropertyWithResultMap($resultMap, $row, &$resultObject)
	{
		$dataFound = false;
		foreach ($resultMap->getColumns() as $property) {
			$this->_IsRowDataFound = false;
			$this->setObjectProperty($resultMap, $property, $row, $resultObject);
			$dataFound = $dataFound || $this->_IsRowDataFound;
		}
		$this->_IsRowDataFound = $dataFound;
		return $dataFound;
	}

	public function __wakeup()
	{
		if (null === $this->_selectQueue) {
			$this->_selectQueue = [];
		}
	}

	public function __sleep()
	{
		$exprops = [];
		$cn = __CLASS__;
		if (!count($this->_selectQueue)) {
			$exprops[] = "\0$cn\0_selectQueue";
		}
		if (null === $this->_groupBy) {
			$exprops[] = "\0$cn\0_groupBy";
		}
		if (!$this->_IsRowDataFound) {
			$exprops[] = "\0$cn\0_IsRowDataFound";
		}
		return array_diff(parent::__sleep(), $exprops);
	}
}
