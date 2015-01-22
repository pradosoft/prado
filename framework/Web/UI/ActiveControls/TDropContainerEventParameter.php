<?php
/**
 * TDropContainer class file
 *
 * @author Christophe BOULAIN (Christophe.Boulain@gmail.com)
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TDropContainerEventParameter class
 *
 * TDropContainerEventParameter encapsulate the parameter
 * data for <b>OnDrop</b> event of TDropContainer components
 *
 * @author Christophe BOULAIN (Christophe.Boulain@ceram.fr)
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado\Web\UI\ActiveControls
 */
class TDropContainerEventParameter extends \Prado\TEventParameter
{
	private $_dragElementId;
	private $_screenX;
	private $_screenY;
	private $_offsetX;
	private $_offsetY;
	private $_clientX;
	private $_clientY;
	private $_shiftKey;
	private $_ctrlKey;
	private $_altKey;

	public function __construct($dropParams)
	{
		$this->_dragElementId = $dropParams->DragElementID;
		$this->_screenX = $dropParams->ScreenX;
		$this->_screenY = $dropParams->ScreenY;
		$this->_offsetX = isset($dropParams->OffsetX) ? $dropParams->OffsetX : false;
		$this->_offsetY = isset($dropParams->OffsetY) ? $dropParams->OffsetY : false;
		$this->_clientX = $dropParams->ClientX;
		$this->_clientY = $dropParams->ClientY;
		$this->_shiftKey = TPropertyValue::ensureBoolean($dropParams->ShiftKey);
		$this->_ctrlKey = TPropertyValue::ensureBoolean($dropParams->CtrlKey);
		$this->_altKey = TPropertyValue::ensureBoolean($dropParams->AltKey);
	}

	public function getDragElementId()			{ return $this->_dragElementId; }
	public function getScreenX()				{ return $this->_screenX; }
	public function getScreenY()				{ return $this->_screenY; }
	public function getOffsetX()				{ return $this->_offsetX; }
	public function geOffsetY()					{ return $this->_offsetY; }
	public function getClientX()				{ return $this->_clientX; }
	public function getClientY()				{ return $this->_clientY; }
	public function getShiftKey()				{ return $this->_shiftKey; }
	public function getCtrlKey()				{ return $this->_ctrlKey; }
	public function getAltKey()					{ return $this->_altKey; }

	/**
	 * GetDroppedControl
	 *
	 * Compatibility method to get the dropped control
	 * @return TControl dropped control, or null if not found
	 */
	 public function getDroppedControl ()
	 {
		 $control=null;
		 $service=prado::getApplication()->getService();
		 if ($service instanceof TPageService)
		 {
			// Find the control
			// Warning, this will not work if you have a '_' in your control Id !
			$dropControlId=str_replace(TControl::CLIENT_ID_SEPARATOR,TControl::ID_SEPARATOR,$this->_dragElementId);
			$control=$service->getRequestedPage()->findControl($dropControlId);
		 }
		 return $control;
	 }
}