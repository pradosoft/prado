<?php

require_once(dirname(__FILE__).'/common.php');
Prado::using('System.Data.SqlMap.TSqlMapManager');

/**
 * @package System.DataAccess.SQLMap
 */
class BaseCase extends UnitTestCase
{
	protected $sqlmap;
	protected $connection;
	private $mapper;
	private $config;
	protected $ScriptDirectory;

	public function testCase1()
	{
		$this->assertTrue(true);
	}

	public function testCase2()
	{
		$this->assertTrue(true);
	}

	public function __construct()
	{
		parent::__construct();
		$this->config = BaseTestConfig::createConfigInstance();
		$this->ScriptDirectory = $this->config->getScriptDir();
	}

	public function hasSupportFor($feature)
	{
		return $this->config->hasFeature($feature);
	}

	public function __destruct()
	{
		if(!is_null($this->mapper))
			$this->mapper->cacheConfiguration();
	}

	function getConnection()
	{
		if(is_null($this->connection))
			$this->connection = $this->config->getConnection();
		$this->connection->setActive(true);
		return $this->connection;
	}

	/**
	 * Initialize an sqlMap
	 */
	protected function initSqlMap()
	{
		$filename = $this->config->getSqlMapConfigFile();
		$conn = $this->config->getConnection();
		$manager = new TSqlMapManager($conn,$filename);
		$this->sqlmap = $manager->getSqlMapGateway();
		$manager->TypeHandlers->registerTypeHandler(new TDateTimeHandler);
	}

	/**
	 * Run a sql batch for the datasource.
	 */
	protected function initScript($script)
	{
		$runner = $this->config->getScriptRunner();
		$runner->runScript($this->getConnection(), $this->ScriptDirectory.$script);
	}

	/**
	 * Create a new account with id = 6
	 */
	protected function NewAccount6()
	{
		$account = new Account();
		$account->setID(6);
		$account->setFirstName('Calamity');
		$account->setLastName('Jane');
		$account->setEmailAddress('no_email@provided.com');
		return $account;
	}

	/**
	 * Verify that the input account is equal to the account(id=1).
	 */
	protected function assertAccount1(Account $account)
	{
		$this->assertIdentical($account->getID(), 1);
		$this->assertIdentical($account->getFirstName(), 'Joe');
		$this->assertIdentical($account->getEmailAddress(), 'Joe.Dalton@somewhere.com');
	}

	/**
	 * Verify that the input account is equal to the account(id=6).
	 */
	protected function assertAccount6(Account $account)
	{
		$this->assertIdentical($account->getID(), 6);
		$this->assertIdentical($account->getFirstName(), 'Calamity');
		$this->assertIdentical($account->getLastName(), 'Jane');
		$this->assertNull($account->getEmailAddress());
	}

	/**
	 * Verify that the input order is equal to the order(id=1).
	 */
	protected function assertOrder1(Order $order)
	{
		$date = @mktime(8,15,0,2,15,2003);

		$this->assertIdentical((int)$order->getID(), 1);
		if($order->getDate() instanceof TDateTime)
			$this->assertIdentical($order->getDate()->getTimestamp(), $date);
		else
			$this->fail();
		$this->assertIdentical($order->getCardType(), 'VISA');
		$this->assertIdentical($order->getCardNumber(), '999999999999');
		$this->assertIdentical($order->getCardExpiry(), '05/03');
		$this->assertIdentical($order->getStreet(), '11 This Street');
		$this->assertIdentical($order->getProvince(), 'BC');
		$this->assertIdentical($order->getPostalCode(), 'C4B 4F4');
	}

	function assertAccount1AsHashArray($account)
	{
		$this->assertIdentical(1, (int)$account["Id"]);
		$this->assertIdentical("Joe", $account["FirstName"]);
		$this->assertIdentical("Dalton", $account["LastName"]);
		$this->assertIdentical("Joe.Dalton@somewhere.com", $account["EmailAddress"]);
	}

	function AssertOrder1AsHashArray($order)
	{
		$date = @mktime(8,15,0,2,15,2003);

		$this->assertIdentical(1, $order["Id"]);
		if($order['Date'] instanceof TDateTime)
			$this->assertIdentical($date, $order["Date"]->getTimestamp());
		else
			$this->fail();
		$this->assertIdentical("VISA", $order["CardType"]);
		$this->assertIdentical("999999999999", $order["CardNumber"]);
		$this->assertIdentical("05/03", $order["CardExpiry"]);
		$this->assertIdentical("11 This Street", $order["Street"]);
		$this->assertIdentical("Victoria", $order["City"]);
		$this->assertIdentical("BC", $order["Province"]);
		$this->assertIdentical("C4B 4F4", $order["PostalCode"]);
	}

}

class HundredsBool extends TSqlMapTypeHandler
{
	public function getResult($string)
	{
		$value = intval($string);
		if($value == 100)
			return true;
		if($value == 200)
			return false;
		//throw new Exception('unexpected value '.$value);
	}

	public function getParameter($parameter)
	{
		if($parameter)
			return 100;
		else
			return 200;
	}

	public function createNewInstance($data=null)
	{
		throw new TDataMapperException('can not create');
	}
}

class OuiNonBool extends TSqlMapTypeHandler
{
	const YES = "Oui";
	const NO = "Non";

	public function getResult($string)
	{
		if($string === self::YES)
			return true;
		if($string === self::NO)
			return false;
		//throw new Exception('unexpected value '.$string);
	}

	public function getParameter($parameter)
	{
		if($parameter)
			return self::YES;
		else
			return self::NO;
	}

	public function createNewInstance($data=null)
	{
		throw new TDataMapperException('can not create');
	}
}

class TDateTimeHandler extends TSqlMapTypeHandler
{
	public function getType()
	{
		return 'date';
	}

	public function getResult($string)
	{
		$time = new TDateTime($string);
		return $time;
	}

	public function getParameter($parameter)
	{
		if($parameter instanceof TDateTime)
			return $parameter->getTimestamp();
		else
			return $parameter;
	}

	public function createNewInstance($data=null)
	{
		return new TDateTime;
	}
}

class TDateTime
{
	private $_datetime;

	public function __construct($datetime=null)
	{
		if(!is_null($datetime))
			$this->setDatetime($datetime);
	}

	public function getTimestamp()
	{
		return strtotime($this->getDatetime());
	}

	public function getDateTime()
	{
		return $this->_datetime;
	}

	public function setDateTime($value)
	{
		$this->_datetime = $value;
	}
}

?>