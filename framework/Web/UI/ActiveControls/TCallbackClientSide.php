<?php
/**
 * TCallbackClientSide class file
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\UI\TClientSideOptions;
use Prado\Web\UI\TControl;

/**
 * TCallbackClientSide class.
 *
 * The following client side events are executing in order if the callback
 * request and response are send and received successfuly.
 *
 * - <b>onPreDispatch</b> executed before a request is dispatched.
 * - <b>onUninitialized</b> executed when callback request is uninitialized.
 * - <b>onLoading</b>* executed when callback request is initiated
 * - <b>onLoaded</b>* executed when callback request begins.
 * - <b>onInteractive</b> executed when callback request is in progress.
 * - <b>onComplete</b>executed when callback response returns.
 * - <b>onSuccess</b> executed when callback request returns and is successful.
 * - <b>onFailure</b> executed when callback request returns and fails.
 * - <b>onException</b> raised when callback request fails due to request/response errors.
 *
 * * Note that theses 2 events are not fired correctly by Opera. To make
 *   them work in this browser, Prado will fire them just after onPreDispatch.
 *
 * In a general way, onUninitialized, onLoading, onLoaded and onInteractive events
 * are not implemented consistently in all browsers.When cross browser compatibility is
 * needed, it is best to avoid use them
 *
 * The OnSuccess and OnFailure events are raised when the
 * response is returned. A successful request/response will raise
 * OnSuccess event otherwise OnFailure will be raised.
 *
 * - <b>PostState</b> true to collect the form inputs and post them during callback, default is true.
 * - <b>RequestTimeOut</b> The request timeout in milliseconds.
 * - <b>EnablePageStateUpdate</b> enable the callback response to enable the
 *   viewstate update.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackClientSide extends TClientSideOptions
{
	/**
	 * Returns javascript statement enclosed within a javascript function.
	 * @param string $javascript javascript statement
	 * @return string javascript statement wrapped in a javascript function
	 */
	protected function ensureFunction($javascript)
	{
		return "function(sender, parameter){ {$javascript} }";
	}

	/**
	 * @param string $javascript javascript code to be executed before a request is dispatched.
	 */
	public function setOnPreDispatch($javascript)
	{
		$this->setFunction('onPreDispatch', $javascript);
	}

	/**
	 * @return string javascript code to be executed before a request is dispatched.
	 */
	public function getOnPreDispatch()
	{
		return $this->getOption('onPreDispatch');
	}

	/**
	 * @return string javascript code for client-side onUninitialized event
	 */
	public function getOnUninitialized()
	{
		return $this->getOption('onUninitialized');
	}

	/**
	 * @param string $javascript javascript code for client-side onUninitialized event.
	 */
	public function setOnUninitialized($javascript)
	{
		$this->setFunction('onUninitialized', $javascript);
	}

	/**
	 * @return string javascript code for client-side onLoading event
	 */
	public function getOnLoading()
	{
		return $this->getOption('onLoading');
	}

	/**
	 * @param string $javascript javascript code for client-side onLoading event.
	 */
	public function setOnLoading($javascript)
	{
		$this->setFunction('onLoading', $javascript);
	}

	/**
	 * @return string javascript code for client-side onLoaded event
	 */
	public function getOnLoaded()
	{
		return $this->getOption('onLoaded');
	}

	/**
	 * @param string $javascript javascript code for client-side onLoaded event.
	 */
	public function setOnLoaded($javascript)
	{
		$this->setFunction('onLoaded', $javascript);
	}
	/**
	 * @return string javascript code for client-side onInteractive event
	 */
	public function getOnInteractive()
	{
		return $this->getOption('onInteractive');
	}

	/**
	 * @param string $javascript javascript code for client-side onInteractive event.
	 */
	public function setOnInteractive($javascript)
	{
		$this->setFunction('onInteractive', $javascript);
	}
	/**
	 * @return string javascript code for client-side onComplete event
	 */
	public function getOnComplete()
	{
		return $this->getOption('onComplete');
	}

	/**
	 * @param string $javascript javascript code for client-side onComplete event.
	 */
	public function setOnComplete($javascript)
	{
		$this->setFunction('onComplete', $javascript);
	}
	/**
	 * @return string javascript code for client-side onSuccess event
	 */
	public function getOnSuccess()
	{
		return $this->getOption('onSuccess');
	}

	/**
	 * @param string $javascript javascript code for client-side onSuccess event.
	 */
	public function setOnSuccess($javascript)
	{
		$this->setFunction('onSuccess', $javascript);
	}

	/**
	 * @return string javascript code for client-side onFailure event
	 */
	public function getOnFailure()
	{
		return $this->getOption('onFailure');
	}

	/**
	 * @param string $javascript javascript code for client-side onFailure event.
	 */
	public function setOnFailure($javascript)
	{
		$this->setFunction('onFailure', $javascript);
	}

	/**
	 * @return string javascript code for client-side onException event
	 */
	public function getOnException()
	{
		return $this->getOption('onException');
	}

	/**
	 * @param string $javascript javascript code for client-side onException event.
	 */
	public function setOnException($javascript)
	{
		$this->setFunction('onException', $javascript);
	}

	/**
	 * @return boolean true to post the inputs of the form on callback, default
	 * is post the inputs on callback.
	 */
	public function getPostState()
	{
		return $this->getOption('PostInputs');
	}

	/**
	 * @param boolean $value true to post the inputs of the form with callback
	 * requests. Default is to post the inputs.
	 */
	public function setPostState($value)
	{
		$this->setOption('PostInputs', TPropertyValue::ensureBoolean($value));
	}

	/**
	 * @return integer callback request timeout.
	 */
	public function getRequestTimeOut()
	{
		return $this->getOption('RequestTimeOut');
	}

	/**
	 * @param integer $value callback request timeout
	 */
	public function setRequestTimeOut($value)
	{
		$this->setOption('RequestTimeOut', TPropertyValue::ensureInteger($value));
	}

	/**
	 * Set to true to enable the callback response to enable the viewstate
	 * update. This will automatically set HasPrority to true.
	 * @param boolean $value true enables the callback response to update the
	 * viewstate.
	 */
	public function setEnablePageStateUpdate($value)
	{
		$enabled = TPropertyValue::ensureBoolean($value);
		$this->setOption('EnablePageStateUpdate', $enabled);
	}

	/**
	 * @return boolean client-side viewstate will be updated on callback
	 * response if true. Default is true.
	 */
	public function getEnablePageStateUpdate()
	{
		$option = $this->getOption('EnablePageStateUpdate');
		return ($option === null) ? true : $option;
	}

	/**
	 * @return string post back target ID
	 */
	public function getPostBackTarget()
	{
		return $this->getOption('EventTarget');
	}

	/**
	 * @param string $value post back target ID
	 */
	public function setPostBackTarget($value)
	{
		if ($value instanceof TControl) {
			$value = $value->getUniqueID();
		}
		$this->setOption('EventTarget', $value);
	}

	/**
	 * @return string post back event parameter.
	 */
	public function getPostBackParameter()
	{
		return $this->getOption('EventParameter');
	}

	/**
	 * @param string $value post back event parameter.
	 */
	public function setPostBackParameter($value)
	{
		$this->setOption('EventParameter', $value);
	}
}
