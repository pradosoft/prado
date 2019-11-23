<?php

require_once(__DIR__ . '/BaseGateway.php');

class MagicCallTest extends BaseGateway
{
	public function test_magic_call()
	{
		$this->add_record1();
		$this->add_record2();

		$result = $this->getGateway()->findByUsername("record2");
		$this->assertEquals($result['username'], 'record2');
	}

	public function test_combined_and_or()
	{
		$this->add_record1();
		$this->add_record2();

		$result = $this->getGateway()->findAllByUsername_OR_phone('Username', '45233')->readAll();
		$this->assertEquals(2, count($result));
	}

	public function test_no_result()
	{
		$this->add_record1();
		$this->add_record2();
		$result = $this->getGateway()->findAllByUsername_and_phone('Username', '45233')->readAll();

		$this->assertEquals(0, count($result));
	}
}
