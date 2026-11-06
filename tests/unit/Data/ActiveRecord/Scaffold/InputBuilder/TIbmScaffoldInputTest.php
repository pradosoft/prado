<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TIbmScaffoldInput;
use Prado\Data\Common\Ibm\TIbmTableColumn;

/**
 * Covers the IBM DB2 column type to input control mapping and the value read back.
 */
class TIbmScaffoldInputTest extends ScaffoldInputTestCase
{
	private function column(string $type, array $info = []): TIbmTableColumn
	{
		return new TIbmTableColumn($this->columnInfo($type, $info));
	}

	public static function controlProvider(): array
	{
		return [
			['date', 'date'],
			['time', 'time'],
			['timestamp', 'datetime'],
			['TIMESTAMP', 'datetime'],
			['smallint', 'integer'],
			['integer', 'integer'],
			['bigint', 'integer'],
			['decimal', 'float'],
			['numeric', 'float'],
			['real', 'float'],
			['float', 'float'],
			['double', 'float'],
			['char', 'multiline'],
			['varchar', 'multiline'],
			['clob', 'text'],
		];
	}

	/**
	 * @dataProvider controlProvider
	 * @param string $type
	 * @param string $kind
	 */
	public function testCreateControl(string $type, string $kind)
	{
		$item = $this->build(new TIbmScaffoldInput(), $this->column($type), $this->newRecord());

		$this->assertSame($kind, $this->inputKind($item));
	}

	public static function valueProvider(): array
	{
		return [
			'date' => ['date', '2001-02-03', '2001-02-03'],
			'time' => ['time', '04:05:06', '04:05:06'],
			'timestamp' => ['timestamp', '2001-02-03 04:05:06.000000', '2001-02-03 04:05:06'],
			'varchar' => ['varchar', 'abc', 'abc'],
		];
	}

	/**
	 * @dataProvider valueProvider
	 * @param string $type
	 * @param string $value
	 * @param string $expected
	 */
	public function testValue(string $type, string $value, string $expected)
	{
		$column = $this->column($type);
		$builder = new TIbmScaffoldInput();
		$record = $this->newRecord(['value' => $value]);
		$item = $this->build($builder, $column, $record);

		$this->assertSame($expected, $this->load($builder, $item, $column, $record));
	}
}
