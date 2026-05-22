<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\SqlMap\Configuration\TSqlMapCacheKey;

class TSqlMapCacheKeyTest extends PHPUnit\Framework\TestCase
{
	public function test_hash_is_a_hex_string()
	{
		$key = new TSqlMapCacheKey('hello');
		$hash = $key->getHash();
		$this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $hash, 'Hash should be a lowercase hex string');
	}

	public function test_same_object_produces_same_hash()
	{
		$key1 = new TSqlMapCacheKey('hello');
		$key2 = new TSqlMapCacheKey('hello');
		$this->assertSame($key1->getHash(), $key2->getHash());
	}

	public function test_different_objects_produce_different_hashes()
	{
		$key1 = new TSqlMapCacheKey('hello');
		$key2 = new TSqlMapCacheKey('world');
		$this->assertNotSame($key1->getHash(), $key2->getHash());
	}

	public function test_null_value_hashes()
	{
		$key = new TSqlMapCacheKey(null);
		$hash = $key->getHash();
		$this->assertIsString($hash);
		$this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $hash);
	}

	public function test_array_value_hashes()
	{
		$key1 = new TSqlMapCacheKey(['a' => 1, 'b' => 2]);
		$key2 = new TSqlMapCacheKey(['a' => 1, 'b' => 2]);
		$key3 = new TSqlMapCacheKey(['a' => 1, 'b' => 3]);

		$this->assertSame($key1->getHash(), $key2->getHash());
		$this->assertNotSame($key1->getHash(), $key3->getHash());
	}

	public function test_object_value_hashes()
	{
		$obj = new stdClass();
		$obj->foo = 'bar';

		$obj2 = new stdClass();
		$obj2->foo = 'bar';

		$key1 = new TSqlMapCacheKey($obj);
		$key2 = new TSqlMapCacheKey($obj2);
		$this->assertSame($key1->getHash(), $key2->getHash());
	}

	public function test_integer_vs_string_value_produces_different_hashes()
	{
		$key1 = new TSqlMapCacheKey(1);
		$key2 = new TSqlMapCacheKey('1');
		// serialize(1) !== serialize('1'), so hashes must differ
		$this->assertNotSame($key1->getHash(), $key2->getHash());
	}

	public function test_hash_is_crc32_hex_of_serialized()
	{
		$value = 'test_value';
		$expected = sprintf('%x', crc32(serialize($value)));
		$key = new TSqlMapCacheKey($value);
		$this->assertSame($expected, $key->getHash());
	}

	public function test_empty_string_hashes()
	{
		$key1 = new TSqlMapCacheKey('');
		$key2 = new TSqlMapCacheKey('');
		$this->assertSame($key1->getHash(), $key2->getHash());
	}
}
