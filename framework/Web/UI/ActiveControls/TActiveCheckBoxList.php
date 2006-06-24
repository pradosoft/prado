<?php
/**
 * TActiveCheckBoxList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  : $
 * @package System.Web.UI.ActiveControls
 */

/**
 * Load active control adapter.
 */
Prado::using('System.Web.UI.ActiveControls.TActiveListControlAdapter');

/**
 * TActiveCheckBoxList class.
 * 
 * The active control counter part to checkbox list control. 
 * The {@link setAutoPostBack AutoPostBack} property is set to true by default. 
 * Thus, when a checkbox is clicked a {@link onCallback OnCallback} event is 
 * raised after {@link OnSelectedIndexChanged} event. 
 * 
 * With {@link TBaseActiveControl::setEnableUpdate() ActiveControl.EnabledUpdate}
 * set to true (default is true), changes to the selection will be updated
 * on the client side.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Sun Jun 25 01:50:27 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TActiveCheckBoxList extends TCheckBoxList implements IActiveControl, ICallbackEventHandler
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveListControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveListControlAdapter($this));
		$this->setAutoPostBack(true);
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}
	
	/**
	 * No client class for this control.
	 * This method overrides the parent implementation.
	 * @return null no javascript class name.
	 */
	protected function getClientClassName()
	{
		return null;
	}	
	
	/**
	 * Creates a control used for repetition (used as a template).
	 * @return TControl the control to be repeated
	 */
	protected function createRepeatedControl()
	{
		return new TActiveCheckBox;
	}
	
	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. 
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */
 	public function raiseCallbackEvent($param)
	{
		$this->onCallback($param);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter event parameter to be passed to the event handlers
	 */
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}	
}

?>