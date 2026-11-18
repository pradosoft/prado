<?php

namespace Prado\Test\Unit\Email\Mailbox;

use PHPUnit\Framework\TestCase;
use Prado\Email\Mailbox\IEmailMailbox;
use Prado\Email\Mailbox\TImapMailbox;
use Prado\Email\Net\TEmailEncryption;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNetworkException;
use Prado\Test\Unit\Email\Net\TEmailTestServer;

class TImapMailboxTest extends TestCase
{
	private function mailbox(TEmailTestServer $test, string $encryption = TEmailEncryption::None): TImapMailbox
	{
		$mailbox = new TImapMailbox();
		$mailbox->setHost('imap.example.com');
		$mailbox->setEncryption($encryption);
		$mailbox->setUsername('user@example.com');
		$mailbox->setPassword('pa"ss\\word');
		$mailbox->setConnection($test->client);
		return $mailbox;
	}

	private function login(TEmailTestServer $test): void
	{
		$test->reply('* OK IMAP4rev1 ready', 'A0001 OK logged in', '* 3 EXISTS', 'A0002 OK [READ-WRITE] selected');
	}

	public function testDefaultsAndProperties()
	{
		$mailbox = new TImapMailbox();
		$this->assertInstanceOf(IEmailMailbox::class, $mailbox);
		$this->assertSame(TEmailEncryption::Ssl, $mailbox->getEncryption());
		$this->assertSame(993, $mailbox->getPort());
		$this->assertSame('INBOX', $mailbox->getMailbox());
		$this->assertSame(30.0, $mailbox->getTimeout());
		$this->assertTrue($mailbox->getVerifyPeer());
		$this->assertSame(26214400, $mailbox->getMaxMessageSize());
		$mailbox->setEncryption('starttls');
		$this->assertSame(TEmailEncryption::StartTls, $mailbox->getEncryption());
		$this->assertSame(143, $mailbox->getPort());
		$mailbox->setPort(1143);
		$this->assertSame(1143, $mailbox->getPort());
	}

	public function testDsn()
	{
		$mailbox = new TImapMailbox();
		$mailbox->setDsn('imaps://user%40example.com:p%2Bss@imap.example.com/Bounces%20In');
		$this->assertSame('imap.example.com', $mailbox->getHost());
		$this->assertSame(TEmailEncryption::Ssl, $mailbox->getEncryption());
		$this->assertSame(993, $mailbox->getPort());
		$this->assertSame('user@example.com', $mailbox->getUsername());
		$this->assertSame('p+ss', $mailbox->getPassword());
		$this->assertSame('Bounces In', $mailbox->getMailbox());

		$mailbox->setDsn('imap://u@localhost:1143?encryption=none&token=tok&verify=0&timeout=4');
		$this->assertSame(TEmailEncryption::None, $mailbox->getEncryption());
		$this->assertSame(1143, $mailbox->getPort());
		$this->assertSame('tok', $mailbox->getAccessToken());
		$this->assertFalse($mailbox->getVerifyPeer());
		$this->assertSame(4.0, $mailbox->getTimeout());

		$mailbox->setDsn('imap://u:p@imap.example.com');
		$this->assertSame(TEmailEncryption::StartTls, $mailbox->getEncryption());
		$this->assertSame(143, $mailbox->getPort());
	}

