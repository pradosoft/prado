<?php

require_once(dirname(__FILE__).'/BaseGatewayTest.php');

class DeleteByPkTest extends BaseGatewayTest
{
	function test_delete_by_1_pk()
	{
		$this->add_record1();
		$id = $this->getGateway()->getLastInsertId();
		$deleted = $this->getGateway()->deleteByPk($id);

		$this->assertEqual(1, $deleted);
	}

	function test_delete_by_multiple_pk()
	{
		$this->add_record1();
		$id1 = $this->getGateway()->getLastInsertId();
		$this->add_record2();
		$id2 = $this->getGateway()->getLastInsertId();

		$deleted = $this->getGateway()->deleteByPk($id1, $id2);

		$this->assertEqual(2, $deleted);
	}

	function test_delete_by_multiple_pk2()
	{
		$this->add_record1();
		$id1 = $this->getGateway()->getLastInsertId();
		$this->add_record2();
		$id2 = $this->getGateway()->getLastInsertId();

		$deleted = $this->getGateway()->deleteByPk(array($id1, $id2));

		$this->assertEqual(2, $deleted);
	}

	function test_delete_by_multiple_pk3()
	{
		$this->add_record1();
		$id1 = $this->getGateway()->getLastInsertId();
		$this->add_record2();
		$id2 = $this->getGateway()->getLastInsertId();

		$deleted = $this->getGateway()->deleteByPk(array(array($id1), array($id2)));

		$this->assertEqual(2, $deleted);
	}
}
?>