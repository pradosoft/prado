<?php
Prado::using('System.Data.*');
Prado::using('System.Data.Common.Mysql.TMysqlMetaData');

class CommandBuilderMysqlTest extends UnitTestCase
{
	function mysql_meta_data()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=tests;port=3307', 'test5','test5');
		return new TMysqlMetaData($conn);
	}

	function test()
	{
		$this->mysql_meta_data()->getTableInfo("tests.table1");
	}
}

?>