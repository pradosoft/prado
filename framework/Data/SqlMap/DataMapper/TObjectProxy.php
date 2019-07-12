<?php
/**
 * TLazyLoadList, TObjectProxy classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

/**
 * TObjectProxy sets up a simple object that intercepts method calls to a
 * particular object and relays the call to handler object.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
class TObjectProxy
{
	private $_object;
	private $_handler;

	/**
	 * @param object $handler handler to method calls.
	 * @param object $object the object to by proxied.
	 */
	public function __construct($handler, $object)
	{
		$this->_handler = $handler;
		$this->_object = $object;
	}

	/**
	 * Relay the method call to the handler object (if able to be handled), otherwise
	 * it calls the proxied object's method.
	 * @param string $method method name called
	 * @param array $params method arguments
	 * @return mixed method return value.
	 */
	public function __call($method, $params)
	{
		if ($this->_handler->hasMethod($method)) {
			return $this->_handler->intercept($method, $params);
		} else {
			return call_user_func_array([$this->_object, $method], $params);
		}
	}
}
