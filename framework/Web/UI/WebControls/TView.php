<?php
/**
 * TMultiView and TView class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TView class
 *
 * TView is a container for a group of controls. TView must be contained
 * within a {@link TMultiView} control in which only one view can be active
 * at one time.
 *
 * To activate a view, set {@link setActive Active} to true.
 * When a view is activated, it raises {@link onActivate OnActivate} event;
 * and when a view is deactivated, it raises {@link onDeactivate OnDeactivate}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TView extends TControl
{
	private $_active=false;

	/**
	 * Raises <b>OnActivate</b> event.
	 * @param TEventParameter event parameter
	 */
	public function onActivate($param)
	{
		$this->raiseEvent('OnActivate',$this,$param);
	}

	/**
	 * Raises <b>OnDeactivate</b> event.
	 * @param TEventParameter event parameter
	 */
	public function onDeactivate($param)
	{
		$this->raiseEvent('OnDeactivate',$this,$param);
	}

	/**
	 * @return boolean whether this view is active. Defaults to false.
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param boolean whether this view is active.
	 */
	public function setActive($value)
	{
		$value=TPropertyValue::ensureBoolean($value);
		$this->_active=$value;
		parent::setVisible($value);
	}

	/**
	 * @param boolean whether the parents should also be checked if visible
	 * @return boolean whether this view is visible.
	 * The view is visible if it is active and its parent is visible.
	 */
	public function getVisible($checkParents=true)
	{
		if(($parent=$this->getParent())===null)
			return $this->getActive();
		else if($this->getActive())
			return $parent->getVisible($checkParents);
		else
			return false;
	}

	/**
	 * @param boolean
	 * @throws TInvalidOperationException whenever this method is invoked.
	 */
	public function setVisible($value)
	{
		throw new TInvalidOperationException('view_visible_readonly');
	}
}