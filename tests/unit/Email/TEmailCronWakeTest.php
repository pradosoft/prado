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

	public function addTask($task, $runtime = false)
	{
		if ($this->refuse) {
			throw new \RuntimeException('the cron table is locked');
		}
		return parent::addTask($task, $runtime);
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

	protected function setUp(): void
	{
		$this->_app = new TTestApplication();
		$this->_module = $this->newEmailModule();
		$this->_module->setID('email');
		$this->_module->setClock((new TMockClock())->setTime(self::NOW));
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

	public function testQueuedEmailAddsAOneShotTaskAndCronDeliversIt()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$name = TEmailModule::WAKE_TASK_PREFIX . self::NOW;
		$first = $this->email('ann@example.com');
		$this->assertSame(TEmailStatus::Queued, $this->_module->sendEmail($first));
		$task = $this->_cron->getTask($name);
		$this->assertInstanceOf(TEmailQueueTask::class, $task);
		$this->assertSame('@' . self::NOW, $task->getSchedule());
		$this->assertSame('email', $task->getModuleId());
		$this->assertTrue($task->getAutoRemove());
		$this->assertSame(1, $this->_cron->wakes, 'adding the earliest due task signals the wake');

		$second = $this->email('bob@example.com');
		$this->_module->sendEmail($second);
		$this->assertSame(1, $this->_cron->wakes, 'a task for the same time is added once');
		$this->assertSame([], $this->_transport->sent);

		$this->_cron->processPendingTasks();
		$this->assertSame(['ann@example.com', 'bob@example.com'], array_map(fn ($e) => $e->getTo()[0]->getAddress(), $this->_transport->sent));
		$this->assertSame(2, $this->_store->countEmails(null, TEmailStatus::Sent));
		$this->assertEmpty($this->_cron->getTask($name), 'the one-shot task removes itself');
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
