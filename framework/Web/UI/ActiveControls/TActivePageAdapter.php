<?php
/**
 * TActivePageAdapter, TCallbackEventParameter, TCallbackErrorHandler
 * and TInvalidCallbackException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.ActiveControls
 */

/**
 * Load callback response adapter class.
 */
Prado::using('System.Web.UI.ActiveControls.TCallbackResponseAdapter');

/**
 * TActivePageAdapter class.
 *
 * Callback request handler.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @version $Id$
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TActivePageAdapter extends TControlAdapter
{
	/**
	 * Callback response data header name.
	 */
	const CALLBACK_DATA_HEADER = 'X-PRADO-DATA';
	/**
	 * Callback response client-side action header name.
	 */
	const CALLBACK_ACTION_HEADER = 'X-PRADO-ACTIONS';
	/**
	 * Callback error header name.
	 */
	const CALLBACK_ERROR_HEADER = 'X-PRADO-ERROR';
	/**
	 * Callback page state header name.
	 */
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
	 * @param THtmlWriter html content writer.
	 */
	public function processCallbackEvent($writer)
	{
		Prado::trace("ActivePage raiseCallbackEvent()",'System.Web.UI.ActiveControls.TActivePageAdapter');
		$this->raiseCallbackEvent();
	}

	/**
	 * Trap errors and exceptions to be handled by TCallbackErrorHandler.
	 */
	protected function trapCallbackErrorsExceptions()
	{
		$this->getApplication()->setErrorHandler(new TCallbackErrorHandler);
	}

	/**
	 * Render the callback response.
	 * @param THtmlWriter html content writer.
	 */
	public function renderCallbackResponse($writer)
	{
		Prado::trace("ActivePage renderCallbackResponse()",'System.Web.UI.ActiveControls.TActivePageAdapter');
		$this->renderResponse($writer);
	}

	/**
	 * Renders the callback response by adding additional callback data and
	 * javascript actions in the header and page state if required.
	 * @param THtmlWriter html content writer.
	 */
	protected function renderResponse($writer)
	{
		$response = $this->getResponse();

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

		//safari must receive at least 1 byte of data.
		$writer->write(" ");

		//output the end javascript
		if($this->getPage()->getClientScript()->hasEndScripts())
		{
			$writer = $response->createHtmlWriter();
			$this->getPage()->getClientScript()->renderEndScripts($writer);
			$this->getPage()->getCallbackClient()->evaluateScript($writer);
		}

		//output the actions
		$executeJavascript = $this->getCallbackClientHandler()->getClientFunctionsToExecute();
		$actions = TJavascript::jsonEncode($executeJavascript);
		$response->appendHeader(self::CALLBACK_ACTION_HEADER.': '.$actions);

	}

	/**
	 * Trys to find the callback event handler and raise its callback event.
	 * @throws TInvalidCallbackException if call back target is not found.
	 * @throws TInvalidCallbackException if the requested target does not
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
				throw new TInvalidCallbackException(
					'callback_invalid_handler', $callbackHandler->getUniqueID());
			}
		 }
		 else
		 {
		 	$target = $this->getRequest()->itemAt(TPage::FIELD_CALLBACK_TARGET);
		 	throw new TInvalidCallbackException('callback_invalid_target', $target);
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
	 * Gets callback parameter. JSON encoding is assumed.
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
	 * Gets the callback client script handler. It handlers the javascript functions
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
 * requestion in the {@link getCallbackParameter CallbackParameter} property. The
 * callback response content (e.g. new HTML content) must be rendered
 * using an THtmlWriter obtained from the {@link getNewWriter NewWriter}
 * property, which returns a <b>NEW</b> instance of TCallbackResponseWriter.
 *
 * Each instance TCallbackResponseWriter is associated with a unique
 * boundary delimited. By default each panel only renders its own content.
 * To replace the content of ONE panel with that of rendered from multiple panels
 * use the same writer instance for the panels to be rendered.
 *
 * The response data (i.e., passing results back to the client-side
 * callback handler function) can be set using {@link setResponseData ResponseData} property.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @version $Id$
 * @package System.Web.UI.ActiveControls
 * @since 3.1
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
	 * @return TCallbackResponseWriter holds the response content.
	 */
	public function getNewWriter()
	{
		return $this->_response->createHtmlWriter(null);
	}

	/**
	 * @return mixed callback request parameter.
	 */
	public function getCallbackParameter()
	{
		return $this->_parameter;
	}

	/**
	 * @param mixed callback response data.
	 */
	public function setResponesData($value)
	{
		$this->_response->getAdapter()->setResponseData($value);
	}

	/**
	 * @return mixed callback response data.
	 */
	public function getResponesData()
	{
		return $this->_response->getAdapter()->getResponseData();
	}
}

/**
 * TCallbackErrorHandler class.
 *
 * Captures errors and exceptions and send them back during callback response.
 * When the application is in debug mode, the error and exception stack trace
 * are shown. A TJavascriptLogger must be present on the client-side to view
 * the error stack trace.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TCallbackErrorHandler extends TErrorHandler
{
	/**
	 * Displays the exceptions to the client-side TJavascriptLogger.
	 * A HTTP 500 status code is sent and the stack trace is sent as JSON encoded.
	 * @param Exception exception details.
	 */
	protected function displayException($exception)
	{
		if($this->getApplication()->getMode()===TApplication::STATE_DEBUG)
		{
			$response = $this->getApplication()->getResponse();
			$trace = TJavascript::jsonEncode($this->getExceptionStackTrace($exception));
			$response->appendHeader('HTTP/1.0 500 Internal Error');
			$response->appendHeader(TActivePageAdapter::CALLBACK_ERROR_HEADER.': '.$trace);
		}
		else
		{
			error_log("Error happened while processing an existing error:\n".$exception->__toString());
			header('HTTP/1.0 500 Internal Error');
		}
		$this->getApplication()->getResponse()->flush();
	}

	/**
	 * @param Exception exception details.
	 * @return array exception stack trace details.
	 */
	private function getExceptionStackTrace($exception)
	{
		$data['code']=$exception->getCode() > 0 ? $exception->getCode() : 500;
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

/**
 * TInvalidCallbackException class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TInvalidCallbackException extends TException
{
}

?>