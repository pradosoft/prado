<?php

namespace Prado\Test\Unit\Email\Transport;

use PHPUnit\Framework\TestCase;
use Prado\Email\Net\TEmailEncryption;
use Prado\Email\TEmail;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Email\Transport\TSmtpAuthMechanism;
use Prado\Email\Transport\TSmtpException;
use Prado\Email\Transport\TSmtpTransport;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Email\Net\TEmailTestServer;

class TSmtpTransportTest extends TestCase
{
	private function email(): TEmail
	{
		$email = new TEmail();
		$email->setFrom('Shop <shop@example.com>');
		$email->setTo('ann@example.com');
		$email->setSubject('Hello');
		$email->setText("Hi\n.dot line\n..two dots\nend");
		$email->setMessageId('abc@example.com');
		return $email;
	}

	private function transport(TEmailTestServer $test, string $encryption = TEmailEncryption::None): TSmtpTransport
	{
		$transport = new TSmtpTransport();
		$transport->setHost('smtp.example.com');
		$transport->setEncryption($encryption);
		$transport->setLocalDomain('client.example.com');
		$transport->setConnection($test->client);
		return $transport;
	}

	private function greet(TEmailTestServer $test, string ...$extensions): void
	{
		$test->reply('220 smtp.example.com ESMTP');
		$lines = array_merge(['smtp.example.com'], $extensions);
		foreach ($lines as $index => $line) {
			$test->reply('250' . ($index === count($lines) - 1 ? ' ' : '-') . $line);
		}
	}

	private function accept(TEmailTestServer $test, int $recipients = 1): void
	{
		$test->reply('250 sender ok', ...array_fill(0, $recipients, '250 recipient ok'));
		$test->reply('354 go ahead', '250 queued as 1');
	}

	public function testDefaultsAndProperties()
	{
		$transport = new TSmtpTransport();
		$this->assertSame('', $transport->getHost());
		$this->assertSame(TEmailEncryption::StartTls, $transport->getEncryption());
		$this->assertSame(587, $transport->getPort());
		$this->assertSame(TSmtpAuthMechanism::Auto, $transport->getAuthMechanism());
		$this->assertSame(30.0, $transport->getTimeout());
		$this->assertTrue($transport->getVerifyPeer());
		$this->assertMatchesRegularExpression('/^(\[127\.0\.0\.1\]|[A-Za-z0-9.\-]+\.[A-Za-z0-9.\-]+)$/', $transport->getLocalDomain());
		$transport->setEncryption('ssl');
		$this->assertSame(TEmailEncryption::Ssl, $transport->getEncryption());
		$this->assertSame(465, $transport->getPort());
		$transport->setEncryption('none');
		$this->assertSame(25, $transport->getPort());
		$transport->setPort('2525');
		$this->assertSame(2525, $transport->getPort());
		$transport->setAuthMechanism('login');
		$this->assertSame(TSmtpAuthMechanism::Login, $transport->getAuthMechanism());
		$transport->setLocalDomain('[192.0.2.1]');
		$this->assertSame('[192.0.2.1]', $transport->getLocalDomain());
	}

	public function testDsn()
	{
		$transport = new TSmtpTransport();
		$transport->setDsn('smtp://user%40example.com:p%40ss%3Aword@smtp.example.com');
		$this->assertSame('smtp.example.com', $transport->getHost());
		$this->assertSame(587, $transport->getPort());
		$this->assertSame(TEmailEncryption::StartTls, $transport->getEncryption());
		$this->assertSame('user@example.com', $transport->getUsername());
		$this->assertSame('p@ss:word', $transport->getPassword());

		$transport->setDsn('smtps://smtp.example.com');
		$this->assertSame(TEmailEncryption::Ssl, $transport->getEncryption());
		$this->assertSame(465, $transport->getPort());
		$this->assertSame('', $transport->getUsername());

		$transport->setDsn('smtp://u@localhost:1025?encryption=none&auth=xoauth2&token=tok123&verify=0&timeout=5');
		$this->assertSame(TEmailEncryption::None, $transport->getEncryption());
		$this->assertSame(1025, $transport->getPort());
		$this->assertSame(TSmtpAuthMechanism::XOAuth2, $transport->getAuthMechanism());
		$this->assertSame('tok123', $transport->getAccessToken());
		$this->assertFalse($transport->getVerifyPeer());
		$this->assertSame(5.0, $transport->getTimeout());
	}

