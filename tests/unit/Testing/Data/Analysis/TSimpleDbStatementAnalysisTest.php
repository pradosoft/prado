<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

Prado::using('System.Testing.Data.Analysis.TSimpleDbStatementAnalysis');

/**
 * @package System.Testing.Data.Analysis
 */
class TSimpleDbStatementAnalysisTest extends PHPUnit_Framework_TestCase
{
	private $analyser;

	public function setUp()
	{
		$this->analyser = new TSimpleDbStatementAnalysis();
	}

	public function tearDown()
	{
		$this->analyser = null;
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

	public function testClassificationAnalysisDDL()
	{
		$parameter = new TDbStatementAnalysisParameter('CREATE DATABASE `prado_system_data_sqlmap` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
		self::assertEquals(TDbStatementClassification::DDL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('DROP TABLE IF EXISTS `dynamicparametertest1`');
		self::assertEquals(TDbStatementClassification::DDL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('
			CREATE TABLE `dynamicparametertest1` (
				`testname` varchar(50) NOT NULL,
				`teststring` varchar(50) NOT NULL,
				`testinteger` int(11) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8
		');
		self::assertEquals(TDbStatementClassification::DDL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('
			CREATE TABLE `tab3`
			/* 1 multiline comment in one line */
			SELECT
				t1.*,
				t2.`foo` AS `bar`
			FROM
				# 1 single line shell comment
				`tab1` t1
				# 2 single line shell comment
				RIGHT JOIN `tab2` t2 ON (
					t2.tab1_id=t1.tab1_ref
					AND
					t2.`disabled` IS NULL
					AND
					(t2.`flags`&?)=?
				)
			-- 1 single line comment
			WHERE
			/*
				2 multiline comment
				in two lines
			*/
				t1.`idx`=?
				AND
			-- 2 single line comment
				t1.`disabled`IS NULL
			GROUP BY
				t2.`foo`
			HAVING
				t2.tab1_id=1,
				t2.disabled IS NULL
			ORDER BY
				`bar` DESC
		');
		self::assertEquals(TDbStatementClassification::DDL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('DROP TABLE `tab3`');
		self::assertEquals(TDbStatementClassification::DDL, $this->analyser->getClassificationAnalysis($parameter));
	}

	public function testClassificationAnalysisDML()
	{
		$parameter = new TDbStatementAnalysisParameter('TRUNCATE TABLE `dynamicparametertest1`');
		self::assertEquals(TDbStatementClassification::DML, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('
			UPDATE `dynamicparametertest1` SET
				`testinteger`=FLOOR(7 + (RAND() * 5))
			WHERE
				`testname` IN(
					SELECT `testname` FROM `dynamicparametertest2`
				)
		');
		self::assertEquals(TDbStatementClassification::DML, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('
			INSERT INTO `tab3`
			/* 1 multiline comment in one line */
			SELECT
				t1.*,
				t2.`foo` AS `bar`
			FROM
				# 1 single line shell comment
				`tab1` t1
				# 2 single line shell comment
				RIGHT JOIN `tab2` t2 ON (
					t2.tab1_id=t1.tab1_ref
					AND
					t2.`disabled` IS NULL
					AND
					(t2.`flags`&?)=?
				)
			-- 1 single line comment
			WHERE
			/*
				2 multiline comment
				in two lines
			*/
				t1.`idx`=?
				AND
			-- 2 single line comment
				t1.`disabled`IS NULL
			GROUP BY
				t2.`foo`
			HAVING
				t2.tab1_id=1,
				t2.disabled IS NULL
			ORDER BY
				`bar` DESC
		');
		self::assertEquals(TDbStatementClassification::DML, $this->analyser->getClassificationAnalysis($parameter));
	}

	public function testClassificationAnalysisSQL()
	{
		$parameter = new TDbStatementAnalysisParameter('
			/* 1 multiline comment in one line */
			SELECT
				t1.*,
				t2.`foo` AS `bar`
			FROM
				# 1 single line shell comment
				`tab1` t1
				# 2 single line shell comment
				RIGHT JOIN `tab2` t2 ON (
					t2.tab1_id=t1.tab1_ref
					AND
					t2.`disabled` IS NULL
					AND
					(t2.`flags`&?)=?
				)
			-- 1 single line comment
			WHERE
			/*
				2 multiline comment
				in two lines
			*/
				t1.`idx`=?
				AND
			-- 2 single line comment
				t1.`disabled`IS NULL
			GROUP BY
				t2.`foo`
			HAVING
				t2.tab1_id=1,
				t2.disabled IS NULL
			ORDER BY
				`bar` DESC
		');
		self::assertEquals(TDbStatementClassification::SQL, $this->analyser->getClassificationAnalysis($parameter));
	}

	public function testClassificationAnalysisDCL()
	{
		$parameter = new TDbStatementAnalysisParameter('
			GRANT ALL ON `prado_system_data_sqlmap`.*
				TO "prado_unitest"@"localhost"
				IDENTIFIED BY "prado_system_data_sqlmap_unitest"');
		self::assertEquals(TDbStatementClassification::DCL, $this->analyser->getClassificationAnalysis($parameter));
	}

	public function testClassificationAnalysisTCL()
	{
		$parameter = new TDbStatementAnalysisParameter('START TRANSACTION');
		self::assertEquals(TDbStatementClassification::TCL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('BEGIN');
		self::assertEquals(TDbStatementClassification::TCL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('COMMIT');
		self::assertEquals(TDbStatementClassification::TCL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('RELEASE SAVEPOINT');
		self::assertEquals(TDbStatementClassification::TCL, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('XA START');
		self::assertEquals(TDbStatementClassification::TCL, $this->analyser->getClassificationAnalysis($parameter));
	}

	public function testClassificationAnalysisUNKNOWN()
	{
		$parameter = new TDbStatementAnalysisParameter('CALL `sp_my_storedprocedure`("foobar")');
		self::assertEquals(TDbStatementClassification::UNKNOWN, $this->analyser->getClassificationAnalysis($parameter));
	}

	public function testClassificationAnalysisCONTEXT()
	{
		$parameter = new TDbStatementAnalysisParameter('SET NAMES "utf8"');
		self::assertEquals(TDbStatementClassification::CONTEXT, $this->analyser->getClassificationAnalysis($parameter));

		$parameter = new TDbStatementAnalysisParameter('USE `prado_system_data_sqlmap`');
		self::assertEquals(TDbStatementClassification::CONTEXT, $this->analyser->getClassificationAnalysis($parameter));
	}
}
?>