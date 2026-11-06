<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Data\ActiveRecord\Relations\TActiveRecordRelationContext;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\TDbConnection;

/**
 * Records over an in-memory SQLite database whose association tables link
 * self_item to itself. self_link_st declares the source_id key first;
 * self_link_ts declares the target_id key first.
 */
abstract class SelfManyToManySqliteRecord extends TActiveRecord
{
	public const SCHEMA = <<<'SQL'
		CREATE TABLE self_item (id integer PRIMARY KEY, name varchar(20));
		CREATE TABLE self_link_st (
			source_id integer NOT NULL,
			target_id integer NOT NULL,
			FOREIGN KEY (source_id) REFERENCES self_item (id),
			FOREIGN KEY (target_id) REFERENCES self_item (id)
		);
		CREATE TABLE self_link_ts (
			source_id integer NOT NULL,
			target_id integer NOT NULL,
			FOREIGN KEY (target_id) REFERENCES self_item (id),
			FOREIGN KEY (source_id) REFERENCES self_item (id)
		);
		INSERT INTO self_item VALUES (1, 'first');
		INSERT INTO self_item VALUES (2, 'connected');
		INSERT INTO self_item VALUES (3, 'other');
		INSERT INTO self_link_st VALUES (1, 2);
		INSERT INTO self_link_st VALUES (3, 1);
		INSERT INTO self_link_ts VALUES (1, 2);
		INSERT INTO self_link_ts VALUES (3, 1);
		SQL;

	public const TABLE = 'self_item';

	protected static $conn;

	public $id;
	public $name;

	/** association column named by the relation, filled on the related records */
	public $source_id;
	/** association column named by the relation, filled on the related records */
	public $target_id;

	public $targets = [];
	public $sources = [];
	public $linked = [];

	public static function resetDatabase()
	{
		self::$conn = null;
		self::connection()->getPdoInstance()->exec(self::SCHEMA);
	}

	public static function connection()
	{
		if (self::$conn === null) {
			self::$conn = new TDbConnection('sqlite::memory:');
			self::$conn->setActive(true);
		}
		return self::$conn;
	}

	public function getDbConnection()
	{
		return self::connection();
	}
}

class SelfItemSourceFirst extends SelfManyToManySqliteRecord
{
	public static $RELATIONS = [
		'targets' => [self::MANY_TO_MANY, SelfItemSourceFirst::class, 'self_link_st.target_id'],
		'sources' => [self::MANY_TO_MANY, SelfItemSourceFirst::class, 'self_link_st.source_id'],
		'linked' => [self::MANY_TO_MANY, SelfItemSourceFirst::class, 'self_link_st'],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

class SelfItemTargetFirst extends SelfManyToManySqliteRecord
{
	public static $RELATIONS = [
		'targets' => [self::MANY_TO_MANY, SelfItemTargetFirst::class, 'self_link_ts.target_id'],
		'sources' => [self::MANY_TO_MANY, SelfItemTargetFirst::class, 'self_link_ts.source_id'],
		'linked' => [self::MANY_TO_MANY, SelfItemTargetFirst::class, 'self_link_ts'],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

/**
 * Regression test for a self-referencing MANY_TO_MANY relation: the association
 * table holds two foreign keys to the same table, declared in either order.
 */
class SelfManyToManyTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		SelfManyToManySqliteRecord::resetDatabase();
	}

	public static function recordClasses(): array
	{
		return [
			'source key declared first' => [SelfItemSourceFirst::class, 'self_link_st'],
			'target key declared first' => [SelfItemTargetFirst::class, 'self_link_ts'],
		];
	}

	private static function ids(array $records): array
	{
		$ids = array_map(fn ($record) => (int) $record->id, $records);
		sort($ids);
		return $ids;
	}

	/**
	 * @dataProvider recordClasses
	 * @param string $class
	 * @param string $link
	 */
	public function testNamedTargetColumn(string $class, string $link)
	{
		$first = $class::finder()->withTargets()->findByPk(1);
		$this->assertSame([2], self::ids($first->targets));

		$connected = $class::finder()->withTargets()->findByPk(2);
		$this->assertSame([], self::ids($connected->targets));
	}

	/**
	 * @dataProvider recordClasses
	 * @param string $class
	 * @param string $link
	 */
	public function testNamedSourceColumn(string $class, string $link)
	{
		$first = $class::finder()->withSources()->findByPk(1);
		$this->assertSame([3], self::ids($first->sources));

		$connected = $class::finder()->withSources()->findByPk(2);
		$this->assertSame([1], self::ids($connected->sources));
	}

	/**
	 * @dataProvider recordClasses
	 * @param string $class
	 * @param string $link
	 */
	public function testFindAllWithBothDirections(string $class, string $link)
	{
		$items = $class::finder()->withTargets()->withSources()->findAll('1=1 ORDER BY id');
		$this->assertSame([1, 2, 3], self::ids($items));
		$this->assertSame([2], self::ids($items[0]->targets));
		$this->assertSame([3], self::ids($items[0]->sources));
		$this->assertSame([], self::ids($items[1]->targets));
		$this->assertSame([1], self::ids($items[1]->sources));
		$this->assertSame([1], self::ids($items[2]->targets));
		$this->assertSame([], self::ids($items[2]->sources));
	}

	/**
	 * Without a named column the direction follows the metadata order of the keys,
	 * which differs between drivers. The relation still joins two different keys.
	 * @dataProvider recordClasses
	 * @param string $class
	 * @param string $link
	 */
	public function testUnnamedColumnUsesTwoKeys(string $class, string $link)
	{
		$first = $class::finder()->withLinked()->findByPk(1);
		$this->assertContains(self::ids($first->linked), [[2], [3]]);
	}

	/**
	 * TActiveRecord::save() does not write relations, so the test runs the
	 * relation context update directly.
	 * @dataProvider recordClasses
	 * @param string $class
	 * @param string $link
	 */
	public function testUpdateAssociatedRecordsInsertsLinkInNamedDirection(string $class, string $link)
	{
		$other = $class::finder()->findByPk(3);
		$other->targets[] = $class::finder()->findByPk(2);
		$context = new TActiveRecordRelationContext($other);
		$this->assertTrue($context->updateAssociatedRecords());

		$command = $class::connection()->createCommand("SELECT source_id, target_id FROM {$link} ORDER BY source_id, target_id");
		$rows = array_map(fn ($row) => [(int) $row['source_id'], (int) $row['target_id']], $command->queryAll());
		$this->assertSame([[1, 2], [3, 1], [3, 2]], $rows);
	}
}
