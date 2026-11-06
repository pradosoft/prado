<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput;
use Prado\Data\Common\Mysql\TMysqlTableColumn;

/**
 * Covers the MySQL column type to input control mapping and the value read back.
 * MySQL metadata reports the type without its size, such as `int unsigned` or `tinyint`.
 */
class TMysqlScaffoldInputTest extends ScaffoldInputTestCase
{
	private function column(string $type, array $info = []): TMysqlTableColumn
	{
		return new TMysqlTableColumn($this->columnInfo($type, $info));
	}

	public static function controlProvider(): array
	{
		return [
			['date', [], 'date'],
			['blob', [], 'multiline'],
			['tinyblob', [], 'multiline'],
			['mediumblob', [], 'multiline'],
			['longblob', [], 'multiline'],
			['text', [], 'multiline'],
			['tinytext', [], 'multiline'],
			['mediumtext', [], 'multiline'],
			['longtext', [], 'multiline'],
			['year', [], 'year'],
			['int', ['ColumnSize' => 11], 'integer'],
			['integer', [], 'integer'],
			['tinyint', ['ColumnSize' => 4], 'integer'],
			['tinyint', ['ColumnSize' => 1], 'checkbox'],
			['smallint', [], 'integer'],
			['mediumint', [], 'integer'],
			['bigint', [], 'integer'],
			['int unsigned', [], 'integer'],
			['INT UNSIGNED ZEROFILL', [], 'integer'],
			['decimal', ['NumericPrecision' => 10, 'NumericScale' => 2], 'float'],
			['double', [], 'float'],
			['float unsigned', [], 'float'],
			['time', [], 'time'],
			['datetime', [], 'datetime'],
			['timestamp', [], 'datetime'],
			['set', ['DbTypeValues' => ['a', 'b']], 'set'],
			['enum', ['DbTypeValues' => ['a', 'b']], 'enum'],
			['varchar', ['ColumnSize' => 10], 'text'],
			['char', [], 'text'],
		];
	}

	/**
	 * @dataProvider controlProvider
	 * @param string $type
	 * @param array $info
	 * @param string $kind
	 */
	public function testCreateControl(string $type, array $info, string $kind)
	{
		$item = $this->build(new TMysqlScaffoldInput(), $this->column($type, $info), $this->newRecord());

		$this->assertSame($kind, $this->inputKind($item));
	}

	public function testCreateControl_returnsTheIntegerInput()
	{
		$builder = new class () extends TMysqlScaffoldInput {
			public function callCreateControl($container, $column, $record)
			{
				return $this->createControl($container, $column, $record);
			}
		};
		// createScaffoldInput sets the parent whose validation group the validators read.
		$builder->createScaffoldInput($this->newParent(), $this->newItem(), $this->column('int'), $this->newRecord());

		$integer = $builder->callCreateControl($this->newItem()->_input, $this->column('int'), $this->newRecord());
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TTextBox::class, $integer);

		$boolean = $builder->callCreateControl($this->newItem()->_input, $this->column('tinyint', ['ColumnSize' => 1]), $this->newRecord());
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TCheckBox::class, $boolean);
	}

	public function testValue_date()
	{
		$column = $this->column('date');
		$builder = new TMysqlScaffoldInput();
		$record = $this->newRecord(['value' => '2020-05-06']);
		$item = $this->build($builder, $column, $record);

		$this->assertSame('2020-05-06', $this->load($builder, $item, $column, $record));
	}

	public function testValue_year()
	{
		$column = $this->column('year');
		$builder = new TMysqlScaffoldInput();
		$year = (string) ((int) date('Y') - 2);
		$record = $this->newRecord(['value' => $year]);
		$item = $this->build($builder, $column, $record);

		$this->assertSame($year, $this->load($builder, $item, $column, $record));
	}

	public function testValue_time()
	{
		$column = $this->column('time');
		$builder = new TMysqlScaffoldInput();
		$record = $this->newRecord(['value' => '08:09:10']);
		$item = $this->build($builder, $column, $record);

		$this->assertSame('08:09:10', $this->load($builder, $item, $column, $record));
	}

	public function testValue_dateTimeAndTimestamp()
	{
		$builder = new TMysqlScaffoldInput();
		foreach (['datetime', 'timestamp'] as $type) {
			$column = $this->column($type);
			$record = $this->newRecord(['value' => '2021-12-31 23:59:58']);
			$item = $this->build($builder, $column, $record);

			$this->assertSame('2021-12-31 23:59:58', $this->load($builder, $item, $column, $record), $type);
		}
	}

	public function testValue_tinyintBooleanIsInteger()
	{
		$column = $this->column('tinyint', ['ColumnSize' => 1]);
		$builder = new TMysqlScaffoldInput();
		$record = $this->newRecord(['value' => '1']);
		$item = $this->build($builder, $column, $record);

		$this->assertTrue($this->input($item)->getChecked());
		$this->assertSame(1, $this->load($builder, $item, $column, $record));
		$this->input($item)->setChecked(false);
		$this->assertSame(0, $this->load($builder, $item, $column, $record));
	}

	public function testValue_wideTinyintIsText()
	{
		$column = $this->column('tinyint', ['ColumnSize' => 4]);
		$builder = new TMysqlScaffoldInput();
		$record = $this->newRecord(['value' => '12']);
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setText('99');
		$this->assertSame('99', $this->load($builder, $item, $column, $record));
	}

	public function testValue_setAndEnum()
	{
		$builder = new TMysqlScaffoldInput();

		$column = $this->column('set', ['DbTypeValues' => ['x', 'y', 'z']]);
		$record = $this->newRecord(['value' => 'x,z']);
		$item = $this->build($builder, $column, $record);
		$this->assertSame('x,z', $this->load($builder, $item, $column, $record));

		$column = $this->column('enum', ['DbTypeValues' => ['x', 'y', 'z']]);
		$record = $this->newRecord(['value' => 'y']);
		$item = $this->build($builder, $column, $record);
		$this->assertSame('y', $this->load($builder, $item, $column, $record));
	}

	public function testValue_default()
	{
		$column = $this->column('varchar');
		$builder = new TMysqlScaffoldInput();
		$record = $this->newRecord(['value' => 'abc']);
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setText('def');
		$this->assertSame('def', $this->load($builder, $item, $column, $record));
	}

	public function testValue_autoIncrementKeyIsNotWritten()
	{
		$column = $this->column('int', ['IsPrimaryKey' => true, 'AutoIncrement' => true]);
		$builder = new TMysqlScaffoldInput();
		$record = $this->newRecord(['value' => '7']);
		$item = $this->build($builder, $column, $record, $this->newParent([7]));

		$this->assertFalse($this->input($item)->getEnabled());
		$this->input($item)->setText('8');
		$this->assertSame('7', $this->load($builder, $item, $column, $record, $this->newParent([7])));
	}
}
