<?php

require_once(__DIR__ . '/../../PradoUnit.php');

use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;

class TDataGatewayEventParameterTest extends PHPUnit\Framework\TestCase
{
	// -------  TDataGatewayEventParameter  -------

	public function test_constructor_stores_command_and_criteria()
	{
		$command = new stdClass();
		$command->name = 'cmd';
		$criteria = new stdClass();
		$criteria->where = '1=1';

		$param = new TDataGatewayEventParameter($command, $criteria);

		$this->assertSame($command, $param->getCommand());
		$this->assertSame($criteria, $param->getCriteria());
	}

	public function test_command_and_criteria_are_read_only()
	{
		$command = 'command-value';
		$criteria = 'criteria-value';
		$param = new TDataGatewayEventParameter($command, $criteria);

		$this->assertSame('command-value', $param->getCommand());
		$this->assertSame('criteria-value', $param->getCriteria());

		// No setters exist — confirm by checking canSetProperty returns false
		$this->assertFalse($param->canSetProperty('Command'));
		$this->assertFalse($param->canSetProperty('Criteria'));
	}

	public function test_constructor_accepts_null_values()
	{
		$param = new TDataGatewayEventParameter(null, null);
		$this->assertNull($param->getCommand());
		$this->assertNull($param->getCriteria());
	}

	public function test_is_teventparameter()
	{
		$param = new TDataGatewayEventParameter('cmd', 'crit');
		$this->assertInstanceOf(\Prado\TEventParameter::class, $param);
	}

	// -------  TDataGatewayResultEventParameter  -------

	public function test_result_constructor_stores_command_and_result()
	{
		$command = new stdClass();
		$result = ['row1', 'row2'];

		$param = new TDataGatewayResultEventParameter($command, $result);

		$this->assertSame($command, $param->getCommand());
		$this->assertSame($result, $param->getResult());
	}

	public function test_result_set_result_mutates()
	{
		$param = new TDataGatewayResultEventParameter('cmd', 'original');
		$this->assertSame('original', $param->getResult());

		$param->setResult('modified');
		$this->assertSame('modified', $param->getResult());
	}

	public function test_result_command_is_read_only()
	{
		$param = new TDataGatewayResultEventParameter('cmd', 'res');
		$this->assertFalse($param->canSetProperty('Command'));
	}

	public function test_result_accepts_null_values()
	{
		$param = new TDataGatewayResultEventParameter(null, null);
		$this->assertNull($param->getCommand());
		$this->assertNull($param->getResult());
	}

	public function test_result_is_teventparameter()
	{
		$param = new TDataGatewayResultEventParameter('cmd', 'res');
		$this->assertInstanceOf(\Prado\TEventParameter::class, $param);
	}

	public function test_result_set_result_to_array()
	{
		$param = new TDataGatewayResultEventParameter('cmd', null);
		$data = [1, 2, 3];
		$param->setResult($data);
		$this->assertSame($data, $param->getResult());
	}
}
