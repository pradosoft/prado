<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\Bounce\TEmailBounce;
use Prado\Email\Cron\TEmailBounceTask;
use Prado\Email\Mailbox\IEmailMailbox;
use Prado\Email\Mailbox\TImapMailbox;
use Prado\Email\Store\TEmailSuppressionReason;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailService;
use Prado\Email\TEmailStatus;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;
use Prado\Test\Unit\Email\Transport\TCaptureTransport;

/**
 * A service that a plugin substitutes for TEmailService.
 */
class TEmailExtensionSeamsTestService extends TEmailService
{
}

/**
 * A mailbox that a plugin substitutes for TImapMailbox and that records its Dsn.
 */
class TEmailExtensionSeamsTestMailbox implements IEmailMailbox
{
	public string $dsn = '';

	public function setDsn($dsn)
	{
		$this->dsn = $dsn;
	}

	public function open(): void
	{
	}

	public function getUnseenMessageIds(): array
	{
		return [];
	}

	public function fetchMessage(string $id): string
	{
		return '';
	}

	public function markSeen(string $id): void
	{
	}

	public function close(): void
	{
	}
}

class TEmailExtensionSeamsTest extends TestCase
{
	use TEmailModuleCleanupTrait {
		tearDown as private releaseEmailModules;
	}

	private TEmailModule $_module;

	private TSqliteEmailStore $_store;

	private TCaptureTransport $_transport;

	private TEmailGlobalListener $_listener;

	protected function setUp(): void
	{
		$this->_module = $this->newEmailModule();
		$this->_module->setBasePath(__DIR__);
		$this->_module->setTransport($this->_transport = new TCaptureTransport());
		$this->_module->setStore($this->_store = $this->newEmailStore());
		$this->_listener = new TEmailGlobalListener();
	}

	protected function tearDown(): void
	{
		$this->_listener->unlisten();
		$this->releaseEmailModules();
	}

	private function email(string $to = 'ann@example.com', string $list = ''): TEmail
	{
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo($to);
		$email->setSubject('Hello');
		$email->setText('Hi');
		$email->setList($list);
		$email->addTrackedLink('https://www.example.com/a');
		return $email;
	}

	public function testSendingEventsAreRaisedGlobally()
	{
		$local = [];
		foreach (['onSendEmail', 'onEmailSent', 'onEmailFailed'] as $name) {
			$this->_module->attachEventHandler($name, function ($sender, $param) use (&$local, $name) {
				$local[] = $name;
			});
		}
		$email = $this->email();
		$this->_module->sendEmail($email);
		$this->assertSame(['onSendEmail', 'onEmailSent'], $local);
		$this->assertSame(['fxEmailSend', 'fxEmailSent'], $this->_listener->names());
		$this->assertSame($this->_module, $this->_listener->events[0][1], 'the sender is the module');
		$this->assertSame($email, $this->_listener->events[1][2]->getEmail());

		$this->_transport->failures[] = new TEmailTransportException('smtptransport_command_failed', 'RCPT', '550 no such user');
		$this->_module->sendEmail($this->email('gone@example.com'));
		$this->assertSame(['fxEmailSend', 'fxEmailSent', 'fxEmailSend', 'fxEmailFailed'], $this->_listener->names());
		$this->assertInstanceOf(TEmailTransportException::class, $this->_listener->events[3][2]->getException());
	}

	public function testGlobalHandlerMayCancelAndChange()
	{
		$this->_listener->cancel = true;
		$this->assertSame(TEmailStatus::Cancelled, $this->_module->sendEmail($this->email()));
		$this->assertSame([], $this->_transport->sent);
		$this->assertSame(['fxEmailSend'], $this->_listener->names());
	}

	public function testLocalCancelSkipsTheGlobalEvent()
	{
		$this->_module->attachEventHandler('onSendEmail', fn ($sender, $param) => $param->cancel());
		$this->assertSame(TEmailStatus::Cancelled, $this->_module->sendEmail($this->email()));
		$this->assertSame([], $this->_listener->names());
	}

	public function testTrackingEventsAreRaisedGlobally()
	{
		$email = $this->email('ann@example.com', 'news');
		$this->_module->sendEmail($email);
		$id = $email->getTrackingId();
		$this->_listener->events = [];
		$this->assertTrue($this->_module->recordOpen($id));
		$this->assertSame('https://www.example.com/a', $this->_module->recordClick($id, 0));
		$this->_module->recordUnsubscribe($id);
		$this->assertSame(['fxEmailOpen', 'fxEmailClick', 'fxEmailUnsubscribe'], $this->_listener->names());
		$this->assertSame($id, $this->_listener->events[0][2]->getTrackingId());
		$this->assertSame(1, $this->_listener->events[0][2]->getInfo()['open_count']);
		$this->assertSame('https://www.example.com/a', $this->_listener->events[1][2]->getUrl());
		$this->assertSame('ann@example.com', $this->_listener->events[2][2]->getSuppressed());
	}

