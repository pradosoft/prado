<?php
/*
 * Created on 1/05/2006
 */

class TClientSideOptions extends TComponent
{
	private $_options;
	
	public function __construct()
	{
		$this->_options = Prado::createComponent('System.Collections.TMap');
	}
	
	protected function setFunction($name, $code)
	{
		$this->_options->add($name, $this->ensureFunction($code));
	}
	
	protected function getOption($name)
	{
		return $this->_options->itemAt($name);
	}
	
	public function getOptions()
	{
		return $this->_options;
	}
	
	protected function ensureFunction($javascript)
	{
		return $javascript;
	}
}

/**
 * The following client side events are executing in order if the callback
 * request and response are send and received successfuly.
 * 
 * - <b>onUninitialized</b> executed when AJAX request is uninitialized. 
 * - <b>onLoading</b> executed when AJAX request is initiated 
 * - <b>onLoaded</b> executed when AJAX request begins. 
 * - <b>onInteractive</b> executed when AJAX request is in progress. 
 * - <b>onComplete</b>executed when AJAX response returns.
 * 
 * The <tt>OnSuccess</tt> and <tt>OnFailure</tt> events are raised when the
 * response is returned. A successful request/response will raise
 * <tt>OnSuccess</tt> event otherwise <tt>OnFailure</tt> will be raised.
 * 
 * - <b>onSuccess</b> executed when AJAX request returns and is successful. 
 * - <b>onFailure</b> executed when AJAX request returns and fails.
 * 
 * - <b>CausesValidation</b> true to perform before callback request.
 * - <b>ValidationGroup</b> validation grouping name.  
 */
class TCallbackClientSideOptions extends TClientSideOptions
{
	protected function ensureFunction($javascript)
	{
		if(TJavascript::isFunction($javascript))
			return $javascript;
		else
		{
			$code = "function(request, result){ {$javascript} }";
			return TJavascript::quoteFunction($code);
		}
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
	
	public function getCausesValidation()
	{
		return $this->getOption('CausesValidation');
	}
	
	public function setCausesValidation($value)
	{
		$this->getOptions()->add('CausesValidation', TPropertyValue::ensureBoolean($value));
	}
	
	public function getValidationGroup()
	{
		return $this->getOption('ValidationGroup');
	}
	
	public function setValidationGroup($value)
	{
		$this->getOptions()->add('ValidationGroup', $value);
	}
	
	public function getValidationForm()
	{
		return $this->getOption('Form');
	}
	
	public function setValidationForm($value)
	{
		$this->getOptions()->add('Form', $value);
	}
} 

?>
