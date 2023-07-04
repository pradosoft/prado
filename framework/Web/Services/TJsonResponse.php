<?php
/**
 * TJsonService and TJsonResponse class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

/**
 * TJsonResponse Class
 *
 * TJsonResponse is the base class for all JSON response provider classes.
 *
 * Derived classes must implement {@see getJsonContent()} to return
 * an object or literals to be converted to JSON format. The response
 * will be empty if the returned content is null.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
abstract class TJsonResponse extends \Prado\TApplicationComponent
{
	private $_id = '';

	/**
	 * Initializes the feed.
	 * @param \Prado\Xml\TXmlElement $config configurations specified in {@see \Prado\Web\Services\TJsonService}.
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
	 * @param string $value ID of this response
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}

	/**
	 * @return object json response content, null to suppress output.
	 */
	abstract public function getJsonContent();
}
