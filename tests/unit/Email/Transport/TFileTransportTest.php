<?php

namespace Prado\Test\Unit\Email\Transport;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmail;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Email\Transport\TFileTransport;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;

class TFileTransportTest extends TestCase
{
	private string $_dir;

	protected function setUp(): void
	{
		$this->_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-email-' . bin2hex(random_bytes(6));
	}

	protected function tearDown(): void
	{
		foreach (glob($this->_dir . '/*.eml') ?: [] as $file) {
			unlink($file);
		}
		if (is_dir($this->_dir)) {
			rmdir($this->_dir);
		}
	}

	private function email(): TEmail
	{
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		$email->setTo('ann@example.com');
		$email->setBcc('eve@example.com');
		$email->setSubject('Hello');
		$email->setText('Hi');
		return $email;
	}

	public function testSendWritesTheMessage()
	{
		$transport = new TFileTransport();
		$transport->setPath($this->_dir . '/');
		$this->assertSame($this->_dir, $transport->getPath());
		$this->assertNull($transport->getLastFile());
		$email = $this->email();
		$transport->send($email);
		$file = $transport->getLastFile();
		$this->assertFileExists($file);
		$this->assertSame($this->_dir, dirname($file));
		$this->assertMatchesRegularExpression('/^\d{8}-\d{6}-' . $email->getTrackingId() . '\.eml$/', basename($file));
		$message = file_get_contents($file);
		$this->assertStringContainsString("Subject: Hello\r\n", $message);
		$this->assertStringContainsString("Bcc: eve@example.com\r\n", $message);
		$this->assertStringEndsWith("\r\n\r\nHi", $message);
		$transport->close();
	}

	public function testDefaultPathIsInTheRuntimePath()
	{
		$this->assertSame(Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'emails', (new TFileTransport())->getPath());
	}

	public function testNamespacePath()
	{
		$transport = new TFileTransport();
		$transport->setPath('Application.runtime');
		$this->assertSame(Prado::getPathOfNamespace('Application.runtime'), $transport->getPath());
	}

	public function testUnknownNamespaceThrows()
	{
		$this->expectException(TConfigurationException::class);
		(new TFileTransport())->setPath('NoSuchAlias.emails');
	}

	public function testUnwritablePathThrows()
	{
		$transport = new TFileTransport();
		$transport->setPath(__FILE__ . '/sub');
		$this->expectException(TEmailTransportException::class);
		$transport->send($this->email());
	}

	public function testEmailWithoutSenderThrows()
	{
		$transport = new TFileTransport();
		$transport->setPath($this->_dir);
		$email = new TEmail();
		$email->setTo('ann@example.com');
		$this->expectException(TEmailTransportException::class);
		$transport->send($email);
	}
}
