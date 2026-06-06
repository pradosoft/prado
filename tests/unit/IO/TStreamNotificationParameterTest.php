<?php

use Prado\IO\TStreamNotificationParameter;

/**
 * Tests for the {@see \Prado\IO\TStreamNotificationParameter} event-parameter data holder.
 */
class TStreamNotificationParameterTest extends PHPUnit\Framework\TestCase
{
	public function testIsAnEventParameter()
	{
		self::assertInstanceOf(\Prado\TEventParameter::class, new TStreamNotificationParameter());
	}

	public function testDefaults()
	{
		$p = new TStreamNotificationParameter();
		self::assertSame(0, $p->getNotificationCode());
		self::assertSame(0, $p->getSeverity());
		self::assertNull($p->getMessage());
		self::assertSame(0, $p->getMessageCode());
		self::assertSame(0, $p->getBytesTransferred());
		self::assertSame(0, $p->getBytesMax());
	}

	public function testConstructorPopulatesAllFields()
	{
		$p = new TStreamNotificationParameter(7, 2, 'connected', 200, 1024, 4096);
		self::assertSame(7, $p->getNotificationCode());
		self::assertSame(2, $p->getSeverity());
		self::assertSame('connected', $p->getMessage());
		self::assertSame(200, $p->getMessageCode());
		self::assertSame(1024, $p->getBytesTransferred());
		self::assertSame(4096, $p->getBytesMax());
	}

	public function testSettersRoundTrip()
	{
		$p = new TStreamNotificationParameter();
		$p->setNotificationCode(3);
		$p->setSeverity(1);
		$p->setMessage('progress');
		$p->setMessageCode(0);
		$p->setBytesTransferred(512);
		$p->setBytesMax(2048);
		self::assertSame(3, $p->getNotificationCode());
		self::assertSame(1, $p->getSeverity());
		self::assertSame('progress', $p->getMessage());
		self::assertSame(512, $p->getBytesTransferred());
		self::assertSame(2048, $p->getBytesMax());
	}
}
