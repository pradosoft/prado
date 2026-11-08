<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TFirebirdScaffoldInput;
use Prado\Data\Common\Firebird\TFirebirdTableColumn;

/**
 * Covers the Firebird column type to input control mapping and the value read back.
 * Firebird metadata reports upper case type names; the mapping ignores case.
 */
class TFirebirdScaffoldInputTest extends ScaffoldInputTestCase
{
	private function column(string $type, array $info = []): TFirebirdTableColumn
	{
		return new TFirebirdTableColumn($this->columnInfo($type, $info));
	}

	public static function controlProvider(): array
	{
		return [
			['DATE', 'date'],
			['date', 'date'],
			['TIME', 'time'],
			['TIME WITH TIME ZONE', 'time'],
			['TIMESTAMP', 'datetime'],
			['TIMESTAMP WITH TIME ZONE', 'datetime'],
			['SMALLINT', 'integer'],
			['INTEGER', 'integer'],
			['BIGINT', 'integer'],
			['FLOAT', 'float'],
			['DOUBLE PRECISION', 'float'],
			['DECIMAL', 'float'],
			['NUMERIC', 'float'],
			['DECFLOAT(16)', 'float'],
			['DECFLOAT(34)', 'float'],
			['BOOLEAN', 'checkbox'],
			['CHAR', 'multiline'],
			['VARCHAR', 'multiline'],
			['TEXT', 'multiline'],
			['BLOB', 'multiline'],
			['INT128', 'text'],
		];
	}

	/**
	 * @dataProvider controlProvider
	 * @param string $type
	 * @param string $kind
	 */
	public function testCreateControl(string $type, string $kind)
	{
		$item = $this->build(new TFirebirdScaffoldInput(), $this->column($type), $this->newRecord());

		$this->assertSame($kind, $this->inputKind($item));
	}

	public static function valueProvider(): array
	{
		return [
			'date' => ['DATE', '2001-02-03', '2001-02-03'],
			'time' => ['TIME', '04:05:06.0000', '04:05:06'],
			'time with zone' => ['TIME WITH TIME ZONE', '04:05:06', '04:05:06'],
			'timestamp' => ['TIMESTAMP', '2001-02-03 04:05:06', '2001-02-03 04:05:06'],
			'timestamp with zone' => ['TIMESTAMP WITH TIME ZONE', '2001-02-03 04:05:06', '2001-02-03 04:05:06'],
			'boolean' => ['BOOLEAN', '1', true],
			'varchar' => ['VARCHAR', 'abc', 'abc'],
		];
	}

	/**
	 * @dataProvider valueProvider
	 * @param mixed $expected
	 * @param string $type
	 * @param string $value
	 */
	public function testValue(string $type, string $value, $expected)
	{
		$column = $this->column($type);
		$builder = new TFirebirdScaffoldInput();
		$record = $this->newRecord(['value' => $value]);
		$item = $this->build($builder, $column, $record);

		$this->assertSame($expected, $this->load($builder, $item, $column, $record));
	}
}
