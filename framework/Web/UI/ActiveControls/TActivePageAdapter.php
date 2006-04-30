<?php
/**
 * TActivePageAdapter class file
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 */
 
/**
 * TActivePageAdapter class.
 * 
 * Callback request page handler.
 * 
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TActivePageAdapter extends TControlAdapter
{	
	/**
	 * @var ICallbackEventHandler callback event handler. 
	 */
	private $_callbackEventTarget;
	/**
	 * @var mixed callback event parameter.
	 */
	private $_callbackEventParameter;
	/**
	 * @var TCallbackClientScript callback client script handler
	 */
	private $_callbackClient;
	/**
	 * @var TCallbackResponse callback response handler.
	 */
	private $_callbackResponse;
	
	private $_callbackEventResult;
	 
	/**
	 * Constructor, trap errors and exception to let the callback response
	 * handle them.
	 */
	public function __construct(TPage $control)
	{
		parent::__construct($control);
		$this->trapCallbackErrorsExceptions();
	}

	/**
	 * Process the callback request.
	 */
	public function processCallbackEvent($writer)
	{
		Prado::trace("ActivePage raiseCallbackEvent()",'System.Web.UI.ActiveControls.TActivePageAdapter');
		$this->raiseCallbackEvent();
	}
	
	protected function trapCallbackErrorsExceptions()
	{
		//TODO: How to trap the errors and exceptions and return them
		// as part of the response.
	}
	
	public function renderCallbackResponse($writer)
	{
		Prado::trace("ActivePage renderCallbackResponse()",'System.Web.UI.ActiveControls.TActivePageAdapter');
		$this->renderResponse($writer);
	}	
	
	protected function renderResponse($writer)
	{
		//var_dump(getallheaders());
		//TODO: How to render the response, it will contain 3 pieces of data
		// 1) The arbituary data returned to the client-side callback handler
		// 2) client-side function call statements
		// 3) Content body, which may need to be partitioned
		
		/*
		$response = $this->getCallbackResponseHandler();
		$response->writeClientScriptResponse($this->getCallbackClientHandler());
		$response->writeResponseData($this->getCallbackEventResult());
		$response->flush();
		*/
	}
	
	/**
	 * Trys to find the callback event handler and raise its callback event.
	 * @throws TInvalidCallbackRequestException if call back target is not
	 * found.
	 * @throws TInvalidCallbackHandlerException if the requested target does not
	 * implement ICallbackEventHandler.
	 */
	private function raiseCallbackEvent()
	{
		 if(($callbackHandler=$this->getCallbackEventTarget())!==null)
		 {
			if($callbackHandler instanceof ICallbackEventHandler)
				$callbackHandler->raiseCallbackEvent($this->getCallbackEventParameter());
			else
				throw new TInvalidCallbackHandlerException($callbackHandler->getUniqueID());
		 }
		 else
		 {
		 	$target = $this->getRequest()->itemAt(TPage::FIELD_CALLBACK_TARGET);
		 	throw new TInvalidCallbackRequestException($target);
		 }
	}
	
	/**
	 * @return mixed callback event result.
	 */
	public function getCallbackEventResult()
	{
		return $this->_callbackEventResult->getResult();
	}
	
	/**
	 * @return TControl the control responsible for the current callback event,
	 * null if nonexistent
	 */
	public function getCallbackEventTarget()
	{
		if($this->_callbackEventTarget===null)
		{
			$eventTarget=$this->getRequest()->itemAt(TPage::FIELD_CALLBACK_TARGET);
			if(!empty($eventTarget))
				$this->_callbackEventTarget=$this->getPage()->findControl($eventTarget);
		}
		return $this->_callbackEventTarget;
	}

	/**
	 * Registers a control to raise callback event in the current request.
	 * @param TControl control registered to raise callback event.
	 */
	public function setCallbackEventTarget(TControl $control)
	{
		$this->_callbackEventTarget=$control;
	}

	/**
	 * Callback parameter is decoded assuming JSON encoding. 
	 * @return string postback event parameter
	 */
	public function getCallbackEventParameter()
	{
		if($this->_callbackEventParameter===null)
		{
			$param = $this->getRequest()->itemAt(TPage::FIELD_CALLBACK_PARAMETER);
			if(strlen($param) > 0)
				$this->_callbackEventParameter=TJavascript::jsonDecode((string)$param);
			var_dump($param);
		}
		return $this->_callbackEventParameter;
	}
	
	/**
	 * @param mixed postback event parameter
	 */
	public function setCallbackEventParameter($value)
	{
		$this->_callbackEventParameter=$value;
	}
	
	/**
	 * Gets the callback client script handler that allows javascript functions
	 * to be executed during the callback response. 
	 * @return TCallbackClientScript callback client handler.
	 */
	public function getCallbackClientHandler()
	{
		if(is_null($this->_callbackClient))
			$this->_callbackClient = new TCallbackClientScript;
		return $this->_callbackClient;
	}
	
	/**
	 * @param TCallbackClientScript new callback client handler.
	 */
	public function setCallbackClientHandler($handler)
	{
		$this->_callbackClient = $handler;
	}
	
	/**
	 * Gets the callback response handler.
	 * @return TCallbackResponse callback response
	 */
	public function getCallbackResponseHandler()
	{
		if(is_null($this->_callbackResponse))
			$this->_callbackResponse = new TCallbackResponse;
		return $this->_callbackResponse;
	}
	
	/**
	 * @param TCallbackResponse new callback response handler.
	 */
	public function setCallbackResponseHandler($handler)
	{
		$this->_callbackResponse = $handler;
	}
}

class TInvalidCallbackHandlerException extends TException
{
	
} 

class TInvalidCallbackRequestException extends TException
{
}

?>
