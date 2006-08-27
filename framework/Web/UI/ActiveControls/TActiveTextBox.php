<?php
/**
 * TActiveTextBox class file.
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
Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');

/**
 * TActiveTextBox class.
 *
 * TActiveTextBox allows the {@link setText Text} property of the textbox to
 * be changed during callback. When {@link setAutoPostBack AutoPostBack} property
 * is true, changes to the textbox contents will perform a callback request causing
 * {@link onTextChanged OnTextChange} to be fired first followed by {@link onCallback OnCallback}
 * event.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Sun Jun 18 20:05:16 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TActiveTextBox extends TTextBox implements ICallbackEventHandler, IActiveControl
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Client-side Text property can only be updated after the OnLoad stage.
	 * @param string text content for the textbox
	 */
	public function setText($value)
	{
		parent::setText($value);
		if($this->getActiveControl()->canUpdateClientSide() && $this->getHasLoadedPostData())
			$this->getPage()->getCallbackClient()->setValue($this, $value);
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

	/**
	 * Renders the javascript for textbox.
	 */
	protected function renderClientControlScript($writer)
	{
		$writer->addAttribute('id',$this->getClientID());
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(), $this->getPostBackOptions());
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveTextBox';
	}
}

?>