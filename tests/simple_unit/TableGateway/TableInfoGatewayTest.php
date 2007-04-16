<?php

require_once(dirname(__FILE__).'/BaseGatewayTest.php');

class TableInfoGatewayTest extends BaseGatewayTest
{
	function test_table_info()
	{
		$conn = $this->getGateway()->getDbConnection();
		$this->add_record1();
		$this->add_record2();
		$info = TDbMetaData::getInstance($conn)->getTableInfo('address');
		$table = new TTableGateway($info, $conn);
		$this->assertEqual(count($table->findAll()->readAll()), 2);
	}
}
?>