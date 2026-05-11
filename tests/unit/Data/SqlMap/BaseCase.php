<?php

require_once(__DIR__ . '/common.php');

use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;
use Prado\Data\SqlMap\TSqlMapManager;

class BaseCase extends PHPUnit\Framework\TestCase
{
	protected static $sqlmap;
	protected static $connection;
	protected static $mapper;
	protected static $config;
	protected static $scriptDirectory;

	/**
	 * Subclasses set this to a config class name (e.g. 'MySQLBaseTestConfig') to
	 * run the full SqlMap test suite against a different database driver.
	 * An empty string means "use BaseTestConfig::createConfigInstance()".
	 */
	protected static string $configClass = '';

	public function testCase1()
	{
		$this->assertTrue(true);
	}

	public function testCase2()
	{
		$this->assertTrue(true);
	}

	protected function skipIfUnavailable(): void
	{
		if (static::$config === null) {
			$this->markTestSkipped('Database connection unavailable for ' . static::class);
		}
	}

	public function hasSupportFor($feature)
	{
		if (static::$config === null) {
			return false;
		}
		return static::$config->hasFeature($feature);
	}

	protected function setUp(): void
	{
		$this->skipIfUnavailable();
	}

	public static function setUpBeforeClass(): void
	{
		if (static::$configClass !== '') {
			$cls = static::$configClass;
			try {
				static::$config = new $cls();
				// Verify connectivity; skip the whole class if DB is unavailable.
				static::$config->getConnection()->setActive(true);
				static::$config->getConnection()->setActive(false);
			} catch (\Exception $e) {
				static::$config = null;
			}
		} else {
			static::$config = BaseTestConfig::createConfigInstance();
		}
		if (static::$config !== null) {
			static::$scriptDirectory = static::$config->getScriptDir();
			// Bootstrap the database schema (creates tables if they don't exist yet).
			static::initSchema();
		}
	}

	/**
	 * Runs the driver's schema-creation script (DataBase.sql / database.sql) if present.
	 * This is a no-op for SQLiteBaseTestConfig which uses CopyFileScriptRunner.
	 * For MySQL, PostgreSQL, etc. it creates the SqlMap tables so TRUNCATE-based
	 * data-init scripts can execute successfully.
	 */
	protected static function initSchema(): void
	{
		if (static::$config === null) {
			return;
		}
		$dir = static::$config->getScriptDir();
		foreach (['DataBase.sql', 'database.sql', 'DBCreation.sql'] as $candidate) {
			$path = $dir . $candidate;
			if (file_exists($path)) {
				try {
					$runner = static::$config->getScriptRunner();
					$runner->runScript(static::getConnection(), $path);
				} catch (\Exception $e) {
					// Schema initialisation failed — treat DB as unavailable.
					static::$config = null;
				}
				return;
			}
		}
	}

    public static function tearDownAfterClass(): void
    {
		if (null !== static::$mapper) {
			static::$mapper->cacheConfiguration();
		}
	}

	public static function getConnection()
	{
		if (static::$config === null) {
			return null;
		}
		if (null === static::$connection) {
			static::$connection = static::$config->getConnection();
		}
		static::$connection->setActive(true);
		return static::$connection;
	}

	/**
	 * Initialize an sqlMap
	 */
	protected static function initSqlMap()
	{
		if (static::$config === null) {
			return;
		}
		$manager = new TSqlMapManager(static::$config->getConnection());
		$manager->configureXml(static::$config->getSqlMapConfigFile());
		static::$sqlmap = $manager->getSqlMapGateway();
		$manager->TypeHandlers->registerTypeHandler(new TDateTimeHandler);
	}

