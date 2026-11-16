<?php

namespace Prado\Test\Unit\Util\Email\Mailbox;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNetworkException;
use Prado\Util\Email\Mailbox\IEmailMailbox;
use Prado\Util\Email\Mailbox\TImapMailbox;

class TImapMailboxTestMailbox extends TImapMailbox
{
	public array $script = [];
	public string $sent = '';
	public ?string $remote = null;
	public bool $crypto = false;
	private string $buffer = '';

	public function respond(string ...$lines): void
	{
		$this->buffer .= implode('', $lines);
	}

	protected function connectTransport(string $remote): void
	{
		$this->remote = $remote;
		$this->respond("* OK IMAP4rev1 ready\r\n");
	}

	protected function enableTransportCrypto(): void
	{
		$this->crypto = true;
	}

	protected function writeData(string $data): void
	{
		$this->sent .= $data;
		$tag = strtok($data, ' ');
		if (($reply = array_shift($this->script)) !== null) {
			$this->respond(str_replace('TAG', $tag, $reply));
		}
	}

	protected function readLine(): string
	{
		if (($pos = strpos($this->buffer, "\n")) === false) {
			throw new TNetworkException('imapmailbox_read_failed', 'test');
		}
		$line = substr($this->buffer, 0, $pos + 1);
		$this->buffer = substr($this->buffer, $pos + 1);
		return $line;
	}

	protected function readBytes(int $length): string
	{
		$data = substr($this->buffer, 0, $length);
		$this->buffer = substr($this->buffer, $length);
		return $data;
	}

	protected function closeTransport(): void
	{
	}
}

class TImapMailboxTest extends TestCase
{
	private function mailbox(array $script): TImapMailboxTestMailbox
	{
		$mailbox = new TImapMailboxTestMailbox();
		$mailbox->setDsn('imaps://user%40example.com:p%22w@imap.example.com/Bounces');
		$mailbox->script = $script;
		return $mailbox;
	}

	public function testImplementsInterface()
	{
		$this->assertInstanceOf(IEmailMailbox::class, new TImapMailbox());
	}

	public function testDsn()
	{
		$mailbox = new TImapMailbox();
		$mailbox->setDsn('imap://u:p@host.example.com:1143/Box%20A?encryption=none&auth=xoauth2&token=T');
		$this->assertSame('host.example.com', $mailbox->getHost());
		$this->assertSame(1143, $mailbox->getPort());
		$this->assertSame('none', $mailbox->getEncryption());
		$this->assertSame('XOAUTH2', $mailbox->getAuthMechanism());
		$this->assertSame('T', $mailbox->getAccessToken());
		$this->assertSame('Box A', $mailbox->getMailbox());
		$mailbox->setDsn('imap://h.example.com');
		$this->assertSame('starttls', $mailbox->getEncryption());
		$this->expectException(TConfigurationException::class);
		$mailbox->setDsn('not a dsn');
	}

	public function testReadsUnseenMessages()
	{
		$message = "Subject: Bounce\r\n\r\nBody {not a literal}\r\n";
		$mailbox = $this->mailbox([
			"TAG OK LOGIN done\r\n",
			"* 2 EXISTS\r\nTAG OK [READ-WRITE] SELECT done\r\n",
			"* SEARCH 7 9\r\nTAG OK SEARCH done\r\n",
			'* 1 FETCH (UID 7 BODY[] {' . strlen($message) . "}\r\n" . $message . ")\r\nTAG OK FETCH done\r\n",
			"* 1 FETCH (FLAGS (\\Seen))\r\nTAG OK STORE done\r\n",
			"* BYE\r\nTAG OK LOGOUT done\r\n",
		]);
		$this->assertSame(['7', '9'], $mailbox->getUnseenMessageIds());
		$this->assertSame('ssl://imap.example.com:993', $mailbox->remote);
		$this->assertSame($message, $mailbox->fetchMessage('7'));
		$mailbox->markSeen('7');
		$mailbox->close();
		$this->assertStringContainsString("A0001 LOGIN \"user@example.com\" \"p\\\"w\"\r\n", $mailbox->sent);
		$this->assertStringContainsString("A0002 SELECT \"Bounces\"\r\n", $mailbox->sent);
		$this->assertStringContainsString("A0003 UID SEARCH UNSEEN\r\n", $mailbox->sent);
		$this->assertStringContainsString("A0004 UID FETCH 7 BODY.PEEK[]\r\n", $mailbox->sent);
		$this->assertStringContainsString("A0005 UID STORE 7 +FLAGS (\\Seen)\r\n", $mailbox->sent);
		$this->assertStringContainsString("A0006 LOGOUT\r\n", $mailbox->sent);
	}

	public function testStartTlsAndXoauth2()
	{
		$mailbox = new TImapMailboxTestMailbox();
		$mailbox->setDsn('imap://me@example.com@imap.example.com/INBOX?auth=xoauth2&token=TOKEN');
		$mailbox->script = ["TAG OK Begin TLS\r\n", "TAG OK AUTHENTICATE done\r\n", "TAG OK SELECT done\r\n"];
		$mailbox->open();
		$this->assertTrue($mailbox->crypto);
		$this->assertSame('tcp://imap.example.com:143', $mailbox->remote);
		$this->assertStringContainsString('AUTHENTICATE XOAUTH2 ' . base64_encode("user=me@example.com\x01auth=Bearer TOKEN\x01\x01"), $mailbox->sent);
	}

	public function testRejectedLoginHidesPassword()
	{
		$mailbox = $this->mailbox(["TAG NO [AUTHENTICATIONFAILED] Invalid credentials\r\n"]);
		try {
			$mailbox->open();
			$this->fail('Expected TNetworkException');
		} catch (TNetworkException $e) {
			$this->assertStringContainsString('AUTHENTICATIONFAILED', $e->getMessage());
			$this->assertStringNotContainsString('p"w', $e->getMessage());
		}
	}

	public function testRejectsLineBreaksAndBadUids()
	{
		$mailbox = $this->mailbox([]);
		$mailbox->setPassword("x\r\nA9 DELETE INBOX");
		try {
			$mailbox->open();
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertStringNotContainsString('DELETE', $mailbox->sent);
		}
		$this->expectException(TConfigurationException::class);
		$this->mailbox(["TAG OK\r\n", "TAG OK\r\n"])->markSeen('1:*');
	}
}
