<?php
/**
 * TActiveRecord class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord
 */

Prado::using('System.Data.ActiveRecord.TActiveRecordManager');
Prado::using('System.Data.ActiveRecord.TActiveRecordCriteria');

/**
 * Base class for active records.
 *
 * An active record creates an object that wraps a row in a database table
 * or view, encapsulates the database access, and adds domain logic on that data.
 *
 * The essence of an Active Record is an object model of the
 * domain (e.g. products, items) that incorporates both behavior and
 * data in which the classes match very closely the record structure of an
 * underlying database. Each Active Record is responsible for saving and
 * loading to the database and also for any domain logic that acts on the data.
 *
 * The Active Record provides methods that do the following:
 *  1. Construct an instance of the Active Record from a SQL result set row.
 *  2. Construct a new instance for later insertion into the table.
 *  3. Finder methods to wrap commonly used SQL queries and return Active Record objects.
 *  4. Update the database and insert into it the data in the Active Record.
 *
 * Example:
 * <code>
 * class UserRecord extends TActiveRecord
 * {
 *     const TABLE='users'; //optional table name.
 *
 *     public $username; //corresponds to the fieldname in the table
 *     public $email;
 *
 *     //returns active record finder instance
 *     public static function finder($className=__CLASS__)
 *     {
 *         return parent::finder($className);
 *     }
 * }
 *
 * //create a connection and give it to the ActiveRecord manager.
 * $dsn = 'pgsql:host=localhost;dbname=test';
 * $conn = new TDbConnection($dsn, 'dbuser','dbpass');
 * TActiveRecordManager::getInstance()->setDbConnection($conn);
 *
 * //load the user record with username (primary key) 'admin'.
 * $user = UserRecord::finder()->findByPk('admin');
 * $user->email = 'admin@example.org';
 * $user->save(); //update the 'admin' record.
 * </code>
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
abstract class TActiveRecord extends TComponent
{
	/**
	 * @var boolean true if this class is read only.
	 */
	private $_readOnly=false;

	/**
	 * @var TDbConnection database connection object.
	 */
	private $_connection;

	/**
	 * Prevent __call() method creating __sleep() when serializing.
	 */
	public function __sleep()
	{
		return array_keys(get_object_vars($this));
	}

	/**
	 * Prevent __call() method creating __wake() when unserializing.
	 */
	public function __wake(){}

	/**
	 * Create a new instance of an active record with given $data. The record
	 * can be saved to the database specified by the $connection object.
	 *
	 * @param array optional name value pair record data.
	 * @param TDbConnection optional database connection this object record use.
	 */
	public function __construct($data=array(), $connection=null)
	{
		$this->copyFrom($data);
		if($connection!==null)
			$this->_connection=$connection;
	}

	/**
	 * Copies data from an array or another object.
	 * @throws TActiveRecordException if data is not array or not object.
	 * @return TActiveRecord current instance.
	 */
	public function copyFrom($data)
	{
		$data = is_object($data) ? get_object_vars($data) : $data;
		if(!is_array($data))
			throw new TActiveRecordException('ar_must_copy_from_array_or_object', get_class($this));
		foreach($data as $name=>$value)
				$this->$name = $value;
		return $this;
	}

	/**
	 * Gets the current Db connection, the connection object is obtained from
	 * the TActiveRecordManager if connection is currently null.
	 * @return TDbConnection current db connection for this object.
	 */
	public function getDbConnection()
	{
		if($this->_connection===null)
			$this->_connection=self::getRecordManager()->getDbConnection();
		if($this->_connection===null) //check it
			throw new TActiveRecordException('ar_invalid_db_connection',get_class($this));
		return $this->_connection;
	}

	/**
	 * @param TDbConnection db connection object for this record.
	 */
	public function setDbConnection($connection)
	{
		$this->_connection=$connection;
	}

	/**
	 * Returns the instance of a active record finder for a particular class.
	 * @param string active record class name.
	 * @return TActiveRecord active record finder instance.
	 * @throws TActiveRecordException if class name equals 'TActiveRecord'.
	 */
	public static function finder($className=__CLASS__)
	{
		if($className==='TActiveRecord')
			throw new TActiveRecordException('ar_invalid_finder_class_name');

		static $finders = array();
		if(!isset($finders[$className]))
		{
			$f = Prado::createComponent($className);
			$f->_readOnly=true;
			$finders[$className]=$f;
		}
		return $finders[$className];
	}

	/**
	 * Gets the record manager for this object, the default is to call
	 * TActiveRecordManager::getInstance().
	 * @return TActiveRecordManager default active record manager.
	 */
	public function getRecordManager()
	{
		return TActiveRecordManager::getInstance();
	}

	/**
	 * Saves the current record to the database, insert or update is automatically determined.
	 * @return boolean true if record was saved successfully, false otherwise.
	 */
	public function save()
	{
		return $this->commitChanges();
	}

	/**
	 * Commit changes to the record, may insert, update or delete depending
	 * on the record state given in TObjectStateRegistery.
	 * @return boolean true if changes were made.
	 */
	protected function commitChanges()
	{
		$registry = $this->getRecordManager()->getObjectStateRegistry();
		$gateway = $this->getRecordManager()->getRecordGateway();
		if(!$this->_readOnly)
			$this->_readOnly = $gateway->getMetaData($this)->getIsView();
		if($this->_readOnly)
			throw new TActiveRecordException('ar_readonly_exception',get_class($this));
		return $registry->commit($this,$gateway);
	}

	/**
	 * Deletes the current record from the database. Once deleted, this object
	 * can not be saved again in the same instance.
	 * @return boolean true if the record was deleted successfully, false otherwise.
	 */
	public function delete()
	{
		$registry = $this->getRecordManager()->getObjectStateRegistry();
		$registry->registerRemoved($this);
		return $this->commitChanges();
	}

	/**
	 * Delete records by primary key. Usage:
	 *
	 * <code>
	 * $finder->deleteByPk($primaryKey); //delete 1 record
	 * $finder->deleteByPk($key1,$key2,...); //delete multiple records
	 * $finder->deleteByPk(array($key1,$key2,...)); //delete multiple records
	 * </code>
	 *
	 * For composite primary keys (determined from the table definitions):
	 * <code>
	 * $finder->deleteByPk(array($key1,$key2)); //delete 1 record
	 *
	 * //delete multiple records
	 * $finder->deleteByPk(array($key1,$key2), array($key3,$key4),...);
	 *
	 * //delete multiple records
	 * $finder->deleteByPk(array( array($key1,$key2), array($key3,$key4), .. ));
	 * </code>
	 *
	 * @param mixed primary key values.
	 * @return int number of records deleted.
	 */
	public function deleteByPk($keys)
	{
		if(func_num_args() > 1)
			$keys = func_get_args();
		$gateway = $this->getRecordManager()->getRecordGateway();
		return $gateway->deleteRecordsByPk($this,(array)$keys);
	}


	/**
	 * Delete multiple records using a criteria.
	 * @param string|TActiveRecordCriteria SQL condition or criteria object.
	 * @param mixed parameter values.
	 * @return int number of records deleted.
	 */
	public function deleteAll($criteria, $parameters=array())
	{
		if(is_string($criteria))
		{
			if(!is_array($parameters) && func_num_args() > 1)
			{
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$criteria=new TActiveRecordCriteria($criteria,$parameters);
		}
		$gateway = $this->getRecordManager()->getRecordGateway();
		return $gateway->deleteRecordsByCriteria($this, $criteria);
	}

	/**
	 * Populate the record with data, registers the object as clean.
	 * @param string new record name
	 * @param array name value pair record data
	 * @return TActiveRecord object record, null if data is empty.
	 */
	protected function populateObject($type, $data)
	{
		if(empty($data)) return null;
		$registry = $this->getRecordManager()->getObjectStateRegistry();

		//try the cache (the cache object must be clean)
		if(!is_null($obj = $registry->getCachedInstance($data)))
			return $obj;

		//create and populate the object
		$obj = Prado::createComponent($type);
		foreach($data as $name => $value)
			$obj->{$name} = $value;

		$gateway = $this->getRecordManager()->getRecordGateway();
		$obj->_readOnly = $gateway->getMetaData($this)->getIsView();

		//cache it
		return $registry->addCachedInstance($data,$obj);
	}

	/**
	 * Find one single record that matches the criteria.
	 *
	 * Usage:
	 * <code>
	 * $finder->find('username = :name AND password = :pass',
	 * 					array(':name'=>$name, ':pass'=>$pass));
	 * $finder->find('username = ? AND password = ?', array($name, $pass));
	 * $finder->find('username = ? AND password = ?', $name, $pass);
	 * //$criteria is of TActiveRecordCriteria
	 * $finder->find($criteria); //the 2nd parameter for find() is ignored.
	 * </code>
	 *
	 * @param string|TActiveRecordCriteria SQL condition or criteria object.
	 * @param mixed parameter values.
	 * @return TActiveRecord matching record object.
	 */
	public function find($criteria,$parameters=array())
	{
		if(is_string($criteria))
		{
			if(!is_array($parameters) && func_num_args() > 1)
			{
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$criteria=new TActiveRecordCriteria($criteria,$parameters);
		}
		$gateway = $this->getRecordManager()->getRecordGateway();
		$data = $gateway->findRecordsByCriteria($this,$criteria);
		return $this->populateObject(get_class($this), $data);
	}

	/**
	 * Same as find() but returns an array of objects.
	 *
	 * @param string|TActiveRecordCriteria SQL condition or criteria object.
	 * @param mixed parameter values.
	 * @return array matching record objects
	 */
	public function findAll($criteria=null,$parameters=array())
	{
		if(is_string($criteria))
		{
			if(!is_array($parameters) && func_num_args() > 1)
			{
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$criteria=new TActiveRecordCriteria($criteria,$parameters);
		}
		$gateway = $this->getRecordManager()->getRecordGateway();
		$results = array();
		$class = get_class($this);
		foreach($gateway->findRecordsByCriteria($this,$criteria,true)  as $data)
			$results[] = $this->populateObject($class,$data);
		return $results;
	}

	/**
	 * Find one record using only the primary key or composite primary keys. Usage:
	 *
	 * <code>
	 * $finder->findByPk($primaryKey);
	 * $finder->findByPk($key1, $key2, ...);
	 * $finder->findByPk(array($key1,$key2,...));
	 * </code>
	 *
	 * @param mixed primary keys
	 * @return TActiveRecord
	 */
	public function findByPk($keys)
	{
		if(func_num_args() > 1)
			$keys = func_get_args();
		$gateway = $this->getRecordManager()->getRecordGateway();
		$data = $gateway->findRecordByPK($this,$keys);
		return $this->populateObject(get_class($this), $data);
	}

	/**
	 * Find multiple records matching a list of primary or composite keys.
	 *
	 * For scalar primary keys:
	 * <code>
	 * $finder->findAllByPk($key1, $key2, ...);
	 * $finder->findAllByPk(array($key1, $key2, ...));
	 * </code>
	 *
	 * For composite keys:
	 * <code>
	 * $finder->findAllByPk(array($key1, $key2), array($key3, $key4), ...);
	 * $finder->findAllByPk(array(array($key1, $key2), array($key3, $key4), ...));
	 * </code>
	 * @param mixed primary keys
	 * @return array matching ActiveRecords
	 */
	public function findAllByPks($keys)
	{
		if(func_num_args() > 1)
			$keys = func_get_args();
		$gateway = $this->getRecordManager()->getRecordGateway();
		$results = array();
		$class = get_class($this);
		foreach($gateway->findRecordsByPks($this,(array)$keys) as $data)
			$results[] = $this->populateObject($class,$data);
		return $results;
	}

	/**
	 * Find records using full SQL, returns corresponding record object.
	 * The names of the column retrieved must be defined in your Active Record
	 * class.
	 * @param string select SQL
	 * @param array $parameters
	 * @return array matching active records.
	 */
	public function findBySql($sql,$parameters=array())
	{
		if(!is_array($parameters) && func_num_args() > 1)
		{
			$parameters = func_get_args();
			array_shift($parameters);
		}
		$gateway = $this->getRecordManager()->getRecordGateway();
		$data = $gateway->findRecordsBySql($this,$sql,$parameters);
		$results = array();
		$class = get_class($this);
		foreach($gateway->findRecordsBySql($this,$sql,$parameters) as $data)
			$results[] = $this->populateObject($class,$data);
		return $results;
	}

	/**
	 * Find the number of records.
	 * @param string|TActiveRecordCriteria SQL condition or criteria object.
	 * @param mixed parameter values.
	 * @return int number of records.
	 */
	public function count($criteria=null,$parameters=array())
	{
		if(is_string($criteria))
		{
			if(!is_array($parameters) && func_num_args() > 1)
			{
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$criteria=new TActiveRecordCriteria($criteria,$parameters);
		}
		$gateway = $this->getRecordManager()->getRecordGateway();
		return $gateway->countRecords($this,$criteria);
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
	 * $finder->findByName($name)
	 * $finder->find('Name = ?', $name);
	 * </code>
	 * <code>
	 * $finder->findByUsernameAndPassword($name,$pass); // OR may be used
	 * $finder->findBy_Username_And_Password($name,$pass); // _OR_ may be used
	 * $finder->find('Username = ? AND Password = ?', $name, $pass);
	 * </code>
	 * <code>
	 * $finder->findAllByAge($age);
	 * $finder->findAll('Age = ?', $age);
	 * </code>
	 * <code>
	 * $finder->deleteAll('Name = ?', $name);
	 * $finder->deleteByName($name);
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
			return null;//throw new TActiveRecordException('ar_invalid_finder_method',$method);

		$criteria = $this->createCriteriaFromString($method, $condition, $args);
		if($delete)
			return $this->deleteAll($criteria);
		else
			return $findOne ? $this->find($criteria) : $this->findAll($criteria);
	}

	/**
	 * @param string __call method name
	 * @param string criteria conditions
	 * @param array method arguments
	 * @return TActiveRecordCriteria criteria created from the method name and its arguments.
	 */
	private function createCriteriaFromString($method, $condition, $args)
	{
		$fields = $this->extractMatchingConditions($method, $condition);
		$args=count($args) === 1 && is_array($args[0]) ? $args[0] : $args;
		if(count($fields)>count($args))
		{
			throw new TActiveRecordException('ar_mismatch_args_exception',
				$method,count($fields),count($args));
		}
		return new TActiveRecordCriteria(implode(' ',$fields),$args);
	}

	/**
	 * Calculates the AND/OR condition from dynamic method substrings using
	 * table meta data, allows for any AND-OR combinations.
	 * @param string dynamic method name
	 * @param string dynamic method search criteria
	 * @return array search condition substrings
	 */
	private function extractMatchingConditions($method, $condition)
	{
		$meta = $this->getRecordManager()->getRecordGateway()->getMetaData($this);
		$search = implode('|', $meta->getColumnNames());
		$regexp = '/('.$search.')(and|_and_|or|_or_)?/i';
		$matches = array();
		if(!preg_match_all($regexp, strtolower($condition), $matches,PREG_SET_ORDER))
		{
			throw new TActiveRecordException('ar_mismatch_column_names',
				$method, implode(', ', $meta->getColumnNames()), $meta->getTableName());
		}
		$fields = array();
		foreach($matches as $match)
		{
			$column = $meta->getColumn($match[1]);
			$sql = $column->getName() . ' = ? ';
			if(count($match) > 2)
				$sql .= strtoupper(str_replace('_', '', $match[2]));
			$fields[] = $sql;
		}
		return $fields;
	}
}
?>