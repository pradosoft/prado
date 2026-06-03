<?php

use Prado\IO\TUriDefaultPort;
use Prado\TEnumerable;

class TUriDefaultPortTest extends PHPUnit\Framework\TestCase
{
	public function testIsEnumerable()
	{
		self::assertInstanceOf(TEnumerable::class, new TUriDefaultPort());
		self::assertSame(80, TUriDefaultPort::HTTP);
		self::assertSame(11434, TUriDefaultPort::OLLAMA);
	}

	public function testForSchemeWebAndMail()
	{
		self::assertSame(80, TUriDefaultPort::forScheme('http'));
		self::assertSame(443, TUriDefaultPort::forScheme('https'));
		self::assertSame(25, TUriDefaultPort::forScheme('smtp'));
		self::assertSame(143, TUriDefaultPort::forScheme('imap'));
		self::assertSame(22, TUriDefaultPort::forScheme('ssh'));
	}

	public function testForSchemeDatabases()
	{
		self::assertSame(3306, TUriDefaultPort::forScheme('mysql'));
		self::assertSame(5432, TUriDefaultPort::forScheme('pgsql'));
		self::assertSame(5432, TUriDefaultPort::forScheme('postgresql'));
		self::assertSame(3050, TUriDefaultPort::forScheme('firebird'));
		self::assertSame(1433, TUriDefaultPort::forScheme('sqlsrv'));
		self::assertSame(1433, TUriDefaultPort::forScheme('mssql'));
		self::assertSame(1521, TUriDefaultPort::forScheme('oci'));
		self::assertSame(1521, TUriDefaultPort::forScheme('oracle'));
		self::assertSame(50000, TUriDefaultPort::forScheme('ibm'));
		self::assertSame(50000, TUriDefaultPort::forScheme('db2'));
	}

	public function testForSchemeAi()
	{
		self::assertSame(11434, TUriDefaultPort::forScheme('ollama'));
	}

	public function testForSchemeCaseInsensitive()
	{
		self::assertSame(443, TUriDefaultPort::forScheme('HTTPS'));
		self::assertSame(1433, TUriDefaultPort::forScheme('SqlSrv'));
	}

	public function testForSchemeUnknownOrInvalid()
	{
		self::assertNull(TUriDefaultPort::forScheme('bogus'));
		self::assertNull(TUriDefaultPort::forScheme(''));
		self::assertNull(TUriDefaultPort::forScheme('svn+ssh'), 'invalid constant name must not error');
	}
}
