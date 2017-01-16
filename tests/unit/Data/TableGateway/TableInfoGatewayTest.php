<?php

require_once(dirname(__FILE__).'/BaseGateway.php');

/**
 * @package System.Data.TableGateway
 */
class TableInfoGatewayTest extends BaseGateway
{
	function test_table_info()
	{
		$conn = $this->getGateway()->getDbConnection();
		$this->add_record1();
		$this->add_record2();
		$info = TDbMetaData::getInstance($conn)->getTableInfo('address');
		$table = new TTableGateway($info, $conn);
		$this->assertEquals(count($table->findAll()->readAll()), 2);
	}
}