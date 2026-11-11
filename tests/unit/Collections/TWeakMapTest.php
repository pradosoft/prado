<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\IWeakRetainable;
use Prado\Collections\TMap;
use Prado\Collections\TWeakMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventHandler;
use ArrayIterator;
use WeakReference;

/**
 * Test-subclass that exposes WeakMap bookkeeping and allows DiscardInvalid to
 * be changed more than once (needed to verify transition behaviour).
 */
class TWeakMapUnit extends TWeakMap
{
	use TListResetTrait;

	public function getWeakCount(): ?int
	{
		return $this->weakCount();
	}

	public function getWeakObjectCount(object $obj): ?int
	{
		return $this->weakObjectCount($obj);
	}

	/** Bypass the once-only guard so tests can toggle DiscardInvalid. */
	public function resetDiscardInvalid(bool $value): void
	{
		$this->setDiscardInvalid($value);
	}
}

/**
 * A plain object used as a map value.
 */
class WeakMapItem
{
	public mixed $data;

	public function __construct(mixed $data = null)
	{
		$this->data = $data;
	}

	/** Callable method so instances can be used as TEventHandler targets. */
	public function handle(): void
	{
	}
}

/**
 * An object that opts out of WeakReference wrapping.
 */
class WeakMapRetainableItem extends WeakMapItem implements IWeakRetainable
{
}

/**
 * Unit tests for TWeakMap.
 *
 * Structure mirrors TWeakListTest: each public method exercises one area of the
 * class, with separate _TEventHandler variants for TEventHandler-specific paths.
 *
 * The GC re-entrancy scenario covered by $_scrubbing is intentionally omitted —
 * reliably triggering PHP's cyclic GC in a test is not feasible.
 */
class TWeakMapTest extends TMapTest
{
	// ---- fixture ----------------------------------------------------------

	protected function newList(): string
	{
		return TWeakMapUnit::class;
	}

	protected function newListItem(): string
	{
		return WeakMapItem::class;
	}

	protected function setUp(): void
	{
		$this->_baseClass = $this->newList();
		$this->_baseItemClass = $this->newListItem();

		$this->map = new $this->_baseClass();
		$this->item1 = new $this->_baseItemClass(1);
		$this->item2 = new $this->_baseItemClass(2);
		$this->item3 = new $this->_baseItemClass(3);
		$this->map->add('key1', $this->item1);
		$this->map->add('key2', $this->item2);
	}

	// ---- override TMapTest cases that are incompatible with weak semantics ----

	/**
	 * TMap stores null values directly; TWeakMap does too (null is not an object
	 * so it is never wrapped).  This simply confirms the behaviour is unchanged.
	 */
	public function testArrayRead()
	{
		// Non-existent key → null (via dyNoItem)
		$this->assertNull($this->map['NoItemHere']);

		$this->assertSame($this->item1, $this->map['key1']);
		$this->assertSame($this->item2, $this->map['key2']);

		// Null value stored directly — contains() must still return true
		$this->map['key3'] = null;
		$this->assertNull($this->map['key3']);
		$this->assertNull($this->map->itemAt('key3'));
		$this->assertTrue($this->map->contains('key3'));
	}

	// ========================================================================
	// Construction
	// ========================================================================

	public function testConstructTWeakMap()
	{
		// Default (mutable): discardInvalid=true
		$m = new $this->_baseClass();
		$this->assertTrue($m->getDiscardInvalid());

		// readOnly=false → discardInvalid defaults to true
		$m = new $this->_baseClass(null, false);
		$this->assertTrue($m->getDiscardInvalid());

		// readOnly=true → discardInvalid defaults to false
		$m = new $this->_baseClass(null, true);
		$this->assertFalse($m->getDiscardInvalid());

		// Explicit discardInvalid overrides the readOnly-based default
		$m = new $this->_baseClass(null, true, true);
		$this->assertTrue($m->getDiscardInvalid());

		$m = new $this->_baseClass(null, false, false);
		$this->assertFalse($m->getDiscardInvalid());

		// Data loaded at construction: objects that survive are still accessible
		$obj1 = new $this->_baseItemClass(1);
		$obj2 = new $this->_baseItemClass(2);
		$m = new $this->_baseClass(['a' => $obj1, 'b' => $obj2]);
		$this->assertSame($obj1, $m['a']);
		$this->assertSame($obj2, $m['b']);
		$this->assertSame(2, $m->getCount());

		// Data with dead values in discardInvalid=true mode:
		// after construction the object is held by $obj3, so not yet GC'd.
		$obj3 = new $this->_baseItemClass(3);
		$m = new $this->_baseClass(['x' => $obj3], false, true);
		$this->assertSame($obj3, $m['x']);
		$obj3 = null;
		// Now GC'd — scrub on next access
		$this->assertSame(0, $m->getCount());

		// discardInvalid=false: GC'd value stays as null entry
		$obj4 = new $this->_baseItemClass(4);
		$m = new $this->_baseClass(['y' => $obj4], false, false);
		$obj4 = null;
		$this->assertSame(1, $m->getCount());
		$this->assertNull($m['y']);
	}

