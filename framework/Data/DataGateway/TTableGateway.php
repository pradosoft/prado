<?php

Prado::using('System.Data.DataGateway.TSqlCriteria');
Prado::using('System.Data.DataGateway.TDataGatewayCommand');

class TTableGateway extends TComponent
{
	private $_command;
	private $_connection;

	public function __construct($tableName,$connection)
	{
		$this->_connection=$connection;
		$this->setTableName($tableName);
	}

	protected function setTableName($tableName)
	{
		Prado::using('System.Data.Common.TDbMetaData');
		$meta = TDbMetaData::getMetaData($this->getDbConnection());
		$builder = $meta->createCommandBuilder($tableName);
		$this->_command = new TDataGatewayCommand($builder);
	}

	protected function getCommand()
	{
		return $this->_command;
	}

	/**
	 * @return TDbConnection database connection.
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * Execute arbituary sql command with binding parameters.
	 * @param string SQL query string.
	 * @param array binding parameters, positional or named.
	 * @return TDbDataReader query results.
	 */
	public function findBySql($sql, $parameters=array())
	{
		$args = func_num_args() > 1 ? array_slice(func_get_args(),1) : null;
		$criteria = $this->getCriteria($sql,$parameters, $args);
		return $this->getCommand()->findBySql($criteria);
	}

	/**
	 * Find one single record that matches the criteria.
	 *
	 * Usage:
	 * <code>
	 * $table->find('username = :name AND password = :pass',
	 * 					array(':name'=>$name, ':pass'=>$pass));
	 * $table->find('username = ? AND password = ?', array($name, $pass));
	 * $table->find('username = ? AND password = ?', $name, $pass);
	 * //$criteria is of TSqlCriteria
	 * $table->find($criteria); //the 2nd parameter for find() is ignored.
	 * </code>
	 *
	 * @param string|TSqlCriteria SQL condition or criteria object.
	 * @param mixed parameter values.
	 * @return array matching record object.
	 */
	public function find($criteria, $parameters=array())
	{
		$args = func_num_args() > 1 ? array_slice(func_get_args(),1) : null;
		$criteria = $this->getCriteria($criteria,$parameters, $args);
		return $this->getCommand()->find($criteria);
	}

	/**
	 * Accepts same parameters as find(), but returns TDbDataReader instead.
	 * @param string|TSqlCriteria SQL condition or criteria object.
	 * @param mixed parameter values.
	 * @return TDbDataReader matching records.
	 */
	public function findAll($criteria, $parameters=array())
	{
		$args = func_num_args() > 1 ? array_slice(func_get_args(),1) : null;
		$criteria = $this->getCriteria($criteria,$parameters, $args);
		return $this->getCommand()->findAll($criteria);
	}

	/**
	 * Find one record using only the primary key or composite primary keys. Usage:
	 *
	 * <code>
	 * $table->findByPk($primaryKey);
	 * $table->findByPk($key1, $key2, ...);
	 * $table->findByPk(array($key1,$key2,...));
	 * </code>
	 *
	 * @param mixed primary keys
	 * @return array matching record.
	 */
	public function findByPk($keys)
	{
		if(func_num_args() > 1)
			$keys = func_get_args();
		return $this->getCommand()->findByPk($keys);
	}

	/**
	 * Similar to findByPk(), but returns TDbDataReader instead.
	 *
	 * For scalar primary keys:
	 * <code>
	 * $table->findAllByPk($key1, $key2, ...);
	 * $table->findAllByPk(array($key1, $key2, ...));
	 * </code>
	 *
	 * For composite keys:
	 * <code>
	 * $table->findAllByPk(array($key1, $key2), array($key3, $key4), ...);
	 * $table->findAllByPk(array(array($key1, $key2), array($key3, $key4), ...));
	 * </code>
	 * @param mixed primary keys
	 * @return TDbDataReader data reader.
	 */
	public function findAllByPks($keys)
	{
		if(func_num_args() > 1)
			$keys = func_get_args();
		return $this->getCommand()->findAllByPk($keys);
	}

	/**
	 * Delete records from the table with condition given by $where and
	 * binding values specified by $parameter argument.
	 * This method uses additional arguments as $parameters. E.g.
	 * <code>
	 * $table->delete('age > ? AND location = ?', $age, $location);
	 * </code>
	 * @param string delete condition.
	 * @param array condition parameters.
	 * @return integer number of records deleted.
	 */
	public function deleteAll($criteria, $parameters=array())
	{
		$args = func_num_args() > 1 ? array_slice(func_get_args(),1) : null;
		$criteria = $this->getCriteria($criteria,$parameters, $args);
		return $this->getCommand()->delete($criteria);
	}

