<?php

use Prado\Data\TDataCharset;
use Prado\Data\TDbColumnCaseMode;
use Prado\Data\TDbDriver;
use Prado\Data\TDbNullConversionMode;

class TDbDriverTest extends PHPUnit\Framework\TestCase
{
	// -------  TDbDriver  -------

	public function test_supported_driver_constants()
	{
		$this->assertSame('mysql', TDbDriver::DRIVER_MYSQL);
		$this->assertSame('pgsql', TDbDriver::DRIVER_PGSQL);
		$this->assertSame('sqlite', TDbDriver::DRIVER_SQLITE);
		$this->assertSame('sqlite2', TDbDriver::DRIVER_SQLITE2);
		$this->assertSame('sqlsrv', TDbDriver::DRIVER_SQLSRV);
		$this->assertSame('dblib', TDbDriver::DRIVER_DBLIB);
		$this->assertSame('oci', TDbDriver::DRIVER_OCI);
		$this->assertSame('ibm', TDbDriver::DRIVER_IBM);
		$this->assertSame('firebird', TDbDriver::DRIVER_FIREBIRD);
		$this->assertSame('interbase', TDbDriver::DRIVER_INTERBASE);
	}

	public function test_unsupported_driver_constants()
	{
		$this->assertSame('odbc', TDbDriver::DRIVER_ODBC);
		$this->assertSame('cubrid', TDbDriver::DRIVER_CUBRID);
		$this->assertSame('informix', TDbDriver::DRIVER_INFORMIX);
	}

	public function test_common_and_extension_constants()
	{
		$this->assertSame('mongo', TDbDriver::DRIVER_MONGO);
		$this->assertSame('mysqli', TDbDriver::EXTENSION_MYSQLI);
		$this->assertSame('mssql', TDbDriver::EXTENSION_MSSQL);
	}

	public function test_driver_values_are_strings()
	{
		$ref = new ReflectionClass(TDbDriver::class);
		foreach ($ref->getConstants() as $name => $value) {
			$this->assertIsString($value, "Constant $name should be a string");
		}
	}

	public function test_driver_values_are_unique()
	{
		$ref = new ReflectionClass(TDbDriver::class);
		$values = array_values($ref->getConstants());
		$unique = array_unique($values);
		$this->assertCount(count($values), $unique, 'All TDbDriver constant values should be unique');
	}

	public function test_all_driver_constants_present()
	{
		$ref = new ReflectionClass(TDbDriver::class);
		$values = array_values($ref->getConstants());
		$this->assertContains('mysql', $values);
		$this->assertContains('pgsql', $values);
		$this->assertContains('sqlite', $values);
		$this->assertContains('sqlite2', $values);
		$this->assertContains('sqlsrv', $values);
		$this->assertContains('dblib', $values);
		$this->assertContains('oci', $values);
		$this->assertContains('ibm', $values);
		$this->assertContains('firebird', $values);
		$this->assertContains('interbase', $values);
		$this->assertContains('odbc', $values);
		$this->assertContains('cubrid', $values);
		$this->assertContains('informix', $values);
		$this->assertContains('mongo', $values);
		$this->assertContains('mysqli', $values);
		$this->assertContains('mssql', $values);
		$this->assertCount(16, $values);
	}

	public function test_enumerable_iterator()
	{
		$enum = new TDbDriver();
		$values = [];
		foreach ($enum as $k => $v) {
			$values[$k] = $v;
		}
		$this->assertArrayHasKey('DRIVER_MYSQL', $values);
		$this->assertSame('mysql', $values['DRIVER_MYSQL']);
		$this->assertCount(16, $values);
	}

	public function test_has_constant()
	{
		$this->assertTrue(TDbDriver::hasConstant('DRIVER_MYSQL'));
		$this->assertTrue(TDbDriver::hasConstant('DRIVER_PGSQL'));
		$this->assertFalse(TDbDriver::hasConstant('DRIVER_NONEXISTENT'));
	}

	public function test_value_of_constant()
	{
		$this->assertSame('mysql', TDbDriver::valueOfConstant('DRIVER_MYSQL'));
		$this->assertSame('pgsql', TDbDriver::valueOfConstant('DRIVER_PGSQL'));
	}

	// -------  TDbColumnCaseMode  -------

	public function test_column_case_mode_constants()
	{
		$this->assertSame('Preserved', TDbColumnCaseMode::Preserved);
		$this->assertSame('LowerCase', TDbColumnCaseMode::LowerCase);
		$this->assertSame('UpperCase', TDbColumnCaseMode::UpperCase);
	}

	public function test_column_case_mode_all_values()
	{
		$ref = new ReflectionClass(TDbColumnCaseMode::class);
		$values = array_values($ref->getConstants());
		$this->assertCount(3, $values);
		$this->assertContains('Preserved', $values);
		$this->assertContains('LowerCase', $values);
		$this->assertContains('UpperCase', $values);
	}

	// -------  TDbNullConversionMode  -------

	public function test_null_conversion_mode_constants()
	{
		$this->assertSame('Preserved', TDbNullConversionMode::Preserved);
		$this->assertSame('NullToEmptyString', TDbNullConversionMode::NullToEmptyString);
		$this->assertSame('EmptyStringToNull', TDbNullConversionMode::EmptyStringToNull);
	}

	public function test_null_conversion_mode_all_values()
	{
		$ref = new ReflectionClass(TDbNullConversionMode::class);
		$values = array_values($ref->getConstants());
		$this->assertCount(3, $values);
		$this->assertContains('Preserved', $values);
		$this->assertContains('NullToEmptyString', $values);
		$this->assertContains('EmptyStringToNull', $values);
	}

	// -------  TDataCharset  -------

	public function test_charset_constants()
	{
		$this->assertSame('UTF-8', TDataCharset::UTF8);
		$this->assertSame('UTF-16', TDataCharset::UTF16);
		$this->assertSame('UTF-16LE', TDataCharset::UTF16LE);
		$this->assertSame('UTF-16BE', TDataCharset::UTF16BE);
		$this->assertSame('ISO-8859-1', TDataCharset::Latin1);
		$this->assertSame('ISO-8859-2', TDataCharset::Latin2);
		$this->assertSame('US-ASCII', TDataCharset::ASCII);
		$this->assertSame('windows-1250', TDataCharset::Win1250);
		$this->assertSame('windows-1251', TDataCharset::Win1251);
		$this->assertSame('windows-1252', TDataCharset::Win1252);
		$this->assertSame('KOI8-R', TDataCharset::KOI8R);
		$this->assertSame('KOI8-U', TDataCharset::KOI8U);
	}

	public function test_charset_all_values()
	{
		$ref = new ReflectionClass(TDataCharset::class);
		$values = array_values($ref->getConstants());
		$this->assertCount(12, $values);
		$this->assertContains('UTF-8', $values);
		$this->assertContains('ISO-8859-1', $values);
		$this->assertContains('US-ASCII', $values);
		$this->assertContains('KOI8-R', $values);
	}

	public function test_charset_iterator()
	{
		$enum = new TDataCharset();
		$values = [];
		foreach ($enum as $k => $v) {
			$values[] = $v;
		}
		$this->assertCount(12, $values);
		$this->assertContains('UTF-8', $values);
	}

	public function test_charset_values_are_iana_registered_names()
	{
		$ref = new ReflectionClass(TDataCharset::class);
		foreach ($ref->getConstants() as $name => $charset) {
			$this->assertMatchesRegularExpression(
				'/^[A-Za-z0-9\-_]+$/',
				$charset,
				"Charset '$charset' is not a valid IANA name"
			);
		}
	}
}