	// ========================================================================
	// getIterator
	// ========================================================================

	public function testGetIteratorTWeakMap()
	{
		unset($this->item2);

		$iter = $this->map->getIterator();
		$arr = iterator_to_array($iter);

		$this->assertCount(1, $arr);
		$this->assertSame($this->item1, $arr['key1']);
		$this->assertArrayNotHasKey('key2', $arr);
	}

	// ========================================================================
	// getCount / scrubbing
	// ========================================================================

	public function testGetCountTWeakMap()
	{
		// Both alive → 2
		$this->assertSame(2, $this->map->getCount());

		// Release item2 → scrub reduces to 1
		unset($this->item2);
		$this->assertSame(1, $this->map->getCount());
		$this->assertSame(1, $this->map->getWeakCount());

		// discardInvalid=false: GC'd entry stays, count unchanged
		$obj1 = new $this->_baseItemClass(10);
		$obj2 = new $this->_baseItemClass(20);
		$m = new $this->_baseClass(['a' => $obj1, 'b' => $obj2], false, false);
		unset($obj2);
		$this->assertSame(2, $m->getCount());
		$this->assertNull($m['b']);
	}

	// ========================================================================
	// itemAt
	// ========================================================================

	public function testItemAtTWeakMap()
	{
		// Live value → returned normally
		$this->assertSame($this->item1, $this->map->itemAt('key1'));
		$this->assertSame($this->item2, $this->map->itemAt('key2'));

		// After GC → key disappears (discardInvalid=true)
		unset($this->item2);
		$this->assertNull($this->map->itemAt('key2')); // scrubbed; calls dyNoItem

		// discardInvalid=false → null returned in-place
		$obj = new $this->_baseItemClass(99);
		$m = new $this->_baseClass(['k' => $obj], false, false);
		unset($obj);
		$this->assertNull($m->itemAt('k'));
		$this->assertTrue($m->contains('k')); // key still present
	}

	// ========================================================================
	// add — WeakMap accounting
	// ========================================================================

	public function testAddTWeakMap()
	{
		// After adding item3 we have 3 tracked objects
		$this->map->add('key3', $this->item3);
		$this->assertSame(3, $this->map->getWeakCount());

		// Release item2 → WeakMap shrinks, scrub clears key2
		unset($this->item2);
		$this->map->add('key4', $this->item3); // triggers scrub
		$this->assertSame(2, $this->map->getWeakCount()); // item1 + item3
		$this->assertSame(2, $this->map->getWeakObjectCount($this->item3)); // key3 + key4
		$this->assertSame(1, $this->map->getWeakObjectCount($this->item1));
	}

	public function testAddOverwriteTWeakMap()
	{
		// Overwriting an existing key must remove the old WeakMap entry first
		$old = $this->item1;
		$this->map->add('key1', $this->item3);

		$this->assertSame(2, $this->map->getWeakCount()); // old item1 out, item3 in
		$this->assertNull($this->map->getWeakObjectCount($old));
		$this->assertSame(1, $this->map->getWeakObjectCount($this->item3));
		$this->assertSame($this->item3, $this->map->itemAt('key1'));
	}

	public function testAddTWeakMap_TEventHandler()
	{
		$this->map->clear();

		$obj = new WeakMapItem('handler');
		$handler = [$obj, 'handle'];
		$eh = new TEventHandler($handler, 5);

		$this->map->add('eh', $eh);

		// TEventHandler itself is IWeakRetainable — inner obj is tracked
		$this->assertSame(1, $this->map->getWeakCount());
		$this->assertSame(1, $this->map->getWeakObjectCount($obj));
		$this->assertSame($eh, $this->map->itemAt('eh'));
	}

	// ========================================================================
	// remove
	// ========================================================================

	public function testRemoveTWeakMap()
	{
		$removed = $this->map->remove('key1');
		$this->assertSame($this->item1, $removed);
		$this->assertSame(1, $this->map->getCount());
		$this->assertFalse($this->map->contains('key1'));
		$this->assertNull($this->map->getWeakObjectCount($this->item1));

		// Removing non-existent key returns null
		$this->assertNull($this->map->remove('no-such-key'));

		// After GC in discardInvalid=true mode, key is scrubbed before remove attempt
		unset($this->item2);
		$this->assertNull($this->map->remove('key2')); // already gone after scrub
	}

