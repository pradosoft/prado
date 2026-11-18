<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Email\TEmailModule;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TLogException;
use Prado\Test\Unit\Email\TEmailModuleCleanupTrait;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TEmailLogRoute;
use Prado\Util\Log\TLogger;

/**
 * A TEmailLogRoute that records each mail instead of sending it.
 */
class TRecordingEmailLogRoute extends TEmailLogRoute
{
	public array $sent = [];

	public bool $accept = true;

	protected function sendMail(string $email, string $subject, string $message, string $headers): bool
	{
		$this->sent[] = [$email, $subject, $message, $headers];
		return $this->accept;
	}
}

/**
 * A TEmailLogRoute whose email module is given by the test.
 */
class TModuleEmailLogRoute extends TRecordingEmailLogRoute
{
	public ?TEmailModule $module = null;

	protected function getEmailModule(): ?TEmailModule
	{
		return $this->module ?? parent::getEmailModule();
	}

	public function process(array $logs): void
	{
		$this->processLogs($logs, true, []);
	}
}

class TEmailLogRouteTest extends \PHPUnit\Framework\TestCase
{
	use TEmailModuleCleanupTrait {
		tearDown as private releaseEmailModules;
	}

	private $sendmailFrom;

	protected function setUp(): void
	{
		$this->sendmailFrom = ini_get('sendmail_from');
	}

	protected function tearDown(): void
	{
		ini_set('sendmail_from', $this->sendmailFrom);
		$this->releaseEmailModules();
	}

	private function logEntry(string $message): array
	{
		return [TLogger::LOG_MESSAGE => $message, TLogger::LOG_LEVEL => TLogger::ERROR, TLogger::LOG_CATEGORY => 'Test', TLogger::LOG_TIME => 1700000000.0, TLogger::LOG_MEMORY => 0, TLogger::LOG_PID => 1, TLogger::LOG_CONTROL => null];
	}

	public function testEmailModuleSendsTheLogs()
	{
		$module = $this->newEmailModule();
		$module->setFrom('Module <module@example.com>');
		$module->setTransport($transport = new TCaptureTransport());
		$route = new TModuleEmailLogRoute();
		$route->module = $module;
		$this->assertSame('', $route->getEmailModuleID());
		$route->setEmailModuleID('email');
		$this->assertSame('email', $route->getEmailModuleID());
		$route->setEmails('admin@example.com, ops@example.com');
		$route->setSubject('Application Error');
		ini_set('sendmail_from', '');
		$route->init(null);
		$route->process([$this->logEntry('Disk is full {{email:to}}')]);
		$this->assertSame([], $route->sent, 'mail() is not used');
		$this->assertCount(2, $transport->sent);
		$this->assertSame('admin@example.com', $transport->sent[0]->getTo()[0]->getAddress());
		$this->assertSame('ops@example.com', $transport->sent[1]->getTo()[0]->getAddress());
		$this->assertSame('Application Error', $transport->sent[0]->getSubject());
		$this->assertSame('module@example.com', $transport->sent[0]->getFrom()->getAddress(), 'the From of the module fills an empty SentFrom');
		$this->assertStringContainsString('{{email:to}}', $transport->sent[0]->getText(), 'log text is not searched for placeholders');
		$this->assertStringContainsString('[Error] [Test] Disk is', $transport->sent[0]->getText());
		$this->assertNull($transport->sent[0]->getHtml());

		$route->setSentFrom('app@example.com');
		$route->process([$this->logEntry('Again')]);
		$this->assertSame('app@example.com', $transport->sent[2]->getFrom()->getAddress());
	}

	public function testEmailModuleFailureThrows()
	{
		$module = $this->newEmailModule();
		$module->setFrom('module@example.com');
		$module->setTransport($transport = new TCaptureTransport());
		$transport->failures[] = new TEmailTransportException('smtptransport_command_failed', 'RCPT', '550 no such user');
		$route = new TModuleEmailLogRoute();
		$route->module = $module;
		$route->setEmails('admin@example.com');
		$this->expectException(TLogException::class);
		$route->process([$this->logEntry('Disk is full')]);
	}

	public function testEmailModuleIdMustBeAnEmailModule()
	{
		$route = new TModuleEmailLogRoute();
		$route->setEmails('admin@example.com');
		$route->setEmailModuleID('no-such-module');
		$route->init(null);
		try {
			$route->process([$this->logEntry('Disk is full')]);
			$this->fail('an EmailModuleID that is not an email module throws.');
		} catch (TConfigurationException $e) {
			$this->assertSame('emaillogroute_emailmoduleid_invalid', $e->getErrorCode());
		}
	}

