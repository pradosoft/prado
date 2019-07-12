<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TControlCollection class
 *
 * TControlCollection implements a collection that enables
 * controls to maintain a list of their child controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TControlCollection extends \Prado\Collections\TList
{
	/**
	 * the control that owns this collection.
	 * @var TControl
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param TControl $owner the control that owns this collection.
	 * @param bool $readOnly whether the list is read-only
	 */
	public function __construct(TControl $owner, $readOnly = false)
	{
		$this->_o = $owner;
		parent::__construct(null, $readOnly);
	}

	/**
	 * @return TControl the control that owns this collection.
	 */
	protected function getOwner()
	{
		return $this->_o;
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added child control.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is neither a string nor a TControl.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TControl) {
			parent::insertAt($index, $item);
			$this->_o->addedControl($item);
		} elseif (is_string($item) || ($item instanceof IRenderable)) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('controlcollection_control_required');
		}
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a child control.
	 * @param int $index the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$item = parent::removeAt($index);
		if ($item instanceof TControl) {
			$this->_o->removedControl($item);
		}
		return $item;
	}

	/**
	 * Overrides the parent implementation by invoking {@link TControl::clearNamingContainer}
	 */
	public function clear()
	{
		parent::clear();
		if ($this->_o instanceof INamingContainer) {
			$this->_o->clearNamingContainer();
		}
	}
}
