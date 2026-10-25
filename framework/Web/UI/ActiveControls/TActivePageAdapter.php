<?php

/**
 * TActivePageAdapter, TCallbackErrorHandler and TInvalidCallbackException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load callback response adapter class.
 */
use Prado\Prado;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TRenderFilterContext;
use Prado\Web\UI\TRenderFilterParameter;
use Prado\Web\UI\WebControls\THtmlElement;

/**
 * TActivePageAdapter class.
 *
 * Callback request handler.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @since 3.1
 */
class TActivePageAdapter extends TControlAdapter
{
	/**
	 * The elements with an optional end tag that following content can close.
	 */
	private const OPTIONAL_END_TAGS = ['p', 'li', 'dt', 'dd', 'option', 'optgroup', 'rb', 'rt', 'rtc', 'rp'];

	/**
	 * Callback response data header name.
	 */
	public const CALLBACK_DATA_HEADER = 'X-PRADO-DATA';
	/**
	 * Callback response client-side action header name.
	 */
	public const CALLBACK_ACTION_HEADER = 'X-PRADO-ACTIONS';
	/**
	 * Callback error header name.
	 */
	public const CALLBACK_ERROR_HEADER = 'X-PRADO-ERROR';
	/**
	 * Callback error header name.
	 */
	public const CALLBACK_DEBUG_HEADER = 'X-PRADO-DEBUG';
	/**
	 * Callback page state header name.
	 */
	public const CALLBACK_PAGESTATE_HEADER = 'X-PRADO-PAGESTATE';
	/**
	 * Callback response part holding a fresh CSRF token for the page's hidden field.
	 * @since 4.4.0
	 */
	public const CALLBACK_CSRFTOKEN_HEADER = 'X-PRADO-CSRF-TOKEN';
	/**
	 * Script list header name.
	 */
	public const CALLBACK_SCRIPTLIST_HEADER = 'X-PRADO-SCRIPTLIST';
	/**
	 * Stylesheet list header name.
	 */
	public const CALLBACK_STYLESHEETLIST_HEADER = 'X-PRADO-STYLESHEETLIST';
	/**
	 * Stylesheet header name.
	 */
	public const CALLBACK_STYLESHEET_HEADER = 'X-PRADO-STYLESHEET';
	/**
	 * Hidden field list header name.
	 */
	public const CALLBACK_HIDDENFIELDLIST_HEADER = 'X-PRADO-HIDDENFIELDLIST';

	/**
	 * Callback redirect url header name.
	 */
	public const CALLBACK_REDIRECT = 'X-PRADO-REDIRECT';

	/**
	 * @var ?TControl callback event handler, must implement ICallbackEventHandler.
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
	 * @param \Prado\Web\UI\THtmlWriter $writer html content writer.
	 */
	public function processCallbackEvent($writer)
	{
		Prado::trace("ActivePage raiseCallbackEvent()", TActivePageAdapter::class);
		$this->raiseCallbackEvent();
	}

	/**
	 * Register a control for defered render() call.
	 * @param \Prado\Web\UI\TControl $control control for defered rendering
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	public function registerControlToRender($control, $writer)
	{
		$id = $control->getUniqueID();
		if (!isset($this->_controlsToRender[$id])) {
			$this->_controlsToRender[$id] = [$control, $writer];
		}
	}

	/**
	 * Renders a control registered by {@see registerControlToRender} with its
	 * `render()` method. The render filters of the control, then those of its
	 * ancestors that filter fragments ({@see TControl::getRenderFilterFragments}),
	 * filter what it renders into a {@see TCallbackResponseWriter}, innermost first.
	 * An exempt descendant is hidden from them, as in {@see TControl::renderControl}.
	 * The output stays in the same writer, so the boundary that client functions
	 * name during the render holds the filtered output.
	 * @param TControl $control the control to render
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer of the control
	 * @since 4.4.0
	 */
	protected function renderDeferredControl($control, $writer): void
	{
		$inner = ($writer instanceof THtmlWriter) ? $writer->getWriter() : null;
		if (!($inner instanceof TCallbackResponseWriter) || ($filters = $this->getDeferredFilters($control)) === []) {
			$control->render($writer);
			return;
		}
		$written = $inner->flushWithoutBoundary();
		if (($context = $writer->getRenderFilterContext()) === null) {
			$context = new TRenderFilterContext();
			$writer->setRenderFilterContext($context);
		}
		foreach ($filters as $filter) {
			$context->beginCapture();
		}
		$control->render($writer);
		$output = $inner->flushWithoutBoundary();
		foreach ($filters as $filter) {
			if ($output !== '') {
				$output = ($filter === $control) ? $control->onRenderFilter($output) : $this->filterFragment($filter, $control, $output);
			}
			$output = $context->endCapture((string) $output);
		}
		$inner->write($written . $output);
	}

