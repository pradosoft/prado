<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Util\Cron\TCompoundCronTask;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;

/**
 * Records its steps' runs in order, and fails when its name says so.
 */
class TTestCompoundStepTask extends TCronTask
{
	public static array $runs = [];

	public function execute($cron)
	{
		self::$runs[] = $this->getName();
		if (str_contains($this->getName(), '#2') && TCompoundCronTaskTest::$failSecond) {
			throw new \RuntimeException('step 2 failed');
		}
	}
}

class TCompoundCronTaskTest extends \PHPUnit\Framework\TestCase
{
	public static bool $failSecond = false;

	private TCronModule $module;

	private TCompoundCronTask $task;

	protected function setUp(): void
	{
		TTestCompoundStepTask::$runs = [];
		self::$failSecond = false;
		$this->module = new TCronModule();
		$this->module->init([['name' => 'referenced', 'schedule' => '@never', 'task' => TTestCompoundStepTask::class]]);
		$this->task = new TCompoundCronTask();
		$this->task->setName('compound');
	}

	protected function tearDown(): void
	{
		$this->module->unlisten();
		Prado::getApplication()->setGlobalState(TCronModule::TASKS_INFO, null);
	}

	public function testProperties()
	{
		self::assertSame([], $this->task->getTasks());
		self::assertTrue($this->task->getStopOnFailure());
		self::assertFalse($this->task->getParallel());
		$this->task->setTasks(' A\\Task ; mod->method(1, 2);; @named ');
		self::assertSame(['A\\Task', 'mod->method(1, 2)', '@named'], $this->task->getTasks());
		$this->task->setStopOnFailure('false')->setParallel(true);
		$copy = unserialize(serialize($this->task));
		self::assertSame(['A\\Task', 'mod->method(1, 2)', '@named'], $copy->getTasks());
		self::assertFalse($copy->getStopOnFailure());
		self::assertTrue($copy->getParallel());
	}

	public function testExecute_RunsTheStepsInOrder()
	{
		$this->task->setTasks([TTestCompoundStepTask::class, '@referenced', TTestCompoundStepTask::class]);
		$this->task->execute($this->module);
		self::assertSame(['compound#1', 'referenced', 'compound#3'], TTestCompoundStepTask::$runs);
	}

	public function testExecute_AFailureStopsTheRestOrNot()
	{
		self::$failSecond = true;
		$this->task->setTasks([TTestCompoundStepTask::class, TTestCompoundStepTask::class, TTestCompoundStepTask::class]);
		try {
			$this->task->execute($this->module);
			self::fail('the failed step did not end the run');
		} catch (\RuntimeException $e) {
			self::assertSame('step 2 failed', $e->getMessage());
		}
		self::assertSame(['compound#1', 'compound#2'], TTestCompoundStepTask::$runs, 'StopOnFailure stops the rest');

		TTestCompoundStepTask::$runs = [];
		$this->task->setStopOnFailure(false);
		try {
			$this->task->execute($this->module);
			self::fail('the failed step did not end the run');
		} catch (\RuntimeException $e) {
		}
		self::assertSame(['compound#1', 'compound#2', 'compound#3'], TTestCompoundStepTask::$runs, 'the rest run, then the failure ends the run');

		TTestCompoundStepTask::$runs = [];
		$this->task->setParallel(true);
		try {
			$this->task->execute($this->module);
			self::fail('the failed parallel step did not end the run');
		} catch (\RuntimeException $e) {
		}
		self::assertCount(3, TTestCompoundStepTask::$runs, 'every parallel step runs');
	}

	public function testExecute_RunsInTheModuleAsOneRun()
	{
		$this->module->setDaemonMutex(new \Prado\Util\Mutex\TMemoryMutex());
		$this->module->init([['name' => 'nightly', 'schedule' => '0 0 1 1 ? 2000', 'task' => TCompoundCronTask::class, 'tasks' => TTestCompoundStepTask::class . ';@referenced']]);
		self::assertTrue($this->module->runTask($this->module->getTask('nightly')));
		self::assertSame(['nightly#1', 'referenced'], TTestCompoundStepTask::$runs);
		self::assertSame(1, $this->module->getTask('nightly')->getProcessCount());
		self::assertSame(0, $this->module->getTask('referenced')->getProcessCount(), 'a referenced step is not a run of its own');
	}

	public function testExecute_WithoutStepsOrWithAnUnknownReferenceThrows()
	{
		try {
			$this->task->execute($this->module);
			self::fail('no steps did not throw');
		} catch (TConfigurationException $e) {
		}
		$this->task->setTasks('@nobody');
		self::expectException(TConfigurationException::class);
		$this->task->execute($this->module);
	}

	public function testSerialize_LeavesOutTheDefaults()
	{
		$copy = unserialize(serialize($this->task));
		self::assertSame([], $copy->getTasks());
		self::assertTrue($copy->getStopOnFailure());
		self::assertFalse($copy->getParallel());
		self::assertStringNotContainsString('_stopOnFailure', serialize($this->task));
	}

	public function testExecute_AReferenceBackToTheCompoundThrows()
	{
		$this->module->init([
			['name' => 'outer', 'schedule' => '@never', 'task' => TCompoundCronTask::class, 'tasks' => '@inner'],
			['name' => 'inner', 'schedule' => '@never', 'task' => TCompoundCronTask::class, 'tasks' => '@referenced;@outer'],
			['name' => 'self', 'schedule' => '@never', 'task' => TCompoundCronTask::class, 'tasks' => '@self'],
			['name' => 'nested', 'schedule' => '@never', 'task' => TCompoundCronTask::class, 'tasks' => '@referenced'],
			['name' => 'top', 'schedule' => '@never', 'task' => TCompoundCronTask::class, 'tasks' => '@nested;@nested'],
		]);
		foreach (['outer', 'inner', 'self'] as $name) {
			try {
				$this->module->getTask($name)->execute($this->module);
				self::fail("the cycle of '{$name}' did not throw");
			} catch (TConfigurationException $e) {
				self::assertStringContainsString('leads back to it', $e->getMessage());
			}
		}
		self::assertSame([], TTestCompoundStepTask::$runs, 'a cycle runs no step');
		$this->module->getTask('top')->execute($this->module);
		self::assertSame(['referenced', 'referenced'], TTestCompoundStepTask::$runs, 'a compound referenced twice is no cycle');
	}
}