	public function testRemoveTWeakMap_TEventHandler()
	{
		$obj = new WeakMapItem('h');
		$eh = new TEventHandler([$obj, 'handle'], 3);
		$this->map->add('eh', $eh);

		$this->assertSame(1, $this->map->getWeakObjectCount($obj));

		$removed = $this->map->remove('eh');
		$this->assertSame($eh, $removed);
		$this->assertSame(0, $this->map->getWeakCount() - 2); // only item1+item2 left
		$this->assertNull($this->map->getWeakObjectCount($obj));
	}

	// ========================================================================
	// removeItem
	// ========================================================================

	public function testRemoveItemTWeakMap()
	{
		// Add item1 under a second key
		$this->map->add('key1-also', $this->item1);

		$result = $this->map->removeItem($this->item1);
		$this->assertEquals(['key1' => $this->item1, 'key1-also' => $this->item1], $result);
		$this->assertSame(1, $this->map->getCount());
		$this->assertFalse($this->map->contains('key1'));
		$this->assertNull($this->map->getWeakObjectCount($this->item1));

		// Item not in map → empty array
		$this->assertEquals([], $this->map->removeItem($this->item3));
	}

	// ========================================================================
	// clear
	// ========================================================================

	public function testClearTWeakMap()
	{
		$this->map->add('key3', $this->item3);
		$this->assertSame(3, $this->map->getWeakCount());

		$this->map->clear();

		$this->assertSame(0, $this->map->getCount());
		$this->assertSame(0, $this->map->getWeakCount());
		$this->assertNull($this->map->getWeakObjectCount($this->item1));
		$this->assertNull($this->map->getWeakObjectCount($this->item2));
		$this->assertNull($this->map->getWeakObjectCount($this->item3));
	}

	// ========================================================================
	// contains
	// ========================================================================

	public function testContainsTWeakMap()
	{
		$this->assertTrue($this->map->contains('key1'));
		$this->assertTrue($this->map->contains('key2'));
		$this->assertFalse($this->map->contains('key3'));

		// GC'd value: key removed by scrub
		unset($this->item2);
		$this->assertFalse($this->map->contains('key2'));
	}

	// ========================================================================
	// keyOf
	// ========================================================================

	public function testKeyOfTWeakMap()
	{
		$this->map->add('key1-also', $this->item1);

		// Multiple matches
		$this->assertEquals(['key1' => $this->item1, 'key1-also' => $this->item1], $this->map->keyOf($this->item1));

		// Single match
		$this->assertSame('key2', $this->map->keyOf($this->item2, false));

		// Not found
		$this->assertFalse($this->map->keyOf($this->item3, false));
		$this->assertEquals([], $this->map->keyOf($this->item3));

		// GC'd value: key2 is scrubbed; key1 and key1-also survive (both hold item1)
		unset($this->item2);
		$this->assertSame(2, $this->map->getCount());
		$this->assertArrayNotHasKey('key2', $this->map->keyOf($this->item1));
	}

	// ========================================================================
	// toArray
	// ========================================================================

	public function testToArrayTWeakMap()
	{
		// Both alive
		$this->assertEquals(['key1' => $this->item1, 'key2' => $this->item2], $this->map->toArray());

		// After GC: dead entry removed (discardInvalid=true)
		unset($this->item2);
		$this->assertEquals(['key1' => $this->item1], $this->map->toArray());

		// discardInvalid=false: null in place of GC'd value
		$obj1 = new $this->_baseItemClass(10);
		$obj2 = new $this->_baseItemClass(20);
		$m = new $this->_baseClass(['a' => $obj1, 'b' => $obj2], false, false);
		unset($obj2);
		$this->assertEquals(['a' => $obj1, 'b' => null], $m->toArray());
	}

	// ========================================================================
	// copyFrom / mergeWith
	// ========================================================================

	public function testCopyFromTWeakMap()
	{
		$this->map->copyFrom(['x' => $this->item3]);

		$this->assertSame(1, $this->map->getCount());
		$this->assertSame($this->item3, $this->map['x']);
		$this->assertNull($this->map->getWeakObjectCount($this->item1));
		$this->assertSame(1, $this->map->getWeakObjectCount($this->item3));
	}

	public function testMergeWithTWeakMap()
	{
		$this->map->mergeWith(['key2' => $this->item3, 'key3' => $this->item3]);

		$this->assertSame(3, $this->map->getCount());
		$this->assertSame($this->item1, $this->map['key1']);
		$this->assertSame($this->item3, $this->map['key2']); // overwritten
		$this->assertSame($this->item3, $this->map['key3']);
		$this->assertNull($this->map->getWeakObjectCount($this->item2)); // evicted from WeakMap
		$this->assertSame(2, $this->map->getWeakObjectCount($this->item3));
	}

	// ========================================================================
	// Closure values — not wrapped in WeakReference
	// ========================================================================