	/**
	 * Returns the controls whose render filters see the callback re-render of a
	 * control, innermost first: the control, and each ancestor that filters fragments.
	 * Walking up from the control, the first {@see TControl::getRenderFilterExempt}
	 * setting decides for the ancestors above it.
	 * @param TControl $control the control re-rendering
	 * @return TControl[] the controls with render filters to raise
	 * @since 4.4.0
	 */
	protected function getDeferredFilters($control): array
	{
		$filters = $control->hasEventHandler('onRenderFilter') ? [$control] : [];
		$exempt = $control->getRenderFilterExempt();
		for ($parent = $control->getParent(); $parent !== null && $exempt !== true; $parent = $parent->getParent()) {
			if ($parent->getRenderFilterFragments() && $parent->hasEventHandler('onRenderFilter')) {
				$filters[] = $parent;
			}
			$exempt ??= $parent->getRenderFilterExempt();
		}
		return $filters;
	}

	/**
	 * Raises the `onRenderFilter` event of an ancestor on a fragment of its output.
	 * The fragment is inside the start tags that enclose it in the output of the
	 * ancestor ({@see getFragmentContext}), so skipped tags and exempt markup apply
	 * as in a full render. Marker comments bound the fragment; the handlers see
	 * them, and a handler that removes one leaves the fragment unfiltered.
	 * @param TControl $ancestor the ancestor
	 * @param TControl $control the control re-rendering
	 * @param string $html the output of the control re-rendering
	 * @return string the filtered output
	 * @since 4.4.0
	 */
	protected function filterFragment($ancestor, $control, string $html): string
	{
		[$open, $close] = $this->getFragmentContext($ancestor, $control);
		$marker = 'prado-fragment-' . bin2hex(random_bytes(4));
		$begin = '<!--' . $marker . '-->';
		$end = '<!--/' . $marker . '-->';
		$param = new TRenderFilterParameter($open . $begin . $html . $end . $close);
		$param->setIsFragment(true);
		$ancestor->raiseEvent('onRenderFilter', $ancestor, $param);
		$output = $param->getFilterText();
		if (($start = strpos($output, $begin)) === false || ($stop = strpos($output, $end, $start)) === false) {
			return $html;
		}
		$start += strlen($begin);
		return substr($output, $start, $stop - $start);
	}

	/**
	 * Returns the start tags that enclose a control in the output of an ancestor,
	 * and their end tags. Each control on the path from the ancestor to the parent
	 * of the control adds its element and the elements its text opens before the
	 * next control on the path. A {@see THtmlElement} adds its tag; another control
	 * adds a `div` only with {@see TRenderFilterParameter::EXEMPT_ATTRIBUTE}.
	 * The fragment can close an element with an optional end tag, such as `p`, so
	 * that element is left out, or is a `div` with its exempt attribute.
	 * @param TControl $ancestor the ancestor
	 * @param TControl $control the control re-rendering
	 * @return string[] the start tags and the end tags
	 * @since 4.4.0
	 */
	protected function getFragmentContext($ancestor, $control): array
	{
		$path = [];
		for ($node = $control; $node !== $ancestor; $node = $node->getParent()) {
			$path[] = $node;
		}
		$path[] = $ancestor;
		$path = array_reverse($path);
		$tags = [];
		for ($i = 0, $n = count($path) - 1; $i < $n; $i++) {
			if (($tag = $this->getFragmentStartTag($path[$i])) !== null) {
				$tags[] = $tag;
			}
			$text = '';
			foreach ($path[$i]->getControls() as $child) {
				if ($child === $path[$i + 1]) {
					break;
				}
				if (is_string($child)) {
					$text .= $child;
				}
			}
			array_push($tags, ...$this->getOpenTags($text));
		}
		$tags = array_values(array_filter(array_map([$this, 'getClosableTag'], $tags)));
		$close = '';
		foreach (array_reverse($tags) as [$name]) {
			$close .= '</' . $name . '>';
		}
		return [implode('', array_column($tags, 1)), $close];
	}

