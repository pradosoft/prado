<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\IEmailBody;
use Prado\Email\IEmailMailer;
use Prado\Email\TEmail;
use Prado\Email\TEmailAddress;
use Prado\Email\TEmailFile;
use Prado\Email\TEmailStatus;
use Prado\Exceptions\TInvalidDataValueException;

class TEmailTest extends TestCase
{
	private function body(?callable $render = null): IEmailBody
	{
		return new class ($render) implements IEmailBody {
			public int $count = 0;

			public function __construct(private $_render)
			{
			}

			public function renderEmail(TEmail $email): void
			{
				$this->count++;
				if ($this->_render) {
					($this->_render)($email);
				} else {
					$email->setHtml('<p>Hello {{NAME}}</p>');
				}
			}
		};
	}

	public function testDefaults()
	{
		$email = new TEmail();
		$this->assertNull($email->getFrom());
		$this->assertNull($email->getSender());
		$this->assertNull($email->getReturnPath());
		$this->assertSame([], $email->getTo());
		$this->assertSame([], $email->getCc());
		$this->assertSame([], $email->getBcc());
		$this->assertSame([], $email->getReplyTo());
		$this->assertSame('', $email->getSubject());
		$this->assertNull($email->getDate());
		$this->assertNull($email->getPriority());
		$this->assertNull($email->getMessageId());
		$this->assertSame('UTF-8', $email->getCharset());
		$this->assertSame([], $email->getHeaders());
		$this->assertNull($email->getHtml());
		$this->assertNull($email->getText());
		$this->assertNull($email->getBody());
		$this->assertSame([], $email->getFiles());
		$this->assertSame([], $email->getData());
		$this->assertSame('', $email->getList());
		$this->assertFalse($email->getSecure());
		$this->assertSame([], $email->getTrackedLinks());
		$this->assertNull($email->getUsername());
		$this->assertSame(TEmailStatus::New, $email->getStatus());
		$this->assertNull($email->getError());
		$this->assertNull($email->getMailer());
		$this->assertFalse($email->getIsRendered());
		$this->assertFalse($email->getIsFinal());
	}

	public function testSingleAddresses()
	{
		$email = new TEmail();
		$email->setFrom('Shop <shop@example.com>');
		$email->setSender(['bot@example.com', 'Bot']);
		$email->setReturnPath(new TEmailAddress('bounce@example.com'));
		$this->assertSame('Shop', $email->getFrom()->getName());
		$this->assertSame('Bot', $email->getSender()->getName());
		$this->assertSame('bounce@example.com', $email->getReturnPath()->getAddress());
		$email->setFrom('');
		$email->setSender(null);
		$this->assertNull($email->getFrom());
		$this->assertNull($email->getSender());
		$this->assertSame('shop@example.com', $email->From = 'shop@example.com');
		$this->assertInstanceOf(TEmailAddress::class, $email->From);
	}

	public function testAddressLists()
	{
		$email = new TEmail();
		$email->setTo('"Doe, John" <john@example.com>, jane@example.com');
		$email->addTo('bob@example.com', 'Bob');
		$email->addTo('Cat <cat@example.com>', 'Ignored');
		$this->assertSame(['Doe, John', '', 'Bob', 'Cat'], array_map(fn ($a) => $a->getName(), $email->getTo()));
		$email->setCc(['ann@example.com']);
		$email->addCc('dan@example.com');
		$email->setBcc(null);
		$email->addBcc(new TEmailAddress('eve@example.com'));
		$email->setReplyTo('help@example.com');
		$this->assertCount(2, $email->getCc());
		$this->assertCount(1, $email->getBcc());
		$this->assertSame('help@example.com', $email->getReplyTo()[0]->getAddress());
	}

	public function testRecipientsAreUnique()
	{
		$email = new TEmail();
		$email->setTo('ann@example.com, bob@example.com');
		$email->setCc('ANN@example.com');
		$email->setBcc('eve@example.com, bob@example.com');
		$this->assertSame(['ann@example.com', 'bob@example.com', 'eve@example.com'], array_map(fn ($a) => $a->getAddress(), $email->getRecipients()));
	}

