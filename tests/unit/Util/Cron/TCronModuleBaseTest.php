<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Prado;
use Prado\Test\Unit\Harness\Util\Cron\TTestCronModule;
use Prado\Test\Unit\PradoUnit;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Caching\TGlobalStateCacheDependency;
use Prado\IO\Async\TFiberContext;
use Prado\IO\Async\TCancellationToken;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTaskEventParameter;
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

	/** @var string[] the status directories to remove after the test */
	private array $_dirs = [];

	/** @var array the recorded task information before the test, restored after it */
	private array $_tasksInfo = [];

	protected function setUp(): void
	{
		$this->_tasksInfo = Prado::getApplication()->getGlobalState(TCronModule::TASKS_INFO, []);
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setGlobalState(TCronModule::TASKS_INFO, $this->_tasksInfo, []);
		foreach ($this->_modules as $module) {
			$module->unlisten();
		}
		$this->_modules = [];
		foreach ($this->_dirs as $dir) {
			foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
				unlink($file);
			}
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
		$this->_dirs = [];
	}

	private function statusDir(): string
	{
		return $this->_dirs[] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-cronbase-' . bin2hex(random_bytes(4));
	}

	private function newTestModule(?array $tasks = null): TTestCronModule
	{
		$module = $this->_modules[] = new TTestCronModule();
		$module->setID('cronBaseTest' . bin2hex(random_bytes(3)));
		$module->init($tasks);
		return $module;
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

	public function testProcessPendingTasksOutsideTheShellSweepsInDaemonMode()
	{
		foreach ([true, false] as $enableStatus) {
			$module = $this->_modules[] = new TTestCronModule();
			$module->setID('cronBaseWeb' . bin2hex(random_bytes(3)));
			$module->setDaemon(true);
			$module->setStatusPath($this->statusDir());
			$module->setEnableStatus($enableStatus);
			$module->fakeSapi = 'fpm-fcgi';
			$module->init([$this->job('webDue' . bin2hex(random_bytes(3)), '0 0 1 1 ? 2000')]);
			self::assertSame(1, $module->processPendingTasks(), 'a web request sweeps once, even with Daemon');
			self::assertSame([], glob($module->getStatusPath() . DIRECTORY_SEPARATOR . '*.json') ?: [], 'the status is removed after the sweep');
		}
	}

	public function testProcessPendingTasksEndsItsStatusWhenTheSweepThrows()
	{
		foreach ([true, false] as $enableStatus) {
			$module = $this->_modules[] = new TTestCronModule();
			$module->setID('cronBaseThrow' . bin2hex(random_bytes(3)));
			$module->setStatusPath($this->statusDir());
			$module->setEnableStatus($enableStatus);
			$module->init([$this->job('throwDue' . bin2hex(random_bytes(3)), '0 0 1 1 ? 2000')]);
			$module->attachEventHandler('OnSweepStart', static function () {
				throw new \RuntimeException('sweep failed');
			});
			try {
				$module->processPendingTasks();
				self::fail('the error of a sweep is lost');
			} catch (\RuntimeException $e) {
				self::assertSame('sweep failed', $e->getMessage());
			}
			self::assertSame([], glob($module->getStatusPath() . DIRECTORY_SEPARATOR . '*.json') ?: []);
		}
	}

	public function testATaskWithADeadlineAndNoTriggerIsNotPastIt()
	{
		$module = $this->newTestModule([$this->job('deadlineNever', '@never')]);
		$task = $module->getTask('deadlineNever');
		$task->setStartingDeadline(60);
		self::assertFalse(PradoUnit::invoke($module, 'isTaskPastDeadline', $task));
	}

	public function testGetTaskResultsReadsEachRecordedValue()
	{
		self::assertSame([], $this->newModule()->getTaskResults(), 'a module without tasks has no results');
		$app = Prado::getApplication();
		$app->setGlobalState(TCronModule::TASKS_INFO, ['resultsAll' => ['lastExecTime' => 100, 'processCount' => 3, 'lastResult' => 'ok', 'lastDuration' => 1.5, 'okCount' => 2, 'failedCount' => 1, 'totalDuration' => 4.5, 'maxDuration' => 2.5]]);
		$results = $this->newModule([$this->job('resultsAll')])->getTaskResults();
		self::assertSame(['time' => 100, 'count' => 3, 'result' => 'ok', 'duration' => 1.5, 'ok' => 2, 'failed' => 1, 'total' => 4.5, 'max' => 2.5], $results['resultsAll']);
		$app->setGlobalState(TCronModule::TASKS_INFO, ['resultsTime' => ['lastExecTime' => 100, 'processCount' => 1]]);
		$results = $this->newModule([$this->job('resultsTime')])->getTaskResults();
		self::assertSame([100, null, null], [$results['resultsTime']['time'], $results['resultsTime']['duration'], $results['resultsTime']['max']]);
		$app->setGlobalState(TCronModule::TASKS_INFO, ['resultsDuration' => ['lastExecTime' => null, 'processCount' => 0, 'lastDuration' => 0.5]]);
		$results = $this->newModule([$this->job('resultsDuration')])->getTaskResults();
		self::assertSame([null, 0.5, null], [$results['resultsDuration']['time'], $results['resultsDuration']['duration'], $results['resultsDuration']['max']]);
		$app->setGlobalState(TCronModule::TASKS_INFO, ['resultsMax' => ['lastExecTime' => null, 'processCount' => 0, 'maxDuration' => 0.5]]);
		$results = $this->newModule([$this->job('resultsMax')])->getTaskResults();
		self::assertSame([null, null, 0.5], [$results['resultsMax']['time'], $results['resultsMax']['duration'], $results['resultsMax']['max']]);
	}

	public function testAStatusOfThisHostWithoutAPidIsNotAlive()
	{
		$module = $this->newModule();
		self::assertFalse(PradoUnit::invoke($module, 'isStatusAlive', ['host' => 'here', 'pid' => 0], 'here', 1.0));
	}

	public function testBeginRunStatusRecordsTheDeadlineAndWarning()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBaseRun' . bin2hex(random_bytes(3)));
		$module->setStatusPath($this->statusDir());
		$module->setEnableStatus(true);
		$module->init([$this->job('runStatus')]);
		$task = $module->getTask('runStatus');
		self::assertTrue(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP));
		try {
			foreach ([[null, null], [30, null], [null, 5], [30, 5]] as [$timeout, $warn]) {
				$task->setTimeout($timeout);
				$task->setWarnDuration($warn);
				$id = PradoUnit::invoke($module, 'beginRunStatus', $task, 'cron');
				$run = PradoUnit::getProp($module, '_status')['running'][$id];
				self::assertSame([$timeout === null, $warn === null], [$run['deadline'] === null, $run['warn'] === null]);
			}
		} finally {
			PradoUnit::invoke($module, 'endStatus');
		}
	}

	public function testEndRunStatusIgnoresAnUnknownRun()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBaseEnd' . bin2hex(random_bytes(3)));
		$module->setStatusPath($this->statusDir());
		$module->setEnableStatus(true);
		$module->init([$this->job('endStatus')]);
		self::assertTrue(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP));
		try {
			PradoUnit::invoke($module, 'endRunStatus', null, TCronModule::RESULT_OK, 1.0);
			PradoUnit::invoke($module, 'endRunStatus', 999, TCronModule::RESULT_OK, 1.0);
			self::assertSame([], PradoUnit::getProp($module, '_status')['recent'], 'no run without a known ID ends');
			$id = PradoUnit::invoke($module, 'beginRunStatus', $module->getTask('endStatus'), 'cron');
			PradoUnit::invoke($module, 'endRunStatus', $id, TCronModule::RESULT_OK, 1.0);
			self::assertSame('endStatus', PradoUnit::getProp($module, '_status')['recent'][0]['name']);
		} finally {
			PradoUnit::invoke($module, 'endStatus');
		}
	}

	public function testAProcessOfAnotherUserRuns()
	{
		if (!function_exists('posix_get_last_error')) {
			self::markTestSkipped('the EPERM check needs posix');
		}
		$module = $this->newModule();
		self::assertTrue(PradoUnit::invoke($module, 'isProcessRunning', 1), 'pid 1 belongs to another user and cannot be signaled');
		self::assertFalse(PradoUnit::invoke($module, 'isProcessRunning', 999999999));
	}

	public function testCheckDaemonConfigRetiresOnAChange()
	{
		$module = $this->newModule();
		PradoUnit::setProp($module, '_daemonRetiring', true);
		PradoUnit::setProp($module, '_daemonFingerprint', 'changed');
		PradoUnit::invoke($module, 'checkDaemonConfig');
		self::assertFalse(PradoUnit::getProp($module, '_daemonRetireRequested'), 'a retiring daemon checks nothing');

		PradoUnit::setProp($module, '_daemonRetiring', false);
		PradoUnit::setProp($module, '_daemonFingerprint', null);
		PradoUnit::invoke($module, 'checkDaemonConfig');
		self::assertFalse(PradoUnit::getProp($module, '_daemonRetireRequested'), 'no fingerprint and no dependency');

		$app = Prado::getApplication();
		$key = 'cronBaseDependency' . bin2hex(random_bytes(3));
		$app->setGlobalState($key, 1);
		try {
			$module->setDaemonDependency(new TGlobalStateCacheDependency($key));
			PradoUnit::invoke($module, 'checkDaemonConfig');
			self::assertFalse(PradoUnit::getProp($module, '_daemonRetireRequested'), 'an unchanged dependency');
			PradoUnit::setProp($module, '_daemonFingerprint', PradoUnit::invoke($module, 'getDaemonConfigFingerprint'));
			$app->setGlobalState($key, 2);
			PradoUnit::invoke($module, 'checkDaemonConfig');
			self::assertTrue(PradoUnit::getProp($module, '_daemonRetireRequested'), 'a changed dependency beside an unchanged configuration');

			PradoUnit::setProp($module, '_daemonRetireRequested', false);
			PradoUnit::setProp($module, '_daemonFingerprint', 'changed');
			PradoUnit::invoke($module, 'checkDaemonConfig');
			self::assertTrue(PradoUnit::getProp($module, '_daemonRetireRequested'), 'a changed configuration');
			PradoUnit::invoke($module, 'checkDaemonConfig');
			self::assertTrue(PradoUnit::getProp($module, '_daemonRetireRequested'), 'a requested retirement checks nothing more');
		} finally {
			$app->clearGlobalState($key);
		}
	}

	public function testGetTaskInfosIsCachedUntilForced()
	{
		$module = $this->newModule();
		$infos = $module->getTaskInfos();
		PradoUnit::setProp($module, '_tasksInfo', ['cached']);
		self::assertSame(['cached'], $module->getTaskInfos());
		self::assertEquals($infos, $module->getTaskInfos(true));
	}

	public function testGetNextTriggersFreshAndCached()
	{
		self::assertSame([], $this->newModule()->getNextTriggers(10, true));
		self::assertSame([], $this->newModule()->getNextTriggers());
		$module = $this->newModule([$this->job('triggerSoon', '0 0 1 1 ? 2098'), $this->job('triggerNever', '@never')]);
		self::assertSame(['triggerSoon'], array_keys($module->getNextTriggers()));
		self::assertSame([], $module->getNextTriggers(0));
		$app = Prado::getApplication();
		$info = $app->getGlobalState(TCronModule::TASKS_INFO, []);
		$info['triggerSoon']['lastExecTime'] = gmmktime(0, 0, 0, 1, 1, 2097);
		$app->setGlobalState(TCronModule::TASKS_INFO, $info);
		$app->saveGlobals();
		self::assertSame(['triggerSoon'], array_keys($module->getNextTriggers(10, true)));
		unset($info['triggerSoon']);
		$app->setGlobalState(TCronModule::TASKS_INFO, $info);
		$app->saveGlobals();
		self::assertSame(['triggerSoon'], array_keys($module->getNextTriggers(10, true)), 'a task without a recorded run counts from 0');
	}

	public function testGetTaskOfAnUnconstructedTaskTheUserMayNotRead()
	{
		$module = $this->newModule([$this->job('deniedTask')]);
		$module->attachBehavior('deny', new TTestCronDenyingBehavior(['dyGetUserTasks']));
		self::assertNull($module->getTask('deniedTask'));
		$module->detachBehavior('deny');
		self::assertNotNull($module->getTask('deniedTask'));
	}

	public function testGetTaskSkipReasonForACaughtUpRun()
	{
		$module = $this->newTestModule([$this->job('caughtTask')]);
		$task = $module->getTask('caughtTask');
		self::assertSame(TCronTaskEventParameter::REASON_DEADLINE, PradoUnit::invoke($module, 'getTaskSkipReason', $task, []));
		self::assertNull(PradoUnit::invoke($module, 'getTaskSkipReason', $task, [100]));
		$task->setDefaultTimeZone('UTC');
		$task->setBetween('00:00-00:01');
		$module->fakeNow = gmmktime(12, 0, 0, 6, 1, 2026);
		self::assertSame(TCronTaskEventParameter::REASON_WINDOW, PradoUnit::invoke($module, 'getTaskSkipReason', $task, [100]));
		$task->setBetween('');
		$module->attachBehavior('decline', new TTestCronDecliningBehavior());
		self::assertSame(TCronTaskEventParameter::REASON_CONDITION, PradoUnit::invoke($module, 'getTaskSkipReason', $task, [100]));
	}

	public function testTaskTimeoutValues()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setTaskTimeout('30');
		self::assertSame(30.0, $module->getTaskTimeout());
		$module->setTaskTimeout('');
		self::assertNull($module->getTaskTimeout());
		$module->setTaskTimeout(30);
		$module->setTaskTimeout(null);
		self::assertNull($module->getTaskTimeout());
		self::expectException(TInvalidDataValueException::class);
		$module->setTaskTimeout(0);
	}

	public function testRegisterShellActionOnlyInAShellThatAllowsIt()
	{
		$module = $this->newModule();
		$module->registerShellAction(null, null);
		$module->attachBehavior('deny', new TTestCronDenyingBehavior(['dyRegisterShellAction']));
		$module->registerShellAction(null, null);
		self::assertTrue(true, 'outside a shell application, or denied, nothing is registered');
	}

	public function testTheTransportAndMutexByModuleID()
	{
		$app = Prado::getApplication();
		$snapshot = PradoUnit::snapshot($app, ['_modules']);
		$transport = new TCronWakeFile(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-' . bin2hex(random_bytes(4)));
		$mutex = new TMemoryMutex();
		try {
			$app->setModule('cronBaseWake', $transport);
			$app->setModule('cronBaseMutex', $mutex);
			$module = $this->_modules[] = new TCronModule();
			$module->setWakeTransport('cronBaseWake');
			$module->setDaemonMutex('cronBaseMutex');
			self::assertSame($transport, $module->getWakeTransport());
			self::assertSame($mutex, $module->getDaemonMutex());
			$module->setWakeTransport('noSuchWakeModule');
			try {
				$module->getWakeTransport();
				self::fail('a module ID that is no transport was accepted');
			} catch (TConfigurationException $e) {
			}
		} finally {
			PradoUnit::restore($app, $snapshot);
		}
	}

	public function testUserManagerValues()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setUserManager('users');
		$module->setUserManager(new TUserManager());
		$module->setUserManager(null);
		self::expectException(TConfigurationException::class);
		$module->setUserManager(5);
	}

	public function testSetRunStatusPidOfAnUnknownRun()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBasePid' . bin2hex(random_bytes(3)));
		$module->setStatusPath($this->statusDir());
		$module->setEnableStatus(true);
		$module->init([$this->job('pidStatus')]);
		self::assertTrue(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP));
		try {
			PradoUnit::invoke($module, 'setRunStatusPid', null, 5);
			PradoUnit::invoke($module, 'setRunStatusPid', 999, 5);
			$id = PradoUnit::invoke($module, 'beginRunStatus', $module->getTask('pidStatus'), 'cron');
			PradoUnit::invoke($module, 'setRunStatusPid', $id, 4242);
			self::assertSame(4242, PradoUnit::getProp($module, '_status')['running'][$id]['pid']);
		} finally {
			PradoUnit::invoke($module, 'endStatus');
		}
	}

	private function userManager(): TUserManager
	{
		$app = Prado::getApplication();
		$users = new TUserManager();
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<users><user name="admin" password="demo"/><user name="cron" password="cron" /></users>');
		$prior = $app->getConfigurationType();
		$app->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		try {
			$users->init($config);
		} finally {
			$app->setConfigurationType($prior);
		}
		return $users;
	}

	public function testRunAsTaskUserRestoresTheFiberUser()
	{
		$app = Prado::getApplication();
		$priorUser = $app->getUser();
		$module = $this->_modules[] = new TCronModule();
		$module->setUserManager($users = $this->userManager());
		$module->init([$this->job('asUser') + ['username' => 'admin']]);
		$task = $module->getTask('asUser');
		try {
			PradoUnit::setProp($app, '_user', null);
			$seen = PradoUnit::invoke($module, 'runAsTaskUser', $task, static fn ($username) => $app->getUser()->getName() . '@' . $username);
			self::assertSame('admin@admin', $seen);
			self::assertFalse(TFiberContext::has(TApplication::USER_CONTEXT_KEY), 'without a prior fiber user, the user is removed');

			$outer = new TUser($users);
			$outer->setName('outer');
			TFiberContext::set(TApplication::USER_CONTEXT_KEY, $outer);
			try {
				PradoUnit::invoke($module, 'runAsTaskUser', $task, static fn () => throw new \RuntimeException('task failed'));
				self::fail('the exception of the callback is lost');
			} catch (\RuntimeException $e) {
			}
			self::assertSame($outer, TFiberContext::get(TApplication::USER_CONTEXT_KEY), 'the prior fiber user is restored after an exception');
		} finally {
			TFiberContext::remove(TApplication::USER_CONTEXT_KEY);
			PradoUnit::setProp($app, '_user', $priorUser);
		}

		$module = $this->_modules[] = new TCronModule();
		$module->init([$this->job('asNobody')]);
		try {
			PradoUnit::invoke($module, 'runAsTaskUser', $module->getTask('asNobody'), static fn () => throw new \RuntimeException('task failed'));
			self::fail('the exception of the callback is lost');
		} catch (\RuntimeException $e) {
			self::assertFalse(TFiberContext::has(TApplication::USER_CONTEXT_KEY), 'without a user manager there is no task user');
		}
	}

	public function testTakeOverDaemonFromARetiringDaemon()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBaseTakeOver' . bin2hex(random_bytes(3)));
		$module->setDaemonMutex($mutex = new TMemoryMutex());
		$module->setDaemonHandoffTimeout(0.05);
		$other = new TMemoryMutex();
		$lease = $module->getDaemonLockName();
		$retiring = $module->getDaemonRetiringLockName();
		$handoff = $module->getDaemonHandoffLockName();
		try {
			self::assertTrue($other->acquire($lease));
			self::assertFalse(PradoUnit::invoke($module, 'takeOverDaemon', $mutex), 'a healthy daemon keeps its lease');
			self::assertTrue($other->acquire($retiring));
			self::assertTrue($other->acquire($handoff));
			self::assertFalse(PradoUnit::invoke($module, 'takeOverDaemon', $mutex), 'another process already waits');
			$other->release($handoff);
			self::assertFalse(PradoUnit::invoke($module, 'takeOverDaemon', $mutex), 'the lease was not handed over in time');
			self::assertFalse($mutex->isAcquired($handoff), 'the handoff lock is released');
			$other->release($lease);
			self::assertTrue(PradoUnit::invoke($module, 'takeOverDaemon', $mutex), 'the released lease is taken over');
			$mutex->release($lease);
		} finally {
			$other->releaseAll();
			$mutex->releaseAll();
		}
	}

	public function testStatusPathEmptyIsTheDefault()
	{
		$module = $this->_modules[] = new TCronModule();
		$default = $module->getStatusPath();
		$module->setStatusPath('/tmp/cronstatus');
		$module->setStatusPath('');
		self::assertSame($default, $module->getStatusPath());
		$module->setStatusPath('/tmp/cronstatus');
		$module->setStatusPath(null);
		self::assertSame($default, $module->getStatusPath());
	}

	public function testStopActiveRun()
	{
		$module = $this->_modules[] = new TCronModule();
		self::assertFalse(PradoUnit::invoke($module, 'stopActiveRun', 'noRun'));
		PradoUnit::setProp($module, '_activeRuns', ['bare' => ['token' => null, 'process' => null, 'replaced' => false], 'tokened' => ['token' => $token = new TCancellationToken(), 'process' => null, 'replaced' => false]]);
		self::assertTrue(PradoUnit::invoke($module, 'stopActiveRun', 'bare'), 'a run without a token or process is marked replaced');
		self::assertTrue(PradoUnit::getProp($module, '_activeRuns')['bare']['replaced']);
		self::assertTrue(PradoUnit::invoke($module, 'stopActiveRun', 'tokened'));
		self::assertTrue($token->isCancelled());
	}

	public function testRunTaskNowWithAndWithoutAStatus()
	{
		foreach ([true, false] as $enableStatus) {
			$module = $this->_modules[] = new TCronModule();
			$module->setID('cronBaseNow' . bin2hex(random_bytes(3)));
			$module->setStatusPath($this->statusDir());
			$module->setEnableStatus($enableStatus);
			$module->setDaemonMutex(new TMemoryMutex());
			$module->init([$this->job('nowTask')]);
			self::assertTrue($module->runTaskNow($module->getTask('nowTask')));
			$module->attachEventHandler('OnTaskStart', static function () {
				throw new \RuntimeException('start failed');
			});
			try {
				$module->runTaskNow($module->getTask('nowTask'));
				self::fail('the error of a start handler is lost');
			} catch (\RuntimeException $e) {
			}
			self::assertSame([], glob($module->getStatusPath() . DIRECTORY_SEPARATOR . '*.json') ?: [], 'the status ends either way');
		}
	}

	public function testGetStatusesSkipsForeignAndBrokenFiles()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBaseStatuses');
		$module->setStatusPath($dir = $this->statusDir());
		mkdir($dir);
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'broken.json', 'not json');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'other.json', json_encode(['module' => 'anotherModule']));
		self::assertSame([], $module->getStatuses());
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'mine.json', json_encode(['module' => 'cronBaseStatuses', 'host' => 'another-host.invalid', 'updated' => 0]));
		$statuses = $module->getStatuses();
		self::assertCount(1, $statuses);
		self::assertSame([[], [], false], [$statuses[0]['running'], $statuses[0]['recent'], $statuses[0]['alive']]);
	}

	public function testSweepWithoutPendingTasks()
	{
		$module = $this->newTestModule([$this->job('futureTask')]);
		$ended = null;
		$module->attachEventHandler('OnSweepEnd', static function ($sender, $param) use (&$ended) {
			$ended = $param->getCount();
		});
		self::assertSame(0, PradoUnit::invoke($module, 'sweepPendingTasks'));
		self::assertSame(0, $ended, 'a sweep with no task due still ends');
		$module->attachBehavior('deny', new TTestCronDenyingBehavior(['dyGetPendingTasks']));
		$ended = null;
		self::assertSame(0, PradoUnit::invoke($module, 'sweepPendingTasks'));
		self::assertNull($ended, 'a user who may not read the pending tasks sweeps nothing');
	}

	public function testRequestDaemonWaitsForTheAcknowledgment()
	{
		$module = $this->_modules[] = new class () extends TCronModule {
			public array $states = [];

			public function getDaemonState(): ?string
			{
				return (count($this->states) > 1) ? array_shift($this->states) : ($this->states[0] ?? null);
			}

			public function signalWake(): bool
			{
				return false;
			}
		};
		$module->setID('cronBaseRequest' . bin2hex(random_bytes(3)));
		$module->setDaemonMutex($mutex = new TMemoryMutex());
		$lock = $module->getDaemonLockName() . ':test';
		$retired = static fn (?string $state): bool => $state === TCronModule::DAEMON_STATE_RETIRING;
		self::assertNull(PradoUnit::invoke($module, 'requestDaemon', $lock, $retired, 0.05), 'no daemon');
		$module->states = [TCronModule::DAEMON_STATE_RETIRING];
		self::assertTrue(PradoUnit::invoke($module, 'requestDaemon', $lock, $retired, 0.05), 'already acknowledged');
		$module->states = [TCronModule::DAEMON_STATE_RUNNING, TCronModule::DAEMON_STATE_RUNNING, TCronModule::DAEMON_STATE_RETIRING];
		self::assertTrue(PradoUnit::invoke($module, 'requestDaemon', $lock, $retired, 2.0), 'acknowledged after a wait');
		$module->states = [TCronModule::DAEMON_STATE_RUNNING];
		self::assertFalse(PradoUnit::invoke($module, 'requestDaemon', $lock, $retired, 0.05), 'not acknowledged in time');
		self::assertFalse($mutex->isAcquired($lock), 'the request lock is released');
		$other = new TMemoryMutex();
		try {
			self::assertTrue($other->acquire($lock));
			self::assertFalse(PradoUnit::invoke($module, 'requestDaemon', $lock, $retired, 0.05), 'another request holds the lock');
		} finally {
			$other->releaseAll();
		}
	}

	public function testTaskProcessCommandOfEachSapiAndMemoryLimit()
	{
		$module = $this->newTestModule([$this->job('procCommand')]);
		$task = $module->getTask('procCommand');
		$module->fakeSapi = 'cli';
		self::assertSame(PHP_BINARY, $module->getTaskProcessCommand($task)[0]);
		$task->setProcessMemoryLimit('64M');
		self::assertSame([PHP_BINARY, '-d', 'memory_limit=64M'], array_slice($module->getTaskProcessCommand($task), 0, 3));
		$module->fakeSapi = 'fpm-fcgi';
		self::assertStringStartsWith(PHP_BINDIR . DIRECTORY_SEPARATOR . 'php', $module->getTaskProcessCommand($task)[0]);
		$task->setProcessMemoryLimit(null);
		self::assertSame('-d=' . Prado::getApplication()->getBasePath(), $module->getTaskProcessCommand($task)[2]);
	}

	public function testMarkTaskOverdueOfAnUnknownRun()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBaseOverdue' . bin2hex(random_bytes(3)));
		$module->setStatusPath($this->statusDir());
		$module->setEnableStatus(true);
		$module->init([$this->job('overdueTask')]);
		$task = $module->getTask('overdueTask');
		$overdue = 0;
		$module->attachEventHandler('OnTaskOverdue', static function () use (&$overdue) {
			$overdue++;
		});
		self::assertTrue(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP));
		try {
			$param = new TCronTaskEventParameter($task);
			PradoUnit::invoke($module, 'markTaskOverdue', $task, $param, null, 2.0);
			PradoUnit::invoke($module, 'markTaskOverdue', $task, $param, 999, 2.0);
			$id = PradoUnit::invoke($module, 'beginRunStatus', $task, 'cron');
			PradoUnit::invoke($module, 'markTaskOverdue', $task, $param, $id, 2.0);
			self::assertTrue(PradoUnit::getProp($module, '_status')['running'][$id]['overdue']);
			self::assertSame(3, $overdue, 'the event is raised for every overdue run');
		} finally {
			PradoUnit::invoke($module, 'endStatus');
		}
	}

	public function testWriteTaskPausedKeepsAValidListAndLogsAnUnopenableFile()
	{
		$dir = $this->statusDir();
		$module = $this->_modules[] = new class () extends TCronModule {
			public string $pausedFile = '';

			protected function getPausedFile(): string
			{
				return $this->pausedFile;
			}
		};
		$module->pausedFile = $dir . DIRECTORY_SEPARATOR . 'missing' . DIRECTORY_SEPARATOR . 'paused.json';
		PradoUnit::invoke($module, 'writeTaskPaused', 'pausedTask', true);
		self::assertSame([], $module->getPausedTasks(), 'a file that cannot be opened stores nothing');
		mkdir($dir);
		$module->pausedFile = $dir . DIRECTORY_SEPARATOR . 'paused.json';
		file_put_contents($module->pausedFile, '"not a list"');
		PradoUnit::invoke($module, 'writeTaskPaused', 'pausedTask', true);
		self::assertSame(['pausedTask'], $module->getPausedTasks());
		file_put_contents($module->pausedFile, json_encode(['pausedTask', 5, 'otherTask']));
		PradoUnit::invoke($module, 'writeTaskPaused', 'pausedTask', false);
		self::assertSame(['otherTask'], $module->getPausedTasks(), 'a resume removes the name and the entries that are no names');
	}

	public function testSignalWakeWithoutATransport()
	{
		$module = $this->_modules[] = new class () extends TCronModule {
			public function getWakeTransport(): ?\Prado\Util\Cron\Wake\ICronWakeTransport
			{
				return null;
			}
		};
		self::assertFalse($module->signalWake());
	}

	public function testConstructTaskWithoutProperties()
	{
		$module = $this->newModule();
		$task = PradoUnit::invoke($module, 'constructTask', [TCronModule::NAME_KEY => 'bareTask', TCronModule::TASK_KEY => TTestCronModuleTask::class]);
		self::assertSame('bareTask', $task->getName());
	}

	public function testRunStats()
	{
		self::assertSame(['ok' => 0, 'failed' => 0, 'successRate' => null, 'avgDuration' => null, 'maxDuration' => 1.0, 'totalDuration' => 0.0], TCronModule::getRunStats(['max' => 1]));
		self::assertSame(['ok' => 1, 'failed' => 1, 'successRate' => 0.5, 'avgDuration' => 1.5, 'maxDuration' => null, 'totalDuration' => 3.0], TCronModule::getRunStats(['ok' => 1, 'failed' => 1, 'total' => 3]));
	}

	public function testDaemonWaitTakesTheEarliestLimit()
	{
		$module = $this->newTestModule();
		$module->setDaemonIdle(30);
		$module->setDaemonLifetime(0);
		$start = PradoUnit::invoke($module, 'getMonotonicClock')->seconds();
		self::assertSame(30.0, PradoUnit::invoke($module, 'getDaemonWait', $start), 'no trigger and no lifetime: the idle');
		$module->setDaemonLifetime(10);
		self::assertEqualsWithDelta(10.0, PradoUnit::invoke($module, 'getDaemonWait', $start), 0.5, 'the remaining lifetime');
		PradoUnit::setProp($module, '_daemonRetiring', true);
		self::assertSame(30.0, PradoUnit::invoke($module, 'getDaemonWait', $start), 'a retiring daemon has no lifetime left to wait for');
		PradoUnit::setProp($module, '_daemonRetiring', false);
		$module = $this->newTestModule([$this->job('waitSoon', '0 0 1 1 ? 2098')]);
		$module->setDaemonIdle(30);
		$module->setDaemonLifetime(0);
		$module->fakeMicrotime = (float) ($module->getNextPendingTime() - 5);
		self::assertEqualsWithDelta(5.0, PradoUnit::invoke($module, 'getDaemonWait', $start), 0.01, 'the next trigger');
	}

	public function testTransportAndMutexTypes()
	{
		$module = $this->_modules[] = new TCronModule();
		foreach (['setWakeTransport' => new TCronWakeFile(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cronwake-' . bin2hex(random_bytes(4))), 'setDaemonMutex' => new TMemoryMutex()] as $setter => $valid) {
			$module->$setter('moduleId');
			$module->$setter($valid);
			$module->$setter(null);
			foreach ([5, new \stdClass()] as $invalid) {
				try {
					$module->$setter($invalid);
					self::fail($setter . ' accepted a ' . get_debug_type($invalid));
				} catch (TConfigurationException $e) {
					self::assertStringContainsString(get_debug_type($invalid), $e->getMessage());
				}
			}
		}
	}

	public function testAPausedFileThatCannotBeReadPausesNothing()
	{
		if (function_exists('posix_getuid') && posix_getuid() === 0) {
			self::markTestSkipped('root reads any file');
		}
		$module = $this->newModule([$this->job('unreadablePause')]);
		$file = PradoUnit::invoke($module, 'getPausedFile');
		try {
			file_put_contents($file, json_encode(['unreadablePause']));
			chmod($file, 0o000);
			self::assertSame([], $module->getPausedTasks());
		} finally {
			@chmod($file, 0o600);
			@unlink($file);
		}
	}

	public function testBeginStatusRemovesTheEndedStatusesOfThisHostOnly()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBaseBegin');
		$module->setStatusPath($dir = $this->statusDir());
		$module->setEnableStatus(true);
		$module->init(null);
		mkdir($dir);
		$host = (string) gethostname();
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'ended.json', json_encode(['module' => 'cronBaseBegin', 'host' => $host, 'pid' => 999999999]));
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'alive.json', json_encode(['module' => 'cronBaseBegin', 'host' => $host, 'pid' => getmypid()]));
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'remote.json', json_encode(['module' => 'cronBaseBegin', 'host' => 'another-host.invalid', 'pid' => 1, 'updated' => 0]));
		try {
			self::assertTrue(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP));
			self::assertFalse(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP), 'a status is begun once');
			self::assertFileDoesNotExist($dir . DIRECTORY_SEPARATOR . 'ended.json');
			self::assertFileExists($dir . DIRECTORY_SEPARATOR . 'alive.json');
			self::assertFileExists($dir . DIRECTORY_SEPARATOR . 'remote.json', 'another host checks its own processes');
		} finally {
			PradoUnit::invoke($module, 'endStatus');
		}
	}

	public function testWatchDaemonSignalsOnALoopWithoutSignals()
	{
		if (!extension_loaded('pcntl')) {
			self::markTestSkipped('the signal watch needs pcntl');
		}
		$module = $this->newModule();
		$loop = $this->createMock(\Prado\IO\Async\IEventLoop::class);
		$loop->method('onSignal')->willThrowException(new \Prado\Exceptions\TNotSupportedException('eventloop_signals_unsupported'));
		self::assertSame([], PradoUnit::invoke($module, 'watchDaemonSignals', $loop));
		$loop = $this->createMock(\Prado\IO\Async\IEventLoop::class);
		$loop->method('onSignal')->willReturnOnConsecutiveCalls('sigterm', 'sigint');
		self::assertSame(['sigterm', 'sigint'], PradoUnit::invoke($module, 'watchDaemonSignals', $loop));
	}

	public function testTasksByTypeFilterSortAndPage()
	{
		$module = $this->newModule([
			$this->job('typeB'),
			$this->job('typeA'),
			$this->job('otherType', '0 0 1 1 ? 2099', \Prado\Util\Cron\TCronMethodTask::class),
		]);
		self::assertSame(['typeA', 'typeB'], array_keys($module->getTasksByType(TTestCronModuleTask::class)));
		self::assertSame(['typeB', 'typeA'], array_keys($module->getTasksByType(TTestCronModuleTask::class, null, 0, 0, false)));
		self::assertSame(['typeB'], array_keys($module->getTasksByType(TTestCronModuleTask::class, null, 1, 1)));
		self::assertSame([], $module->getTasksByType(TTestCronModuleTask::class, 'nothing%'));
	}

	public function testLogCronDaemonStartWithAndWithoutWatching()
	{
		$module = $this->newModule();
		PradoUnit::invoke($module, 'logCronDaemonStart', 'cron:daemon:test', false);
		PradoUnit::invoke($module, 'logCronDaemonStart', 'cron:daemon:test', true);
		$this->addToAssertionCount(1);
	}

	public function testMetricsOfOneTask()
	{
		$metrics = $this->newModule([$this->job('metricTask')])->getMonitorMetrics();
		self::assertStringContainsString('prado_cron_task_running{', $metrics);
		self::assertStringContainsString('task="metricTask"', $metrics);
	}

	public function testGetTaskResultsOfEachCombinationOfRecordedValues()
	{
		$app = Prado::getApplication();
		foreach ([false, true] as $hasTime) {
			foreach ([false, true] as $hasDuration) {
				foreach ([false, true] as $hasMax) {
					$name = 'combo' . (int) $hasTime . (int) $hasDuration . (int) $hasMax;
					$info = ['lastExecTime' => $hasTime ? 100 : null, 'processCount' => 0];
					if ($hasDuration) {
						$info['lastDuration'] = 1.5;
					}
					if ($hasMax) {
						$info['maxDuration'] = 2.5;
					}
					$app->setGlobalState(TCronModule::TASKS_INFO, [$name => $info]);
					$result = $this->newModule([$this->job($name)])->getTaskResults()[$name];
					self::assertSame([$hasTime ? 100 : null, $hasDuration ? 1.5 : null, $hasMax ? 2.5 : null], [$result['time'], $result['duration'], $result['max']]);
				}
			}
		}
	}

	public function testEnableStatusEmptyIsUnset()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setEnableStatus(true);
		$module->setEnableStatus('');
		self::assertNull($module->getEnableStatus());
	}

	public function testWaitForWakeSleepsWhenTheTransportIsUnavailable()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setWakeTransport(new TCronWakeFile(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'no-such-dir-' . bin2hex(random_bytes(4)) . DIRECTORY_SEPARATOR . 'cronwake'));
		self::assertFalse(PradoUnit::invoke($module, 'waitForWake', microtime(true) - 1));
	}

	public function testReadTasksInfoFreshFromAStoreWithoutTaskInformation()
	{
		$app = Prado::getApplication();
		$persister = PradoUnit::getProp($app, '_statePersister');
		$store = new class () implements \Prado\IStatePersister {
			public mixed $state = null;

			public function load()
			{
				return $this->state;
			}

			public function save($state)
			{
			}
		};
		try {
			PradoUnit::setProp($app, '_statePersister', $store);
			$module = $this->newModule();
			self::assertSame([], PradoUnit::invoke($module, 'readTasksInfo', true), 'a state that is no array');
			$store->state = ['other' => 1];
			self::assertSame([], PradoUnit::invoke($module, 'readTasksInfo', true), 'a state without task information');
			$store->state = [TCronModule::TASKS_INFO => 'not a list'];
			self::assertSame([], PradoUnit::invoke($module, 'readTasksInfo', true), 'task information that is no array');
			$store->state = [TCronModule::TASKS_INFO => ['stored' => ['lastExecTime' => 1]]];
			self::assertSame(['stored' => ['lastExecTime' => 1]], PradoUnit::invoke($module, 'readTasksInfo', true));
		} finally {
			PradoUnit::setProp($app, '_statePersister', $persister);
		}
	}

	public function testGetStatusesOfAnotherModuleOnly()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setID('cronBaseOnlyOther');
		$module->setStatusPath($dir = $this->statusDir());
		mkdir($dir);
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'other.json', json_encode(['module' => 'anotherModule']));
		self::assertSame([], $module->getStatuses());
		unlink($dir . DIRECTORY_SEPARATOR . 'other.json');
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'broken.json', 'not json');
		self::assertSame([], $module->getStatuses());
	}

	public function testSweepWithAnInCronShellSetting()
	{
		$module = $this->newTestModule([$this->job('futureInCron')]);
		$module->setInCronShell(false);
		self::assertSame(0, PradoUnit::invoke($module, 'sweepPendingTasks'), 'nothing due');
		$module->attachBehavior('deny', new TTestCronDenyingBehavior(['dyGetPendingTasks']));
		self::assertSame(0, PradoUnit::invoke($module, 'sweepPendingTasks'), 'denied');
	}

	public function testDaemonConfigFilesOfAnEmptyConfigurationFileName()
	{
		$app = Prado::getApplication();
		$file = PradoUnit::getProp($app, '_configFile');
		try {
			PradoUnit::setProp($app, '_configFile', '');
			self::assertSame([], $this->newModule()->getDaemonConfigFiles());
		} finally {
			PradoUnit::setProp($app, '_configFile', $file);
		}
	}

	public function testGetNextTriggersFreshOfOneTask()
	{
		$app = Prado::getApplication();
		foreach (['0 0 1 1 ? 2098', '@never'] as $schedule) {
			foreach ([true, false] as $recorded) {
				$name = 'freshOne' . bin2hex(random_bytes(3));
				$module = $this->newModule([$this->job($name, $schedule)]);
				$module->getTasks();
				$info = $app->getGlobalState(TCronModule::TASKS_INFO, []);
				if ($recorded) {
					$info[$name]['lastExecTime'] = gmmktime(0, 0, 0, 1, 1, 2097);
				} else {
					unset($info[$name]);
				}
				$app->setGlobalState(TCronModule::TASKS_INFO, $info);
				$app->saveGlobals();
				self::assertSame(($schedule === '@never') ? [] : [$name], array_keys($module->getNextTriggers(10, true)));
			}
		}
	}

	public function testTasksByTypeOfOneTask()
	{
		foreach ([TTestCronModuleTask::class, \Prado\Util\Cron\TCronMethodTask::class] as $class) {
			foreach ([true, false] as $ascending) {
				foreach ([0, 1] as $pageSize) {
					$module = $this->newModule([$this->job('oneTyped', '0 0 1 1 ? 2099', $class)]);
					$expected = ($class === TTestCronModuleTask::class) ? ['oneTyped'] : [];
					self::assertSame($expected, array_keys($module->getTasksByType(TTestCronModuleTask::class, null, $pageSize, 0, $ascending)));
				}
			}
		}
		foreach ([true, false] as $ascending) {
			foreach ([0, 1] as $pageSize) {
				self::assertSame([], $this->newModule()->getTasksByType(TTestCronModuleTask::class, null, $pageSize, 0, $ascending));
			}
		}
	}

	public function testBeginStatusWithOneStatusFile()
	{
		$host = (string) gethostname();
		foreach ([
			[null, true],
			[['host' => $host, 'pid' => 999999999], false],
			[['host' => $host, 'pid' => getmypid()], true],
			[['host' => 'another-host.invalid', 'pid' => 1, 'updated' => 0], true],
		] as [$status, $kept]) {
			$module = $this->_modules[] = new TCronModule();
			$module->setID('cronBaseOneStatus');
			$module->setStatusPath($dir = $this->statusDir());
			$module->setEnableStatus(true);
			mkdir($dir);
			if ($status !== null) {
				file_put_contents($dir . DIRECTORY_SEPARATOR . 'one.json', json_encode(['module' => 'cronBaseOneStatus'] + $status));
			}
			try {
				self::assertTrue(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP));
				if ($status !== null) {
					self::assertSame($kept, is_file($dir . DIRECTORY_SEPARATOR . 'one.json'));
				}
			} finally {
				PradoUnit::invoke($module, 'endStatus');
			}
		}
		$module = $this->_modules[] = new TCronModule();
		self::assertFalse(PradoUnit::invoke($module, 'beginStatus', TCronModule::STATUS_ROLE_SWEEP), 'without EnableStatus');
	}

	public function testMetricsWithARunningDaemon()
	{
		$module = $this->_modules[] = new class () extends TCronModule {
			public function getMonitorReport(?array $tasks = null): array
			{
				return ['module' => 'cronMetrics', 'daemon' => ['pid' => 1], 'processes' => [['pid' => 1]], 'tasks' => []];
			}
		};
		self::assertStringContainsString('prado_cron_daemon_up{module="cronMetrics"} 1', $module->getMonitorMetrics());
	}

	public function testTimeZoneEmptyIsUnset()
	{
		$module = $this->_modules[] = new TCronModule();
		$module->setTimeZone('UTC');
		$module->setTimeZone('');
		self::assertNull($module->getTimeZone());
	}

	public function testDaemonConfigFilesIsTheApplicationConfigurationFile()
	{
		$app = Prado::getApplication();
		$file = PradoUnit::getProp($app, '_configFile');
		try {
			PradoUnit::setProp($app, '_configFile', '/srv/app/application.xml');
			self::assertSame(['/srv/app/application.xml'], $this->newModule()->getDaemonConfigFiles());
		} finally {
			PradoUnit::setProp($app, '_configFile', $file);
		}
	}

	public function testRunDaemonWithoutAWakeTransport()
	{
		$module = $this->_modules[] = new class () extends TCronModule {
			public function getWakeTransport(): ?\Prado\Util\Cron\Wake\ICronWakeTransport
			{
				return null;
			}
		};
		$module->setID('cronBaseNoWake' . bin2hex(random_bytes(3)));
		$module->setDaemonMutex(new TMemoryMutex());
		$module->init(null);
		$watching = null;
		$module->attachEventHandler('OnDaemonStart', static function ($sender, $param) use (&$watching) {
			$watching = $param->getWatching();
			$sender->stopDaemon();
		});
		self::assertSame(0, $module->runDaemon());
		self::assertFalse($watching, 'without a transport the daemon watches no wakes');
	}
}
