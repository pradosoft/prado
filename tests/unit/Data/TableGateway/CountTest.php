<?php

require_once(__DIR__ . '/BaseGateway.php');

class CountTest extends BaseGateway
{
	public function test_simple_count()
	{
		$result = $this->getGateway2()->count();
		$this->assertEquals(5, $result);

		$result = $this->getGateway2()->count('department_id = ?', 1);
		$this->assertEquals(2, $result);
	}
}
