<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput;
use Prado\Data\Common\Sqlite\TSqliteTableColumn;

/**
 * Covers the SQLite column type to input control mapping and the value read back.
 * A `timestamp` column holding a Unix time is edited as a date and time and saved
 * as a Unix time; a `timestamp` column holding date text keeps the text form.
 */
class TSqliteScaffoldInputTest extends ScaffoldInputTestCase
{
	/** 2024-02-29 13:05:09 UTC */
	private const UNIX_TIME = 1709211909;

	private string $_timezone;

	protected function setUp(): void
	{
		parent::setUp();
		$this->_timezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
	}

	protected function tearDown(): void
	{
		date_default_timezone_set($this->_timezone);
		parent::tearDown();
	}

	private function column(string $type, array $info = []): TSqliteTableColumn
	{
		return new TSqliteTableColumn($this->columnInfo($type, $info));
	}

	public static function controlProvider(): array
	{
		return [
			['boolean', 'checkbox'],
			['BOOLEAN', 'checkbox'],
			['date', 'date'],
			['blob', 'multiline'],
			['tinyblob', 'multiline'],
			['mediumblob', 'multiline'],
			['longblob', 'multiline'],
			['text', 'multiline'],
			['tinytext', 'multiline'],
			['mediumtext', 'multiline'],
			['longtext', 'multiline'],
			['year', 'year'],
			['int', 'integer'],
			['integer', 'integer'],
			['tinyint', 'integer'],
			['smallint', 'integer'],
			['mediumint', 'integer'],
			['bigint', 'integer'],
			['decimal', 'float'],
			['double', 'float'],
			['float', 'float'],
			['time', 'time'],
			['datetime', 'datetime'],
			['timestamp', 'datetime'],
			['varchar', 'text'],
			['real', 'text'],
		];
	}

	/**
	 * @dataProvider controlProvider
	 * @param string $type
	 * @param string $kind
	 */
	public function testCreateControl(string $type, string $kind)
	{
		$item = $this->build(new TSqliteScaffoldInput(), $this->column($type), $this->newRecord());

		$this->assertSame($kind, $this->inputKind($item));
	}

	public function testValue_boolean()
	{
		$column = $this->column('boolean');
		$builder = new TSqliteScaffoldInput();
		$record = $this->newRecord(['value' => '1']);
		$item = $this->build($builder, $column, $record);

		$this->assertTrue($this->load($builder, $item, $column, $record));
		$this->input($item)->setChecked(false);
		$this->assertFalse($this->load($builder, $item, $column, $record));
	}

	public function testValue_dateYearTime()
	{
		$builder = new TSqliteScaffoldInput();

		$column = $this->column('date');
		$record = $this->newRecord(['value' => '1999-12-31']);
		$item = $this->build($builder, $column, $record);
		$this->assertSame('1999-12-31', $this->load($builder, $item, $column, $record));

		$year = date('Y');
		$column = $this->column('year');
		$record = $this->newRecord(['value' => $year]);
		$item = $this->build($builder, $column, $record);
		$this->assertSame($year, $this->load($builder, $item, $column, $record));

		$column = $this->column('time');
		$record = $this->newRecord(['value' => '01:02:03']);
		$item = $this->build($builder, $column, $record);
		$this->assertSame('01:02:03', $this->load($builder, $item, $column, $record));
	}

	public function testValue_dateTimeText()
	{
		$column = $this->column('datetime');
		$builder = new TSqliteScaffoldInput();
		$record = $this->newRecord(['value' => '2024-02-29 13:05:09']);
		$item = $this->build($builder, $column, $record);

		$this->assertSame('2024-02-29 13:05:09', $this->load($builder, $item, $column, $record));
	}

	public function testValue_default()
	{
		$column = $this->column('varchar');
		$builder = new TSqliteScaffoldInput();
		$record = $this->newRecord(['value' => 'v']);
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setText('w');
		$this->assertSame('w', $this->load($builder, $item, $column, $record));
	}

	public function testTimestamp_unixTimeSelectsDateAndTime()
	{
		$builder = new TSqliteScaffoldInput();
		$item = $this->build($builder, $this->column('timestamp'), $this->newRecord(['value' => (string) self::UNIX_TIME]));

		$this->assertSame('2024-02-29', $this->input($item)->getDate());
		$this->assertSame('13', $this->input($item, 'scaffold_time_hour')->getSelectedValue());
		$this->assertSame('5', $this->input($item, 'scaffold_time_min')->getSelectedValue());
		$this->assertSame('9', $this->input($item, 'scaffold_time_sec')->getSelectedValue());
	}

	public function testTimestamp_unixTimeRoundTrips()
	{
		$column = $this->column('timestamp');
		$builder = new TSqliteScaffoldInput();
		$record = $this->newRecord(['value' => (string) self::UNIX_TIME]);
		$item = $this->build($builder, $column, $record);

		$this->assertSame(self::UNIX_TIME, $this->load($builder, $item, $column, $record));

		$this->input($item)->setDate('2024-03-01');
		$this->input($item, 'scaffold_time_hour')->setSelectedValue('0');
		$this->input($item, 'scaffold_time_min')->setSelectedValue('0');
		$this->input($item, 'scaffold_time_sec')->setSelectedValue('1');
		$this->assertSame(gmmktime(0, 0, 1, 3, 1, 2024), $this->load($builder, $item, $column, $record));
	}

	public function testTimestamp_newRecordSavesUnixTime()
	{
		$column = $this->column('timestamp');
		$builder = new TSqliteScaffoldInput();
		$record = $this->newRecord();
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setDate('2024-02-29');
		$this->input($item, 'scaffold_time_hour')->setSelectedValue('13');
		$this->input($item, 'scaffold_time_min')->setSelectedValue('5');
		$this->input($item, 'scaffold_time_sec')->setSelectedValue('9');
		$this->assertSame(self::UNIX_TIME, $this->load($builder, $item, $column, $record));
	}

	public function testTimestamp_dateTextKeepsTextForm()
	{
		$column = $this->column('timestamp');
		$builder = new TSqliteScaffoldInput();
		$record = $this->newRecord(['value' => '2024-02-29 13:05:09']);
		$item = $this->build($builder, $column, $record);

		$this->assertSame('2024-02-29', $this->input($item)->getDate());
		$this->assertSame('13', $this->input($item, 'scaffold_time_hour')->getSelectedValue());
		$this->assertSame('2024-02-29 13:05:09', $this->load($builder, $item, $column, $record));
	}
}
