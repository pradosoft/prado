<?php

/**
 * THttpResponseAdatper class
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TInvalidOperationException;

/**
 * THttpResponseAdapter class.
 *
 * THttpResponseAdapter allows the base http response class to change behavior
 * without change the class hierarchy.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0
 */
class THttpResponseAdapter extends \Prado\TApplicationComponent
{
	/**
	 * @var THttpResponse the response object the adapter is attached.
	 */
	private $_response;

	/**
	 * Constructor. Attach a response to be adapted.
	 * @param THttpResponse $response the response object the adapter is to attach to.
	 */
	public function __construct($response)
	{
		$this->_response = $response;
		parent::__construct();
	}

	/**
	 * @return THttpResponse the response object adapted.
	 */
	public function getResponse()
	{
		return $this->_response;
	}

	/**
	 * This method is invoked when the response flushes the content and headers.
	 * Default implementation calls the attached response flushContent method.
	 * @param bool $continueBuffering
	 */
	public function flushContent($continueBuffering = true)
	{
		$this->_response->flushContent($continueBuffering);
	}

	/**
	 * This method is invoked when the response is to redirect to another page.
	 * @param string $url new url to redirect to.
	 */
	public function httpRedirect($url)
	{
		$this->_response->httpRedirect($url);
	}

	/**
	 * This method is invoked when a new HtmlWriter needs to be created.
	 * Default implementation calls the attached response createNewHtmlWriter method.
	 * @param string $type type of the HTML writer to be created.
	 * @param \Prado\IO\ITextWriter $writer the writer responsible for holding the content.
	 */
	public function createNewHtmlWriter($type, $writer)
	{
		return $this->_response->createNewHtmlWriter($type, $writer);
	}

	/**
	 * @param mixed $data
	 * @throws TInvalidOperationException
	 */
	public function setResponseData($data)
	{
		throw new TInvalidOperationException('httpresponse_responsedata_method_unavailable', __METHOD__);
	}

	/**
	 * @throws TInvalidOperationException
	 */
	public function getResponseData()
	{
		throw new TInvalidOperationException('httpresponse_responsedata_method_unavailable', __METHOD__);
	}
}
