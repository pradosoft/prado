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

	private static function createEnumerableClassLowerValue(array $constants): string
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
		$className = $this->createEnumerableClass(['Left' => 'LeftValue', 'Right' => 'RightValue']);
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
		$className = $this->createEnumerableClass(['Left' => 'LeftValue', 'Right' => 'RightValue']);

		self::assertTrue($className::hasConstant('Left', false));
		self::assertTrue($className::hasConstant('left', false));
		self::assertTrue($className::hasConstant('LEFT', false));
	}

	public function testHasConstantWithNonExistentClass()
	{
		self::assertFalse(TEnumerable::hasConstant('SomeConstant'));
		self::assertFalse(TEnumerable::hasConstant('NonExistent'));
	}

	public function testHasConstantWithPrefixFilter()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'InnerRight' => 'InnerRightValue',
			'OuterLeft' => 'OuterLeftValue',
		]);

		self::assertTrue($className::hasConstant('InnerLeft'));
		self::assertTrue($className::hasConstant('InnerRight'));
		self::assertTrue($className::hasConstant('OuterLeft'));
		self::assertFalse($className::hasConstant('Center'));
	}

	public function testHasConstantWithPrefixFilterCaseInsensitive()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'InnerRight' => 'InnerRightValue',
		]);

		self::assertTrue($className::hasConstant('innerleft', false));
		self::assertTrue($className::hasConstant('INNERRIGHT', false));
		self::assertFalse($className::hasConstant('innerleft', true));
	}

	public function testHasConstantWithSuffixFilterStar()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'OuterLeft' => 'OuterLeftValue',
		]);

		self::assertTrue($className::hasConstant('InnerLeft'));
		self::assertTrue($className::hasConstant('OuterLeft'));
		self::assertFalse($className::hasConstant('InnerRight'));
	}

	public function testHasConstantWithSuffixFilterDash()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'OuterLeft' => 'OuterLeftValue',
		]);

		self::assertTrue($className::hasConstant('InnerLeft'));
		self::assertTrue($className::hasConstant('OuterLeft'));
	}

	public function testHasConstantWithSuffixFilterCaseInsensitive()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
		]);

		self::assertTrue($className::hasConstant('innerleft', false));
		self::assertFalse($className::hasConstant('innerleft', true));
	}

	public function testHasConstantWithEmptyAffix()
	{
		$className = $this->createEnumerableClass([
			'Left' => 'Left',
		]);

		self::assertTrue($className::hasConstant('Left'));
	}

	public function testValueOfConstant()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1', 'Right' => 'Right2']);

		self::assertEquals('Left1', $className::valueOfConstant('Left'));
		self::assertEquals('Right2', $className::valueOfConstant('Right'));
		self::assertNull($className::valueOfConstant('Center'));
		self::assertNull($className::valueOfConstant('left'));
		self::assertNull($className::valueOfConstant('LEFT'));
	}

	public function testValueOfConstantCaseInsensitive()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left1', 'Right' => 'Right2']);

		self::assertEquals('Left1', $className::valueOfConstant('Left', false));
		self::assertEquals('Left1', $className::valueOfConstant('left', false));
		self::assertEquals('Left1', $className::valueOfConstant('LEFT', false));
		self::assertEquals('Right2', $className::valueOfConstant('RIGHT', false));
	}

	public function testValueOfConstantNotFound()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertNull($className::valueOfConstant('Center'));
		self::assertNull($className::valueOfConstant('Center', false));
	}

	public function testValueOfConstantWithPrefixFilter()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'InnerRight' => 'InnerRightValue',
			'OuterLeft' => 'OuterLeftValue',
		]);

		self::assertEquals('InnerLeftValue', $className::valueOfConstant('InnerLeft', 'Inner'));
		self::assertEquals('OuterLeftValue', $className::valueOfConstant('OuterLeft', 'Out'));
		self::assertNull($className::valueOfConstant('Center', 'Inner'));
	}

	public function testValueOfConstantWithSuffixFilter()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'OuterLeft' => 'OuterLeftValue',
			'InnerRight' => 'InnerRightValue',
		]);

		self::assertEquals('InnerLeftValue', $className::valueOfConstant('InnerLeft', '*Left'));
		self::assertEquals('InnerRightValue', $className::valueOfConstant('InnerRight', '*Right'));
		self::assertNull($className::valueOfConstant('InnerLeft', '*Right'));
	}

	public function testValueOfConstantWithPrefixFilterCaseInsensitive()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'innerRight' => 'innerRightValue',
		]);

		self::assertEquals('InnerLeftValue', $className::valueOfConstant('innerLeft', false));
		self::assertNull($className::valueOfConstant('innerLeft', true));
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

	public function testConstantOf()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertEquals('Left', $className::constantOfValue('Left'));
		self::assertEquals('Right', $className::constantOfValue('Right'));
		self::assertNull($className::constantOfValue('Center'));
		self::assertNull($className::constantOfValue('left'));
		self::assertNull($className::constantOfValue('LEFT'));
	}

	public function testConstantOfCaseInsensitive()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertEquals('Left', $className::constantOfValue('Left', false));
		self::assertEquals('Left', $className::constantOfValue('left', false));
		self::assertEquals('Left', $className::constantOfValue('LEFT', false));
		self::assertEquals('Right', $className::constantOfValue('RIGHT', false));
	}

	public function testConstantOfNotFound()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left', 'Right' => 'Right']);

		self::assertNull($className::constantOfValue('Center'));
		self::assertNull($className::constantOfValue('Center', false));
	}

	public function testConstantOfWithDifferentNameAndValue()
	{
		$className = $this->createEnumerableClass(['Left' => 'LeftValue', 'Right' => 'RightValue']);

		self::assertEquals('Left', $className::constantOfValue('LeftValue'));
		self::assertEquals('Right', $className::constantOfValue('RightValue'));
		self::assertNull($className::constantOfValue('Left'));
		self::assertNull($className::constantOfValue('leftvalue', true));
		self::assertEquals('Left', $className::constantOfValue('leftvalue', false));
	}

	public function testConstantOfWithPrefixFilter()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftVal',
			'InnerRight' => 'InnerRightVal',
			'OuterLeft' => 'OuterLeftVal',
		]);

		self::assertEquals('InnerLeft', $className::constantOfValue('InnerLeftVal', 'Inner'));
		self::assertEquals('OuterLeft', $className::constantOfValue('OuterLeftVal', 'Out'));
		self::assertNull($className::constantOfValue('InnerLeftVal', 'Out'));
	}

	public function testConstantOfWithSuffixFilter()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftVal',
			'OuterLeft' => 'OuterLeftVal',
			'InnerRight' => 'InnerRightVal',
		]);

		self::assertEquals('InnerLeft', $className::constantOfValue('InnerLeftVal', '*Left'));
		self::assertEquals('InnerRight', $className::constantOfValue('InnerRightVal', '*Right'));
		self::assertNull($className::constantOfValue('InnerLeftVal', '*Right'));
	}

	public function testConstantOfWithPrefixFilterCaseInsensitive()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftVal',
			'innerRight' => 'innerRightVal',
		]);

		self::assertEquals('InnerLeft', $className::constantOfValue('InnerLeftVal', 'inner', false));
		self::assertNull($className::constantOfValue('InnerLeftVal', 'inner', true));
	}

	public function testHasConstantWithMultipleMatchesCaseInsensitive()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeft',
			'innerRight' => 'innerRight',
			'INNERBOTH' => 'INNERBOTH',
		]);

		self::assertTrue($className::hasConstant('innerleft', false));
		self::assertTrue($className::hasConstant('INNERBOTH', false));
		self::assertFalse($className::hasConstant('innerleft', true));
	}

	public function testValueOfConstantWithMultipleMatchesCaseInsensitive()
	{
		$className = $this->createEnumerableClass([
			'InnerLeft' => 'InnerLeftValue',
			'innerRight' => 'innerRightValue',
		]);

		self::assertEquals('InnerLeftValue', $className::valueOfConstant('innerleft', false));
	}

	public function testEmptyConstantsClass()
	{
		$className = $this->createEnumerableClass([]);

		self::assertFalse($className::hasConstant('anything'));
		self::assertNull($className::valueOfConstant('anything'));
		self::assertNull($className::constantOfValue('anything'));
	}

	public function testDefaultParameters()
	{
		$className = $this->createEnumerableClass(['Left' => 'Left']);

		self::assertTrue($className::hasConstant('Left'));
		self::assertTrue($className::hasConstant('Left', true));
		self::assertTrue($className::hasConstant('Left', true, true));
		self::assertEquals('Left', $className::valueOfConstant('Left'));
		self::assertEquals('Left', $className::valueOfConstant('Left', true));
		self::assertEquals('Left', $className::valueOfConstant('Left', true, true));
		self::assertEquals('Left', $className::constantOfValue('Left'));
		self::assertEquals('Left', $className::constantOfValue('Left', true));
		self::assertEquals('Left', $className::constantOfValue('Left', true, true));
	}
}