<?php
/**
 * TCallbackClientSideOptions class file
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 */
 
/**
 * TCallbackClientSideOptions class.
 * 
 * The following client side events are executing in order if the callback
 * request and response are send and received successfuly.
 * 
 * - <b>onUninitialized</b> executed when callback request is uninitialized. 
 * - <b>onLoading</b> executed when callback request is initiated 
 * - <b>onLoaded</b> executed when callback request begins. 
 * - <b>onInteractive</b> executed when callback request is in progress. 
 * - <b>onComplete</b>executed when callback response returns.
 * 
 * The <tt>OnSuccess</tt> and <tt>OnFailure</tt> events are raised when the
 * response is returned. A successful request/response will raise
 * <tt>OnSuccess</tt> event otherwise <tt>OnFailure</tt> will be raised.
 * 
 * - <b>onSuccess</b> executed when callback request returns and is successful. 
 * - <b>onFailure</b> executed when callback request returns and fails.
 * - <b>onException</b> raised when callback request fails due to
 * request/response errors.
 * 
 * - <b>PostInputs</b> true to collect the form inputs and post them during
 * callback, default is true.
 * - <b>RequestTimeOut</b> The request timeout in milliseconds.
 * - <b>HasPriority</b> true to ensure that the callback request will be sent
 * immediately and will abort existing prioritized requests. It does not affect
 * callbacks that are not prioritized.
 * - <b>EnablePageStateUpdate</b> enable the callback response to enable the
 * viewstate update. This will automatically set HasPrority to true when
 * enabled.
 * 
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TCallbackClientSideOptions extends TClientSideOptions
{
	/**
	 * Returns javascript statement enclosed within a javascript function.
	 * @param string javascript statement, if string begins within
	 * "javascript:" the whole string is assumed to be a function.
	 * @return string javascript statement wrapped in a javascript function
	 */
	protected function ensureFunction($javascript)
	{
		return "function(request, result){ {$javascript} }";
	}
	
	/**
	 * @return string javascript code for client-side onUninitialized event
	 */
	public function getOnUninitialized()
	{
		return $this->getOption('onUninitialized');
	}
	
	/**
	 * @param string javascript code for client-side onUninitialized event.
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
	 * @param string javascript code for client-side onLoading event.
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
	 * @param string javascript code for client-side onLoaded event.
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
	 * @param string javascript code for client-side onInteractive event.
	 */
	public function setonInteractive($javascript)
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
	 * @param string javascript code for client-side onComplete event.
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
	 * @param string javascript code for client-side onSuccess event.
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
	 * @param string javascript code for client-side onFailure event.
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
	 * @param string javascript code for client-side onException event.
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
	 * @param boolean true to post the inputs of the form with callback
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
	 * @param integer callback request timeout 
	 */
	public function setRequestTimeOut($value)
	{
		$this->setOption('RequestTimeOut', TPropertyValue::ensureInteger($value));
	}
	
	/**
	 * @return boolean true if the callback request has priority and will abort
	 * existing prioritized request in order to send immediately. It does not
	 * affect callbacks that are not prioritized.
	 */
	public function getHasPriority()
	{
		return $this->getOption('HasPriority');
	}
	
	/**
	 * @param boolean true to ensure that the callback request will be sent
	 * immediately and will abort existing prioritized requests. It does not
	 * affect callbacks that are not prioritized.
	 */
	public function setHasPriority($value)
	{
		$hasPriority = TPropertyValue::ensureBoolean($value);
		$this->setOption('HasPriority', $hasPriority);
		if(!$hasPriority)
			$this->setEnablePageStateUpdate(false);
	}
	
	/**
	 * Set to true to enable the callback response to enable the viewstate
	 * update. This will automatically set HasPrority to true.
	 * @param boolean true enables the callback response to update the
	 * viewstate.
	 */
	public function setEnablePageStateUpdate($value)
	{
		$enabled = TPropertyValue::ensureBoolean($value); 
		$this->setOption('EnablePageStateUpdate', $enabled);
		if($enabled) 
			$this->setHasPriority(true);
	}
	
	/**
	 * @return boolean client-side viewstate will be updated on callback
	 * response if true.
	 */
	public function getEnablePageStateUpdate()
	{
		return $this->getOption('EnablePageStateUpdate');
	}
} 

?>