	public function testClosureValueNotWrapped()
	{
		$fn = static function () {
			return 42;
		};
		$this->map->add('fn', $fn);

		// Closure must survive without any other strong reference
		$retrieved = $this->map->itemAt('fn');
		$this->assertInstanceOf(\Closure::class, $retrieved);
		$this->assertSame(42, $retrieved());

		// Closure is not tracked in the WeakMap
		$this->assertSame(2, $this->map->getWeakCount()); // only item1 + item2
	}

	// ========================================================================
	// IWeakRetainable values — stored directly, tracked in WeakMap
	// ========================================================================

	public function testIWeakRetainableValueNotWrapped()
	{
		$ret = new WeakMapRetainableItem('retained');
		$this->map->add('r', $ret);

		// Returned as-is, not dereferenced from WeakReference
		$this->assertSame($ret, $this->map->itemAt('r'));

		// IS tracked in the WeakMap (it's an object, just not wrapped)
		$this->assertSame(3, $this->map->getWeakCount());
		$this->assertSame(1, $this->map->getWeakObjectCount($ret));
	}

	// ========================================================================
	// Non-object values — stored directly, not tracked
	// ========================================================================

	public function testNonObjectValues()
	{
		$this->map->add('str', 'hello');
		$this->map->add('int', 42);
		$this->map->add('null', null);
		$this->map->add('arr', [1, 2, 3]);

		$this->assertSame('hello', $this->map->itemAt('str'));
		$this->assertSame(42, $this->map->itemAt('int'));
		$this->assertNull($this->map->itemAt('null'));
		$this->assertSame([1, 2, 3], $this->map->itemAt('arr'));

		// Non-objects are not tracked in the WeakMap
		$this->assertSame(2, $this->map->getWeakCount());
	}

	// ========================================================================
	// discardInvalid mode transitions
	// ========================================================================

	public function testDiscardInvalidTWeakMap()
	{
		// Confirm default
		$this->assertTrue($this->map->getDiscardInvalid());

		// External caller may not change it once set
		$this->expectException(TInvalidOperationException::class);
		$this->map->setDiscardInvalid(false);
	}

	public function testDiscardInvalidTransitionTrueToFalse()
	{
		// Transition from true → false stops the WeakMap
		$this->assertSame(2, $this->map->getWeakCount());
		$this->map->resetDiscardInvalid(false);
		$this->assertNull($this->map->getWeakCount()); // WeakMap stopped
		$this->assertFalse($this->map->getDiscardInvalid());

		// Values still accessible (strong array storage)
		$this->assertSame($this->item1, $this->map['key1']);
	}

	public function testDiscardInvalidTransitionFalseToTrue()
	{
		// Start with discardInvalid=false
		$obj1 = new $this->_baseItemClass(10);
		$obj2 = new $this->_baseItemClass(20);
		$obj3 = new $this->_baseItemClass(30);
		$m = new $this->_baseClass(['a' => $obj1, 'b' => $obj2, 'c' => $obj3], false, false);

		// Kill obj2 before transitioning
		unset($obj2);

		// Transition to true: dead entry is removed, live objects tracked
		$m->resetDiscardInvalid(true);
		$this->assertTrue($m->getDiscardInvalid());
		$this->assertSame(2, $m->getCount()); // obj2 removed
		$this->assertSame(2, $m->getWeakCount());
		$this->assertSame(1, $m->getWeakObjectCount($obj1));
		$this->assertSame(1, $m->getWeakObjectCount($obj3));
	}

	public function testDiscardInvalidTransitionWithTEventHandler()
	{
		$m = new $this->_baseClass(null, false, false);
		$obj = new WeakMapItem('inner');
		$eh = new TEventHandler([$obj, 'handle'], 1);
		$m->add('eh', $eh);

		// Kill the inner handler object
		unset($obj);

		// Transition to discardInvalid=true should remove the dead TEventHandler entry
		$m->resetDiscardInvalid(true);
		$this->assertSame(0, $m->getCount());
	}

	// ========================================================================
	// Serialisation — data must not be persisted (values are weak)
	// ========================================================================

	public function testSleepDoesNotPersistData()
	{
		// Serialize a populated map and unserialize it.
		// Weak values are not persisted, so the restored map must be empty.
		$m = new $this->_baseClass(['a' => $this->item1, 'b' => $this->item2]);
		$this->assertSame(2, $m->getCount());

		$restored = unserialize(serialize($m));

		$this->assertSame(0, $restored->getCount());
		$this->assertFalse($restored->contains('a'));
		$this->assertFalse($restored->contains('b'));
	}

	// ========================================================================
	// ArrayAccess via TMap (offsetGet/offsetSet/offsetUnset/offsetExists)
	// ========================================================================

	public function testOffsetSetTWeakMap()
	{
		unset($this->item2);

		// Replace key2 with item3
		$this->map['key2'] = $this->item3;

		// Old item2 should be gone from WeakMap, item3 registered
		$this->assertSame(2, $this->map->getWeakCount());
		$this->assertSame(1, $this->map->getWeakObjectCount($this->item1));
		$this->assertSame(1, $this->map->getWeakObjectCount($this->item3));
		$this->assertSame($this->item3, $this->map['key2']);
	}

