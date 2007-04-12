<?php

require_once(dirname(__FILE__).'/BaseGatewayTest.php');

class MagicCallTest extends BaseGatewayTest
{
	function test_magic_call()
	{
		$this->add_record1(); $this->add_record2();

		$result = $this->getGateway()->findByUsername("record2");
		$this->assertEqual($result['username'], 'record2');
	}

	function test_combined_and_or()
	{
		$this->add_record1(); $this->add_record2();

		$result = $this->getGateway()->findAllByUsername_OR_phone('Username', '45233')->readAll();
		$this->assertEqual(2, count($result));
	}

	function test_no_result()
	{
		$this->add_record1(); $this->add_record2();
		$result = $this->getGateway()->findAllByUsername_and_phone('Username', '45233')->readAll();

		$this->assertEqual(0, count($result));
	}
}
?>