<?php

Prado::using('System.Data.*');
Prado::using('System.Data.Common.Sqlite.TSqliteMetaData');
Prado::using('System.Data.DataGateway.TTableGateway');
class SqliteColumnTest extends UnitTestCase
{
	/**
	 * @return TSqliteMetaData
	 */
	function meta_data()
	{
		$conn = new TDbConnection('sqlite:c:/test.db');
		return new TSqliteMetaData($conn);
	}

	function test_it()
	{
		$table = $this->meta_data()->getTableInfo('foo');
		//var_dump($table);
	}

	function test_table()
	{
		$conn = new TDbConnection('sqlite:c:/test.db');
		$table = new TTableGateway('Accounts', $conn);
//		var_dump($table->findAll()->readAll());
	}
}
?>