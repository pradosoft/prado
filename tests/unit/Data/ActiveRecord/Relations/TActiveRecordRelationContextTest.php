<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Relations;

use Prado\Collections\TList;
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\Relations\TActiveRecordBelongsTo;
use Prado\Data\ActiveRecord\Relations\TActiveRecordHasMany;
use Prado\Data\ActiveRecord\Relations\TActiveRecordHasManyAssociation;
use Prado\Data\ActiveRecord\Relations\TActiveRecordHasOne;
use Prado\Data\ActiveRecord\Relations\TActiveRecordRelationContext;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Test\Unit\PradoUnit;

class ContextTestRecord extends TActiveRecord
{
	public const TABLE = 'context_items';

	public static $RELATIONS = [
		'parent' => [self::BELONGS_TO, ContextTestRecord::class, 'parent_id', 'active = :a', [':a' => 1]],
		'children' => [self::HAS_MANY, ContextTestRecord::class],
		'tags' => [self::MANY_TO_MANY, ContextTestRecord::class, 'item_tags'],
		'detail' => [self::HAS_ONE, ContextTestRecord::class, ''],
	];

	public $id;
	public $parent_id;
	public $parent;
	public $children = [];
	public $tags = [];
	public $detail;
}

class ContextTestEmptyCheck extends TActiveRecordRelationContext
{
	public function isEmpty($value)
	{
		return $this->isEmptyFkObject($value);
	}
}

/**
 * Covers the relation definition accessors of TActiveRecordRelationContext and the
 * handler class chosen for each relation type. None of these touch a database.
 */
class TActiveRecordRelationContextTest extends \PHPUnit\Framework\TestCase
{
	private function context(string $property): TActiveRecordRelationContext
	{
		$record = new ContextTestRecord(['id' => 3, 'parent_id' => 1]);
		return new TActiveRecordRelationContext($record, $property, ContextTestRecord::$RELATIONS[$property]);
	}

	public function testDefinitionAccessors()
	{
		$context = $this->context('parent');
		$this->assertTrue($context->hasRecordRelation());
		$this->assertSame('parent', $context->getProperty());
		$this->assertInstanceOf(ContextTestRecord::class, $context->getSourceRecord());
		$this->assertSame(TActiveRecord::BELONGS_TO, $context->getRelationType());
		$this->assertSame(ContextTestRecord::class, $context->getForeignRecordClass());
		$this->assertSame('parent_id', $context->getFkField());
		$this->assertSame('active = :a', $context->getCondition());
		$this->assertSame([':a' => 1], $context->getParameters());
		$this->assertTrue($context->hasFkField());
		$this->assertFalse($context->hasAssociationTable());
		$this->assertSame(TActiveRecord::finder(ContextTestRecord::class), $context->getForeignRecordFinder());
	}

	public function testPropertyValue_readsTheSourceProperty()
	{
		$context = $this->context('parent');
		$parent = new ContextTestRecord(['id' => 1]);
		$context->getSourceRecord()->parent = $parent;
		$this->assertSame($parent, $context->getPropertyValue());
	}

	public function testOptionalParts_defaultToNoConditionAndNoParameters()
	{
		$context = $this->context('children');
		$this->assertNull($context->getCondition());
		$this->assertSame([], $context->getParameters());
		$this->assertFalse($context->hasFkField());
		$this->assertFalse($context->hasAssociationTable());
	}

	public function testManyToMany_hasAssociationTableAndNoFkField()
	{
		$context = $this->context('tags');
		$this->assertTrue($context->hasAssociationTable());
		$this->assertSame('item_tags', $context->getAssociationTable());
		$this->assertFalse($context->hasFkField());
	}

	public function testEmptyFkField_isNoFkField()
	{
		$this->assertFalse($this->context('detail')->hasFkField());
	}

	public function testGetRelationHandler_createsHandlerPerType()
	{
		$this->assertInstanceOf(TActiveRecordBelongsTo::class, $this->context('parent')->getRelationHandler());
		$this->assertInstanceOf(TActiveRecordHasMany::class, $this->context('children')->getRelationHandler());
		$this->assertInstanceOf(TActiveRecordHasManyAssociation::class, $this->context('tags')->getRelationHandler());
		$this->assertInstanceOf(TActiveRecordHasOne::class, $this->context('detail')->getRelationHandler());
	}

	public function testGetRelationHandler_defaultCriteriaComesFromDefinition()
	{
		$handler = $this->context('parent')->getRelationHandler();
		$criteria = PradoUnit::invoke($handler, 'getCriteria');
		$this->assertSame('active = :a', $criteria->getCondition());
		$this->assertSame([':a' => 1], $criteria->getParameters()->toArray());

		$given = new TActiveRecordCriteria('id > 0');
		$handler = $this->context('parent')->getRelationHandler($given);
		$this->assertSame($given, PradoUnit::invoke($handler, 'getCriteria'));
	}

	public function testGetRelationHandler_withoutRelationThrows()
	{
		$context = new TActiveRecordRelationContext(new ContextTestRecord(), 'missing');
		$this->assertFalse($context->hasRecordRelation());
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/missing/');
		$context->getRelationHandler();
	}

	public function testGetRelationHandler_unknownTypeThrows()
	{
		$context = new TActiveRecordRelationContext(new ContextTestRecord(), 'odd', ['ONE_TO_SOME', ContextTestRecord::class]);
		$this->expectException(TActiveRecordException::class);
		$context->getRelationHandler();
	}

	public function testIsEmptyFkObject()
	{
		$context = new ContextTestEmptyCheck(new ContextTestRecord());
		$this->assertTrue($context->isEmpty(null));
		$this->assertTrue($context->isEmpty([]));
		$this->assertTrue($context->isEmpty(new TList()));
		$this->assertFalse($context->isEmpty(new TList([1])));
		$this->assertFalse($context->isEmpty([1]));
		$this->assertFalse($context->isEmpty(new ContextTestRecord()));
		$this->assertFalse($context->isEmpty('Team a'));
	}
}
