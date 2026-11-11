<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqlSrvScaffoldInput;
use Prado\Data\Common\SqlSrv\TSqlSrvTableColumn;

/**
 * Covers the SQL Server column type to input control mapping and the value read back.
 * An empty text input of a nullable column reads back as null.
 */
class TSqlSrvScaffoldInputTest extends ScaffoldInputTestCase
{
	private function column(string $type, array $info = []): TSqlSrvTableColumn
	{
		return new TSqlSrvTableColumn($this->columnInfo($type, $info));
	}

	public static function controlProvider(): array
	{
		return [
			['bit', 'checkbox'],
			['text', 'multiline'],
			['smallint', 'integer'],
			['int', 'integer'],
			['bigint', 'integer'],
			['tinyint', 'integer'],
			['decimal', 'float'],
			['float', 'float'],
			['money', 'float'],
			['numeric', 'float'],
			['real', 'float'],
			['smallmoney', 'float'],
			['datetime', 'datetime'],
			['smalldatetime', 'datetime'],
			['DATETIME', 'datetime'],
			['varchar', 'text'],
			['nvarchar', 'text'],
			['timestamp', 'text'],
		];
	}

	/**
	 * @dataProvider controlProvider
	 * @param string $type
	 * @param string $kind
	 */
	public function testCreateControl(string $type, string $kind)
	{
		$item = $this->build(new TSqlSrvScaffoldInput(), $this->column($type), $this->newRecord());

		$this->assertSame($kind, $this->inputKind($item));
	}

	public function testCreateControl_rowVersionInputIsDisabled()
	{
		$item = $this->build(new TSqlSrvScaffoldInput(), $this->column('timestamp'), $this->newRecord());
		$this->assertFalse($this->input($item)->getEnabled());

		$item = $this->build(new TSqlSrvScaffoldInput(), $this->column('varchar'), $this->newRecord());
		$this->assertTrue($this->input($item)->getEnabled());
	}

	public static function bitProvider(): array
	{
		return [
			'nullable, checked' => [true, true],
			'nullable, unchecked' => [true, false],
			'not null, checked' => [false, true],
			'not null, unchecked' => [false, false],
		];
	}

	/**
	 * @dataProvider bitProvider
	 * @param bool $allowNull
	 * @param bool $checked
	 */
	public function testValue_bitIsTheCheckedState(bool $allowNull, bool $checked)
	{
		$column = $this->column('bit', ['AllowNull' => $allowNull]);
		$builder = new TSqlSrvScaffoldInput();
		$record = $this->newRecord(['value' => $checked ? '0' : '1']);
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setChecked($checked);
		$this->assertSame($checked, $this->load($builder, $item, $column, $record));
	}

	public function testValue_dateTime()
	{
		$column = $this->column('datetime');
		$builder = new TSqlSrvScaffoldInput();
		$record = $this->newRecord(['value' => '2010-11-12 01:02:03.000']);
		$item = $this->build($builder, $column, $record);

		$this->assertSame('2010-11-12 01:02:03', $this->load($builder, $item, $column, $record));
	}

	public static function textProvider(): array
	{
		return [
			'nullable, empty' => [true, '', null],
			'nullable, blank' => [true, '  ', null],
			'nullable, text' => [true, 'abc', 'abc'],
			'not null, empty' => [false, '', ''],
			'not null, text' => [false, 'abc', 'abc'],
		];
	}

	/**
	 * @dataProvider textProvider
	 * @param bool $allowNull
	 * @param string $text
	 * @param ?string $expected
	 */
	public function testValue_text(bool $allowNull, string $text, ?string $expected)
	{
		$column = $this->column('varchar', ['AllowNull' => $allowNull]);
		$builder = new TSqlSrvScaffoldInput();
		$record = $this->newRecord(['value' => 'old']);
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setText($text);
		$this->assertSame($expected, $this->load($builder, $item, $column, $record));
	}

	public function testValue_emptyNullableIntegerIsNull()
	{
		$column = $this->column('int');
		$builder = new TSqlSrvScaffoldInput();
		$record = $this->newRecord(['value' => '5']);
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setText('');
		$this->assertNull($this->load($builder, $item, $column, $record));
	}
}
