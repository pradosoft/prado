<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\IEmailMailer;
use Prado\Email\TEmail;
use Prado\Email\TEmailApplicationBehavior;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailSendMode;
use Prado\Email\TEmailStatus;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Email\Transport\TMailTransport;
use Prado\Email\UI\TEmailBody;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Email\Store\TEmailSuppressionReason;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;
use Prado\Xml\TXmlDocument;

class TEmailModuleTest extends TestCase
{
	use TEmailModuleCleanupTrait;

	private TCaptureTransport $_transport;

	private function module(?TSqliteEmailStore $store = null): TEmailModule
	{
		$module = $this->newEmailModule();
		$module->setBasePath(__DIR__ . '/Fixtures/Emails');
		$module->setTransport($this->_transport = new TCaptureTransport());
		$module->setStore($store);
		return $module;
	}

	private function email(string $to = 'ann@example.com'): TEmail
	{
		$email = new TEmail();
		$email->setFrom('Shop <shop@example.com>');
		$email->setTo($to);
		$email->setSubject('Hello {{NAME}}');
		$email->setHtml('<p>Hello {{NAME}}</p>');
		$email->setData(['NAME' => 'Ann']);
		return $email;
	}

	private function stored(TSqliteEmailStore $store, TEmail $email): ?string
	{
		return $store->getEmailInfo($email->getTrackingId())['status'] ?? null;
	}

	private function transient(): TEmailTransportException
	{
		return (new TEmailTransportException('smtptransport_connection_failed', 'smtp.example.com', 'timeout'))->setIsTransient(true);
	}

	public function testDefaults()
	{
		$module = $this->newEmailModule();
		$this->assertInstanceOf(IEmailMailer::class, $module);
		$this->assertInstanceOf(TMailTransport::class, $module->getTransport());
		$this->assertNull($module->getStore());
		$this->assertSame('', $module->getStoreID());
		$this->assertSame(TEmailSendMode::Immediate, $module->getSendMode());
		$this->assertSame(TEmailBody::class, $module->getBodyClass());
		$this->assertNull($module->getFrom());
		$this->assertNull($module->getReturnPath());
		$this->assertSame([], $module->getReplyTo());
		$this->assertSame([], $module->getCc());
		$this->assertSame([], $module->getBcc());
		$this->assertSame('', $module->getMessageIdDomain());
	}

	public function testInitLoadsTransportAndRules()
	{
		$xml = new TXmlDocument();
		$xml->loadFromString('<module><transport class="' . TCaptureTransport::class . '" Label="primary" />'
			. '<emails Email.ReplyTo="reply@example.com"><email id="Order.*" Number="7" /></emails></module>');
		$module = $this->newEmailModule();
		$module->setBasePath(__DIR__ . '/Fixtures/Emails');
		$module->init($xml);
		$transport = $module->getTransport();
		$this->assertInstanceOf(TCaptureTransport::class, $transport);
		$this->assertSame('primary', $transport->getLabel());
		$this->assertNotFalse($transport->config);
		$this->assertSame(['Email.ReplyTo' => 'reply@example.com', 'Number' => '7'], $module->getConfiguration()->getEmailProperties('Order.Shipped'));

		$app = Prado::getApplication();
		$this->assertInstanceOf(TEmailApplicationBehavior::class, $app->asa(TEmailModule::BEHAVIOR_NAME));
		$email = $app->createEmail('Order.Shipped');
		$this->assertSame(7, $email->getBody()->getNumber());
		$this->assertSame('reply@example.com', $email->getReplyTo()[0]->getAddress());
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$this->assertSame(TEmailStatus::Sent, $app->sendEmail($email));
		$this->assertCount(1, $transport->sent);

		$this->expectException(TInvalidOperationException::class);
		$module->setBasePath(__DIR__);
	}

