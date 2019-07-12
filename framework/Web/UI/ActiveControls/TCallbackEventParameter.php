<?php
/**
 * TCallbackEventParameter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\THttpResponse;

/**
 * TCallbackEventParameter class.
 *
 * The TCallbackEventParameter provides the parameter passed during the callback
 * request in the {@link getCallbackParameter CallbackParameter} property. The
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
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackEventParameter extends \Prado\TEventParameter
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
	 * @param mixed $response
	 * @param mixed $parameter
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
	 * @param mixed $value callback response data.
	 */
	public function setResponseData($value)
	{
		$this->_response->getAdapter()->setResponseData($value);
	}

	/**
	 * @return mixed callback response data.
	 */
	public function getResponseData()
	{
		return $this->_response->getAdapter()->getResponseData();
	}
}
