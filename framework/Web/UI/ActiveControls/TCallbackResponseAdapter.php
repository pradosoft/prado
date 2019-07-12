<?php
/**
 * TCallbackResponseAdapter and TCallbackResponseWriter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\THttpResponseAdapter;

/**
 * TCallbackResponseAdapter alters the THttpResponse's outputs.
 *
 * A TCallbackResponseWriter is used instead of the TTextWrite when
 * createHtmlWriter is called. Each call to createHtmlWriter will create
 * a new TCallbackResponseWriter. When flushContent() is called each
 * instance of TCallbackResponseWriter's content is flushed.
 *
 * The callback response data can be set using the {@link setResponseData ResponseData}
 * property.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackResponseAdapter extends THttpResponseAdapter
{
	/**
	 * @var TCallbackResponseWriter[] list of writers.
	 */
	private $_writers = [];
	/**
	 * @var mixed callback response data.
	 */
	private $_data;

	private $_redirectUrl;

	/**
	 * Returns a new instance of THtmlWriter.
	 * An instance of TCallbackResponseWriter is created to hold the content.
	 * @param string $type writer class name.
	 * @param THttpResponse $response http response handler.
	 */
	public function createNewHtmlWriter($type, $response)
	{
		$writer = new TCallbackResponseWriter();
		$this->_writers[] = $writer;
		return parent::createNewHtmlWriter($type, $writer);
	}

	/**
	 * Flushes the contents in the writers.
	 */
	public function flushContent()
	{
		foreach ($this->_writers as $writer) {
			echo $writer->flush();
		}
		parent::flushContent();
	}

	/**
	 * @param mixed $data callback response data.
	 */
	public function setResponseData($data)
	{
		$this->_data = $data;
	}

	/**
	 * @return mixed callback response data.
	 */
	public function getResponseData()
	{
		return $this->_data;
	}

	/**
	 * Delay the redirect until we process the rest of the page.
	 * @param string $url new url to redirect to.
	 */
	public function httpRedirect($url)
	{
		if ($url[0] === '/') {
			$url = $this->getRequest()->getBaseUrl() . $url;
		}
		$this->_redirectUrl = str_replace('&amp;', '&', $url);
	}

	/**
	 * @return string new url for callback response to redirect to.
	 */
	public function getRedirectedUrl()
	{
		return $this->_redirectUrl;
	}
}