	/**
	 * Delete records by primary key. Usage:
	 *
	 * <code>
	 * $table->deleteByPk($primaryKey); //delete 1 record
	 * $table->deleteByPk($key1,$key2,...); //delete multiple records
	 * $table->deleteByPk(array($key1,$key2,...)); //delete multiple records
	 * </code>
	 *
	 * For composite primary keys (determined from the table definitions):
	 * <code>
	 * $table->deleteByPk(array($key1,$key2)); //delete 1 record
	 *
	 * //delete multiple records
	 * $table->deleteByPk(array($key1,$key2), array($key3,$key4),...);
	 *
	 * //delete multiple records
	 * $table->deleteByPk(array( array($key1,$key2), array($key3,$key4), .. ));
	 * </code>
	 *
	 * @param mixed primary key values.
	 * @return int number of records deleted.
	 */
	public function deleteByPk($keys)
	{
		if(func_num_args() > 1)
			$keys = func_get_args();
		return $this->getCommand()->deleteByPk($keys);
	}

	/**
	 * Find the number of records.
	 * @param string|TSqlCriteria SQL condition or criteria object.
	 * @param mixed parameter values.
	 * @return int number of records.
	 */
	public function count($criteria=null,$parameters=array())
	{
		$args = func_num_args() > 1 ? array_slice(func_get_args(),1) : null;
		if($criteria!==null)
			$criteria = $this->getCriteria($criteria,$parameters, $args);
		return $this->getCommand()->count($criteria);
	}

	/**
	 * Updates the table with new name-value pair $data. Each array key must
	 * correspond to a column name in the table. The update condition is
	 * specified by the $where argument and additional binding values can be
	 * specified using the $parameter argument.
	 * This method uses additional arguments as $parameters. E.g.
	 * <code>
	 * $gateway->update($data, 'age > ? AND location = ?', $age, $location);
	 * </code>
	 * @param array new record data.
	 * @param string update condition
	 * @param array additional binding name-value pairs.
	 * @return integer number of records updated.
	 */
	public function update($data, $criteria, $parameters=array())
	{
		$args = func_num_args() > 2 ? array_slice(func_get_args(),2) : null;
		$criteria = $this->getCriteria($criteria,$parameters, $args);
		return $this->getCommand()->update($data, $criteria);
	}

	/**
	 * Inserts a new record into the table. Each array key must
	 * correspond to a column name in the table unless a null value is permitted.
	 * @param array new record data.
	 * @return mixed last insert id if one column contains a serial or sequence,
	 * otherwise true if command executes successfully and affected 1 or more rows.
	 */
	public function insert($data)
	{
		return $this->getCommand()->insert($data);
	}

	/**
	 * @return mixed last insert id, null if none is found.
	 */
	public function getLastInsertId()
	{
		return $this->getCommand()->getLastInsertId();
	}

	/**
	 * Create a new TSqlCriteria object from a string $criteria. The $args
	 * are additional parameters and are used in place of the $parameters
	 * if $parameters is not an array and $args is an arrary.
	 * @param string|TSqlCriteria sql criteria
	 * @param mixed parameters passed by the user.
	 * @param array additional parameters obtained from function_get_args().
	 * @return TSqlCriteria criteria object.
	 */
	protected function getCriteria($criteria, $parameters, $args)
	{
		if(is_string($criteria))
		{
			$useArgs = !is_array($parameters) && is_array($args);
			return new TSqlCriteria($criteria,$useArgs ? $args : $parameters);
		}
		else if($criteria instanceof TSqlCriteria)
			return $criteria;
		else
			throw new TDbException('dbtablegateway_invalid_criteria');
	}

	/**
	 * Dynamic find method using parts of method name as search criteria.
	 * Method name starting with "findBy" only returns 1 record.
	 * Method name starting with "findAllBy" returns 0 or more records.
	 * Method name starting with "deleteBy" deletes records by the trail criteria.
	 * The condition is taken as part of the method name after "findBy", "findAllBy"
	 * or "deleteBy".
	 *
	 * The following are equivalent:
	 * <code>
	 * $table->findByName($name)
	 * $table->find('Name = ?', $name);
	 * </code>
	 * <code>
	 * $table->findByUsernameAndPassword($name,$pass); // OR may be used
	 * $table->findBy_Username_And_Password($name,$pass); // _OR_ may be used
	 * $table->find('Username = ? AND Password = ?', $name, $pass);
	 * </code>
	 * <code>
	 * $table->findAllByAge($age);
	 * $table->findAll('Age = ?', $age);
	 * </code>
	 * <code>
	 * $table->deleteAll('Name = ?', $name);
	 * $table->deleteByName($name);
	 * </code>
	 * @return mixed single record if method name starts with "findBy", 0 or more records
	 * if method name starts with "findAllBy"
	 */
	public function __call($method,$args)
	{
		$delete =false;
		if($findOne = substr(strtolower($method),0,6)==='findby')
			$condition = $method[6]==='_' ? substr($method,7) : substr($method,6);
		else if(substr(strtolower($method),0,9)==='findallby')
			$condition = $method[9]==='_' ? substr($method,10) : substr($method,9);
		else if($delete = substr(strtolower($method),0,8)==='deleteby')
			$condition = $method[8]==='_' ? substr($method,9) : substr($method,8);
		else
			return null;

		$criteria = $this->getCommand()->createCriteriaFromString($method, $condition, $args);
		if($delete)
			return $this->deleteAll($criteria);
		else
			return $findOne ? $this->find($criteria) : $this->findAll($criteria);
	}
}

?>