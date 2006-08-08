<?php
/**
 * TCallbackResponseAdapter and TCallbackResponseWriter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version 3.0
 * @package System.Web.UI.ActiveControls
 */

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
 * @version $Revision: $  Sun Jun 18 07:52:14 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TCallbackResponseAdapter extends THttpResponseAdapter
{
	/**
	 * @TCallbackResponseWriter[] list of writers.
	 */
	private $_writers=array();
	/**
	 * @mixed callback response data.
	 */
	private $_data;

	/**
	 * Returns a new instance of THtmlWriter.
	 * An instance of TCallbackResponseWriter is created to hold the content.
	 * @param string writer class name.
	 * @param THttpResponse http response handler.
	 */
	public function createNewHtmlWriter($type,$response)
	{
		$writer = new TCallbackResponseWriter();
		$this->_writers[] = $writer;
		return parent::createNewHtmlWriter($type,$writer);
	}

	/**
	 * Flushes the contents in the writers.
	 */
	public function flushContent()
	{
		foreach($this->_writers as $writer)
			echo $writer->flush();
		parent::flushContent();
	}

	/**
	 * @param mixed callback response data.
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
}

/**
 * TCallbackResponseWriter class.
 *
 * TCallbackResponseWriter class enclosed a chunck of content within a
 * html comment boundary. This allows multiple chuncks of content to return
 * in the callback response and update multiple HTML elements.
 *
 * The {@link setBoundary Boundary} property sets boundary identifier in the
 * HTML comment that forms the boundary. By default, the boundary identifier
 * is generated from the object instance ID.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Sun Jun 18 08:02:21 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TCallbackResponseWriter extends TTextWriter
{
	/**
	 * @var string boundary ID
	 */
	private $_boundary;

	/**
	 * Constructor. Generates boundary ID using object instance ID.
	 */
	public function __construct()
	{
		$this->_boundary = sprintf('%x',crc32(time()));
	}

	/**
	 * @return string boundary identifier.
	 */
	public function getBoundary()
	{
		return $this->_boundary;
	}

	/**
	 * @param string boundary identifier.
	 */
	public function setBoundary($value)
	{
		$this->_boundary = $value;
	}

	/**
	 * Returns the text content wrapped within a HTML comment with boundary
	 * identifier as its comment content.
	 * @return string text content chunck.
	 */
	public function flush()
	{
		$content = '<!--'.$this->getBoundary().'-->';
		$content .= parent::flush();
		$content .= '<!--//'.$this->getBoundary().'-->';
		return $content;
	}
}

?>