<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TDbEmailServiceTest extends \PHPUnit\Framework\TestCase
{
	public function testGetEmailerModuleWithString()
	{
		$service = $this->createPartialMock(\Prado\Web\Services\TDbEmailService::class, ['getApplication']);
		
		$module = $this->createMock(\Prado\Util\Email\TDbEmailerModule::class);
		$app = $this->createMock(\Prado\TApplication::class);
		$app->expects($this->once())
			->method('getModule')
			->with('testModule')
			->willReturn($module);
		
		$service->expects($this->once())
			->method('getApplication')
			->willReturn($app);
			
		$service->setEmailerModule('testModule');
		$this->assertSame($module, $service->getEmailerModule());
	}

	public function testGetEmailerModuleWithModule()
	{
		$module = $this->createMock(\Prado\Util\Email\TDbEmailerModule::class);
		$service = new \Prado\Web\Services\TDbEmailService();
		$service->setEmailerModule($module);
		$this->assertSame($module, $service->getEmailerModule());
	}

	public function testGetEmailerModuleWithInvalidModule()
	{
		$service = $this->createPartialMock(\Prado\Web\Services\TDbEmailService::class, ['getApplication']);
		
		$module = $this->createMock(\Prado\TModule::class);
		$app = $this->createMock(\Prado\TApplication::class);
		$app->expects($this->once())
			->method('getModule')
			->with('testModule')
			->willReturn($module);
		
		$service->expects($this->once())
			->method('getApplication')
			->willReturn($app);
			
		$service->setEmailerModule('testModule');
		
		// This should throw an exception
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$service->getEmailerModule();
	}

	public function testSetEmailerModule()
	{
		$module = $this->createMock(\Prado\Util\Email\TDbEmailerModule::class);
		$service = new \Prado\Web\Services\TDbEmailService();
		$service->setEmailerModule($module);
		$this->assertSame($module, $service->_emailer);
	}
}