	public function testOffsetUnsetTWeakMap()
	{
		unset($this->map['key1']);

		$this->assertSame(1, $this->map->getCount());
		$this->assertFalse($this->map->contains('key1'));
		$this->assertNull($this->map->getWeakObjectCount($this->item1));
		$this->assertSame(1, $this->map->getWeakCount());
	}

	// ========================================================================
	// TEventHandler as value — inner handler tracked
	// ========================================================================

	public function testTEventHandlerValueTracking()
	{
		$this->map->clear();

		$obj1 = new WeakMapItem('h1');
		$obj2 = new WeakMapItem('h2');
		$eh1 = new TEventHandler([$obj1, 'handle'], 1);
		$eh2 = new TEventHandler([$obj2, 'handle'], 2);

		$this->map->add('a', $eh1);
		$this->map->add('b', $eh2);

		$this->assertSame(2, $this->map->getWeakCount()); // obj1 + obj2 tracked
		$this->assertSame(1, $this->map->getWeakObjectCount($obj1));
		$this->assertSame(1, $this->map->getWeakObjectCount($obj2));

		// Kill obj2 — its TEventHandler entry should be scrubbed
		unset($obj2);
		$this->assertSame(1, $this->map->getCount());
		$this->assertSame(1, $this->map->getWeakCount());
		$this->assertSame($eh1, $this->map->itemAt('a'));
	}

	public function testTEventHandlerValueRemovedWithKey()
	{
		$this->map->clear();

		$obj = new WeakMapItem('h');
		$eh = new TEventHandler([$obj, 'handle'], 1);
		$this->map->add('k', $eh);

		$this->assertSame(1, $this->map->getWeakObjectCount($obj));

		$this->map->remove('k');
		$this->assertNull($this->map->getWeakObjectCount($obj));
	}

	// ========================================================================
	// Nested TEventHandler
	// ========================================================================

	public function testNestedTEventHandler()
	{
		$this->map->clear();

		$obj = new WeakMapItem('deep');
		$inner = new TEventHandler([$obj, 'handle'], 1);
		$outer = new TEventHandler($inner, 2);

		$this->map->add('k', $outer);

		// The innermost handler object is tracked
		$this->assertSame(1, $this->map->getWeakCount());
		$this->assertSame(1, $this->map->getWeakObjectCount($obj));

		unset($obj);
		$this->assertSame(0, $this->map->getCount());
	}

	// ========================================================================
	// Constructor DiscardInvalid resolution
	// ========================================================================

	public function testConstructorResolvesDiscardInvalid()
	{
		$obj = new WeakMapItem();
		$data = ['a' => $obj];

		$this->assertTrue((new TWeakMap($data))->getDiscardInvalid());
		$this->assertTrue((new TWeakMap($data, false))->getDiscardInvalid());
		$this->assertFalse((new TWeakMap($data, true))->getDiscardInvalid());
		$this->assertFalse((new TWeakMap($data, null, false))->getDiscardInvalid());
		$this->assertTrue((new TWeakMap($data, true, true))->getDiscardInvalid());
		$this->assertTrue((new TWeakMap([], null))->getDiscardInvalid());
		$this->assertTrue((new TWeakMap())->getDiscardInvalid());
		$this->assertFalse((new TWeakMap(null, true))->getDiscardInvalid());
	}

	public function testConstructorWithDataDiscardsCollectedValues()
	{
		$map = new TWeakMap(['a' => new WeakMapItem(), 'b' => 1]);
		gc_collect_cycles();

		$this->assertSame(1, $map->getCount());
		$this->assertSame(['b' => 1], $map->toArray());

		$this->expectException(TInvalidOperationException::class);
		$map->setDiscardInvalid(false);
	}

	// ========================================================================
	// TEventHandler without a live handler object
	// ========================================================================

	public function testAddEventHandlerWithCollectedObject()
	{
		$obj = new WeakMapItem();
		$eh = new TEventHandler([$obj, 'handle']);
		$obj = null;

		$map = new TWeakMap();
		$map->add('k', $eh);
		$this->assertSame(0, $map->getCount(), 'A TEventHandler collected before it is added is scrubbed');
		$this->assertSame([], $map->toArray());
	}

	public function testDiscardInvalidTransitionKeepsEventHandlerWithoutObject()
	{
		$map = new TWeakMapUnit(null, null, false);
		$eh = new TEventHandler('strlen');
		$map->add('k', $eh);

		$map->resetDiscardInvalid(true);

		$this->assertSame(1, $map->getCount());
		$this->assertSame($eh, $map->itemAt('k'));
	}

