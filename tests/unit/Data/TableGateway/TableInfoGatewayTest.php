<?php

namespace Prado\Test\Unit\Data\TableGateway;

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TTableGateway;

class TableInfoGatewayTest extends BaseGateway
{
	public function test_table_info()
	{
		$conn = $this->getGateway()->getDbConnection();
		$this->add_record1();
		$this->add_record2();
		$info = TDbMetaData::getInstance($conn)->getTableInfo('address');
		$table = new TTableGateway($info, $conn);
		$this->assertEquals(count($table->findAll()->readAll()), 2);
	}
}
