<?php

namespace Prado\Test\Unit\Data\DbSpecific\Pgsql\Common;

use Prado\Data\Common\Pgsql\TPgsqlCommandBuilder;
use Prado\Data\Common\Pgsql\TPgsqlMetaData;
use Prado\Data\Common\Pgsql\TPgsqlTableColumn;
use Prado\Data\Common\Pgsql\TPgsqlTableInfo;
use Prado\Data\TDbConnection;
use Prado\Test\Unit\PradoUnit;

/**
 * Server-less tests for the PostgreSQL search and type helpers.
 *
 * The builder and metadata run on an in-memory SQLite connection; the tested
 * methods read only column types and build SQL text.
 */
class TPgsqlCommandBuilderTest extends \PHPUnit\Framework\TestCase
{
	private TDbConnection $_conn;

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$this->_conn = new TDbConnection('sqlite::memory:');
	}

	private function builder(): TPgsqlCommandBuilder
	{
		return new TPgsqlCommandBuilder($this->_conn, new TPgsqlTableInfo(['TableName' => 't']));
	}

	public static function searchableTypeProvider(): array
	{
		return [
			'character varying' => ['character varying', true],
			'varchar' => ['VARCHAR', true],
			'character' => ['character', true],
			'char' => ['char', true],
			'text' => ['text', true],
			'integer' => ['integer', false],
		];
	}

	/**
	 * @dataProvider searchableTypeProvider
	 */
	public function testSearchableColumnTypes(string $type, bool $searchable): void
	{
		$column = new TPgsqlTableColumn(['DbType' => $type]);

		$this->assertSame($searchable, PradoUnit::invoke($this->builder(), 'isSearchableColumn', $column));
	}

	public function testSearchExpressionWithoutFieldsIsEmpty(): void
	{
		$this->assertSame('', $this->builder()->getSearchExpression([], 'word'));
	}

	public static function precisionTypeProvider(): array
	{
		return [
			'numeric' => [' NUMERIC ', true],
			'interval' => ['interval', true],
			'time' => ['timestamp without time zone', true],
			'text' => ['text', false],
		];
	}

	/**
	 * @dataProvider precisionTypeProvider
	 */
	public function testPrecisionTypes(string $type, bool $precision): void
	{
		$this->assertSame($precision, PradoUnit::invoke(new TPgsqlMetaData($this->_conn), 'isPrecisionType', $type));
	}
}
