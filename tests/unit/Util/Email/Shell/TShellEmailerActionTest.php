<?php

namespace Prado\Test\Unit\Util\Email\Shell;

class TShellEmailerActionTest extends \PHPUnit\Framework\TestCase
{
	public function testGetModuleClass()
	{
		$action = new \Prado\Util\Email\Shell\TShellEmailerAction();
		$this->assertEquals('Prado\\Util\\Email\\TDbEmailModule', $action->getModuleClass());
	}

	public function testGetEmailerModuleWithoutCron()
	{
		$action = $this->createPartialMock(\Prado\Util\Email\Shell\TShellEmailerAction::class, ['getApplication', 'getWriter']);
		
		$app = $this->createMock(\Prado\TApplication::class);
		$app->expects($this->once())
			->method('getModulesByType')
			->with('Prado\\Util\\Email\\TDbEmailModule', false)
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
		$action = new \Prado\Util\Email\Shell\TShellEmailerAction();
		$action->setEmailerModule($module);
		$this->assertSame($module, $action->_emailer);
	}
}