	public function testInvalidAddressThrows()
	{
		$email = new TEmail();
		$this->expectException(TInvalidDataValueException::class);
		$email->setTo("ann@example.com\r\nBcc: eve@example.com");
	}

	public function testSubjectRejectsLineBreak()
	{
		$email = new TEmail();
		$email->setSubject('Hello');
		$this->assertSame('Hello', $email->getSubject());
		$this->expectException(TInvalidDataValueException::class);
		$email->setSubject("Hello\r\nBcc: eve@example.com");
	}

	public function testDate()
	{
		$email = new TEmail();
		$email->setDate(1700000000);
		$this->assertSame(1700000000, $email->getDate());
		$email->setDate('1700000001');
		$this->assertSame(1700000001, $email->getDate());
		$email->setDate('2024-01-02 03:04:05 UTC');
		$this->assertSame(1704164645, $email->getDate());
		$email->setDate(new \DateTimeImmutable('@1700000002'));
		$this->assertSame(1700000002, $email->getDate());
		$email->setDate(null);
		$this->assertNull($email->getDate());
		$this->expectException(TInvalidDataValueException::class);
		$email->setDate('not a date');
	}

	public function testPriority()
	{
		$email = new TEmail();
		$email->setPriority('1');
		$this->assertSame(1, $email->getPriority());
		$email->setPriority(9);
		$this->assertSame(5, $email->getPriority());
		$email->setPriority(-3);
		$this->assertSame(1, $email->getPriority());
		$email->setPriority('');
		$this->assertNull($email->getPriority());
	}

	public function testMessageId()
	{
		$email = new TEmail();
		$email->setMessageId(' <abc.123@example.com> ');
		$this->assertSame('abc.123@example.com', $email->getMessageId());
		$email->setMessageId(null);
		$this->assertNull($email->getMessageId());
		$this->expectException(TInvalidDataValueException::class);
		$email->setMessageId('no-domain');
	}

	public function testCharset()
	{
		$email = new TEmail();
		$email->setCharset('ISO-8859-1');
		$this->assertSame('ISO-8859-1', $email->getCharset());
		$this->expectException(TInvalidDataValueException::class);
		$email->setCharset("utf-8\r\nX: y");
	}

	public function testHeaders()
	{
		$email = new TEmail();
		$email->setHeader('X-Campaign', 'spring');
		$email->setHeader('x-campaign', 'summer');
		$email->setHeader('X-Other', '1');
		$this->assertSame(['x-campaign' => 'summer', 'X-Other' => '1'], $email->getHeaders());
		$this->assertSame('summer', $email->getHeader('X-CAMPAIGN'));
		$email->setHeader('X-Campaign', null);
		$this->assertNull($email->getHeader('X-Campaign'));
		$this->assertSame(['X-Other' => '1'], $email->getHeaders());
	}

	public function testReservedHeaderThrows()
	{
		$email = new TEmail();
		$this->expectException(TInvalidDataValueException::class);
		$email->setHeader('Bcc', 'eve@example.com');
	}

	public function testHeaderValueWithLineBreakThrows()
	{
		$email = new TEmail();
		$this->expectException(TInvalidDataValueException::class);
		$email->setHeader('X-Test', "a\r\nBcc: eve@example.com");
	}

	public function testHeaderNameWithColonThrows()
	{
		$email = new TEmail();
		$this->expectException(TInvalidDataValueException::class);
		$email->setHeader('X-Test: a', 'b');
	}

