<?php
/**
 * TJsonService and TJsonResponse class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.Services
 */

/**
 * TJsonResponse Class
 *
 * TJsonResponse is the base class for all JSON response provider classes.
 *
 * Derived classes must implement {@link getJsonContent()} to return
 * an object or literals to be converted to JSON format. The response
 * will be empty if the returned content is null.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.Services
 * @since 3.1
 */
abstract class TJsonResponse extends TApplicationComponent
{
	private $_id='';

	/**
	 * Initializes the feed.
	 * @param TXmlElement configurations specified in {@link TJsonService}.
	 */
	public function init($config)
	{
	}

	/**
	 * @return string ID of this response
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string ID of this response
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return object json response content, null to suppress output.
	 */
	abstract public function getJsonContent();
}