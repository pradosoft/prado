<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Data\TDbConnection;
use Prado\Email\Cron\TEmailQueueTask;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailSendMode;
use Prado\Email\TEmailStatus;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Util\Cron\TTestCronDenyingBehavior;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Cron\TDbCronManager;

/**
 * A TDbCronManager on an in-memory SQLite database that counts the wakes it signals.
 */
class TEmailCronWakeTestCron extends TDbCronManager
{
	public int $wakes = 0;

	public bool $refuse = false;

	protected function getCustomDbConnection(): ?TDbConnection
	{
		return new TDbConnection('sqlite::memory:');
	}

	public function signalWake(): bool
	{
		$this->wakes++;
		return true;
	}

	public function addSystemTask($task): bool
	{
		if ($this->refuse) {
			throw new \RuntimeException('the cron table is locked');
		}
		return parent::addSystemTask($task);
	}
}

class TEmailCronWakeTest extends TestCase
{
	use TEmailModuleCleanupTrait {
		tearDown as private releaseEmailModules;
	}

	private const NOW = 1700000000;

	private ?TTestApplication $_app = null;

	private TEmailModule $_module;

	private TSqliteEmailStore $_store;

	private TCaptureTransport $_transport;

	private TEmailCronWakeTestCron $_cron;

	private TMockClock $_clock;

	protected function setUp(): void
	{
		$this->_app = new TTestApplication();
		$this->_module = $this->newEmailModule();
		$this->_module->setID('email');
		$this->_module->setClock($this->_clock = (new TMockClock())->setTime(self::NOW));
		$this->_module->setTransport($this->_transport = new TCaptureTransport());
		$this->_module->setStore($this->_store = $this->newEmailStore(self::NOW));
		$this->_cron = new TEmailCronWakeTestCron();
		$this->_cron->setID('cron');
		$this->_app->setModule('email', $this->_module);
		$this->_app->setModule('cron', $this->_cron);
	}

	protected function tearDown(): void
	{
		$this->_cron->unlisten();
		$this->releaseEmailModules();
		$this->_app?->restoreApplication();
		$this->_app = null;
	}

	private function email(string $to = 'ann@example.com'): TEmail
	{
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo($to);
		$email->setSubject('Hello');
		$email->setText('Hi');
		return $email;
	}

	public function testDefaults()
	{
		$module = $this->newEmailModule();
		$this->assertTrue($module->getWakeCron());
		$this->assertSame('', $module->getCronID());
		$this->assertSame($this->_cron, $module->getCron(), 'the first TDbCronManager of the application');
		$this->assertSame('cron', $module->getCronID());
		$module->setWakeCron('false');
		$this->assertFalse($module->getWakeCron());
	}

	public function testCronId()
	{
		$module = $this->newEmailModule();
		$module->setCronID('cron');
		$this->assertSame('cron', $module->getCronID());
		$this->assertSame($this->_cron, $module->getCron());
		$module->setCronID('email');
		try {
			$module->getCron();
			$this->fail('a CronID that is not a TDbCronManager throws');
		} catch (TConfigurationException $e) {
			$this->assertSame('emailmodule_cronid_invalid', $e->getErrorCode());
		}
		$module->setCronID('');
		$module->setCron($this->_cron);
		$this->assertSame($this->_cron, $module->getCron());
	}

	private function wakeTask(int $time): mixed
	{
		return $this->_cron->getTask(TEmailModule::WAKE_TASK_PREFIX . $time);
	}

	private function sentTo(): array
	{
		return array_map(fn ($e) => $e->getTo()[0]->getAddress(), $this->_transport->sent);
	}

	public function testQueuedEmailAddsASelfRemovingTaskAndCronDeliversIt()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->assertSame(TEmailStatus::Queued, $this->_module->sendEmail($this->email('ann@example.com')));
		$task = $this->wakeTask(self::NOW);
		$this->assertInstanceOf(TEmailQueueTask::class, $task);
		$this->assertSame('@' . self::NOW, $task->getSchedule());
		$this->assertSame('email', $task->getModuleId());
		$this->assertTrue($task->getAutoRemove());
		$this->assertEmpty($task->getUserName(), 'the task runs as the default user of cron');
		$this->assertSame(1, $this->_cron->wakes, 'adding the earliest due task signals the wake');
		$this->assertSame([], $this->_transport->sent);