	public function testInitKeepsSentFrom()
	{
		$route = new TRecordingEmailLogRoute();
		$route->setSentFrom('app@example.com');
		ini_set('sendmail_from', 'ini@example.com');
		$route->init(null);
		$this->assertSame('app@example.com', $route->getSentFrom());
	}

	public function testInitReadsSendmailFrom()
	{
		$route = new TRecordingEmailLogRoute();
		ini_set('sendmail_from', 'ini@example.com');
		$route->init(null);
		$this->assertSame('ini@example.com', $route->getSentFrom());
	}

	public function testInitWithoutSenderThrows()
	{
		$route = new TRecordingEmailLogRoute();
		ini_set('sendmail_from', '');
		try {
			$route->init(null);
			$this->fail('TEmailLogRoute::init() without a sender throws.');
		} catch (TConfigurationException $e) {
			$this->assertSame('emaillogroute_sentfrom_required', $e->getErrorCode());
		}
	}

	public function testSubjectDefaultsToDefaultSubject()
	{
		$route = new TEmailLogRoute();
		$this->assertSame(TEmailLogRoute::DEFAULT_SUBJECT, $route->getSubject());

		$route->setSubject('Errors');
		$this->assertSame('Errors', $route->getSubject());

		$route->setSubject(null);
		$this->assertSame(TEmailLogRoute::DEFAULT_SUBJECT, $route->getSubject());
	}

	public function testEmails()
	{
		$route = new TEmailLogRoute();
		$this->assertSame([], $route->getEmails());

		$route->setEmails(' a@example.com , not-an-address, b.c+d@mail.example.org ');
		$this->assertSame(['a@example.com', 'b.c+d@mail.example.org'], $route->getEmails());

		$route->setEmails(['x@example.com', 'y@example.com']);
		$this->assertSame(['x@example.com', 'y@example.com'], $route->getEmails());
	}

	public function testProcessLogsSendsOneMailPerAddress()
	{
		$route = new TRecordingEmailLogRoute();
		$route->setEmails('a@example.com, b@example.com');
		$route->setSentFrom('app@example.com');
		$route->setSubject('Log');

		$logs = [
			TLogEntryFactory::entry('first', TLogger::ERROR),
			TLogEntryFactory::entry(str_repeat('word ', 30), TLogger::FATAL),
		];
		PradoUnit::invoke($route, 'processLogs', $logs, true, []);

		$this->assertCount(2, $route->sent);
		[$email, $subject, $message, $headers] = $route->sent[0];
		$this->assertSame('a@example.com', $email);
		$this->assertSame('b@example.com', $route->sent[1][0]);
		$this->assertSame('Log', $subject);
		$this->assertStringContainsString('[Error] [Test\Category] first', $message);
		foreach (explode("\n", $message) as $line) {
			$this->assertLessThanOrEqual(70, strlen($line));
		}
		$returnPath = ini_get('sendmail_path') ? "Return-Path:app@example.com\r\n" : '';
		$this->assertSame("From:app@example.com\r\n" . $returnPath, $headers);
	}

	public function testProcessLogsThrowsWhenMailFails()
	{
		$route = new TRecordingEmailLogRoute();
		$route->setEmails('a@example.com, b@example.com');
		$route->setSentFrom('app@example.com');
		$route->accept = false;
		try {
			PradoUnit::invoke($route, 'processLogs', [TLogEntryFactory::entry()], true, []);
			$this->fail('A refused mail throws TLogException.');
		} catch (TLogException $e) {
			$this->assertSame('emaillogroute_mail_failed', $e->getErrorCode());
			$this->assertStringContainsString('a@example.com', $e->getMessage());
		}
		$this->assertCount(1, $route->sent);
	}

	public function testEmailsOfOneAddress()
	{
		$route = new TEmailLogRoute();
		$route->setEmails('a@example.com');
		$this->assertSame(['a@example.com'], $route->getEmails());
		$route->setEmails('bogus');
		$this->assertSame([], $route->getEmails());
	}

	public function testEmailsChecksEachAddressOfAnArray()
	{
		$route = new TEmailLogRoute();
		$route->setEmails([' x@example.com ', 'not-an-address', '', 'y@example.com']);
		$this->assertSame(['x@example.com', 'y@example.com'], $route->getEmails());
	}

	public function testEmailsAcceptLongTopLevelDomains()
	{
		$route = new TEmailLogRoute();
		$route->setEmails('admin@example.technology, ops@example.museum');
		$this->assertSame(['admin@example.technology', 'ops@example.museum'], $route->getEmails());
	}
}
