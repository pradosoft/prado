<?php

namespace Prado\Test\Unit\Util\Email\Shell;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Util\Email\TDbEmailModuleTestModule;
use Prado\Util\Email\Shell\TShellEmailerAction;
use Prado\Util\Email\TEmail;

class TShellEmailerActionTestAction extends TShellEmailerAction
{
	public $input;

	protected function getInputStream()
	{
		return $this->input;
	}
}

class TShellEmailerActionTest extends TestCase
{
	private TShellEmailerActionTestAction $action;
	private TDbEmailModuleTestModule $module;
	private TTextWriter $output;

	protected function setUp(): void
	{
		$this->module = new TDbEmailModuleTestModule();
		$this->output = new TTextWriter();
		$this->action = new TShellEmailerActionTestAction();
		$this->action->setWriter(new TShellWriter($this->output));
		$this->action->setEmailerModule($this->module);
	}

	private function email(): TEmail
	{
		$email = new TEmail();
		$email->setTo('ann@example.com');
		$email->setFrom('app@example.com');
		$email->setSubject('Hello');
		$email->setTextBody('Text body');
		return $email;
	}

	public function testValidActions()
	{
		$this->assertSame('send', $this->action->isValidAction(['email/send']));
		$this->assertSame('bounce', $this->action->isValidAction(['email/bounce']));
		$this->assertNull($this->action->isValidAction(['email/show']));
		$this->assertSame('show', $this->action->isValidAction(['email/show', 'abc']));
	}

	public function testSendPending()
	{
		$this->module->setOfflineEmail(true);
		$this->module->sendEmail($this->email(), null);
		$this->assertTrue($this->action->actionSend(['email/send']));
		$this->assertStringContainsString('Sent 1 pending emails.', $this->output->flush());
	}

	public function testLogShowAndFlush()
	{
		$email = $this->email();
		$this->module->sendEmail($email, null);
		$this->action->actionLog(['email/log', '5']);
		$this->assertStringContainsString($email->getTrackingId(), $this->output->flush());
		$this->action->actionShow(['email/show', $email->getTrackingId()]);
		$out = $this->output->flush();
		$this->assertStringContainsString('Hello', $out);
		$this->assertStringContainsString('Text body', $out);
		$this->action->actionFlush(['email/flush', '-1']);
		$this->assertStringContainsString('Deleted 1 logged emails.', $this->output->flush());
	}

	public function testBounceFromInput()
	{
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, "X-Failed-Recipients: gone@example.org\n\n550 5.1.1 No such user\n");
		rewind($stream);
		$this->action->input = $stream;
		$this->action->actionBounce(['email/bounce']);
		$out = $this->output->flush();
		$this->assertStringContainsString('gone@example.org 5.1.1 failed', $out);
		$this->assertStringContainsString('1 bounced recipients.', $out);
	}
}
