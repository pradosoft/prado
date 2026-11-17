<?php

namespace Prado\Test\Unit\Util;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\TVarDumper;

class TVarDumperTestObject
{
	public $public = 1;
	protected $protected = 'two';
	private $private = null;
	public $child;
}

class TVarDumperTestDebugInfo
{
	public $hidden = 'hidden';

	public function __debugInfo()
	{
		return ['shown' => true];
	}
}

class TVarDumperTestBadDebugInfo
{
	public function __debugInfo()
	{
		return 'not an array';
	}
}

class TVarDumperTest extends \PHPUnit\Framework\TestCase
{
	public function testScalars()
	{
		self::assertSame('true', TVarDumper::dump(true));
		self::assertSame('false', TVarDumper::dump(false));
		self::assertSame('42', TVarDumper::dump(42));
		self::assertSame('1.5', TVarDumper::dump(1.5));
		self::assertSame("'it\\'s'", TVarDumper::dump("it's"));
		self::assertSame('null', TVarDumper::dump(null));
	}

	public function testResource()
	{
		$handle = fopen('php://memory', 'r');
		self::assertSame('{resource}', TVarDumper::dump($handle));
		fclose($handle);
		self::assertSame('{resource (closed)}', TVarDumper::dump($handle));
	}

	public function testArrays()
	{
		self::assertSame('array()', TVarDumper::dump([]));
		self::assertSame("array\n(\n    0 => 'a'\n    'k' => array\n    (\n        0 => true\n    )\n)", TVarDumper::dump(['a', 'k' => [true]]));
	}

	public function testArrayDepth()
	{
		self::assertSame('array(...)', TVarDumper::dump([1], 0));
		self::assertSame("array\n(\n    0 => array(...)\n)", TVarDumper::dump([[1]], 1));
	}

	public function testObject()
	{
		$object = new TVarDumperTestObject();
		$class = TVarDumperTestObject::class;
		self::assertSame(
			"$class#1\n(\n    [public] => 1\n    [*:protected] => 'two'\n    [$class:private] => null\n    [child] => null\n)",
			TVarDumper::dump($object)
		);
	}

	public function testObjectRecursionAndDepth()
	{
		$object = new TVarDumperTestObject();
		$object->child = $object;
		$class = TVarDumperTestObject::class;
		self::assertStringContainsString("[child] => $class#1(...)", TVarDumper::dump($object));

		$object->child = new TVarDumperTestObject();
		self::assertStringContainsString("[child] => $class(...)", TVarDumper::dump($object, 1));
		self::assertSame("$class(...)", TVarDumper::dump($object, 0));
	}

	public function testDebugInfo()
	{
		$class = TVarDumperTestDebugInfo::class;
		self::assertSame("$class#1\n(\n    [shown] => true\n)", TVarDumper::dump(new TVarDumperTestDebugInfo()));
	}

	public function testDebugInfoNotArrayThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		TVarDumper::dump(new TVarDumperTestBadDebugInfo());
	}

	public function testIncompleteClass()
	{
		$object = unserialize('O:22:"TVarDumperMissingClass":1:{s:1:"a";i:5;}');
		$dump = TVarDumper::dump($object);
		self::assertStringStartsWith('__PHP_Incomplete_Class#1', $dump);
		self::assertStringContainsString('[a] => 5', $dump);
	}

	public function testHighlight()
	{
		$dump = TVarDumper::dump(['a' => 1], 10, true);
		self::assertStringNotContainsString('&lt;?php', $dump);
		self::assertStringContainsString('array', $dump);
		self::assertStringContainsString('<code', $dump);
	}
}
