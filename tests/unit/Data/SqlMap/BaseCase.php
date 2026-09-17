<?php

namespace Prado\Test\Unit\Data\SqlMap;

require_once(__DIR__ . '/common.php');

use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;
use Prado\Data\SqlMap\TSqlMapManager;
use Prado\Test\Unit\Data\SqlMap\Domain\Account;
use Prado\Test\Unit\Data\SqlMap\Domain\Order;

class BaseCase extends \PHPUnit\Framework\TestCase
{
	protected static $sqlmap;
	protected static $connection;
	private static $mapper;
	private static $config;
	protected static $scriptDirectory;

	public function testCase1()
	{
		$this->assertTrue(true);
	}

	public function testCase2()
	{
		$this->assertTrue(true);
	}

	public function hasSupportFor($feature)
	{
		return self::$config->hasFeature($feature);
	}

	public static function setUpBeforeClass(): void
	{
		self::$config = BaseTestConfig::createConfigInstance();
		self::$scriptDirectory = self::$config->getScriptDir();
	}

    public static function tearDownAfterClass(): void
    {
		if (null !== self::$mapper) {
			self::$mapper->cacheConfiguration();
		}
	}

	public static function getConnection()
	{
		if (null === self::$connection) {
			self::$connection = self::$config->getConnection();
		}
		self::$connection->setActive(true);
		return self::$connection;
	}

	/**
	 * Initialize an sqlMap
	 */
	protected static function initSqlMap()
	{
		$manager = new TSqlMapManager(self::$config->getConnection());
		$manager->configureXml(self::$config->getSqlMapConfigFile());
		self::$sqlmap = $manager->getSqlMapGateway();
		$manager->TypeHandlers->registerTypeHandler(new TDateTimeHandler);
	}

	/**
	 * Run a sql batch for the datasource.
	 * @param mixed $script
	 */
	protected static function initScript($script)
	{
		$runner = self::$config->getScriptRunner();
		$runner->runScript(self::getConnection(), self::$scriptDirectory . $script);
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
