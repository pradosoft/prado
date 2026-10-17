<?php

namespace Prado\Test\Unit\Data\SqlMap\Statements;

use Prado\Data\SqlMap\Statements\TSqlMapObjectCollectionTree;

class TSqlMapObjectCollectionTreeTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Adds a child node the way TMappedStatement does: the reference first, then the object.
	 */
	private function addChild(TSqlMapObjectCollectionTree $tree, string $parent, string $node, mixed $value): void
	{
		$tree->add($parent, $node, '');
		$tree->add($parent, $node, ['object' => $value, 'property' => 'items']);
	}

	public function testCollectGroupsChildren(): void
	{
		$tree = new TSqlMapObjectCollectionTree();
		$root = new \stdClass();
		$root->items = [];
		$tree->add(null, 'p', ['object' => $root, 'property' => null]);
		$this->addChild($tree, 'p', 'a', 'one');
		$this->addChild($tree, 'p', 'b', 'two');

		$this->assertSame([$root], $tree->collect());
		$this->assertSame(['one', 'two'], $root->items);
	}

	public function testCollectVisitsNodeKeyZero(): void
	{
		$tree = new TSqlMapObjectCollectionTree();
		$root = new \stdClass();
		$root->items = [];
		$tree->add(null, 'p', ['object' => $root, 'property' => null]);
		$this->addChild($tree, 'p', '0', 'zero');
		$this->addChild($tree, 'p', '1', 'one');

		$this->assertSame([$root], $tree->collect());
		$this->assertSame(['zero', 'one'], $root->items);
	}

	public function testAddMatchesParentKeyStrictly(): void
	{
		$tree = new TSqlMapObjectCollectionTree();
		$first = new \stdClass();
		$first->items = [];
		$second = new \stdClass();
		$second->items = [];
		$tree->add(null, '1e3', ['object' => $first, 'property' => null]);
		$tree->add(null, '1000', ['object' => $second, 'property' => null]);
		$this->addChild($tree, '1e3', 'a', 'first child');
		$this->addChild($tree, '1000', 'b', 'second child');

		$tree->collect();
		$this->assertSame(['first child'], $first->items);
		$this->assertSame(['second child'], $second->items);
	}
}
