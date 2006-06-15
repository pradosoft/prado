<?php
/**
 * TActivePageAdapter class file.
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
	const CALLBACK_DATA_HEADER = 'X-PRADO-DATA';
	const CALLBACK_ACTION_HEADER = 'X-PRADO-ACTIONS';
	const CALLBACK_ERROR_HEADER = 'X-PRADO-ERROR';
	const CALLBACK_PAGESTATE_HEADER = 'X-PRADO-PAGESTATE';
		
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
	 * Constructor, trap errors and exception to let the callback response
	 * handle them.
	 */
	public function __construct(TPage $control)
	{
		parent::__construct($control);
		
		//TODO: can this be done later?
		$response = $this->getApplication()->getResponse();
		$response->setAdapter(new TCallbackResponseAdapter($response));
		
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
		$this->getApplication()->setErrorHandler(new TCallbackErrorHandler);
	}
	
	/**
	 * Render the callback response.
	 */
	public function renderCallbackResponse($writer)
	{
		Prado::trace("ActivePage renderCallbackResponse()",'System.Web.UI.ActiveControls.TActivePageAdapter');
		$this->renderResponse($writer);
	}	
	
	/**
	 * Renders the callback response by adding additional callback data and
	 * javascript actions in the header and page state if required.
	 */
	protected function renderResponse($writer)
	{
		$response = $this->getResponse();
		$executeJavascript = $this->getCallbackClientHandler()->getClientFunctionsToExecute()->toArray();
		$actions = TJavascript::jsonEncode($executeJavascript);
		$response->appendHeader(self::CALLBACK_ACTION_HEADER.': '.$actions);
		
		//send response data in header
		if($response->getHasAdapter())
		{
			$responseData = $response->getAdapter()->getResponseData();
			if(!is_null($responseData))
			{
				$data = TJavascript::jsonEncode($responseData);
				$response->appendHeader(self::CALLBACK_DATA_HEADER.': '.$data);
			}
		}
		
		//sends page state in header
		if(($handler = $this->getCallbackEventTarget()) !== null)
		{
			if($handler->getActiveControl()->getClientSide()->getEnablePageStateUpdate())
			{
				$pagestate = $this->getPage()->getClientState();
				$response->appendHeader(self::CALLBACK_PAGESTATE_HEADER.': '.$pagestate);
			}
		}
	}

	/**
	 * Trys to find the callback event handler and raise its callback event.
	 * @throws TInvalidCallbackRequestException if call back target is not found.
	 * @throws TInvalidCallbackHandlerException if the requested target does not
	 * implement ICallbackEventHandler.
	 */
	private function raiseCallbackEvent()
	{
		 if(($callbackHandler=$this->getCallbackEventTarget())!==null)
		 {
			if($callbackHandler instanceof ICallbackEventHandler)
			{
				$param = $this->getCallbackEventParameter();
				$result = new TCallbackEventParameter($this->getResponse(), $param);
				$callbackHandler->raiseCallbackEvent($result);
			}
			else
			{
				throw new TInvalidCallbackHandlerException($callbackHandler->getUniqueID());
			}
		 }
		 else
		 {
		 	$target = $this->getRequest()->itemAt(TPage::FIELD_CALLBACK_TARGET);
		 	throw new TInvalidCallbackRequestException($target);
		 }
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
}

/**
 * TCallbackEventParameter class.
 * 
 * The TCallbackEventParameter provides the parameter passed during the callback
 * requestion in the {@link getParameter Parameter} property. The
 * callback response response content (e.g. new HTML content) can be written to
 * the {@link getOutput Output} property, which returns an instance of
 * THtmlWriter. The response data (i.e., passing results back to the client-side
 * callback handler function) can be set using {@link setData Data} property. 
 * 
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TCallbackEventParameter extends TEventParameter
{
	/**
	 * @var THttpResponse output content.
	 */
	private $_response;
	/**
	 * @var mixed callback request parameter.
	 */
	private $_parameter;

	/**
	 * Creates a new TCallbackEventParameter.
	 */
	public function __construct($response, $parameter)
	{
		$this->_response = $response;
		$this->_parameter = $parameter;
	}

	/**
	 * @return THtmlWriter holds the response content.
	 */
	public function getOutput()
	{
		return $this->_response->createHtmlWriter(null);
	}
	
	/**
	 * @return mixed callback request parameter.
	 */
	public function getParameter()
	{
		return $this->_parameter;
	}
	
	/**
	 * @param mixed callback response data.
	 */
	public function setData($value)
	{
		$this->_response->getAdapter()->setResponseData($value);
	}
	
	/**
	 * @return mixed callback response data.
	 */
	public function getData()
	{
		return $this->_response->getAdapter()->getResponseData();
	}
}

class TCallbackErrorHandler extends TErrorHandler
{
	protected function displayException($exception)
	{
		if($this->getApplication()->getMode()===TApplication::STATE_DEBUG)
		{
			$response = $this->getApplication()->getResponse();
			$data = TJavascript::jsonEncode($this->getExceptionData($exception));			
			$response->appendHeader('HTTP/1.0 500 Internal Error');
			$response->appendHeader(TActivePageAdapter::CALLBACK_ERROR_HEADER.': '.$data);
		}
		else
		{
			error_log("Error happened while processing an existing error:\n".$exception->__toString());
			header('HTTP/1.0 500 Internal Error');
		}
		$this->getApplication()->getResponse()->flush();
	}
	
	private function getExceptionData($exception)
	{
		$data['code']=$exception->getCode() > 0 ? $exception->getCode() : 505;
		$data['file']=$exception->getFile();
		$data['line']=$exception->getLine();
		$data['trace']=$exception->getTrace();
		if($exception instanceof TPhpErrorException)
		{
			// if PHP exception, we want to show the 2nd stack level context
			// because the 1st stack level is of little use (it's in error handler)
			if(isset($trace[0]) && isset($trace[0]['file']) && isset($trace[0]['line']))
			{
				$data['file']=$trace[0]['file'];
				$data['line']=$trace[0]['line'];
			}
		}
		$data['type']=get_class($exception);
		$data['message']=$exception->getMessage();
		$data['version']=$_SERVER['SERVER_SOFTWARE'].' '.Prado::getVersion();
		$data['time']=@strftime('%Y-%m-%d %H:%M',time());
		return $data;
	}
}

class TInvalidCallbackHandlerException extends TException
{
	
} 

class TInvalidCallbackRequestException extends TException
{
}

?>