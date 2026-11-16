<?php

namespace Prado\Test\Unit\Util\Email\Cron;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Util\Email\TDbEmailModuleTestModule;
use Prado\Util\Email\Cron\TIMAPBouncedTask;
use Prado\Util\Email\Mailbox\IEmailMailbox;
use Prado\Util\Email\TEmailModule;

class TIMAPBouncedTaskTestMailbox implements IEmailMailbox
{
	public array $seen = [];
	public bool $closed = false;

	public function __construct(public array $messages)
	{
	}

	public function open(): void
	{
	}

	public function getUnseenMessageIds(): array
	{
		return array_map('strval', array_keys($this->messages));
	}

	public function fetchMessage(string $id): string
	{
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

class TIMAPBouncedTaskTestTask extends TIMAPBouncedTask
{
	public ?TIMAPBouncedTaskTestMailbox $mailbox = null;
	public ?TEmailModule $module = null;

	protected function createMailbox(): IEmailMailbox
	{
		return $this->mailbox;
	}

	protected function getEmailerModule(): TEmailModule
	{
		return $this->module ?? parent::getEmailerModule();
	}
}

class TIMAPBouncedTaskTest extends TestCase
{
	public function testProcessesAndMarksUnseenMessages()
	{
		$dsn = "Content-Type: multipart/report; boundary=b\n\n--b\nContent-Type: message/delivery-status\n\n\nFinal-Recipient: rfc822; a@example.com\nAction: failed\nStatus: 5.1.1\n--b--\n";
		$task = new TIMAPBouncedTaskTestTask();
		$task->mailbox = new TIMAPBouncedTaskTestMailbox([3 => $dsn, 4 => "Subject: hi\n\nnot a bounce\n", 5 => $dsn]);
		$task->module = new TDbEmailModuleTestModule();
		$task->setLimit(2);
		$bounces = [];
		$task->module->attachEventHandler('onBounce', function ($sender, $bounce) use (&$bounces) {
			$bounces[] = $bounce->getRecipient();
		});
		$task->execute(null);
		$this->assertSame(['3', '4'], $task->mailbox->seen);
		$this->assertSame(['a@example.com'], $bounces);
		$this->assertTrue($task->mailbox->closed);
	}

	public function testProperties()
	{
		$task = new TIMAPBouncedTask();
		$this->assertSame('', $task->getDsn());
		$task->setDsn('imaps://u:p@imap.example.com/INBOX');
		$this->assertSame('imaps://u:p@imap.example.com/INBOX', $task->getDsn());
		$this->assertSame(100, $task->getLimit());
		$task->setLimit(-5);
		$this->assertSame(0, $task->getLimit());
	}

	public function testInvalidModuleId()
	{
		$task = new TIMAPBouncedTaskTestTask();
		$task->mailbox = new TIMAPBouncedTaskTestMailbox([]);
		$task->setModuleId('no-such-module');
		$this->expectException(TInvalidDataValueException::class);
		$task->execute(null);
	}
}
