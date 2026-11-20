<?php

namespace Prado\Test\Unit\Email\Net;

use PHPUnit\Framework\TestCase;
use Prado\Email\Net\TEmailSocketConnection;
use Prado\Exceptions\TNetworkException;

class TEmailSocketConnectionTest extends TestCase
{
	public function testDefaults()
	{
		$connection = new TEmailSocketConnection();
		$this->assertFalse($connection->getIsOpen());
		$this->assertFalse($connection->getIsEncrypted());
		$this->assertSame(30.0, $connection->getTimeout());
		$this->assertTrue($connection->getVerifyPeer());
		$connection->setTimeout('0.2');
		$this->assertSame(1.0, $connection->getTimeout());
		$connection->setVerifyPeer('false');
		$this->assertFalse($connection->getVerifyPeer());
		$connection->close();
	}

	public function testClosedConnectionThrows()
	{
		$this->expectException(TNetworkException::class);
		(new TEmailSocketConnection())->writeLine('NOOP');
	}

	public function testLines()
	{
		$test = new TEmailTestServer();
		$this->assertTrue($test->client->getIsOpen());
		$test->reply('220 ready', '250-one', '250 two');
		$this->assertSame('220 ready', $test->client->readLine());
		$this->assertSame('250-one', $test->client->readLine());
		$this->assertSame('250 two', $test->client->readLine());
		$test->client->writeLine('EHLO client');
		$test->client->write("A\r\nB\r\n");
		$this->assertSame(['EHLO client', 'A', 'B'], $test->receivedLines());
	}

	public function testReadBytes()
	{
		$test = new TEmailTestServer();
		$data = random_bytes(5000);
		$test->replyRaw($data . "tail\r\n");
		$this->assertSame($data, $test->client->readBytes(5000));
		$this->assertSame('', $test->client->readBytes(0));
		$this->assertSame('tail', $test->client->readLine());
	}

	public function testReadTimeoutThrows()
	{
		$test = new TEmailTestServer();
		$test->replyRaw('partial line without an end');
		$this->expectException(TNetworkException::class);
		$test->client->readLine();
	}

	public function testReadAfterHangUpThrows()
	{
		$test = new TEmailTestServer();
		$test->hangUp();
		$this->expectException(TNetworkException::class);
		$test->client->readLine();
	}

	public function testReadBytesShortThrows()
	{
		$test = new TEmailTestServer();
		$test->replyRaw('abc');
		$test->hangUp();
		$this->expectException(TNetworkException::class);
		$test->client->readBytes(10);
	}

	public function testStartTls()
	{
		$test = new TEmailTestServer();
		$test->client->startTls();
		$this->assertSame(1, $test->tlsStarts);
		$this->assertTrue($test->client->getIsEncrypted());
	}

	public function testStartTlsFailureThrows()
	{
		$test = new TEmailTestServer();
		$test->tlsResult = false;
		try {
			$test->client->startTls();
			$this->fail('a failed handshake throws');
		} catch (TNetworkException $e) {
			$this->assertFalse($test->client->getIsEncrypted());
		}
	}

	public function testClose()
	{
		$test = new TEmailTestServer();
		$test->client->close();
		$this->assertFalse($test->client->getIsOpen());
		$test->client->close();
		$this->expectException(TNetworkException::class);
		$test->client->readLine();
	}

	public function testOpenRefusedThrows()
	{
		$connection = new TEmailSocketConnection();
		$connection->setTimeout(1);
		$this->expectException(TNetworkException::class);
		$connection->open('127.0.0.1', 1);
	}
}