	/**
	 * Run a sql batch for the datasource.
	 * @param mixed $script
	 */
	protected static function initScript($script)
	{
		if (static::$config === null) {
			return;
		}
		$runner = static::$config->getScriptRunner();
		$runner->runScript(static::getConnection(), static::$scriptDirectory . $script);
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
	 * @param Account $account
	 */
	protected function assertAccount1(Account $account)
	{
		$this->assertSame($account->getID(), 1);
		$this->assertSame($account->getFirstName(), 'Joe');
		$this->assertSame($account->getEmailAddress(), 'Joe.Dalton@somewhere.com');
	}

	/**
	 * Verify that the input account is equal to the account(id=6).
	 * @param Account $account
	 */
	protected function assertAccount6(Account $account)
	{
		$this->assertSame($account->getID(), 6);
		$this->assertSame($account->getFirstName(), 'Calamity');
		$this->assertSame($account->getLastName(), 'Jane');
		$this->assertNull($account->getEmailAddress());
	}

	/**
	 * Verify that the input order is equal to the order(id=1).
	 * @param Order $order
	 */
	protected function assertOrder1(Order $order)
	{
		$date = @mktime(8, 15, 0, 2, 15, 2003);

		$this->assertSame((int) $order->getID(), 1);
		if ($order->getDate() instanceof TDateTime) {
			$this->assertSame($order->getDate()->getTimestamp(), $date);
		} else {
			$this->fail();
		}
		$this->assertSame($order->getCardType(), 'VISA');
		$this->assertSame($order->getCardNumber(), '999999999999');
		$this->assertSame($order->getCardExpiry(), '05/03');
		$this->assertSame($order->getStreet(), '11 This Street');
		$this->assertSame($order->getProvince(), 'BC');
		$this->assertSame($order->getPostalCode(), 'C4B 4F4');
	}

	public function assertAccount1AsHashArray($account)
	{
		$this->assertSame(1, (int) $account["Id"]);
		$this->assertSame("Joe", $account["FirstName"]);
		$this->assertSame("Dalton", $account["LastName"]);
		$this->assertSame("Joe.Dalton@somewhere.com", $account["EmailAddress"]);
	}

	public function AssertOrder1AsHashArray($order)
	{
		$date = @mktime(8, 15, 0, 2, 15, 2003);

		$this->assertSame(1, $order["Id"]);
		if ($order['Date'] instanceof TDateTime) {
			$this->assertSame($date, $order["Date"]->getTimestamp());
		} else {
			$this->fail();
		}
		$this->assertSame("VISA", $order["CardType"]);
		$this->assertSame("999999999999", $order["CardNumber"]);
		$this->assertSame("05/03", $order["CardExpiry"]);
		$this->assertSame("11 This Street", $order["Street"]);
		$this->assertSame("Victoria", $order["City"]);
		$this->assertSame("BC", $order["Province"]);
		$this->assertSame("C4B 4F4", $order["PostalCode"]);
	}
}

class HundredsBool extends TSqlMapTypeHandler
{
	public function getResult($string)
	{
		$value = (int) $string;
		if ($value == 100) {
			return true;
		}
		if ($value == 200) {
			return false;
		}
		//throw new Exception('unexpected value '.$value);
	}

	public function getParameter($parameter)
	{
		if ($parameter) {
			return 100;
		} else {
			return 200;
		}
	}

	public function createNewInstance($data = null)
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
		if ($string === self::YES) {
			return true;
		}
		if ($string === self::NO) {
			return false;
		}
		//throw new Exception('unexpected value '.$string);
	}

	public function getParameter($parameter)
	{
		if ($parameter) {
			return self::YES;
		} else {
			return self::NO;
		}
	}

	public function createNewInstance($data = null)
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
		if ($parameter instanceof TDateTime) {
			return $parameter->getTimestamp();
		} else {
			return $parameter;
		}
	}

	public function createNewInstance($data = null)
	{
		return new TDateTime;
	}
}

class TDateTime
{
	private $_datetime;

	public function __construct($datetime = null)
	{
		if (null !== $datetime) {
			$this->setDatetime($datetime);
		}
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