	public function testListUnsubscribe()
	{
		$email = new TEmail();
		$email->setListUnsubscribe('https://example.com/u/1', 'mailto:leave@example.com');
		$this->assertSame('<https://example.com/u/1>, <mailto:leave@example.com>', $email->getHeader('List-Unsubscribe'));
		$this->assertSame('List-Unsubscribe=One-Click', $email->getHeader('List-Unsubscribe-Post'));
		$email->setListUnsubscribe('https://example.com/u/1', null, false);
		$this->assertSame('<https://example.com/u/1>', $email->getHeader('List-Unsubscribe'));
		$this->assertNull($email->getHeader('List-Unsubscribe-Post'));
		$email->setListUnsubscribe(null, 'leave@example.com');
		$this->assertSame('<mailto:leave@example.com>', $email->getHeader('List-Unsubscribe'));
		$this->assertNull($email->getHeader('List-Unsubscribe-Post'));
		$email->setListUnsubscribe(null);
		$this->assertNull($email->getHeader('List-Unsubscribe'));
	}

	public function testFiles()
	{
		$email = new TEmail();
		$attached = $email->attach(__FILE__, 'test.php');
		$data = $email->attachData('a,b', 'a.csv');
		$cid = $email->embed(__FILE__);
		$this->assertSame($cid, $email->embed(__FILE__));
		$this->assertSame('logo', $email->embed(__FILE__, 'logo'));
		$this->assertMatchesRegularExpression('/^[0-9a-f]{12}\.TEmailTest\.php$/', $cid);
		$this->assertCount(4, $email->getFiles());
		$this->assertSame([$attached, $data], $email->getAttachments());
		$this->assertSame([$cid, 'logo'], array_map(fn ($f) => $f->getContentId(), $email->getInlineFiles()));
		$email->setFiles([TEmailFile::fromData('x', 'x.txt')]);
		$this->assertCount(1, $email->getFiles());
	}

	public function testListSecureUsernameStatusError()
	{
		$email = new TEmail();
		$email->setList('newsletter');
		$email->setSecure('true');
		$email->setUsername('ann');
		$email->setStatus('Sent');
		$email->setError('550 no such user');
		$this->assertSame('newsletter', $email->getList());
		$this->assertTrue($email->getSecure());
		$this->assertSame('ann', $email->getUsername());
		$this->assertSame(TEmailStatus::Sent, $email->getStatus());
		$this->assertSame('550 no such user', $email->getError());
		$email->setUsername('');
		$email->setError('');
		$this->assertNull($email->getUsername());
		$this->assertNull($email->getError());
	}

