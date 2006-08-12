<?php
/**
 * TActiveListBox class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  : $
 * @package System.Web.UI.ActiveControls
 */

/**
 * TActiveListBox class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Mon Jun 26 00:50:16 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TActiveListBox extends TListBox implements IActiveControl, ICallbackEventHandler
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
	 * Javascript client class for this control.
	 * This method overrides the parent implementation.
	 * @return null no javascript class name.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveListBox';
	}

	/**
	 * Loads user input data. Disables the client-side update during loading
	 * and restore the EnableUpdate of ActiveControl after loading.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the component has been changed
	 */
	public function loadPostData($key,$values)
	{
		$enabled = $this->getActiveControl()->getEnableUpdate();
		$this->getActiveControl()->setEnableUpdate(false);
		$result = parent::loadPostData($key, $values);
		$this->getActiveControl()->setEnableUpdate($enabled);
		return $result;
	}

	/**
	 * Sets the selection mode of the list control (Single, Multiple)
	 * on the client-side if the  {@link setEnableUpdate EnableUpdate}
	 * property is set to true.
	 * @param string the selection mode
	 */
	public function setSelectionMode($value)
	{
		parent::setSelectionMode($value);
		$multiple = $this->getIsMultiSelect();
		$id = $this->getUniqueID(); $multi_id = $id.'[]';
		if($multiple)
			$this->getPage()->registerPostDataLoader($multi_id);
		if($this->getActiveControl()->canUpdateClientSide())
		{
			$client = $this->getPage()->getCallbackClient();
			$client->setAttribute($this, 'multiple', $multiple ? 'multiple' : false);
			$client->setAttribute($this, 'name', $multiple ? $multi_id : $id);
			if($multiple)
				$client->addPostDataLoader($multi_id);
		}
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