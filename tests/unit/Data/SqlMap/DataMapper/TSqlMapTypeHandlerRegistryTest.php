<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\SqlMap\DataMapper\TSqlMapException;
use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;
use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandlerRegistry;

/**
 * Minimal concrete TSqlMapTypeHandler for testing.
 */
class TestTypeHandler extends TSqlMapTypeHandler
{
	public function __construct(string $type = 'TestClass', string $dbType = 'VARCHAR')
	{
		$this->setType($type);
		$this->setDbType($dbType);
	}

	public function getParameter($object)
	{
		return $object;
	}

	public function getResult($string)
	{
		return $string;
	}

	public function createNewInstance($row = null)
	{
		return new self($this->getType(), $this->getDbType());
	}
}

class TSqlMapTypeHandlerRegistryTest extends PHPUnit\Framework\TestCase
{
	private TSqlMapTypeHandlerRegistry $registry;

	protected function setUp(): void
	{
		$this->registry = new TSqlMapTypeHandlerRegistry();
	}

	// -------  createInstanceOf  -------

	public function test_create_string_primitive()
	{
		$this->assertSame('', $this->registry->createInstanceOf('string'));
	}

	public function test_create_array_primitive()
	{
		$this->assertSame([], $this->registry->createInstanceOf('array'));
	}

	public function test_create_float_primitive()
	{
		$this->assertSame(0.0, $this->registry->createInstanceOf('float'));
	}

	public function test_create_double_primitive()
	{
		$this->assertSame(0.0, $this->registry->createInstanceOf('double'));
	}

	public function test_create_decimal_primitive()
	{
		$this->assertSame(0.0, $this->registry->createInstanceOf('decimal'));
	}

	public function test_create_integer_primitive()
	{
		$this->assertSame(0, $this->registry->createInstanceOf('integer'));
	}

	public function test_create_int_primitive()
	{
		$this->assertSame(0, $this->registry->createInstanceOf('int'));
	}

	public function test_create_bool_primitive()
	{
		$this->assertSame(false, $this->registry->createInstanceOf('bool'));
	}

	public function test_create_boolean_primitive()
	{
		$this->assertSame(false, $this->registry->createInstanceOf('boolean'));
	}

	public function test_create_empty_type_returns_null()
	{
		$result = $this->registry->createInstanceOf('');
		$this->assertNull($result);
	}

	public function test_create_null_type_returns_null()
	{
		$result = $this->registry->createInstanceOf(null);
		$this->assertNull($result);
	}

	public function test_create_known_class()
	{
		$result = $this->registry->createInstanceOf(stdClass::class);
		$this->assertInstanceOf(stdClass::class, $result);
	}

	public function test_create_unknown_class_throws()
	{
		// When Prado is loaded, createInstanceOf calls Prado::createComponent which
		// tries new $type() — an unknown class causes an Error (not TSqlMapException).
		// Either Error or TSqlMapException is acceptable; we assert an exception is thrown.
		$this->expectException(\Throwable::class);
		$this->registry->createInstanceOf('CompletelyUnknownClass_XYZ_99999');
	}

	public function test_create_is_case_insensitive_for_primitives()
	{
		$this->assertSame('', $this->registry->createInstanceOf('STRING'));
		$this->assertSame(0, $this->registry->createInstanceOf('INTEGER'));
		$this->assertSame(false, $this->registry->createInstanceOf('BOOLEAN'));
	}

	// -------  convertToType  -------

	public function test_convert_to_integer()
	{
		$result = $this->registry->convertToType('integer', '42');
		$this->assertSame(42, $result);
	}

	public function test_convert_to_int()
	{
		$result = $this->registry->convertToType('int', '100');
		$this->assertSame(100, $result);
	}

	public function test_convert_to_float()
	{
		$result = $this->registry->convertToType('float', '3.14');
		$this->assertSame(3.14, $result);
	}

	public function test_convert_to_double()
	{
		$result = $this->registry->convertToType('double', '2.71');
		$this->assertSame(2.71, $result);
	}

	public function test_convert_to_decimal()
	{
		$result = $this->registry->convertToType('decimal', '1.5');
		$this->assertSame(1.5, $result);
	}

	public function test_convert_to_boolean_true()
	{
		$result = $this->registry->convertToType('boolean', '1');
		$this->assertTrue($result);
	}

	public function test_convert_to_bool_false()
	{
		$result = $this->registry->convertToType('bool', '');
		$this->assertFalse($result);
	}

	public function test_convert_to_string()
	{
		$result = $this->registry->convertToType('string', 42);
		$this->assertSame('42', $result);
	}

	public function test_convert_to_null_type_returns_value_unchanged()
	{
		$original = ['a', 'b'];
		$result = $this->registry->convertToType(null, $original);
		$this->assertSame($original, $result);
	}

	public function test_convert_unknown_type_returns_value_unchanged()
	{
		$original = 'some string';
		$result = $this->registry->convertToType('MyClass', $original);
		$this->assertSame($original, $result);
	}

	// -------  Handler registration and lookup  -------

	public function test_register_and_get_type_handler()
	{
		$handler = new TestTypeHandler('TestClass', 'VARCHAR');
		$this->registry->registerTypeHandler($handler);

		$found = $this->registry->getTypeHandler('TestClass');
		$this->assertSame($handler, $found);
	}

	public function test_get_type_handler_unknown_class_returns_null()
	{
		$result = $this->registry->getTypeHandler('UnknownClass');
		$this->assertNull($result);
	}

	public function test_register_and_get_db_type_handler()
	{
		$handler = new TestTypeHandler('TestClass', 'TIMESTAMP');
		$this->registry->registerTypeHandler($handler);

		$found = $this->registry->getDbTypeHandler('TIMESTAMP');
		$this->assertSame($handler, $found);
	}

	public function test_get_db_type_handler_default_null_type_returns_null()
	{
		$result = $this->registry->getDbTypeHandler('NULL');
		$this->assertNull($result);
	}

	public function test_register_overwrites_same_type()
	{
		$handler1 = new TestTypeHandler('MyClass', 'VARCHAR');
		$handler2 = new TestTypeHandler('MyClass', 'TEXT');
		$this->registry->registerTypeHandler($handler1);
		$this->registry->registerTypeHandler($handler2);

		$found = $this->registry->getTypeHandler('MyClass');
		$this->assertSame($handler2, $found);
	}
}
