<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\Wake\TCronWakeFile;
use Prado\Util\Mutex\TFileMutex;
use Prado\Util\Mutex\TMemoryMutex;

/**
 * Tests of TCronModule behavior that TDbCronManager replaces with its table, so TDbCronManagerTest,
 * which inherits TCronModuleTest, does not run them.
 */
class TCronModuleBaseTest extends \PHPUnit\Framework\TestCase
{
	/** @var TCronModule[] the modules to unlisten after the test */
	private array $_modules = [];

	protected function tearDown(): void
	{
		foreach ($this->_modules as $module) {
			$module->unlisten();
		}
		$this->_modules = [];
	}

	private function newModule(?array $tasks = null, ?string $id = null): TCronModule
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID($id ?? 'cronBase' . bin2hex(random_bytes(3)));
		$module->init($tasks);
		return $module;
	}

	private function job(string $name, string $schedule = '0 0 1 1 ? 2099', string $task = TTestCronModuleTask::class): array
	{
		return ['name' => $name, 'schedule' => $schedule, 'task' => $task];
	}

	public function testTaskListsPage()
	{
		$module = $this->newModule([$this->job('pageA'), $this->job('pageB'), $this->job('pageC')]);
		self::assertSame(['pageB'], array_keys($module->getTasks('page%', 1, 1)));
		self::assertSame(['pageA', 'pageB'], array_keys($module->getUserTasks($module->getDefaultUserName(), 'page%', 2)));
		self::assertSame(['pageC'], array_keys($module->getTasksByType(TTestCronModuleTask::class, 'page%', 1, 0, false)));
	}

	public function testGetNextPendingTimeIsTheEarliestTrigger()
	{
		$module = $this->newModule([$this->job('soonTask', '0 0 1 1 ? 2098'), $this->job('laterTask', '0 0 1 1 ? 2099'), $this->job('neverTask', '@never')]);
		self::assertSame($module->getTask('soonTask')->getNextTriggerTime(), $module->getNextPendingTime());
	}

	public function testEmptyStringMeansTheDefaultTransportAndMutex()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setWakeTransport(new TCronWakeFile(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-' . bin2hex(random_bytes(4))));
		$module->setWakeTransport('');
		self::assertNull(PradoUnit::getProp($module, '_wakeTransport'));
		$module->setDaemonMutex(new TMemoryMutex());
		$module->setDaemonMutex('');
		self::assertInstanceOf(TFileMutex::class, $module->getDaemonMutex(), 'an empty mutex is the default file mutex');
	}

	public function testAPausedFileThatIsNoListPausesNothing()
	{
		$module = $this->newModule([$this->job('pausable')]);
		$file = PradoUnit::invoke($module, 'getPausedFile');
		try {
			file_put_contents($file, '"not a list"');
			self::assertSame([], $module->getPausedTasks());
		} finally {
			@unlink($file);
		}
	}

	public function testDaemonConfigFilesWithoutAConfigurationFile()
	{
		$app = Prado::getApplication();
		$file = PradoUnit::getProp($app, '_configFile');
		try {
			PradoUnit::setProp($app, '_configFile', null);
			self::assertSame([], $this->newModule()->getDaemonConfigFiles());
		} finally {
			PradoUnit::setProp($app, '_configFile', $file);
		}
	}

	public function testMetricsOfAModuleWithoutTasks()
	{
		$metrics = $this->newModule()->getMonitorMetrics();
		self::assertStringContainsString('# TYPE prado_cron_task_running gauge', $metrics);
		self::assertStringNotContainsString('prado_cron_task_running{', $metrics, 'a metric without tasks has no samples');
	}
}