	public function testInvalidDsnThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TSmtpTransport())->setDsn('imap://mail.example.com');
	}

	public function testInvalidLocalDomainThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TSmtpTransport())->setLocalDomain("a\r\nMAIL FROM:<x>");
	}

	public function testHostRequired()
	{
		$this->expectException(TConfigurationException::class);
		(new TSmtpTransport())->send($this->email());
	}

	public function testSend()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$this->greet($test, 'SIZE 1000000', '8BITMIME');
		$this->accept($test, 3);
		$email = $this->email();
		$email->setCc('bob@example.com');
		$email->setBcc('eve@example.com');
		$email->setReturnPath('bounce@example.com');
		$transport->send($email);

		$received = $test->received();
		[$commands, $rest] = explode("DATA\r\n", $received, 2);
		$this->assertSame([
			'EHLO client.example.com',
			'MAIL FROM:<bounce@example.com>',
			'RCPT TO:<ann@example.com>',
			'RCPT TO:<bob@example.com>',
			'RCPT TO:<eve@example.com>',
		], explode("\r\n", rtrim($commands)));
		$this->assertStringEndsWith("\r\n.\r\n", $rest);
		$message = substr($rest, 0, -3);
		$this->assertStringContainsString("To: ann@example.com\r\n", $message);
		$this->assertStringContainsString("Cc: bob@example.com\r\n", $message);
		$this->assertStringNotContainsString('eve@example.com', $message);
		$this->assertStringContainsString("Hi\r\n..dot line\r\n...two dots\r\nend", $message);
		$this->assertSame(['SIZE' => '1000000', '8BITMIME' => ''], $transport->getCapabilities());
	}

	public function testConnectionIsReusedWithReset()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$this->greet($test);
		$this->accept($test);
		$transport->send($this->email());
		$test->received();

		$test->reply('250 reset');
		$this->accept($test);
		$transport->send($this->email());
		$lines = $test->receivedLines();
		$this->assertSame(['RSET', 'MAIL FROM:<shop@example.com>', 'RCPT TO:<ann@example.com>', 'DATA'], array_slice($lines, 0, 4));

		$test->reply('221 bye');
		$transport->close();
		$this->assertSame(['QUIT'], $test->receivedLines());
		$this->assertFalse($test->client->getIsOpen());
	}

	public function testHeloFallback()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$test->reply('220 old server', '502 command not implemented', '250 hello');
		$this->accept($test);
		$transport->send($this->email());
		$this->assertSame(['EHLO client.example.com', 'HELO client.example.com'], array_slice($test->receivedLines(), 0, 2));
		$this->assertSame([], $transport->getCapabilities());
	}

	public function testStartTls()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test, TEmailEncryption::StartTls);
		$this->greet($test, 'STARTTLS');
		$test->reply('220 go ahead', '250-smtp.example.com', '250 AUTH PLAIN');
		$this->accept($test);
		$transport->send($this->email());
		$this->assertSame(['EHLO client.example.com', 'STARTTLS', 'EHLO client.example.com', 'MAIL FROM:<shop@example.com>'], array_slice($test->receivedLines(), 0, 4));
		$this->assertSame(1, $test->tlsStarts);
		$this->assertTrue($test->client->getIsEncrypted());
		$this->assertSame(['AUTH' => 'PLAIN'], $transport->getCapabilities());
	}

	public function testStartTlsNotOfferedIsPermanentFailure()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test, TEmailEncryption::StartTls);
		$this->greet($test, 'AUTH PLAIN');
		try {
			$transport->send($this->email());
			$this->fail('a server without STARTTLS is refused');
		} catch (TSmtpException $e) {
			$this->assertFalse($e->getIsTransient());
			$this->assertSame(0, $test->tlsStarts);
			$this->assertSame(['EHLO client.example.com'], $test->receivedLines());
		}
	}

	public function testStartTlsHandshakeFailureIsTransient()
	{
		$test = new TEmailTestServer();
		$test->tlsResult = false;
		$transport = $this->transport($test, TEmailEncryption::StartTls);
		$this->greet($test, 'STARTTLS');
		$test->reply('220 go ahead');
		try {
			$transport->send($this->email());
			$this->fail('a failed handshake fails the email');
		} catch (TEmailTransportException $e) {
			$this->assertNotInstanceOf(TSmtpException::class, $e);
			$this->assertTrue($e->getIsTransient());
			$this->assertFalse($test->client->getIsOpen());
		}
	}

	public function testAuthPlain()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$transport->setUsername('user');
		$transport->setPassword('secret');
		$this->greet($test, 'AUTH LOGIN PLAIN');
		$test->reply('235 authenticated');
		$this->accept($test);
		$transport->send($this->email());
		$this->assertSame('AUTH PLAIN ' . base64_encode("\0user\0secret"), $test->receivedLines()[1]);
	}

	public function testAuthLoginWhenPlainIsNotOffered()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$transport->setUsername('user');
		$transport->setPassword('secret');
		$this->greet($test, 'AUTH LOGIN');
		$test->reply('334 VXNlcm5hbWU6', '334 UGFzc3dvcmQ6', '235 authenticated');
		$this->accept($test);
		$transport->send($this->email());
		$this->assertSame(['AUTH LOGIN', base64_encode('user'), base64_encode('secret')], array_slice($test->receivedLines(), 1, 3));
	}

	public function testAuthXOAuth2()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$transport->setUsername('user@example.com');
		$transport->setAccessToken('tok123');
		$this->greet($test, 'AUTH XOAUTH2');
		$test->reply('235 authenticated');
		$this->accept($test);
		$transport->send($this->email());
		$this->assertSame('AUTH XOAUTH2 ' . base64_encode("user=user@example.com\x01auth=Bearer tok123\x01\x01"), $test->receivedLines()[1]);
	}

	public function testAuthFailureHidesCredentials()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$transport->setUsername('user');
		$transport->setPassword('secret');
		$this->greet($test, 'AUTH PLAIN');
		$test->reply('535 5.7.8 bad credentials');
		try {
			$transport->send($this->email());
			$this->fail('refused credentials fail the email');
		} catch (TSmtpException $e) {
			$this->assertSame(535, $e->getReplyCode());
			$this->assertSame('5.7.8 bad credentials', $e->getReplyText());
			$this->assertFalse($e->getIsTransient());
			$this->assertStringNotContainsString(base64_encode("\0user\0secret"), $e->getMessage());
			$this->assertFalse($test->client->getIsOpen());
		}
	}

	public function testXOAuth2ErrorChallenge()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$transport->setUsername('user');
		$transport->setAccessToken('expired');
		$this->greet($test, 'AUTH XOAUTH2');
		$test->reply('334 eyJzdGF0dXMiOiI0MDEifQ==', '535 5.7.8 token expired');
		try {
			$transport->send($this->email());
			$this->fail('an expired token fails the email');
		} catch (TSmtpException $e) {
			$this->assertSame(535, $e->getReplyCode());
			$this->assertSame('', $test->receivedLines()[2]);
		}
	}

	public function testRefusedRecipientResetsAndKeepsTheConnection()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$this->greet($test);
		$test->reply('250 sender ok', '550 5.1.1 no such user', '250 reset');
		try {
			$transport->send($this->email());
			$this->fail('a refused recipient fails the email');
		} catch (TSmtpException $e) {
			$this->assertSame(550, $e->getReplyCode());
			$this->assertFalse($e->getIsTransient());
			$this->assertStringContainsString('RCPT TO:<ann@example.com>', $e->getMessage());
		}
		$this->assertSame('RSET', array_slice($test->receivedLines(), -1)[0]);
		$this->assertTrue($test->client->getIsOpen());

		$test->reply('250 reset');
		$this->accept($test);
		$transport->send($this->email());
		$this->assertSame('RSET', $test->receivedLines()[0]);
	}

	public function testTemporaryFailureIsTransient()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$this->greet($test);
		$test->reply('250 sender ok', '250 recipient ok', '354 go ahead', '451-4.3.0 try', '451 4.3.0 again later', '250 reset');
		try {
			$transport->send($this->email());
			$this->fail('a 4xx reply fails the email');
		} catch (TSmtpException $e) {
			$this->assertSame(451, $e->getReplyCode());
			$this->assertSame("4.3.0 try\n4.3.0 again later", $e->getReplyText());
			$this->assertTrue($e->getIsTransient());
		}
	}

	public function testLostConnectionIsTransient()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$this->greet($test);
		$test->reply('250 sender ok');
		$test->hangUp();
		try {
			$transport->send($this->email());
			$this->fail('a lost connection fails the email');
		} catch (TEmailTransportException $e) {
			$this->assertNotInstanceOf(TSmtpException::class, $e);
			$this->assertTrue($e->getIsTransient());
			$this->assertFalse($test->client->getIsOpen());
		}
	}

	public function testInvalidReplyIsTransient()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$test->reply('HTTP/1.1 400 Bad Request');
		try {
			$transport->send($this->email());
			$this->fail('a reply that is not SMTP fails the email');
		} catch (TSmtpException $e) {
			$this->assertTrue($e->getIsTransient());
			$this->assertFalse($test->client->getIsOpen());
		}
	}

	public function testEmailWithoutSenderOrRecipientThrows()
	{
		$test = new TEmailTestServer();
		$transport = $this->transport($test);
		$noRecipient = new TEmail();
		$noRecipient->setFrom('shop@example.com');
		try {
			$transport->send($noRecipient);
			$this->fail('an email needs a recipient');
		} catch (TEmailTransportException $e) {
			$this->assertFalse($e->getIsTransient());
		}
		$noSender = new TEmail();
		$noSender->setTo('ann@example.com');
		$this->expectException(TEmailTransportException::class);
		$transport->send($noSender);
	}

	public function testCloseWithoutConnection()
	{
		$transport = new TSmtpTransport();
		$transport->close();
		$this->assertFalse($transport->getConnection()->getIsOpen());
	}
}
