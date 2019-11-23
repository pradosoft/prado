<?php

require_once(__DIR__ . '/BaseGateway.php');

class TableGatewayDeleteByPkTest extends BaseGateway
{
	public function test_delete_by_1_pk()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$this->add_record1();
				$id = $this->getGateway()->getLastInsertId();
				$deleted = $this->getGateway()->deleteByPk($id);

				$this->assertEquals(1, $deleted);
		*/
	}

	public function test_delete_by_multiple_pk()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$this->add_record1();
				$id1 = $this->getGateway()->getLastInsertId();
				$this->add_record2();
				$id2 = $this->getGateway()->getLastInsertId();

				$deleted = $this->getGateway()->deleteByPk($id1, $id2);

				$this->assertEquals(2, $deleted);
		*/
	}

	public function test_delete_by_multiple_pk2()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$this->add_record1();
				$id1 = $this->getGateway()->getLastInsertId();
				$this->add_record2();
				$id2 = $this->getGateway()->getLastInsertId();

				$deleted = $this->getGateway()->deleteByPk(array($id1, $id2));

				$this->assertEquals(2, $deleted);
		*/
	}

	public function test_delete_by_multiple_pk3()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$this->add_record1();
				$id1 = $this->getGateway()->getLastInsertId();
				$this->add_record2();
				$id2 = $this->getGateway()->getLastInsertId();

				$deleted = $this->getGateway()->deleteByPk(array(array($id1), array($id2)));

				$this->assertEquals(2, $deleted);
		*/
	}
}