	public function testInitPhpConfiguration()
	{
		$module = $this->newEmailModule();
		$module->init(['transport' => ['class' => TCaptureTransport::class, 'properties' => ['Label' => 'php']], 'emails' => ['rules' => ['*' => ['Email.List' => 'all']]]]);
		$this->assertSame('php', $module->getTransport()->getLabel());
		$this->assertSame(['Email.List' => 'all'], $module->getConfiguration()->getEmailProperties('Simple'));
	}

	public function testInitTransportErrors()
	{
		foreach ([
			'<module><transport Label="x" /></module>',
			'<module><transport class="' . TEmail::class . '" /></module>',
			'<module><transport class="' . TCaptureTransport::class . '" /><transport class="' . TCaptureTransport::class . '" /></module>',
		] as $config) {
			$xml = new TXmlDocument();
			$xml->loadFromString($config);
			try {
				$this->newEmailModule()->init($xml);
				$this->fail('an invalid transport configuration throws: ' . $config);
			} catch (TConfigurationException $e) {
				$this->assertStringContainsString('transport', strtolower($e->getMessage()));
			}
		}
	}

	public function testSendImmediate()
	{
		$module = $this->module();
		$events = [];
		$module->attachEventHandler('onSendEmail', function ($sender, $param) use (&$events) {
			$events[] = 'send:' . $param->getEmail()->getSubject();
		});
		$module->attachEventHandler('onEmailSent', function ($sender, $param) use (&$events) {
			$events[] = 'sent:' . $param->getEmail()->getStatus();
		});
		$email = $this->email();
		$email->setMailer($module);
		$this->assertSame(TEmailStatus::Sent, $email->send());
		$this->assertSame([$email], $this->_transport->sent);
		$this->assertSame(TEmailStatus::Sent, $email->getStatus());
		$this->assertTrue($email->getIsFinal());
		$this->assertSame('Hello Ann', $email->getSubject());
		$this->assertSame('<p>Hello Ann</p>', $email->getHtml());
		$this->assertSame('Hello Ann', $email->getText());
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}@example\.com$/', $email->getMessageId());
		$this->assertEqualsWithDelta(time(), $email->getDate(), 5);
		$this->assertSame(['send:Hello Ann', 'sent:Sent'], $events);
	}

	public function testModuleDefaultsFillAndAdd()
	{
		$module = $this->module();
		$module->setFrom('Default <default@example.com>');
		$module->setReplyTo('reply@example.com');
		$module->setReturnPath('bounce@example.com');
		$module->setCc('audit@example.com');
		$module->setBcc('archive@example.com');
		$module->setMessageIdDomain('mail.example.org');

		$bare = new TEmail();
		$bare->setTo('ann@example.com');
		$bare->setText('Hi');
		$module->sendEmail($bare);
		$this->assertSame('default@example.com', $bare->getFrom()->getAddress());
		$this->assertSame('reply@example.com', $bare->getReplyTo()[0]->getAddress());
		$this->assertSame('bounce@example.com', $bare->getReturnPath()->getAddress());
		$this->assertSame(['audit@example.com'], array_map(fn ($a) => $a->getAddress(), $bare->getCc()));
		$this->assertSame(['archive@example.com'], array_map(fn ($a) => $a->getAddress(), $bare->getBcc()));
		$this->assertStringEndsWith('@mail.example.org', $bare->getMessageId());

		$own = $this->email();
		$own->setReplyTo('own-reply@example.com');
		$own->setReturnPath('own-bounce@example.com');
		$own->setCc('boss@example.com');
		$own->setMessageId('fixed@example.com');
		$own->setDate(1700000000);
		$module->sendEmail($own);
		$this->assertSame('shop@example.com', $own->getFrom()->getAddress());
		$this->assertSame('own-reply@example.com', $own->getReplyTo()[0]->getAddress());
		$this->assertSame('own-bounce@example.com', $own->getReturnPath()->getAddress());
		$this->assertSame(['boss@example.com', 'audit@example.com'], array_map(fn ($a) => $a->getAddress(), $own->getCc()));
		$this->assertSame('fixed@example.com', $own->getMessageId());
		$this->assertSame(1700000000, $own->getDate());
	}

	public function testFromAndRecipientRequired()
	{
		$module = $this->module();
		$noFrom = new TEmail();
		$noFrom->setTo('ann@example.com');
		try {
			$module->sendEmail($noFrom);
			$this->fail('an email needs a From');
		} catch (TConfigurationException $e) {
			$this->assertSame(TEmailStatus::New, $noFrom->getStatus());
		}
		$noRecipient = new TEmail();
		$noRecipient->setFrom('shop@example.com');
		$this->expectException(TConfigurationException::class);
		$module->sendEmail($noRecipient);
	}

	public function testResendThrows()
	{
		$module = $this->module();
		$email = $this->email();
		$module->sendEmail($email);
		$copy = clone $email;
		$this->assertSame(TEmailStatus::Sent, $module->sendEmail($copy));
		$this->assertNotSame($email->getMessageId(), $copy->getMessageId());
		$this->expectException(TInvalidOperationException::class);
		$module->sendEmail($email);
	}

	public function testCancel()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$module->attachEventHandler('onSendEmail', fn ($sender, $param) => $param->cancel());
		$email = $this->email();
		$this->assertSame(TEmailStatus::Cancelled, $module->sendEmail($email));
		$this->assertSame(TEmailStatus::Cancelled, $email->getStatus());
		$this->assertSame([], $this->_transport->sent);
		$this->assertSame(0, $store->countEmails());
	}

	public function testHandlerMayChangeTheEmail()
	{
		$module = $this->module();
		$module->attachEventHandler('onSendEmail', fn ($sender, $param) => $param->getEmail()->setHeader('X-Tagged', 'yes'));
		$email = $this->email();
		$module->sendEmail($email);
		$this->assertSame('yes', $this->_transport->sent[0]->getHeader('X-Tagged'));
	}

	public function testFailureWithoutStore()
	{
		$module = $this->module();
		$this->_transport->failures[] = $this->transient();
		$failed = [];
		$module->attachEventHandler('onEmailFailed', function ($sender, $param) use (&$failed) {
			$failed[] = [$param->getEmail()->getStatus(), $param->getException()::class];
		});
		$email = $this->email();
		$this->assertSame(TEmailStatus::Failed, $module->sendEmail($email));
		$this->assertSame(TEmailStatus::Failed, $email->getStatus());
		$this->assertStringContainsString('timeout', $email->getError());
		$this->assertSame([[TEmailStatus::Failed, TEmailTransportException::class]], $failed);
	}

	public function testOtherExceptionIsRecordedAndRethrown()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$this->_transport->failures[] = new \RuntimeException('disk full');
		$email = $this->email();
		try {
			$module->sendEmail($email);
			$this->fail('an exception that is not a refusal is rethrown');
		} catch (\RuntimeException $e) {
			$this->assertSame(TEmailStatus::Failed, $email->getStatus());
			$this->assertSame(TEmailStatus::Failed, $this->stored($store, $email));
			$this->assertSame('disk full', $store->getEmailInfo($email->getTrackingId())['error_text']);
		}
	}

	public function testImmediateWithStoreLogsAndRetries()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$store->setRetryDelay(0);
		$ok = $this->email();
		$this->assertSame(TEmailStatus::Sent, $module->sendEmail($ok));
		$this->assertSame(TEmailStatus::Sent, $this->stored($store, $ok));

		$this->_transport->failures[] = $this->transient();
		$retry = $this->email('bob@example.com');
		$this->assertSame(TEmailStatus::Queued, $module->sendEmail($retry));
		$this->assertSame(TEmailStatus::Queued, $retry->getStatus());
		$this->assertSame(TEmailStatus::Queued, $this->stored($store, $retry));

		$this->assertSame(1, $module->sendQueued());
		$this->assertSame(TEmailStatus::Sent, $this->stored($store, $retry));
		$this->assertCount(2, $this->_transport->sent);
		$this->assertSame('bob@example.com', $this->_transport->sent[1]->getTo()[0]->getAddress());
		$this->assertSame($retry->getMessageId(), $this->_transport->sent[1]->getMessageId());

		$this->_transport->failures[] = new TEmailTransportException('smtptransport_command_failed', 'RCPT', '550 no such user');
		$permanent = $this->email('gone@example.com');
		$this->assertSame(TEmailStatus::Failed, $module->sendEmail($permanent));
		$this->assertSame(TEmailStatus::Failed, $this->stored($store, $permanent));
	}

	public function testRetriesStopAtTheStoreLimit()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$store->setRetryDelay(0);
		$store->setMaxAttempts(2);
		$this->_transport->failures = [$this->transient(), $this->transient()];
		$email = $this->email();
		$this->assertSame(TEmailStatus::Queued, $module->sendEmail($email));
		$this->assertSame(0, $module->sendQueued());
		$this->assertSame(TEmailStatus::Failed, $this->stored($store, $email));
		$this->assertSame(0, $module->sendQueued());
	}

	public function testQueuedModeNeedsAStore()
	{
		$module = $this->module();
		$module->setSendMode('queued');
		$this->assertSame(TEmailSendMode::Queued, $module->getSendMode());
		try {
			$module->sendEmail($this->email());
			$this->fail('the Queued mode needs a store');
		} catch (TConfigurationException $e) {
		}
		$this->expectException(TConfigurationException::class);
		$module->sendQueued();
	}

	public function testQueuedMode()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$module->setSendMode(TEmailSendMode::Queued);
		$emails = [$this->email('ann@example.com'), $this->email('bob@example.com'), $this->email('cat@example.com')];
		foreach ($emails as $email) {
			$this->assertSame(TEmailStatus::Queued, $module->sendEmail($email));
		}
		$this->assertSame([], $this->_transport->sent);
		$this->assertSame(2, $module->sendQueued(2));
		$this->assertSame(1, $module->sendQueued());
		$this->assertSame(0, $module->sendQueued());
		$this->assertCount(3, $this->_transport->sent);
		foreach ($emails as $email) {
			$this->assertSame(TEmailStatus::Sent, $this->stored($store, $email));
		}
	}

	public function testDeferredModeWithoutStore()
	{
		$module = $this->module();
		$module->setSendMode(TEmailSendMode::Deferred);
		$email = $this->email();
		$this->assertSame(TEmailStatus::Queued, $module->sendEmail($email));
		$this->assertSame([], $this->_transport->sent);
		$module->endRequest();
		$this->assertSame([$email], $this->_transport->sent);
		$this->assertSame(TEmailStatus::Sent, $email->getStatus());
		$this->assertSame(1, $this->_transport->closes);
		$this->assertSame(0, $module->sendDeferred());
	}

	public function testDeferredModeFallsBackToTheQueue()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$store->setRetryDelay(0);
		$module->setSendMode(TEmailSendMode::Deferred);
		$lost = $this->email('ann@example.com');
		$module->sendEmail($lost);
		$this->assertSame(TEmailStatus::Queued, $this->stored($store, $lost), 'the email is stored before the end of the request');

		$retry = $this->email('bob@example.com');
		$module->sendEmail($retry);
		$this->_transport->failures[] = $this->transient();
		$this->assertSame(1, $module->sendDeferred());
		$this->assertSame(TEmailStatus::Queued, $this->stored($store, $lost));
		$this->assertSame(TEmailStatus::Sent, $this->stored($store, $retry));
		$this->assertSame(1, $module->sendQueued());
		$this->assertSame(TEmailStatus::Sent, $this->stored($store, $lost));
	}

	public function testDeferredSkipsAnEmailAnotherProcessClaimed()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$module->setSendMode(TEmailSendMode::Deferred);
		$email = $this->email();
		$module->sendEmail($email);
		$this->assertTrue($store->claimEmail($email), 'another process claims the email first');
		$this->assertSame(0, $module->sendDeferred());
		$this->assertSame([], $this->_transport->sent);
	}

	public function testSuppression()
	{
		$store = new TSqliteEmailStore();
		$store->addSuppression('bounced@example.com', TEmailSuppressionReason::Bounce);
		$store->addSuppression('left@example.com', TEmailSuppressionReason::Unsubscribe, 'news');
		$module = $this->module($store);
		$module->setBcc('archive@example.com');

		$mixed = $this->email('ann@example.com, Bounced@example.com');
		$this->assertSame(TEmailStatus::Sent, $module->sendEmail($mixed));
		$this->assertSame(['ann@example.com'], array_map(fn ($a) => $a->getAddress(), $mixed->getTo()));

		$transactional = $this->email('left@example.com');
		$this->assertSame(TEmailStatus::Sent, $module->sendEmail($transactional), 'an unsubscribe does not stop a transactional email');

		$news = $this->email('left@example.com');
		$news->setList('news');
		$this->assertSame(TEmailStatus::Suppressed, $module->sendEmail($news));
		$this->assertSame(TEmailStatus::Suppressed, $news->getStatus());
		$this->assertSame(TEmailStatus::Suppressed, $this->stored($store, $news));
		$this->assertSame([], $news->getBcc(), 'a suppressed email is not copied');
		$this->assertCount(2, $this->_transport->sent);
	}

	public function testListUnsubscribeNeedsOneRecipient()
	{
		$module = $this->module();
		$single = $this->email();
		$single->setList('news');
		$single->setListUnsubscribe('https://example.com/u');
		$this->assertSame(TEmailStatus::Sent, $module->sendEmail($single));

		$many = $this->email('ann@example.com, bob@example.com');
		$many->setList('news');
		$many->setListUnsubscribe('https://example.com/u');
		$this->expectException(TConfigurationException::class);
		$module->sendEmail($many);
	}

	public function testSendEach()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$store->addSuppression('left@example.com', TEmailSuppressionReason::Unsubscribe, 'news');
		$template = $module->createEmail('Simple');
		$template->setFrom('shop@example.com');
		$template->setData(['NAME' => 'friend']);
		$count = $module->sendEach($template, [
			'ann@example.com' => ['NAME' => 'Ann'],
			'left@example.com' => ['NAME' => 'Left'],
			'Bob <bob@example.com>' => [],
			'cat@example.com',
		]);
		$this->assertSame(3, $count);
		$sent = $this->_transport->sent;
		$this->assertCount(3, $sent);
		$this->assertSame(['ann@example.com', 'bob@example.com', 'cat@example.com'], array_map(fn ($e) => $e->getTo()[0]->getAddress(), $sent));
		$this->assertSame(['Hello Ann', 'Hello friend', 'Hello friend'], array_map(fn ($e) => $e->getSubject(), $sent));
		$this->assertStringContainsString('Hello Ann</p>', $sent[0]->getHtml());
		$this->assertSame('Hello Ann in text', $sent[0]->getText());
		$this->assertCount(3, array_unique(array_map(fn ($e) => $e->getTrackingId(), $sent)));
		$this->assertCount(3, array_unique(array_map(fn ($e) => $e->getMessageId(), $sent)));
		$this->assertSame('Bob', $sent[1]->getTo()[0]->getName());
		$this->assertFalse($template->getIsFinal());
		$this->assertSame(TEmailStatus::New, $template->getStatus());
		$this->assertStringContainsString('{{NAME}}', $template->getHtml());
	}

	public function testSendEachNeedsAFreshTemplate()
	{
		$module = $this->module();
		$email = $this->email();
		$email->finalize();
		$this->expectException(TInvalidOperationException::class);
		$module->sendEach($email, ['ann@example.com']);
	}

	public function testStoreId()
	{
		$module = $this->newEmailModule();
		$module->setStoreID('no-such-module');
		$this->assertSame('no-such-module', $module->getStoreID());
		try {
			$module->getStore();
			$this->fail('an unknown store ID throws');
		} catch (TConfigurationException $e) {
		}
		$module->setStoreID('');
		$this->assertNull($module->getStore());
	}

	public function testBasePath()
	{
		$module = $this->newEmailModule();
		$module->setBasePath(__DIR__ . '/Fixtures/Emails');
		$this->assertSame(realpath(__DIR__ . '/Fixtures/Emails'), $module->getBasePath());
		$this->expectException(TConfigurationException::class);
		$module->setBasePath(__DIR__ . '/no-such-directory');
	}

	public function testMessageIdDomainValidation()
	{
		$this->expectException(TConfigurationException::class);
		$this->newEmailModule()->setMessageIdDomain("example.com>\r\nBcc: x");
	}

	public function testCloseClosesTheTransport()
	{
		$module = $this->module();
		$module->close();
		$this->assertSame(1, $this->_transport->closes);
	}

	public function testCronTaskInfos()
	{
		$module = $this->module();
		$this->assertTrue($module->getAutoGlobalListen());
		$this->assertSame(['emailbounce'], array_map(fn ($info) => $info->getName(), $module->fxGetCronTaskInfos(null, null)));
		$module->setStore(new TSqliteEmailStore());
		$infos = $module->fxGetCronTaskInfos(null, null);
		$this->assertSame(['emailbounce', 'emailqueue', 'emailcleanup'], array_map(fn ($info) => $info->getName(), $infos));
		$this->assertSame(\Prado\Email\Cron\TEmailQueueTask::class, $infos[1]->getTask());
	}

	public function testPermissions()
	{
		$permissions = $this->module()->getPermissions(null);
		$this->assertCount(1, $permissions);
		$this->assertSame(TEmailModule::PERM_EMAIL_SHELL, $permissions[0]->getName());
	}

	public function testProcessBounceMessage()
	{
		$module = $this->module($store = new TSqliteEmailStore());
		$email = $this->email('ann@example.com');
		$module->sendEmail($email);
		$events = [];
		$module->attachEventHandler('onBounce', function ($sender, $param) use (&$events) {
			$events[] = [$param->getBounce()->getRecipient(), $param->getTrackingId(), $param->getSuppressed(), $param->getInfo()['recipients'][0]['bounce_status'] ?? null];
		});
		$dsn = "From: MAILER-DAEMON@mx.example.com\r\nContent-Type: multipart/report; report-type=delivery-status; boundary=\"b\"\r\n\r\n"
			. "--b\r\nContent-Type: text/plain\r\n\r\nDelivery failed.\r\n"
			. "--b\r\nContent-Type: message/delivery-status\r\n\r\nReporting-MTA: dns; mx.example.com\r\n\r\n"
			. "Final-Recipient: rfc822; ann@example.com\r\nAction: failed\r\nStatus: 5.1.1\r\nDiagnostic-Code: smtp; 550 5.1.1 no such user\r\n"
			. "--b\r\nContent-Type: message/rfc822\r\n\r\nMessage-ID: <" . $email->getMessageId() . ">\r\nSubject: Hello\r\n\r\n--b--\r\n";
		$bounces = $module->processBounceMessage($dsn);
		$this->assertCount(1, $bounces);
		$this->assertSame([['ann@example.com', $email->getTrackingId(), 'ann@example.com', '5.1.1']], $events);
		$this->assertSame(['ann@example.com'], $store->getSuppressed(['ann@example.com']));

		$again = $this->email('ann@example.com');
		$this->assertSame(TEmailStatus::Suppressed, $module->sendEmail($again), 'a hard bounce stops the next email');
		$this->assertSame([], $module->processBounceMessage("Subject: hello\r\n\r\nnot a bounce"));
		$this->assertCount(1, $events);
	}

	public function testProcessBounceMessageWithoutStore()
	{
		$module = $this->module();
		$events = [];
		$module->attachEventHandler('onBounce', function ($sender, $param) use (&$events) {
			$events[] = [$param->getBounce()->getStatus(), $param->getTrackingId(), $param->getInfo()];
		});
		$module->processBounceMessage("X-Failed-Recipients: gone@example.com\r\nSubject: Mail delivery failed\r\n\r\nThe address failed.");
		$this->assertCount(1, $events);
		$this->assertNull($events[0][1]);
		$this->assertNull($events[0][2]);
	}
}
