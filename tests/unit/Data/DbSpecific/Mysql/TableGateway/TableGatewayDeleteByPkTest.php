<?php

require_once(__DIR__ . '/BaseGateway.php');

class TableGatewayDeleteByPkTest extends TTableGatewayTestBase
{
	/**
	 * @agent these should stay as skipped until the framework bug is fixed
	 * @todo fix this framework bug in TTableGateway: deleteByPk() passes null to count(),
	 *       which requires a Countable|array argument as of PHP 8.
	 */
	public function test_delete_by_1_pk()
	{
		$this->add_record1();
		$pk = $this->get_record1()['username'];
		$deleted = $this->getGateway()->deleteByPk($pk);

		$this->assertEquals(1, $deleted);
	}

	public function test_delete_by_multiple_pk()
	{
		$this->add_record1();
		$this->add_record2();
		$pk1 = $this->get_record1()['username'];
		$pk2 = $this->get_record2()['username'];

		$deleted = $this->getGateway()->deleteByPk($pk1, $pk2);

		$this->assertEquals(2, $deleted);
	}

	public function test_delete_by_multiple_pk2()
	{
		$this->add_record1();
		$this->add_record2();
		$pk1 = $this->get_record1()['username'];
		$pk2 = $this->get_record2()['username'];

		$deleted = $this->getGateway()->deleteByPk([$pk1, $pk2]);

		$this->assertEquals(2, $deleted);
	}

	public function test_delete_by_multiple_pk3()
	{
		$this->add_record1();
		$this->add_record2();
		$pk1 = $this->get_record1()['username'];
		$pk2 = $this->get_record2()['username'];

		$deleted = $this->getGateway()->deleteByPk([[$pk1], [$pk2]]);

		$this->assertEquals(2, $deleted);
	}
}