	public function testClearReadOnly_TWeakMap()
	{
		$value = new WeakMapItem();
		$map = new TWeakMap(['a' => $value], true, true);
		$value = null;
		$map->clear();
		$this->assertSame(0, $map->getCount(), 'A read-only map holding only collected values clears without error');

		$map = new TWeakMap(['a' => 1], true);
		try {
			$map->clear();
			$this->fail('A read-only map with entries must not clear');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('map_readonly', $e->getErrorCode());
		}
		$this->assertSame(['a' => 1], $map->toArray());
	}

	public function testReadOnlyMatchesTMap_TWeakMap()
	{
		$operations = [
			'clear' => [['a' => 1], fn ($map) => $map->clear(), 'map_readonly'],
			'copyFrom empty' => [['a' => 1], fn ($map) => $map->copyFrom([]), 'map_readonly'],
			'copyFrom data' => [['a' => 1], fn ($map) => $map->copyFrom(['b' => 2]), 'map_readonly'],
			'copyFrom null' => [['a' => 1], fn ($map) => $map->copyFrom(null), null],
			'mergeWith data' => [['a' => 1], fn ($map) => $map->mergeWith(['b' => 2]), 'map_readonly'],
			'mergeWith empty' => [['a' => 1], fn ($map) => $map->mergeWith([]), null],
			'empty clear' => [[], fn ($map) => $map->clear(), null],
			'empty copyFrom empty' => [[], fn ($map) => $map->copyFrom([]), null],
			'empty copyFrom data' => [[], fn ($map) => $map->copyFrom(['b' => 2]), 'map_readonly'],
			'empty mergeWith data' => [[], fn ($map) => $map->mergeWith(['b' => 2]), 'map_readonly'],
		];
		foreach ($operations as $name => [$data, $operation, $errorCode]) {
			foreach ([TMap::class, TWeakMap::class] as $class) {
				$map = new $class($data, true);
				$code = null;
				try {
					$operation($map);
				} catch (TInvalidOperationException $e) {
					$code = $e->getErrorCode();
				}
				$this->assertSame($errorCode, $code, "$class $name error code");
				$this->assertSame($data, $map->toArray(), "$class $name leaves the map unchanged");
			}
		}
	}

	public function testClearResetsBookkeeping_TWeakMap()
	{
		$map = new TWeakMapUnit();
		$item = new WeakMapItem();
		$map->add('h', new TEventHandler('strlen'));
		$map->add('i', $item);
		$map->clear();

		$this->assertSame(0, PradoUnit::getProp($map, '_eventHandlerCount'));
		$this->assertSame(0, PradoUnit::getProp($map, '_weakCount'));
		$this->assertNull($map->getWeakObjectCount($item));
	}

	public function testExplicitWeakReferenceTracksReferent_TWeakMap()
	{
		$map = new TWeakMapUnit();
		$object = new WeakMapItem();
		$map->add('k', \WeakReference::create($object));
		$this->assertSame(1, $map->getWeakObjectCount($object));
		$this->assertSame($object, $map->itemAt('k'));

		$map->remove('k');
		$this->assertNull($map->getWeakObjectCount($object));

		$map->add('k', \WeakReference::create($object));
		$object = null;
		$this->assertSame(0, $map->getCount(), 'A collected referent is scrubbed');
		$this->assertSame([], $map->toArray());
	}

	public function testExplicitWeakReferenceSharesTrackingWithItsObject_TWeakMap()
	{
		$map = new TWeakMapUnit();
		$object = new WeakMapItem();
		$map->add('a', $object);
		$map->add('b', \WeakReference::create($object));
		$this->assertSame(2, $map->getWeakObjectCount($object));

		$map->remove('a');
		$this->assertSame(1, $map->getWeakObjectCount($object));

		$object = null;
		$this->assertSame(0, $map->getCount());
	}

	public function testExplicitWeakReferenceCollectedBeforeAdd_TWeakMap()
	{
		$object = new WeakMapItem();
		$weak = \WeakReference::create($object);
		$object = null;

		$map = new TWeakMap();
		$map->add('k', $weak);
		$this->assertSame(0, $map->getCount(), 'A referent collected before it is added is scrubbed');
		$this->assertSame([], $map->toArray());

		$map = new TWeakMap(null, null, false);
		$map->add('k', $weak);
		$this->assertSame(['k' => null], $map->toArray(), 'DiscardInvalid false keeps the entry');
	}

	public function testExplicitWeakReferenceToEventHandler_TWeakMap()
	{
		$map = new TWeakMapUnit();
		$target = new WeakMapItem();
		$wrapped = new TEventHandler([$target, 'handle']);
		$map->add('k', \WeakReference::create($wrapped));
		$this->assertSame(1, $map->getWeakObjectCount($wrapped), 'The referent is tracked, not its handler object');
		$this->assertNull($map->getWeakObjectCount($target));
		$this->assertSame(0, PradoUnit::getProp($map, '_eventHandlerCount'));

		$wrapped = null;
		$this->assertSame(0, $map->getCount(), 'The entry ends with the referenced handler while its target lives');
	}

