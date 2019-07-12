<?php
/**
 * TActiveCustomValidator class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\Prado;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TCustomValidator;

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
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TActiveCustomValidator extends TCustomValidator implements ICallbackEventHandler, IActiveControl
{
	/**
	 * @var bool true if validation is made during a callback request.
	 */
	private $_isCallback = false;

	/**
	 * @return bool true if validation is made during a callback request.
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
		$this->getActiveControl()->setClientSide(new TActiveCustomValidatorClientSide);
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		return $this->getAdapter()->getBaseActiveControl()->getClientSide();
	}

	/**
	 * Client validation function is NOT supported.
	 * @param mixed $value
	 */
	public function setClientValidationFunction($value)
	{
		throw new TNotSupportedException(
			'tactivecustomvalidator_clientfunction_unsupported',
			get_class($this)
		);
	}

	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. The {@link onServerValidate
	 * OnServerValidate} event is raised first and then the
	 * {@link onCallback OnCallback} event.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$this->_isCallback = true;
		$result = $this->onServerValidate($param->getCallbackParameter());
		$param->setResponseData($result);
		$this->onCallback($param);
	}

	/**
	 * @param bool $value whether the value is valid; this method will trigger a clientside update if needed
	 */
	public function setIsValid($value)
	{
		// Always update the clientside, since the clientside's value for IsValid
		// it could have been changed by the clientside validation.

		parent::setIsValid($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$client = $this->getPage()->getCallbackClient();
			$func = 'Prado.Validation.updateActiveCustomValidator';
			$client->callClientFunction($func, [$this, $value]);
		}
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
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
		$options = TBaseValidator::getClientScriptOptions();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}

	/**
	 * Sets the text for the error message. Updates client-side error message.
	 * @param string $value the error message
	 */
	public function setErrorMessage($value)
	{
		if (parent::getErrorMessage() === $value) {
			return;
		}


		parent::setErrorMessage($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$client = $this->getPage()->getCallbackClient();
			$func = 'Prado.Validation.setErrorMessage';
			$client->callClientFunction($func, [$this, $value]);
		}
	}


	/**
	 * It's mandatory for the EnableClientScript to be activated or the TActiveCustomValidator won't work.
	 * @return bool whether client-side validation is enabled.
	 */
	public function getEnableClientScript()
	{
		return true;
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 * @param mixed $writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		TBaseValidator::registerClientScriptValidator();
	}

	/**
	 * @return string corresponding javascript class name for this this.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveCustomValidator';
	}
}
