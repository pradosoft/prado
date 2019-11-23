<?php

require_once(__DIR__ . '/BaseGateway.php');

class TestFindByPk extends BaseGateway
{
	public function test_one_key()
	{
		$this->add_record1();
		$id = $this->getGateway()->getLastInsertId();
		$result = $this->getGateway()->findByPk($id);

		$record1 = $this->get_record1();

		//clean and ignore some fields
		unset($result['id']);
		unset($result['field7_timestamp']);
		unset($record1['field7_timestamp']);
		$result['phone'] = (int) ($result['phone']);
		$result['field9_numeric'] = (float) ($result['field9_numeric']);

		$this->assertEquals($record1, $result);
	}

	public function test_composite_key()
	{
		$gateway = $this->getGateway2();

		$result = $gateway->findByPk(1, 1);
		$expect = ["department_id" => 1, "section_id" => 1, "order" => 0];
		$this->assertEquals($expect, $result);
	}

	public function test_find_all_keys()
	{
		$gateway = $this->getGateway2();

		$result = $gateway->findAllByPks([1, 1], [3, 13])->readAll();

		$expect = [
			["department_id" => 1, "section_id" => 1, "order" => 0],
			["department_id" => 3, "section_id" => 13, "order" => 0]];

		$this->assertEquals($expect, $result);
	}
}