	public function testCloneTWeakMapKeepsIndependentWeakMap()
	{
		$clone = clone $this->map;
		$clone->add('key3', $this->item3);

		$this->assertSame(2, $this->map->getWeakCount());
		$this->assertSame(3, $clone->getWeakCount());
		$this->assertNull($this->map->getWeakObjectCount($this->item3));

		unset($this->item2);

		$this->assertSame(['key1' => $this->item1], $this->map->toArray());
		$this->assertSame(['key1' => $this->item1, 'key3' => $this->item3], $clone->toArray());
		$this->assertSame(1, $this->map->getWeakCount());
		$this->assertSame(2, $clone->getWeakCount());
	}

	public function testSerializeTWeakMapRestartsTrackingWhenDiscardInvalid()
	{
		$this->assertTrue($this->map->getDiscardInvalid());

		$serialized = serialize($this->map);

		$this->assertStringContainsString('_discardInvalid', $serialized);
		$this->assertStringNotContainsString('_weakMap', $serialized);
		$this->assertSame(['key1' => $this->item1, 'key2' => $this->item2], $this->map->toArray(), 'Sleeping must not change the source map.');

		$restored = unserialize($serialized);

		$this->assertTrue($restored->getDiscardInvalid());
		$this->assertSame(0, $restored->getWeakCount());
		$restored->add('k', $this->item3);
		$this->assertSame(1, $restored->getWeakCount());
		unset($this->item3);
		$this->assertSame(0, $restored->getCount());
	}

	public function testSerializeTWeakMapKeepsDiscardInvalidFalse()
	{
		$m = new $this->_baseClass(null, false, false);
		$m->add('a', $this->item1);

		$restored = unserialize(serialize($m));

		$this->assertFalse($restored->getDiscardInvalid());
		$this->assertNull($restored->getWeakCount());
		$restored->add('k', $this->item3);
		unset($this->item3);
		$this->assertSame(1, $restored->getCount());
		$this->assertNull($restored['k']);
	}

	public function testSerializeTWeakMapLeavesUnresolvedDiscardInvalidLazy()
	{
		$m = new $this->_baseClass(null, true);

		$serialized = serialize($m);

		$this->assertStringNotContainsString('_discardInvalid', $serialized);
		$restored = unserialize($serialized);
		$this->assertTrue($restored->getReadOnly());
		$this->assertFalse($restored->getDiscardInvalid());
	}

	public function testTEventHandlerWithoutHandlerObjectTWeakMap()
	{
		$eh = new TEventHandler('strlen', 3);

		$this->map->add('fn', $eh);

		$this->assertSame(1, PradoUnit::getProp($this->map, '_eventHandlerCount'));
		$this->assertSame(2, $this->map->getWeakCount());
		$this->assertSame($eh, $this->map['fn']);
		$this->assertSame(3, $this->map->getCount());

		$this->assertSame($eh, $this->map->remove('fn'));

		$this->assertSame(0, PradoUnit::getProp($this->map, '_eventHandlerCount'));
		$this->assertSame(2, $this->map->getWeakCount());
		$this->assertSame(2, $this->map->getCount());
	}

	public function testRemoveClosureValueTWeakMap()
	{
		$fn = static fn () => 1;
		$this->map->add('fn', $fn);

		$this->assertSame($fn, $this->map->remove('fn'));

		$this->assertFalse($this->map->contains('fn'));
		$this->assertSame(2, $this->map->getWeakCount());
	}

	public function testRemoveIWeakRetainableValueTWeakMap()
	{
		$item = new WeakMapRetainableItem('kept');
		$this->map->add('r', $item);
		$this->assertSame(1, $this->map->getWeakObjectCount($item));

		$this->assertSame($item, $this->map->remove('r'));

		$this->assertNull($this->map->getWeakObjectCount($item));
		$this->assertSame(2, $this->map->getWeakCount());
	}

	public function testRemoveCollectedAndNonObjectValuesWithoutDiscardInvalid()
	{
		$m = new $this->_baseClass(null, false, false);
		$obj = new WeakMapItem('gone');
		$m->add('k', $obj);
		$m->add('n', null);
		$m->add('s', 'text');
		$obj = null;

		$this->assertTrue($m->contains('k'));
		$this->assertNull($m->remove('k'));
		$this->assertFalse($m->contains('k'));

		$this->assertNull($m->remove('n'));
		$this->assertFalse($m->contains('n'));

		$this->assertSame('text', $m->remove('s'));
		$this->assertSame(0, $m->getCount());
	}

