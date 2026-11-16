<?php

namespace Prado\Test\Unit\Util\Email;

use PHPUnit\Framework\TestCase;
use Prado\Util\Email\TEmailBounce;
use Prado\Util\Email\TEmailBounceParser;

class TEmailBounceParserTest extends TestCase
{
	private function dsn(string $action = 'failed', string $status = '5.1.1'): string
	{
		return "From: Mail Delivery Subsystem <mailer-daemon@googlemail.com>\r\n"
			. "To: sender@example.com\r\n"
			. "Subject: Delivery Status Notification (Failure)\r\n"
			. "In-Reply-To: <reply-id@example.com>\r\n"
			. "Content-Type: multipart/report; boundary=\"BOUND\"; report-type=delivery-status\r\n"
			. "\r\n"
			. "--BOUND\r\n"
			. "Content-Type: text/plain; charset=UTF-8\r\n"
			. "\r\n"
			. "Address not found.\r\n"
			. "--BOUND\r\n"
			. "Content-Type: message/delivery-status\r\n"
			. "\r\n"
			. "Reporting-MTA: dns; googlemail.com\r\n"
			. "X-Original-Message-ID: <abc123@example.com>\r\n"
			. "\r\n"
			. "Final-Recipient: rfc822; nobody@google.com\r\n"
			. "Action: {$action}\r\n"
			. "Status: {$status}\r\n"
			. "Remote-MTA: dns; mx.google.com\r\n"
			. "Diagnostic-Code: smtp; 550 5.1.1 The email account does not\r\n"
			. "    exist.\r\n"
			. "\r\n"
			. "Final-Recipient: rfc822; ok@google.com\r\n"
			. "Action: delivered\r\n"
			. "Status: 2.0.0\r\n"
			. "--BOUND\r\n"
			. "Content-Type: text/rfc822-headers\r\n"
			. "\r\n"
			. "Message-ID: <headers-id@example.com>\r\n"
			. "Subject: Hello\r\n"
			. "--BOUND--\r\n";
	}

	public function testParsesDeliveryStatusNotification()
	{
		$bounces = (new TEmailBounceParser())->parse($this->dsn());
		$this->assertCount(1, $bounces);
		$bounce = $bounces[0];
		$this->assertInstanceOf(TEmailBounce::class, $bounce);
		$this->assertSame('nobody@google.com', $bounce->getRecipient());
		$this->assertSame('failed', $bounce->getAction());
		$this->assertSame('5.1.1', $bounce->getStatus());
		$this->assertSame('550 5.1.1 The email account does not exist.', $bounce->getDiagnosticCode());
		$this->assertSame('<abc123@example.com>', $bounce->getOriginalMessageId());
		$this->assertSame('mx.google.com', $bounce->getRemoteMta());
		$this->assertTrue($bounce->getIsPermanent());
	}

	public function testDelayedIsTransient()
	{
		$bounces = (new TEmailBounceParser())->parse($this->dsn('delayed', '4.4.7'));
		$this->assertSame('delayed', $bounces[0]->getAction());
		$this->assertFalse($bounces[0]->getIsPermanent());
	}

	public function testOriginalIdFallsBackToReturnedHeaders()
	{
		$message = str_replace("X-Original-Message-ID: <abc123@example.com>\r\n", '', $this->dsn());
		$this->assertSame('<headers-id@example.com>', (new TEmailBounceParser())->parse($message)[0]->getOriginalMessageId());
	}

	public function testBase64DeliveryStatus()
	{
		$status = "Reporting-MTA: dns; mx.example.com\n\nFinal-Recipient: rfc822; gone@example.org\nAction: failed\nStatus: 5.2.1\n";
		$message = "Content-Type: multipart/report; boundary=b\n\n--b\nContent-Type: message/delivery-status\nContent-Transfer-Encoding: base64\n\n"
			. chunk_split(base64_encode($status)) . "--b--\n";
		$bounces = (new TEmailBounceParser())->parse($message);
		$this->assertSame('gone@example.org', $bounces[0]->getRecipient());
		$this->assertSame('5.2.1', $bounces[0]->getStatus());
	}

	public function testFailedRecipientsHeaderWithoutDsn()
	{
		$message = "From: postmaster@example.net\nX-Failed-Recipients: a@example.net, b@example.net\nIn-Reply-To: <orig@example.com>\n\nThe message could not be delivered.\n550 5.1.1 User unknown\n";
		$bounces = (new TEmailBounceParser())->parse($message);
		$this->assertCount(2, $bounces);
		$this->assertSame('b@example.net', $bounces[1]->getRecipient());
		$this->assertSame('5.1.1', $bounces[1]->getStatus());
		$this->assertSame('550 5.1.1 User unknown', $bounces[1]->getDiagnosticCode());
		$this->assertSame('<orig@example.com>', $bounces[1]->getOriginalMessageId());
	}

	public function testOrdinaryMessageIsNotABounce()
	{
		$this->assertSame([], (new TEmailBounceParser())->parse("From: a@b.c\nSubject: Hi\n\nHello\n"));
	}

	public function testHelpers()
	{
		$this->assertSame('b c', TEmailBounceParser::getHeaderParameter('multipart/mixed; boundary="b c"', 'boundary'));
		$this->assertSame('xyz', TEmailBounceParser::getHeaderParameter('multipart/mixed; Boundary=xyz; x=1', 'boundary'));
		$this->assertNull(TEmailBounceParser::getHeaderParameter('text/plain', 'boundary'));
		$this->assertSame('a@b.c', TEmailBounceParser::stripAddressType('rfc822; <a@b.c>'));
		$this->assertSame('caf=C3', TEmailBounceParser::decodeContent('caf=3DC3', 'quoted-printable'));
		$this->assertSame(['subject' => ['a b']], TEmailBounceParser::parseHeaders("Subject: a\n b"));
	}

	public function testIsPermanent()
	{
		$this->assertTrue((new TEmailBounce('a@b.c', 'failed', '5.0.0'))->getIsPermanent());
		$this->assertFalse((new TEmailBounce('a@b.c', 'failed', '4.2.2'))->getIsPermanent());
		$this->assertFalse((new TEmailBounce('a@b.c', 'delayed', '4.0.0'))->getIsPermanent());
		$this->assertTrue((new TEmailBounce('a@b.c', 'failed', ''))->getIsPermanent());
	}
}