	/**
	 * @param TControl $control a control on the path to the control re-rendering
	 * @return ?array the name and start tag the control adds to the fragment context, or null
	 * @since 4.4.0
	 */
	protected function getFragmentStartTag($control): ?array
	{
		$name = $control instanceof THtmlElement ? strtolower($control->getTagName()) : null;
		$attribute = '';
		if ($control->getHasAttributes() && ($value = $control->getAttribute(TRenderFilterParameter::EXEMPT_ATTRIBUTE)) !== null) {
			$name ??= 'div';
			$attribute = ' ' . TRenderFilterParameter::EXEMPT_ATTRIBUTE . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"';
		}
		return $name === null ? null : [$name, '<' . $name . $attribute . '>'];
	}

	/**
	 * Replaces an element with an optional end tag, which a fragment can close,
	 * by a `div` with its {@see TRenderFilterParameter::EXEMPT_ATTRIBUTE}.
	 * @param array $tag the name and start tag of an element
	 * @return ?array the tag, the `div`, or null for an element without the attribute
	 * @since 4.4.0
	 */
	protected function getClosableTag(array $tag): ?array
	{
		if (!in_array($tag[0], self::OPTIONAL_END_TAGS, true)) {
			return $tag;
		}
		$attribute = '/\s' . TRenderFilterParameter::EXEMPT_ATTRIBUTE . '(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*))?/i';
		return preg_match($attribute, $tag[1], $match) ? ['div', '<div' . $match[0] . '>'] : null;
	}

	/**
	 * Returns the elements that markup opens and leaves open. Comments, void
	 * elements, self-closed tags, and raw text elements open nothing.
	 * @param string $markup the markup
	 * @return array[] the name and start tag of each open element, outermost first
	 * @since 4.4.0
	 */
	protected function getOpenTags(string $markup): array
	{
		$void = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];
		$open = [];
		preg_match_all('/<!--.*?-->|<(script|style|textarea|title)\b[^>]*+>.*?<\/\1\s*>|<(\/?)([a-z][^\s\/>]*+)[^>]*+>/is', $markup, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			if (($match[3] ?? '') === '') {
				continue;
			}
			$name = strtolower($match[3]);
			if ($match[2] === '/') {
				for ($i = count($open) - 1; $i >= 0; $i--) {
					if ($open[$i][0] === $name) {
						array_splice($open, $i);
						break;
					}
				}
			} elseif (!in_array($name, $void, true) && !str_ends_with($match[0], '/>')) {
				$open[] = [$name, $match[0]];
			}
		}
		return $open;
	}

	/**
	 * Trap errors and exceptions to be handled by TCallbackErrorHandler.
	 */
	protected function trapCallbackErrorsExceptions()
	{
		$this->getApplication()->setErrorHandler(new TCallbackErrorHandler());
	}

	/**
	 * Render the callback response.
	 * @param \Prado\Web\UI\THtmlWriter $writer html content writer.
	 */
	public function renderCallbackResponse($writer)
	{
		Prado::trace("ActivePage renderCallbackResponse()", TActivePageAdapter::class);
		/** @var TCallbackResponseAdapter $adapter */
		$adapter = $this->getResponse()->getAdapter();
		if (($url = $adapter->getRedirectedUrl()) === null) {
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
		Prado::trace("ActivePage redirect()", TActivePageAdapter::class);
		$this->appendContentPart($this->getResponse(), self::CALLBACK_REDIRECT, $url);
	}

	/**
	 * Renders the callback response by adding additional callback data and
	 * javascript actions in the header and page state if required.
	 * @param \Prado\Web\UI\THtmlWriter $writer html content writer.
	 */
	protected function renderResponse($writer)
	{
		Prado::trace("ActivePage renderResponse()", TActivePageAdapter::class);
		//renders all the defered render() calls.
		foreach ($this->_controlsToRender as $rid => $forRender) {
			$this->renderDeferredControl($forRender[0], $forRender[1]);
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

		//sends a fresh CSRF token, since a login or logout during the callback replaces the secret
		if ($this->getPage()->getEnableCsrfValidation()) {
			$this->appendContentPart($response, self::CALLBACK_CSRFTOKEN_HEADER, $this->getRequest()->getCsrfToken());
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


		$cs = $this->getPage()->getClientScript();

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
	 * @return null|\Prado\Web\UI\ActiveControls\IActiveControl|\Prado\Web\UI\TControl the control responsible for the current callback event,
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
	 * @param \Prado\Web\UI\TControl $control control registered to raise callback event.
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
			$this->_callbackClient = new TCallbackClientScript();
		}
		return $this->_callbackClient;
	}
}