	public function testRecordBounceTakesAStructuredBounce()
	{
		$email = $this->email('ann@example.com, bob@example.com');
		$this->_module->sendEmail($email);
		$this->_listener->events = [];
		$local = 0;
		$this->_module->attachEventHandler('onBounce', function () use (&$local) {
			$local++;
		});
		$info = $this->_module->recordBounce(new TEmailBounce('bob@example.com', 'failed', '5.1.1', 'mailbox does not exist', $email->getMessageId()));
		$this->assertSame('bob@example.com', $info['suppressed']);
		$this->assertSame($email->getTrackingId(), $info['tracking_id']);
		$this->assertSame(1, $local);
		$this->assertSame(['fxEmailBounce'], $this->_listener->names());
		$this->assertSame('5.1.1', $this->_listener->events[0][2]->getBounce()->getStatus());
		$this->assertSame(['bob@example.com'], $this->_store->getSuppressed(['ann@example.com', 'bob@example.com']));

		$this->assertNull($this->_module->recordBounce(new TEmailBounce('x@example.com', 'failed', '5.1.1', '', 'unknown@example.com')));
		$this->assertCount(2, $this->_listener->events, 'an unmatched bounce still raises the event');
		$this->assertNull($this->_listener->events[1][2]->getTrackingId());
	}

	public function testRecordComplaintSuppressesTheRecipient()
	{
		$single = $this->email('ann@example.com');
		$this->_module->sendEmail($single);
		$many = $this->email('bob@example.com, cat@example.com');
		$this->_module->sendEmail($many);
		$this->_listener->events = [];

		$info = $this->_module->recordComplaint($single->getTrackingId());
		$this->assertSame('ann@example.com', $info['suppressed']);
		$this->assertSame(['fxEmailComplaint'], $this->_listener->names());
		$this->assertSame('ann@example.com', $this->_listener->events[0][2]->getSuppressed());
		$this->assertSame(TEmailSuppressionReason::Complaint, $this->_store->getSuppressions()[0]['reason']);
		$this->assertSame(TEmailStatus::Suppressed, $this->_module->sendEmail($this->email('ann@example.com')), 'a complaint stops every later email');

		$this->assertNull($this->_module->recordComplaint($many->getTrackingId()), 'several recipients need the address');
		$this->assertNull($this->_module->recordComplaint($many->getTrackingId(), 'eve@example.com'), 'the address must be a recipient');
		$this->assertSame('cat@example.com', $this->_module->recordComplaint($many->getTrackingId(), ' Cat@Example.com ')['suppressed']);
		$this->assertNull($this->_module->recordComplaint('0123456789abcdef'));
		$this->assertCount(2, $this->_listener->events);
	}

	public function testComplaintWithoutStoreRecordsNothing()
	{
		$this->_module->setStore(null);
		$this->assertNull($this->_module->recordComplaint('0123456789abcdef'));
		$this->assertNull($this->_module->recordBounce(new TEmailBounce('x@example.com', 'failed', '5.1.1')));
		$this->assertSame(['fxEmailBounce'], $this->_listener->names());
	}

	public function testFindTrackingIdByMessageId()
	{
		$email = $this->email();
		$this->_module->sendEmail($email);
		$this->assertSame($email->getTrackingId(), $this->_store->findTrackingId('<' . $email->getMessageId() . '>'));
		$this->assertSame($email->getTrackingId(), $this->_store->findTrackingId($email->getMessageId()));
		$this->assertNull($this->_store->findTrackingId('unknown@example.com'));
		$this->assertNull($this->_store->findTrackingId(''));
	}

	public function testAdditionalEmailPathsFromAGlobalListener()
	{
		$this->_listener->path = __DIR__ . '/Fixtures/Emails/Order/Shipped';
		$email = $this->_module->createEmail('Order.Shipped');
		$this->assertSame('Order shipped', $email->getSubject());
		$this->assertSame(['fxEmailAdditionalPaths'], $this->_listener->names());
		$this->assertSame('Order.Shipped', $this->_listener->events[0][2]);
	}

	public function testServiceClassCanBeSubstituted()
	{
		$app = Prado::getApplication();
		$module = $this->newEmailModule();
		try {
			$module->init(['service' => ['id' => 'mailplus', 'class' => TEmailExtensionSeamsTestService::class, 'ShowEmails' => 'false']]);
			$config = $app->getRegisteredService('mailplus');
			$this->assertSame(TEmailExtensionSeamsTestService::class, $config[0]);
			$this->assertSame(['ShowEmails' => 'false', 'EmailModule' => $module], $config[1]);
		} finally {
			$app->unregisterService('mailplus');
		}
		$other = $this->newEmailModule();
		$this->expectException(TConfigurationException::class);
		$other->init(['service' => ['id' => 'mailbad', 'class' => TEmail::class]]);
	}

	public function testBounceTaskMailboxClass()
	{
		$task = new class () extends TEmailBounceTask {
			public function mailbox(): IEmailMailbox
			{
				return $this->createMailbox();
			}
		};
		$this->assertSame(TImapMailbox::class, $task->getMailboxClass());
		$task->setDsn('pop3://user:pass@mail.example.com');
		$task->setMailboxClass(TEmailExtensionSeamsTestMailbox::class);
		$mailbox = $task->mailbox();
		$this->assertInstanceOf(TEmailExtensionSeamsTestMailbox::class, $mailbox);
		$this->assertSame('pop3://user:pass@mail.example.com', $mailbox->dsn);
		$task->setMailboxClass(TEmail::class);
		$this->expectException(TConfigurationException::class);
		$task->mailbox();
	}
}
