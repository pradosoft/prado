<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Cron\TDbCronModule;
use Prado\Util\Cron\TDbCronCleanLogTask;

class TDbCronZCleanLogTaskTest extends TCronTaskTest
{	
	protected function getTestClass()
	{
		return TDbCronCleanLogTask::class;
	}
	
	public function hasNullModuleId()
	{
		return true;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TDbCronCleanLogTask::class, $this->obj);
	}

	public function testExecute()
	{
		$dbcron = new TDbCronModule();
		$cron = new TCronModule();
		$dbcron->attachBehavior('shellLog', new TShellCronLogBehavior(new TShellWriter(new TTextWriter())));
		$cron->attachBehavior('shellLog', new TShellCronLogBehavior(new TShellWriter(new TTextWriter())));
		
		$this->obj->setModuleId('NotExistModule');
		try {
			$this->obj->execute($dbcron);
			self::fail("failed to throw TInvalidDataValueException when moduleid module does not exist");
		} catch(TInvalidDataValueException $e) {}
		
		//module is not TDbCronModule, output has red
		$cron->getOutputWriter()->setColorSupported(true);
		$dbcron->getOutputWriter()->setColorSupported(true);
		$this->obj->setModuleId(null);
		$this->obj->execute($cron);
		$str = $cron->getOutputWriter()->flush();
		self::assertEquals(1, preg_match("/(\033\\[31m|\033\\[41m)/", $str));
		
		//works properly, output has green
		{
			
			$db = $dbcron->getDbConnection();
			$time = time();
			$cmd = $db->createCommand(
				"INSERT INTO {$dbcron->getTableName()} " .
					"(name, schedule, task, moduleid, username, processcount, lastexectime, active)" .
					" VALUES ('testTask1', '* * * * *', 'TTestCronModuleTask', 'module1', 'cron', '3', " . ($time - 60) .  ", NULL)".
					",('testTask2', '* * * * *', 'TTestCronModuleTask', 'module2', 'cron', '14', " . ($time - 60) .  ", NULL)".
					",('testTask3', '* * * * *', 'CMT_UserManager3".TCronModule::METHOD_SEPARATOR."method1', 'CMT_UserManager3', 'cron', '15', " . ($time - 60) .  ", NULL)".
					",('testTask4', '* * * * *', 'CMT_UserManager3".TCronModule::METHOD_SEPARATOR."method2(true)', 'CMT_UserManager3', 'cron', '16', " . ($time - 60) .  ", NULL)".
					",('testTask5', '5 * * * *', 'CMT_UserManager3".TCronModule::METHOD_SEPARATOR."method3(86400)', 'CMT_UserManager3', 'cron', '17', " . ($time - 60) .  ", NULL)".
					", ('testTask1', '1 * * * *', 'TTestCronModuleTask', 'module1', 'cron', '18', " . ($time - 120) .  ", NULL)".
					",('testTask2', '* * * * *', 'TTestCronModuleTask', 'module2', 'cron', '20', " . ($time - 120) .  ", NULL)".
					",('testTask3', '* * * * *', 'CMT_UserManager3".TCronModule::METHOD_SEPARATOR."method1', 'CMT_UserManager3', 'cron', '21', " . ($time - 120) .  ", NULL)".
					",('testTask4', '* * * * *', 'CMT_UserManager3".TCronModule::METHOD_SEPARATOR."method2(true)', 'CMT_UserManager3', 'cron', '23', " . ($time - 120) .  ", NULL)".
					",('testTask5', '* * * * *', 'CMT_UserManager3".TCronModule::METHOD_SEPARATOR."method3(86400)', 'CMT_UserManager3', 'cron', '24', " . ($time - 120) .  ", NULL)");
			
			$cmd->execute();
		}
		self::assertEquals(10, $dbcron->getCronLogCount());
		$this->obj->setTimePeriod(90);
		$this->obj->execute($dbcron);
		self::assertEquals(5, $dbcron->getCronLogCount());
		$str = $dbcron->getOutputWriter()->flush();
		self::assertEquals(1, preg_match("/(\033\\[32m|\033\\[42m)/", $str));
		
		$dbcron->clearCronLog(0);
		$dbcron->unlisten();
		$cron->unlisten();
		$dbcron = null;
		$cron = null;
	}
	
	public function testTimePeriod()
	{
		$value = 86400 * 7;
		$this->obj->setTimePeriod($value);
		self::assertEquals($value, $this->obj->getTimePeriod());
		
		$value = 0;
		$this->obj->setTimePeriod($value);
		self::assertEquals($value, $this->obj->getTimePeriod());
		
		$this->obj->setTimePeriod('');
		self::assertEquals(0, $this->obj->getTimePeriod());
	}

}
