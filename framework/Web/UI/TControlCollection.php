<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */


/**
 * TControlCollection class
 *
 * TControlCollection implements a collection that enables
 * controls to maintain a list of their child controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
class TControlCollection extends TList
{
	/**
	 * the control that owns this collection.
	 * @var TControl
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param TControl the control that owns this collection.
	 * @param boolean whether the list is read-only
	 */
	public function __construct(TControl $owner,$readOnly=false)
	{
		$this->_o=$owner;
		parent::__construct(null,$readOnly);
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
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is neither a string nor a TControl.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TControl)
		{
			parent::insertAt($index,$item);
			$this->_o->addedControl($item);
		}
		else if(is_string($item) || ($item instanceof IRenderable))
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('controlcollection_control_required');
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a child control.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$item=parent::removeAt($index);
		if($item instanceof TControl)
			$this->_o->removedControl($item);
		return $item;
	}

	/**
	 * Overrides the parent implementation by invoking {@link TControl::clearNamingContainer}
	 */
	public function clear()
	{
		parent::clear();
		if($this->_o instanceof INamingContainer)
			$this->_o->clearNamingContainer();
	}
}