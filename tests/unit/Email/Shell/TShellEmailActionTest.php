<?php

namespace Prado\Test\Unit\Email\Shell;

use PHPUnit\Framework\TestCase;
use Prado\Email\Shell\TShellEmailAction;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailSendMode;
use Prado\Email\Transport\TEmailTransportException;
use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;

class TShellEmailActionTest extends TestCase
{
	use \Prado\Test\Unit\Email\TEmailModuleCleanupTrait;

	private TShellEmailAction $_action;

	private TEmailModule $_module;

	private TSqliteEmailStore $_store;

	private TCaptureTransport $_transport;

	private TTextWriter $_output;

	/** @var resource the stream the action reads as standard input. */
	private $_input;

	protected function setUp(): void
	{
		$this->_module = $this->newEmailModule();
		$this->_module->setFrom('Shop <shop@example.com>');
		$this->_module->setTransport($this->_transport = new TCaptureTransport());
		$this->_module->setStore($this->_store = new TSqliteEmailStore(1700000000));
		$this->_output = new TTextWriter();
		$this->_input = fopen('php://memory', 'r+');
		$input = $this->_input;
		$this->_action = new class ($input) extends TShellEmailAction {
			public function __construct(private $_input)
			{
				parent::__construct();
			}

			protected function getInputStream()
			{
				return $this->_input;
			}
		};
		$this->_action->setWriter(new TShellWriter($this->_output));
		$this->_action->setEmailModule($this->_module);
	}

	private function email(string $to = 'ann@example.com'): TEmail
	{
		$email = new TEmail();
		$email->setTo($to);
		$email->setSubject('Hello');
		$email->setText('Text of the email');
		$email->setUsername('ann');
		return $email;
	}

	public function testValidActions()
	{
		$this->assertSame('send', $this->_action->isValidAction(['email/send']));
		$this->assertSame('log', $this->_action->isValidAction(['email/log']));
		$this->assertSame('bounce', $this->_action->isValidAction(['email/bounce']));
		$this->assertNull($this->_action->isValidAction(['email/show']));
		$this->assertSame('show', $this->_action->isValidAction(['email/show', 'abc']));
		$this->assertNull($this->_action->isValidAction(['email/test']));
		$this->assertSame('test', $this->_action->isValidAction(['email/test', 'ann@example.com']));
	}

	public function testSend()
	{
		$this->_module->setSendMode(TEmailSendMode::Queued);
		$this->_module->sendEmail($this->email());
		$this->assertTrue($this->_action->actionSend(['email/send']));
		$this->assertStringContainsString('Sent 1 queued emails.', $this->_output->flush());
		$this->assertCount(1, $this->_transport->sent);
		$this->assertSame(1, $this->_transport->closes);
	}

	public function testLog()
	{
		$this->_action->actionLog(['email/log']);
		$this->assertStringContainsString('No stored emails.', $this->_output->flush());
		$first = $this->email();
		$this->_module->sendEmail($first);
		$other = $this->email('bob@example.com');
		$other->setUsername('bob');
		$this->_module->sendEmail($other);
		$this->_action->actionLog(['email/log']);
		$output = $this->_output->flush();
		$this->assertStringContainsString($first->getTrackingId(), $output);
		$this->assertStringContainsString($other->getTrackingId(), $output);
		$this->assertStringContainsString('"Hello"', $output);
		$this->assertStringContainsString('Sent', $output);
		$this->_action->actionLog(['email/log', '@bob', '5']);
		$output = $this->_output->flush();
		$this->assertStringNotContainsString($first->getTrackingId(), $output);
		$this->assertStringContainsString($other->getTrackingId(), $output);
		$this->_action->actionLog(['email/log', '1']);
		$this->assertStringNotContainsString($first->getTrackingId(), $this->_output->flush());
	}

