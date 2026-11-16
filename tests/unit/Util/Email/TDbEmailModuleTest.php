<?php

namespace Prado\Test\Unit\Util\Email;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\Email\TDbEmailModule;
use Prado\Util\Email\TEmail;
use Prado\Util\Email\TEmailBounce;
use Prado\Util\Email\TEmailModule;
use Prado\Util\IDbModule;

class TDbEmailModuleTest extends TestCase
{
	private function email(string $to = 'ann@example.com'): TEmail
	{
		$email = new TEmail();
		$email->setTo($to);
		$email->setFrom('app@example.com');
		$email->setSubject('Hello');
		$email->setBody('<html><body>Hi {{NAME}}</body></html>');
		$email->setTextBody('Hi {{NAME}}');
		return $email;
	}

	public function testIsDbModule()
	{
		$module = new TDbEmailModuleTestModule();
		$this->assertInstanceOf(TEmailModule::class, $module);
		$this->assertInstanceOf(IDbModule::class, $module);
		$this->assertTrue($module->getLogging());
		$this->assertFalse($module->getOfflineEmail());
		$this->assertNull($module->getServiceID());
		$this->assertSame('emails', $module->getTableName());
		$this->assertTrue($module->getAutoCreateEmailTable());
	}

	public function testSendLogsEmail()
	{
		$module = new TDbEmailModuleTestModule();
		$email = $this->email();
		$this->assertSame(1, $module->sendEmail($email, ['NAME' => 'Ann']));
		$this->assertCount(1, $module->sent);
		$row = $module->getEmailByTrackingId($email->getTrackingId());
		$this->assertSame(TDbEmailModule::STATUS_SENT, $row['status']);
		$this->assertSame('<ann@example.com>', $row['recipients']);
		$this->assertSame($email->getMessageId(), $row['message_id']);
		$this->assertSame('<html><body>Hi {{NAME}}</body></html>', $row['body']);
		$this->assertNotNull($row['sent_time']);
		$this->assertSame($row, $module->getEmailByUID($email->getUID()));
		$this->assertSame($row, $module->getEmailByMessageId('<' . $email->getMessageId() . '@example.com>'));
	}

	public function testFailedSendIsLogged()
	{
		$module = new TDbEmailModuleTestModule();
		$module->mailerResult = false;
		$email = $this->email();
		$module->sendEmail($email, null);
		$this->assertSame(TDbEmailModule::STATUS_FAILED, $module->getEmailByTrackingId($email->getTrackingId())['status']);
	}

	public function testOfflineQueuesAndSendsPending()
	{
		$module = new TDbEmailModuleTestModule();
		$module->setOfflineEmail(true);
		$email = $this->email();
		$email->addAttachment('note.txt', ['data' => "bin\0ary", 'name' => 'note.txt']);
		$this->assertTrue($module->sendEmail($email, ['NAME' => 'Ann']));
		$this->assertCount(0, $module->sent);
		$this->assertCount(1, $module->getPendingEmails());

		$this->assertSame(1, $module->sendPendingEmails());
		$this->assertCount(1, $module->sent);
		$sent = $module->sent[0];
		$this->assertSame('<html><body>Hi Ann</body></html>', $sent->getBody());
		$this->assertSame('Hello', $sent->getSubject());
		$this->assertSame($email->getTrackingId(), $sent->getTrackingId());
		$this->assertSame("bin\0ary", $sent->getAttachments()['note.txt']['data']);
		$this->assertCount(0, $module->getPendingEmails());
		$this->assertSame(TDbEmailModule::STATUS_SENT, $module->getEmailByTrackingId($email->getTrackingId())['status']);
		$this->assertSame(1, $module->getEmailLogCount());
	}

	public function testTracking()
	{
		$module = new TDbEmailModuleTestModule();
		$email = $this->email();
		$link = $email->addTrackedLink('https://example.com/welcome?name={{NAME}}');
		$this->assertSame($link, $email->addTrackedLink('https://example.com/welcome?name={{NAME}}'));
		$module->sendEmail($email, ['NAME' => 'Ann']);
		$id = $email->getTrackingId();
		$events = [];
		foreach (['onOpenEmail', 'onClickEmail', 'onUnsubscribe'] as $event) {
			$module->attachEventHandler($event, function ($sender, $param) use (&$events, $event) {
				$events[] = $event;
			});
		}

		$this->assertNotNull($module->recordOpen($id));
		$this->assertSame('https://example.com/welcome?name=Ann', $module->recordClick($id, $link));
		$this->assertNull($module->recordClick($id, 5));
		$this->assertNull($module->recordClick($id, 'x'));
		$this->assertNull($module->recordOpen('unknown'));
		$this->assertNotNull($module->recordUnsubscribe($id));

		$row = $module->getEmailByTrackingId($id);
		$this->assertSame(1, (int) $row['open_count']);
		$this->assertSame(1, (int) $row['click_count']);
		$this->assertNotNull($row['opened_time']);
		$this->assertNotNull($row['unsubscribed_time']);
		$this->assertSame(['onOpenEmail', 'onClickEmail', 'onUnsubscribe'], $events);
	}

