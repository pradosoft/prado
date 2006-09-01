<?php
/**
 * TActiveCustomValidator class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: 31/08/2006 $
 * @package System.Web.UI.ActiveControls
 */

/**
 * TActiveCustomValidator Class
 *
 * Performs custom validation using only server-side {@link onServerValidate onServerValidate}
 * validation event. The client-side uses callbacks to raise
 * the {@link onServerValidate onServerValidate} event.
 *
 * Beware that the {@link onServerValidate onServerValidate} may be
 * raised when the control to validate on the client side
 * changes value, that is, the server validation may be called many times.
 *
 * After the callback or postback, the {@link onServerValidate onServerValidate}
 * is raised once more. The {@link getIsCallback IsCallback} property
 * will be true when validation is made during a callback request.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $ 31/08/2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TActiveCustomValidator extends TCustomValidator
	implements ICallbackEventHandler, IActiveControl
{
	/**
	 * @var boolean true if validation is made during a callback request.
	 */
	private $_isCallback = false;

	/**
	 * @return boolean true if validation is made during a callback request.
	 */
	public function getIsCallback()
	{
		return $this->_isCallback;
	}

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
	 * Client validation function is NOT supported.
	 */
	public function setClientValidationFunction($value)
	{
		throw new TNotSupportedException('tactivecustomvalidator_clientfunction_unsupported',
			get_class($this));
	}

	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. The {@link onServerValidate
	 * OnServerValidate} event is raised first and then the
	 * {@link onCallback OnCallback} event.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */
 	public function raiseCallbackEvent($param)
	{
		$this->_isCallback = true;
		$result = $this->onServerValidate($param->getParameter());
		$param->setData($result);
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
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options=TBaseValidator::getClientScriptOptions();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}

	/**
	 * Register the javascript for the active custom validator.
	 */
	protected function registerClientScriptValidator()
	{
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(), $this->getClientScriptOptions());
	}

	/**
	 * @return string corresponding javascript class name for this this.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveCustomValidator';
	}
}

?>