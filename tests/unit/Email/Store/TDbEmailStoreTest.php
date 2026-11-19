<?php

namespace Prado\Test\Unit\Email\Store;

use PHPUnit\Framework\TestCase;
use Prado\Email\Bounce\TEmailBounce;
use Prado\Email\Store\IEmailStore;
use Prado\Email\Store\TDbEmailStore;
use Prado\Email\Store\TEmailSuppressionReason;
use Prado\Email\TEmail;
use Prado\Email\TEmailFile;
use Prado\Email\TEmailStatus;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\IPermissions;
use Prado\Util\IDbModule;

class TDbEmailStoreTest extends TestCase
{
	use \Prado\Test\Unit\Email\TEmailModuleCleanupTrait;

	private function email(string $to = 'Ann <ann@example.com>', string $list = ''): TEmail
	{
		$email = new TEmail();
		$email->setFrom('Shop <shop@example.com>');
		$email->setTo($to);
		$email->setSubject('Hello');
		$email->setHtml('<p>Hello</p>');
		$email->setList($list);
		$email->setMessageId(bin2hex(random_bytes(8)) . '@example.com');
		$email->finalize();
		return $email;
	}

	public function testDefaults()
	{
		$store = $this->newEmailStore();
		$this->assertInstanceOf(IEmailStore::class, $store);
		$this->assertInstanceOf(IDbModule::class, $store);
		$this->assertInstanceOf(IPermissions::class, $store);
		$this->assertSame('email_', $store->getTablePrefix());
		$this->assertSame('email_messages', $store->getTableName('messages'));
		$this->assertTrue($store->getAutoCreateTables());
		$this->assertSame(5, $store->getMaxAttempts());
		$this->assertSame(60, $store->getRetryDelay());
		$this->assertSame(900, $store->getClaimTimeout());
		$this->assertCount(2, $store->getPermissions(null));
	}

	public function testPropertiesLockAfterInit()
	{
		$store = $this->newEmailStore();
		$store->setTablePrefix('mail_');
		$store->setTablePrefix('');
		$store->init(null);
		foreach (['setTablePrefix' => 'x_', 'setAutoCreateTables' => false, 'setConnectionID' => 'db'] as $setter => $value) {
			try {
				$store->$setter($value);
				$this->fail("{$setter} is locked after init");
			} catch (TInvalidOperationException $e) {
			}
		}
		$store->setMaxAttempts(0);
		$this->assertSame(1, $store->getMaxAttempts());
	}

