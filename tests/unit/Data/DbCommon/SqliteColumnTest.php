<?php

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\DataGateway\TTableGateway;

class SqliteColumnTest extends PHPUnit\Framework\TestCase
{
	/**
	 * @return TSqliteMetaData
	 */
	public function meta_data()
	{
		$conn = new TDbConnection('sqlite:c:/test.db');
		return new TSqliteMetaData($conn);
	}

	public function test_it()
	{
		//$table = $this->meta_data()->getTableInfo('foo');
		//var_dump($table);
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function test_table()
	{
		$conn = new TDbConnection('sqlite:c:/test.db');
		//$table = new TTableGateway('Accounts', $conn);
//		var_dump($table->findAll()->readAll());
		throw new PHPUnit\Framework\IncompleteTestError();
	}
}