	public function testBounceRecordsLoggedEmail()
	{
		$module = new TDbEmailModuleTestModule();
		$email = $this->email();
		$module->sendEmail($email, null);
		$raised = [];
		$module->attachEventHandler('onBounce', function ($sender, $bounce) use (&$raised) {
			$raised[] = $bounce;
		});

		$dsn = "Content-Type: multipart/report; boundary=b\n\n--b\nContent-Type: message/delivery-status\n\n"
			. 'X-Original-Message-ID: <' . $email->getMessageId() . "@example.com>\n\n"
			. "Final-Recipient: rfc822; ann@example.com\nAction: failed\nStatus: 5.1.1\nDiagnostic-Code: smtp; 550 No such user\n--b--\n";
		$bounces = $module->processBounceMessage($dsn);

		$this->assertCount(1, $bounces);
		$this->assertSame($bounces, $raised);
		$row = $module->getEmailByTrackingId($email->getTrackingId());
		$this->assertSame(TDbEmailModule::STATUS_BOUNCED, $row['status']);
		$this->assertSame('5.1.1', $row['bounce_status']);
		$this->assertSame('550 No such user', $row['bounce_diagnostic']);
	}

	public function testTransientBounceKeepsStatus()
	{
		$module = new TDbEmailModuleTestModule();
		$email = $this->email();
		$module->sendEmail($email, null);
		$module->onBounce(new TEmailBounce('ann@example.com', 'delayed', '4.2.2', '', $email->getMessageId()));
		$row = $module->getEmailByTrackingId($email->getTrackingId());
		$this->assertSame(TDbEmailModule::STATUS_SENT, $row['status']);
		$this->assertSame('4.2.2', $row['bounce_status']);
	}

	public function testEmailLogAndClear()
	{
		$module = new TDbEmailModuleTestModule();
		foreach (['a', 'b', 'a'] as $user) {
			$email = $this->email();
			$email->setUsername($user);
			$module->sendEmail($email, null);
		}
		$module->setOfflineEmail(true);
		$module->sendEmail($this->email(), null);

		$this->assertSame(4, $module->getEmailLogCount());
		$this->assertSame(2, $module->getEmailLogCount('a'));
		$log = $module->getEmailLog('a', 1);
		$this->assertCount(1, $log);
		$this->assertSame('a', $log[0]['username']);

		$this->assertSame(3, $module->clearEmailLog(-10));
		$this->assertSame(1, $module->getEmailLogCount());
		$this->assertSame(1, $module->clearEmailLog(-10, TDbEmailModule::STATUS_PENDING));
	}

	public function testRemoveEmailLogItem()
	{
		$module = new TDbEmailModuleTestModule();
		$email = $this->email();
		$module->sendEmail($email, null);
		$row = $module->getEmailByTrackingId($email->getTrackingId());
		$this->assertSame(1, $module->removeEmailLogItem($row['email_id']));
		$this->assertNull($module->getEmailByTrackingId($email->getTrackingId()));
	}

	public function testTrackingUrl()
	{
		$module = new TDbEmailModuleTestModule();
		$module->setServiceID('email');
		$module->setServiceUrl('https://example.com/index.php');
		$this->assertSame('https://example.com/index.php?email=abc&click=2', $module->getTrackingUrl('abc', ['click' => 2]));
		$module->setServiceUrl('https://example.com/index.php?lang=en');
		$this->assertSame('https://example.com/index.php?lang=en&email=abc', $module->getTrackingUrl('abc'));

		$module->setServiceID(null);
		$this->expectException(TConfigurationException::class);
		$module->getTrackingUrl('abc');
	}

	public function testNormalizeMessageId()
	{
		$this->assertSame('abc', TDbEmailModule::normalizeMessageId('<abc@example.com>'));
		$this->assertSame('abc', TDbEmailModule::normalizeMessageId(' abc '));
		$this->assertSame('', TDbEmailModule::normalizeMessageId(null));
	}

	public function testTableNameIsValidated()
	{
		$module = new TDbEmailModuleTestModule();
		$module->setTableName('mail_log');
		$this->assertSame('mail_log', $module->getTableName());
		$this->expectException(TInvalidDataValueException::class);
		$module->setTableName('x; DROP TABLE y');
	}

	public function testPropertiesLockAfterInit()
	{
		$module = new TDbEmailModuleTestModule();
		$property = new \ReflectionProperty(TDbEmailModule::class, '_dbInitialized');
		$property->setValue($module, true);
		$this->expectException(TInvalidOperationException::class);
		$module->setTableName('other');
	}

	public function testCronTaskInfos()
	{
		$module = new TDbEmailModuleTestModule();
		$names = array_map(fn ($info) => $info->getName(), $module->fxGetCronTaskInfos(null, null));
		$this->assertSame(['emailsender', 'emailcleaner'], $names);
		$this->assertTrue($module->getAutoGlobalListen());
	}

	public function testPermissions()
	{
		$names = array_map(fn ($p) => $p->getName(), (new TDbEmailModuleTestModule())->getPermissions(null));
		$this->assertSame([TDbEmailModule::PERM_EMAIL_SHELL, TDbEmailModule::PERM_EMAIL_LOG_READ, TDbEmailModule::PERM_EMAIL_LOG_DELETE], $names);
	}
}
