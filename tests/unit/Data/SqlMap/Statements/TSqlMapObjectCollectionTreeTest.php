<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Collections\TList;
use Prado\Data\SqlMap\Statements\TSqlMapObjectCollectionTree;

class TSqlMapObjectCollectionTreeTest extends PHPUnit\Framework\TestCase
{
	private TSqlMapObjectCollectionTree $tree;

	protected function setUp(): void
	{
		$this->tree = new TSqlMapObjectCollectionTree();
	}

	public function test_is_empty_initially()
	{
		$this->assertTrue($this->tree->isEmpty());
	}

	public function test_add_single_root_node_not_empty()
	{
		// add(null, node, obj) populates _entries; isEmpty checks _entries count
		$this->tree->add(null, 'node1', ['object' => new stdClass(), 'property' => null]);
		$this->assertFalse($this->tree->isEmpty());
	}

	public function test_add_root_with_empty_string_parent_not_empty()
	{
		$this->tree->add('', 'root', 'rootObj');
		$this->assertFalse($this->tree->isEmpty());
	}

	public function test_zappable_empty_not_in_sleep()
	{
		// Empty tree/entries/list → all three excluded from __sleep()
		$sleepKeys = $this->tree->__sleep();
		$cn = TSqlMapObjectCollectionTree::class;
		$this->assertNotContains("\0$cn\0_tree", $sleepKeys);
		$this->assertNotContains("\0$cn\0_entries", $sleepKeys);
		$this->assertNotContains("\0$cn\0_list", $sleepKeys);
	}

	public function test_zappable_entries_populated_in_sleep()
	{
		// A root-only add populates _entries but NOT _tree
		$this->tree->add(null, 'root', ['object' => new stdClass(), 'property' => null]);

		$sleepKeys = $this->tree->__sleep();
		$cn = TSqlMapObjectCollectionTree::class;

		// _entries is non-empty — should appear in __sleep()
		$this->assertContains("\0$cn\0_entries", $sleepKeys);
		// _tree is still empty after root-only add — should be absent
		$this->assertNotContains("\0$cn\0_tree", $sleepKeys);
	}

	public function test_zappable_tree_populated_in_sleep()
	{
		// Add root then child placeholder — this populates _tree
		$parentObj = new stdClass();
		$this->tree->add(null, 'root', ['object' => $parentObj, 'property' => null]);
		$this->tree->add('root', 'child', '');  // placeholder creates _tree['root']['child']

		$sleepKeys = $this->tree->__sleep();
		$cn = TSqlMapObjectCollectionTree::class;
		$this->assertContains("\0$cn\0_tree", $sleepKeys);
		$this->assertContains("\0$cn\0_entries", $sleepKeys);
	}

	public function test_is_tcomponent()
	{
		$this->assertInstanceOf(\Prado\TComponent::class, $this->tree);
	}

	public function test_collect_empty_tree_returns_empty_array()
	{
		$result = $this->tree->collect();
		$this->assertSame([], $result);
	}

	public function test_add_placeholder_then_update_entry()
	{
		$parentObj = new stdClass();
		$childObj = new stdClass();

		// Step 1: add root
		$this->tree->add(null, 'p1', ['object' => $parentObj, 'property' => null]);
		// Step 2: add child placeholder — preserves parent entry
		$this->tree->add('p1', 'c1', '');
		// Step 3: update child entry
		$this->tree->add('p1', 'c1', ['object' => $childObj, 'property' => 'items']);

		$this->assertFalse($this->tree->isEmpty());
	}

	/**
	 * Verify the real 3-step pattern: root + placeholder + update, then collect.
	 */
	public function test_collect_with_parent_child_using_tlist()
	{
		$parentObj = new stdClass();
		$parentObj->items = new TList();

		$childObj1 = new stdClass();
		$childObj1->val = 'c1';
		$childObj2 = new stdClass();
		$childObj2->val = 'c2';

		// Root node
		$this->tree->add(null, 'p1', ['object' => $parentObj, 'property' => null]);

		// Child 1: placeholder then update
		$this->tree->add('p1', 'c1', '');
		$this->tree->add('p1', 'c1', ['object' => $childObj1, 'property' => 'items']);

		// Child 2: placeholder then update
		$this->tree->add('p1', 'c2', '');
		$this->tree->add('p1', 'c2', ['object' => $childObj2, 'property' => 'items']);

		$result = $this->tree->collect();
		$this->assertCount(1, $result);
		$this->assertSame($parentObj, $result[0]);
		$this->assertSame(2, $parentObj->items->getCount());
	}

	public function test_collect_root_only_no_children_empty_result()
	{
		// A root node with no children: after collect() the root-only collect
		// goes through collectChildren, processes it, calls onChildNodesVisited(null, [root])
		// which returns early due to empty parent.
		// So result is [].
		$parentObj = new stdClass();
		$this->tree->add(null, 'p1', ['object' => $parentObj, 'property' => null]);

		// No children added — _tree is empty, so collect() loop exits immediately
		$result = $this->tree->collect();
		$this->assertSame([], $result);
	}

	public function test_serialization_round_trip()
	{
		$parentObj = new stdClass();
		$this->tree->add(null, 'n1', ['object' => $parentObj, 'property' => null]);
		$this->tree->add('n1', 'c1', '');

		$serialized = serialize($this->tree);
		/** @var TSqlMapObjectCollectionTree $restored */
		$restored = unserialize($serialized);
		$this->assertFalse($restored->isEmpty());
	}

	public function test_serialization_empty_tree_round_trip()
	{
		$serialized = serialize($this->tree);
		$restored = unserialize($serialized);
		$this->assertTrue($restored->isEmpty());
	}

	public function test_add_existing_node_both_entries_set_updates_node()
	{
		$parentObj = new stdClass();
		$childObj = new stdClass();
		$childObjUpdated = new stdClass();

		$this->tree->add(null, 'p1', ['object' => $parentObj, 'property' => null]);
		$this->tree->add('p1', 'c1', '');
		$this->tree->add('p1', 'c1', ['object' => $childObj, 'property' => 'items']);

		// Both parent and child are now in _entries with non-null values:
		// Re-add with updated object triggers the early-update path
		$this->tree->add('p1', 'c1', ['object' => $childObjUpdated, 'property' => 'items']);

		// Tree is still non-empty
		$this->assertFalse($this->tree->isEmpty());
	}
}
