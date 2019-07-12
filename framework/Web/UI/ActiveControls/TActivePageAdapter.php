<?php
/**
 * TActivePageAdapter, TCallbackErrorHandler and TInvalidCallbackException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load callback response adapter class.
 */
use Prado\Prado;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\TPage;

/**
 * TActivePageAdapter class.
 *
 * Callback request handler.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @package Prado\Web\UI\ActiveControls
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
	 * Callback error header name.
	 */
	const CALLBACK_DEBUG_HEADER = 'X-PRADO-DEBUG';
	/**
	 * Callback page state header name.
	 */
	const CALLBACK_PAGESTATE_HEADER = 'X-PRADO-PAGESTATE';
	/**
	 * Script list header name.
	 */
	const CALLBACK_SCRIPTLIST_HEADER = 'X-PRADO-SCRIPTLIST';
	/**
	 * Stylesheet list header name.
	 */
	const CALLBACK_STYLESHEETLIST_HEADER = 'X-PRADO-STYLESHEETLIST';
	/**
	 * Stylesheet header name.
	 */
	const CALLBACK_STYLESHEET_HEADER = 'X-PRADO-STYLESHEET';
	/**
	 * Hidden field list header name.
	 */
	const CALLBACK_HIDDENFIELDLIST_HEADER = 'X-PRADO-HIDDENFIELDLIST';

	/**
	 * Callback redirect url header name.
	 */
	const CALLBACK_REDIRECT = 'X-PRADO-REDIRECT';

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

	private $_controlsToRender = [];

	/**
	 * Constructor, trap errors and exception to let the callback response
	 * handle them.
	 * @param TPage $control
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
	 * @param THtmlWriter $writer html content writer.
	 */
	public function processCallbackEvent($writer)
	{
		Prado::trace("ActivePage raiseCallbackEvent()", 'Prado\Web\UI\ActiveControls\TActivePageAdapter');
		$this->raiseCallbackEvent();
	}

	/**
	 * Register a control for defered render() call.
	 * @param TControl $control control for defered rendering
	 * @param THtmlWriter $writer the renderer
	 */
	public function registerControlToRender($control, $writer)
	{
		$id = $control->getUniqueID();
		if (!isset($this->_controlsToRender[$id])) {
			$this->_controlsToRender[$id] = [$control, $writer];
		}
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
	 * @param THtmlWriter $writer html content writer.
	 */
	public function renderCallbackResponse($writer)
	{
		Prado::trace("ActivePage renderCallbackResponse()", 'Prado\Web\UI\ActiveControls\TActivePageAdapter');
		if (($url = $this->getResponse()->getAdapter()->getRedirectedUrl()) === null) {
			$this->renderResponse($writer);
		} else {
			$this->redirect($url);
		}
	}

	/**
	 * Redirect url on the client-side using javascript.
	 * @param string $url new url to load.
	 */
	protected function redirect($url)
	{
		Prado::trace("ActivePage redirect()", 'Prado\Web\UI\ActiveControls\TActivePageAdapter');
		$this->appendContentPart($this->getResponse(), self::CALLBACK_REDIRECT, $url);
	}

	/**
	 * Renders the callback response by adding additional callback data and
	 * javascript actions in the header and page state if required.
	 * @param THtmlWriter $writer html content writer.
	 */
	protected function renderResponse($writer)
	{
		Prado::trace("ActivePage renderResponse()", 'Prado\Web\UI\ActiveControls\TActivePageAdapter');
		//renders all the defered render() calls.
		foreach ($this->_controlsToRender as $rid => $forRender) {
			$forRender[0]->render($forRender[1]);
		}

		$response = $this->getResponse();

		//send response data in header
		if ($response->getHasAdapter()) {
			$responseData = $response->getAdapter()->getResponseData();
			if ($responseData !== null) {
				$data = TJavaScript::jsonEncode($responseData);

				$this->appendContentPart($response, self::CALLBACK_DATA_HEADER, $data);
			}
		}

		//sends page state in header
		if (($handler = $this->getCallbackEventTarget()) !== null) {
			if ($handler->getActiveControl()->getClientSide()->getEnablePageStateUpdate()) {
				$pagestate = $this->getPage()->getClientState();
				$this->appendContentPart($response, self::CALLBACK_PAGESTATE_HEADER, $pagestate);
			}
		}

		//safari must receive at least 1 byte of data.
		$writer->write(" ");

		//output the end javascript
		if ($this->getPage()->getClientScript()->hasEndScripts()) {
			$writer = $response->createHtmlWriter();
			$this->getPage()->getClientScript()->renderEndScriptsCallback($writer);
			$this->getPage()->getCallbackClient()->evaluateScript($writer);
		}

		//output the actions
		$executeJavascript = $this->getCallbackClientHandler()->getClientFunctionsToExecute();
		$actions = TJavaScript::jsonEncode($executeJavascript);
		$this->appendContentPart($response, self::CALLBACK_ACTION_HEADER, $actions);


		$cs = $this->Page->getClientScript();

		// collect all stylesheet file references
		$stylesheets = $cs->getStyleSheetUrls();
		if (count($stylesheets) > 0) {
			$this->appendContentPart($response, self::CALLBACK_STYLESHEETLIST_HEADER, TJavaScript::jsonEncode($stylesheets));
		}

		// collect all stylesheet snippets references
		$stylesheets = $cs->getStyleSheetCodes();
		if (count($stylesheets) > 0) {
			$this->appendContentPart($response, self::CALLBACK_STYLESHEET_HEADER, TJavaScript::jsonEncode($stylesheets));
		}

		// collect all script file references
		$scripts = $cs->getScriptUrls();
		if (count($scripts) > 0) {
			$this->appendContentPart($response, self::CALLBACK_SCRIPTLIST_HEADER, TJavaScript::jsonEncode($scripts));
		}

		// collect all hidden field references
		$fields = $cs->getHiddenFields();
		if (count($fields) > 0) {
			$this->appendContentPart($response, self::CALLBACK_HIDDENFIELDLIST_HEADER, TJavaScript::jsonEncode($fields));
		}
	}

	/**
	 * Appends data or javascript code to the body content surrounded with delimiters
	 * @param mixed $response
	 * @param mixed $delimiter
	 * @param mixed $data
	 */
	private function appendContentPart($response, $delimiter, $data)
	{
		$content = $response->createHtmlWriter();
		$content->getWriter()->setBoundary($delimiter);
		$content->write($data);
	}

	/**
	 * Trys to find the callback event handler and raise its callback event.
	 * @throws TInvalidCallbackException if call back target is not found.
	 * @throws TInvalidCallbackException if the requested target does not
	 * implement ICallbackEventHandler.
	 */
	private function raiseCallbackEvent()
	{
		if (($callbackHandler = $this->getCallbackEventTarget()) !== null) {
			if ($callbackHandler instanceof ICallbackEventHandler) {
				$param = $this->getCallbackEventParameter();
				$result = new TCallbackEventParameter($this->getResponse(), $param);
				$callbackHandler->raiseCallbackEvent($result);
			} else {
				throw new TInvalidCallbackException(
					'callback_invalid_handler',
					$callbackHandler->getUniqueID()
				);
			}
		} else {
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
		if ($this->_callbackEventTarget === null) {
			$eventTarget = $this->getRequest()->itemAt(TPage::FIELD_CALLBACK_TARGET);
			if (!empty($eventTarget)) {
				$this->_callbackEventTarget = $this->getPage()->findControl($eventTarget);
			}
		}
		return $this->_callbackEventTarget;
	}

	/**
	 * Registers a control to raise callback event in the current request.
	 * @param TControl $control control registered to raise callback event.
	 */
	public function setCallbackEventTarget(TControl $control)
	{
		$this->_callbackEventTarget = $control;
	}

	/**
	 * Gets callback parameter.
	 * @return string postback event parameter
	 */
	public function getCallbackEventParameter()
	{
		if ($this->_callbackEventParameter === null) {
			$param = $this->getRequest()->itemAt(TPage::FIELD_CALLBACK_PARAMETER);
			$this->_callbackEventParameter = $param;
		}
		return $this->_callbackEventParameter;
	}

	/**
	 * @param mixed $value postback event parameter
	 */
	public function setCallbackEventParameter($value)
	{
		$this->_callbackEventParameter = $value;
	}

	/**
	 * Gets the callback client script handler. It handlers the javascript functions
	 * to be executed during the callback response.
	 * @return TCallbackClientScript callback client handler.
	 */
	public function getCallbackClientHandler()
	{
		if ($this->_callbackClient === null) {
			$this->_callbackClient = new TCallbackClientScript;
		}
		return $this->_callbackClient;
	}
}
