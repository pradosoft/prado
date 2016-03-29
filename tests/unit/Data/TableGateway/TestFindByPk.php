<?php

require_once(dirname(__FILE__).'/BaseGateway.php');

/**
 * @package System.Data.TableGateway
 */
class TestFindByPk extends BaseGateway
{
	function test_one_key()
	{
		$this->add_record1();
		$id = $this->getGateway()->getLastInsertId();
		$result = $this->getGateway()->findByPk($id);

		$record1 = $this->get_record1();

		//clean and ignore some fields
		unset($result['id']);
		unset($result['field7_timestamp']);
		unset($record1['field7_timestamp']);
		$result['phone'] = intval($result['phone']);
		$result['field9_numeric'] = floatval($result['field9_numeric']);

		$this->assertEquals($record1, $result);
	}

	function test_composite_key()
	{
		$gateway = $this->getGateway2();

		$result = $gateway->findByPk(1,1);
		$expect = array("department_id" => 1, "section_id" => 1, "order" =>  0);
		$this->assertEquals($expect, $result);
	}

	function test_find_all_keys()
	{
		$gateway = $this->getGateway2();

		$result = $gateway->findAllByPks(array(1,1), array(3,13))->readAll();

		$expect = array(
			array("department_id" => 1, "section_id" => 1, "order" =>  0),
			array("department_id" => 3, "section_id" => 13, "order" =>  0));

		$this->assertEquals($expect, $result);

	}
}