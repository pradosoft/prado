<?php

require_once(dirname(__FILE__).'/BaseGatewayTest.php');

class CountTest extends BaseGatewayTest
{
	function test_simple_count()
	{
		$result = $this->getGateway2()->count();
		$this->assertEqual(44,$result);

		$result = $this->getGateway2()->count('department_id = ?', 1);
		$this->assertEqual(4, $result);
	}
}
?>