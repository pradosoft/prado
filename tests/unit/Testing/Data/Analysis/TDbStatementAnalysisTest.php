<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

Prado::using('System.Testing.Data.Analysis.TDbStatementAnalysis');

/**
 * @package System.Testing.Data.Analysis
 */
class TDbStatementAnalysisTest extends PHPUnit_Framework_TestCase
{
	private $analyser;

	public function setUp()
	{
		$this->analyser = new TDbStatementAnalysis();
	}

	public function tearDown()
	{
		$this->analyser = null;
	}

	public function testStaticClassificationAnalysis()
	{
		$parameter = new TDbStatementAnalysisParameter();
		self::assertEquals(TDbStatementClassification::UNKNOWN, TDbStatementAnalysis::doClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('SELECT 1');
		self::assertEquals(TDbStatementClassification::UNKNOWN, TDbStatementAnalysis::doClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('SELECT 1', TDbStatementClassification::SQL);
		self::assertEquals(TDbStatementClassification::SQL, TDbStatementAnalysis::doClassificationAnalysis($parameter));
	}

	public function testDriverName()
	{
		self::assertNull($this->analyser->getDriverName());

		$this->analyser->setDriverName('mysql');
		self::assertEquals('mysql', $this->analyser->getDriverName());

		$this->analyser->setDriverName('mssql');
		self::assertEquals('mssql', $this->analyser->getDriverName());

		$this->analyser->setDriverName(null);
		self::assertNull($this->analyser->getDriverName());
	}

	public function testClassificationAnalysis()
	{
		$parameter = new TDbStatementAnalysisParameter();
		self::assertEquals(TDbStatementClassification::UNKNOWN, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('SELECT 1');
		self::assertEquals(TDbStatementClassification::UNKNOWN, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('SELECT 1', TDbStatementClassification::SQL);
		self::assertEquals(TDbStatementClassification::SQL, $this->analyser->getClassificationAnalysis($parameter));
	}

}
?>