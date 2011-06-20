<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

Prado::using('System.Testing.Data.Analysis.TDbStatementAnalysis');

/**
 * @package System.Testing.Data.Analysis
 */
class TDbStatementAnalysisParameterTest extends PHPUnit_Framework_TestCase
{
	private $analyserParameter;

	public function setUp()
	{
		$this->analyserParameter = new TDbStatementAnalysisParameter();
	}

	public function tearDown()
	{
		$this->analyserParameter = null;
	}

	public function testConstruct() {
		$this->analyserParameter = new TDbStatementAnalysisParameter();
		self::assertInternalType('string', $this->analyserParameter->getStatement());
		self::assertEquals('', $this->analyserParameter->getStatement());
		self::assertEquals(TDbStatementClassification::UNKNOWN, $this->analyserParameter->getDefaultClassification());
		self::assertNull($this->analyserParameter->getDriverName());

		$this->analyserParameter = new TDbStatementAnalysisParameter('SELECT 1', TDbStatementClassification::SQL, 'mysql');
		self::assertInternalType('string', $this->analyserParameter->getStatement());
		self::assertEquals('SELECT 1', $this->analyserParameter->getStatement());
		self::assertEquals(TDbStatementClassification::SQL, $this->analyserParameter->getDefaultClassification());
		self::assertEquals('mysql', $this->analyserParameter->getDriverName());
	}

	public function testStatement() {
		self::assertInternalType('string', $this->analyserParameter->getStatement());
		self::assertEquals('', $this->analyserParameter->getStatement());

		$this->analyserParameter->setStatement('SELECT 1');
		self::assertInternalType('string', $this->analyserParameter->getStatement());
		self::assertEquals('SELECT 1', $this->analyserParameter->getStatement());

		$this->analyserParameter->setStatement(null);
		self::assertInternalType('string', $this->analyserParameter->getStatement());
		self::assertEquals('', $this->analyserParameter->getStatement());
	}

	public function testDriverName() {
		self::assertNull($this->analyserParameter->getDriverName());

		$this->analyserParameter->setDriverName('mysql');
		self::assertEquals('mysql', $this->analyserParameter->getDriverName());

		$this->analyserParameter->setDriverName('mssql');
		self::assertEquals('mssql', $this->analyserParameter->getDriverName());

		$this->analyserParameter->setDriverName(null);
		self::assertNull($this->analyserParameter->getDriverName());
	}

	public function testDefaultClassification() {
		self::assertEquals(TDbStatementClassification::UNKNOWN, $this->analyserParameter->getDefaultClassification());

		$this->analyserParameter->setDefaultClassification(TDbStatementClassification::SQL);
		self::assertEquals(TDbStatementClassification::SQL, $this->analyserParameter->getDefaultClassification());

		$this->analyserParameter->setDefaultClassification(TDbStatementClassification::DML);
		self::assertEquals(TDbStatementClassification::DML, $this->analyserParameter->getDefaultClassification());

		$this->analyserParameter->setDefaultClassification(null);
		self::assertEquals(TDbStatementClassification::UNKNOWN, $this->analyserParameter->getDefaultClassification());
	}
}
?>