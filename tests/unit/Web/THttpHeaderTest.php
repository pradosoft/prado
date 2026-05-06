<?php

use Prado\Web\THttpHeader;
use Prado\Web\THttpHeadersManager;

/**
 * Minimal THttpHeadersManager stub — satisfies the type hint in THttpHeader's
 * constructor without requiring a live TApplication.
 */
class TTestHeaderStubManager extends THttpHeadersManager {}

class THttpHeaderTest extends PHPUnit\Framework\TestCase
{
	private TTestHeaderStubManager $manager;

	protected function setUp(): void
	{
		$this->manager = new TTestHeaderStubManager();
	}

	// -----------------------------------------------------------------------
	// Constructor / getManager
	// -----------------------------------------------------------------------

	public function testConstructorStoresManager()
	{
		$header = new THttpHeader($this->manager);
		self::assertSame($this->manager, $header->getManager());
	}

	// -----------------------------------------------------------------------
	// Name
	// -----------------------------------------------------------------------

	public function testGetSetName()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('X-Custom-Header');
		self::assertEquals('X-Custom-Header', $header->getName());
	}

	public function testSetNameOverwrites()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('First');
		$header->setName('Second');
		self::assertEquals('Second', $header->getName());
	}

	public function testNameDefaultIsNull()
	{
		$header = new THttpHeader($this->manager);
		self::assertNull($header->getName());
	}

	public function testSetNameEnforcesString()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('X-Frame-Options');
		self::assertIsString($header->getName());
	}

	// -----------------------------------------------------------------------
	// Value
	// -----------------------------------------------------------------------

	public function testGetSetValue()
	{
		$header = new THttpHeader($this->manager);
		$header->setValue('nosniff');
		self::assertEquals('nosniff', $header->getValue());
	}

	public function testSetValueOverwrites()
	{
		$header = new THttpHeader($this->manager);
		$header->setValue('first');
		$header->setValue('second');
		self::assertEquals('second', $header->getValue());
	}

	public function testValueDefaultIsNull()
	{
		$header = new THttpHeader($this->manager);
		self::assertNull($header->getValue());
	}

	public function testSetValueEnforcesString()
	{
		$header = new THttpHeader($this->manager);
		$header->setValue('max-age=31536000');
		self::assertIsString($header->getValue());
	}

	// -----------------------------------------------------------------------
	// init()
	// -----------------------------------------------------------------------

	public function testInitIsNoOpAndDoesNotThrow()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('X-Test');
		$header->setValue('value');
		// init() is a no-op in the base class; must be callable without side-effects
		$header->init([]);
		self::assertEquals('X-Test', $header->getName());
		self::assertEquals('value', $header->getValue());
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToStringFormat()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('X-Frame-Options');
		$header->setValue('DENY');
		self::assertEquals('X-Frame-Options: DENY', (string) $header);
	}

	public function testToStringWithComplexValue()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('Strict-Transport-Security');
		$header->setValue('max-age=31536000; includeSubDomains');
		self::assertEquals(
			'Strict-Transport-Security: max-age=31536000; includeSubDomains',
			(string) $header
		);
	}

	public function testToStringWithCacheControl()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('Cache-Control');
		$header->setValue('no-cache, no-store, must-revalidate');
		self::assertEquals(
			'Cache-Control: no-cache, no-store, must-revalidate',
			(string) $header
		);
	}

	public function testToStringReflectsUpdatedValue()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('X-Content-Type-Options');
		$header->setValue('nosniff');
		self::assertEquals('X-Content-Type-Options: nosniff', (string) $header);

		$header->setValue('changed');
		self::assertEquals('X-Content-Type-Options: changed', (string) $header);
	}

	public function testToStringContainsColonSpaceSeparator()
	{
		$header = new THttpHeader($this->manager);
		$header->setName('Foo');
		$header->setValue('Bar');
		// Exactly "Name: Value" — one colon followed by one space
		self::assertMatchesRegularExpression('/^[^:]+: .+$/', (string) $header);
	}
}
