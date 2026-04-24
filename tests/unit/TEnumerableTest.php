<?php

use Prado\TEnumerable;

/**
 */
class TEnumerableTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}


	protected function tearDown(): void
	{
	}

	private static function createEnumerableClass(array $constants): string
	{
		$consts = [];
		foreach ($constants as $name => $value) {
			$consts[] = "const $name = '$value';";
		}
		$className = 'TestEnum' . uniqid();
		$code = "class $className extends \\Prado\\TEnumerable { " . implode(' ', $consts) . " }";
		eval($code);
		return $className;
	}

	public function testConstructor()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1', 'Right' => 'Right2', 'Center' => 'Center3']);
		$enum = new $className();

		self::assertInstanceOf(TEnumerable::class, $enum);
	}

	public function testIterator()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1', 'Right' => 'Right2']);
		$enum = new $className();

		$values = [];
		foreach ($enum as $key => $value) {
			$values[$key] = $value;
		}

		self::assertEquals(['Left' => 'Left1', 'Right' => 'Right2'], $values);
	}

	public function testIteratorRewind()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1', 'Right' => 'Right2', 'Center' => 'Center3']);
		$enum = new $className();

		$firstPass = [];
		foreach ($enum as $key => $value) {
			$firstPass[$key] = $value;
		}

		$secondPass = [];
		foreach ($enum as $key => $value) {
			$secondPass[$key] = $value;
		}

		self::assertEquals($firstPass, $secondPass);
	}

	public function testIteratorWithEmptyConstants()
	{
		$className = $this->createEnumerableClass([]);
		$enum = new $className();

		$values = [];
		foreach ($enum as $key => $value) {
			$values[$key] = $value;
		}

		self::assertEquals([], $values);
	}

	public function testIteratorValid()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1']);
		$enum = new $className();

		self::assertTrue($enum->valid());

		while ($enum->valid()) {
			$enum->next();
		}

		self::assertFalse($enum->valid());
	}

	public function testIteratorCurrent()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1', 'Right' => 'Right2']);
		$enum = new $className();

		self::assertEquals('Left1', $enum->current());

		$enum->next();
		self::assertEquals('Right2', $enum->current());
	}

	public function testIteratorKey()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);
		$enum = new $className();

		self::assertEquals('Left', $enum->key());

		$enum->next();
		self::assertEquals('Right', $enum->key());
	}

	public function testHasConstant()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1', 'Right' => 'Right2']);

		self::assertTrue($className::hasConstant('Left'));
		self::assertTrue($className::hasConstant('Right'));
		self::assertFalse($className::hasConstant('Center'));
		self::assertFalse($className::hasConstant('left'));
		self::assertFalse($className::hasConstant('LEFT'));
	}

	public function testHasConstantCaseInsensitive()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertTrue($className::hasConstant('Left', false));
		self::assertTrue($className::hasConstant('left', false));
		// Note: The $caseSensitive parameter exists but is not currently used by ReflectionClass::hasConstant
		self::assertTrue($className::hasConstant('LEFT', false));
	}

	public function testHasConstantWithNonExistentClass()
	{
		self::assertFalse(TEnumerable::hasConstant('SomeConstant'));
		self::assertFalse(TEnumerable::hasConstant('NonExistent'));
	}

	public function testValueOf()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertEquals('Left', $className::valueOf('Left'));
		self::assertEquals('Right', $className::valueOf('Right'));
		self::assertFalse($className::valueOf('Center'));
		self::assertFalse($className::valueOf('left'));
		self::assertFalse($className::valueOf('LEFT'));
	}

	public function testValueOfCaseInsensitive()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertEquals('Left', $className::valueOf('Left', false));
		self::assertEquals('Left', $className::valueOf('left', false));
		self::assertEquals('Left', $className::valueOf('LEFT', false));
		self::assertEquals('Right', $className::valueOf('RIGHT', false));
	}

	public function testValueOfNotFound()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertFalse($className::valueOf('Center'));
		self::assertFalse($className::valueOf('Center', false));
	}

	public function testAccessConstantValueViaVariable()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		$align = 'Left';
		$value = constant($className . '::' . $align);
		self::assertEquals('Left', $value);

		$align = 'Right';
		$value = constant($className . '::' . $align);
		self::assertEquals('Right', $value);
	}

	public function testAccessConstantValueViaReflection()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		$ref = new ReflectionClass($className);

		$align = 'Left';
		$value = $ref->getConstant($align);
		self::assertEquals('Left', $value);

		$align = 'Right';
		$value = $ref->getConstant($align);
		self::assertEquals('Right', $value);

		$value = $ref->getConstant('Center');
		self::assertFalse($value);
	}
}