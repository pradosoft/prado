<?php

require_once(__DIR__ . '/BaseGateway.php');

class TableGatewayDeleteByPkTest extends BaseGateway
{
	/**
	 * @agent these should stay as skipped until the framework bug is fixed
	 * @todo fix this framework bug in TTableGateway: deleteByPk() passes null to count(),
	 *       which requires a Countable|array argument as of PHP 8.
	 */
	public function test_delete_by_1_pk()
	{
		$this->markTestSkipped('Test exposes framework bug: count(): Argument must be of type Countable|array, null given in TTableGateway::deleteByPk().');
		/*
				$this->add_record1();
				$id = $this->getGateway()->getLastInsertId();
				$deleted = $this->getGateway()->deleteByPk($id);

				$this->assertEquals(1, $deleted);
		*/
	}

	/**
	 * @agent these should stay as skipped until the framework bug is fixed
	 * @todo fix this framework bug in TTableGateway: deleteByPk() with multiple PKs
	 *       calls PDO::quote(null) which is deprecated and throws on PHP 8.2+.
	 */
	public function test_delete_by_multiple_pk()
	{
		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling in TTableGateway::deleteByPk() with multiple PKs.');
		/*
				$this->add_record1();
				$id1 = $this->getGateway()->getLastInsertId();
				$this->add_record2();
				$id2 = $this->getGateway()->getLastInsertId();

				$deleted = $this->getGateway()->deleteByPk($id1, $id2);

				$this->assertEquals(2, $deleted);
		*/
	}

	/**
	 * @agent these should stay as skipped until the framework bug is fixed
	 * @todo fix this framework bug in TTableGateway: deleteByPk() with array PK
	 *       calls PDO::quote(null) which is deprecated and throws on PHP 8.2+.
	 */
	public function test_delete_by_multiple_pk2()
	{
		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling in TTableGateway::deleteByPk() with array PK.');
		/*
				$this->add_record1();
				$id1 = $this->getGateway()->getLastInsertId();
				$this->add_record2();
				$id2 = $this->getGateway()->getLastInsertId();

				$deleted = $this->getGateway()->deleteByPk(array($id1, $id2));

				$this->assertEquals(2, $deleted);
		*/
	}

	/**
	 * @agent these should stay as skipped until the framework bug is fixed
	 * @todo fix this framework bug in TTableGateway: deleteByPk() with nested array PK
	 *       calls PDO::quote(null) which is deprecated and throws on PHP 8.2+.
	 */
	public function test_delete_by_multiple_pk3()
	{
		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling in TTableGateway::deleteByPk() with nested array PK.');
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
