<?php

namespace Prado\Test\Unit\Email\Transport;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmail;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Email\Transport\TMailTransport;

class TMailTransportTest extends TestCase
{
	private function transport(bool|\Throwable $result = true): TMailTransport
	{
		return new class ($result) extends TMailTransport {
			public array $calls = [];

			public function __construct(private $_result)
			{
				parent::__construct();
			}

			protected function mail(string $to, string $subject, string $content, array $headers, string $parameters): bool
			{
				$this->calls[] = compact('to', 'subject', 'content', 'headers', 'parameters');
				if ($this->_result instanceof \Throwable) {
					throw $this->_result;
				}
				return $this->_result;
			}
		};
	}

	private function email(): TEmail
	{
		$email = new TEmail();
		$email->setFrom('Shop <shop@example.com>');
		$email->setTo('Zoë <zoe@example.com>, bob@example.com');
		$email->setBcc('eve@example.com');
		$email->setSubject('Größe');
		$email->setText('Hi');
		$email->setHtml('<p>Hi</p>');
		return $email;
	}

	public function testSend()
	{
		$transport = $this->transport();
		$transport->send($this->email());
		$this->assertCount(1, $transport->calls);
		$call = $transport->calls[0];
		$this->assertSame('=?UTF-8?B?' . base64_encode('Zoë') . '?= <zoe@example.com>, bob@example.com', $call['to']);
		$this->assertSame('=?UTF-8?B?' . base64_encode('Größe') . '?=', $call['subject']);
		$this->assertArrayNotHasKey('To', $call['headers']);
		$this->assertArrayNotHasKey('Subject', $call['headers']);
		$this->assertSame('Shop <shop@example.com>', $call['headers']['From']);
		$this->assertSame('eve@example.com', $call['headers']['Bcc']);
		$this->assertSame('1.0', $call['headers']['MIME-Version']);
		$this->assertStringStartsWith('multipart/alternative; boundary="', $call['headers']['Content-Type']);
		$this->assertStringContainsString('Content-Type: text/html; charset=UTF-8', $call['content']);
		$this->assertSame('', $call['parameters']);
		foreach ($call['headers'] as $value) {
			$this->assertDoesNotMatchRegularExpression('/[\r\n]/', $value);
		}
	}

	public function testParameters()
	{
		$transport = $this->transport();
		$transport->setAdditionalParameters('-oi');
		$this->assertFalse($transport->getUseEnvelopeSender());
		$transport->setUseEnvelopeSender('true');
		$email = $this->email();
		$email->setReturnPath('bounce@example.com');
		$transport->send($email);
		$this->assertSame('-oi -fbounce@example.com', $transport->calls[0]['parameters']);
	}

	public function testUnsafeEnvelopeSenderIsNotPassed()
	{
		$transport = $this->transport();
		$transport->setUseEnvelopeSender(true);
		$email = $this->email();
		$email->setReturnPath("o'brien`id`@example.com");
		$transport->send($email);
		$this->assertSame('', $transport->calls[0]['parameters']);
	}

	public function testRefusalIsTransientFailure()
	{
		try {
			$this->transport(false)->send($this->email());
			$this->fail('a refused email throws');
		} catch (TEmailTransportException $e) {
			$this->assertTrue($e->getIsTransient());
		}
	}

	public function testWarningIsFailure()
	{
		try {
			$this->transport(new \RuntimeException('sendmail is missing'))->send($this->email());
			$this->fail('a mail() warning throws');
		} catch (TEmailTransportException $e) {
			$this->assertStringContainsString('sendmail is missing', $e->getMessage());
		}
	}

	public function testEmailWithoutRecipientThrows()
	{
		$transport = $this->transport();
		$email = new TEmail();
		$email->setFrom('shop@example.com');
		try {
			$transport->send($email);
			$this->fail('an email needs a recipient');
		} catch (TEmailTransportException $e) {
			$this->assertSame([], $transport->calls);
		}
	}
}
