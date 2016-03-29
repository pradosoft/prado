<?php


Prado::using('System.Data.*');
Prado::using('System.Data.SqlMap.*');

/**
 * @package System.Data.SqlMap
 */
class DynamicParameterTest extends PHPUnit_Framework_TestCase
{

	protected function getMysqlSqlMapManager()
	{
		static $conn;
		static $sqlMapManager;

		if(Prado::getApplication() === null)
			Prado::setApplication(new TApplication(dirname(__FILE__).'/app'));

		if($conn === null)
			$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');

		$conn->setActive(true);

		if($sqlMapManager === null)
		{
			$sqlMapManager = new TSqlMapManager($conn);
			$sqlMapManager->configureXml( dirname(__FILE__) . '/DynamicParameterTestMap.xml');
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

		$aParams = array(
			'tablename' => 'dynamicparametertest1',
			'testname'	=> 'dynamictable'
		);
		$value = $gateway->queryForObject('SelectDynamicComplex', $aParams);
		self::assertEquals('#dynamictableparametertest1$', $value);

		$aParams = array(
			'tablename' => 'dynamicparametertest2',
			'testname'	=> 'dynamictable'
		);
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