		$this->_cron->processPendingTasks();
		$this->assertSame(['ann@example.com'], $this->sentTo());
		$this->assertSame(1, $this->_store->countEmails(null, TEmailStatus::Sent));
		$this->assertEmpty($this->wakeTask(self::NOW), 'the task removes itself');
	}

	public function testLaterEmailsOfTheSecondGetTheTaskOfTheNextSecond()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_module->sendEmail($this->email('ann@example.com'));
		$this->_cron->processPendingTasks();
		$this->assertSame(['ann@example.com'], $this->sentTo(), 'the task of this second ran and is gone');

		$this->_module->sendEmail($this->email('bob@example.com'));
		$this->assertEmpty($this->wakeTask(self::NOW), 'the task of this second may have read the queue already');
		$next = $this->wakeTask(self::NOW + 1);
		$this->assertInstanceOf(TEmailQueueTask::class, $next);
		$this->assertSame('@' . (self::NOW + 1), $next->getSchedule());
		$this->_module->sendEmail($this->email('cat@example.com'));
		$this->assertEmpty($this->wakeTask(self::NOW + 2), 'the task of the next second takes every email of this one');

		$this->_clock->setTime(self::NOW + 1);
		$this->_module->sendEmail($this->email('dan@example.com'));
		$this->assertInstanceOf(TEmailQueueTask::class, $this->wakeTask(self::NOW + 2), 'the task of a second that began may have started');

		$this->_cron->processPendingTasks();
		$this->assertSame(['ann@example.com', 'bob@example.com', 'cat@example.com', 'dan@example.com'], $this->sentTo());
		$this->assertEmpty($this->wakeTask(self::NOW + 1));
		$this->assertEmpty($this->wakeTask(self::NOW + 2));
	}

	public function testRequestsOfOneSecondShareOneTask()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_module->sendEmail($this->email('ann@example.com'));
		$this->_module->endRequest();
		$this->_module->sendEmail($this->email('bob@example.com'));
		$this->assertSame(1, $this->_cron->wakes, 'the second request finds the task of this second waiting');
		$this->assertEmpty($this->wakeTask(self::NOW + 1));
		$this->assertSame(1, $this->wakeTaskRows(self::NOW));

		$this->_cron->processPendingTasks();
		$this->assertSame(['ann@example.com', 'bob@example.com'], $this->sentTo(), 'one run delivers both');
		$this->assertSame(0, $this->wakeTaskRows(self::NOW));
	}

	private function wakeTaskRows(int $time): int
	{
		$command = $this->_cron->getDbConnection()->createCommand('SELECT COUNT(*) FROM ' . $this->_cron->getTableName() . ' WHERE name=:name AND active IS NOT NULL');
		$command->bindValue(':name', TEmailModule::WAKE_TASK_PREFIX . $time);
		return (int) $command->queryScalar();
	}

	public function testTwoRowsOfOneNameRunOnce()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_module->sendEmail($this->email('ann@example.com'));
		$table = $this->_cron->getTableName();
		$columns = 'name, schedule, task, moduleid, username, options, lastexectime, nextexectime, processcount, active';
		$copy = $this->_cron->getDbConnection()->createCommand("INSERT INTO {$table} ({$columns}) SELECT {$columns} FROM {$table} WHERE name=:name AND active IS NOT NULL");
		$copy->bindValue(':name', TEmailModule::WAKE_TASK_PREFIX . self::NOW);
		$copy->execute();
		$this->assertSame(2, $this->wakeTaskRows(self::NOW), 'two processes added the task at the same moment');

		$this->_cron->processPendingTasks();
		$this->assertSame(['ann@example.com'], $this->sentTo());
		$this->assertSame(0, $this->wakeTaskRows(self::NOW));
		$this->_cron->processPendingTasks();
		$this->assertCount(1, $this->_transport->sent);
	}

	public function testRequestOfAUserWithoutCronPermissionsWakesCron()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_cron->attachBehavior('denies', new TTestCronDenyingBehavior(['dyAddTask', 'dyAddTaskGlobal']));
		$denied = new TEmailQueueTask();
		$denied->setName('denied');
		$denied->setSchedule('@' . self::NOW);
		$denied->setModuleId('email');
		$this->assertNull($this->_cron->addTask($denied), 'the user, such as a guest, may not add a cron task');

		$this->assertSame(TEmailStatus::Queued, $this->_module->sendEmail($this->email()));
		$this->assertInstanceOf(TEmailQueueTask::class, $this->wakeTask(self::NOW));
		$this->assertSame(1, $this->_cron->wakes);
		$this->_cron->processPendingTasks();
		$this->assertSame(['ann@example.com'], $this->sentTo());
	}

	public function testEndOfRequestAllowsTheNextWake()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_module->sendEmail($this->email());
		$this->_cron->processPendingTasks();
		$this->_module->endRequest();
		$this->_module->sendEmail($this->email('bob@example.com'));
		$this->assertInstanceOf(TEmailQueueTask::class, $this->_cron->getTask(TEmailModule::WAKE_TASK_PREFIX . self::NOW));
		$this->assertSame(2, $this->_cron->wakes);
	}

	public function testRetryIsWokenAtTheNextAttempt()
	{
		$this->_store->setRetryDelay(90);
		$this->_transport->failures[] = (new TEmailTransportException('smtptransport_connection_failed', 'smtp.example.com', 'timeout'))->setIsTransient(true);
		$this->assertSame(TEmailStatus::Queued, $this->_module->sendEmail($this->email()));
		$this->assertEmpty($this->_cron->getTask(TEmailModule::WAKE_TASK_PREFIX . self::NOW));
		$task = $this->_cron->getTask(TEmailModule::WAKE_TASK_PREFIX . (self::NOW + 90));
		$this->assertInstanceOf(TEmailQueueTask::class, $task);
		$this->assertSame('@' . (self::NOW + 90), $task->getSchedule());
	}

	public function testImmediateDeliveryAddsNoTask()
	{
		$this->assertSame(TEmailStatus::Sent, $this->_module->sendEmail($this->email()));
		$this->assertSame(0, $this->_cron->wakes);
		$this->assertEmpty($this->_cron->getTask(TEmailModule::WAKE_TASK_PREFIX . self::NOW));
	}

	public function testWakeCronOffLeavesTheEmailForTheSchedule()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_module->setWakeCron(false);
		$this->assertSame(TEmailStatus::Queued, $this->_module->sendEmail($this->email()));
		$this->assertEmpty($this->_cron->getTask(TEmailModule::WAKE_TASK_PREFIX . self::NOW));
		$this->assertSame(0, $this->_cron->wakes);
		$this->assertSame(1, $this->_store->countEmails(null, TEmailStatus::Queued));
	}

	public function testRefusedTaskDoesNotFailTheSend()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_cron->refuse = true;
		$this->assertSame(TEmailStatus::Queued, $this->_module->sendEmail($this->email()));
		$this->assertSame(1, $this->_store->countEmails(null, TEmailStatus::Queued));
	}
}
