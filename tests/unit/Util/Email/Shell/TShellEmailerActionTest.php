<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TShellEmailerActionTest extends \PHPUnit\Framework\TestCase
{
	public function testGetModuleClass()
	{
		$action = new \Prado\Util\Cron\TShellEmailerAction();
		$this->assertEquals('Prado\\Util\\Email\\TDBEmailerModule', $action->getModuleClass());
	}

	public function testGetEmailerModuleWithoutCron()
	{
		$action = $this->createPartialMock(\Prado\Util\Cron\TShellEmailerAction::class, ['getApplication', 'getWriter']);
		
		$app = $this->createMock(\Prado\TApplication::class);
		$app->expects($this->once())
			->method('getModulesByType')
			->with('Prado\\Util\\Email\\TDBEmailerModule', false)
			->willReturn([]);
		
		$action->expects($this->once())
			->method('getApplication')
			->willReturn($app);
			
		$action->expects($this->never())
			->method('getWriter');
		
		$this->assertNull($action->getEmailerModule());
	}

	public function testSetEmailerModule()
	{
		$module = $this->createMock(\Prado\Util\Cron\TCronModule::class);
		$action = new \Prado\Util\Cron\TShellEmailerAction();
		$action->setEmailerModule($module);
		$this->assertSame($module, $action->_emailer);
	}
}