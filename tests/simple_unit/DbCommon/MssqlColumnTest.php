<?php

Prado::using('System.Data.*');
Prado::using('System.Data.Common.Mssql.TMssqlMetaData');
Prado::using('System.Data.DataGateway.TTableGateway');

class MssqlColumnTest extends UnitTestCase
{
	function get_conn()
	{
		return new TDbConnection('mssql:host=localhost\\sqlexpress', 'test', 'test01');
	}

	/**
	 * @return TMssqlMetaData
	 */
	function meta_data()
	{
		return new TMssqlMetaData($this->get_conn());
	}

	function test_insert()
	{
		$table = new TTableGateway('table1', $this->get_conn());
		$this->assertTrue(is_int($table->insert(array('name'=>'cool'))));
	}

/*	function test_meta()
	{
		$result = $this->meta_data()->getTableInfo("bar");
		var_dump($result);
	}
*/
	/*function test_insert()
	{
		$table = new TTableGateway('table1', $this->get_conn());
		//var_dump($table->insert(array('name'=>'cool')));
		//var_dump($table->getLastInsertId());
		$criteria = new TSqlCriteria();
		$criteria->Limit = 5;
		$criteria->Offset = 2;

		$result = $table->findAll($criteria)->readAll();
		var_dump($result);
	}*/
}

?>