<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\ActiveRecord\TActiveRecordGateway;
use Prado\Data\ActiveRecord\TActiveRecordInvalidFinderResult;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;

class LifecycleItemRecord extends TActiveRecord
{
	public const TABLE = 'items';

	public static $COLUMN_MAPPING = ['item_name' => 'name'];

	public static $RELATIONS = [
		'owner' => [self::BELONGS_TO, LifecycleOwnerRecord::class, 'owner_id'],
		'Parts' => [self::HAS_MANY, LifecycleItemRecord::class],
	];

	public $id;
	public $name;
	public $owner_id;

	public function fetchRelation($property)
	{
		return $this->fetchResultsFor($property);
	}

	public function criteriaFor($criteria, $parameters = [], $args = [])
	{
		return $this->getRecordCriteria($criteria, $parameters, $args);
	}
}

class LifecycleOwnerRecord extends TActiveRecord
{
	public const TABLE = 'owners';

	public $owner_id;
}

/** Records the keys the finder passes to the gateway and returns no rows. */
class LifecycleRecordingGateway extends TActiveRecordGateway
{
	public array $calls = [];

	public function findRecordByPK(TActiveRecord $record, $keys)
	{
		$this->calls[] = ['findRecordByPK', $keys];
		return [];
	}

	public function findRecordsByPks(TActiveRecord $record, $keys)
	{
		$this->calls[] = ['findRecordsByPks', $keys];
		return [];
	}

	public function deleteRecordsByPk(TActiveRecord $record, $keys)
	{
		$this->calls[] = ['deleteRecordsByPk', $keys];
		return count($keys);
	}
}

/**
 * Covers the TActiveRecord behavior that runs without a database: record state checks,
 * event cancellation, column mapping, relation property magic, dynamic method dispatch,
 * the invalid finder setting, and serialization.
 */
class TActiveRecordTest extends \PHPUnit\Framework\TestCase
{
	private $_manager;
	private $_invalidFinderResult;

	protected function setUp(): void
	{
		$this->_manager = TActiveRecordManager::getInstance();
		$this->_invalidFinderResult = $this->_manager->getInvalidFinderResult();
	}

	protected function tearDown(): void
	{
		$this->_manager->setInvalidFinderResult($this->_invalidFinderResult);
		TActiveRecordManager::getInstance($this->_manager);
	}

	public function testConstruct_withConnection_usesIt()
	{
		$connection = new TDbConnection('sqlite::memory:');
		$record = new LifecycleItemRecord(['id' => 3], $connection);
		$this->assertSame($connection, $record->getDbConnection());
		$this->assertSame(3, $record->id);
	}

	public function testCopyFrom_object_copiesPublicProperties()
	{
		$data = new \stdClass();
		$data->id = 7;
		$data->name = 'seven';
		$record = new LifecycleItemRecord();
		$record->copyFrom($data);
		$this->assertSame(7, $record->id);
		$this->assertSame('seven', $record->name);
	}

	public function testCopyFrom_record_leavesStateAndConnection()
	{
		$connection = new TDbConnection('sqlite::memory:');
		$source = TActiveRecord::createRecord(LifecycleItemRecord::class, ['id' => 8, 'name' => 'eight']);
		$source->setDbConnection($connection);
		$this->assertSame(TActiveRecord::STATE_LOADED, PradoUnit::getProp($source, '_recordState'));

		$record = new LifecycleItemRecord();
		$record->copyFrom($source);

		$this->assertSame(8, $record->id);
		$this->assertSame('eight', $record->name);
		$this->assertSame(TActiveRecord::STATE_NEW, PradoUnit::getProp($record, '_recordState'));
		$this->assertNull(PradoUnit::getProp($record, '_connection'));
	}

	public function testCopyFrom_object_skipsNonPublicAndUninitializedProperties()
	{
		$data = new class () {
			public $id = 9;
			public int $owner_id;
			public static $name = 'static';
			protected $name_protected = 'protected';
			private $name_private = 'private';
		};
		$record = new LifecycleItemRecord();
		$record->copyFrom($data);
		$this->assertSame(9, $record->id);
		$this->assertNull($record->owner_id);
		$this->assertNull($record->name);
	}

	public function testCopyFrom_scalar_throws()
	{
		$record = new LifecycleItemRecord();
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/LifecycleItemRecord/');
		$record->copyFrom(5);
	}

	public function testColumnValue_appliesColumnMapping()
	{
		$record = new LifecycleItemRecord(['item_name' => 'mapped']);
		$this->assertSame('mapped', $record->name);
		$this->assertSame('mapped', $record->getColumnValue('item_name'));
		$record->setColumnValue('item_name', 'changed');
		$this->assertSame('changed', $record->name);
		$this->assertSame('changed', $record->getColumnValue('name'));
	}

