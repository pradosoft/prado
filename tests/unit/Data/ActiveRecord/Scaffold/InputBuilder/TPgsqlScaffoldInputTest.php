<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput;
use Prado\Data\Common\Pgsql\TPgsqlTableColumn;

/**
 * Covers the PostgreSQL column type to input control mapping and the value read back.
 */
class TPgsqlScaffoldInputTest extends ScaffoldInputTestCase
{
	private function column(string $type, array $info = []): TPgsqlTableColumn
	{
		return new TPgsqlTableColumn($this->columnInfo($type, $info));
	}

	public static function controlProvider(): array
	{
		return [
			['boolean', 'checkbox'],
			['date', 'date'],
			['text', 'multiline'],
			['smallint', 'integer'],
			['integer', 'integer'],
			['bigint', 'integer'],
			['decimal', 'float'],
			['numeric', 'float'],
			['real', 'float'],
			['double precision', 'float'],
			['time without time zone', 'time'],
			['timestamp without time zone', 'datetime'],
			['TIMESTAMP WITHOUT TIME ZONE', 'datetime'],
			['character varying', 'text'],
			['uuid', 'text'],
		];
	}

	/**
	 * @dataProvider controlProvider
	 * @param string $type
	 * @param string $kind
	 */
	public function testCreateControl(string $type, string $kind)
	{
		$item = $this->build(new TPgsqlScaffoldInput(), $this->column($type), $this->newRecord());

		$this->assertSame($kind, $this->inputKind($item));
	}

	public static function valueProvider(): array
	{
		return [
			'boolean' => ['boolean', '1', true],
			'date' => ['date', '2001-02-03', '2001-02-03'],
			'time' => ['time without time zone', '04:05:06', '04:05:06'],
			'timestamp' => ['timestamp without time zone', '2001-02-03 04:05:06', '2001-02-03 04:05:06'],
			'text' => ['text', 'abc', 'abc'],
			'integer' => ['integer', '12', '12'],
			'empty integer' => ['integer', '', null],
			'empty numeric' => ['numeric', '', null],
			'empty character varying' => ['character varying', '', ''],
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
		$builder = new TPgsqlScaffoldInput();
		$record = $this->newRecord(['value' => $value]);
		$item = $this->build($builder, $column, $record);

		$this->assertSame($expected, $this->load($builder, $item, $column, $record));
	}

	public function testValue_sequenceColumnIsNotWritten()
	{
		$column = $this->column('integer', ['IsPrimaryKey' => true, 'SequenceName' => 'items_id_seq']);
		$builder = new TPgsqlScaffoldInput();
		$record = $this->newRecord(['value' => '3']);
		$item = $this->build($builder, $column, $record);

		$this->assertFalse($this->input($item)->getEnabled());
		$this->input($item)->setText('4');
		$this->assertSame('3', $this->load($builder, $item, $column, $record));
	}
}
