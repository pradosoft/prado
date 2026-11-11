<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TDbEmailerModuleTest extends \PHPUnit\Framework\TestCase
{
	public function testGetLogging()
	{
		$module = new \Prado\Web\Services\TDbEmailerModule();
		$this->assertTrue($module->getLogging());
	}

	public function testSetLogging()
	{
		$module = new \Prado\Web\Services\TDbEmailerModule();
		$module->setLogging(false);
		$this->assertFalse($module->getLogging());
	}

	public function testGetOfflineEmail()
	{
		$module = new \Prado\Web\Services\TDbEmailerModule();
		$this->assertFalse($module->getOfflineEmail());
	}

	public function testSetOfflineEmail()
	{
		$module = new \Prado\Web\Services\TDbEmailerModule();
		$module->setOfflineEmail(true);
		$this->assertTrue($module->getOfflineEmail());
	}

	public function testGetServiceID()
	{
		$module = new \Prado\Web\Services\TDbEmailerModule();
		$this->assertEquals('', $module->getServiceID());
	}

	public function testSetServiceID()
	{
		$module = new \Prado\Web\Services\TDbEmailerModule();
		$module->setServiceID('testService');
		$this->assertEquals('testService', $module->getServiceID());
	}

	public function testGetHasAuthorization()
	{
		$module = new \Prado\Web\Services\TDbEmailerModule();
		$this->assertTrue($module->getHasAuthorization());
	}
}