	public function testInvalidTablePrefixThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		($this->newEmailStore())->setTablePrefix('mail; DROP TABLE x');
	}

	public function testAddEmailAndRebuild()
	{
		$store = $this->newEmailStore();
		$email = new TEmail();
		$email->setFrom('"Shop, Inc." <shop@example.com>');
		$email->setSender('bot@example.com');
		$email->setReturnPath('bounce@example.com');
		$email->setReplyTo('help@example.com, Zoë <zoe@example.com>');
		$email->setTo('Ann <Ann@Example.com>, bob@example.com');
		$email->setCc('cat@example.com');
		$email->setBcc('eve@example.com');
		$email->setSubject('Größe ✓');
		$email->setHtml('<p>Hello <a href="x">link</a></p>');
		$email->setText('Hello link');
		$email->setDate(1699999999);
		$email->setPriority(2);
		$email->setHeader('X-Campaign', 'spring');
		$email->setListUnsubscribe('https://example.com/u');
		$email->setList('news');
		$email->setSecure(true);
		$email->setUsername('ann');
		$email->setMessageId('abc@example.com');
		$email->addTrackedLink('https://example.com/a');
		$email->attachData("\x00\xFFbinary", 'a.bin');
		$email->addFile(TEmailFile::fromPath(__FILE__)->withContentId('self'));
		$email->finalize();
		$store->addEmail($email, TEmailStatus::Queued);

		$copy = $store->getEmail($email->getTrackingId());
		$this->assertNotSame($email, $copy);
		$this->assertTrue($copy->getIsFinal());
		$this->assertSame(TEmailStatus::Queued, $copy->getStatus());
		$this->assertSame($email->getTrackingId(), $copy->getTrackingId());
		$this->assertSame('abc@example.com', $copy->getMessageId());
		$this->assertSame('"Shop, Inc." <shop@example.com>', (string) $copy->getFrom());
		$this->assertSame('bot@example.com', (string) $copy->getSender());
		$this->assertSame('bounce@example.com', (string) $copy->getReturnPath());
		$this->assertSame(['help@example.com', 'Zoë <zoe@example.com>'], array_map('strval', $copy->getReplyTo()));
		$this->assertSame(['Ann <Ann@Example.com>', 'bob@example.com'], array_map('strval', $copy->getTo()));
		$this->assertSame(['cat@example.com'], array_map('strval', $copy->getCc()));
		$this->assertSame(['eve@example.com'], array_map('strval', $copy->getBcc()));
		$this->assertSame('Größe ✓', $copy->getSubject());
		$this->assertSame('<p>Hello <a href="x">link</a></p>', $copy->getHtml());
		$this->assertSame('Hello link', $copy->getText());
		$this->assertSame(1699999999, $copy->getDate());
		$this->assertSame(2, $copy->getPriority());
		$this->assertSame($email->getHeaders(), $copy->getHeaders());
		$this->assertSame('news', $copy->getList());
		$this->assertTrue($copy->getSecure());
		$this->assertSame('ann', $copy->getUsername());
		$this->assertSame(['https://example.com/a'], $copy->getTrackedLinks());
		$this->assertSame("\x00\xFFbinary", $copy->getAttachments()[0]->getContent());
		$this->assertSame(__FILE__, $copy->getInlineFiles()[0]->getPath());
		$this->assertSame('self', $copy->getInlineFiles()[0]->getContentId());
		$this->assertNull($store->getEmail('0123456789abcdef'));
	}

	public function testEmailInfo()
	{
		$store = $this->newEmailStore(1700000000);
		$email = $this->email('Ann <ann@example.com>');
		$email->setUsername('ann');
		$store->addEmail($email, TEmailStatus::Queued);
		$info = $store->getEmailInfo($email->getTrackingId());
		$this->assertSame($email->getTrackingId(), $info['tracking_id']);
		$this->assertSame($email->getMessageId(), $info['rfc_message_id']);
		$this->assertSame('ann', $info['username']);
		$this->assertSame('', $info['list_name']);
		$this->assertSame('Hello', $info['subject']);
		$this->assertSame(TEmailStatus::Queued, $info['status']);
		$this->assertSame(0, $info['attempts']);
		$this->assertSame(1700000000, $info['created_time']);
		$this->assertNull($info['sent_time']);
		$this->assertSame(0, $info['open_count']);
		$this->assertSame([['address' => 'ann@example.com', 'display_name' => 'Ann', 'kind' => 'to', 'bounce_status' => null, 'bounce_action' => null, 'bounce_diagnostic' => null, 'bounced_time' => null]], $info['recipients']);
		$this->assertArrayNotHasKey('html_body', $info);
		$this->assertArrayNotHasKey('claim_token', $info);
		$this->assertNull($store->getEmailInfo('0123456789abcdef'));
	}

	public function testClaimIsExclusive()
	{
		$store = $this->newEmailStore();
		$email = $this->email();
		$store->addEmail($email, TEmailStatus::Queued);
		$this->assertTrue($store->claimEmail($email));
		$this->assertFalse($store->claimEmail($email), 'a claimed email cannot be claimed again');
		$this->assertSame([], $store->claimEmails(), 'a claimed email is not in the queue');
		$this->assertSame(TEmailStatus::Sending, $store->getEmailInfo($email->getTrackingId())['status']);
		$store->markSent($email);
		$info = $store->getEmailInfo($email->getTrackingId());
		$this->assertSame(TEmailStatus::Sent, $info['status']);
		$this->assertSame(1, $info['attempts']);
		$this->assertSame(1700000000, $info['sent_time']);
		$this->assertFalse($store->claimEmail($email));
	}

	public function testAddEmailAsSendingIsAlreadyClaimed()
	{
		$store = $this->newEmailStore();
		$email = $this->email();
		$store->addEmail($email, TEmailStatus::Sending);
		$this->assertFalse($store->claimEmail($email));
		$this->assertSame([], $store->claimEmails());
	}

	public function testClaimEmailsOldestFirstWithLimit()
	{
		$store = $this->newEmailStore();
		$emails = [$this->email('a@example.com'), $this->email('b@example.com'), $this->email('c@example.com')];
		foreach ($emails as $email) {
			$store->addEmail($email, TEmailStatus::Queued);
		}
		$first = $store->claimEmails(2);
		$this->assertSame(['a@example.com', 'b@example.com'], array_map(fn ($e) => $e->getTo()[0]->getAddress(), $first));
		$this->assertSame(TEmailStatus::Sending, $first[0]->getStatus());
		$this->assertSame($emails[0]->getTrackingId(), $first[0]->getTrackingId());
		$rest = $store->claimEmails();
		$this->assertSame(['c@example.com'], array_map(fn ($e) => $e->getTo()[0]->getAddress(), $rest));
		$this->assertSame([], $store->claimEmails());
	}

	public function testTransientFailureRetriesWithBackoff()
	{
		$store = $this->newEmailStore(1700000000);
		$store->setMaxAttempts(3);
		$store->setRetryDelay(100);
		$email = $this->email();
		$store->addEmail($email, TEmailStatus::Sending);

		$this->assertSame(TEmailStatus::Queued, $store->markFailed($email, 'timeout 1', true));
		$info = $store->getEmailInfo($email->getTrackingId());
		$this->assertSame(1, $info['attempts']);
		$this->assertSame('timeout 1', $info['error_text']);
		$this->assertSame(1700000100, $info['next_attempt_time']);
		$this->assertSame([], $store->claimEmails(), 'the retry is not due yet');

		$store->clock->setTime(1700000100);
		$this->assertCount(1, $store->claimEmails());
		$this->assertSame(TEmailStatus::Queued, $store->markFailed($email, 'timeout 2', true));
		$this->assertSame(1700000300, $store->getEmailInfo($email->getTrackingId())['next_attempt_time'], 'the delay doubles');

		$store->clock->setTime(1700000300);
		$this->assertCount(1, $store->claimEmails());
		$this->assertSame(TEmailStatus::Failed, $store->markFailed($email, 'timeout 3', true), 'the third attempt is the last');
		$info = $store->getEmailInfo($email->getTrackingId());
		$this->assertSame(3, $info['attempts']);
		$this->assertNull($info['next_attempt_time']);
		$store->clock->setTime(1800000000);
		$this->assertSame([], $store->claimEmails());
	}

	public function testPermanentFailureIsNotRetried()
	{
		$store = $this->newEmailStore();
		$email = $this->email();
		$store->addEmail($email, TEmailStatus::Sending);
		$this->assertSame(TEmailStatus::Failed, $store->markFailed($email, str_repeat('x', 5000), false));
		$info = $store->getEmailInfo($email->getTrackingId());
		$this->assertSame(TEmailStatus::Failed, $info['status']);
		$this->assertSame(2000, strlen($info['error_text']));
	}

	public function testMaxAttemptsOneTurnsRetriesOff()
	{
		$store = $this->newEmailStore();
		$store->setMaxAttempts(1);
		$email = $this->email();
		$store->addEmail($email, TEmailStatus::Sending);
		$this->assertSame(TEmailStatus::Failed, $store->markFailed($email, 'timeout', true));
	}

	public function testStaleClaimReturnsToTheQueue()
	{
		$store = $this->newEmailStore(1700000000);
		$store->setClaimTimeout(600);
		$email = $this->email();
		$store->addEmail($email, TEmailStatus::Sending);
		$store->clock->setTime(1700000600);
		$this->assertSame([], $store->claimEmails(), 'a claim at the timeout is still held');
		$store->clock->setTime(1700000601);
		$claimed = $store->claimEmails();
		$this->assertCount(1, $claimed);
		$this->assertSame($email->getTrackingId(), $claimed[0]->getTrackingId());
	}

	public function testOpenAndClick()
	{
		$store = $this->newEmailStore(1700000000);
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$email->addTrackedLink('https://example.com/a?x=1&y=2');
		$email->addTrackedLink('https://example.com/b');
		$email->finalize();
		$store->addEmail($email, TEmailStatus::Sent);
		$id = $email->getTrackingId();

		$this->assertFalse($store->recordOpen('0123456789abcdef'));
		$this->assertTrue($store->recordOpen($id));
		$store->clock->setTime(1700000050);
		$this->assertTrue($store->recordOpen($id));
		$info = $store->getEmailInfo($id);
		$this->assertSame(2, $info['open_count']);
		$this->assertSame(1700000000, $info['opened_time'], 'the first open time is kept');

		$this->assertSame('https://example.com/a?x=1&y=2', $store->recordClick($id, 0));
		$this->assertSame('https://example.com/b', $store->recordClick($id, 1));
		$this->assertNull($store->recordClick($id, 2));
		$this->assertNull($store->recordClick($id, -1));
		$this->assertNull($store->recordClick('0123456789abcdef', 0));
		$info = $store->getEmailInfo($id);
		$this->assertSame(2, $info['click_count']);
		$this->assertSame(1700000050, $info['clicked_time']);
	}

	public function testClickMarksAnUnopenedEmailOpened()
	{
		$store = $this->newEmailStore(1700000000);
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$email->addTrackedLink('https://example.com/a');
		$email->finalize();
		$store->addEmail($email, TEmailStatus::Sent);
		$store->recordClick($email->getTrackingId(), 0);
		$info = $store->getEmailInfo($email->getTrackingId());
		$this->assertSame(1700000000, $info['opened_time']);
		$this->assertSame(0, $info['open_count']);
	}

	public function testSuppressionList()
	{
		$store = $this->newEmailStore();
		$this->assertSame([], $store->getSuppressed([], 'news'));
		$this->assertSame([], $store->getSuppressed(['ann@example.com'], 'news'));
		$store->addSuppression(' Ann@Example.com ', TEmailSuppressionReason::Unsubscribe, 'news');
		$store->addSuppression('ann@example.com', TEmailSuppressionReason::Manual, 'news');
		$store->addSuppression('bob@example.com', TEmailSuppressionReason::Bounce);
		$this->assertCount(2, $store->getSuppressions());
		$this->assertSame(TEmailSuppressionReason::Unsubscribe, $store->getSuppressions()[1]['reason'], 'the first suppression of an address and list stays');

		$this->assertSame(['ann@example.com', 'bob@example.com'], $store->getSuppressed(['ANN@example.com', 'bob@example.com', 'cat@example.com'], 'news'));
		$this->assertSame(['bob@example.com'], $store->getSuppressed(['ann@example.com', 'bob@example.com'], 'offers'));
		$this->assertSame(['bob@example.com'], $store->getSuppressed(['ann@example.com', 'bob@example.com']), 'a transactional email ignores list suppressions');

		$this->assertSame(0, $store->removeSuppression('ann@example.com', ''));
		$this->assertSame(1, $store->removeSuppression('ANN@example.com', 'news'));
		$this->assertSame(1, $store->removeSuppression('bob@example.com'));
		$this->assertSame([], $store->getSuppressions());
	}

	public function testInvalidSuppressionReasonThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		($this->newEmailStore())->addSuppression('ann@example.com', 'Bored');
	}

	public function testUnsubscribeSuppressesTheListRecipient()
	{
		$store = $this->newEmailStore(1700000000);
		$news = $this->email('Ann <Ann@Example.com>', 'news');
		$store->addEmail($news, TEmailStatus::Sent);
		$info = $store->recordUnsubscribe($news->getTrackingId());
		$this->assertSame('ann@example.com', $info['suppressed']);
		$this->assertSame(1700000000, $info['unsubscribed_time']);
		$this->assertSame(['ann@example.com'], $store->getSuppressed(['ann@example.com'], 'news'));
		$this->assertSame([], $store->getSuppressed(['ann@example.com']));
		$this->assertSame($news->getTrackingId(), $store->getSuppressions()[0]['tracking_id']);
		$store->clock->setTime(1700000999);
		$this->assertSame(1700000000, $store->recordUnsubscribe($news->getTrackingId())['unsubscribed_time']);

		$transactional = $this->email('bob@example.com');
		$store->addEmail($transactional, TEmailStatus::Sent);
		$this->assertNull($store->recordUnsubscribe($transactional->getTrackingId())['suppressed'], 'an email without a list suppresses nothing');

		$many = $this->email('cat@example.com, dan@example.com', 'news');
		$store->addEmail($many, TEmailStatus::Sent);
		$this->assertNull($store->recordUnsubscribe($many->getTrackingId())['suppressed'], 'an email to several recipients names no one');
		$this->assertNull($store->recordUnsubscribe('0123456789abcdef'));
	}

	public function testBounceIsRecordedOnTheRecipient()
	{
		$store = $this->newEmailStore(1700000000);
		$email = $this->email('ann@example.com, Bob <Bob@Example.com>');
		$store->addEmail($email, TEmailStatus::Sent);
		$bounce = new TEmailBounce('bob@example.com', 'failed', '5.1.1', '550 no such user', '<' . $email->getMessageId() . '>', 'mx.example.com');
		$info = $store->recordBounce($bounce);
		$this->assertSame('bob@example.com', $info['suppressed']);
		$this->assertNull($info['recipients'][0]['bounce_status']);
		$this->assertSame('5.1.1', $info['recipients'][1]['bounce_status']);
		$this->assertSame('failed', $info['recipients'][1]['bounce_action']);
		$this->assertSame('550 no such user', $info['recipients'][1]['bounce_diagnostic']);
		$this->assertSame(1700000000, (int) $info['recipients'][1]['bounced_time']);
		$this->assertSame(['bob@example.com'], $store->getSuppressed(['ann@example.com', 'bob@example.com'], 'any'));
		$this->assertSame(TEmailSuppressionReason::Bounce, $store->getSuppressions()[0]['reason']);
	}

	public function testDelayedBounceDoesNotSuppress()
	{
		$store = $this->newEmailStore();
		$email = $this->email();
		$store->addEmail($email, TEmailStatus::Sent);
		$info = $store->recordBounce(new TEmailBounce('ann@example.com', 'delayed', '4.4.1', 'connection timed out', $email->getMessageId()));
		$this->assertNull($info['suppressed']);
		$this->assertSame('4.4.1', $info['recipients'][0]['bounce_status']);
		$this->assertSame([], $store->getSuppressions());
	}

	public function testBounceFallsBackToTheOnlyRecipient()
	{
		$store = $this->newEmailStore();
		$email = $this->email('ann@example.com');
		$store->addEmail($email, TEmailStatus::Sent);
		$info = $store->recordBounce(new TEmailBounce('ann-alias@other.example', 'failed', '5.1.1', '', $email->getMessageId()));
		$this->assertSame('ann@example.com', $info['suppressed']);
	}

	public function testForgedBounceSuppressesNothing()
	{
		$store = $this->newEmailStore();
		$email = $this->email('ann@example.com, bob@example.com');
		$store->addEmail($email, TEmailStatus::Sent);
		$this->assertNull($store->recordBounce(new TEmailBounce('victim@example.com', 'failed', '5.1.1', '', 'unknown@example.com')), 'an unknown Message-ID matches no email');
		$this->assertNull($store->recordBounce(new TEmailBounce('victim@example.com', 'failed', '5.1.1', '', '')), 'a bounce without a Message-ID matches no email');
		$this->assertNull($store->recordBounce(new TEmailBounce('victim@example.com', 'failed', '5.1.1', '', $email->getMessageId())), 'an address the email was not sent to is not suppressed');
		$this->assertSame([], $store->getSuppressions());
	}

	public function testLog()
	{
		$store = $this->newEmailStore(1700000000);
		$emails = [];
		foreach ([['ann', TEmailStatus::Sent], ['ann', TEmailStatus::Failed], ['bob', TEmailStatus::Sent], [null, TEmailStatus::Queued]] as [$user, $status]) {
			$email = $this->email();
			$email->setUsername($user);
			$store->addEmail($email, $status);
			$emails[] = $email;
		}
		$this->assertSame(4, $store->countEmails());
		$this->assertSame(2, $store->countEmails('ann'));
		$this->assertSame(2, $store->countEmails(null, TEmailStatus::Sent));
		$this->assertSame(1, $store->countEmails('ann', TEmailStatus::Sent));
		$all = $store->findEmails();
		$this->assertSame(array_reverse(array_map(fn ($e) => $e->getTrackingId(), $emails)), array_column($all, 'tracking_id'));
		$this->assertArrayNotHasKey('recipients', $all[0]);
		$this->assertCount(2, $store->findEmails('ann'));
		$this->assertSame([$emails[2]->getTrackingId()], array_column($store->findEmails(null, null, 1, 1), 'tracking_id'));
		$this->assertSame([$emails[1]->getTrackingId()], array_column($store->findEmails(null, TEmailStatus::Failed), 'tracking_id'));
	}

	public function testDeleteEmails()
	{
		$store = $this->newEmailStore(1700000000);
		$old = [$this->email(), $this->email(), $this->email()];
		$store->addEmail($old[0], TEmailStatus::Sent);
		$store->addEmail($old[1], TEmailStatus::Failed);
		$store->addEmail($old[2], TEmailStatus::Queued);
		$store->clock->setTime(1700001000);
		$recent = $this->email();
		$store->addEmail($recent, TEmailStatus::Sent);

		$this->assertSame(0, $store->deleteEmails(2000));
		$this->assertSame(1, $store->deleteEmails(500, TEmailStatus::Failed));
		$this->assertSame(1, $store->deleteEmails(500), 'queued emails are kept');
		$this->assertSame([$recent->getTrackingId(), $old[2]->getTrackingId()], array_column($store->findEmails(), 'tracking_id'));
		$this->assertCount(2, $store->rows('recipients'), 'the recipients of deleted emails are deleted');
		$this->assertSame(1, $store->deleteEmails(500, TEmailStatus::Queued));
		$this->assertSame(1, $store->deleteEmails(0));
		$this->assertSame([], $store->rows('messages'));
		$this->assertSame([], $store->rows('recipients'));
	}

	public function testPermissionBehaviorBlocksTheLog()
	{
		$store = $this->newEmailStore();
		$store->addEmail($this->email(), TEmailStatus::Sent);
		$store->attachBehavior('deny', new class () extends \Prado\Util\TBehavior {
			public function dyFindEmails($return, $username, $status, $chain)
			{
				return true;
			}

			public function dyCountEmails($return, $username, $status, $chain)
			{
				return true;
			}

			public function dyDeleteEmails($return, $seconds, $status, $chain)
			{
				return true;
			}
		});
		$this->assertFalse($store->findEmails());
		$this->assertFalse($store->countEmails());
		$this->assertFalse($store->deleteEmails(0));
		$this->assertCount(1, $store->rows('messages'));
	}
}