	public function testShow()
	{
		$this->_transport->failures[] = new TEmailTransportException('smtptransport_command_failed', 'RCPT', '550 no such user');
		$email = $this->email();
		$this->_module->sendEmail($email);
		$this->_action->actionShow(['email/show', $email->getTrackingId()]);
		$output = $this->_output->flush();
		$this->assertStringContainsString('ann@example.com', $output);
		$this->assertStringContainsString('Hello', $output);
		$this->assertStringContainsString('Failed', $output);
		$this->assertStringContainsString('550 no such user', $output);
		$this->assertStringContainsString('Text of the email', $output);
		$this->_action->actionShow(['email/show', '0123456789abcdef']);
		$this->assertStringContainsString('No email has the tracking ID', $this->_output->flush());
	}

	public function testFlush()
	{
		$this->_module->sendEmail($this->email());
		$this->_store->clock->setTime(1700000000 + 86400 * 3);
		$this->_action->actionFlush(['email/flush', 'soon']);
		$this->assertStringContainsString('must be a number', $this->_output->flush());
		$this->_action->actionFlush(['email/flush', '5']);
		$this->assertStringContainsString('Deleted 0 stored emails.', $this->_output->flush());
		$this->_action->actionFlush(['email/flush', '2']);
		$this->assertStringContainsString('Deleted 1 stored emails.', $this->_output->flush());
	}

	public function testBounceFromStandardInputAndFile()
	{
		$email = $this->email();
		$this->_module->sendEmail($email);
		$bounce = "X-Failed-Recipients: ann@example.com\r\nIn-Reply-To: <" . $email->getMessageId() . ">\r\nSubject: Mail delivery failed\r\n\r\nFailed.";
		fwrite($this->_input, $bounce);
		rewind($this->_input);
		$this->_action->actionBounce(['email/bounce']);
		$output = $this->_output->flush();
		$this->assertStringContainsString('ann@example.com', $output);
		$this->assertStringContainsString('1 bounced recipients.', $output);
		$this->assertSame(['ann@example.com'], $this->_store->getSuppressed(['ann@example.com']));

		$file = tempnam(sys_get_temp_dir(), 'bounce');
		file_put_contents($file, "Subject: hi\r\n\r\nNot a bounce.");
		try {
			$this->_action->actionBounce(['email/bounce', $file]);
			$this->assertStringContainsString('0 bounced recipients.', $this->_output->flush());
		} finally {
			unlink($file);
		}
		$this->_action->actionBounce(['email/bounce', $file]);
		$this->assertStringContainsString('not found.', $this->_output->flush());
	}

	public function testTest()
	{
		$this->_action->actionTest(['email/test', 'ann@example.com']);
		$this->assertStringContainsString('The test email is sent.', $this->_output->flush());
		$this->assertSame('PRADO email test', $this->_transport->sent[0]->getSubject());
		$this->assertSame('shop@example.com', $this->_transport->sent[0]->getFrom()->getAddress());

		$this->_transport->failures[] = new TEmailTransportException('smtptransport_command_failed', 'RCPT', '550 no such user');
		$this->_action->actionTest(['email/test', 'gone@example.com']);
		$this->assertStringContainsString('550 no such user', $this->_output->flush());

		$this->_action->actionTest(['email/test', 'not an address']);
		$this->assertStringContainsString('invalid', $this->_output->flush());
	}

	public function testWithoutStore()
	{
		$this->_module->setStore(null);
		foreach (['actionSend' => ['email/send'], 'actionLog' => ['email/log'], 'actionShow' => ['email/show', 'x'], 'actionFlush' => ['email/flush', '1']] as $method => $args) {
			$this->assertTrue($this->_action->$method($args));
			$this->assertStringContainsString('has no store', $this->_output->flush());
		}
	}

	public function testWithoutModule()
	{
		$this->_action->setEmailModule(null);
		$this->assertNull($this->_action->getEmailModule());
		$this->assertStringContainsString('is not found', $this->_output->flush());
	}
}