	public function testCollectedTEventHandlerOutputsNullWithoutDiscardInvalid()
	{
		$m = new $this->_baseClass(null, false, false);
		$obj = new WeakMapItem('h');
		$m->add('eh', new TEventHandler([$obj, 'handle']));
		$obj = null;

		$this->assertNull($m['eh']);
		$this->assertSame(['eh' => null], $m->toArray());
		$this->assertSame(1, $m->getCount());
	}

	public function testScrubKeepsNonObjectValuesTWeakMap()
	{
		$this->map->add('num', 42);
		$this->map->add('arr', [1, 2]);

		unset($this->item2);

		$this->assertSame(['key1' => $this->item1, 'num' => 42, 'arr' => [1, 2]], $this->map->toArray());
		$this->assertSame(1, $this->map->getWeakCount());
	}

	public function testDiscardInvalidTransitionKeepsNonObjectAndClosureValues()
	{
		$obj = new WeakMapItem('o');
		$fn = static fn () => 1;
		$m = new $this->_baseClass(null, false, false);
		$m->add('s', 'text');
		$m->add('o', $obj);
		$m->add('fn', $fn);

		$m->resetDiscardInvalid(true);

		$this->assertSame(['s' => 'text', 'o' => $obj, 'fn' => $fn], $m->toArray());
		$this->assertSame(1, $m->getWeakCount());
		$this->assertSame(1, $m->getWeakObjectCount($obj));
	}

	public function testEmptyTWeakMapOperations()
	{
		$m = new $this->_baseClass();

		$this->assertSame([], $m->toArray());
		$this->assertSame([], $m->keyOf($this->item1));
		$this->assertFalse($m->keyOf($this->item1, false));
		$this->assertSame([], $m->removeItem($this->item1));
		$m->clear();
		$this->assertSame(0, $m->getCount());
	}

	public function testRemoveItemSingleMatchTWeakMap()
	{
		$m = new $this->_baseClass();
		$m->add('only', $this->item3);

		$this->assertSame(['only' => $this->item3], $m->removeItem($this->item3));

		$this->assertSame(0, $m->getCount());
		$this->assertNull($m->getWeakObjectCount($this->item3));
	}

	public function testCopyFromTraversableAndEmptySourcesTWeakMap()
	{
		$m = new $this->_baseClass();

		$m->copyFrom(new ArrayIterator([]));
		$m->copyFrom([]);
		$this->assertSame([], $m->toArray());

		$m->copyFrom(new ArrayIterator(['a' => $this->item3]));
		$this->assertSame(['a' => $this->item3], $m->toArray());

		$m->copyFrom(new ArrayIterator(['b' => 'text']));
		$this->assertSame(['b' => 'text'], $m->toArray());
		$this->assertSame(0, $m->getWeakCount());

		$m->copyFrom(['c' => $this->item1]);
		$m->copyFrom(null);
		$this->assertSame(['c' => $this->item1], $m->toArray());

		$m->copyFrom([]);
		$this->assertSame([], $m->toArray());

		$m->copyFrom(['d' => 'text']);
		$m->copyFrom(new ArrayIterator([]));
		$this->assertSame([], $m->toArray());

		try {
			$m->copyFrom('not iterable');
			$this->fail('copyFrom did not throw TInvalidDataTypeException for a non-iterable.');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('map_data_not_iterable', $e->getErrorCode());
		}
	}

	public function testMergeWithTraversableAndEmptySourcesTWeakMap()
	{
		$m = new $this->_baseClass();

		$m->mergeWith(new ArrayIterator([]));
		$m->mergeWith([]);
		$m->mergeWith(null);
		$this->assertSame([], $m->toArray());

		$m->mergeWith(new ArrayIterator(['a' => $this->item3]));
		$m->mergeWith(['b' => 'text']);
		$this->assertSame(['a' => $this->item3, 'b' => 'text'], $m->toArray());
		$this->assertSame(1, $m->getWeakCount());

		try {
			$m->mergeWith('not iterable');
			$this->fail('mergeWith did not throw TInvalidDataTypeException for a non-iterable.');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('map_data_not_iterable', $e->getErrorCode());
		}
	}

	public function testFilterItemForInputLeavesWeakReferenceUnchanged()
	{
		$ref = WeakReference::create($this->item1);
		$item = $ref;

		TWeakMap::filterItemForInput($item);

		$this->assertSame($ref, $item);
	}

	/**
	 * Without DiscardInvalid there is no WeakMap; clear() empties the map, zeroes
	 * the event handler count, and creates no WeakMap.
	 */
	public function testClearWithoutDiscardInvalidCreatesNoWeakMap()
	{
		$m = new $this->_baseClass(['a' => $this->item1, 'b' => $this->item2], false, false);
		$this->assertFalse($m->getDiscardInvalid());
		$this->assertNull($m->getWeakCount());

		$m->clear();

		$this->assertSame(0, $m->getCount());
		$this->assertSame(0, PradoUnit::getProp($m, '_eventHandlerCount'));
		$this->assertNull($m->getWeakCount());
	}
}