	public function testInvalidDsnThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TImapMailbox())->setDsn('smtp://mail.example.com');
	}

	public function testHostRequired()
	{
		$this->expectException(TConfigurationException::class);
		(new TImapMailbox())->open();
	}

	public function testReadsUnseenMessages()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$message = "From: mailer-daemon@example.com\r\nSubject: Undelivered\r\n\r\nBody with {7} braces\r\nand lines\r\n";
		$this->login($test);
		$test->reply('* SEARCH 4 7', '* SEARCH 9', 'A0003 OK search done');
		$test->replyRaw('* 1 FETCH (UID 4 BODY[] {' . strlen($message) . "}\r\n" . $message . ")\r\n");
		$test->reply('A0004 OK fetch done', '* 1 FETCH (FLAGS (\Seen))', 'A0005 OK store done', '* BYE', 'A0006 OK bye');

		$this->assertSame(['4', '7', '9'], $mailbox->getUnseenMessageIds());
		$this->assertSame($message, $mailbox->fetchMessage('4'));
		$mailbox->markSeen('4');
		$mailbox->close();

		$this->assertSame([
			'A0001 LOGIN "user@example.com" "pa\"ss\\\\word"',
			'A0002 SELECT "INBOX"',
			'A0003 UID SEARCH UNSEEN',
			'A0004 UID FETCH 4 BODY.PEEK[]',
			'A0005 UID STORE 4 +FLAGS (\Seen)',
			'A0006 LOGOUT',
		], $test->receivedLines());
		$this->assertFalse($test->client->getIsOpen());
	}

	public function testNoUnseenMessages()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$this->login($test);
		$test->reply('* SEARCH', 'A0003 OK');
		$this->assertSame([], $mailbox->getUnseenMessageIds());
	}

	public function testStartTls()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test, TEmailEncryption::StartTls);
		$test->reply('* OK ready', 'A0001 OK begin TLS', 'A0002 OK logged in', 'A0003 OK selected');
		$mailbox->open();
		$this->assertSame(1, $test->tlsStarts);
		$this->assertSame('A0001 STARTTLS', $test->receivedLines()[0]);
	}

	public function testXOAuth2()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$mailbox->setAccessToken('tok123');
		$test->reply('* OK ready', 'A0001 OK authenticated', 'A0002 OK selected');
		$mailbox->open();
		$this->assertSame('A0001 AUTHENTICATE XOAUTH2 ' . base64_encode("user=user@example.com\x01auth=Bearer tok123\x01\x01"), $test->receivedLines()[0]);
	}

	public function testXOAuth2ErrorChallengeIsAnswered()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$mailbox->setAccessToken('expired');
		$test->reply('* OK ready', '+ eyJzdGF0dXMiOiI0MDEifQ==', 'A0001 NO authentication failed');
		try {
			$mailbox->open();
			$this->fail('a refused token throws');
		} catch (TNetworkException $e) {
			$this->assertStringContainsString('AUTHENTICATE', $e->getMessage());
			$this->assertStringNotContainsString(base64_encode("user=user@example.com\x01auth=Bearer expired\x01\x01"), $e->getMessage());
		}
		$this->assertSame('', $test->receivedLines()[1]);
		$this->assertFalse($test->client->getIsOpen());
	}

	public function testLoginFailureHidesThePassword()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$test->reply('* OK ready', 'A0001 NO [AUTHENTICATIONFAILED] invalid credentials');
		try {
			$mailbox->open();
			$this->fail('refused credentials throw');
		} catch (TNetworkException $e) {
			$this->assertStringNotContainsString('word', $e->getMessage());
			$this->assertStringContainsString('AUTHENTICATIONFAILED', $e->getMessage());
		}
	}

	public function testBadGreetingThrows()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$test->reply('* BYE server busy');
		$this->expectException(TNetworkException::class);
		$mailbox->open();
	}

	public function testMissingMessageThrows()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$this->login($test);
		$test->reply('A0003 OK fetch done');
		$this->expectException(TNetworkException::class);
		$mailbox->fetchMessage('99');
	}

	public function testOversizedMessageIsRefused()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$mailbox->setMaxMessageSize(100);
		$this->login($test);
		$test->reply('* 1 FETCH (UID 4 BODY[] {101}');
		try {
			$mailbox->fetchMessage('4');
			$this->fail('a message over the limit throws');
		} catch (TNetworkException $e) {
			$this->assertFalse($test->client->getIsOpen());
		}
	}

	public function testInvalidUidThrows()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$this->login($test);
		$this->expectException(TInvalidDataValueException::class);
		$mailbox->fetchMessage("4\r\nA9 DELETE INBOX");
	}

	public function testCredentialWithLineBreakThrows()
	{
		$test = new TEmailTestServer();
		$mailbox = $this->mailbox($test);
		$mailbox->setPassword("x\r\nA9 DELETE INBOX");
		$test->reply('* OK ready');
		$this->expectException(TInvalidDataValueException::class);
		$mailbox->open();
	}

	public function testCloseWithoutOpen()
	{
		$mailbox = new TImapMailbox();
		$mailbox->close();
		$this->assertFalse($mailbox->getConnection()->getIsOpen());
	}
}