	public function testCreateRecord_emptyData_returnsNull()
	{
		$this->assertNull(TActiveRecord::createRecord(LifecycleItemRecord::class, []));
		$record = TActiveRecord::createRecord(LifecycleItemRecord::class, ['id' => 1]);
		$this->assertInstanceOf(LifecycleItemRecord::class, $record);
		$this->assertSame(TActiveRecord::STATE_LOADED, PradoUnit::getProp($record, '_recordState'));
	}

	public function testSave_cancelledByOnInsert_returnsFalseAndStaysNew()
	{
		$record = new LifecycleItemRecord(['id' => 1]);
		$record->OnInsert[] = function ($sender, TActiveRecordChangeEventParameter $param) {
			$param->setIsValid(false);
		};
		$this->assertFalse($record->save());
		$this->assertSame(TActiveRecord::STATE_NEW, PradoUnit::getProp($record, '_recordState'));
	}

	public function testSave_cancelledByOnUpdate_returnsFalse()
	{
		$record = TActiveRecord::createRecord(LifecycleItemRecord::class, ['id' => 1]);
		$record->OnUpdate[] = function ($sender, TActiveRecordChangeEventParameter $param) {
			$param->setIsValid(false);
		};
		$this->assertFalse($record->save());
	}

	public function testSave_deletedRecord_throws()
	{
		$record = TActiveRecord::createRecord(LifecycleItemRecord::class, ['id' => 1]);
		PradoUnit::setProp($record, '_recordState', TActiveRecord::STATE_DELETED);
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/LifecycleItemRecord/');
		$record->save();
	}

	public function testDelete_cancelledByOnDelete_returnsFalseAndStaysLoaded()
	{
		$record = TActiveRecord::createRecord(LifecycleItemRecord::class, ['id' => 1]);
		$record->OnDelete[] = function ($sender, TActiveRecordChangeEventParameter $param) {
			$param->setIsValid(false);
		};
		$this->assertFalse($record->delete());
		$this->assertSame(TActiveRecord::STATE_LOADED, PradoUnit::getProp($record, '_recordState'));
	}

	public function testDelete_newRecord_throws()
	{
		$record = new LifecycleItemRecord(['id' => 1]);
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/LifecycleItemRecord/');
		$record->delete();
	}

	public function testFindByPk_null_returnsNullWithoutQuery()
	{
		$this->assertNull((new LifecycleItemRecord())->findByPk(null));
	}

	public function testGetRecordCriteria_copiesCriteriaObject()
	{
		$record = new LifecycleItemRecord();
		$criteria = new \Prado\Data\ActiveRecord\TActiveRecordCriteria('id = ?', [1]);
		$copy = $record->criteriaFor($criteria);
		$this->assertNotSame($criteria, $copy);
		$this->assertSame('id = ?', $copy->getCondition());
		$copy->setLimit(1);
		$this->assertNull($criteria->getLimit());
	}

	public function testGetRecordCriteria_stringUsesArgsWhenParametersAreNotAnArray()
	{
		$record = new LifecycleItemRecord();
		$criteria = $record->criteriaFor('id = ? AND name = ?', 1, [1, 'a']);
		$this->assertSame([1, 'a'], $criteria->getParameters()->toArray());
		$criteria = $record->criteriaFor('id = ?', [2], [9]);
		$this->assertSame([2], $criteria->getParameters()->toArray());
		$this->assertNull($record->criteriaFor(null, [])->getCondition());
	}

	public function testRecordRelations_areCaseInsensitive()
	{
		$record = new LifecycleItemRecord();
		$this->assertTrue($record->hasRecordRelation('OWNER'));
		$this->assertTrue($record->hasRecordRelation('parts'));
		$this->assertFalse($record->hasRecordRelation('name'));
		$this->assertSame(['Parts', [TActiveRecord::HAS_MANY, LifecycleItemRecord::class]], $record->getRecordRelation('PARTS'));
		$this->assertNull($record->getRecordRelation('missing'));
		$this->assertSame(['owner', 'parts'], array_keys($record->getRecordRelations()));
	}

	public function testRelationProperty_assignedValueIsReadBackWithoutQuery()
	{
		$record = new LifecycleItemRecord();
		$owner = new LifecycleOwnerRecord(['owner_id' => 4]);
		$this->assertFalse(isset($record->owner));
		$record->owner = $owner;
		$this->assertTrue(isset($record->owner));
		$this->assertSame($owner, $record->owner);
		$record->owner = null;
		$this->assertFalse(isset($record->owner));
		$this->assertNull($record->owner);
	}

	public function testRelationProperty_unsetDiscardsTheValue()
	{
		$record = new LifecycleItemRecord();
		$record->Parts = [new LifecycleItemRecord()];
		$this->assertTrue(isset($record->parts));
		unset($record->Parts);
		$this->assertFalse(isset($record->Parts));
		$this->assertSame([], PradoUnit::getProp($record, '_relationsObjs'));

		$record->PARTS = ['assigned'];
		$this->assertSame(['assigned'], $record->parts);
		$this->assertSame(['Parts' => ['assigned']], PradoUnit::getProp($record, '_relationsObjs'));
	}

