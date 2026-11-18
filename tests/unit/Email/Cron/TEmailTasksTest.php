<?php

namespace Prado\Test\Unit\Email\Cron;

use PHPUnit\Framework\TestCase;
use Prado\Email\Cron\TEmailBounceTask;
use Prado\Email\Cron\TEmailCleanupTask;
use Prado\Email\Cron\TEmailQueueTask;
use Prado\Email\Mailbox\IEmailMailbox;
use Prado\Email\Mailbox\TImapMailbox;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailSendMode;
use Prado\Email\TEmailStatus;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;

/**
 * A mailbox of fixed messages that records what is marked seen.
 */
class TEmailTasksTestMailbox implements IEmailMailbox
{
	public array $seen = [];

	public bool $opened = false;

	public bool $closed = false;

	public function __construct(public array $messages)
	{
	}

	public function open(): void
	{
		$this->opened = true;
	}

	public function getUnseenMessageIds(): array
	{
		return array_map('strval', array_keys($this->messages));
	}

	public function fetchMessage(string $id): string
	{
		if ($this->messages[$id] instanceof \Throwable) {
			throw $this->messages[$id];
		}
		return $this->messages[$id];
	}

	public function markSeen(string $id): void
	{
		$this->seen[] = $id;
	}

	public function close(): void
	{
		$this->closed = true;
	}
}

class TEmailTasksTest extends TestCase
{
	use \Prado\Test\Unit\Email\TEmailModuleCleanupTrait;

	private TCaptureTransport $_transport;

	private TSqliteEmailStore $_store;

	private function module(bool $withStore = true): TEmailModule
	{
		$module = $this->newEmailModule();
		$module->setTransport($this->_transport = new TCaptureTransport());
		if ($withStore) {
			$module->setStore($this->_store = new TSqliteEmailStore(1700000000));
		}
		return $module;
	}

	private function bounceTask(TEmailModule $module, ?TEmailTasksTestMailbox $mailbox = null): TEmailBounceTask
	{
		return new class ($module, $mailbox) extends TEmailBounceTask {
			public function __construct(private TEmailModule $_module, private ?TEmailTasksTestMailbox $_mailbox)
			{
				parent::__construct();
			}

			protected function getEmailModule(): TEmailModule
			{
				return $this->_module;
			}

			protected function createMailbox(): IEmailMailbox
			{
				return $this->_mailbox ?? parent::createMailbox();
			}

			public function mailbox(): IEmailMailbox
			{
				return parent::createMailbox();
			}
		};
	}

	private function email(string $to): TEmail
	{
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo($to);
		$email->setSubject('Hello');
		$email->setText('Hi');
		return $email;
	}

	public function testQueueTask()
	{
		$module = $this->module();
		$module->setSendMode(TEmailSendMode::Queued);
		foreach (['a@example.com', 'b@example.com', 'c@example.com'] as $to) {
			$module->sendEmail($this->email($to));
		}
		$task = new class ($module) extends TEmailQueueTask {
			public function __construct(private TEmailModule $_module)
			{
				parent::__construct();
			}

			protected function getEmailModule(): TEmailModule
			{
				return $this->_module;
			}
		};
		$this->assertSame(0, $task->getLimit());
		$task->setLimit('2');
		$task->execute(null);
		$this->assertCount(2, $this->_transport->sent);
		$this->assertSame(1, $this->_transport->closes);
		$task->execute(null);
		$this->assertCount(3, $this->_transport->sent);
		$this->assertSame(3, $this->_store->countEmails(null, TEmailStatus::Sent));
		$task->setLimit(-5);
		$this->assertSame(0, $task->getLimit());
	}

	public function testCleanupTask()
	{
		$module = $this->module();
		$module->sendEmail($this->email('a@example.com'));
		$this->_store->clock->setTime(1700000000 + 86400 * 100);
		$module->sendEmail($this->email('b@example.com'));
		$task = new class ($module) extends TEmailCleanupTask {
			public function __construct(private TEmailModule $_module)
			{
				parent::__construct();
			}

			protected function getEmailModule(): TEmailModule
			{
				return $this->_module;
			}
		};
		$this->assertSame(7776000, $task->getTimePeriod());
		$this->assertNull($task->getStatus());
		$task->execute(null);
		$this->assertSame(1, $this->_store->countEmails());
		$task->setTimePeriod(0);
		$task->setStatus('failed');
		$this->assertSame(TEmailStatus::Failed, $task->getStatus());
		$task->execute(null);
		$this->assertSame(1, $this->_store->countEmails(), 'only the named status is deleted');
		$task->setStatus('');
		$task->execute(null);
		$this->assertSame(0, $this->_store->countEmails());
	}

	public function testCleanupTaskNeedsAStore()
	{
		$module = $this->module(false);
		$task = new class ($module) extends TEmailCleanupTask {
			public function __construct(private TEmailModule $_module)
			{
				parent::__construct();
			}

			protected function getEmailModule(): TEmailModule
			{
				return $this->_module;
			}
		};
		$this->expectException(TConfigurationException::class);
		$task->execute(null);
	}

	public function testBounceTask()
	{
		$module = $this->module();
		$email = $this->email('ann@example.com');
		$module->sendEmail($email);
		$bounce = "X-Failed-Recipients: ann@example.com\r\nIn-Reply-To: <" . $email->getMessageId() . ">\r\nSubject: Mail delivery failed\r\n\r\nFailed.";
		$mailbox = new TEmailTasksTestMailbox([4 => $bounce, 7 => "Subject: hello\r\n\r\nNot a bounce.", 9 => $bounce]);
		$task = $this->bounceTask($module, $mailbox);
		$this->assertSame(100, $task->getLimit());
		$task->setLimit(2);
		$task->execute(null);
		$this->assertTrue($mailbox->opened);
		$this->assertTrue($mailbox->closed);
		$this->assertSame(['4', '7'], $mailbox->seen);
		$this->assertSame(['ann@example.com'], $this->_store->getSuppressed(['ann@example.com']));
	}

	public function testBounceTaskClosesTheMailboxOnFailure()
	{
		$module = $this->module();
		$mailbox = new TEmailTasksTestMailbox([4 => new \RuntimeException('connection lost')]);
		$task = $this->bounceTask($module, $mailbox);
		try {
			$task->execute(null);
			$this->fail('a mailbox failure is rethrown');
		} catch (\RuntimeException $e) {
			$this->assertTrue($mailbox->closed);
			$this->assertSame([], $mailbox->seen);
		}
	}

	public function testBounceTaskMailboxFromDsn()
	{
		$task = $this->bounceTask($this->module(false));
		$this->assertSame('', $task->getDsn());
		$task->setDsn('imaps://user:pass@imap.example.com/Bounces');
		$mailbox = $task->mailbox();
		$this->assertInstanceOf(TImapMailbox::class, $mailbox);
		$this->assertSame('imap.example.com', $mailbox->getHost());
		$this->assertSame('Bounces', $mailbox->getMailbox());
	}

	public function testModuleIdMustBeAnEmailModule()
	{
		$task = new TEmailQueueTask();
		$task->setModuleId('no-such-module');
		$this->expectException(TInvalidDataValueException::class);
		$task->execute(null);
	}

	public function testApplicationWithoutEmailModuleThrows()
	{
		$this->expectException(TConfigurationException::class);
		(new TEmailQueueTask())->execute(null);
	}
}