	public function testListTooLongThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TEmail())->setList(str_repeat('a', 65));
	}

	public function testInvalidStatusThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TEmail())->setStatus('Lost');
	}

	public function testTrackingId()
	{
		$email = new TEmail();
		$id = $email->getTrackingId();
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $id);
		$this->assertSame($id, $email->getTrackingId());
		$this->assertNotSame($id, (new TEmail())->getTrackingId());
		$email->setTrackingId('abcdefghij0123456789');
		$this->assertSame('abcdefghij0123456789', $email->getTrackingId());
		$email->setTrackingId(null);
		$this->assertNotSame('abcdefghij0123456789', $email->getTrackingId());
		$this->expectException(TInvalidDataValueException::class);
		$email->setTrackingId('short');
	}

	public function testTrackedLinks()
	{
		$email = new TEmail();
		$this->assertSame(0, $email->addTrackedLink('https://example.com/a'));
		$this->assertSame(1, $email->addTrackedLink('https://example.com/b'));
		$this->assertSame(0, $email->addTrackedLink('https://example.com/a'));
		$this->assertSame(['https://example.com/a', 'https://example.com/b'], $email->getTrackedLinks());
		$email->setTrackedLinks([5 => 'https://example.com/c']);
		$this->assertSame(['https://example.com/c'], $email->getTrackedLinks());
		$email->setTrackedLinks(null);
		$this->assertSame([], $email->getTrackedLinks());
	}

	public function testRenderRunsTheBodyOnce()
	{
		$email = new TEmail();
		$email->setBody($body = $this->body());
		$email->render();
		$email->render();
		$this->assertSame(1, $body->count);
		$this->assertTrue($email->getIsRendered());
		$this->assertFalse($email->getIsFinal());
		$this->assertSame('<p>Hello {{NAME}}</p>', $email->getHtml());
		$email->setBody($body);
		$this->assertFalse($email->getIsRendered());
		$email->render();
		$this->assertSame(2, $body->count);
	}

	public function testFinalizeReplacesPlaceholders()
	{
		$email = new TEmail();
		$email->setTo('ann@example.com');
		$email->setSubject('Order {{NUMBER}} for {{ NAME }}');
		$email->setHtml('<p>{{NAME}} {{{RAW}}} {{MISSING}} {{email:to}}</p>');
		$email->setText('{{NAME}} {{{RAW}}} {{MISSING}}');
		$email->setData(['NUMBER' => 1042, 'NAME' => 'Tom & <Zoë> "Q"', 'RAW' => '<b>bold</b>']);
		$email->finalize();
		$this->assertTrue($email->getIsFinal());
		$this->assertSame('Order 1042 for Tom & <Zoë> "Q"', $email->getSubject());
		$this->assertSame('<p>Tom &amp; &lt;Zoë&gt; &quot;Q&quot; <b>bold</b> {{MISSING}} ann@example.com</p>', $email->getHtml());
		$this->assertSame('Tom & <Zoë> "Q" <b>bold</b> {{MISSING}}', $email->getText());
	}

	public function testFinalizeTreatsKeysAndValuesLiterally()
	{
		$email = new TEmail();
		$email->setText('A.B={{A.B}} AxB={{AxB}} N={{N}}');
		$email->setData(['A.B' => 'dot', 'N' => 'Cost $10 \\1 {{A.B}}']);
		$email->finalize();
		$this->assertSame('A.B=dot AxB={{AxB}} N=Cost $10 \\1 {{A.B}}', $email->getText());
	}

	public function testFinalizeValueTypes()
	{
		$email = new TEmail();
		$email->setText('[{{NULL}}][{{TRUE}}][{{FALSE}}][{{FLOAT}}][{{ARRAY}}][{{OBJECT}}]');
		$email->setData(['NULL' => null, 'TRUE' => true, 'FALSE' => false, 'FLOAT' => 1.5, 'ARRAY' => [1], 'OBJECT' => new TEmailAddress('ann@example.com')]);
		$email->finalize();
		$this->assertSame('[][1][][1.5][][ann@example.com]', $email->getText());
	}

	public function testFinalizeKeepsTheSubjectOnOneLine()
	{
		$email = new TEmail();
		$email->setSubject('Hi {{NAME}}');
		$email->setData(['NAME' => "Ann\r\nBcc: eve@example.com"]);
		$email->finalize();
		$this->assertSame('Hi Ann Bcc: eve@example.com', $email->getSubject());
	}

	public function testFinalizeDerivesTextFromHtml()
	{
		$email = new TEmail();
		$email->setHtml('<p>Hello {{NAME}}, see <a href="https://example.com/?q={{NAME}}">this</a></p>');
		$email->setData(['NAME' => 'A&B']);
		$email->finalize();
		$this->assertSame('Hello A&B, see this (https://example.com/?q=A&B)', $email->getText());
		$this->assertSame('<p>Hello A&amp;B, see <a href="https://example.com/?q=A&amp;B">this</a></p>', $email->getHtml());
	}

	public function testFinalizeKeepsAGivenText()
	{
		$email = new TEmail();
		$email->setHtml('<p>Rich</p>');
		$email->setText('Plain');
		$email->finalize();
		$this->assertSame('Plain', $email->getText());
		$text = new TEmail();
		$text->setText('Only text');
		$text->finalize();
		$this->assertNull($text->getHtml());
	}

	public function testFinalizeTrackingIdAndLinks()
	{
		$email = new TEmail();
		$email->setHtml('<a href="https://example.com/t?email={{email:tracking-id}}&click=0">x</a>');
		$email->addTrackedLink('https://example.com/u?name={{NAME}}&next={{{NEXT}}}');
		$email->setData(['NAME' => 'A b&c', 'NEXT' => 'a=1&b=2', 'email:tracking-id' => 'forged']);
		$email->finalize();
		$id = $email->getTrackingId();
		$this->assertSame('<a href="https://example.com/t?email=' . $id . '&click=0">x</a>', $email->getHtml());
		$this->assertSame(['https://example.com/u?name=A%20b%26c&next=a=1&b=2'], $email->getTrackedLinks());
	}

	public function testFinalizeReplacesPlaceholdersInHeaders()
	{
		$email = new TEmail();
		$email->setListUnsubscribe('https://example.com/t?email={{email:tracking-id}}&unsubscribe=1');
		$email->setHeader('X-Customer', '{{NAME}}');
		$email->setData(['NAME' => "Ann\r\nBcc: eve@example.com"]);
		$email->finalize();
		$this->assertSame('<https://example.com/t?email=' . $email->getTrackingId() . '&unsubscribe=1>', $email->getHeader('List-Unsubscribe'));
		$this->assertSame('Ann Bcc: eve@example.com', $email->getHeader('X-Customer'));
	}

	public function testFinalizeActsOnce()
	{
		$email = new TEmail();
		$email->setBody($body = $this->body());
		$email->setData(['NAME' => 'Ann']);
		$email->finalize();
		$email->setData(['NAME' => 'Bob']);
		$email->finalize();
		$this->assertSame(1, $body->count);
		$this->assertSame('<p>Hello Ann</p>', $email->getHtml());
		$this->assertSame('Hello Ann', $email->getText());
	}

	public function testCloneAfterRenderSharesContentWithNewIds()
	{
		$template = new TEmail();
		$template->setBody($body = $this->body());
		$template->setMessageId('first@example.com');
		$template->setStatus(TEmailStatus::Failed);
		$template->setError('boom');
		$template->render();
		$ids = [$template->getTrackingId()];
		foreach (['Ann', 'Bob'] as $name) {
			$copy = clone $template;
			$copy->setData(['NAME' => $name]);
			$copy->finalize();
			$this->assertSame("<p>Hello {$name}</p>", $copy->getHtml());
			$this->assertNull($copy->getMessageId());
			$this->assertSame(TEmailStatus::New, $copy->getStatus());
			$this->assertNull($copy->getError());
			$ids[] = $copy->getTrackingId();
		}
		$this->assertSame(1, $body->count);
		$this->assertCount(3, array_unique($ids));
		$this->assertSame('<p>Hello {{NAME}}</p>', $template->getHtml());
		$this->assertFalse($template->getIsFinal());
	}

	public function testSendUsesTheMailer()
	{
		$mailer = new class () implements IEmailMailer {
			public array $sent = [];

			public function sendEmail(TEmail $email): string
			{
				$this->sent[] = $email;
				return TEmailStatus::Sent;
			}
		};
		$email = new TEmail();
		$email->setMailer($mailer);
		$this->assertSame($mailer, $email->getMailer());
		$this->assertSame(TEmailStatus::Sent, $email->send());
		$this->assertSame([$email], $mailer->sent);
	}

	public function testSerializationDropsMailerAndBody()
	{
		$email = new TEmail();
		$email->setTo('Ann <ann@example.com>');
		$email->setSubject('Hi');
		$email->setHtml('<p>Hi</p>');
		$email->attachData('x', 'x.txt');
		$email->setBody($this->body());
		$email->setMailer(new class () implements IEmailMailer {
			public function sendEmail(TEmail $email): string
			{
				return TEmailStatus::Sent;
			}
		});
		$id = $email->getTrackingId();
		$copy = unserialize(serialize($email));
		$this->assertInstanceOf(TEmail::class, $copy);
		$this->assertNull($copy->getMailer());
		$this->assertNull($copy->getBody());
		$this->assertSame('Ann', $copy->getTo()[0]->getName());
		$this->assertSame('Hi', $copy->getSubject());
		$this->assertSame('<p>Hi</p>', $copy->getHtml());
		$this->assertSame('x', $copy->getFiles()[0]->getContent());
		$this->assertSame($id, $copy->getTrackingId());
	}
}
