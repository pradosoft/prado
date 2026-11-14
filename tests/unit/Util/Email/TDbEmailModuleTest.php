<?php

namespace Prado\Test\Unit\Util\Email;

class TDbEmailModuleTest extends \PHPUnit\Framework\TestCase
{
	public function testGetLogging()
	{
		$module = new \Prado\Util\Email\TDbEmailModule();
		$this->assertTrue($module->getLogging());
	}

	public function testSetLogging()
	{
		$module = new \Prado\Util\Email\TDbEmailModule();
		$module->setLogging(false);
		$this->assertFalse($module->getLogging());
	}

	public function testGetOfflineEmail()
	{
		$module = new \Prado\Util\Email\TDbEmailModule();
		$this->assertFalse($module->getOfflineEmail());
	}

	public function testSetOfflineEmail()
	{
		$module = new \Prado\Util\Email\TDbEmailModule();
		$module->setOfflineEmail(true);
		$this->assertTrue($module->getOfflineEmail());
	}

	public function testGetServiceID()
	{
		$module = new \Prado\Util\Email\TDbEmailModule();
		$this->assertEquals('', $module->getServiceID());
	}

	public function testSetServiceID()
	{
		$module = new \Prado\Util\Email\TDbEmailModule();
		$module->setServiceID('testService');
		$this->assertEquals('testService', $module->getServiceID());
	}

	public function testGetHasAuthorization()
	{
		$module = new \Prado\Util\Email\TDbEmailModule();
		$this->assertTrue($module->getHasAuthorization());
	}
}