<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');
use Prado\Data\SqlMap\TSqlMapManager;
use Prado\Data\TDbConnection;
use Prado\Prado;
use Prado\TApplication;

class MysqlDynamicParameterTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_MYSQL;
	}

	protected function getTestTables(): array
	{
		return ['dynamicparametertest1'];
	}

	protected function setUp(): void
	{
		$this->setUpConnection();
	}


	//	------- Tests
	protected function getMysqlSqlMapManager()
	{
		static $conn;
		static $sqlMapManager;

		$xmlFile = __DIR__ . '/DynamicParameterTestMap.xml';
		if (!file_exists($xmlFile)) {
			$this->markTestSkipped('DynamicParameterTestMap.xml not found — create it to enable these tests.');
		}

		if (Prado::getApplication() === null) {
			Prado::setApplication(new TApplication(__DIR__ . '/app'));
		}

		if ($conn === null) {
			$conn = $this->setupPradoUnitConnection('prado_unitest');
			if (!($conn instanceof TDbConnection)) {
				$this->markTestSkipped('MySQL database not available.');
			}
		}

		if ($sqlMapManager === null) {
			$sqlMapManager = new TSqlMapManager($conn);
			$sqlMapManager->configureXml($xmlFile);
		}

		return $sqlMapManager;
	}

	public function testMysqlSelectStaticSql()
	{
		$mapper = $this->getMysqlSqlMapManager();
		$gateway = $mapper->getSqlmapGateway();

		$value = $gateway->queryForObject('SelectStaticSql1');
		self::assertEquals('staticsql1', $value);

		$value = $gateway->queryForObject('SelectStaticSql2');
		self::assertEquals('staticsql2', $value);
	}

	public function testMysqlSelectDynamicTable()
	{
		$mapper = $this->getMysqlSqlMapManager();
		$gateway = $mapper->getSqlmapGateway();

		$value = $gateway->queryForObject('SelectDynamicTable', 'dynamicparametertest1');
		self::assertEquals('dynamictableparametertest1', $value);

		$value = $gateway->queryForObject('SelectDynamicTable', 'dynamicparametertest2');
		self::assertEquals('dynamictableparametertest2', $value);
	}

	public function testMysqlSelectDynamicComplex()
	{
		$mapper = $this->getMysqlSqlMapManager();
		$gateway = $mapper->getSqlmapGateway();

		$aParams = [
			'tablename' => 'dynamicparametertest1',
			'testname' => 'dynamictable'
		];
		$value = $gateway->queryForObject('SelectDynamicComplex', $aParams);
		self::assertEquals('#dynamictableparametertest1$', $value);

		$aParams = [
			'tablename' => 'dynamicparametertest2',
			'testname' => 'dynamictable'
		];
		$value = $gateway->queryForObject('SelectDynamicComplex', $aParams);
		self::assertEquals('#dynamictableparametertest2$', $value);
	}

	public function testMysqlSelectNoDynamic()
	{
		$mapper = $this->getMysqlSqlMapManager();
		$gateway = $mapper->getSqlmapGateway();

		$value = $gateway->queryForObject('SelectNoDynamic', 'dynamictable');
		self::assertEquals('dynamictableparametertest1', $value);

		$value = $gateway->queryForObject('SelectNoDynamic', 'staticsql');
		self::assertEquals('staticsql1', $value);
	}

	/**
	 * Issue#209 test
	 */
	public function testMysqlInlineEscapeParam()
	{
		$mapper = $this->getMysqlSqlMapManager();
		$gateway = $mapper->getSqlmapGateway();

		$value = $gateway->queryForObject('SelectInlineEscapeParam', "'1234567*123$456789$012345' AS foobar");
		self::assertEquals('1234567*123$456789$012345', $value);

		$value = $gateway->queryForObject('SelectInlineEscapeParam', '"1234567*123$456789$012345" AS foobar');
		self::assertEquals('1234567*123$456789$012345', $value);
	}
}
