<?php
/**
 * TMappedStatement and related classes.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Statements
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Collections\TList;
use Prado\Data\SqlMap\DataMapper\TPropertyAccess;
use Prado\Data\SqlMap\DataMapper\TSqlMapExecutionException;

/**
 * TSQLMapObjectCollectionTree class.
 *
 * Maps object collection graphs as trees. Nodes in the collection can
 * be {@link add} using parent relationships. The object collections can be
 * build using the {@link collect} method.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.1
 */
class TSqlMapObjectCollectionTree extends \Prado\TComponent
{
	/**
	 * @var array object graph as tree
	 */
	private $_tree = [];
	/**
	 * @var array tree node values
	 */
	private $_entries = [];
	/**
	 * @var array resulting object collection
	 */
	private $_list = [];

	/**
	 * @return bool true if the graph is empty
	 */
	public function isEmpty()
	{
		return count($this->_entries) == 0;
	}

	/**
	 * Add a new node to the object tree graph.
	 * @param string $parent parent node id
	 * @param string $node new node id
	 * @param mixed $object node value
	 */
	public function add($parent, $node, $object = '')
	{
		if (isset($this->_entries[$parent]) && ($this->_entries[$parent] !== null)
			&& isset($this->_entries[$node]) && ($this->_entries[$node] !== null)) {
			$this->_entries[$node] = $object;
			return;
		}
		$this->_entries[$node] = $object;
		if (empty($parent)) {
			if (isset($this->_entries[$node])) {
				return;
			}
			$this->_tree[$node] = [];
		}
		$found = $this->addNode($this->_tree, $parent, $node);
		if (!$found && !empty($parent)) {
			$this->_tree[$parent] = [];
			if (!isset($this->_entries[$parent]) || $object !== '') {
				$this->_entries[$parent] = $object;
			}
			$this->addNode($this->_tree, $parent, $node);
		}
	}

	/**
	 * Find the parent node and add the new node as its child.
	 * @param array &$childs list of nodes to check
	 * @param string $parent parent node id
	 * @param string $node new node id
	 * @return bool true if parent node is found.
	 */
	protected function addNode(&$childs, $parent, $node)
	{
		$found = false;
		reset($childs);
		for ($i = 0, $k = count($childs); $i < $k; $i++) {
			$key = key($childs);
			next($childs);
			if ($key == $parent) {
				$found = true;
				$childs[$key][$node] = [];
			} else {
				$found = $found || $this->addNode($childs[$key], $parent, $node);
			}
		}
		return $found;
	}

	/**
	 * @return array object collection
	 */
	public function collect()
	{
		while (count($this->_tree) > 0) {
			$this->collectChildren(null, $this->_tree);
		}
		return $this->getCollection();
	}

	/**
	 * @param array &$nodes list of nodes to check
	 * @return bool true if all nodes are leaf nodes, false otherwise
	 */
	protected function hasChildren(&$nodes)
	{
		$hasChildren = false;
		foreach ($nodes as $node) {
			if (count($node) != 0) {
				return true;
			}
		}
		return $hasChildren;
	}

	/**
	 * Visit all the child nodes and collect them by removing.
	 * @param string $parent parent node id
	 * @param array &$nodes list of child nodes.
	 */
	protected function collectChildren($parent, &$nodes)
	{
		$noChildren = !$this->hasChildren($nodes);
		$childs = [];
		for (reset($nodes); $key = key($nodes);) {
			next($nodes);
			if ($noChildren) {
				$childs[] = $key;
				unset($nodes[$key]);
			} else {
				$this->collectChildren($key, $nodes[$key]);
			}
		}
		if (count($childs) > 0) {
			$this->onChildNodesVisited($parent, $childs);
		}
	}

	/**
	 * Set the object properties for all the child nodes visited.
	 * @param string $parent parent node id
	 * @param array $nodes list of child nodes visited.
	 */
	protected function onChildNodesVisited($parent, $nodes)
	{
		if (empty($parent) || empty($this->_entries[$parent])) {
			return;
		}

		$parentObject = $this->_entries[$parent]['object'];
		$property = $this->_entries[$nodes[0]]['property'];

		$list = TPropertyAccess::get($parentObject, $property);

		foreach ($nodes as $node) {
			if ($list instanceof TList) {
				$parentObject->{$property}[] = $this->_entries[$node]['object'];
			} elseif (is_array($list)) {
				$list[] = $this->_entries[$node]['object'];
			} else {
				throw new TSqlMapExecutionException(
					'sqlmap_property_must_be_list'
				);
			}
		}

		if (is_array($list)) {
			TPropertyAccess::set($parentObject, $property, $list);
		}

		if ($this->_entries[$parent]['property'] === null) {
			$this->_list[] = $parentObject;
		}
	}

	/**
	 * @return array object collection.
	 */
	protected function getCollection()
	{
		return $this->_list;
	}

	public function __sleep()
	{
		$exprops = [];
		$cn = __CLASS__;
		if (!count($this->_tree)) {
			$exprops[] = "\0$cn\0_tree";
		}
		if (!count($this->_entries)) {
			$exprops[] = "\0$cn\0_entries";
		}
		if (!count($this->_list)) {
			$exprops[] = "\0$cn\0_list";
		}
		return array_diff(parent::__sleep(), $exprops);
	}
}
