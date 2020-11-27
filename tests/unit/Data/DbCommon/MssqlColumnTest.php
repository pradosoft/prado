<?php

use Prado\Data\Common\Mssql\TMssqlMetaData;
use Prado\Data\DataGateway\TTableGateway;

class MssqlColumnTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!extension_loaded('mssql')) {
			$this->markTestSkipped(
				'The mssql extension is not available.'
			);
		}
	}

	public function get_conn()
	{
		return new TDbConnection('mssql:host=localhost\\sqlexpress', 'test', 'test01');
	}

	/**
	 * @return TMssqlMetaData
	 */
	public function meta_data()
	{
		return new TMssqlMetaData($this->get_conn());
	}

	public function test_insert()
	{
		$table = new TTableGateway('table1', $this->get_conn());
		$this->assertTrue(is_int($table->insert(['name' => 'cool'])));
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
