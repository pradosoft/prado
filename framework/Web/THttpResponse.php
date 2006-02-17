<?php
/**
 * THttpResponse class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web
 */

/**
 * THttpResponse class
 *
 * THttpResponse implements the mechanism for sending output to client users.
 *
 * To output a string to client, use {@link write()}. By default, the output is
 * buffered until {@link flush()} is called or the application ends. The output in
 * the buffer can also be cleaned by {@link clear()}. To disable output buffering,
 * set BufferOutput property to false.
 *
 * To send cookies to client, use {@link getCookies()}.
 * To redirect client browser to a new URL, use {@link redirect()}.
 * To send a file to client, use {@link writeFile()}.
 *
 * By default, THttpResponse is registered with {@link TApplication} as the
 * response module. It can be accessed via {@link TApplication::getResponse()}.
 *
 * THttpRequest may be configured in application configuration file as follows
 * <module id="response" CacheExpire="20" CacheControl="nocache" BufferOutput="true" />
 * where {@link getCacheExpire CacheExpire}, {@link getCacheControl CacheControl}
 * and {@link getBufferOutput BufferOutput} are configurable properties of THttpResponse.
 *
 * When sending headers the Charset set in {@link TGlobalization::getCharset()}
 * is use when Charset is null or empty in THttpResponse.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class THttpResponse extends TModule implements ITextWriter
{
	/**
	 * @var boolean whether to buffer output
	 */
	private $_bufferOutput=true;
	/**
	 * @var boolean if the application is initialized
	 */
	private $_initialized=false;
	/**
	 * @var THttpCookieCollection list of cookies to return
	 */
	private $_cookies=null;
	/**
	 * @var integer response status code
	 */
	private $_status=200;
	/**
	 * @var string HTML writer type
	 */
	private $_htmlWriterType='System.Web.UI.THtmlWriter';
	/**
	 * @var string content type
	 */
	private $_contentType='text/html';
	/**
	 * @var string character set, e.g. UTF-8
	 */
	private $_charset;

	/**
	 * Destructor.
	 * Flushes any existing content in buffer.
	 */
	public function __destruct()
	{
		if($this->_bufferOutput)
			@ob_end_flush();
	}

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It starts output buffer if it is enabled.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
		if($this->_bufferOutput)
			ob_start();
		$this->_initialized=true;
		$this->getApplication()->setResponse($this);
	}

	/**
	 * @return integer time-to-live for cached session pages in minutes, this has no effect for nocache limiter. Defaults to 180.
	 */
	public function getCacheExpire()
	{
		return session_cache_expire();
	}

	/**
	 * @param integer time-to-live for cached session pages in minutes, this has no effect for nocache limiter.
	 */
	public function setCacheExpire($value)
	{
		session_cache_expire(TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return string cache control method to use for session pages
	 */
	public function getCacheControl()
	{
		return session_cache_limiter();
	}

	/**
	 * @param string cache control method to use for session pages. Valid values
	 *               include none/nocache/private/private_no_expire/public
	 */
	public function setCacheControl($value)
	{
		session_cache_limiter(TPropertyValue::ensureEnum($value,array('none','nocache','private','private_no_expire','public')));
	}

	/**
	 * @string content type, default is text/html
	 */
	public function setContentType($type)
	{
		$this->_contentType = $type;
	}

	/**
	 * @return string current content type
	 */
	public function getContentType()
	{
		return $this->_contentType;
	}

	/**
	 * @return string output charset.
	 */
	public function getCharset()
	{
		return $this->_charset;
	}

	/**
	 * @param string output charset.
	 */
	public function setCharset($charset)
	{
		$this->_charset = $charset;
	}

	/**
	 * @return boolean whether to enable output buffer
	 */
	public function getBufferOutput()
	{
		return $this->_bufferOutput;
	}

	/**
	 * @param boolean whether to enable output buffer
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setBufferOutput($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('httpresponse_bufferoutput_unchangeable');
		else
			$this->_bufferOutput=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return integer HTTP status code, defaults to 200
	 */
	public function getStatusCode()
	{
		return $this->_status;
	}

	/**
	 * @param integer HTTP status code
	 */
	public function setStatusCode($status)
	{
		$this->_status=TPropertyValue::ensureInteger($status);
	}

	/**
	 * @return THttpCookieCollection list of output cookies
	 */
	public function getCookies()
	{
		if($this->_cookies===null)
			$this->_cookies=new THttpCookieCollection($this);
		return $this->_cookies;
	}

	/**
	 * Outputs a string.
	 * It may not be sent back to user immediately if output buffer is enabled.
	 * @param string string to be output
	 */
	public function write($str)
	{
		echo $str;
	}

	/**
	 * Sends a file back to user.
	 * Make sure not to output anything else after calling this method.
	 * @param string file name
	 * @throws TInvalidDataValueException if the file cannot be found
	 */
	public function writeFile($fileName)
	{
		static $defaultMimeTypes=array(
			'css'=>'text/css',
			'gif'=>'image/gif',
			'jpg'=>'image/jpeg',
			'jpeg'=>'image/jpeg',
			'htm'=>'text/html',
			'html'=>'text/html',
			'js'=>'javascript/js'
		);

		if(!is_file($fileName))
			throw new TInvalidDataValueException('httpresponse_file_inexistent',$fileName);
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Component: must-revalidate, post-check=0, pre-check=0');
		$mimeType='text/plain';
		if(function_exists('mime_content_type'))
			$mimeType=mime_content_type($fileName);
		else
		{
			$ext=array_pop(explode('.',$fileName));
			if(isset($defaultMimeTypes[$ext]))
				$mimeType=$defaultMimeTypes[$ext];
		}
		$fn=basename($fileName);
		header("Content-type: $mimeType");
		header('Content-Length: '.filesize($fileName));
		header("Content-Disposition: attachment; filename=\"$fn\"");
		header('Content-Transfer-Encoding: binary');
		readfile($fileName);
	}

	/**
	 * Redirects the browser to the specified URL.
	 * The current application will be terminated after this method is invoked.
	 * @param string URL to be redirected to
	 */
	public function redirect($url)
	{
		if(!$this->getApplication()->getRequestCompleted())
			$this->getApplication()->onEndRequest();
		header('Location:'.$url);
		exit();
	}

	/**
	 * Outputs the buffered content, sends content-type and charset header.
	 */
	public function flush()
	{
		Prado::trace("Flushing output",'System.Web.THttpResponse');
		$this->sendContentTypeHeader();
		if($this->_bufferOutput)
			ob_flush();
	}

	/**
	 * Sends content type header if charset is not empty.
	 */
	protected function sendContentTypeHeader()
	{
		$charset = $this->getCharset();
		if(empty($charset) && ($globalization=$this->getApplication()->getGlobalization())!==null)
			$charset = $globalization->getCharset();
		if(!empty($charset))
		{
			$header='Content-Type: '.$this->getContentType().';charset='.$charset;
			$this->appendHeader($header);
		}
	}

	/**
	 * Returns the content in the output buffer.
	 * The buffer will NOT be cleared after calling this method.
	 * Use {@link clear()} is you want to clear the buffer.
	 * @return string output that is in the buffer.
	 */
	public function getContents()
	{
		Prado::trace("Retrieving output",'System.Web.THttpResponse');
		return $this->_bufferOutput?ob_get_contents():'';
	}

	/**
	 * Clears any existing buffered content.
	 */
	public function clear()
	{
		if($this->_bufferOutput)
			ob_clean();
		Prado::trace("Clearing output",'System.Web.THttpResponse');
	}

	/**
	 * Sends a header.
	 * @param string header
	 */
	public function appendHeader($value)
	{
		Prado::trace("Sending header '$value'",'System.Web.THttpResponse');
		header($value);
	}

	/**
	 * Writes a log message into error log.
	 * This method is simple wrapper of PHP function error_log.
	 * @param string The error message that should be logged
	 * @param integer where the error should go
	 * @param string The destination. Its meaning depends on the message parameter as described above
	 * @param string The extra headers. It's used when the message parameter is set to 1. This message type uses the same internal function as mail() does.
	 * @see http://us2.php.net/manual/en/function.error-log.php
	 */
	public function appendLog($message,$messageType=0,$destination='',$extraHeaders='')
	{
		error_log($message,$messageType,$destination,$extraHeaders);
	}

	/**
	 * Sends a cookie.
	 * Do not call this method directly. Operate with the result of {@link getCookies} instead.
	 * @param THttpCookie cook to be sent
	 */
	public function addCookie($cookie)
	{
		$request=$this->getRequest();
		if($request->getEnableCookieValidation())
		{
			$value=$this->getApplication()->getSecurityManager()->hashData($cookie->getValue());
			setcookie($cookie->getName(),$value,$cookie->getExpire(),$cookie->getPath(),$cookie->getDomain(),$cookie->getSecure());
		}
		else
			setcookie($cookie->getName(),$cookie->getValue(),$cookie->getExpire(),$cookie->getPath(),$cookie->getDomain(),$cookie->getSecure());
	}

	/**
	 * Deletes a cookie.
	 * Do not call this method directly. Operate with the result of {@link getCookies} instead.
	 * @param THttpCookie cook to be deleted
	 */
	public function removeCookie($cookie)
	{
		setcookie($cookie->getName(),null,0,$cookie->getPath(),$cookie->getDomain(),$cookie->getSecure());
	}

	/**
	 * @return string the type of HTML writer to be used, defaults to THtmlWriter
	 */
	public function getHtmlWriterType()
	{
		return $this->_htmlWriterType;
	}

	/**
	 * @param string the type of HTML writer to be used, may be the class name or the namespace
	 */
	public function setHtmlWriterType($value)
	{
		$this->_htmlWriterType=$value;
	}

	/**
	 * Creates a new instance of HTML writer.
	 * If the type of the HTML writer is not supplied, {@link getHtmlWriterType HtmlWriterType} will be assumed.
	 * @param string type of the HTML writer to be created. If null, {@link getHtmlWriterType HtmlWriterType} will be assumed.
	 */
	public function createHtmlWriter($type=null)
	{
		if($type===null)
			$type=$this->_htmlWriterType;
		return Prado::createComponent($type,$this);
	}
}

?>