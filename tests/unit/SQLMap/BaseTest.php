<?php
require_once dirname(__FILE__).'/../phpunit2.php';

require_once(dirname(__FILE__).'/common.php');
require_once(SQLMAP_DIR.'/TSqlMapClient.php');

/**
 * @package System.DataAccess.SQLMap
 */
class BaseTest extends PHPUnit2_Framework_TestCase
{
	protected $sqlmap;
	protected $connection;
	private $mapper;
	private $config;

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
			$this->connection = new TAdodbConnection($this->config->getConnectionString());
		$this->connection->open();
		return $this->connection;
	}

	protected static $ScriptDirectory;

	/**
	 * Initialize an sqlMap
	 */
	protected function initSqlMap()
	{
		$filename = $this->config->getSqlMapConfigFile();
		$this->mapper = new TSQLMapClient;
		$this->sqlmap = $this->mapper->configure($filename,true);
		$this->sqlmap->getTypeHandlerFactory()->register('date', new TDateTimeHandler);
		$this->sqlmap->getDataProvider()->setConnectionString($this->config->getConnectionString());
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
		$this->assertEquals($account->getID(), 1);
		$this->assertEquals($account->getFirstName(), 'Joe');
		$this->assertEquals($account->getEmailAddress(), 'Joe.Dalton@somewhere.com');
	}

	/**
	 * Verify that the input account is equal to the account(id=6).
	 */
	protected function assertAccount6(Account $account)
	{
		$this->assertEquals($account->getID(), 6);
		$this->assertEquals($account->getFirstName(), 'Calamity');
		$this->assertEquals($account->getLastName(), 'Jane');
		$this->assertNull($account->getEmailAddress());
	}

	/**
	 * Verify that the input order is equal to the order(id=1).
	 */
	protected function assertOrder1(Order $order)
	{
		$date = @mktime(8,15,0,2,15,2003);
		
		$this->assertEquals((int)$order->getID(), 1);
		if($order->getDate() instanceof TDateTime)
			$this->assertEquals($order->getDate()->getTimestamp(), $date);
		else
			$this->fail();
		$this->assertEquals($order->getCardType(), 'VISA');
		$this->assertEquals($order->getCardNumber(), '999999999999');
		$this->assertEquals($order->getCardExpiry(), '05/03');
		$this->assertEquals($order->getStreet(), '11 This Street');
		$this->assertEquals($order->getProvince(), 'BC');
		$this->assertEquals($order->getPostalCode(), 'C4B 4F4');
	}

	function assertAccount1AsHashArray($account)
	{
		$this->assertEquals(1, (int)$account["Id"]);
		$this->assertEquals("Joe", $account["FirstName"]);
		$this->assertEquals("Dalton", $account["LastName"]);
		$this->assertEquals("Joe.Dalton@somewhere.com", $account["EmailAddress"]);
	}
	
	function AssertOrder1AsHashArray($order)
	{
		$date = @mktime(8,15,0,2,15,2003);

		$this->assertEquals(1, $order["Id"]);
		if($order['Date'] instanceof TDateTime)
			$this->assertEquals($date, $order["Date"]->getTimestamp());
		else
			$this->fail();
		$this->assertEquals("VISA", $order["CardType"]);
		$this->assertEquals("999999999999", $order["CardNumber"]);
		$this->assertEquals("05/03", $order["CardExpiry"]);
		$this->assertEquals("11 This Street", $order["Street"]);
		$this->assertEquals("Victoria", $order["City"]);
		$this->assertEquals("BC", $order["Province"]);
		$this->assertEquals("C4B 4F4", $order["PostalCode"]);
	}

}

class HundredsBool implements ITypeHandlerCallback
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

	public function createNewInstance()
	{
		throw new TDataMapperException('can not create');
	}
}

class OuiNonBool implements ITypeHandlerCallback
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

	public function createNewInstance()
	{
		throw new TDataMapperException('can not create');
	}
}

class TDateTimeHandler implements ITypeHandlerCallback
{
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

	public function createNewInstance()
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