	public function testIssetAndUnset_ofComponentPropertiesUseTComponent()
	{
		$connection = new TDbConnection('sqlite::memory:');
		$record = new LifecycleItemRecord([], $connection);
		$this->assertTrue(isset($record->DbConnection));
		$this->assertFalse(isset($record->NoSuchProperty));
		unset($record->DbConnection);
		$this->assertNull(PradoUnit::getProp($record, '_connection'));
	}

	public function testWith_unknownRelation_returnsNull()
	{
		$record = new LifecycleItemRecord();
		$this->assertNull($record->withMissing());
		$this->assertNull($record->with_Missing());
		$this->assertNull($record->with());
	}

	public function testFetchResultsFor_unknownRelation_returnsFalse()
	{
		$this->assertFalse((new LifecycleItemRecord())->fetchRelation('missing'));
	}

	public function testCall_dyAndFxMethods_goToTComponent()
	{
		$record = new LifecycleItemRecord();
		$this->assertSame('value', $record->dyAnything('value'));
		$this->assertNull($record->fxAnything());
	}

	public function testCall_invalidFinder_returnsNullByDefault()
	{
		$this->_manager->setInvalidFinderResult(TActiveRecordInvalidFinderResult::Null);
		$record = new LifecycleItemRecord();
		$this->assertSame(TActiveRecordInvalidFinderResult::Null, $record->getInvalidFinderResult());
		$this->assertNull($record->retrieveEverything());
	}

	public function testCall_invalidFinder_throwsWhenManagerSaysException()
	{
		$this->_manager->setInvalidFinderResult(TActiveRecordInvalidFinderResult::Exception);
		$record = new LifecycleItemRecord();
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/retrieveEverything/');
		$record->retrieveEverything();
	}

	public function testInvalidFinderResult_recordSettingOverridesManager()
	{
		$this->_manager->setInvalidFinderResult(TActiveRecordInvalidFinderResult::Exception);
		$record = new LifecycleItemRecord();
		$record->setInvalidFinderResult('Null');
		$this->assertSame(TActiveRecordInvalidFinderResult::Null, $record->getInvalidFinderResult());
		$this->assertNull($record->retrieveEverything());

		$record->setInvalidFinderResult(null);
		$this->assertSame(TActiveRecordInvalidFinderResult::Exception, $record->getInvalidFinderResult());
	}

	public function testInvalidFinderResult_rejectsUnknownValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new LifecycleItemRecord())->setInvalidFinderResult('Silent');
	}

	public function testSerialize_dropsConnectionAndKeepsState()
	{
		$connection = new TDbConnection('sqlite::memory:');
		$record = TActiveRecord::createRecord(LifecycleItemRecord::class, ['id' => 5, 'item_name' => 'five']);
		$record->setDbConnection($connection);
		$record->owner = new LifecycleOwnerRecord(['owner_id' => 2]);

		$serialized = serialize($record);
		$this->assertStringNotContainsString('_connection', $serialized);

		$copy = unserialize($serialized);
		$this->assertInstanceOf(LifecycleItemRecord::class, $copy);
		$this->assertSame(5, $copy->id);
		$this->assertSame('five', $copy->name);
		$this->assertSame(TActiveRecord::STATE_LOADED, PradoUnit::getProp($copy, '_recordState'));
		$this->assertSame(2, $copy->owner->owner_id);
		$this->assertNull(PradoUnit::getProp($copy, '_connection'));
		$this->assertSame('five', $copy->getColumnValue('item_name'));
		$this->assertTrue($copy->hasRecordRelation('owner'));
	}

	public function testPrimaryKeyFinders_packArgumentsIntoKeyList()
	{
		$manager = new TActiveRecordManager();
		$manager->setGatewayClass(LifecycleRecordingGateway::class);
		TActiveRecordManager::getInstance($manager);
		$record = new LifecycleItemRecord();

		$this->assertNull($record->findByPk(1));
		$this->assertNull($record->findByPk(1, 2));
		$this->assertSame([], $record->findAllByPks(1, 2));
		$this->assertSame([], $record->findAllByPks([1, 2]));
		$this->assertSame(2, $record->deleteByPk(1, 2));
		$this->assertSame(2, $record->deleteAllByPks(3, 4));
		$this->assertSame(1, $record->deleteAllByPks(5));

		$this->assertSame([
			['findRecordByPK', 1],
			['findRecordByPK', [1, 2]],
			['findRecordsByPks', [1, 2]],
			['findRecordsByPks', [1, 2]],
			['deleteRecordsByPk', [1, 2]],
			['deleteRecordsByPk', [3, 4]],
			['deleteRecordsByPk', [5]],
		], $manager->getRecordGateway()->calls);
	}
}
